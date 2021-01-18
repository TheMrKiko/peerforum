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
 * This page receives ajax peergrade submissions
 *
 * It is similar to peergrade.php. Unlike peergrade.php a return url is NOT required.
 *
 * @package    core_peergrade
 * @category   peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../config.php');
require_once($CFG->dirroot . '/peergrade/lib.php');

$contextid = required_param('contextid', PARAM_INT);
$component = required_param('component', PARAM_COMPONENT);
$peergradearea = required_param('peergradearea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$scaleid = required_param('scaleid', PARAM_INT);
$userpeergrade = required_param('peergrade', PARAM_INT);
$peergradeduserid = required_param('peergradeduserid', PARAM_INT); // The user being peergraded. Required to update their grade.
$aggregationmethod =
        optional_param('aggregation', PEERGRADE_AGGREGATE_NONE, PARAM_INT); // Used to calculate the aggregate to return.

$result = new stdClass;

// If session has expired and its an ajax request so we cant do a page redirect.
if (!isloggedin()) {
    $result->error = get_string('sessionerroruser', 'error');
    echo json_encode($result);
    die();
}

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null; // Now we have a context object, throw away the id from the user.
$PAGE->set_context($context);
$PAGE->set_url('/peergrade/peergrade_ajax.php', array('contextid' => $context->id));

if (!confirm_sesskey() || !has_capability('moodle/peergrade:peergrade', $context)) {
    echo $OUTPUT->header();
    echo get_string('peergradepermissiondenied', 'peergrade');
    echo $OUTPUT->footer();
    die();
}

$rm = new peergrade_manager();
$result = $rm->add_peergrade($cm, $context, $component, $peergradearea, $itemid, $scaleid, $userpeergrade, $peergradeduserid,
        $aggregationmethod);

// Return translated error.
if (!empty($result->error)) {
    $result->error = get_string($result->error, 'peergrade');
}

echo json_encode($result);
