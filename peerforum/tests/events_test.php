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
 * Tests for peerforum events.
 *
 * @package    mod_peerforum
 * @category   test
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for peerforum events.
 *
 * @package    mod_peerforum
 * @category   test
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_events_testcase extends advanced_testcase {

    /**
     * Tests set up.
     */
    public function setUp(): void {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_peerforum\subscriptions::reset_peerforum_cache();

        $this->resetAfterTest();
    }

    public function tearDown(): void {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_peerforum\subscriptions::reset_peerforum_cache();
    }

    /**
     * Ensure course_searched event validates that searchterm is set.
     */
    public function test_course_searched_searchterm_validation() {
        $course = $this->getDataGenerator()->create_course();
        $coursectx = context_course::instance($course->id);
        $params = array(
                'context' => $coursectx,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'searchterm' value must be set in other.");
        \mod_peerforum\event\course_searched::create($params);
    }

    /**
     * Ensure course_searched event validates that context is the correct level.
     */
    public function test_course_searched_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);
        $params = array(
                'context' => $context,
                'other' => array('searchterm' => 'testing'),
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_COURSE.');
        \mod_peerforum\event\course_searched::create($params);
    }

    /**
     * Test course_searched event.
     */
    public function test_course_searched() {

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $coursectx = context_course::instance($course->id);
        $searchterm = 'testing123';

        $params = array(
                'context' => $coursectx,
                'other' => array('searchterm' => $searchterm),
        );

        // Create event.
        $event = \mod_peerforum\event\course_searched::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\course_searched', $event);
        $this->assertEquals($coursectx, $event->get_context());
        $expected = array($course->id, 'peerforum', 'search', "search.php?id={$course->id}&amp;search={$searchterm}", $searchterm);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure discussion_created event validates that peerforumid is set.
     */
    public function test_discussion_created_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_created::create($params);
    }

    /**
     * Ensure discussion_created event validates that the context is the correct level.
     */
    public function test_discussion_created_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_created::create($params);
    }

    /**
     * Test discussion_created event.
     */
    public function test_discussion_created() {

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $discussion->id,
                'other' => array('peerforumid' => $peerforum->id),
        );

        // Create the event.
        $event = \mod_peerforum\event\discussion_created::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_created', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'add discussion', "discuss.php?d={$discussion->id}", $discussion->id,
                $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure discussion_updated event validates that peerforumid is set.
     */
    public function test_discussion_updated_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_updated::create($params);
    }

    /**
     * Ensure discussion_created event validates that the context is the correct level.
     */
    public function test_discussion_updated_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_updated::create($params);
    }

    /**
     * Test discussion_created event.
     */
    public function test_discussion_updated() {

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $discussion->id,
                'other' => array('peerforumid' => $peerforum->id),
        );

        // Create the event.
        $event = \mod_peerforum\event\discussion_updated::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_updated', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure discussion_deleted event validates that peerforumid is set.
     */
    public function test_discussion_deleted_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_deleted::create($params);
    }

    /**
     * Ensure discussion_deleted event validates that context is of the correct level.
     */
    public function test_discussion_deleted_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_deleted::create($params);
    }

    /**
     * Test discussion_deleted event.
     */
    public function test_discussion_deleted() {

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $discussion->id,
                'other' => array('peerforumid' => $peerforum->id),
        );

        $event = \mod_peerforum\event\discussion_deleted::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'delete discussion', "view.php?id={$peerforum->cmid}", $peerforum->id,
                $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure discussion_moved event validates that frompeerforumid is set.
     */
    public function test_discussion_moved_frompeerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $topeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $context = context_module::instance($topeerforum->cmid);

        $params = array(
                'context' => $context,
                'other' => array('topeerforumid' => $topeerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'frompeerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_moved::create($params);
    }

    /**
     * Ensure discussion_moved event validates that topeerforumid is set.
     */
    public function test_discussion_moved_topeerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $frompeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $topeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($topeerforum->cmid);

        $params = array(
                'context' => $context,
                'other' => array('frompeerforumid' => $frompeerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'topeerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_moved::create($params);
    }

    /**
     * Ensure discussion_moved event validates that the context level is correct.
     */
    public function test_discussion_moved_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $frompeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $topeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $frompeerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $discussion->id,
                'other' => array('frompeerforumid' => $frompeerforum->id, 'topeerforumid' => $topeerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_moved::create($params);
    }

    /**
     * Test discussion_moved event.
     */
    public function test_discussion_moved() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $frompeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $topeerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $frompeerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $context = context_module::instance($topeerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $discussion->id,
                'other' => array('frompeerforumid' => $frompeerforum->id, 'topeerforumid' => $topeerforum->id)
        );

        $event = \mod_peerforum\event\discussion_moved::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_moved', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'move discussion', "discuss.php?d={$discussion->id}",
                $discussion->id, $topeerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure discussion_viewed event validates that the contextlevel is correct.
     */
    public function test_discussion_viewed_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $discussion->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_viewed::create($params);
    }

    /**
     * Test discussion_viewed event.
     */
    public function test_discussion_viewed() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $discussion->id,
        );

        $event = \mod_peerforum\event\discussion_viewed::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

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
     * Ensure course_module_viewed event validates that the contextlevel is correct.
     */
    public function test_course_module_viewed_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $peerforum->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\course_module_viewed::create($params);
    }

    /**
     * Test the course_module_viewed event.
     */
    public function test_course_module_viewed() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $peerforum->id,
        );

        $event = \mod_peerforum\event\course_module_viewed::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $expected =
                array($course->id, 'peerforum', 'view peerforum', "view.php?f={$peerforum->id}", $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure subscription_created event validates that the peerforumid is set.
     */
    public function test_subscription_created_peerforumid_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\subscription_created::create($params);
    }

    /**
     * Ensure subscription_created event validates that the relateduserid is set.
     */
    public function test_subscription_created_relateduserid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $peerforum->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\subscription_created::create($params);
    }

    /**
     * Ensure subscription_created event validates that the contextlevel is correct.
     */
    public function test_subscription_created_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\subscription_created::create($params);
    }

    /**
     * Test the subscription_created event.
     */
    public function test_subscription_created() {
        // Setup test data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($peerforum->cmid);

        // Add a subscription.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $subscription = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_subscription($record);

        $params = array(
                'context' => $context,
                'objectid' => $subscription->id,
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $event = \mod_peerforum\event\subscription_created::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_created', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'subscribe', "view.php?f={$peerforum->id}", $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/subscribers.php', array('id' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure subscription_deleted event validates that the peerforumid is set.
     */
    public function test_subscription_deleted_peerforumid_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\subscription_deleted::create($params);
    }

    /**
     * Ensure subscription_deleted event validates that the relateduserid is set.
     */
    public function test_subscription_deleted_relateduserid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $peerforum->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\subscription_deleted::create($params);
    }

    /**
     * Ensure subscription_deleted event validates that the contextlevel is correct.
     */
    public function test_subscription_deleted_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\subscription_deleted::create($params);
    }

    /**
     * Test the subscription_deleted event.
     */
    public function test_subscription_deleted() {
        // Setup test data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($peerforum->cmid);

        // Add a subscription.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $subscription = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_subscription($record);

        $params = array(
                'context' => $context,
                'objectid' => $subscription->id,
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $event = \mod_peerforum\event\subscription_deleted::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'unsubscribe', "view.php?f={$peerforum->id}", $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/subscribers.php', array('id' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure readtracking_enabled event validates that the peerforumid is set.
     */
    public function test_readtracking_enabled_peerforumid_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\readtracking_enabled::create($params);
    }

    /**
     * Ensure readtracking_enabled event validates that the relateduserid is set.
     */
    public function test_readtracking_enabled_relateduserid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $peerforum->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\readtracking_enabled::create($params);
    }

    /**
     * Ensure readtracking_enabled event validates that the contextlevel is correct.
     */
    public function test_readtracking_enabled_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\readtracking_enabled::create($params);
    }

    /**
     * Test the readtracking_enabled event.
     */
    public function test_readtracking_enabled() {
        // Setup test data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $event = \mod_peerforum\event\readtracking_enabled::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\readtracking_enabled', $event);
        $this->assertEquals($context, $event->get_context());
        $expected =
                array($course->id, 'peerforum', 'start tracking', "view.php?f={$peerforum->id}", $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     *  Ensure readtracking_disabled event validates that the peerforumid is set.
     */
    public function test_readtracking_disabled_peerforumid_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\readtracking_disabled::create($params);
    }

    /**
     *  Ensure readtracking_disabled event validates that the relateduserid is set.
     */
    public function test_readtracking_disabled_relateduserid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $peerforum->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\readtracking_disabled::create($params);
    }

    /**
     *  Ensure readtracking_disabled event validates that the contextlevel is correct
     */
    public function test_readtracking_disabled_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\readtracking_disabled::create($params);
    }

    /**
     *  Test the readtracking_disabled event.
     */
    public function test_readtracking_disabled() {
        // Setup test data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $event = \mod_peerforum\event\readtracking_disabled::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\readtracking_disabled', $event);
        $this->assertEquals($context, $event->get_context());
        $expected =
                array($course->id, 'peerforum', 'stop tracking', "view.php?f={$peerforum->id}", $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     *  Ensure subscribers_viewed event validates that the peerforumid is set.
     */
    public function test_subscribers_viewed_peerforumid_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\subscribers_viewed::create($params);
    }

    /**
     *  Ensure subscribers_viewed event validates that the contextlevel is correct.
     */
    public function test_subscribers_viewed_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_system::instance(),
                'other' => array('peerforumid' => $peerforum->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\subscribers_viewed::create($params);
    }

    /**
     *  Test the subscribers_viewed event.
     */
    public function test_subscribers_viewed() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'other' => array('peerforumid' => $peerforum->id),
        );

        $event = \mod_peerforum\event\subscribers_viewed::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscribers_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'view subscribers', "subscribers.php?id={$peerforum->id}", $peerforum->id,
                $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure user_report_viewed event validates that the reportmode is set.
     */
    public function test_user_report_viewed_reportmode_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $params = array(
                'context' => context_course::instance($course->id),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'reportmode' value must be set in other.");
        \mod_peerforum\event\user_report_viewed::create($params);
    }

    /**
     * Ensure user_report_viewed event validates that the contextlevel is correct.
     */
    public function test_user_report_viewed_contextlevel_validation() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'other' => array('reportmode' => 'posts'),
                'relateduserid' => $user->id,
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be either CONTEXT_SYSTEM, CONTEXT_COURSE or CONTEXT_USER.');
        \mod_peerforum\event\user_report_viewed::create($params);
    }

    /**
     *  Ensure user_report_viewed event validates that the relateduserid is set.
     */
    public function test_user_report_viewed_relateduserid_validation() {

        $params = array(
                'context' => context_system::instance(),
                'other' => array('reportmode' => 'posts'),
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\user_report_viewed::create($params);
    }

    /**
     * Test the user_report_viewed event.
     */
    public function test_user_report_viewed() {
        // Setup test data.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $params = array(
                'context' => $context,
                'relateduserid' => $user->id,
                'other' => array('reportmode' => 'discussions'),
        );

        $event = \mod_peerforum\event\user_report_viewed::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\user_report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'user report',
                "user.php?id={$user->id}&amp;mode=discussions&amp;course={$course->id}", $user->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     *  Ensure post_created event validates that the postid is set.
     */
    public function test_post_created_postid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'other' => array('peerforumid' => $peerforum->id, 'peerforumtype' => $peerforum->type,
                        'discussionid' => $discussion->id)
        );

        \mod_peerforum\event\post_created::create($params);
    }

    /**
     * Ensure post_created event validates that the discussionid is set.
     */
    public function test_post_created_discussionid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('peerforumid' => $peerforum->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'discussionid' value must be set in other.");
        \mod_peerforum\event\post_created::create($params);
    }

    /**
     *  Ensure post_created event validates that the peerforumid is set.
     */
    public function test_post_created_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\post_created::create($params);
    }

    /**
     * Ensure post_created event validates that the peerforumtype is set.
     */
    public function test_post_created_peerforumtype_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumtype' value must be set in other.");
        \mod_peerforum\event\post_created::create($params);
    }

    /**
     *  Ensure post_created event validates that the contextlevel is correct.
     */
    public function test_post_created_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\post_created::create($params);
    }

    /**
     * Test the post_created event.
     */
    public function test_post_created() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $event = \mod_peerforum\event\post_created::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_created', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'add post', "discuss.php?d={$discussion->id}#p{$post->id}",
                $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/discuss.php', array('d' => $discussion->id));
        $url->set_anchor('p' . $event->objectid);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test the post_created event for a single discussion peerforum.
     */
    public function test_post_created_single() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'single'));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $event = \mod_peerforum\event\post_created::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_created', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'add post', "view.php?f={$peerforum->id}#p{$post->id}",
                $peerforum->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $url->set_anchor('p' . $event->objectid);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     *  Ensure post_deleted event validates that the postid is set.
     */
    public function test_post_deleted_postid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'other' => array('peerforumid' => $peerforum->id, 'peerforumtype' => $peerforum->type,
                        'discussionid' => $discussion->id)
        );

        \mod_peerforum\event\post_deleted::create($params);
    }

    /**
     * Ensure post_deleted event validates that the discussionid is set.
     */
    public function test_post_deleted_discussionid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('peerforumid' => $peerforum->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'discussionid' value must be set in other.");
        \mod_peerforum\event\post_deleted::create($params);
    }

    /**
     *  Ensure post_deleted event validates that the peerforumid is set.
     */
    public function test_post_deleted_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\post_deleted::create($params);
    }

    /**
     * Ensure post_deleted event validates that the peerforumtype is set.
     */
    public function test_post_deleted_peerforumtype_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumtype' value must be set in other.");
        \mod_peerforum\event\post_deleted::create($params);
    }

    /**
     *  Ensure post_deleted event validates that the contextlevel is correct.
     */
    public function test_post_deleted_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\post_deleted::create($params);
    }

    /**
     * Test post_deleted event.
     */
    public function test_post_deleted() {
        global $DB;

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // When creating a discussion we also create a post, so get the post.
        $discussionpost = $DB->get_records('peerforum_posts');
        // Will only be one here.
        $discussionpost = reset($discussionpost);

        // Add a few posts.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $posts = array();
        $posts[$discussionpost->id] = $discussionpost;
        for ($i = 0; $i < 3; $i++) {
            $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);
            $posts[$post->id] = $post;
        }

        // Delete the last post and capture the event.
        $lastpost = end($posts);
        $sink = $this->redirectEvents();
        peerforum_delete_post($lastpost, true, $course, $cm, $peerforum);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Check that the events contain the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_deleted', $event);
        $this->assertEquals(context_module::instance($peerforum->cmid), $event->get_context());
        $expected =
                array($course->id, 'peerforum', 'delete post', "discuss.php?d={$discussion->id}", $lastpost->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/discuss.php', array('d' => $discussion->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Delete the whole discussion and capture the events.
        $sink = $this->redirectEvents();
        peerforum_delete_discussion($discussion, true, $course, $cm, $peerforum);
        $events = $sink->get_events();
        // We will have 4 events. One for the discussion, another one for the discussion topic post, and two for the posts.
        $this->assertCount(4, $events);

        // Loop through the events and check they are valid.
        foreach ($events as $event) {
            if ($event instanceof \mod_peerforum\event\discussion_deleted) {
                // Check that the event contains the expected values.
                $this->assertEquals($event->objectid, $discussion->id);
                $this->assertEquals(context_module::instance($peerforum->cmid), $event->get_context());
                $expected = array($course->id, 'peerforum', 'delete discussion', "view.php?id={$peerforum->cmid}",
                        $peerforum->id, $peerforum->cmid);
                $this->assertEventLegacyLogData($expected, $event);
                $url = new \moodle_url('/mod/peerforum/view.php', array('id' => $peerforum->cmid));
                $this->assertEquals($url, $event->get_url());
                $this->assertEventContextNotUsed($event);
                $this->assertNotEmpty($event->get_name());
            } else {
                $post = $posts[$event->objectid];
                // Check that the event contains the expected values.
                $this->assertInstanceOf('\mod_peerforum\event\post_deleted', $event);
                $this->assertEquals($event->objectid, $post->id);
                $this->assertEquals(context_module::instance($peerforum->cmid), $event->get_context());
                $expected = array($course->id, 'peerforum', 'delete post', "discuss.php?d={$discussion->id}", $post->id,
                        $peerforum->cmid);
                $this->assertEventLegacyLogData($expected, $event);
                $url = new \moodle_url('/mod/peerforum/discuss.php', array('d' => $discussion->id));
                $this->assertEquals($url, $event->get_url());
                $this->assertEventContextNotUsed($event);
                $this->assertNotEmpty($event->get_name());
            }
        }
    }

    /**
     * Test post_deleted event for a single discussion peerforum.
     */
    public function test_post_deleted_single() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'single'));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $event = \mod_peerforum\event\post_deleted::create($params);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'delete post', "view.php?f={$peerforum->id}", $post->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Ensure post_updated event validates that the discussionid is set.
     */
    public function test_post_updated_discussionid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('peerforumid' => $peerforum->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'discussionid' value must be set in other.");
        \mod_peerforum\event\post_updated::create($params);
    }

    /**
     * Ensure post_updated event validates that the peerforumid is set.
     */
    public function test_post_updated_peerforumid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\post_updated::create($params);
    }

    /**
     * Ensure post_updated event validates that the peerforumtype is set.
     */
    public function test_post_updated_peerforumtype_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumtype' value must be set in other.");
        \mod_peerforum\event\post_updated::create($params);
    }

    /**
     *  Ensure post_updated event validates that the contextlevel is correct.
     */
    public function test_post_updated_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $params = array(
                'context' => context_system::instance(),
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\post_updated::create($params);
    }

    /**
     * Test post_updated event.
     */
    public function test_post_updated() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $event = \mod_peerforum\event\post_updated::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_updated', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'update post', "discuss.php?d={$discussion->id}#p{$post->id}",
                $post->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/discuss.php', array('d' => $discussion->id));
        $url->set_anchor('p' . $event->objectid);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test post_updated event.
     */
    public function test_post_updated_single() {
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', array('course' => $course->id, 'type' => 'single'));
        $user = $this->getDataGenerator()->create_user();

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array('discussionid' => $discussion->id, 'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type)
        );

        $event = \mod_peerforum\event\post_updated::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\post_updated', $event);
        $this->assertEquals($context, $event->get_context());
        $expected = array($course->id, 'peerforum', 'update post', "view.php?f={$peerforum->id}#p{$post->id}",
                $post->id, $peerforum->cmid);
        $this->assertEventLegacyLogData($expected, $event);
        $url = new \moodle_url('/mod/peerforum/view.php', array('f' => $peerforum->id));
        $url->set_anchor('p' . $post->id);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test discussion_subscription_created event.
     */
    public function test_discussion_subscription_created() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();

        // Trigger the event by subscribing the user to the peerforum discussion.
        \mod_peerforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_created', $event);

        $cm = get_coursemodule_from_instance('peerforum', $discussion->peerforum);
        $context = \context_module::instance($cm->id);
        $this->assertEquals($context, $event->get_context());

        $url = new \moodle_url('/mod/peerforum/subscribe.php', array(
                'id' => $peerforum->id,
                'd' => $discussion->id
        ));

        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test validation of discussion_subscription_created event.
     */
    public function test_discussion_subscription_created_validation() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        $event = \mod_peerforum\event\discussion_subscription_created::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
    }

    /**
     * Test contextlevel validation of discussion_subscription_created event.
     */
    public function test_discussion_subscription_created_validation_contextlevel() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => \context_course::instance($course->id),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        // Without an invalid context.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_subscription_created::create($params);
    }

    /**
     * Test discussion validation of discussion_subscription_created event.
     */
    public function test_discussion_subscription_created_validation_discussion() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        // Without the discussion.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'discussion' value must be set in other.");
        \mod_peerforum\event\discussion_subscription_created::create($params);
    }

    /**
     * Test peerforumid validation of discussion_subscription_created event.
     */
    public function test_discussion_subscription_created_validation_peerforumid() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        // Without the peerforumid.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'discussion' => $discussion->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_subscription_created::create($params);
    }

    /**
     * Test relateduserid validation of discussion_subscription_created event.
     */
    public function test_discussion_subscription_created_validation_relateduserid() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        // Without the relateduserid.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\discussion_subscription_created::create($params);
    }

    /**
     * Test discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_INITIALSUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();

        // Trigger the event by unsubscribing the user to the peerforum discussion.
        \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_deleted', $event);

        $cm = get_coursemodule_from_instance('peerforum', $discussion->peerforum);
        $context = \context_module::instance($cm->id);
        $this->assertEquals($context, $event->get_context());

        $url = new \moodle_url('/mod/peerforum/subscribe.php', array(
                'id' => $peerforum->id,
                'd' => $discussion->id
        ));

        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test validation of discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted_validation() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_INITIALSUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = \mod_peerforum\subscriptions::PEERFORUM_DISCUSSION_UNSUBSCRIBED;

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => $context,
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        $event = \mod_peerforum\event\discussion_subscription_deleted::create($params);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Without an invalid context.
        $params['context'] = \context_course::instance($course->id);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_deleted::create($params);

        // Without the discussion.
        unset($params['discussion']);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('The \'discussion\' value must be set in other.');
        \mod_peerforum\event\discussion_deleted::create($params);

        // Without the peerforumid.
        unset($params['peerforumid']);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('The \'peerforumid\' value must be set in other.');
        \mod_peerforum\event\discussion_deleted::create($params);

        // Without the relateduserid.
        unset($params['relateduserid']);
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('The \'relateduserid\' value must be set in other.');
        \mod_peerforum\event\discussion_deleted::create($params);
    }

    /**
     * Test contextlevel validation of discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted_validation_contextlevel() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        $params = array(
                'context' => \context_course::instance($course->id),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        // Without an invalid context.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context level must be CONTEXT_MODULE.');
        \mod_peerforum\event\discussion_subscription_deleted::create($params);
    }

    /**
     * Test discussion validation of discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted_validation_discussion() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        // Without the discussion.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'discussion' value must be set in other.");
        \mod_peerforum\event\discussion_subscription_deleted::create($params);
    }

    /**
     * Test peerforumid validation of discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted_validation_peerforumid() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        // Without the peerforumid.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'relateduserid' => $user->id,
                'other' => array(
                        'discussion' => $discussion->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'peerforumid' value must be set in other.");
        \mod_peerforum\event\discussion_subscription_deleted::create($params);
    }

    /**
     * Test relateduserid validation of discussion_subscription_deleted event.
     */
    public function test_discussion_subscription_deleted_validation_relateduserid() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // The user is not subscribed to the peerforum. Insert a new discussion subscription.
        $subscription = new \stdClass();
        $subscription->userid = $user->id;
        $subscription->peerforum = $peerforum->id;
        $subscription->discussion = $discussion->id;
        $subscription->preference = time();

        $subscription->id = $DB->insert_record('peerforum_discussion_subs', $subscription);

        $context = context_module::instance($peerforum->cmid);

        // Without the relateduserid.
        $params = array(
                'context' => context_module::instance($peerforum->cmid),
                'objectid' => $subscription->id,
                'other' => array(
                        'peerforumid' => $peerforum->id,
                        'discussion' => $discussion->id,
                )
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The 'relateduserid' must be set.");
        \mod_peerforum\event\discussion_subscription_deleted::create($params);
    }

    /**
     * Test that the correct context is used in the events when subscribing
     * users.
     */
    public function test_peerforum_subscription_page_context_valid() {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $options = array('course' => $course->id, 'forcesubscribe' => PEERFORUM_CHOOSESUBSCRIBE);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', $options);
        $quiz = $this->getDataGenerator()->create_module('quiz', $options);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $peerforum->id;
        $record['userid'] = $user->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion($record);

        // Add a post.
        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_post($record);

        // Set up the default page event to use this peerforum.
        $PAGE = new moodle_page();
        $cm = get_coursemodule_from_instance('peerforum', $discussion->peerforum);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);
        $PAGE->set_cm($cm, $course, $peerforum);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();

        // Trigger the event by subscribing the user to the peerforum.
        \mod_peerforum\subscriptions::subscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user to the peerforum.
        \mod_peerforum\subscriptions::unsubscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by subscribing the user to the discussion.
        \mod_peerforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user from the discussion.
        \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

        // Now try with the context for a different module (quiz).
        $PAGE = new moodle_page();
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        $quizcontext = \context_module::instance($cm->id);
        $PAGE->set_context($quizcontext);
        $PAGE->set_cm($cm, $course, $quiz);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();

        // Trigger the event by subscribing the user to the peerforum.
        \mod_peerforum\subscriptions::subscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user to the peerforum.
        \mod_peerforum\subscriptions::unsubscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by subscribing the user to the discussion.
        \mod_peerforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user from the discussion.
        \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

        // Now try with the course context - the module context should still be used.
        $PAGE = new moodle_page();
        $coursecontext = \context_course::instance($course->id);
        $PAGE->set_context($coursecontext);

        // Trigger the event by subscribing the user to the peerforum.
        \mod_peerforum\subscriptions::subscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user to the peerforum.
        \mod_peerforum\subscriptions::unsubscribe_user($user->id, $peerforum);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by subscribing the user to the discussion.
        \mod_peerforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_created', $event);
        $this->assertEquals($context, $event->get_context());

        // Trigger the event by unsubscribing the user from the discussion.
        \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion);

        $events = $sink->get_events();
        $sink->clear();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_peerforum\event\discussion_subscription_deleted', $event);
        $this->assertEquals($context, $event->get_context());

    }

    /**
     * Test mod_peerforum_observer methods.
     */
    public function test_observers() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/peerforum/lib.php');

        $peerforumgen = $this->getDataGenerator()->get_plugin_generator('mod_peerforum');

        $course = $this->getDataGenerator()->create_course();
        $trackedrecord = array('course' => $course->id, 'type' => 'general', 'forcesubscribe' => PEERFORUM_INITIALSUBSCRIBE);
        $untrackedrecord = array('course' => $course->id, 'type' => 'general');
        $trackedpeerforum = $this->getDataGenerator()->create_module('peerforum', $trackedrecord);
        $untrackedpeerforum = $this->getDataGenerator()->create_module('peerforum', $untrackedrecord);

        // Used functions don't require these settings; adding
        // them just in case there are APIs changes in future.
        $user = $this->getDataGenerator()->create_user(array(
                'maildigest' => 1,
                'trackforums' => 1
        ));

        $manplugin = enrol_get_plugin('manual');
        $manualenrol = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'));
        $student = $DB->get_record('role', array('shortname' => 'student'));

        // The role_assign observer does it's job adding the peerforum_subscriptions record.
        $manplugin->enrol_user($manualenrol, $user->id, $student->id);

        // They are not required, but in a real environment they are supposed to be required;
        // adding them just in case there are APIs changes in future.
        set_config('peerforum_trackingtype', 1);
        set_config('peerforum_trackreadposts', 1);

        $record = array();
        $record['course'] = $course->id;
        $record['peerforum'] = $trackedpeerforum->id;
        $record['userid'] = $user->id;
        $discussion = $peerforumgen->create_discussion($record);

        $record = array();
        $record['discussion'] = $discussion->id;
        $record['userid'] = $user->id;
        $post = $peerforumgen->create_post($record);

        peerforum_tp_add_read_record($user->id, $post->id);
        peerforum_set_user_maildigest($trackedpeerforum, 2, $user);
        peerforum_tp_stop_tracking($untrackedpeerforum->id, $user->id);

        $this->assertEquals(1, $DB->count_records('peerforum_subscriptions'));
        $this->assertEquals(1, $DB->count_records('peerforum_digests'));
        $this->assertEquals(1, $DB->count_records('peerforum_track_prefs'));
        $this->assertEquals(1, $DB->count_records('peerforum_read'));

        // The course_module_created observer does it's job adding a subscription.
        $peerforumrecord = array('course' => $course->id, 'type' => 'general', 'forcesubscribe' => PEERFORUM_INITIALSUBSCRIBE);
        $extrapeerforum = $this->getDataGenerator()->create_module('peerforum', $peerforumrecord);
        $this->assertEquals(2, $DB->count_records('peerforum_subscriptions'));

        $manplugin->unenrol_user($manualenrol, $user->id);

        $this->assertEquals(0, $DB->count_records('peerforum_digests'));
        $this->assertEquals(0, $DB->count_records('peerforum_subscriptions'));
        $this->assertEquals(0, $DB->count_records('peerforum_track_prefs'));
        $this->assertEquals(0, $DB->count_records('peerforum_read'));
    }

}
