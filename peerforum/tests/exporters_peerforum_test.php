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
 * The discussion peerforum tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\exporters\peerforum as peerforum_exporter;

/**
 * The discussion peerforum tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_exporters_peerforum_testcase extends advanced_testcase {
    /**
     * Test the export function returns expected values.
     */
    public function test_export() {
        global $PAGE;
        $this->resetAfterTest();

        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', [
                'course' => $course->id,
                'groupmode' => VISIBLEGROUPS
        ]);
        $coursemodule = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $context = context_module::instance($coursemodule->id);
        $entityfactory = \mod_peerforum\local\container::get_entity_factory();
        $peerforum = $entityfactory->get_peerforum_from_stdclass($peerforum, $context, $coursemodule, $course);

        $exporter = new peerforum_exporter($peerforum, [
                'legacydatamapperfactory' => \mod_peerforum\local\container::get_legacy_data_mapper_factory(),
                'urlfactory' => \mod_peerforum\local\container::get_url_factory(),
                'capabilitymanager' => (\mod_peerforum\local\container::get_manager_factory())->get_capability_manager($peerforum),
                'user' => $user,
                'currentgroup' => null,
                'vaultfactory' => \mod_peerforum\local\container::get_vault_factory()
        ]);

        $exportedpeerforum = $exporter->export($renderer);

        $this->assertEquals($peerforum->get_id(), $exportedpeerforum->id);
        $this->assertEquals(VISIBLEGROUPS, $exportedpeerforum->state['groupmode']);
        $this->assertEquals(false, $exportedpeerforum->userstate['tracked']);
        $this->assertEquals(false, $exportedpeerforum->capabilities['viewdiscussions']);
        $this->assertEquals(false, $exportedpeerforum->capabilities['create']);
        $this->assertEquals(false, $exportedpeerforum->capabilities['subscribe']);
        $this->assertNotEquals(null, $exportedpeerforum->urls['create']);
        $this->assertNotEquals(null, $exportedpeerforum->urls['markasread']);
    }
}
