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
 * PeerForum data mapper.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\data_mappers\legacy;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\peerforum as peerforum_entity;
use stdClass;

/**
 * Convert a peerforum entity into an stdClass.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerforum {
    /**
     * Convert a list of peerforum entities into stdClasses.
     *
     * @param peerforum_entity[] $peerforums The peerforums to convert.
     * @return stdClass[]
     */
    public function to_legacy_objects(array $peerforums): array {
        return array_map(function(peerforum_entity $peerforum) {
            return (object) [
                    'id' => $peerforum->get_id(),
                    'course' => $peerforum->get_course_id(),
                    'type' => $peerforum->get_type(),
                    'name' => $peerforum->get_name(),
                    'intro' => $peerforum->get_intro(),
                    'introformat' => $peerforum->get_intro_format(),
                    'assessed' => $peerforum->get_rating_aggregate(),
                    'assesstimestart' => $peerforum->get_assess_time_start(),
                    'assesstimefinish' => $peerforum->get_assess_time_finish(),
                    'scale' => $peerforum->get_scale(),
                    'grade_peerforum' => $peerforum->get_grade_for_peerforum(),
                    'grade_peerforum_notify' => $peerforum->should_notify_students_default_when_grade_for_peerforum(),
                    'maxbytes' => $peerforum->get_max_bytes(),
                    'maxattachments' => $peerforum->get_max_attachments(),
                    'forcesubscribe' => $peerforum->get_subscription_mode(),
                    'trackingtype' => $peerforum->get_tracking_type(),
                    'rsstype' => $peerforum->get_rss_type(),
                    'rssarticles' => $peerforum->get_rss_articles(),
                    'timemodified' => $peerforum->get_time_modified(),
                    'warnafter' => $peerforum->get_warn_after(),
                    'blockafter' => $peerforum->get_block_after(),
                    'blockperiod' => $peerforum->get_block_period(),
                    'completiondiscussions' => $peerforum->get_completion_discussions(),
                    'completionreplies' => $peerforum->get_completion_replies(),
                    'completionposts' => $peerforum->get_completion_posts(),
                    'displaywordcount' => $peerforum->should_display_word_count(),
                    'lockdiscussionafter' => $peerforum->get_lock_discussions_after(),
                    'duedate' => $peerforum->get_due_date(),
                    'cutoffdate' => $peerforum->get_cutoff_date()
            ];
        }, $peerforums);
    }

    /**
     * Convert a peerforum entity into an stdClass.
     *
     * @param peerforum_entity $peerforum The peerforum to convert.
     * @return stdClass
     */
    public function to_legacy_object(peerforum_entity $peerforum): stdClass {
        return $this->to_legacy_objects([$peerforum])[0];
    }
}
