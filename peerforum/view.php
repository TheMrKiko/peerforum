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
 * Displays the list of discussions in a peerforum.
 *
 * @package   mod_peerforum
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_peerforum\grades\peerforum_gradeitem;

require_once('../../config.php');

$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$peerforumvault = $vaultfactory->get_peerforum_vault();
$discussionvault = $vaultfactory->get_discussion_vault();
$postvault = $vaultfactory->get_post_vault();
$discussionlistvault = $vaultfactory->get_discussions_in_peerforum_vault();

$cmid = optional_param('id', 0, PARAM_INT);
$peerforumid = optional_param('f', 0, PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$showall = optional_param('showall', '', PARAM_INT);
$pageno = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_CLEAN);
$pageno = optional_param('p', $pageno, PARAM_INT);
$pagesize = optional_param('s', 0, PARAM_INT);
$sortorder = optional_param('o', null, PARAM_INT);

if (!$cmid && !$peerforumid) {
    print_error('missingparameter');
}

if ($cmid) {
    $peerforum = $peerforumvault->get_from_course_module_id($cmid);
    if (empty($peerforum)) {
        throw new \moodle_exception('Unable to find peerforum with cmid ' . $cmid);
    }
} else {
    $peerforum = $peerforumvault->get_from_id($peerforumid);
    if (empty($peerforum)) {
        throw new \moodle_exception('Unable to find peerforum with id ' . $peerforumid);
    }
}

if (!empty($showall)) {
    // The user wants to see all discussions.
    $pageno = 0;
    $pagesize = 0;
}

$urlfactory = mod_peerforum\local\container::get_url_factory();
$capabilitymanager = $managerfactory->get_capability_manager($peerforum);

$url = $urlfactory->get_peerforum_view_url_from_peerforum($peerforum);
$PAGE->set_url($url);

$course = $peerforum->get_course_record();
$coursemodule = $peerforum->get_course_module_record();
$cm = \cm_info::create($coursemodule);

require_course_login($course, true, $cm);

$istypesingle = $peerforum->get_type() === 'single';
$saveddisplaymode = get_user_preferences('peerforum_displaymode', $CFG->peerforum_displaymode);

if ($mode) {
    $displaymode = $mode;
} else {
    $displaymode = $saveddisplaymode;
}

if (get_user_preferences('forum_useexperimentalui', false)) {
    if ($displaymode == PEERFORUM_MODE_NESTED) {
        $displaymode = PEERFORUM_MODE_NESTED_V2;
    }
} else {
    if ($displaymode == PEERFORUM_MODE_NESTED_V2) {
        $displaymode = PEERFORUM_MODE_NESTED;
    }
}

if ($displaymode != $saveddisplaymode) {
    set_user_preference('peerforum_displaymode', $displaymode);
}

$PAGE->set_context($peerforum->get_context());
$PAGE->set_title($peerforum->get_name());
$PAGE->add_body_class('peerforumtype-' . $peerforum->get_type());
$PAGE->set_heading($course->fullname);
$PAGE->set_button(peerforum_search_form($course, $search));

if ($istypesingle && $displaymode == PEERFORUM_MODE_NESTED_V2) {
    $PAGE->add_body_class('nested-v2-display-mode reset-style');
    $settingstrigger = $OUTPUT->render_from_template('mod_peerforum/settings_drawer_trigger', null);
    $PAGE->add_header_action($settingstrigger);
}

if (empty($cm->visible) && !has_capability('moodle/course:viewhiddenactivities', $peerforum->get_context())) {
    redirect(
            $urlfactory->get_course_url_from_peerforum($peerforum),
            get_string('activityiscurrentlyhidden'),
            null,
            \core\output\notification::NOTIFY_WARNING
    );
}

if (!$capabilitymanager->can_view_discussions($USER)) {
    redirect(
            $urlfactory->get_course_url_from_peerforum($peerforum),
            get_string('noviewdiscussionspermission', 'peerforum'),
            null,
            \core\output\notification::NOTIFY_WARNING
    );
}

// Mark viewed and trigger the course_module_viewed event.
$peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();
$peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);
peerforum_view(
        $peerforumrecord,
        $peerforum->get_course_record(),
        $peerforum->get_course_module_record(),
        $peerforum->get_context()
);

// Return here if we post or set subscription etc.
$SESSION->fromdiscussion = qualified_me();

if (!empty($CFG->enablerssfeeds) && !empty($CFG->peerforum_enablerssfeeds) && $peerforum->get_rss_type() &&
        $peerforum->get_rss_articles()) {
    require_once("{$CFG->libdir}/rsslib.php");

    $rsstitle = format_string($course->shortname, true, [
                    'context' => context_course::instance($course->id),
            ]) . ': ' . format_string($peerforum->get_name());
    rss_add_http_header($peerforum->get_context(), 'mod_peerforum', $peerforumrecord, $rsstitle);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($peerforum->get_name()), 2);

if (!$istypesingle && !empty($peerforum->get_intro())) {
    echo $OUTPUT->box(format_module_intro('peerforum', $peerforumrecord, $cm->id), 'generalbox', 'intro');
}

if ($sortorder) {
    set_user_preference('peerforum_discussionlistsortorder', $sortorder);
}

$sortorder = get_user_preferences('peerforum_discussionlistsortorder', $discussionlistvault::SORTORDER_LASTPOST_DESC);

// Fetch the current groupid.
$groupid = groups_get_activity_group($cm, true) ?: null;
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
switch ($peerforum->get_type()) {
    case 'single':
        $peerforumgradeitem = peerforum_gradeitem::load_from_peerforum_entity($peerforum);
        if ($capabilitymanager->can_grade($USER)) {

            if ($peerforumgradeitem->is_grading_enabled()) {
                $groupid = groups_get_activity_group($cm, true) ?: null;
                $gradeobj = (object) [
                        'contextid' => $peerforum->get_context()->id,
                        'cmid' => $cmid,
                        'name' => $peerforum->get_name(),
                        'courseid' => $course->id,
                        'coursename' => $course->shortname,
                        'experimentaldisplaymode' => $displaymode == PEERFORUM_MODE_NESTED_V2,
                        'groupid' => $groupid,
                        'gradingcomponent' => $peerforumgradeitem->get_grading_component_name(),
                        'gradingcomponentsubtype' => $peerforumgradeitem->get_grading_component_subtype(),
                        'sendstudentnotifications' => $peerforum->should_notify_students_default_when_grade_for_peerforum(),
                ];
                echo $OUTPUT->render_from_template('mod_peerforum/grades/grade_button', $gradeobj);
            }
        } else {
            if ($peerforumgradeitem->is_grading_enabled()) {
                $groupid = groups_get_activity_group($cm, true) ?: null;
                $gradeobj = (object) [
                        'contextid' => $peerforum->get_context()->id,
                        'cmid' => $cmid,
                        'name' => $peerforum->get_name(),
                        'courseid' => $course->id,
                        'coursename' => $course->shortname,
                        'groupid' => $groupid,
                        'userid' => $USER->id,
                        'gradingcomponent' => $peerforumgradeitem->get_grading_component_name(),
                        'gradingcomponentsubtype' => $peerforumgradeitem->get_grading_component_subtype(),
                ];
                echo $OUTPUT->render_from_template('mod_peerforum/grades/view_grade_button', $gradeobj);
            }
        }
        $discussion = $discussionvault->get_last_discussion_in_peerforum($peerforum);
        $discussioncount = $discussionvault->get_count_discussions_in_peerforum($peerforum);
        $hasmultiplediscussions = $discussioncount > 1;
        $discussionsrenderer = $rendererfactory->get_single_discussion_list_renderer($peerforum, $discussion,
                $hasmultiplediscussions, $displaymode);
        $post = $postvault->get_from_id($discussion->get_first_post_id());
        $orderpostsby = $displaymode == PEERFORUM_MODE_FLATNEWEST ? 'created DESC' : 'created ASC';
        $replies = $postvault->get_replies_to_post(
                $USER,
                $post,
                $capabilitymanager->can_view_any_private_reply($USER),
                $orderpostsby
        );
        echo $discussionsrenderer->render($USER, $post, $replies);

        if (!$CFG->peerforum_usermarksread && peerforum_tp_is_tracked($peerforumrecord, $USER)) {
            $postids = array_map(function($post) {
                return $post->get_id();
            }, array_merge([$post], array_values($replies)));
            peerforum_tp_mark_posts_read($USER, $postids);
        }
        break;
    case 'blog':
        $discussionsrenderer = $rendererfactory->get_blog_discussion_list_renderer($peerforum);
        // Blog peerforums always show discussions newest first.
        echo $discussionsrenderer->render($USER, $cm, $groupid, $discussionlistvault::SORTORDER_CREATED_DESC,
                $pageno, $pagesize);

        if (!$CFG->peerforum_usermarksread && peerforum_tp_is_tracked($peerforumrecord, $USER)) {
            $discussions = mod_peerforum_get_discussion_summaries($peerforum, $USER, $groupid, null, $pageno, $pagesize);
            $firstpostids = array_map(function($discussion) {
                return $discussion->get_first_post()->get_id();
            }, array_values($discussions));
            peerforum_tp_mark_posts_read($USER, $firstpostids);
        }
        break;
    default:
        $discussionsrenderer = $rendererfactory->get_discussion_list_renderer($peerforum);
        echo $discussionsrenderer->render($USER, $cm, $groupid, $sortorder, $pageno, $pagesize, $displaymode);
}

echo $OUTPUT->footer();
