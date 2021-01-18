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
 * Unit tests for the core_peergrade implementation of the Privacy API.
 *
 * @package    core_peergrade
 * @category   test
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/peergrade/lib.php');

use \core_peergrade\privacy\provider;
use \core_privacy\local\request\writer;

/**
 * Unit tests for the core_peergrade implementation of the Privacy API.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_peergrade_privacy_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * PeerGrade something as a user.
     *
     * @param int $userid
     * @param string $component
     * @param string $peergradearea
     * @param int $itemid
     * @param \context $context
     * @param string $score
     */
    protected function peergrade_as_user($userid, $component, $peergradearea, $itemid, $context, $score) {
        // PeerGrade the courses.
        $rm = new peergrade_manager();
        $peergradeoptions = (object) [
                'component' => $component,
                'peergradearea' => $peergradearea,
                'scaleid' => 100,
        ];

        // PeerGrade all courses as u1, and the course category too..
        $peergradeoptions->itemid = $itemid;
        $peergradeoptions->userid = $userid;
        $peergradeoptions->context = $context;
        $peergrade = new \peergrade($peergradeoptions);
        $peergrade->update_peergrade($score);
    }

    /**
     * Ensure that the get_sql_join function returns valid SQL which returns the correct list of peergraded itemids.
     */
    public function test_get_sql_join() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // PeerGrade the courses.
        $rm = new peergrade_manager();
        $peergradeoptions = (object) [
                'component' => 'core_course',
                'peergradearea' => 'course',
                'scaleid' => 100,
        ];

        // PeerGrade all courses as u1, and something else in the same context.
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course1->id, \context_course::instance($course1->id), 25);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 50);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course3->id, \context_course::instance($course3->id), 75);
        $this->peergrade_as_user($u1->id, 'core_course', 'files', $course3->id, \context_course::instance($course3->id), 99);

        // PeerGrade course2 as u2, and something else in a different context/component..
        $this->peergrade_as_user($u2->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 90);
        $this->peergrade_as_user($u2->id, 'user', 'user', $u3->id, \context_user::instance($u3->id), 10);

        // Return any course which the u1 has peergraded.
        // u1 peergraded all three courses.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u1->id);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(3, $courses);
        $this->assertTrue(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertTrue(isset($courses[$course3->id]));

        // User u1 peergraded files in course 3 only.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'files', 'c.id', $u1->id);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertFalse(isset($courses[$course2->id]));
        $this->assertTrue(isset($courses[$course3->id]));

        // Return any course which the u2 has peergraded.
        // User u2 peergraded only course 2.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u2->id);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertFalse(isset($courses[$course3->id]));

        // User u2 peergraded u3.
        $peergradequery = provider::get_sql_join('r', 'user', 'user', 'u.id', $u2->id);
        $sql = "SELECT u.id FROM {user} u {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $users = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $users);
        $this->assertFalse(isset($users[$u1->id]));
        $this->assertFalse(isset($users[$u2->id]));
        $this->assertTrue(isset($users[$u3->id]));

        // Return any course which the u3 has peergraded.
        // User u3 did not peergrade anything.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u3->id);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(0, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertFalse(isset($courses[$course2->id]));
        $this->assertFalse(isset($courses[$course3->id]));
    }

    /**
     * Ensure that the get_sql_join function returns valid SQL which returns the correct list of peergraded itemids.
     * This makes use of the optional inner join argument.
     */
    public function test_get_sql_join_inner() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // PeerGrade the courses.
        $rm = new peergrade_manager();
        $peergradeoptions = (object) [
                'component' => 'core_course',
                'peergradearea' => 'course',
                'scaleid' => 100,
        ];

        // PeerGrade all courses as u1, and something else in the same context.
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course1->id, \context_course::instance($course1->id), 25);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 50);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course3->id, \context_course::instance($course3->id), 75);
        $this->peergrade_as_user($u1->id, 'core_course', 'files', $course3->id, \context_course::instance($course3->id), 99);

        // PeerGrade course2 as u2, and something else in a different context/component..
        $this->peergrade_as_user($u2->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 90);
        $this->peergrade_as_user($u2->id, 'user', 'user', $u3->id, \context_user::instance($u3->id), 10);

        // Return any course which the u1 has peergraded.
        // u1 peergraded all three courses.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u1->id, true);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(3, $courses);
        $this->assertTrue(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertTrue(isset($courses[$course3->id]));

        // User u1 peergraded files in course 3 only.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'files', 'c.id', $u1->id, true);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertFalse(isset($courses[$course2->id]));
        $this->assertTrue(isset($courses[$course3->id]));

        // Return any course which the u2 has peergraded.
        // User u2 peergraded only course 2.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u2->id, true);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertTrue(isset($courses[$course2->id]));
        $this->assertFalse(isset($courses[$course3->id]));

        // User u2 peergraded u3.
        $peergradequery = provider::get_sql_join('r', 'user', 'user', 'u.id', $u2->id, true);
        $sql = "SELECT u.id FROM {user} u {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $users = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(1, $users);
        $this->assertFalse(isset($users[$u1->id]));
        $this->assertFalse(isset($users[$u2->id]));
        $this->assertTrue(isset($users[$u3->id]));

        // Return any course which the u3 has peergraded.
        // User u3 did not peergrade anything.
        $peergradequery = provider::get_sql_join('r', 'core_course', 'course', 'c.id', $u3->id, true);
        $sql = "SELECT c.id FROM {course} c {$peergradequery->join} WHERE {$peergradequery->userwhere}";
        $courses = $DB->get_records_sql($sql, $peergradequery->params);

        $this->assertCount(0, $courses);
        $this->assertFalse(isset($courses[$course1->id]));
        $this->assertFalse(isset($courses[$course2->id]));
        $this->assertFalse(isset($courses[$course3->id]));
    }

    /**
     * Ensure that export_area_peergrades exports all peergrades that a user has made, and all peergrades for a users own content.
     */
    public function test_export_area_peergrades() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // PeerGrade the courses.
        $rm = new peergrade_manager();
        $peergradeoptions = (object) [
                'component' => 'core_course',
                'peergradearea' => 'course',
                'scaleid' => 100,
        ];

        // PeerGrade all courses as u1, and something else in the same context.
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course1->id, \context_course::instance($course1->id), 25);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 50);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course3->id, \context_course::instance($course3->id), 75);
        $this->peergrade_as_user($u1->id, 'core_course', 'files', $course3->id, \context_course::instance($course3->id), 99);
        $this->peergrade_as_user($u1->id, 'user', 'user', $u3->id, \context_user::instance($u3->id), 10);

        // PeerGrade course2 as u2, and something else in a different context/component..
        $this->peergrade_as_user($u2->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 90);
        $this->peergrade_as_user($u2->id, 'user', 'user', $u3->id, \context_user::instance($u3->id), 20);

        // Test exports.
        // User 1 peergraded all three courses, and the core_course, and user 3.
        // User 1::course1 is stored in [] subcontext.
        $context = \context_course::instance($course1->id);
        $subcontext = [];
        provider::export_area_peergrades($u1->id, $context, $subcontext, 'core_course', 'course', $course1->id, true);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $peergrade = $writer->get_related_data($subcontext, 'peergrade');
        $this->assert_has_peergrade($u1, 25, $peergrade);

        // User 1::course2 is stored in ['foo'] subcontext.
        $context = \context_course::instance($course2->id);
        $subcontext = ['foo'];
        provider::export_area_peergrades($u1->id, $context, $subcontext, 'core_course', 'course', $course2->id, true);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $result = $writer->get_related_data($subcontext, 'peergrade');
        $this->assertCount(1, $result);
        $this->assert_has_peergrade($u1, 50, $result);

        // User 1::course3 is stored in ['foo'] subcontext.
        $context = \context_course::instance($course3->id);
        $subcontext = ['foo'];
        provider::export_area_peergrades($u1->id, $context, $subcontext, 'core_course', 'course', $course3->id, true);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $result = $writer->get_related_data($subcontext, 'peergrade');
        $this->assertCount(1, $result);
        $this->assert_has_peergrade($u1, 75, $result);

        // User 1::course3::files is stored in ['foo', 'files'] subcontext.
        $context = \context_course::instance($course3->id);
        $subcontext = ['foo', 'files'];
        provider::export_area_peergrades($u1->id, $context, $subcontext, 'core_course', 'files', $course3->id, true);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $result = $writer->get_related_data($subcontext, 'peergrade');
        $this->assertCount(1, $result);
        $this->assert_has_peergrade($u1, 99, $result);

        // Both users 1 and 2 peergraded user 3.
        // Exporting the data for user 3 should include both of those peergrades.
        $context = \context_user::instance($u3->id);
        $subcontext = ['user'];
        provider::export_area_peergrades($u3->id, $context, $subcontext, 'user', 'user', $u3->id, false);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $result = $writer->get_related_data($subcontext, 'peergrade');
        $this->assertCount(2, $result);
        $this->assert_has_peergrade($u1, 10, $result);
        $this->assert_has_peergrade($u2, 20, $result);
    }

    /**
     * Test delete_peergrades() method.
     */
    public function test_delete_peergrades() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // PeerGrade all courses as u1, and something else in the same context.
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course1->id, \context_course::instance($course1->id), 25);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 50);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course3->id, \context_course::instance($course3->id), 75);
        $this->peergrade_as_user($u1->id, 'core_course', 'files', $course3->id, \context_course::instance($course3->id), 99);
        $this->peergrade_as_user($u1->id, 'core_user', 'user', $u3->id, \context_user::instance($u3->id), 10);

        // PeerGrade course2 as u2, and something else in a different context/component..
        $this->peergrade_as_user($u2->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 90);
        $this->peergrade_as_user($u2->id, 'core_user', 'user', $u3->id, \context_user::instance($u3->id), 20);

        // Delete all peergrades in course1.
        $expectedpeergradescount = $DB->count_records('peergrade');
        core_peergrade\privacy\provider::delete_peergrades(\context_course::instance($course1->id));
        $expectedpeergradescount -= 1;
        $this->assertEquals($expectedpeergradescount, $DB->count_records('peergrade'));

        // Delete peergrades in course2 specifying wrong component.
        core_peergrade\privacy\provider::delete_peergrades(\context_course::instance($course2->id), 'other_component');
        $this->assertEquals($expectedpeergradescount, $DB->count_records('peergrade'));

        // Delete peergrades in course2 specifying correct component.
        core_peergrade\privacy\provider::delete_peergrades(\context_course::instance($course2->id), 'core_course');
        $expectedpeergradescount -= 2;
        $this->assertEquals($expectedpeergradescount, $DB->count_records('peergrade'));

        // Delete user peergrades specifyng all attributes.
        core_peergrade\privacy\provider::delete_peergrades(\context_user::instance($u3->id), 'core_user', 'user', $u3->id);
        $expectedpeergradescount -= 2;
        $this->assertEquals($expectedpeergradescount, $DB->count_records('peergrade'));
    }

    /**
     * Test delete_peergrades_select() method.
     */
    public function test_delete_peergrades_select() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // PeerGrade all courses as u1, and something else in the same context.
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course1->id, \context_course::instance($course1->id), 25);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 50);
        $this->peergrade_as_user($u1->id, 'core_course', 'course', $course3->id, \context_course::instance($course3->id), 75);
        $this->peergrade_as_user($u1->id, 'core_course', 'files', $course3->id, \context_course::instance($course3->id), 99);
        $this->peergrade_as_user($u1->id, 'core_user', 'user', $u3->id, \context_user::instance($u3->id), 10);

        // PeerGrade course2 as u2, and something else in a different context/component..
        $this->peergrade_as_user($u2->id, 'core_course', 'course', $course2->id, \context_course::instance($course2->id), 90);
        $this->peergrade_as_user($u2->id, 'core_user', 'user', $u3->id, \context_user::instance($u3->id), 20);

        // Delete peergrades in course1.
        list($sql, $params) = $DB->get_in_or_equal([$course1->id, $course2->id], SQL_PARAMS_NAMED);
        $expectedpeergradescount = $DB->count_records('peergrade');
        core_peergrade\privacy\provider::delete_peergrades_select(\context_course::instance($course1->id),
                'core_course', 'course', $sql, $params);
        $expectedpeergradescount -= 1;
        $this->assertEquals($expectedpeergradescount, $DB->count_records('peergrade'));
    }

    /**
     * Assert that a user has the correct peergrade.
     *
     * @param \stdClass $author The user with the peergrade
     * @param int $score The peergrade that was given
     * @param \stdClass[] The peergrades which were found
     */
    protected function assert_has_peergrade($author, $score, $actual) {
        $found = false;
        foreach ($actual as $peergrade) {
            if ($author->id == $peergrade->author) {
                $found = true;
                $this->assertEquals($score, $peergrade->peergrade);
            }
        }
        $this->assertTrue($found);
    }
}
