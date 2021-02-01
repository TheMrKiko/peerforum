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

$peerforum = optional_param('peerforum', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/mod/peerforum/build_training.php', array(
        'peerforum' => $peerforum,
        'edit' => $edit,
        'delete' => $delete,
        'confirm' => $confirm,
));
// These page_params will be passed as hidden variables later in the form.
$pageparams = array('peerforum' => $peerforum, 'edit' => $edit);

$sitecontext = context_system::instance();

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$urlfactory = mod_peerforum\local\container::get_url_factory();

$peerforumvault = $vaultfactory->get_peerforum_vault();
$peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();

$trainingpagevault = $vaultfactory->get_training_page_vault();

if (!isloggedin() or isguestuser()) {
    require_login();
}

require_login(0, false);   // Script is useless unless they're logged in.

if (!empty($peerforum)) {
    // User is adding a new training page in a peerforum.
    $peerforumentity = $peerforumvault->get_from_id($peerforum);
    if (empty($peerforumentity)) {
        print_error('invalidpeerforumid', 'peerforum');
    }

    $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
    $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);
    $course = $peerforumentity->get_course_record();
    if (!$cm = get_coursemodule_from_instance("peerforum", $peerforum->id, $course->id)) {
        print_error("invalidcoursemodule");
    }

    // Retrieve the contexts.
    $modcontext = $peerforumentity->get_context();
    $coursecontext = context_course::instance($course->id);

    if (!$capabilitymanager->can_create_discussions($USER)) { // TODO change!
        print_error('nopostpeerforum', 'peerforum');
    }

    $SESSION->fromurl = get_local_referer(false);

    // Load up the $trainingpage variable.

    $trainingpage = new stdClass();
    $trainingpage->course = $course->id;
    $trainingpage->peerforum = $peerforum->id;
    $trainingpage->discussion = 0;           // Ie discussion # not defined yet.
    $trainingpage->name = null;
    $trainingpage->description = null;
    $trainingpage->descriptionformat = editors_get_preferred_format();
    $trainingpage->descriptiontrust = 0;
    $trainingpage->examples = 0;
    $trainingpage->id_eg = array();
    $trainingpage->name_eg = array();
    $trainingpage->description_eg = array();


} else if (!empty($edit)) {
    // Editing the page.

    $trainingpageentity = $trainingpagevault->get_from_id($edit);
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

    if (!has_capability('mod/peerforum:editanypost', $modcontext)) {
        print_error('cannoteditposts', 'peerforum');
    }

    // Load up the $trainingpage variable.
    $trainingpage->edit = $edit;
    $trainingpage->course = $course->id;
    $trainingpage->peerforum = $peerforum->id;

    $trainingpage = trusttext_pre_edit($trainingpage, 'description', $modcontext);

    $descriptionegs = array();
    foreach ($trainingpage->description_eg as $key => $eg) {
        $descriptionegs[$key] = trusttext_pre_edit($trainingpage->description_eg[$key], 'description', $modcontext);
    }
    $trainingpage->description_eg = $descriptionegs;

} else if (!empty($delete)) {
    // User is deleting a page.

    $trainingpageentity = $trainingpagevault->get_from_id($delete);
    if (empty($trainingpageentity)) {
        print_error('invalidpostid', 'peerforum');
    }

    $peerforumentity = $peerforumvault->get_from_id($trainingpageentity->peerforum);
    if (empty($peerforumentity)) {
        print_error('invalidpeerforumid', 'peerforum');
    }

    $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
    $course = $peerforumentity->get_course_record();
    $cm = $peerforumentity->get_course_module_record();
    $modcontext = $peerforumentity->get_context();

    require_login($course, false, $cm);

    $replycount = $postvault->get_reply_count_for_post_id_in_discussion_id(
            $USER, $postentity->get_id(), $discussionentity->get_id(), true);

    if (!empty($confirm) && confirm_sesskey()) {
        // Do further checks and delete the post.
        $hasreplies = $replycount > 0;

        try {
            $capabilitymanager->validate_delete_post($USER, $discussionentity, $postentity, $hasreplies);

            if (!$postentity->has_parent()) {
                peerforum_delete_discussion(
                        $discussiondatamapper->to_legacy_object($discussionentity),
                        false,
                        $peerforumentity->get_course_record(),
                        $peerforumentity->get_course_module_record(),
                        $peerforumdatamapper->to_legacy_object($peerforumentity)
                );

                $DB->delete_records("peerforum_peergrade_subject",
                        array('name' => $post->subject, 'courseid' => $course->id, 'peerforumid' => $peerforum->id));

                redirect(
                        $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity),
                        get_string('eventdiscussiondeleted', 'peerforum'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                peerforum_delete_post(
                        $postdatamapper->to_legacy_object($postentity),
                        has_capability('mod/peerforum:deleteanypost', $modcontext),
                        $peerforumentity->get_course_record(),
                        $peerforumentity->get_course_module_record(),
                        $peerforumdatamapper->to_legacy_object($peerforumentity)
                );

                $discussionurl = $urlfactory->get_discussion_view_url_from_discussion($discussionentity);

                redirect(
                        peerforum_go_back_to($discussionurl),
                        get_string('eventpostdeleted', 'peerforum'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                );
            }
        } catch (Exception $e) {
            redirect(
                    $urlfactory->get_discussion_view_url_from_discussion($discussionentity),
                    $e->getMessage(),
                    null,
                    \core\output\notification::NOTIFY_ERROR
            );
        }

    } else {

        if (!$capabilitymanager->can_delete_post($USER, $discussionentity, $postentity)) {
            redirect(
                    $urlfactory->get_discussion_view_url_from_discussion($discussionentity),
                    get_string('cannotdeletepost', 'peerforum'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
            );
        }

        $post = $postdatamapper->to_legacy_object($postentity);
        $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);

        // User just asked to delete something.
        peerforum_set_return();
        $PAGE->navbar->add(get_string('delete', 'peerforum'));
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);

        if ($replycount) {
            if (!has_capability('mod/peerforum:deleteanypost', $modcontext)) {
                redirect(
                        peerforum_go_back_to($urlfactory->get_view_post_url_from_post($postentity)),
                        get_string('couldnotdeletereplies', 'peerforum'),
                        null,
                        \core\output\notification::NOTIFY_ERROR
                );
            }

            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($peerforum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesureplural", "peerforum", $replycount + 1),
                    "post.php?delete=$delete&confirm=$delete",
                    $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . '#p' . $post->id);

            $postentities = [$postentity];
            if (empty($post->edit)) {
                $postvault = $vaultfactory->get_post_vault();
                $replies = $postvault->get_replies_to_post(
                        $USER,
                        $postentity,
                        // Note: All replies are fetched here as the user has deleteanypost.
                        true,
                        'created ASC'
                );
                $postentities = array_merge($postentities, $replies);
            }

            $rendererfactory = mod_peerforum\local\container::get_renderer_factory();
            $postsrenderer = $rendererfactory->get_single_discussion_posts_renderer(PEERFORUM_MODE_NESTED, true);
            echo $postsrenderer->render($USER, [$peerforumentity], [$discussionentity], $postentities);
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($peerforum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesure", "peerforum", $replycount),
                    "post.php?delete=$delete&confirm=$delete",
                    $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . '#p' . $post->id);

            $rendererfactory = mod_peerforum\local\container::get_renderer_factory();
            $postsrenderer = $rendererfactory->get_single_discussion_posts_renderer(null, true);
            echo $postsrenderer->render($USER, [$peerforumentity], [$discussionentity], [$postentity]);
        }

    }
    echo $OUTPUT->footer();
    die;

} else {
    print_error('unknowaction');
}

// From now on user must be logged on properly.

require_login($course, false, $cm);

if (isguestuser()) {
    // Just in case.
    print_error('noguest');
}

$mformpage = new mod_peerforum_build_training_form('build_training.php', [
        'course' => $course,
        'cm' => $cm,
        'coursecontext' => $coursecontext,
        'modcontext' => $modcontext,
        'peerforum' => $peerforum,
        'trainingpage' => $trainingpage,
        'edit' => $edit,
]);

// Load data into form NOW!

$formheading = '';
$heading = get_string('yournewtopic', 'peerforum');

$trainingpageid = empty($trainingpage->id) ? null : $trainingpage->id;
$draftideditor = file_get_submitted_draft_itemid('description');
$editoropts = mod_peerforum_build_training_form::editor_options();
$currenttext = file_prepare_draft_area($draftideditor, $modcontext->id, 'mod_peerforum', 'training',
        $trainingpageid, $editoropts, $trainingpage->description);

$descriptionegs = array();
foreach ($trainingpage->description_eg as $key => $eg) {
    $draftideditoreg = file_get_submitted_draft_itemid('description');
    $editoropts = mod_peerforum_build_training_form::editor_options();
    $currenttexteg = file_prepare_draft_area($draftideditoreg, $modcontext->id, 'mod_peerforum', 'training',
            $trainingpageid.$key, $editoropts, $eg->description);

    $descriptionegs[$key] = array(
                'text' => $currenttexteg,
                'format' => !isset($eg->descriptionformat) || !is_numeric($eg->descriptionformat) ?
                        editors_get_preferred_format() : $eg->descriptionformat,
                'itemid' => $draftideditoreg
    );
}

$mformpage->set_data(
        array(
                'general' => $heading, // TODO fill
                'name' => $trainingpage->name,
                'description' => array(
                        'text' => $currenttext,
                        'format' => !isset($trainingpage->descriptionformat) || !is_numeric($trainingpage->descriptionformat) ?
                                editors_get_preferred_format() : $trainingpage->descriptionformat,
                        'itemid' => $draftideditor
                ),
                'course' => $course->id,
                'examples' => $trainingpage->examples,
                'id_eg' => $trainingpage->id_eg,
                'name_eg' => $trainingpage->name_eg,
                'description_eg' => $descriptionegs,
        ) +

        $pageparams +

        // (isset($discussion->timestart) ? array('timestart' => $discussion->timestart) : array()) +

        // (isset($discussion->timeend) ? array('timeend' => $discussion->timeend) : array()) +

        (isset($trainingpage->discussion) ? array('discussion' => $trainingpage->discussion) : array())
);

if ($mformpage->is_cancelled()) {

    redirect($urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity));

} else if ($mformpage->is_submitted() && $fromform = $mformpage->get_data()) {

    if (empty($SESSION->fromurl)) {
        $errordestination = $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity);
    } else {
        $errordestination = $SESSION->fromurl;
    }

    $fromform->itemid = $fromform->description['itemid'];
    $fromform->descriptionformat = $fromform->description['format'];
    $fromform->description = $fromform->description['text'];
    // WARNING: the $fromform->description array has been overwritten, do not use it anymore!
    $fromform->descriptiontrust = trusttext_trusted($modcontext);

    // Clean description text.
    $fromform = trusttext_pre_edit($fromform, 'description', $modcontext);

    $newdescriptioneg = array();
    foreach ($fromform->description_eg as $key => $eg) {
        $newdescriptioneg[$key] = new stdClass();
        $newdescriptioneg[$key]->itemid = $eg['itemid'];
        $newdescriptioneg[$key]->descriptionformat = $eg['format'];
        $newdescriptioneg[$key]->description = $eg['text'];
        // WARNING: the $fromform->description array has been overwritten, do not use it anymore!
        $newdescriptioneg[$key]->descriptiontrust = trusttext_trusted($modcontext);

        // Clean description text.
        $newdescriptioneg[$key] = trusttext_pre_edit($newdescriptioneg[$key], 'description', $modcontext);
    }
    $fromform->description_eg = $newdescriptioneg;

    if ($fromform->edit) {
        // Updating a post.
        $fromform->id = $fromform->edit;
        $description = '';

        if (false && !$capabilitymanager->can_edit_post($USER)) { //hack
            redirect(
                    $urlfactory->get_view_post_url_from_post($postentity),
                    get_string('cannotupdatepost', 'peerforum'),
                    null,
                    \core\output\notification::ERROR
            );
        }

        $updatetrainingpage = $fromform;
        $updatetrainingpage->peerforum = $peerforum->id;
        if (!peerforum_update_training_page($updatetrainingpage, $mformpage)) {
            print_error("couldnotupdate", "peerforum", $errordestination);
        }

        // peerforum_trigger_post_updated_event($post, $discussion, $modcontext, $peerforum);

        $description .= get_string("postupdated", "peerforum");

        $discussionurl = $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity);

        redirect(
                peerforum_go_back_to($discussionurl),
                $description,
                null,
                \core\output\notification::NOTIFY_SUCCESS
        );

    } else {
        // Adding a new page.

        $description = '';
        $addtrainingpage = $fromform;
        $addtrainingpage->peerforum = $peerforum->id;
        if ($fromform->id = peerforum_add_new_training_page($addtrainingpage, $mformpage)) {
            $trainingpageentity = $trainingpagevault->get_from_id($fromform->id);
            $fromform->deleted = 0;

            $description .= '<p>' . get_string("postaddedsuccess", "peerforum") . '</p>';

            $discussionurl = $urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity);

            $params = array(
                    'context' => $modcontext,
                    'objectid' => $fromform->id,
                    'other' => array(
                            'peerforumid' => $peerforum->id,
                    )
            );
            /*$event = \mod_peerforum\event\post_created::create($params);
            $event->add_record_snapshot('peerforum_posts', $fromform);
            $event->add_record_snapshot('peerforum_discussions', $discussion);
            $event->trigger();*/

            redirect(
                    peerforum_go_back_to($discussionurl),
                    $description,
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
            );

        } else {
            print_error("couldnotadd", "peerforum", $errordestination);
        }
        exit;
    }
}

// This section is only shown after all checks are in place, and the peerforumentity and any relevant discussion and post
// entity are available.

if (!empty($edit)) {
    $titlesubject = format_string($trainingpage->name, true);
} else {
    $titlesubject = get_string("addanewdiscussion", "peerforum");
}

if (empty($trainingpage->edit)) {
    $trainingpage->edit = '';
}

$strparentname = 'Training pages builder';
$PAGE->navbar->add($strparentname);

if ($edit) {
    $PAGE->navbar->add(get_string('edit', 'peerforum'));
}

$PAGE->set_title("{$course->shortname}: {$strparentname}: {$titlesubject}");
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($peerforum->name), 2);

// Checkup.

if (empty($edit) && !$capabilitymanager->can_create_discussions($USER)) {
    print_error('cannotcreatediscussion', 'peerforum');
}

if (!empty($peerforum->intro)) {
    echo $OUTPUT->box(format_module_intro('peerforum', $peerforum, $cm->id), 'generalbox', 'intro');
}

if (!empty($formheading)) {
    echo $OUTPUT->heading($formheading, 2, array('class' => 'accesshide'));
}

$mformpage->display();

echo $OUTPUT->footer();
