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
 * Helpers for the core_peergrade subsystem implementation of privacy.
 *
 * @package    core_peergrade
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_peergrade\phpunit;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\tests\request\content_writer;

global $CFG;

/**
 * Helpers for the core_peergrade subsystem implementation of privacy.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait privacy_helper {
    /**
     * Fetch all peergrades on a subcontext.
     *
     * @param \context $context The context being stored.
     * @param array $subcontext The subcontext path to check.
     * @return  array
     */
    protected function get_peergrades_on_subcontext(\context $context, array $subcontext) {
        $writer = \core_privacy\local\request\writer::with_context($context);
        return $writer->get_related_data($subcontext, 'peergrade');
    }

    /**
     * Check that all included peergrades belong to the specified user.
     *
     * @param int $userid The ID of the user being stored.
     * @param \context $context The context being stored.
     * @param array $subcontext The subcontext path to check.
     * @param string $component The component being stored.
     * @param string $peergradearea The peergrade area to store results for.
     * @param int $itemid The itemid to store.
     */
    protected function assert_all_own_peergrades_on_context(
            int $userid,
            \context $context,
            array $subcontext,
            $component,
            $peergradearea,
            $itemid
    ) {
        $writer = \core_privacy\local\request\writer::with_context($context);
        $rm = new \peergrade_manager();
        $dbpeergrades = $rm->get_all_peergrades_for_item((object) [
                'context' => $context,
                'component' => $component,
                'peergradearea' => $peergradearea,
                'itemid' => $itemid,
        ]);

        $exportedpeergrades = $this->get_peergrades_on_subcontext($context, $subcontext);

        foreach ($exportedpeergrades as $peergradeid => $peergrade) {
            $this->assertTrue(isset($dbpeergrades[$peergradeid]));
            $this->assertEquals($userid, $peergrade->author);
            $this->assert_peergrade_matches($dbpeergrades[$peergradeid], $peergrade);
        }

        foreach ($dbpeergrades as $peergrade) {
            if ($peergrade->userid == $userid) {
                $this->assertEquals($peergrade->id, $peergradeid);
            }
        }
    }

    /**
     * Check that all included peergrades are valid. They may belong to any user.
     *
     * @param \context $context The context being stored.
     * @param array $subcontext The subcontext path to check.
     * @param string $component The component being stored.
     * @param string $peergradearea The peergrade area to store results for.
     * @param int $itemid The itemid to store.
     */
    protected function assert_all_peergrades_on_context(\context $context, array $subcontext, $component, $peergradearea, $itemid) {
        $writer = \core_privacy\local\request\writer::with_context($context);
        $rm = new \peergrade_manager();
        $dbpeergrades = $rm->get_all_peergrades_for_item((object) [
                'context' => $context,
                'component' => $component,
                'peergradearea' => $peergradearea,
                'itemid' => $itemid,
        ]);

        $exportedpeergrades = $this->get_peergrades_on_subcontext($context, $subcontext);

        foreach ($exportedpeergrades as $peergradeid => $peergrade) {
            $this->assertTrue(isset($dbpeergrades[$peergradeid]));
            $this->assert_peergrade_matches($dbpeergrades[$peergradeid], $peergrade);
        }

        foreach ($dbpeergrades as $peergrade) {
            $this->assertTrue(isset($exportedpeergrades[$peergrade->id]));
        }
    }

    /**
     * Assert that the peergrade matches.
     *
     * @param \stdClass $expected The expected peergrade structure
     * @param \stdClass $stored The actual peergrade structure
     */
    protected function assert_peergrade_matches($expected, $stored) {
        $this->assertEquals($expected->peergrade, $stored->peergrade);
        $this->assertEquals($expected->userid, $stored->author);
    }
}
