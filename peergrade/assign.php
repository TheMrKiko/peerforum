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
 * This page receives non-ajax peergrade submissions
 * Additional functions for peergrading in PeerForums
 *
 * It is similar to peergrade_ajax.php. Unlike peergrade_ajax.php a return url is required.
 *
 * @package    core_peergrade
 * @category   peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot . '/peergrade/lib.php');

$contextid = required_param('contextid', PARAM_INT);
$component = required_param('component', PARAM_COMPONENT);
$peergradearea = required_param('peergradearea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$action = required_param('action', PARAM_TEXT);
$assigneduserid =
        required_param('assigneduserid', PARAM_INT); // Which user is the assign being change. Required to update their grade.
$returnurl = required_param('returnurl', PARAM_LOCALURL); // Required for non-ajax requests.

$result = new stdClass;

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null; // Now we have a context object, throw away the id from the user.
$PAGE->set_context($context);
$PAGE->set_url('/peergrade/assign.php', array('contextid' => $context->id));

if (!confirm_sesskey() ||
                !has_capability('mod/peerforum:professorpeergrade', $context)) {
    print_error('peergradepermissiondenied', 'peerforum');
}

$pgm = new peergrade_manager();

// Check the module peergrade permissions.
// Doing this check here rather than within peergrade_manager::get_peergrades() so we can choose how to handle the error.
$pluginpermissionsarray = $pgm->get_plugin_permissions_array($context->id, $component, $peergradearea);

if ($action === 'remove' && $assigneduserid) {
    $delopt = new stdClass;
    $delopt->contextid = $context->id;
    $delopt->component = $component;
    $delopt->peergradearea = $peergradearea;
    $delopt->itemid = $itemid;
    $delopt->userid = $assigneduserid;
    $pgm->delete_assignments($delopt);

} else if ($action === 'assign') {
    $peergradeoptions = new stdClass;
    $peergradeoptions->context = $context;
    $peergradeoptions->component = $component;
    $peergradeoptions->peergradearea = $peergradearea;
    $peergradeoptions->itemid = $itemid;
    $peergradeoptions->peergradescaleid = $peergradescaleid;
    $peergradeoptions->userid = $USER->id;
    $peergradeoptions->itemuserid = $peergradeduserid;

    $peergrade = new peergrade($peergradeoptions);
    $peergrade->update_peergrade($userpeergrade, $feedback);
}

redirect($returnurl);