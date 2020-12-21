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
 * Tests for peerforum report summary events.
 *
 * @package    peerforumreport_summary
 * @category   test
 * @copyright  2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for peerforum report summary events.
 *
 * @package    peerforumreport_summary
 * @category   test
 * @copyright  2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerforumreport_summary_events_testcase extends advanced_testcase {
    /**
     * Test report_downloaded event.
     */
    public function test_report_downloaded() {
        global $DB;

        $this->resetAfterTest();

        // Create course and teacher user.
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleteacher = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $roleteacher->id);

        // Create peerforum.
        $this->setUser($teacher);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', ['course' => $course->id]);
        $context = context_module::instance($peerforum->cmid);

        // Trigger and capture event.
        $eventparams = [
                'context' => $context,
                'other' => [
                        'peerforumid' => $peerforum->id,
                        'hasviewall' => true,
                ],
        ];
        $event = \peerforumreport_summary\event\report_downloaded::create($eventparams);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $sink->close();

        // Check the event contains the expected data.
        $this->assertInstanceOf('\peerforumreport_summary\event\report_downloaded', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($peerforum->cmid, $event->contextinstanceid);
        $this->assertEquals($teacher->id, $event->userid);
        $url = new moodle_url('/mod/peerforum/report/summary/index.php',
                ['courseid' => $course->id, 'peerforumid' => $peerforum->id]);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());
    }

    /**
     * Test report_viewed event.
     */
    public function test_report_viewed() {
        global $DB;

        $this->resetAfterTest();

        // Create course and teacher user.
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleteacher = $DB->get_record('role', ['shortname' => 'teacher']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $roleteacher->id);

        // Create peerforum.
        $this->setUser($teacher);
        $peerforum = $this->getDataGenerator()->create_module('peerforum', ['course' => $course->id]);
        $context = context_module::instance($peerforum->cmid);

        // Trigger and capture event.
        $eventparams = [
                'context' => $context,
                'other' => [
                        'peerforumid' => $peerforum->id,
                        'hasviewall' => true,
                ],
        ];
        $event = \peerforumreport_summary\event\report_viewed::create($eventparams);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $sink->close();

        // Check the event contains the expected data.
        $this->assertInstanceOf('\peerforumreport_summary\event\report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($peerforum->cmid, $event->contextinstanceid);
        $this->assertEquals($teacher->id, $event->userid);
        $url = new moodle_url('/mod/peerforum/report/summary/index.php',
                ['courseid' => $course->id, 'peerforumid' => $peerforum->id]);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_description());
    }
}
