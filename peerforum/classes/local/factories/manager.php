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
 * Managers factory.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\factories;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/rating/lib.php');
require_once($CFG->dirroot . '/peergrade/lib.php');

use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\managers\capability as capability_manager;
use rating_manager;
use peergrade_manager;

/**
 * Managers factory.
 *
 * See:
 * https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var legacy_data_mapper $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;

    /**
     * Constructor.
     *
     * @param legacy_data_mapper $legacydatamapperfactory Legacy data mapper factory
     */
    public function __construct(legacy_data_mapper $legacydatamapperfactory) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
    }

    /**
     * Create a capability manager for the given peerforum.
     *
     * @param peerforum_entity $peerforum The peerforum to manage capabilities for
     * @return capability_manager
     */
    public function get_capability_manager(peerforum_entity $peerforum) {
        return new capability_manager(
                $peerforum,
                $this->legacydatamapperfactory->get_peerforum_data_mapper(),
                $this->legacydatamapperfactory->get_discussion_data_mapper(),
                $this->legacydatamapperfactory->get_post_data_mapper(),
                $this->get_peergrade_manager()
        );
    }

    /**
     * Create a rating manager.
     *
     * @return rating_manager
     */
    public function get_rating_manager(): rating_manager {
        return new rating_manager();
    }


    /**
     * Create a peergrade manager.
     *
     * @return peergrade_manager
     */
    public function get_peergrade_manager(): peergrade_manager {
        return new peergrade_manager();
    }
}
