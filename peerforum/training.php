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
 * Edit and save a new post to a discussion
 * Custom functions to allow peergrading of PeerForum posts
 *
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./classes/training_form.php');

$page = optional_param('page', 0, PARAM_INT);
$submitid = optional_param('submitid', null, PARAM_INT);

$PAGE->set_url('/mod/peerforum/training.php', array(
        'page' => $page,
        'submitid' => $submitid,
));
// These page_params will be passed as hidden variables later in the form.
$pageparams = array('page' => $page, 'submitid' => $submitid);

$sitecontext = context_system::instance();

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$urlfactory = mod_peerforum\local\container::get_url_factory();

$peerforumvault = $vaultfactory->get_peerforum_vault();
$peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();

$trainingpagevault = $vaultfactory->get_training_page_vault();
$trainingsubmissionvault = $vaultfactory->get_training_submission_vault();

if (!isloggedin() or isguestuser()) {
    require_login();
}

require_login(0, false);   // Script is useless unless they're logged in.


if (!empty($submitid)) {
    // Editing the page.

    $trainingsubmissionentity = $trainingsubmissionvault->get_from_id($submitid);
    if (empty($trainingsubmissionentity)) {
        print_error('invalidpostid', 'peerforum');
    }

    // Load up the $trainingpage variable.
    $trainingsubmission = $trainingsubmissionentity;
    $page = $trainingsubmission->pageid;
}

if (!empty($page)) {
    // Submitting an answer.

    $trainingpageentity = $trainingpagevault->get_from_id($page);
    if (empty($trainingpageentity)) {
        print_error('invalidpostid', 'peerforum');
    }

    $peerforumentity = $peerforumvault->get_from_id($trainingpageentity->peerforum);
    if (empty($peerforumentity)) {
        print_error('invalidpeerforumid', 'peerforum');
    }

    $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
    $trainingpage = $trainingpageentity;
    $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);
    $course = $peerforumentity->get_course_record();
    $modcontext = $peerforumentity->get_context();
    $coursecontext = context_course::instance($course->id);

    if (!$cm = get_coursemodule_from_instance("peerforum", $peerforum->id, $course->id)) {
        print_error('invalidcoursemodule');
    }

    $PAGE->set_cm($cm, $course, $peerforum);

    if (!has_capability('mod/peerforum:editanypost', $modcontext)) { // TODO change!
        print_error('cannoteditposts', 'peerforum');
    }

    // Load up the $trainingpage variable.
    $trainingpage->submitid = $submitid;
    $trainingpage->course = $course->id;
    $trainingpage->peerforum = $peerforum->id;

    $SESSION->fromurl = get_local_referer(false);

} else {
    print_error('unknowaction');
}

// From now on user must be logged on properly.

require_login($course, false, $cm);

if (isguestuser()) {
    // Just in case.
    print_error('noguest');
}

$trainingpage->description = file_rewrite_pluginfile_urls($trainingpage->description, 'pluginfile.php',
        $modcontext->id, 'mod_peerforum', 'training', $trainingpage->id);

$trainingpage->exercise['description'] = $trainingpage->exercise['description'] ?? array();
foreach ($trainingpage->exercise['description'] as $k => $d) {
    $trainingpage->exercise['description'][$k] = file_rewrite_pluginfile_urls($d->description, 'pluginfile.php',
            $modcontext->id, 'mod_peerforum', 'training', $trainingpage->id.$k);
}

$mformpage = new mod_peerforum_training_form('training.php', [
        'modcontext' => $modcontext,
        'peerforum' => $peerforum,
        'trainingpage' => $trainingpage,
        'submission' => $trainingsubmission ?? null,
]);

// Load data into form NOW!
$grades = !isset($trainingsubmission) ? null : $trainingsubmission->grades;

$mformpage->set_data(
        array(
                'name' => $trainingpage->name,
                'peerforum' => $peerforum->name,
                'course' => $course->id,
                'exercises' => $trainingpage->exercises,
                'open' => time(),
                'grades' => $grades,
        ) +
        $pageparams
);

if ($mformpage->is_cancelled()) {

    redirect($urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity));

} else if ($mformpage->is_submitted() && $fromform = $mformpage->get_data()) {


    if (empty($SESSION->fromurl)) {
        $errordestination = $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity);
    } else {
        $errordestination = $SESSION->fromurl;
    }

    $trainingexercise = $fromform;
    $trainingpage->grades = $fromform->grades;
    $trainingpage->open = $fromform->open;

    $submitid = peerforum_submit_training_page($trainingpage, $mformpage);

    if (!empty($submitid)) {
        redirect(
                "training.php?page=$page&submitid=$submitid",
        );
    }

    print_error("couldnotupdate", "peerforum", $errordestination);
    exit;

}

// This section is only shown after all checks are in place, and the peerforumentity and any relevant discussion and post
// entity are available.

$titlesubject = format_string($trainingpage->name, true);

$strparentname = 'Training page';
$PAGE->navbar->add($strparentname);

$PAGE->set_title("{$course->shortname}: {$strparentname}: {$titlesubject}");
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($trainingpage->name), 2);

// Checkup.

if (!$capabilitymanager->can_create_discussions($USER)) {
    print_error('cannotcreatediscussion', 'peerforum');
}

echo $OUTPUT->box($trainingpage->description);


$mformpage->display();

echo $OUTPUT->footer();

// usar tree_block_contents na tabela
// usar tabtree para o grading
