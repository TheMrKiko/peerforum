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
 * The author exporter tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\author as author_entity;
use mod_peerforum\local\exporters\author as author_exporter;

global $CFG;

/**
 * The author exporter tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_exporters_author_testcase extends advanced_testcase {
    /**
     * Test the export function returns expected values.
     */
    public function test_export_author() {
        global $PAGE;
        $this->resetAfterTest();

        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($coursemodule->id);
        $entityfactory = \mod_peerforum\local\container::get_entity_factory();
        $peerforum = $entityfactory->get_peerforum_from_stdclass($peerforum, $context, $coursemodule, $course);
        $author = new author_entity(
                1,
                1,
                'test',
                'user',
                'test user',
                'test@example.com',
                false
        );

        $exporter = new author_exporter($author, 1, [], true, [
                'urlfactory' => \mod_peerforum\local\container::get_url_factory(),
                'context' => $context,
                'peerforum' => $peerforum,
        ]);

        $exportedauthor = $exporter->export($renderer);

        $this->assertEquals(1, $exportedauthor->id);
        $this->assertEquals('test user', $exportedauthor->fullname);
        $this->assertEquals([], $exportedauthor->groups);
        $this->assertNotEquals(null, $exportedauthor->urls['profile']);
        $this->assertNotEquals(null, $exportedauthor->urls['profileimage']);
    }

    /**
     * Test the export function with groups.
     */
    public function test_export_author_with_groups() {
        global $PAGE;
        $this->resetAfterTest();

        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($coursemodule->id);
        $entityfactory = \mod_peerforum\local\container::get_entity_factory();
        $peerforum = $entityfactory->get_peerforum_from_stdclass($peerforum, $context, $coursemodule, $course);
        $author = new author_entity(
                1,
                1,
                'test',
                'user',
                'test user',
                'test@example.com',
                false
        );

        $group = $datagenerator->create_group(['courseid' => $course->id]);

        $exporter = new author_exporter($author, 1, [$group], true, [
                'urlfactory' => \mod_peerforum\local\container::get_url_factory(),
                'context' => $context,
                'peerforum' => $peerforum,
        ]);

        $exportedauthor = $exporter->export($renderer);

        $this->assertCount(1, $exportedauthor->groups);
        $this->assertEquals($group->id, $exportedauthor->groups[0]['id']);
    }

    /**
     * Test the export function with no view capability.
     */
    public function test_export_author_no_view_capability() {
        global $PAGE;
        $this->resetAfterTest();

        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($coursemodule->id);
        $entityfactory = \mod_peerforum\local\container::get_entity_factory();
        $peerforum = $entityfactory->get_peerforum_from_stdclass($peerforum, $context, $coursemodule, $course);
        $author = new author_entity(
                1,
                1,
                'test',
                'user',
                'test user',
                'test@example.com',
                false
        );

        $group = $datagenerator->create_group(['courseid' => $course->id]);

        $exporter = new author_exporter($author, 1, [$group], false, [
                'urlfactory' => \mod_peerforum\local\container::get_url_factory(),
                'context' => $context,
                'peerforum' => $peerforum,
        ]);

        $exportedauthor = $exporter->export($renderer);

        $this->assertEquals(null, $exportedauthor->id);
        $this->assertNotEquals('test user', $exportedauthor->fullname);
        $this->assertEquals([], $exportedauthor->groups);
        $this->assertEquals(null, $exportedauthor->urls['profile']);
        $this->assertEquals(null, $exportedauthor->urls['profileimage']);
    }
}
