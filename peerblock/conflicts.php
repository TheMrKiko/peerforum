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
 * Configure the conflicts between students.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$vaultfactory = mod_peerforum\local\container::get_vault_factory();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/conflicts.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
));

set_peergradepanel_page($courseid, $userid, $url, 'manageconflicts', true, false);

echo $OUTPUT->box_start('posts-list');
echo $OUTPUT->heading('Nothing to see here.', 3);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();