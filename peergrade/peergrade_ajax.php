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
$peergradeduserid = required_param('peergradeduserid', PARAM_INT);//which user is being peergraded. Required to update their grade
$aggregationmethod = optional_param('aggregation', PEERGRADE_AGGREGATE_NONE,
        PARAM_INT);//we're going to calculate the aggregate and return it to the client

$result = new stdClass;

//if session has expired and its an ajax request so we cant do a page redirect
if (!isloggedin()) {
    $result->error = get_string('sessionerroruser', 'error');
    echo json_encode($result);
    die();
}

list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);

$contextid = null;//now we have a context object throw away the id from the user
$PAGE->set_context($context);
$PAGE->set_url('/peergrade/peergrade_ajax.php', array('contextid' => $context->id));

if (!confirm_sesskey() || !has_capability('moodle/peergrade:peergrade', $context)) {
    echo $OUTPUT->header();
    echo get_string('peergradepermissiondenied', 'peergrade');
    echo $OUTPUT->footer();
    die();
}

$rm = new peergrade_manager();

//check the module peergrade permissions
//doing this check here rather than within peergrade_manager::get_peergrades() so we can return a json error response
$pluginpermissionsarray = $rm->get_plugin_permissions_array($context->id, $component, $peergradearea);

if (!$pluginpermissionsarray['peergrade']) {
    $result->error = get_string('peergradepermissiondenied', 'peergrade');
    echo json_encode($result);
    die();
} else {
    $params = array(
            'context' => $context,
            'component' => $component,
            'peergradearea' => $peergradearea,
            'itemid' => $itemid,
            'scaleid' => $scaleid,
            'peergrade' => $userpeergrade,
            'peergradeduserid' => $peergradeduserid,
            'aggregation' => $aggregationmethod
    );
    if (!$rm->check_peergrade_is_valid($params)) {
        $result->error = get_string('peergradeinvalid', 'peergrade');
        echo json_encode($result);
        die();
    }
}

//peergrade options used to update the peergrade then retrieve the aggregate
$peergradeoptions = new stdClass;
$peergradeoptions->context = $context;
$peergradeoptions->peergradearea = $peergradearea;
$peergradeoptions->component = $component;
$peergradeoptions->itemid = $itemid;
$peergradeoptions->scaleid = $scaleid;
$peergradeoptions->userid = $USER->id;

if ($userpeergrade != PEERGRADE_UNSET_PEERGRADE) {
    $peergrade = new peergrade($peergradeoptions);
    $peergrade->update_peergrade($userpeergrade);
} else { //delete the peergrade if the user set to Rate...
    $options = new stdClass;
    $options->contextid = $context->id;
    $options->component = $component;
    $options->peergradearea = $peergradearea;
    $options->userid = $USER->id;
    $options->itemid = $itemid;

    $rm->delete_peergrades($options);
}

// Future possible enhancement: add a setting to turn grade updating off for those who don't want them in gradebook
// note that this would need to be done in both peergrade.php and peergrade_ajax.php
if ($context->contextlevel == CONTEXT_MODULE) {
    //tell the module that its grades have changed
    $modinstance = $DB->get_record($cm->modname, array('id' => $cm->instance));
    if ($modinstance) {
        $modinstance->cmidnumber = $cm->id; //MDL-12961
        $functionname = $cm->modname . '_update_grades';
        require_once($CFG->dirroot . "/mod/{$cm->modname}/lib.php");
        if (function_exists($functionname)) {
            $functionname($modinstance, $peergradeduserid);
        }
    }
}

//object to return to client as json
$result->success = true;

//need to retrieve the updated item to get its new aggregate value
$item = new stdClass;
$item->id = $itemid;

//most of $peergradeoptions variables were previously set
$peergradeoptions->items = array($item);
$peergradeoptions->aggregate = $aggregationmethod;

$items = $rm->get_peergrades($peergradeoptions);
$firstpeergrade = $items[0]->peergrade;

//for custom scales return text not the value
//this scales weirdness will go away when scales are refactored
$scalearray = null;
$aggregatetoreturn = round($firstpeergrade->aggregate, 1);

// Output a dash if aggregation method == COUNT as the count is output next to the aggregate anyway
if ($firstpeergrade->settings->aggregationmethod == PEERGRADE_AGGREGATE_COUNT or $firstpeergrade->count == 0) {
    $aggregatetoreturn = ' - ';
} else if ($firstpeergrade->settings->scale->id < 0) { //if its non-numeric scale
    //dont use the scale item if the aggregation method is sum as adding items from a custom scale makes no sense
    if ($firstpeergrade->settings->aggregationmethod != PEERGRADE_AGGREGATE_SUM) {
        $scalerecord = $DB->get_record('scale', array('id' => -$firstpeergrade->settings->scale->id));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            $aggregatetoreturn = $scalearray[$aggregatetoreturn - 1];
        }
    }
}

//See if the user has permission to see the peergrade aggregate
if ($firstpeergrade->user_can_view_aggregate()) {
    $result->aggregate = $aggregatetoreturn;
    $result->count = $firstpeergrade->count;
    $result->itemid = $itemid;
}

echo json_encode($result);