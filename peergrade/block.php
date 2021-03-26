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
 * This page receives non-ajax block/unblock submissions
 *
 * @package    core_peergrade
 * @category   peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot . '/peergrade/lib.php');

$contextid = required_param('contextid', PARAM_INT);
$blockeduserid =
        required_param('blockeduserid', PARAM_INT); // Which user is the block being change.
$returnurl = required_param('returnurl', PARAM_LOCALURL); // Required for non-ajax requests.

$result = new stdClass;

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null; // Now we have a context object, throw away the id from the user.
$PAGE->set_context($context);
$PAGE->set_url('/peergrade/block.php', array('contextid' => $context->id, 'blockeduserid' => $blockeduserid));

if (!confirm_sesskey() ||
                !has_capability('mod/peerforum:professorpeergrade', $context)) {
    print_error('peergradepermissiondenied', 'peerforum');
}

$pgm = new peergrade_manager();

if ($blockeduserid) {
    $blockopt = new stdClass;
    $blockopt->contextid = $context->id;
    $blockopt->userid = $blockeduserid;
    $pgm->toggle_user_block($blockopt);
}

redirect($returnurl);