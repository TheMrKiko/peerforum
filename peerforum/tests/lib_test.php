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
 * The module peerforums tests
 *
 * @package    mod_peerforum
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/mod/peerforum/locallib.php');
require_once($CFG->dirroot . '/rating/lib.php');

class mod_peerforum_lib_testcase extends advanced_testcase {

    public function setUp(): void {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_peerforum\subscriptions::reset_peerforum_cache();
    }

    public function tearDown(): void {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_peerforum\subscriptions::reset_peerforum_cache();
    }

    public function test_peerforum_trigger_content_uploaded_event() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $this->setUser($user->id);
        $fakepost = (object) array('id' => 123, 'message' => 'Yay!', 'discussion' => 100);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        $fs = get_file_storage();
        $dummy = (object) array(
                'contextid' => $context->id,
                'component' => 'mod_peerforum',
                'filearea' => 'attachment',
                'itemid' => $fakepost->id,
                'filepath' => '/',
                'filename' => 'myassignmnent.pdf'
        );
        $fi = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        $data = new stdClass();
        $sink = $this->redirectEvents();
        peerforum_trigger_content_uploaded_event($fakepost, $cm, 'some triggered from value');
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_peerforum\event\assessable_uploaded', $event);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($fakepost->id, $event->objectid);
        $this->assertEquals($fakepost->message, $event->other['content']);
        $this->assertEquals($fakepost->discussion, $event->other['discussionid']);
        $this->assertCount(1, $event->other['pathnamehashes']);
        $this->assertEquals($fi->get_pathnamehash(), $event->other['pathnamehashes'][0]);
        $expected = new stdClass();
        $expected->modulename = 'peerforum';
        $expected->name = 'some triggered from value';
        $expected->cmid = $peerforum->cmid;
        $expected->itemid = $fakepost->id;
        $expected->courseid = $course->id;
        $expected->userid = $user->id;
        $expected->content = $fakepost->message;
        $expected->pathnamehashes = array($fi->get_pathnamehash());
        $this->assertEventLegacyData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_peerforum_get_courses_user_posted_in() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Create 3 peerforums, one in each course.
        $record = new stdClass();
        $record->course = $course1->id;
        $peerforum1 = $this->getDataGenerator()->create_module('peerforum', $record);

        $record = new stdClass();
        $record->course = $course2->id;
        $peerforum2 = $this->getDataGenerator()->create_module('peerforum', $record);

        $record = new stdClass();
        $record->course = $course3->id;
        $peerforum3 = $this->getDataGenerator()->create_module('peerforum', $record);

        // Add a second peerforum in course 1.
        $record = new stdClass();
        $record->course = $course1->id;
        $peerforum4 = $this->getDataGenerator()->create_module('peerforum', $record);

        // Add discussions to course 1 started by user1.
        $record = new stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $record = new stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum4->id;
        $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add discussions to course2 started by user1.
        $record = new stdClass();
        $record->course = $course2->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum2->id;
        $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add discussions to course 3 started by user2.
        $record = new stdClass();
        $record->course = $course3->id;
        $record->userid = $user2->id;
        $record->peerforum = $peerforum3->id;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add post to course 3 by user1.
        $record = new stdClass();
        $record->course = $course3->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum3->id;
        $record->discussion = $discussion3->id;
        $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // User 3 hasn't posted anything, so shouldn't get any results.
        $user3courses = peerforum_get_courses_user_posted_in($user3);
        $this->assertEmpty($user3courses);

        // User 2 has only posted in course3.
        $user2courses = peerforum_get_courses_user_posted_in($user2);
        $this->assertCount(1, $user2courses);
        $user2course = array_shift($user2courses);
        $this->assertEquals($course3->id, $user2course->id);
        $this->assertEquals($course3->shortname, $user2course->shortname);

        // User 1 has posted in all 3 courses.
        $user1courses = peerforum_get_courses_user_posted_in($user1);
        $this->assertCount(3, $user1courses);
        foreach ($user1courses as $course) {
            $this->assertContains($course->id, array($course1->id, $course2->id, $course3->id));
            $this->assertContains($course->shortname, array($course1->shortname, $course2->shortname,
                    $course3->shortname));

        }

        // User 1 has only started a discussion in course 1 and 2 though.
        $user1courses = peerforum_get_courses_user_posted_in($user1, true);
        $this->assertCount(2, $user1courses);
        foreach ($user1courses as $course) {
            $this->assertContains($course->id, array($course1->id, $course2->id));
            $this->assertContains($course->shortname, array($course1->shortname, $course2->shortname));
        }
    }

    /**
     * Test the logic in the peerforum_tp_can_track_peerforums() function.
     */
    public function test_peerforum_tp_can_track_peerforums() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OFF); // Off.
        $peerforumoff = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_FORCED); // On.
        $peerforumforce = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OPTIONAL); // Optional.
        $peerforumoptional = $this->getDataGenerator()->create_module('peerforum', $options);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        // User on, peerforum off, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, peerforum on, should be on.
        $result = peerforum_tp_can_track_peerforums($peerforumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, peerforum optional, should be on.
        $result = peerforum_tp_can_track_peerforums($peerforumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, peerforum off, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum force, should be on.
        $result = peerforum_tp_can_track_peerforums($peerforumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, peerforum optional, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        // User on, peerforum off, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, peerforum on, should be on.
        $result = peerforum_tp_can_track_peerforums($peerforumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, peerforum optional, should be on.
        $result = peerforum_tp_can_track_peerforums($peerforumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, peerforum off, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum force, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum optional, should be off.
        $result = peerforum_tp_can_track_peerforums($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);

    }

    /**
     * Test the logic in the test_peerforum_tp_is_tracked() function.
     */
    public function test_peerforum_tp_is_tracked() {
        global $CFG;

        $this->resetAfterTest();

        $cache = cache::make('mod_peerforum', 'peerforum_is_tracked');
        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OFF); // Off.
        $peerforumoff = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_FORCED); // On.
        $peerforumforce = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OPTIONAL); // Optional.
        $peerforumoptional = $this->getDataGenerator()->create_module('peerforum', $options);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        // User on, peerforum off, should be off.
        $result = peerforum_tp_is_tracked($peerforumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, peerforum optional, should be on.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, peerforum off, should be off.
        $result = peerforum_tp_is_tracked($peerforumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, peerforum optional, should be off.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);

        $cache->purge();
        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        // User on, peerforum off, should be off.
        $result = peerforum_tp_is_tracked($peerforumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, peerforum optional, should be on.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, peerforum off, should be off.
        $result = peerforum_tp_is_tracked($peerforumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum force, should be off.
        $result = peerforum_tp_is_tracked($peerforumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, peerforum optional, should be off.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Stop tracking so we can test again.
        peerforum_tp_stop_tracking($peerforumforce->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumforce->id, $useroff->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useroff->id);

        $cache->purge();
        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        // User on, preference off, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, preference off, peerforum optional, should be on.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useron);
        $this->assertEquals(false, $result);

        // User off, preference off, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, preference off, peerforum optional, should be off.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);

        $cache->purge();
        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        // User on, preference off, peerforum force, should be on.
        $result = peerforum_tp_is_tracked($peerforumforce, $useron);
        $this->assertEquals(false, $result);

        // User on, preference off, peerforum optional, should be on.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useron);
        $this->assertEquals(false, $result);

        // User off, preference off, peerforum force, should be off.
        $result = peerforum_tp_is_tracked($peerforumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, preference off, peerforum optional, should be off.
        $result = peerforum_tp_is_tracked($peerforumoptional, $useroff);
        $this->assertEquals(false, $result);
    }

    /**
     * Test the logic in the peerforum_tp_get_course_unread_posts() function.
     */
    public function test_peerforum_tp_get_course_unread_posts() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OFF); // Off.
        $peerforumoff = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_FORCED); // On.
        $peerforumforce = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OPTIONAL); // Optional.
        $peerforumoptional = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add discussions to the tracking off peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->peerforum = $peerforumoff->id;
        $discussionoff = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add discussions to the tracking forced peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->peerforum = $peerforumforce->id;
        $discussionforce = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add post to the tracking forced discussion.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useroff->id;
        $record->peerforum = $peerforumforce->id;
        $record->discussion = $discussionforce->id;
        $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // Add discussions to the tracking optional peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->peerforum = $peerforumoptional->id;
        $discussionoptional = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        $result = peerforum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
        $this->assertEquals(2, $result[$peerforumforce->id]->unread);
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));
        $this->assertEquals(1, $result[$peerforumoptional->id]->unread);

        $result = peerforum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
        $this->assertEquals(2, $result[$peerforumforce->id]->unread);
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));

        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        $result = peerforum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
        $this->assertEquals(2, $result[$peerforumforce->id]->unread);
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));
        $this->assertEquals(1, $result[$peerforumoptional->id]->unread);

        $result = peerforum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(false, isset($result[$peerforumforce->id]));
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));

        // Stop tracking so we can test again.
        peerforum_tp_stop_tracking($peerforumforce->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumforce->id, $useroff->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useroff->id);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        $result = peerforum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
        $this->assertEquals(2, $result[$peerforumforce->id]->unread);
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));

        $result = peerforum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
        $this->assertEquals(2, $result[$peerforumforce->id]->unread);
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));

        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        $result = peerforum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(false, isset($result[$peerforumforce->id]));
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));

        $result = peerforum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$peerforumoff->id]));
        $this->assertEquals(false, isset($result[$peerforumforce->id]));
        $this->assertEquals(false, isset($result[$peerforumoptional->id]));
    }

    /**
     * Test the logic in the test_peerforum_tp_get_untracked_peerforums() function.
     */
    public function test_peerforum_tp_get_untracked_peerforums() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OFF); // Off.
        $peerforumoff = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_FORCED); // On.
        $peerforumforce = $this->getDataGenerator()->create_module('peerforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => PEERFORUM_TRACKING_OPTIONAL); // Optional.
        $peerforumoptional = $this->getDataGenerator()->create_module('peerforum', $options);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        // On user with force on.
        $result = peerforum_tp_get_untracked_peerforums($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));

        // Off user with force on.
        $result = peerforum_tp_get_untracked_peerforums($useroff->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));

        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        // On user with force off.
        $result = peerforum_tp_get_untracked_peerforums($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));

        // Off user with force off.
        $result = peerforum_tp_get_untracked_peerforums($useroff->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));

        // Stop tracking so we can test again.
        peerforum_tp_stop_tracking($peerforumforce->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useron->id);
        peerforum_tp_stop_tracking($peerforumforce->id, $useroff->id);
        peerforum_tp_stop_tracking($peerforumoptional->id, $useroff->id);

        // Allow force.
        $CFG->peerforum_allowforcedreadtracking = 1;

        // On user with force on.
        $result = peerforum_tp_get_untracked_peerforums($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));

        // Off user with force on.
        $result = peerforum_tp_get_untracked_peerforums($useroff->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));

        // Don't allow force.
        $CFG->peerforum_allowforcedreadtracking = 0;

        // On user with force off.
        $result = peerforum_tp_get_untracked_peerforums($useron->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));

        // Off user with force off.
        $result = peerforum_tp_get_untracked_peerforums($useroff->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$peerforumoff->id]));
        $this->assertEquals(true, isset($result[$peerforumoptional->id]));
        $this->assertEquals(true, isset($result[$peerforumforce->id]));
    }

    /**
     * Test subscription using automatic subscription on create.
     */
    public function test_peerforum_auto_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_INITIALSUBSCRIBE); // Automatic Subscription.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        $result = \mod_peerforum\subscriptions::fetch_subscribed_users($peerforum);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(\mod_peerforum\subscriptions::is_subscribed($user->id, $peerforum));
        }
    }

    /**
     * Test subscription using forced subscription on create.
     */
    public function test_peerforum_forced_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_FORCESUBSCRIBE); // Forced subscription.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        $result = \mod_peerforum\subscriptions::fetch_subscribed_users($peerforum);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(\mod_peerforum\subscriptions::is_subscribed($user->id, $peerforum));
        }
    }

    /**
     * Test subscription using optional subscription on create.
     */
    public function test_peerforum_optional_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE); // Subscription optional.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        $result = \mod_peerforum\subscriptions::fetch_subscribed_users($peerforum);
        // No subscriptions by default.
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(\mod_peerforum\subscriptions::is_subscribed($user->id, $peerforum));
        }
    }

    /**
     * Test subscription using disallow subscription on create.
     */
    public function test_peerforum_disallow_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_DISALLOWSUBSCRIBE); // Subscription prevented.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        $result = \mod_peerforum\subscriptions::fetch_subscribed_users($peerforum);
        // No subscriptions by default.
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(\mod_peerforum\subscriptions::is_subscribed($user->id, $peerforum));
        }
    }

    /**
     * Test that context fetching returns the appropriate context.
     */
    public function test_peerforum_get_context() {
        global $DB, $PAGE;

        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);
        $peerforumcm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $peerforumcontext = \context_module::instance($peerforumcm->id);

        // First check that specifying the context results in the correct context being returned.
        // Do this before we set up the page object and we should return from the coursemodule record.
        // There should be no DB queries here because the context type was correct.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id, $peerforumcontext);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(0, $aftercount - $startcount);

        // And a context which is not the correct type.
        // This tests will result in a DB query to fetch the course_module.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id, $coursecontext);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(1, $aftercount - $startcount);

        // Now do not specify a context at all.
        // This tests will result in a DB query to fetch the course_module.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(1, $aftercount - $startcount);

        // Set up the default page event to use the peerforum.
        $PAGE = new moodle_page();
        $PAGE->set_context($peerforumcontext);
        $PAGE->set_cm($peerforumcm, $course, $peerforum);

        // Now specify a context which is not a context_module.
        // There should be no DB queries here because we use the PAGE.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id, $coursecontext);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(0, $aftercount - $startcount);

        // Now do not specify a context at all.
        // There should be no DB queries here because we use the PAGE.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(0, $aftercount - $startcount);

        // Now specify the page context of the course instead..
        $PAGE = new moodle_page();
        $PAGE->set_context($coursecontext);

        // Now specify a context which is not a context_module.
        // This tests will result in a DB query to fetch the course_module.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id, $coursecontext);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(1, $aftercount - $startcount);

        // Now do not specify a context at all.
        // This tests will result in a DB query to fetch the course_module.
        $startcount = $DB->perf_get_reads();
        $result = peerforum_get_context($peerforum->id);
        $aftercount = $DB->perf_get_reads();
        $this->assertEquals($peerforumcontext, $result);
        $this->assertEquals(1, $aftercount - $startcount);
    }

    /**
     * Test getting the neighbour threads of a discussion.
     */
    public function test_peerforum_get_neighbours() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $timenow = time();
        $timenext = $timenow;

        // Setup test data.
        $peerforumgen = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($cm->id);

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->timemodified = time();
        $disc1 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc2 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc3 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc4 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc5 = $peerforumgen->create_discussion($record);

        // Getting the neighbours.
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc1, $peerforum);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc2->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEquals($disc1->id, $neighbours['prev']->id);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc3, $peerforum);
        $this->assertEquals($disc2->id, $neighbours['prev']->id);
        $this->assertEquals($disc4->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc4, $peerforum);
        $this->assertEquals($disc3->id, $neighbours['prev']->id);
        $this->assertEquals($disc5->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc5, $peerforum);
        $this->assertEquals($disc4->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Post in some discussions. We manually update the discussion record because
        // the data generator plays with timemodified in a way that would break this test.
        $record->timemodified++;
        $disc1->timemodified = $record->timemodified;
        $DB->update_record('peerforum_discussions', $disc1);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc5, $peerforum);
        $this->assertEquals($disc4->id, $neighbours['prev']->id);
        $this->assertEquals($disc1->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc1, $peerforum);
        $this->assertEquals($disc5->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // After some discussions were created.
        $record->timemodified++;
        $disc6 = $peerforumgen->create_discussion($record);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc6, $peerforum);
        $this->assertEquals($disc1->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        $record->timemodified++;
        $disc7 = $peerforumgen->create_discussion($record);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc7, $peerforum);
        $this->assertEquals($disc6->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Adding timed discussions.
        $CFG->peerforum_enabletimedposts = true;
        $now = $record->timemodified;
        $past = $now - 600;
        $future = $now + 600;

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->timestart = $past;
        $record->timeend = $future;
        $record->timemodified = $now;
        $record->timemodified++;
        $disc8 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = $future;
        $record->timeend = 0;
        $disc9 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = 0;
        $record->timeend = 0;
        $disc10 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = 0;
        $record->timeend = $past;
        $disc11 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = $past;
        $record->timeend = $future;
        $disc12 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = $future + 1; // Should be last post for those that can see it.
        $record->timeend = 0;
        $disc13 = $peerforumgen->create_discussion($record);

        // Admin user ignores the timed settings of discussions.
        // Post ordering taking into account timestart:
        //  8 = t
        // 10 = t+3
        // 11 = t+4
        // 12 = t+5
        //  9 = t+60
        // 13 = t+61.
        $this->setAdminUser();
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc9, $peerforum);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc11->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc11, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc9->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc13, $peerforum);
        $this->assertEquals($disc9->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user can see their own timed discussions.
        $this->setUser($user);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc9, $peerforum);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc11->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc11, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc9->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc13, $peerforum);
        $this->assertEquals($disc9->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user does not ignore timed settings.
        $this->setUser($user2);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Reset to normal mode.
        $CFG->peerforum_enabletimedposts = false;
        $this->setAdminUser();

        // Two discussions with identical timemodified will sort by id.
        $record->timemodified += 25;
        $DB->update_record('peerforum_discussions', (object) array('id' => $disc3->id, 'timemodified' => $record->timemodified));
        $DB->update_record('peerforum_discussions', (object) array('id' => $disc2->id, 'timemodified' => $record->timemodified));
        $DB->update_record('peerforum_discussions',
                (object) array('id' => $disc12->id, 'timemodified' => $record->timemodified - 5));
        $disc2 = $DB->get_record('peerforum_discussions', array('id' => $disc2->id));
        $disc3 = $DB->get_record('peerforum_discussions', array('id' => $disc3->id));

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc3, $peerforum);
        $this->assertEquals($disc2->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        // Set timemodified to not be identical.
        $DB->update_record('peerforum_discussions',
                (object) array('id' => $disc2->id, 'timemodified' => $record->timemodified - 1));

        // Test pinned posts behave correctly.
        $disc8->pinned = PEERFORUM_DISCUSSION_PINNED;
        $DB->update_record('peerforum_discussions', (object) array('id' => $disc8->id, 'pinned' => $disc8->pinned));
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc3->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc3, $peerforum);
        $this->assertEquals($disc2->id, $neighbours['prev']->id);
        $this->assertEquals($disc8->id, $neighbours['next']->id);

        // Test 3 pinned posts.
        $disc6->pinned = PEERFORUM_DISCUSSION_PINNED;
        $DB->update_record('peerforum_discussions', (object) array('id' => $disc6->id, 'pinned' => $disc6->pinned));
        $disc4->pinned = PEERFORUM_DISCUSSION_PINNED;
        $DB->update_record('peerforum_discussions', (object) array('id' => $disc4->id, 'pinned' => $disc4->pinned));

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc6, $peerforum);
        $this->assertEquals($disc4->id, $neighbours['prev']->id);
        $this->assertEquals($disc8->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc4, $peerforum);
        $this->assertEquals($disc3->id, $neighbours['prev']->id);
        $this->assertEquals($disc6->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc6->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
    }

    /**
     * Test getting the neighbour threads of a blog-like peerforum.
     */
    public function test_peerforum_get_neighbours_blog() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $timenow = time();
        $timenext = $timenow;

        // Setup test data.
        $peerforumgen = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'blog'));
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($cm->id);

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->timemodified = time();
        $disc1 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc2 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc3 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc4 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $disc5 = $peerforumgen->create_discussion($record);

        // Getting the neighbours.
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc1, $peerforum);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc2->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEquals($disc1->id, $neighbours['prev']->id);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc3, $peerforum);
        $this->assertEquals($disc2->id, $neighbours['prev']->id);
        $this->assertEquals($disc4->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc4, $peerforum);
        $this->assertEquals($disc3->id, $neighbours['prev']->id);
        $this->assertEquals($disc5->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc5, $peerforum);
        $this->assertEquals($disc4->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Make sure that the thread's timemodified does not affect the order.
        $record->timemodified++;
        $disc1->timemodified = $record->timemodified;
        $DB->update_record('peerforum_discussions', $disc1);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc1, $peerforum);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc2->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEquals($disc1->id, $neighbours['prev']->id);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        // Add another blog post.
        $record->timemodified++;
        $disc6 = $peerforumgen->create_discussion($record);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc6, $peerforum);
        $this->assertEquals($disc5->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        $record->timemodified++;
        $disc7 = $peerforumgen->create_discussion($record);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc7, $peerforum);
        $this->assertEquals($disc6->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Adding timed discussions.
        $CFG->peerforum_enabletimedposts = true;
        $now = $record->timemodified;
        $past = $now - 600;
        $future = $now + 600;

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->timestart = $past;
        $record->timeend = $future;
        $record->timemodified = $now;
        $record->timemodified++;
        $disc8 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = $future;
        $record->timeend = 0;
        $disc9 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = 0;
        $record->timeend = 0;
        $disc10 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = 0;
        $record->timeend = $past;
        $disc11 = $peerforumgen->create_discussion($record);
        $record->timemodified++;
        $record->timestart = $past;
        $record->timeend = $future;
        $disc12 = $peerforumgen->create_discussion($record);

        // Admin user ignores the timed settings of discussions.
        $this->setAdminUser();
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc9->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc9, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc9->id, $neighbours['prev']->id);
        $this->assertEquals($disc11->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc11, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user can see their own timed discussions.
        $this->setUser($user);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc9->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc9, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc9->id, $neighbours['prev']->id);
        $this->assertEquals($disc11->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc11, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user does not ignore timed settings.
        $this->setUser($user2);
        $neighbours = peerforum_get_discussion_neighbours($cm, $disc8, $peerforum);
        $this->assertEquals($disc7->id, $neighbours['prev']->id);
        $this->assertEquals($disc10->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc10, $peerforum);
        $this->assertEquals($disc8->id, $neighbours['prev']->id);
        $this->assertEquals($disc12->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc12, $peerforum);
        $this->assertEquals($disc10->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Reset to normal mode.
        $CFG->peerforum_enabletimedposts = false;
        $this->setAdminUser();

        $record->timemodified++;
        // Two blog posts with identical creation time will sort by id.
        $DB->update_record('peerforum_posts', (object) array('id' => $disc2->firstpost, 'created' => $record->timemodified));
        $DB->update_record('peerforum_posts', (object) array('id' => $disc3->firstpost, 'created' => $record->timemodified));

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc2, $peerforum);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc3->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm, $disc3, $peerforum);
        $this->assertEquals($disc2->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
    }

    /**
     * Test getting the neighbour threads of a discussion.
     */
    public function test_peerforum_get_neighbours_with_groups() {
        $this->resetAfterTest();

        $timenow = time();
        $timenext = $timenow;

        // Setup test data.
        $peerforumgen = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');
        $course = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->create_group_member(array('userid' => $user1->id, 'groupid' => $group1->id));

        $peerforum1 =
                $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'groupmode' => VISIBLEGROUPS));
        $peerforum2 = $this->getDataGenerator()
                ->create_module('peerforum', array('course' => $course->id, 'groupmode' => SEPARATEGROUPS));
        $cm1 = get_coursemodule_from_instance('peerforum', $peerforum1->id);
        $cm2 = get_coursemodule_from_instance('peerforum', $peerforum2->id);
        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);

        // Creating discussions in both peerforums.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group1->id;
        $record->timemodified = time();
        $disc11 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $record->timemodified++;
        $disc21 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user2->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group2->id;
        $disc12 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc22 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = null;
        $disc13 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc23 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user2->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group2->id;
        $disc14 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc24 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group1->id;
        $disc15 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc25 = $peerforumgen->create_discussion($record);

        // Admin user can see all groups.
        $this->setAdminUser();
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc12->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc22->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc12, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc22, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc14->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc22->id, $neighbours['prev']->id);
        $this->assertEquals($disc24->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc14, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc24, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc14->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc24->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Admin user is only viewing group 1.
        $_POST['group'] = $group1->id;
        $this->assertEquals($group1->id, groups_get_activity_group($cm1, true));
        $this->assertEquals($group1->id, groups_get_activity_group($cm2, true));

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user viewing non-grouped posts (this is only possible in visible groups).
        $this->setUser($user1);
        $_POST['group'] = 0;
        $this->assertEquals(0, groups_get_activity_group($cm1, true));

        // They can see anything in visible groups.
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc12, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc14->id, $neighbours['next']->id);

        // Normal user, orphan of groups, can only see non-grouped posts in separate groups.
        $this->setUser($user2);
        $_POST['group'] = 0;
        $this->assertEquals(0, groups_get_activity_group($cm2, true));

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEmpty($neighbours['next']);

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc22, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc24, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Switching to viewing group 1.
        $this->setUser($user1);
        $_POST['group'] = $group1->id;
        $this->assertEquals($group1->id, groups_get_activity_group($cm1, true));
        $this->assertEquals($group1->id, groups_get_activity_group($cm2, true));

        // They can see non-grouped or same group.
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Querying the neighbours of a discussion passing the wrong CM.
        $this->expectException('coding_exception');
        peerforum_get_discussion_neighbours($cm2, $disc11, $peerforum2);
    }

    /**
     * Test getting the neighbour threads of a blog-like peerforum with groups involved.
     */
    public function test_peerforum_get_neighbours_with_groups_blog() {
        $this->resetAfterTest();

        $timenow = time();
        $timenext = $timenow;

        // Setup test data.
        $peerforumgen = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');
        $course = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->create_group_member(array('userid' => $user1->id, 'groupid' => $group1->id));

        $peerforum1 = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'blog',
                'groupmode' => VISIBLEGROUPS));
        $peerforum2 = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'blog',
                'groupmode' => SEPARATEGROUPS));
        $cm1 = get_coursemodule_from_instance('peerforum', $peerforum1->id);
        $cm2 = get_coursemodule_from_instance('peerforum', $peerforum2->id);
        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);

        // Creating blog posts in both peerforums.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group1->id;
        $record->timemodified = time();
        $disc11 = $peerforumgen->create_discussion($record);
        $record->timenow = $timenext++;
        $record->peerforum = $peerforum2->id;
        $record->timemodified++;
        $disc21 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user2->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group2->id;
        $disc12 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc22 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = null;
        $disc13 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc23 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user2->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group2->id;
        $disc14 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc24 = $peerforumgen->create_discussion($record);

        $record->timemodified++;
        $record->userid = $user1->id;
        $record->peerforum = $peerforum1->id;
        $record->groupid = $group1->id;
        $disc15 = $peerforumgen->create_discussion($record);
        $record->peerforum = $peerforum2->id;
        $disc25 = $peerforumgen->create_discussion($record);

        // Admin user can see all groups.
        $this->setAdminUser();
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc12->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc22->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc12, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc22, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc14->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc22->id, $neighbours['prev']->id);
        $this->assertEquals($disc24->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc14, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc24, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc14->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc24->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Admin user is only viewing group 1.
        $_POST['group'] = $group1->id;
        $this->assertEquals($group1->id, groups_get_activity_group($cm1, true));
        $this->assertEquals($group1->id, groups_get_activity_group($cm2, true));

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Normal user viewing non-grouped posts (this is only possible in visible groups).
        $this->setUser($user1);
        $_POST['group'] = 0;
        $this->assertEquals(0, groups_get_activity_group($cm1, true));

        // They can see anything in visible groups.
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc12, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc12->id, $neighbours['prev']->id);
        $this->assertEquals($disc14->id, $neighbours['next']->id);

        // Normal user, orphan of groups, can only see non-grouped posts in separate groups.
        $this->setUser($user2);
        $_POST['group'] = 0;
        $this->assertEquals(0, groups_get_activity_group($cm2, true));

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEmpty($neighbours['next']);

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc22, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc24, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Switching to viewing group 1.
        $this->setUser($user1);
        $_POST['group'] = $group1->id;
        $this->assertEquals($group1->id, groups_get_activity_group($cm1, true));
        $this->assertEquals($group1->id, groups_get_activity_group($cm2, true));

        // They can see non-grouped or same group.
        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc11, $peerforum1);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc13->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc21, $peerforum2);
        $this->assertEmpty($neighbours['prev']);
        $this->assertEquals($disc23->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc13, $peerforum1);
        $this->assertEquals($disc11->id, $neighbours['prev']->id);
        $this->assertEquals($disc15->id, $neighbours['next']->id);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc23, $peerforum2);
        $this->assertEquals($disc21->id, $neighbours['prev']->id);
        $this->assertEquals($disc25->id, $neighbours['next']->id);

        $neighbours = peerforum_get_discussion_neighbours($cm1, $disc15, $peerforum1);
        $this->assertEquals($disc13->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);
        $neighbours = peerforum_get_discussion_neighbours($cm2, $disc25, $peerforum2);
        $this->assertEquals($disc23->id, $neighbours['prev']->id);
        $this->assertEmpty($neighbours['next']);

        // Querying the neighbours of a discussion passing the wrong CM.
        $this->expectException('coding_exception');
        peerforum_get_discussion_neighbours($cm2, $disc11, $peerforum2);
    }

    public function test_count_discussion_replies_basic() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);

        // Count the discussion replies in the peerforum.
        $result = peerforum_count_discussion_replies($peerforum->id);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_limited() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits shouldn't make a difference.
        $result = peerforum_count_discussion_replies($peerforum->id, "", 20);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_paginated() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding paging shouldn't make any difference.
        $result = peerforum_count_discussion_replies($peerforum->id, "", -1, 0, 100);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_paginated_sorted() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Specifying the peerforumsort should also give a good result. This follows a different path.
        $result = peerforum_count_discussion_replies($peerforum->id, "d.id asc", -1, 0, 100);
        $this->assertCount(10, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_limited_sorted() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits, and a peerforumsort shouldn't make a difference.
        $result = peerforum_count_discussion_replies($peerforum->id, "d.id asc", 20);
        $this->assertCount(10, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_paginated_sorted_small() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Grabbing a smaller subset and they should be ordered as expected.
        $result = peerforum_count_discussion_replies($peerforum->id, "d.id asc", -1, 0, 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_paginated_sorted_small_reverse() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Grabbing a smaller subset and they should be ordered as expected.
        $result = peerforum_count_discussion_replies($peerforum->id, "d.id desc", -1, 0, 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the last discussionid.
            $discussionid = array_pop($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_limited_sorted_small_reverse() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits, and a peerforumsort shouldn't make a difference.
        $result = peerforum_count_discussion_replies($peerforum->id, "d.id desc", 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the last discussionid.
            $discussionid = array_pop($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    /**
     * Test the reply count when used with private replies.
     */
    public function test_peerforum_count_discussion_replies_private() {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id);

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id);

        $privilegeduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($privilegeduser->id, $course->id, 'editingteacher');

        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($otheruser->id, $course->id);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');

        // Create a discussion with some replies.
        $record = new stdClass();
        $record->course = $peerforum->course;
        $record->peerforum = $peerforum->id;
        $record->userid = $student->id;
        $discussion = $generator->create_discussion($record);
        $replycount = 5;
        $replyto = $DB->get_record('peerforum_posts', array('discussion' => $discussion->id));

        // Create a couple of standard replies.
        $post = new stdClass();
        $post->userid = $student->id;
        $post->discussion = $discussion->id;
        $post->parent = $replyto->id;

        for ($i = 0; $i < $replycount; $i++) {
            $post = $generator->create_post($post);
        }

        // Create a private reply post from the teacher back to the student.
        $reply = new stdClass();
        $reply->userid = $teacher->id;
        $reply->discussion = $discussion->id;
        $reply->parent = $replyto->id;
        $reply->privatereplyto = $replyto->userid;
        $generator->create_post($reply);

        // The user is the author of the private reply.
        $this->setUser($teacher->id);
        $counts = peerforum_count_discussion_replies($peerforum->id);
        $this->assertEquals($replycount + 1, $counts[$discussion->id]->replies);

        // The user is the intended recipient.
        $this->setUser($student->id);
        $counts = peerforum_count_discussion_replies($peerforum->id);
        $this->assertEquals($replycount + 1, $counts[$discussion->id]->replies);

        // The user is not the author or recipient, but does have the readprivatereplies capability.
        $this->setUser($privilegeduser->id);
        $counts = peerforum_count_discussion_replies($peerforum->id, "", -1, -1, 0, true);
        $this->assertEquals($replycount + 1, $counts[$discussion->id]->replies);

        // The user is not allowed to view this post.
        $this->setUser($otheruser->id);
        $counts = peerforum_count_discussion_replies($peerforum->id);
        $this->assertEquals($replycount, $counts[$discussion->id]->replies);
    }

    public function test_discussion_pinned_sort() {
        list($peerforum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $discussions = peerforum_get_discussions($cm);
        // First discussion should be pinned.
        $first = reset($discussions);
        $this->assertEquals(1, $first->pinned, "First discussion should be pinned discussion");
    }

    public function test_peerforum_view() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id),
                array('completion' => 2, 'completionview' => 1));
        $context = context_module::instance($peerforum->cmid);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $this->setAdminUser();
        peerforum_view($peerforum, $course, $cm, $context);

        $events = $sink->get_events();
        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Check completion status.
        $completion = new completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);

    }

    /**
     * Test peerforum_discussion_view.
     */
    public function test_peerforum_discussion_view() {
        global $CFG, $USER;

        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $discussion = $this->create_single_discussion_with_replies($peerforum, $USER, 2);

        $context = context_module::instance($peerforum->cmid);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $this->setAdminUser();
        peerforum_discussion_view($context, $peerforum, $discussion);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_pop($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'view discussion', "discuss.php?d={$discussion->id}",
                $discussion->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());

    }

    /**
     * Create a new course, peerforum, and user with a number of discussions and replies.
     *
     * @param int $discussioncount The number of discussions to create
     * @param int $replycount The number of replies to create in each discussion
     * @return array Containing the created peerforum object, and the ids of the created discussions.
     */
    protected function create_multiple_discussions_with_replies($discussioncount, $replycount) {
        $this->resetAfterTest();

        // Setup the content.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $record = new stdClass();
        $record->course = $course->id;
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $record);

        // Create 10 discussions with replies.
        $discussionids = array();
        for ($i = 0; $i < $discussioncount; $i++) {
            // Pin 3rd discussion.
            if ($i == 3) {
                $discussion = $this->create_single_discussion_pinned_with_replies($peerforum, $user, $replycount);
            } else {
                $discussion = $this->create_single_discussion_with_replies($peerforum, $user, $replycount);
            }

            $discussionids[] = $discussion->id;
        }
        return array($peerforum, $discussionids);
    }

    /**
     * Create a discussion with a number of replies.
     *
     * @param object $peerforum The peerforum which has been created
     * @param object $user The user making the discussion and replies
     * @param int $replycount The number of replies
     * @return object $discussion
     */
    protected function create_single_discussion_with_replies($peerforum, $user, $replycount) {
        global $DB;

        $generator = self::getDataGenerator()->get_plugin_generator('mod_peerforum');

        $record = new stdClass();
        $record->course = $peerforum->course;
        $record->peerforum = $peerforum->id;
        $record->userid = $user->id;
        $discussion = $generator->create_discussion($record);

        // Retrieve the first post.
        $replyto = $DB->get_record('peerforum_posts', array('discussion' => $discussion->id));

        // Create the replies.
        $post = new stdClass();
        $post->userid = $user->id;
        $post->discussion = $discussion->id;
        $post->parent = $replyto->id;

        for ($i = 0; $i < $replycount; $i++) {
            $generator->create_post($post);
        }

        return $discussion;
    }

    /**
     * Create a discussion with a number of replies.
     *
     * @param object $peerforum The peerforum which has been created
     * @param object $user The user making the discussion and replies
     * @param int $replycount The number of replies
     * @return object $discussion
     */
    protected function create_single_discussion_pinned_with_replies($peerforum, $user, $replycount) {
        global $DB;

        $generator = self::getDataGenerator()->get_plugin_generator('mod_peerforum');

        $record = new stdClass();
        $record->course = $peerforum->course;
        $record->peerforum = $peerforum->id;
        $record->userid = $user->id;
        $record->pinned = PEERFORUM_DISCUSSION_PINNED;
        $discussion = $generator->create_discussion($record);

        // Retrieve the first post.
        $replyto = $DB->get_record('peerforum_posts', array('discussion' => $discussion->id));

        // Create the replies.
        $post = new stdClass();
        $post->userid = $user->id;
        $post->discussion = $discussion->id;
        $post->parent = $replyto->id;

        for ($i = 0; $i < $replycount; $i++) {
            $generator->create_post($post);
        }

        return $discussion;
    }

    /**
     * Tests for mod_peerforum_rating_can_see_item_ratings().
     *
     * @throws coding_exception
     * @throws rating_exception
     */
    public function test_mod_peerforum_rating_can_see_item_ratings() {
        global $DB;

        $this->resetAfterTest();

        // Setup test data.
        $course = new stdClass();
        $course->groupmode = SEPARATEGROUPS;
        $course->groupmodeforce = true;
        $course = $this->getDataGenerator()->create_course($course);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $generator = self::getDataGenerator()->get_plugin_generator('mod_peerforum');
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($cm->id);

        // Create users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        // Groups and stuff.
        $role = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id, $role->id);

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1, $user1);
        groups_add_member($group1, $user2);
        groups_add_member($group2, $user3);
        groups_add_member($group2, $user4);

        $record = new stdClass();
        $record->course = $peerforum->course;
        $record->peerforum = $peerforum->id;
        $record->userid = $user1->id;
        $record->groupid = $group1->id;
        $discussion = $generator->create_discussion($record);

        // Retrieve the first post.
        $post = $DB->get_record('peerforum_posts', array('discussion' => $discussion->id));

        $ratingoptions = new stdClass;
        $ratingoptions->context = $context;
        $ratingoptions->ratingarea = 'post';
        $ratingoptions->component = 'mod_peerforum';
        $ratingoptions->itemid = $post->id;
        $ratingoptions->scaleid = 2;
        $ratingoptions->userid = $user2->id;
        $rating = new rating($ratingoptions);
        $rating->update_rating(2);

        // Now try to access it as various users.
        unassign_capability('moodle/site:accessallgroups', $role->id);
        $params = array('contextid' => 2,
                'component' => 'mod_peerforum',
                'ratingarea' => 'post',
                'itemid' => $post->id,
                'scaleid' => 2);
        $this->setUser($user1);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertFalse(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertFalse(mod_peerforum_rating_can_see_item_ratings($params));

        // Now try with accessallgroups cap and make sure everything is visible.
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $role->id, $context->id);
        $this->setUser($user1);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));

        // Change group mode and verify visibility.
        $course->groupmode = VISIBLEGROUPS;
        $DB->update_record('course', $course);
        unassign_capability('moodle/site:accessallgroups', $role->id);
        $this->setUser($user1);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertTrue(mod_peerforum_rating_can_see_item_ratings($params));

    }

    /**
     * Test peerforum_get_discussions
     */
    public function test_peerforum_get_discussions_with_groups() {
        global $DB;

        $this->resetAfterTest(true);

        // Create course to add the module.
        $course = self::getDataGenerator()->create_course(array('groupmode' => VISIBLEGROUPS, 'groupmodeforce' => 0));
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        self::getDataGenerator()->enrol_user($user1->id, $course->id, $role->id);
        self::getDataGenerator()->enrol_user($user2->id, $course->id, $role->id);
        self::getDataGenerator()->enrol_user($user3->id, $course->id, $role->id);

        // PeerForum forcing separate gropus.
        $record = new stdClass();
        $record->course = $course->id;
        $peerforum = self::getDataGenerator()->create_module('peerforum', $record, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        // Create groups.
        $group1 = self::getDataGenerator()->create_group(array('courseid' => $course->id, 'name' => 'group1'));
        $group2 = self::getDataGenerator()->create_group(array('courseid' => $course->id, 'name' => 'group2'));
        $group3 = self::getDataGenerator()->create_group(array('courseid' => $course->id, 'name' => 'group3'));

        // Add the user1 to g1 and g2 groups.
        groups_add_member($group1->id, $user1->id);
        groups_add_member($group2->id, $user1->id);

        // Add the user 2 and 3 to only one group.
        groups_add_member($group1->id, $user2->id);
        groups_add_member($group3->id, $user3->id);

        // Add a few discussions.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user1->id;
        $record['groupid'] = $group1->id;
        $discussiong1u1 = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $record['groupid'] = $group2->id;
        $discussiong2u1 = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $record['userid'] = $user2->id;
        $record['groupid'] = $group1->id;
        $discussiong1u2 = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $record['userid'] = $user3->id;
        $record['groupid'] = $group3->id;
        $discussiong3u3 = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        self::setUser($user1);

        // Test retrieve discussions not passing the groupid parameter. We will receive only first group discussions.
        $discussions = peerforum_get_discussions($cm);
        self::assertCount(2, $discussions);
        foreach ($discussions as $discussion) {
            self::assertEquals($group1->id, $discussion->groupid);
        }

        // Get all my discussions.
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, 0);
        self::assertCount(3, $discussions);

        // Get all my g1 discussions.
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group1->id);
        self::assertCount(2, $discussions);
        foreach ($discussions as $discussion) {
            self::assertEquals($group1->id, $discussion->groupid);
        }

        // Get all my g2 discussions.
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group2->id);
        self::assertCount(1, $discussions);
        $discussion = array_shift($discussions);
        self::assertEquals($group2->id, $discussion->groupid);
        self::assertEquals($user1->id, $discussion->userid);
        self::assertEquals($discussiong2u1->id, $discussion->discussion);

        // Get all my g3 discussions (I'm not enrolled in that group).
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group3->id);
        self::assertCount(0, $discussions);

        // This group does not exist.
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group3->id + 1000);
        self::assertCount(0, $discussions);

        self::setUser($user2);

        // Test retrieve discussions not passing the groupid parameter. We will receive only first group discussions.
        $discussions = peerforum_get_discussions($cm);
        self::assertCount(2, $discussions);
        foreach ($discussions as $discussion) {
            self::assertEquals($group1->id, $discussion->groupid);
        }

        // Get all my viewable discussions.
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, 0);
        self::assertCount(2, $discussions);
        foreach ($discussions as $discussion) {
            self::assertEquals($group1->id, $discussion->groupid);
        }

        // Get all my g2 discussions (I'm not enrolled in that group).
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group2->id);
        self::assertCount(0, $discussions);

        // Get all my g3 discussions (I'm not enrolled in that group).
        $discussions = peerforum_get_discussions($cm, '', true, -1, -1, false, -1, 0, $group3->id);
        self::assertCount(0, $discussions);

    }

    /**
     * Test peerforum_user_can_post_discussion
     */
    public function test_peerforum_user_can_post_discussion() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        // Create course to add the module.
        $course = self::getDataGenerator()->create_course(array('groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1));
        $user = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // PeerForum forcing separate gropus.
        $record = new stdClass();
        $record->course = $course->id;
        $peerforum = self::getDataGenerator()->create_module('peerforum', $record, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($cm->id);

        self::setUser($user);

        // The user is not enroled in any group, try to post in a peerforum with separate groups.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertFalse($can);

        // Create a group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        // Try to post in a group the user is not enrolled.
        $can = peerforum_user_can_post_discussion($peerforum, $group->id, -1, $cm, $context);
        $this->assertFalse($can);

        // Add the user to a group.
        groups_add_member($group->id, $user->id);

        // Try to post in a group the user is not enrolled.
        $can = peerforum_user_can_post_discussion($peerforum, $group->id + 1, -1, $cm, $context);
        $this->assertFalse($can);

        // Now try to post in the user group. (null means it will guess the group).
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertTrue($can);

        $can = peerforum_user_can_post_discussion($peerforum, $group->id, -1, $cm, $context);
        $this->assertTrue($can);

        // Test all groups.
        $can = peerforum_user_can_post_discussion($peerforum, -1, -1, $cm, $context);
        $this->assertFalse($can);

        $this->setAdminUser();
        $can = peerforum_user_can_post_discussion($peerforum, -1, -1, $cm, $context);
        $this->assertTrue($can);

        // Change peerforum type.
        $peerforum->type = 'news';
        $DB->update_record('peerforum', $peerforum);

        // Admin can post news.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertTrue($can);

        // Normal users don't.
        self::setUser($user);
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertFalse($can);

        // Change peerforum type.
        $peerforum->type = 'eachuser';
        $DB->update_record('peerforum', $peerforum);

        // I didn't post yet, so I should be able to post.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertTrue($can);

        // Post now.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->groupid = $group->id; // Jessica deleted
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // I already posted, I shouldn't be able to post.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertFalse($can);

        // Last check with no groups, normal peerforum and course.
        $course->groupmode = NOGROUPS;
        $course->groupmodeforce = 0;
        $DB->update_record('course', $course);

        $peerforum->type = 'general';
        $peerforum->groupmode = NOGROUPS;
        $DB->update_record('peerforum', $peerforum);

        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertTrue($can);
    }

    /**
     * Test peerforum_user_can_post_discussion_after_cutoff
     */
    public function test_peerforum_user_can_post_discussion_after_cutoff() {
        $this->resetAfterTest(true);

        // Create course to add the module.
        $course = self::getDataGenerator()->create_course(array('groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1));
        $student = self::getDataGenerator()->create_user();
        $teacher = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // PeerForum forcing separate gropus.
        $record = new stdClass();
        $record->course = $course->id;
        $record->cutoffdate = time() - 1;
        $peerforum = self::getDataGenerator()->create_module('peerforum', $record);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($cm->id);

        self::setUser($student);

        // Students usually don't have the mod/peerforum:canoverridecutoff capability.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertFalse($can);

        self::setUser($teacher);

        // Teachers usually have the mod/peerforum:canoverridecutoff capability.
        $can = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
        $this->assertTrue($can);
    }

    /**
     * Test peerforum_user_has_posted_discussion with no groups.
     * Jessica deleted.
     */
    public function test_peerforum_user_has_posted_discussion_no_groups() {
        global $CFG;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $author = self::getDataGenerator()->create_user();
        $other = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course->id);
        $peerforum = self::getDataGenerator()->create_module('peerforum', (object) ['course' => $course->id]);

        self::setUser($author);

        // Neither user has posted.
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $author->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $other->id));

        // Post in the peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $author->id;
        $record->peerforum = $peerforum->id;
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // The author has now posted, but the other user has not.
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $other->id));
    }

    /**
     * Test peerforum_user_has_posted_discussion with multiple peerforums
     * Jessica deleted.
     */
    public function test_peerforum_user_has_posted_discussion_multiple_peerforums() {
        global $CFG;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $author = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course->id);
        $peerforum1 = self::getDataGenerator()->create_module('peerforum', (object) ['course' => $course->id]);
        $peerforum2 = self::getDataGenerator()->create_module('peerforum', (object) ['course' => $course->id]);

        self::setUser($author);

        // No post in either peerforum.
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum1->id, $author->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum2->id, $author->id));

        // Post in the peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $author->id;
        $record->peerforum = $peerforum1->id;
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // The author has now posted in peerforum1, but not peerforum2.
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum1->id, $author->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum2->id, $author->id));
    }

    /**
     * Test peerforum_user_has_posted_discussion with multiple groups.
     * Jessica deleted.
     */
    public function test_peerforum_user_has_posted_discussion_multiple_groups() {
        global $CFG;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $author = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course->id);

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1->id, $author->id);
        groups_add_member($group2->id, $author->id);

        $peerforum = self::getDataGenerator()->create_module('peerforum', (object) ['course' => $course->id], [
                'groupmode' => SEPARATEGROUPS,
        ]);

        self::setUser($author);

        // The user has not posted in either group.
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $author->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group1->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group2->id));

        // Post in one group.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $author->id;
        $record->peerforum = $peerforum->id;
        $record->groupid = $group1->id;
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // The author has now posted in one group, but the other user has not.
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id));
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group1->id));
        $this->assertFalse(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group2->id));

        // Post in the other group.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $author->id;
        $record->peerforum = $peerforum->id;
        $record->groupid = $group2->id;
        $discussion = self::getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // The author has now posted in one group, but the other user has not.
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id));
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group1->id));
        $this->assertTrue(peerforum_user_has_posted_discussion($peerforum->id, $author->id, $group2->id));
    }

    /**
     * Tests the mod_peerforum_myprofile_navigation() function.
     */
    public function test_mod_peerforum_myprofile_navigation() {
        $this->resetAfterTest(true);

        // Set up the test.
        $tree = new \core_user\output\myprofile\tree();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $iscurrentuser = true;

        // Set as the current user.
        $this->setUser($user);

        // Check the node tree is correct.
        mod_peerforum_myprofile_navigation($tree, $user, $iscurrentuser, $course);
        $reflector = new ReflectionObject($tree);
        $nodes = $reflector->getProperty('nodes');
        $nodes->setAccessible(true);
        $this->assertArrayHasKey('peerforumposts', $nodes->getValue($tree));
        $this->assertArrayHasKey('peerforumdiscussions', $nodes->getValue($tree));
    }

    /**
     * Tests the mod_peerforum_myprofile_navigation() function as a guest.
     */
    public function test_mod_peerforum_myprofile_navigation_as_guest() {
        global $USER;

        $this->resetAfterTest(true);

        // Set up the test.
        $tree = new \core_user\output\myprofile\tree();
        $course = $this->getDataGenerator()->create_course();
        $iscurrentuser = true;

        // Set user as guest.
        $this->setGuestUser();

        // Check the node tree is correct.
        mod_peerforum_myprofile_navigation($tree, $USER, $iscurrentuser, $course);
        $reflector = new ReflectionObject($tree);
        $nodes = $reflector->getProperty('nodes');
        $nodes->setAccessible(true);
        $this->assertArrayNotHasKey('peerforumposts', $nodes->getValue($tree));
        $this->assertArrayNotHasKey('peerforumdiscussions', $nodes->getValue($tree));
    }

    /**
     * Tests the mod_peerforum_myprofile_navigation() function as a user viewing another user's profile.
     */
    public function test_mod_peerforum_myprofile_navigation_different_user() {
        $this->resetAfterTest(true);

        // Set up the test.
        $tree = new \core_user\output\myprofile\tree();
        $user = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $iscurrentuser = true;

        // Set to different user's profile.
        $this->setUser($user2);

        // Check the node tree is correct.
        mod_peerforum_myprofile_navigation($tree, $user, $iscurrentuser, $course);
        $reflector = new ReflectionObject($tree);
        $nodes = $reflector->getProperty('nodes');
        $nodes->setAccessible(true);
        $this->assertArrayHasKey('peerforumposts', $nodes->getValue($tree));
        $this->assertArrayHasKey('peerforumdiscussions', $nodes->getValue($tree));
    }

    /**
     * Test test_pinned_discussion_with_group.
     */
    public function test_pinned_discussion_with_group() {
        global $SESSION;

        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id));

        // Create an author user.
        $author = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course1->id);

        // Create two viewer users - one in a group, one not.
        $viewer1 = $this->getDataGenerator()->create_user((object) array('trackforums' => 1));
        $this->getDataGenerator()->enrol_user($viewer1->id, $course1->id);

        $viewer2 = $this->getDataGenerator()->create_user((object) array('trackforums' => 1));
        $this->getDataGenerator()->enrol_user($viewer2->id, $course1->id);
        $this->getDataGenerator()->create_group_member(array('userid' => $viewer2->id, 'groupid' => $group1->id));

        $peerforum1 = $this->getDataGenerator()->create_module('peerforum', (object) array(
                'course' => $course1->id,
                'groupmode' => SEPARATEGROUPS,
        ));

        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum1->id);

        $alldiscussions = array();
        $group1discussions = array();

        // Create 4 discussions in all participants group and group1, where the first
        // discussion is pinned in each group.
        $allrecord = new stdClass();
        $allrecord->course = $course1->id;
        $allrecord->userid = $author->id;
        $allrecord->peerforum = $peerforum1->id;
        $allrecord->pinned = PEERFORUM_DISCUSSION_PINNED;

        $group1record = new stdClass();
        $group1record->course = $course1->id;
        $group1record->userid = $author->id;
        $group1record->peerforum = $peerforum1->id;
        $group1record->groupid = $group1->id;
        $group1record->pinned = PEERFORUM_DISCUSSION_PINNED;

        $alldiscussions[] = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($allrecord);
        $group1discussions[] = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($group1record);

        // Create unpinned discussions.
        $allrecord->pinned = PEERFORUM_DISCUSSION_UNPINNED;
        $group1record->pinned = PEERFORUM_DISCUSSION_UNPINNED;
        for ($i = 0; $i < 3; $i++) {
            $alldiscussions[] = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($allrecord);
            $group1discussions[] =
                    $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($group1record);
        }

        // As viewer1 (no group). This user shouldn't see any of group1's discussions
        // so their expected discussion order is (where rightmost is highest priority):
        // Ad1, ad2, ad3, ad0.
        $this->setUser($viewer1->id);

        // CHECK 1.
        // Take the neighbours of ad3, which should be prev: ad2 and next: ad0.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $alldiscussions[3], $peerforum1);
        // Ad2 check.
        $this->assertEquals($alldiscussions[2]->id, $neighbours['prev']->id);
        // Ad0 check.
        $this->assertEquals($alldiscussions[0]->id, $neighbours['next']->id);

        // CHECK 2.
        // Take the neighbours of ad0, which should be prev: ad3 and next: null.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $alldiscussions[0], $peerforum1);
        // Ad3 check.
        $this->assertEquals($alldiscussions[3]->id, $neighbours['prev']->id);
        // Null check.
        $this->assertEmpty($neighbours['next']);

        // CHECK 3.
        // Take the neighbours of ad1, which should be prev: null and next: ad2.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $alldiscussions[1], $peerforum1);
        // Null check.
        $this->assertEmpty($neighbours['prev']);
        // Ad2 check.
        $this->assertEquals($alldiscussions[2]->id, $neighbours['next']->id);

        // Temporary hack to workaround for MDL-52656.
        $SESSION->currentgroup = null;

        // As viewer2 (group1). This user should see all of group1's posts and the all participants group.
        // The expected discussion order is (rightmost is highest priority):
        // Ad1, gd1, ad2, gd2, ad3, gd3, ad0, gd0.
        $this->setUser($viewer2->id);

        // CHECK 1.
        // Take the neighbours of ad1, which should be prev: null and next: gd1.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $alldiscussions[1], $peerforum1);
        // Null check.
        $this->assertEmpty($neighbours['prev']);
        // Gd1 check.
        $this->assertEquals($group1discussions[1]->id, $neighbours['next']->id);

        // CHECK 2.
        // Take the neighbours of ad3, which should be prev: gd2 and next: gd3.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $alldiscussions[3], $peerforum1);
        // Gd2 check.
        $this->assertEquals($group1discussions[2]->id, $neighbours['prev']->id);
        // Gd3 check.
        $this->assertEquals($group1discussions[3]->id, $neighbours['next']->id);

        // CHECK 3.
        // Take the neighbours of gd3, which should be prev: ad3 and next: ad0.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $group1discussions[3], $peerforum1);
        // Ad3 check.
        $this->assertEquals($alldiscussions[3]->id, $neighbours['prev']->id);
        // Ad0 check.
        $this->assertEquals($alldiscussions[0]->id, $neighbours['next']->id);

        // CHECK 4.
        // Take the neighbours of gd0, which should be prev: ad0 and next: null.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $group1discussions[0], $peerforum1);
        // Ad0 check.
        $this->assertEquals($alldiscussions[0]->id, $neighbours['prev']->id);
        // Null check.
        $this->assertEmpty($neighbours['next']);
    }

    /**
     * Test test_pinned_with_timed_discussions.
     */
    public function test_pinned_with_timed_discussions() {
        global $CFG;

        $CFG->peerforum_enabletimedposts = true;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create an user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $peerforum = $this->getDataGenerator()->create_module('peerforum', (object) array(
                'course' => $course->id,
                'groupmode' => SEPARATEGROUPS,
        ));

        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $now = time();
        $discussions = array();
        $discussiongenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->pinned = PEERFORUM_DISCUSSION_PINNED;
        $record->timemodified = $now;

        $discussions[] = $discussiongenerator->create_discussion($record);

        $record->pinned = PEERFORUM_DISCUSSION_UNPINNED;
        $record->timestart = $now + 10;

        $discussions[] = $discussiongenerator->create_discussion($record);

        $record->timestart = $now;

        $discussions[] = $discussiongenerator->create_discussion($record);

        // Expected order of discussions:
        // D2, d1, d0.
        $this->setUser($user->id);

        // CHECK 1.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[2], $peerforum);
        // Null check.
        $this->assertEmpty($neighbours['prev']);
        // D1 check.
        $this->assertEquals($discussions[1]->id, $neighbours['next']->id);

        // CHECK 2.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[1], $peerforum);
        // D2 check.
        $this->assertEquals($discussions[2]->id, $neighbours['prev']->id);
        // D0 check.
        $this->assertEquals($discussions[0]->id, $neighbours['next']->id);

        // CHECK 3.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[0], $peerforum);
        // D2 check.
        $this->assertEquals($discussions[1]->id, $neighbours['prev']->id);
        // Null check.
        $this->assertEmpty($neighbours['next']);
    }

    /**
     * Test test_pinned_timed_discussions_with_timed_discussions.
     */
    public function test_pinned_timed_discussions_with_timed_discussions() {
        global $CFG;

        $CFG->peerforum_enabletimedposts = true;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        // Create an user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a peerforum.
        $record = new stdClass();
        $record->course = $course->id;
        $peerforum = $this->getDataGenerator()->create_module('peerforum', (object) array(
                'course' => $course->id,
                'groupmode' => SEPARATEGROUPS,
        ));

        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $now = time();
        $discussions = array();
        $discussiongenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user->id;
        $record->peerforum = $peerforum->id;
        $record->pinned = PEERFORUM_DISCUSSION_PINNED;
        $record->timemodified = $now;
        $record->timestart = $now + 10;

        $discussions[] = $discussiongenerator->create_discussion($record);

        $record->pinned = PEERFORUM_DISCUSSION_UNPINNED;

        $discussions[] = $discussiongenerator->create_discussion($record);

        $record->timestart = $now;

        $discussions[] = $discussiongenerator->create_discussion($record);

        $record->pinned = PEERFORUM_DISCUSSION_PINNED;

        $discussions[] = $discussiongenerator->create_discussion($record);

        // Expected order of discussions:
        // D2, d1, d3, d0.
        $this->setUser($user->id);

        // CHECK 1.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[2], $peerforum);
        // Null check.
        $this->assertEmpty($neighbours['prev']);
        // D1 check.
        $this->assertEquals($discussions[1]->id, $neighbours['next']->id);

        // CHECK 2.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[1], $peerforum);
        // D2 check.
        $this->assertEquals($discussions[2]->id, $neighbours['prev']->id);
        // D3 check.
        $this->assertEquals($discussions[3]->id, $neighbours['next']->id);

        // CHECK 3.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[3], $peerforum);
        // D1 check.
        $this->assertEquals($discussions[1]->id, $neighbours['prev']->id);
        // D0 check.
        $this->assertEquals($discussions[0]->id, $neighbours['next']->id);

        // CHECK 4.
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $discussions[0], $peerforum);
        // D3 check.
        $this->assertEquals($discussions[3]->id, $neighbours['prev']->id);
        // Null check.
        $this->assertEmpty($neighbours['next']);
    }

    /**
     * Test for peerforum_is_author_hidden.
     */
    public function test_peerforum_is_author_hidden() {
        // First post, different peerforum type.
        $post = (object) ['parent' => 0];
        $peerforum = (object) ['type' => 'standard'];
        $this->assertFalse(peerforum_is_author_hidden($post, $peerforum));

        // Child post, different peerforum type.
        $post->parent = 1;
        $this->assertFalse(peerforum_is_author_hidden($post, $peerforum));

        // First post, single simple discussion peerforum type.
        $post->parent = 0;
        $peerforum->type = 'single';
        $this->assertTrue(peerforum_is_author_hidden($post, $peerforum));

        // Child post, single simple discussion peerforum type.
        $post->parent = 1;
        $this->assertFalse(peerforum_is_author_hidden($post, $peerforum));

        // Incorrect parameters: $post.
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('$post->parent must be set.');
        unset($post->parent);
        peerforum_is_author_hidden($post, $peerforum);

        // Incorrect parameters: $peerforum.
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('$peerforum->type must be set.');
        unset($peerforum->type);
        peerforum_is_author_hidden($post, $peerforum);
    }

    /**
     * Test the peerforum_discussion_is_locked function.
     *
     * @dataProvider peerforum_discussion_is_locked_provider
     * @param stdClass $peerforum
     * @param stdClass $discussion
     * @param bool $expect
     */
    public function test_peerforum_discussion_is_locked($peerforum, $discussion, $expect) {
        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $plugingenerator = $datagenerator->get_plugin_generator('mod_peerforum');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();
        $peerforum = $datagenerator->create_module('peerforum', (object) array_merge([
                'course' => $course->id
        ], $peerforum));
        $discussion = $plugingenerator->create_discussion((object) array_merge([
                'course' => $course->id,
                'userid' => $user->id,
                'peerforum' => $peerforum->id,
        ], $discussion));

        $this->assertEquals($expect, peerforum_discussion_is_locked($peerforum, $discussion));
    }

    /**
     * Dataprovider for peerforum_discussion_is_locked tests.
     *
     * @return  array
     */
    public function peerforum_discussion_is_locked_provider() {
        return [
                'Unlocked: lockdiscussionafter is false' => [
                        ['lockdiscussionafter' => false],
                        [],
                        false
                ],
                'Unlocked: lockdiscussionafter is set; peerforum is of type single; post is recent' => [
                        ['lockdiscussionafter' => DAYSECS, 'type' => 'single'],
                        ['timemodified' => time()],
                        false
                ],
                'Unlocked: lockdiscussionafter is set; peerforum is of type single; post is old' => [
                        ['lockdiscussionafter' => MINSECS, 'type' => 'single'],
                        ['timemodified' => time() - DAYSECS],
                        false
                ],
                'Unlocked: lockdiscussionafter is set; peerforum is of type eachuser; post is recent' => [
                        ['lockdiscussionafter' => DAYSECS, 'type' => 'eachuser'],
                        ['timemodified' => time()],
                        false
                ],
                'Locked: lockdiscussionafter is set; peerforum is of type eachuser; post is old' => [
                        ['lockdiscussionafter' => MINSECS, 'type' => 'eachuser'],
                        ['timemodified' => time() - DAYSECS],
                        true
                ],
        ];
    }

    /**
     * Test the peerforum_is_cutoff_date_reached function.
     *
     * @dataProvider peerforum_is_cutoff_date_reached_provider
     * @param array $peerforum
     * @param bool $expect
     */
    public function test_peerforum_is_cutoff_date_reached($peerforum, $expect) {
        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', (object) array_merge([
                'course' => $course->id
        ], $peerforum));

        $this->assertEquals($expect, peerforum_is_cutoff_date_reached($peerforum));
    }

    /**
     * Dataprovider for peerforum_is_cutoff_date_reached tests.
     *
     * @return  array
     */
    public function peerforum_is_cutoff_date_reached_provider() {
        $now = time();
        return [
                'cutoffdate is unset' => [
                        [],
                        false
                ],
                'cutoffdate is 0' => [
                        ['cutoffdate' => 0],
                        false
                ],
                'cutoffdate is set and is in future' => [
                        ['cutoffdate' => $now + 86400],
                        false
                ],
                'cutoffdate is set and is in past' => [
                        ['cutoffdate' => $now - 86400],
                        true
                ],
        ];
    }

    /**
     * Test the peerforum_is_due_date_reached function.
     *
     * @dataProvider peerforum_is_due_date_reached_provider
     * @param stdClass $peerforum
     * @param bool $expect
     */
    public function test_peerforum_is_due_date_reached($peerforum, $expect) {
        $this->resetAfterTest();

        $this->setAdminUser();

        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', (object) array_merge([
                'course' => $course->id
        ], $peerforum));

        $this->assertEquals($expect, peerforum_is_due_date_reached($peerforum));
    }

    /**
     * Dataprovider for peerforum_is_due_date_reached tests.
     *
     * @return  array
     */
    public function peerforum_is_due_date_reached_provider() {
        $now = time();
        return [
                'duedate is unset' => [
                        [],
                        false
                ],
                'duedate is 0' => [
                        ['duedate' => 0],
                        false
                ],
                'duedate is set and is in future' => [
                        ['duedate' => $now + 86400],
                        false
                ],
                'duedate is set and is in past' => [
                        ['duedate' => $now - 86400],
                        true
                ],
        ];
    }

    /**
     * Test that {@link peerforum_update_post()} keeps correct peerforum_discussions usermodified.
     * Jessica deleted
     */
    public function test_peerforum_update_post_keeps_discussions_usermodified() {
        global $DB;

        $this->resetAfterTest();

        // Let there be light.
        $teacher = self::getDataGenerator()->create_user();
        $student = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();

        $peerforum = self::getDataGenerator()->create_module('peerforum', (object) [
                'course' => $course->id,
        ]);

        $generator = self::getDataGenerator()->get_plugin_generator('mod_peerforum');

        // Let the teacher start a discussion.
        $discussion = $generator->create_discussion((object) [
                'course' => $course->id,
                'userid' => $teacher->id,
                'peerforum' => $peerforum->id,
        ]);

        // On this freshly created discussion, the teacher is the author of the last post.
        $this->assertEquals($teacher->id, $DB->get_field('peerforum_discussions', 'usermodified', ['id' => $discussion->id]));

        // Fetch modified timestamp of the discussion.
        $discussionmodified = $DB->get_field('peerforum_discussions', 'timemodified', ['id' => $discussion->id]);
        $pasttime = $discussionmodified - 3600;

        // Adjust the discussion modified timestamp back an hour, so it's in the past.
        $adjustment = (object) [
                'id' => $discussion->id,
                'timemodified' => $pasttime,
        ];
        $DB->update_record('peerforum_discussions', $adjustment);

        // Let the student reply to the teacher's post.
        $reply = $generator->create_post((object) [
                'course' => $course->id,
                'userid' => $student->id,
                'peerforum' => $peerforum->id,
                'discussion' => $discussion->id,
                'parent' => $discussion->firstpost,
        ]);

        // The student should now be the last post's author.
        $this->assertEquals($student->id, $DB->get_field('peerforum_discussions', 'usermodified', ['id' => $discussion->id]));

        // Fetch modified timestamp of the discussion and student's post.
        $discussionmodified = $DB->get_field('peerforum_discussions', 'timemodified', ['id' => $discussion->id]);
        $postmodified = $DB->get_field('peerforum_posts', 'modified', ['id' => $reply->id]);

        // Discussion modified time should be updated to be equal to the newly created post's time.
        $this->assertEquals($discussionmodified, $postmodified);

        // Adjust the discussion and post timestamps, so they are in the past.
        $adjustment = (object) [
                'id' => $discussion->id,
                'timemodified' => $pasttime,
        ];
        $DB->update_record('peerforum_discussions', $adjustment);

        $adjustment = (object) [
                'id' => $reply->id,
                'modified' => $pasttime,
        ];
        $DB->update_record('peerforum_posts', $adjustment);

        // The discussion and student's post time should now be an hour in the past.
        $this->assertEquals($pasttime, $DB->get_field('peerforum_discussions', 'timemodified', ['id' => $discussion->id]));
        $this->assertEquals($pasttime, $DB->get_field('peerforum_posts', 'modified', ['id' => $reply->id]));

        // Let the teacher edit the student's reply.
        $this->setUser($teacher->id);
        $newpost = (object) [
                'id' => $reply->id,
                'itemid' => 0,
                'subject' => 'Amended subject',
        ];
        peerforum_update_post($newpost, null);

        // The student should still be the last post's author.
        $this->assertEquals($student->id, $DB->get_field('peerforum_discussions', 'usermodified', ['id' => $discussion->id]));

        // The discussion modified time should not have changed.
        $this->assertEquals($pasttime, $DB->get_field('peerforum_discussions', 'timemodified', ['id' => $discussion->id]));

        // The post time should be updated.
        $this->assertGreaterThan($pasttime, $DB->get_field('peerforum_posts', 'modified', ['id' => $reply->id]));
    }

    public function test_peerforum_core_calendar_provide_event_action() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id,
                'completionreplies' => 5, 'completiondiscussions' => 2));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(7, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_peerforum_core_calendar_provide_event_action_in_hidden_section() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create the activity.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id,
                'completionreplies' => 5, 'completiondiscussions' => 2));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Set sections 0 as hidden.
        set_section_visible($course->id, 0, 0);

        // Now, log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    public function test_peerforum_core_calendar_provide_event_action_for_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create the activity.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id,
                'completionreplies' => 5, 'completiondiscussions' => 2));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Now log out.
        $CFG->forcelogin = true; // We don't want to be logged in as guest, as guest users might still have some capabilities.
        $this->setUser();

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(7, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_peerforum_core_calendar_provide_event_action_as_non_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Log out the user and set force login to true.
        \core\session\manager::init_empty_session();
        $CFG->forcelogin = true;

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_peerforum_core_calendar_provide_event_action_already_completed() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id),
                array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_peerforum_core_calendar_provide_event_action_already_completed_for_user() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enablecompletion = 1;

        // Create a course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Create a student.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Create the activity.
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id),
                array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $peerforum->id,
                \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed for the student.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_peerforum_core_calendar_provide_event_action($event, $factory, $student->id);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_mod_peerforum_get_tagged_posts() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $peerforumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');
        $course3 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course1 = $this->getDataGenerator()->create_course();
        $peerforum1 = $this->getDataGenerator()->create_module('peerforum', array('course' => $course1->id));
        $peerforum2 = $this->getDataGenerator()->create_module('peerforum', array('course' => $course2->id));
        $peerforum3 = $this->getDataGenerator()->create_module('peerforum', array('course' => $course3->id));
        $post11 = $peerforumgenerator->create_content($peerforum1, array('tags' => array('Cats', 'Dogs')));
        $post12 = $peerforumgenerator->create_content($peerforum1, array('tags' => array('Cats', 'mice')));
        $post13 = $peerforumgenerator->create_content($peerforum1, array('tags' => array('Cats')));
        $post14 = $peerforumgenerator->create_content($peerforum1);
        $post15 = $peerforumgenerator->create_content($peerforum1, array('tags' => array('Cats')));
        $post16 = $peerforumgenerator->create_content($peerforum1, array('tags' => array('Cats'), 'hidden' => true));
        $post21 = $peerforumgenerator->create_content($peerforum2, array('tags' => array('Cats')));
        $post22 = $peerforumgenerator->create_content($peerforum2, array('tags' => array('Cats', 'Dogs')));
        $post23 = $peerforumgenerator->create_content($peerforum2, array('tags' => array('mice', 'Cats')));
        $post31 = $peerforumgenerator->create_content($peerforum3, array('tags' => array('mice', 'Cats')));

        $tag = core_tag_tag::get_by_name(0, 'Cats');

        // Admin can see everything.
        $res = mod_peerforum_get_tagged_posts($tag, /*$exclusivemode = */ false,
                /*$fromctx = */ 0, /*$ctx = */ 0, /*$rec = */ 1, /*$post = */ 0);
        $this->assertRegExp('/' . $post11->subject . '</', $res->content);
        $this->assertRegExp('/' . $post12->subject . '</', $res->content);
        $this->assertRegExp('/' . $post13->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post14->subject . '</', $res->content);
        $this->assertRegExp('/' . $post15->subject . '</', $res->content);
        $this->assertRegExp('/' . $post16->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post21->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post22->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post23->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post31->subject . '</', $res->content);
        $this->assertEmpty($res->prevpageurl);
        $this->assertNotEmpty($res->nextpageurl);
        $res = mod_peerforum_get_tagged_posts($tag, /*$exclusivemode = */ false,
                /*$fromctx = */ 0, /*$ctx = */ 0, /*$rec = */ 1, /*$post = */ 1);
        $this->assertNotRegExp('/' . $post11->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post12->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post13->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post14->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post15->subject . '</', $res->content);
        $this->assertNotRegExp('/' . $post16->subject . '</', $res->content);
        $this->assertRegExp('/' . $post21->subject . '</', $res->content);
        $this->assertRegExp('/' . $post22->subject . '</', $res->content);
        $this->assertRegExp('/' . $post23->subject . '</', $res->content);
        $this->assertRegExp('/' . $post31->subject . '</', $res->content);
        $this->assertNotEmpty($res->prevpageurl);
        $this->assertEmpty($res->nextpageurl);

        // Create and enrol a user.
        $student = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student->id, $course2->id, $studentrole->id, 'manual');
        $this->setUser($student);
        core_tag_index_builder::reset_caches();

        // User can not see posts in course 3 because he is not enrolled.
        $res = mod_peerforum_get_tagged_posts($tag, /*$exclusivemode = */ false,
                /*$fromctx = */ 0, /*$ctx = */ 0, /*$rec = */ 1, /*$post = */ 1);
        $this->assertRegExp('/' . $post22->subject . '/', $res->content);
        $this->assertRegExp('/' . $post23->subject . '/', $res->content);
        $this->assertNotRegExp('/' . $post31->subject . '/', $res->content);

        // User can search peerforum posts inside a course.
        $coursecontext = context_course::instance($course1->id);
        $res = mod_peerforum_get_tagged_posts($tag, /*$exclusivemode = */ false,
                /*$fromctx = */ 0, /*$ctx = */ $coursecontext->id, /*$rec = */ 1, /*$post = */ 0);
        $this->assertRegExp('/' . $post11->subject . '/', $res->content);
        $this->assertRegExp('/' . $post12->subject . '/', $res->content);
        $this->assertRegExp('/' . $post13->subject . '/', $res->content);
        $this->assertNotRegExp('/' . $post14->subject . '/', $res->content);
        $this->assertRegExp('/' . $post15->subject . '/', $res->content);
        $this->assertRegExp('/' . $post16->subject . '/', $res->content);
        $this->assertNotRegExp('/' . $post21->subject . '/', $res->content);
        $this->assertNotRegExp('/' . $post22->subject . '/', $res->content);
        $this->assertNotRegExp('/' . $post23->subject . '/', $res->content);
        $this->assertEmpty($res->nextpageurl);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'peerforum';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_peerforum_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $peerforum1 = $this->getDataGenerator()->create_module('peerforum', [
                'course' => $course->id,
                'completion' => 2,
                'completiondiscussions' => 3,
                'completionreplies' => 3,
                'completionposts' => 3
        ]);
        $peerforum2 = $this->getDataGenerator()->create_module('peerforum', [
                'course' => $course->id,
                'completion' => 2,
                'completiondiscussions' => 0,
                'completionreplies' => 0,
                'completionposts' => 0
        ]);
        $cm1 = cm_info::create(get_coursemodule_from_instance('peerforum', $peerforum1->id));
        $cm2 = cm_info::create(get_coursemodule_from_instance('peerforum', $peerforum2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
                'completiondiscussions' => 3,
                'completionreplies' => 3,
                'completionposts' => 3
        ]];
        $moddefaults->completion = 2;

        $activeruledescriptions = [
                get_string('completiondiscussionsdesc', 'peerforum', 3),
                get_string('completionrepliesdesc', 'peerforum', 3),
                get_string('completionpostsdesc', 'peerforum', 3)
        ];
        $this->assertEquals(mod_peerforum_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_peerforum_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_peerforum_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_peerforum_get_completion_active_rule_descriptions(new stdClass()), []);
    }

    /**
     * Test the peerforum_post_is_visible_privately function used in private replies.
     */
    public function test_peerforum_post_is_visible_privately() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        $author = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($author->id, $course->id);

        $recipient = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($recipient->id, $course->id);

        $privilegeduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($privilegeduser->id, $course->id, 'editingteacher');

        $otheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($otheruser->id, $course->id);

        // Fake a post - this does not need to be persisted to the DB.
        $post = new \stdClass();
        $post->userid = $author->id;
        $post->privatereplyto = $recipient->id;

        // The user is the author.
        $this->setUser($author->id);
        $this->assertTrue(peerforum_post_is_visible_privately($post, $cm));

        // The user is the intended recipient.
        $this->setUser($recipient->id);
        $this->assertTrue(peerforum_post_is_visible_privately($post, $cm));

        // The user is not the author or recipient, but does have the readprivatereplies capability.
        $this->setUser($privilegeduser->id);
        $this->assertTrue(peerforum_post_is_visible_privately($post, $cm));

        // The user is not allowed to view this post.
        $this->setUser($otheruser->id);
        $this->assertFalse(peerforum_post_is_visible_privately($post, $cm));
    }

    /**
     * An unkown event type should not have any limits
     */
    public function test_mod_peerforum_core_calendar_get_valid_event_timestart_range_unknown_event() {
        global $CFG;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $duedate = time() + DAYSECS;
        $peerforum = new \stdClass();
        $peerforum->duedate = $duedate;

        // Create a valid event.
        $event = new \calendar_event([
                'name' => 'Test event',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'peerforum',
                'instance' => 1,
                'eventtype' => PEERFORUM_EVENT_TYPE_DUE . "SOMETHING ELSE",
                'timestart' => 1,
                'timeduration' => 86400,
                'visible' => 1
        ]);

        list ($min, $max) = mod_peerforum_core_calendar_get_valid_event_timestart_range($event, $peerforum);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * PeerForums configured without a cutoff date should not have any limits applied.
     */
    public function test_mod_peerforum_core_calendar_get_valid_event_timestart_range_due_no_limit() {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $duedate = time() + DAYSECS;
        $peerforum = new \stdClass();
        $peerforum->duedate = $duedate;

        // Create a valid event.
        $event = new \calendar_event([
                'name' => 'Test event',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'peerforum',
                'instance' => 1,
                'eventtype' => PEERFORUM_EVENT_TYPE_DUE,
                'timestart' => 1,
                'timeduration' => 86400,
                'visible' => 1
        ]);

        list($min, $max) = mod_peerforum_core_calendar_get_valid_event_timestart_range($event, $peerforum);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * PeerForums should be top bound by the cutoff date.
     */
    public function test_mod_peerforum_core_calendar_get_valid_event_timestart_range_due_with_limits() {
        global $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $duedate = time() + DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $peerforum = new \stdClass();
        $peerforum->duedate = $duedate;
        $peerforum->cutoffdate = $cutoffdate;

        // Create a valid event.
        $event = new \calendar_event([
                'name' => 'Test event',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'peerforum',
                'instance' => 1,
                'eventtype' => PEERFORUM_EVENT_TYPE_DUE,
                'timestart' => 1,
                'timeduration' => 86400,
                'visible' => 1
        ]);

        list($min, $max) = mod_peerforum_core_calendar_get_valid_event_timestart_range($event, $peerforum);
        $this->assertNull($min);
        $this->assertEquals($cutoffdate, $max[0]);
        $this->assertNotEmpty($max[1]);
    }

    /**
     * An unknown event type should not change the peerforum instance.
     */
    public function test_mod_peerforum_core_calendar_event_timestart_updated_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $peerforumgenerator = $generator->get_plugin_generator('mod_peerforum');
        $duedate = time() + DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $peerforum = $peerforumgenerator->create_instance(['course' => $course->id]);
        $peerforum->duedate = $duedate;
        $peerforum->cutoffdate = $cutoffdate;
        $DB->update_record('peerforum', $peerforum);

        // Create a valid event.
        $event = new \calendar_event([
                'name' => 'Test event',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'peerforum',
                'instance' => $peerforum->id,
                'eventtype' => PEERFORUM_EVENT_TYPE_DUE . "SOMETHING ELSE",
                'timestart' => 1,
                'timeduration' => 86400,
                'visible' => 1
        ]);

        mod_peerforum_core_calendar_event_timestart_updated($event, $peerforum);

        $peerforum = $DB->get_record('peerforum', ['id' => $peerforum->id]);
        $this->assertEquals($duedate, $peerforum->duedate);
        $this->assertEquals($cutoffdate, $peerforum->cutoffdate);
    }

    /**
     * Due date events should update the peerforum due date.
     */
    public function test_mod_peerforum_core_calendar_event_timestart_updated_due_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $peerforumgenerator = $generator->get_plugin_generator('mod_peerforum');
        $duedate = time() + DAYSECS;
        $cutoffdate = $duedate + DAYSECS;
        $newduedate = $duedate + 1;
        $peerforum = $peerforumgenerator->create_instance(['course' => $course->id]);
        $peerforum->duedate = $duedate;
        $peerforum->cutoffdate = $cutoffdate;
        $DB->update_record('peerforum', $peerforum);

        // Create a valid event.
        $event = new \calendar_event([
                'name' => 'Test event',
                'description' => '',
                'format' => 1,
                'courseid' => $course->id,
                'groupid' => 0,
                'userid' => 2,
                'modulename' => 'peerforum',
                'instance' => $peerforum->id,
                'eventtype' => PEERFORUM_EVENT_TYPE_DUE,
                'timestart' => $newduedate,
                'timeduration' => 86400,
                'visible' => 1
        ]);

        mod_peerforum_core_calendar_event_timestart_updated($event, $peerforum);

        $peerforum = $DB->get_record('peerforum', ['id' => $peerforum->id]);
        $this->assertEquals($newduedate, $peerforum->duedate);
        $this->assertEquals($cutoffdate, $peerforum->cutoffdate);
    }

    /**
     * Test peerforum_get_layout_modes function.
     */
    public function test_peerforum_get_layout_modes() {
        $expectednormal = [
                PEERFORUM_MODE_FLATOLDEST => get_string('modeflatoldestfirst', 'peerforum'),
                PEERFORUM_MODE_FLATNEWEST => get_string('modeflatnewestfirst', 'peerforum'),
                PEERFORUM_MODE_THREADED => get_string('modethreaded', 'peerforum'),
                PEERFORUM_MODE_NESTED => get_string('modenested', 'peerforum')
        ];
        $expectedexperimental = [
                PEERFORUM_MODE_FLATOLDEST => get_string('modeflatoldestfirst', 'peerforum'),
                PEERFORUM_MODE_FLATNEWEST => get_string('modeflatnewestfirst', 'peerforum'),
                PEERFORUM_MODE_THREADED => get_string('modethreaded', 'peerforum'),
                PEERFORUM_MODE_NESTED_V2 => get_string('modenestedv2', 'peerforum')
        ];

        $this->assertEquals($expectednormal, peerforum_get_layout_modes());
        $this->assertEquals($expectednormal, peerforum_get_layout_modes(false));
        $this->assertEquals($expectedexperimental, peerforum_get_layout_modes(true));
    }
}
