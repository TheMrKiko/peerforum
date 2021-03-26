<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for peerforum cron.
 *
 * @package    mod_peerforum
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\task;

defined('MOODLE_INTERNAL') || die();

/**
 * The main scheduled task for the peerforum.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expire_assignments extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cronexpiretask', 'peerforum');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        $this->log_start("Fetching active assignments.");
        $assigns = $this->get_active_assignments();
        if (empty($assigns)) {
            $this->log_finish("No active assignments found.", 1);
            return false;
        }
        $this->log_finish(sprintf("Done. Found %s active assignments.", count($assigns)));

        $this->log_start("Filling caches");
        $peerforumvault = \mod_peerforum\local\container::get_vault_factory()->get_peerforum_vault();
        $contexts = array();
        $peerforums = array();
        $finishpeergrades = array();
        $itemsthatshouldend = array();
        foreach ($assigns as $assign) {
            $contextid = $assign->contextid;

            if (isset($contexts[$contextid])) {
                continue;
            }

            $contexts[$contextid] = \context::instance_by_id($contextid);
            $pfid = $contexts[$contextid]->instanceid;

            $peerforum = $peerforumvault->get_from_course_module_id($pfid);
            $peerforums[$contextid] = $peerforum;
            $finishpeergrades[$contextid] = $peerforum->get_finishpeergrade();
        }
        $this->log_finish("All caches filled");

        $this->log_start("Processing assignments");
        $time = time();
        $assignsshouldend = [];
        $assignsshouldexpire = [];
        foreach ($assigns as $assign) {
            $contextid = $assign->contextid;
            $peerforum = $peerforums[$contextid];
            $finishpeergrades = $finishpeergrades[$contextid];
            $itemid = $assign->itemid;

            $assignshouldend = false;

            if ($finishpeergrades) {
                if (isset($itemsthatshouldend[$itemid])) {
                    $assignshouldend = $itemsthatshouldend[$itemid];
                } else {
                    $graded = $DB->count_records_select('peerforum_time_assigned',
                            'itemid = ' . $itemid . ' AND peergraded <> 0');

                    $assignshouldend = $itemsthatshouldend[$itemid] = $graded >= $peerforum->get_minpeergraders();
                }
            }
            if (!$assignshouldend) {
                $timeassigned = $assign->timeassigned;
                $timetilexpire = $peerforum->get_timetopeergrade() * DAYSECS;
                $assignshouldend = $time > $timeassigned + $timetilexpire;
                if ($assignshouldend && empty($assign->ublocked)) {
                    $assignsshouldexpire[] = $assign->id;
                }
            }

            if ($assignshouldend) {
                $assignsshouldend[] = $assign->id;
            }
        }
        $this->log_finish("Assignments processed");

        if (empty($assignsshouldend)) {
            $this->log("No assignments should end.");
            return false;
        }

        $this->log_start("Writing to database");
        // Mark assigns as ended.
        list($in, $params) = $DB->get_in_or_equal($assignsshouldend);
        $DB->set_field_select('peerforum_time_assigned', 'ended', 1, "id {$in}", $params);

        // Mark assigns as expired.
        list($in, $params) = $DB->get_in_or_equal($assignsshouldexpire);
        $DB->set_field_select('peerforum_time_assigned', 'expired', 1, "id {$in}", $params);
        $this->log_finish(
                sprintf(
                        "Ended a total of %d active assignment, %d of which expired",
                        count($assignsshouldend),
                        count($assignsshouldexpire),
                ));
    }

    protected function get_active_assignments() {
        global $DB;

        return $DB->get_records_sql(
                "SELECT
                    a.id,
                    a.itemid,
                    a.postid,
                    a.timeassigned,
                    a.ended,
                    a.contextid,
                    a.component,
                    a.peergradearea,
                    b.id AS ublocked
                  FROM {peerforum_time_assigned} a
                 LEFT JOIN {peerforum_user_block} b ON a.userid = b.userid
                 WHERE a.ended = 0");
    }
}