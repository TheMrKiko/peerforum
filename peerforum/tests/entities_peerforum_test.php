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
 * The peerforum entity tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/rating/lib.php');

use mod_peerforum\local\entities\discussion as discussion_entity;
use mod_peerforum\local\entities\peerforum as peerforum_entity;

/**
 * The peerforum entity tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_entities_peerforum_testcase extends advanced_testcase {
    /**
     * Test the entity returns expected values.
     */
    public function test_entity() {
        $this->resetAfterTest();

        $time = time() - 10;
        $discussion = new discussion_entity(
                1,
                2,
                3,
                'test discussion',
                4,
                5,
                6,
                false,
                $time,
                $time,
                0,
                0,
                false,
                0
        );

        $past = time() - 100;
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($coursemodule->id);
        $effectivegroupmode = NOGROUPS;
        $id = 1;
        $courseid = 2;
        $type = 'standard';
        $name = 'test peerforum';
        $intro = 'this is the intro';
        $introformat = FORMAT_MOODLE;
        $assessed = RATING_AGGREGATE_NONE;
        $assesstimestart = 0;
        $assesstimefinish = 0;
        $scale = 0;
        $gradepeerforum = 0;
        $maxbytes = 200;
        $maxattachments = 5;
        $forcesubscribe = 0;
        $trackingtype = 1;
        $rsstype = 0;
        $rssarticles = 0;
        $timemodified = $past;
        $warnafter = 0;
        $blockafter = 0;
        $blockperiod = 0;
        $completiondiscussions = 0;
        $completionreplies = 0;
        $completionposts = 0;
        $displaywordcount = false;
        $lockdiscussionafter = 0;
        $duedate = 0;
        $cutoffdate = 0;
        $sendnotification = false;
        $peerforum = new peerforum_entity(
                $context,
                $coursemodule,
                $course,
                $effectivegroupmode,
                $id,
                $courseid,
                $type,
                $name,
                $intro,
                $introformat,
                $assessed,
                $assesstimestart,
                $assesstimefinish,
                $scale,
                $gradepeerforum,
                $sendnotification,
                $maxbytes,
                $maxattachments,
                $forcesubscribe,
                $trackingtype,
                $rsstype,
                $rssarticles,
                $timemodified,
                $warnafter,
                $blockafter,
                $blockperiod,
                $completiondiscussions,
                $completionreplies,
                $completionposts,
                $displaywordcount,
                $lockdiscussionafter,
                $duedate,
                $cutoffdate
        );

        $this->assertEquals($context, $peerforum->get_context());
        $this->assertEquals($coursemodule, $peerforum->get_course_module_record());
        $this->assertEquals($coursemodule, $peerforum->get_course_module_record());
        $this->assertEquals($effectivegroupmode, $peerforum->get_effective_group_mode());
        $this->assertEquals(false, $peerforum->is_in_group_mode());
        $this->assertEquals($course, $peerforum->get_course_record());
        $this->assertEquals($id, $peerforum->get_id());
        $this->assertEquals($courseid, $peerforum->get_course_id());
        $this->assertEquals($name, $peerforum->get_name());
        $this->assertEquals($intro, $peerforum->get_intro());
        $this->assertEquals($introformat, $peerforum->get_intro_format());
        $this->assertEquals($assessed, $peerforum->get_rating_aggregate());
        // Rating aggregate is set to none.
        $this->assertEquals(false, $peerforum->has_rating_aggregate());
        $this->assertEquals($assesstimestart, $peerforum->get_assess_time_start());
        $this->assertEquals($assesstimefinish, $peerforum->get_assess_time_finish());
        $this->assertEquals($scale, $peerforum->get_scale());
        $this->assertEquals($gradepeerforum, $peerforum->get_grade_for_peerforum());
        $this->assertEquals($maxbytes, $peerforum->get_max_bytes());
        $this->assertEquals($maxattachments, $peerforum->get_max_attachments());
        $this->assertEquals($forcesubscribe, $peerforum->get_subscription_mode());
        $this->assertEquals($trackingtype, $peerforum->get_tracking_type());
        $this->assertEquals($rsstype, $peerforum->get_rss_type());
        $this->assertEquals($rssarticles, $peerforum->get_rss_articles());
        $this->assertEquals($timemodified, $peerforum->get_time_modified());
        $this->assertEquals($warnafter, $peerforum->get_warn_after());
        $this->assertEquals($blockafter, $peerforum->get_block_after());
        $this->assertEquals($blockperiod, $peerforum->get_block_period());
        $this->assertEquals(false, $peerforum->has_blocking_enabled());
        $this->assertEquals($completiondiscussions, $peerforum->get_completion_discussions());
        $this->assertEquals($completionreplies, $peerforum->get_completion_replies());
        $this->assertEquals($completionposts, $peerforum->get_completion_posts());
        $this->assertEquals($displaywordcount, $peerforum->should_display_word_count());
        $this->assertEquals($lockdiscussionafter, $peerforum->get_lock_discussions_after());
        $this->assertEquals(false, $peerforum->has_lock_discussions_after());
        $this->assertEquals(false, $peerforum->is_discussion_locked($discussion));
        $this->assertEquals(false, $peerforum->has_due_date());
        $this->assertEquals(false, $peerforum->is_due_date_reached());
        $this->assertEquals(false, $peerforum->has_cutoff_date());
        $this->assertEquals(false, $peerforum->is_cutoff_date_reached());
    }
}
