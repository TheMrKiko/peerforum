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
$scaleid = required_param('scaleid', PARAM_INT);
$userpeergrade = required_param('peergrade', PARAM_INT);
$peergradeduserid =
        required_param('peergradeduserid', PARAM_INT); // Which user is being peergraded. Required to update their grade.
$returnurl = required_param('returnurl', PARAM_LOCALURL); // Required for non-ajax requests.

$result = new stdClass;

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null; // Now we have a context object, throw away the id from the user.
$PAGE->set_context($context);
$PAGE->set_url('/peergrade/peergrade.php', array('contextid' => $context->id));

if (!confirm_sesskey() || !has_capability('moodle/peergrade:peergrade', $context)) {
    print_error('peergradepermissiondenied', 'peergrade');
}

$rm = new peergrade_manager();

// Check the module peergrade permissions.
// Doing this check here rather than within peergrade_manager::get_peergrades() so we can choose how to handle the error.
$pluginpermissionsarray = $rm->get_plugin_permissions_array($context->id, $component, $peergradearea);

if (!$pluginpermissionsarray['peergrade']) {
    print_error('peergradepermissiondenied', 'peergrade');
} else {
    $params = array(
            'context' => $context,
            'component' => $component,
            'peergradearea' => $peergradearea,
            'itemid' => $itemid,
            'scaleid' => $scaleid,
            'peergrade' => $userpeergrade,
            'peergradeduserid' => $peergradeduserid
    );
    if (!$rm->check_peergrade_is_valid($params)) {
        echo $OUTPUT->header();
        echo get_string('peergradeinvalid', 'peergrade');
        echo $OUTPUT->footer();
        die();
    }
}

if ($userpeergrade != PEERGRADE_UNSET_PEERGRADE) {
    $peergradeoptions = new stdClass;
    $peergradeoptions->context = $context;
    $peergradeoptions->component = $component;
    $peergradeoptions->peergradearea = $peergradearea;
    $peergradeoptions->itemid = $itemid;
    $peergradeoptions->scaleid = $scaleid;
    $peergradeoptions->userid = $USER->id;

    $peergrade = new peergrade($peergradeoptions);
    $peergrade->update_peergrade($userpeergrade);
} else { // Delete the peergrade if the user set to "PeerGrade..."
    $options = new stdClass;
    $options->contextid = $context->id;
    $options->component = $component;
    $options->peergradearea = $peergradearea;
    $options->userid = $USER->id;
    $options->itemid = $itemid;

    $rm->delete_peergrades($options);
}

if (!empty($cm) && $context->contextlevel == CONTEXT_MODULE) {
    // Tell the module that its grades have changed.
    $modinstance = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);
    $modinstance->cmidnumber = $cm->id; // MDL-12961.
    $functionname = $cm->modname . '_update_grades';
    require_once($CFG->dirroot . "/mod/{$cm->modname}/lib.php");
    if (function_exists($functionname)) {
        $functionname($modinstance, $peergradeduserid);
    }
}

redirect($returnurl);
