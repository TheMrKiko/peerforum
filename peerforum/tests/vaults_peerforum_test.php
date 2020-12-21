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
 * The peerforum vault tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/generator_trait.php');

/**
 * The peerforum vault tests.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_peerforum\local\vaults\peerforum
 */
class mod_peerforum_vaults_peerforum_testcase extends advanced_testcase {
    // Make use of the test generator trait.
    use mod_peerforum_tests_generator_trait;

    /**
     * Test get_from_id.
     *
     * @covers ::get_from_id
     */
    public function test_get_from_id() {
        $this->resetAfterTest();

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $vault = $vaultfactory->get_peerforum_vault();
        $datagenerator = $this->getDataGenerator();
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);

        $entity = $vault->get_from_id($peerforum->id);

        $this->assertEquals($peerforum->id, $entity->get_id());
    }

    /**
     * Test get_from_course_module_id.
     *
     * @covers ::get_from_course_module_id
     */
    public function test_get_from_course_module_id() {
        $this->resetAfterTest();

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $vault = $vaultfactory->get_peerforum_vault();
        $datagenerator = $this->getDataGenerator();
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $peerforum1 = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $peerforum2 = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $coursemodule1 = get_coursemodule_from_instance('peerforum', $peerforum1->id);
        $coursemodule2 = get_coursemodule_from_instance('peerforum', $peerforum2->id);

        // Don't exist.
        $entity = $vault->get_from_course_module_id($coursemodule1->id + 100);
        $this->assertEquals(null, $entity);

        $entity = $vault->get_from_course_module_id($coursemodule1->id);
        $this->assertEquals($peerforum1->id, $entity->get_id());
    }

    /**
     * Test get_from_course_module_ids.
     *
     * @covers ::get_from_course_module_ids
     */
    public function test_get_from_course_module_ids() {
        $this->resetAfterTest();

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $vault = $vaultfactory->get_peerforum_vault();
        $datagenerator = $this->getDataGenerator();
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $peerforum1 = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $peerforum2 = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        $coursemodule1 = get_coursemodule_from_instance('peerforum', $peerforum1->id);
        $coursemodule2 = get_coursemodule_from_instance('peerforum', $peerforum2->id);

        // Don't exist.
        $entities = array_values($vault->get_from_course_module_ids([$coursemodule1->id + 100, $coursemodule1->id + 200]));
        $this->assertEquals([], $entities);

        $entities = array_values($vault->get_from_course_module_ids([$coursemodule1->id, $coursemodule2->id]));
        usort($entities, function($a, $b) {
            return $a->get_id() <=> $b->get_id();
        });
        $this->assertCount(2, $entities);
        $this->assertEquals($peerforum1->id, $entities[0]->get_id());
        $this->assertEquals($peerforum2->id, $entities[1]->get_id());

        $entities = array_values($vault->get_from_course_module_ids([$coursemodule1->id]));
        usort($entities, function($a, $b) {
            return $a->get_id() <=> $b->get_id();
        });
        $this->assertCount(1, $entities);
        $this->assertEquals($peerforum1->id, $entities[0]->get_id());
    }

    /**
     * Test get_from_post_id.
     *
     * @covers ::get_from_post_id
     */
    public function test_get_from_post_id() {
        $this->resetAfterTest();

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $vault = $vaultfactory->get_peerforum_vault();

        $datagenerator = $this->getDataGenerator();
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $peerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        [$discussion, $post] = $this->helper_post_to_peerforum($peerforum, $user);
        $reply = $this->helper_reply_to_post($post, $user);

        $otherpeerforum = $datagenerator->create_module('peerforum', ['course' => $course->id]);
        [$otherdiscussion, $otherpost] = $this->helper_post_to_peerforum($otherpeerforum, $user);
        $otherreply = $this->helper_reply_to_post($otherpost, $user);

        $entity = $vault->get_from_post_id($post->id);
        $this->assertEquals($peerforum->id, $entity->get_id());

        $entity = $vault->get_from_post_id($reply->id);
        $this->assertEquals($peerforum->id, $entity->get_id());

        $this->assertEmpty($vault->get_from_post_id(-1));
    }
}
