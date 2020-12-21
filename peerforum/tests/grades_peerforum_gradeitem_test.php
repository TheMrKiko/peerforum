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
 * Tests for the the PeerForum gradeitem.
 *
 * @package    mod_peerforum
 * @copyright Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tests\mod_peerforum\grades;

use core_grades\component_gradeitem;
use mod_peerforum\grades\peerforum_gradeitem as gradeitem;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use gradingform_controller;
use mod_peerforum\grades\peerforum_gradeitem;

require_once(__DIR__ . '/generator_trait.php');

/**
 * Tests for the the PeerForum gradeitem.
 *
 * @package    mod_peerforum
 * @copyright Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerforum_gradeitem_test extends \advanced_testcase {
    use \mod_peerforum_tests_generator_trait;

    /**
     * Test fetching of a grade for a user when the grade has been created.
     */
    public function test_get_grade_for_user_exists(): void {
        $peerforum = $this->get_peerforum_instance([
                'grade_peerforum' => 0,
        ]);
        $course = $peerforum->get_course_record();
        [$student] = $this->helper_create_users($course, 1);
        [$grader] = $this->helper_create_users($course, 1, 'editingteacher');

        $gradeitem = component_gradeitem::instance('mod_peerforum', $peerforum->get_context(), 'peerforum');

        // Create the grade record.
        $grade = $gradeitem->create_empty_grade($student, $grader);

        $this->assertIsObject($grade);
        $this->assertEquals($student->id, $grade->userid);
    }

    /**
     * Test fetching of a grade for a user when the grade has been created.
     */
    public function test_user_has_grade(): void {
        $peerforum = $this->get_peerforum_instance([
                'grade_peerforum' => 100,
        ]);
        $course = $peerforum->get_course_record();
        [$student] = $this->helper_create_users($course, 1);
        [$grader] = $this->helper_create_users($course, 1, 'editingteacher');

        $gradeitem = component_gradeitem::instance('mod_peerforum', $peerforum->get_context(), 'peerforum');

        $hasgrade = $gradeitem->user_has_grade($student);
        $this->assertEquals(false, $hasgrade);
        // Create the grade record.
        $gradeitem->create_empty_grade($student, $grader);

        $hasgrade = $gradeitem->user_has_grade($student);
        $this->assertEquals(false, $hasgrade);

        // Store a new value.
        $gradeitem->store_grade_from_formdata($student, $grader, (object) ['grade' => 97]);
        $hasgrade = $gradeitem->user_has_grade($student);
        $this->assertEquals(true, $hasgrade);
    }

    /**
     * Ensure that it is possible to get, and update, a grade for a user when simple direct grading is in use.
     */
    public function test_get_and_store_grade_for_user_with_simple_direct_grade(): void {
        $peerforum = $this->get_peerforum_instance([
                'grade_peerforum' => 100,
        ]);
        $course = $peerforum->get_course_record();
        [$student] = $this->helper_create_users($course, 1);
        [$grader] = $this->helper_create_users($course, 1, 'editingteacher');

        $gradeitem = component_gradeitem::instance('mod_peerforum', $peerforum->get_context(), 'peerforum');

        // Create the grade record.
        $grade = $gradeitem->create_empty_grade($student, $grader);

        $this->assertIsObject($grade);
        $this->assertEquals($student->id, $grade->userid);

        // Store a new value.
        $gradeitem->store_grade_from_formdata($student, $grader, (object) ['grade' => 97]);
    }

    /**
     * Ensure that it is possible to get, and update, a grade for a user when a rubric is in use.
     */
    public function test_get_and_store_grade_for_user_with_rubric(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = \testing_util::get_data_generator();
        $gradinggenerator = $generator->get_plugin_generator('core_grading');
        $rubricgenerator = $generator->get_plugin_generator('gradingform_rubric');

        $peerforum = $this->get_peerforum_instance([
                'grade_peerforum' => 100,
        ]);
        $course = $peerforum->get_course_record();
        $context = $peerforum->get_context();
        [$student] = $this->helper_create_users($course, 1);
        [$grader] = $this->helper_create_users($course, 1, 'editingteacher');
        [$editor] = $this->helper_create_users($course, 1, 'editingteacher');

        // Note: This must be run as a user because it messes with file uploads and drafts.
        $this->setUser($editor);

        $controller = $rubricgenerator->get_test_rubric($context, 'mod_peerforum', 'peerforum');

        // Create the peerforum_gradeitem object that we'll be testing.
        $gradeitem = component_gradeitem::instance('mod_peerforum', $peerforum->get_context(), 'peerforum');

        // The current grade should be null initially.
        $this->assertCount(0, $DB->get_records('peerforum_grades'));
        $grade = $gradeitem->get_grade_for_user($student, $grader);
        $instance = $gradeitem->get_advanced_grading_instance($grader, $grade);

        $this->assertIsObject($grade);
        $this->assertEquals($student->id, $grade->userid);
        $this->assertEquals($peerforum->get_id(), $grade->peerforum);

        $rubricgenerator = $generator->get_plugin_generator('gradingform_rubric');
        $data = $rubricgenerator->get_submitted_form_data($controller, $grade->id, [
                'Spelling is important' => [
                        'score' => 2,
                        'remark' => 'Abracadabra',
                ],
                'Pictures' => [
                        'score' => 1,
                        'remark' => 'More than one picture',
                ],
        ]);

        // Store a new value.
        $gradeitem->store_grade_from_formdata($student, $grader, (object) [
                'instanceid' => $instance->get_id(),
                'advancedgrading' => $data,
        ]);
    }

    /**
     * Get a peerforum instance.
     *
     * @param array $config
     * @return peerforum_entity
     */
    protected function get_peerforum_instance(array $config = []): peerforum_entity {
        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', array_merge($config, ['course' => $course->id]));

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $vault = $vaultfactory->get_peerforum_vault();

        return $vault->get_from_id((int) $peerforum->id);
    }
}
