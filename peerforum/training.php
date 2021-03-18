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
 * Display a training page.
 *
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./classes/training_form.php');

$page = optional_param('page', 0, PARAM_INT);
$submitid = optional_param('submitid', null, PARAM_INT);
$openid = optional_param('openid', null, PARAM_INT);

$PAGE->set_url('/mod/peerforum/training.php', array(
        'page' => $page,
        'submitid' => $submitid,
));
// These page_params will be passed as hidden variables later in the form.
$pageparams = array('page' => $page, 'submitid' => $submitid);

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
    // Viewing the answers.

    $trainingsubmissionentity = $trainingsubmissionvault->get_from_id($submitid);
    if (empty($trainingsubmissionentity)) {
        print_error('invalidpostid', 'peerforum');
    }

    if ($trainingsubmissionentity->userid != $USER->id) {
        print_error('differentusersubmission', 'peerforum');
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

    // Load up the $trainingpage variable.
    $trainingpage->submitid = $submitid;
    $trainingpage->course = $course->id;
    $trainingpage->peerforum = $peerforum->id;

    if (empty($openid)) {
        // Store a new empty submission on the database.
        $openid = peerforum_enter_training_page($trainingpage);
        if (empty($submitid)) {
            unset($SESSION->fromurl);

            $lastcorrsubm = $trainingsubmissionvault->get_correct_from_page_id_and_user_id($page, $USER->id);
            if (!empty($lastcorrsubm)) {
                \core\notification::info(
                        'You already completed this training with success before! '
                        . html_writer::link($urlfactory->get_training_url($trainingpage, reset($lastcorrsubm)->id),
                        'See how i did.')
                );
            }
        }
    }

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

if ($peerforum->peergradeassessed) {
    $peergradeoptions = (object) [
            'context' => $modcontext,
            'component' => 'mod_peerforum',
            'peergradearea' => 'post',
            'items' => array((object) [
                    'id' => 0,
                    'userid' => 0
            ]),
            'aggregate' => $peerforum->peergradeassessed,
            'peergradescaleid' => $peerforum->peergradescale,
            'userid' => 0
    ];

    $pgm = \mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
    $peergrade = $pgm->get_peergrades($peergradeoptions)[0]->peergrade;
}
$peergradescaleitems = $peergrade->settings->peergradescale->peergradescaleitems ?? array();

$mformpage = new mod_peerforum_training_form('training.php', [
        'peerforum' => $peerforum,
        'peergradescaleitems' => $peergradescaleitems,
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
                'openid' => $openid,
                'grades' => $grades,
                'previous' => $submitid ?: 0,
        ) +
        $pageparams
);

if (empty($SESSION->fromurl)) {
    $gobackdestination = $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity);
} else {
    $gobackdestination = $SESSION->fromurl;
}

if ($mformpage->is_cancelled()) {
    unset($SESSION->fromurl);
    redirect($gobackdestination);

} else if ($mformpage->is_submitted() && $fromform = $mformpage->get_data()) {

    $trainingexercise = $fromform;
    $trainingpage->grades = $fromform->grades ?? array();
    $trainingpage->openid = $fromform->openid;
    $trainingpage->previous = $fromform->previous;

    list($submitid, $allcorrect) = peerforum_submit_training_page($trainingpage, $mformpage);

    if (!empty($submitid)) {
        redirect(
                $urlfactory->get_training_url($trainingpage, $submitid),
                'Training answers submitted! ' .
                (!$allcorrect ? 'Spoilers? You got things wrong, lels. ' : '') .
                '<a href="#e">View results</a>.',
                null,
                $allcorrect ? \core\output\notification::NOTIFY_SUCCESS :
                        \core\output\notification::NOTIFY_WARNING
        );
    }

    print_error("couldnotupdate", "peerforum", $gobackdestination);
    exit;
}

if (empty($SESSION->fromurl)) {
    $SESSION->fromurl = get_local_referer(false);
}

// This section is only shown after all checks are in place, and the peerforumentity and any relevant discussion and post
// entity are available.

$titlesubject = format_string($trainingpage->name, true);

$strparentname = 'Training page';
$PAGE->navbar->add($strparentname);

if ($capabilitymanager->can_edit_training_pages($USER)) {
    $PAGE->set_button(html_writer::link($urlfactory->get_training_edit_url($trainingpage), 'Edit training page'));
}

$PAGE->set_title("{$course->shortname}: {$strparentname}: {$titlesubject}");
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($trainingpage->name), 2);

echo $OUTPUT->box($trainingpage->description);
echo html_writer::empty_tag('div', array('id' => 'e'));

$mformpage->display();

echo $OUTPUT->footer();
