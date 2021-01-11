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
 * Displays a post, and all the posts below it.
 * If no post is given, displays all posts in a discussion
 *
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$d = required_param('d', PARAM_INT);                // Discussion ID
$parent = optional_param('parent', 0, PARAM_INT);        // If set, then display this post and all children.
$mode = optional_param('mode', 0, PARAM_INT);          // If set, changes the layout of the thread
$move = optional_param('move', 0, PARAM_INT);          // If set, moves this discussion to another peerforum
$mark = optional_param('mark', '', PARAM_ALPHA);       // Used for tracking read posts if user initiated.
$postid = optional_param('postid', 0, PARAM_INT);        // Used for tracking read posts if user initiated.
$pin = optional_param('pin', -1, PARAM_INT);          // If set, pin or unpin this discussion.

$url = new moodle_url('/mod/peerforum/discuss.php', array('d' => $d));
if ($parent !== 0) {
    $url->param('parent', $parent);
}
$PAGE->set_url($url);

$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$discussionvault = $vaultfactory->get_discussion_vault();
$discussion = $discussionvault->get_from_id($d);

if (!$discussion) {
    throw new \moodle_exception('Unable to find discussion with id ' . $discussionid);
}

$peerforumvault = $vaultfactory->get_peerforum_vault();
$peerforum = $peerforumvault->get_from_id($discussion->get_peerforum_id());

if (!$peerforum) {
    throw new \moodle_exception('Unable to find peerforum with id ' . $discussion->get_peerforum_id());
}

$course = $peerforum->get_course_record();
$cm = $peerforum->get_course_module_record();

require_course_login($course, true, $cm);

$managerfactory = mod_peerforum\local\container::get_manager_factory();
$capabilitymanager = $managerfactory->get_capability_manager($peerforum);
$urlfactory = mod_peerforum\local\container::get_url_factory();

// Make sure we can render.
if (!$capabilitymanager->can_view_discussions($USER)) {
    throw new moodle_exception('noviewdiscussionspermission', 'mod_peerforum');
}

$datamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$peerforumdatamapper = $datamapperfactory->get_peerforum_data_mapper();
$peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);
$discussiondatamapper = $datamapperfactory->get_discussion_data_mapper();
$discussionrecord = $discussiondatamapper->to_legacy_object($discussion);
$discussionviewurl = $urlfactory->get_discussion_view_url_from_discussion($discussion);

// move this down fix for MDL-6926
require_once($CFG->dirroot . '/mod/peerforum/lib.php');

$modcontext = $peerforum->get_context();

if (
        !empty($CFG->enablerssfeeds) &&
        !empty($CFG->peerforum_enablerssfeeds) &&
        $peerforum->get_rss_type() &&
        $peerforum->get_rss_articles()
) {
    require_once("$CFG->libdir/rsslib.php");

    $rsstitle = format_string(
            $course->shortname,
            true,
            ['context' => context_course::instance($course->id)]
    );
    $rsstitle .= ': ' . format_string($peerforum->get_name());
    rss_add_http_header($modcontext, 'mod_peerforum', $peerforumrecord, $rsstitle);
}

// Move discussion if requested.
if ($move > 0 && confirm_sesskey()) {
    $peerforumid = $peerforum->get_id();
    $discussionid = $discussion->get_id();
    $return = $discussionviewurl->out(false);

    if (!$peerforumto = $DB->get_record('peerforum', ['id' => $move])) {
        print_error('cannotmovetonotexist', 'peerforum', $return);
    }

    if (!$capabilitymanager->can_move_discussions($USER)) {
        if ($peerforum->get_type() == 'single') {
            print_error('cannotmovefromsinglepeerforum', 'peerforum', $return);
        } else {
            print_error('nopermissions', 'error', $return, get_capability_string('mod/peerforum:movediscussions'));
        }
    }

    if ($peerforumto->type == 'single') {
        print_error('cannotmovetosinglepeerforum', 'peerforum', $return);
    }

    // Get target peerforum cm and check it is visible to current user.
    $modinfo = get_fast_modinfo($course);
    $peerforums = $modinfo->get_instances_of('peerforum');
    if (!array_key_exists($peerforumto->id, $peerforums)) {
        print_error('cannotmovetonotfound', 'peerforum', $return);
    }

    $cmto = $peerforums[$peerforumto->id];
    if (!$cmto->uservisible) {
        print_error('cannotmovenotvisible', 'peerforum', $return);
    }

    $destinationctx = context_module::instance($cmto->id);
    require_capability('mod/peerforum:startdiscussion', $destinationctx);

    if (!peerforum_move_attachments($discussionrecord, $peerforumid, $peerforumto->id)) {
        echo $OUTPUT->notification("Errors occurred while moving attachment directories - check your file permissions");
    }
    // For each subscribed user in this peerforum and discussion, copy over per-discussion subscriptions if required.
    $discussiongroup = $discussion->get_group_id() == -1 ? 0 : $discussion->get_group_id();
    $potentialsubscribers = \mod_peerforum\subscriptions::fetch_subscribed_users(
            $peerforumrecord,
            $discussiongroup,
            $modcontext,
            'u.id',
            true
    );

    // Pre-seed the subscribed_discussion caches.
    // Firstly for the peerforum being moved to.
    \mod_peerforum\subscriptions::fill_subscription_cache($peerforumto->id);
    // And also for the discussion being moved.
    \mod_peerforum\subscriptions::fill_subscription_cache($peerforumid);
    $subscriptionchanges = [];
    $subscriptiontime = time();
    foreach ($potentialsubscribers as $subuser) {
        $userid = $subuser->id;
        $targetsubscription = \mod_peerforum\subscriptions::is_subscribed($userid, $peerforumto, null, $cmto);
        $discussionsubscribed = \mod_peerforum\subscriptions::is_subscribed($userid, $peerforumrecord, $discussionid);
        $peerforumsubscribed = \mod_peerforum\subscriptions::is_subscribed($userid, $peerforumrecord);

        if ($peerforumsubscribed && !$discussionsubscribed && $targetsubscription) {
            // The user has opted out of this discussion and the move would cause them to receive notifications again.
            // Ensure they are unsubscribed from the discussion still.
            $subscriptionchanges[$userid] = \mod_peerforum\subscriptions::PEERFORUM_DISCUSSION_UNSUBSCRIBED;
        } else if (!$peerforumsubscribed && $discussionsubscribed && !$targetsubscription) {
            // The user has opted into this discussion and would otherwise not receive the subscription after the move.
            // Ensure they are subscribed to the discussion still.
            $subscriptionchanges[$userid] = $subscriptiontime;
        }
    }

    $DB->set_field('peerforum_discussions', 'peerforum', $peerforumto->id, ['id' => $discussionid]);
    $DB->set_field('peerforum_read', 'peerforumid', $peerforumto->id, ['discussionid' => $discussionid]);

    // Delete the existing per-discussion subscriptions and replace them with the newly calculated ones.
    $DB->delete_records('peerforum_discussion_subs', ['discussion' => $discussionid]);
    $newdiscussion = clone $discussionrecord;
    $newdiscussion->peerforum = $peerforumto->id;
    foreach ($subscriptionchanges as $userid => $preference) {
        if ($preference != \mod_peerforum\subscriptions::PEERFORUM_DISCUSSION_UNSUBSCRIBED) {
            // Users must have viewdiscussion to a discussion.
            if (has_capability('mod/peerforum:viewdiscussion', $destinationctx, $userid)) {
                \mod_peerforum\subscriptions::subscribe_user_to_discussion($userid, $newdiscussion, $destinationctx);
            }
        } else {
            \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($userid, $newdiscussion, $destinationctx);
        }
    }

    $params = [
            'context' => $destinationctx,
            'objectid' => $discussionid,
            'other' => [
                    'frompeerforumid' => $peerforumid,
                    'topeerforumid' => $peerforumto->id,
            ]
    ];
    $event = \mod_peerforum\event\discussion_moved::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussionrecord);
    $event->add_record_snapshot('peerforum', $peerforumrecord);
    $event->add_record_snapshot('peerforum', $peerforumto);
    $event->trigger();

    // Delete the RSS files for the 2 peerforums to force regeneration of the feeds
    require_once($CFG->dirroot . '/mod/peerforum/rsslib.php');
    peerforum_rss_delete_file($peerforumrecord);
    peerforum_rss_delete_file($peerforumto);

    redirect($return . '&move=-1&sesskey=' . sesskey());
}
// Pin or unpin discussion if requested.
if ($pin !== -1 && confirm_sesskey()) {
    if (!$capabilitymanager->can_pin_discussions($USER)) {
        print_error('nopermissions', 'error', $return, get_capability_string('mod/peerforum:pindiscussions'));
    }

    $params = ['context' => $modcontext, 'objectid' => $discussion->get_id(), 'other' => ['peerforumid' => $peerforum->get_id()]];

    switch ($pin) {
        case PEERFORUM_DISCUSSION_PINNED:
            // Pin the discussion and trigger discussion pinned event.
            peerforum_discussion_pin($modcontext, $peerforumrecord, $discussionrecord);
            break;
        case PEERFORUM_DISCUSSION_UNPINNED:
            // Unpin the discussion and trigger discussion unpinned event.
            peerforum_discussion_unpin($modcontext, $peerforumrecord, $discussionrecord);
            break;
        default:
            echo $OUTPUT->notification("Invalid value when attempting to pin/unpin discussion");
            break;
    }

    redirect($discussionviewurl->out(false));
}

// Trigger discussion viewed event.
peerforum_discussion_view($modcontext, $peerforumrecord, $discussionrecord);

unset($SESSION->fromdiscussion);

$saveddisplaymode = get_user_preferences('peerforum_displaymode', $CFG->peerforum_displaymode);

if ($mode) {
    $displaymode = $mode;
} else {
    $displaymode = $saveddisplaymode;
}

if (get_user_preferences('peerforum_useexperimentalui', false)) {
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

if ($parent) {
    // If flat AND parent, then force nested display this time
    if ($displaymode == PEERFORUM_MODE_FLATOLDEST or $displaymode == PEERFORUM_MODE_FLATNEWEST) {
        $displaymode = PEERFORUM_MODE_NESTED;
    }
} else {
    $parent = $discussion->get_first_post_id();
}

$postvault = $vaultfactory->get_post_vault();
if (!$post = $postvault->get_from_id($parent)) {
    print_error("notexists", 'peerforum', "$CFG->wwwroot/mod/peerforum/view.php?f={$peerforum->get_id()}");
}

if (!$capabilitymanager->can_view_post($USER, $discussion, $post)) {
    print_error('noviewdiscussionspermission', 'peerforum', "$CFG->wwwroot/mod/peerforum/view.php?id={$peerforum->get_id()}");
}

$istracked = peerforum_tp_is_tracked($peerforumrecord, $USER);
if ($mark == 'read' || $mark == 'unread') {
    if ($CFG->peerforum_usermarksread && peerforum_tp_can_track_peerforums($peerforumrecord) && $istracked) {
        if ($mark == 'read') {
            peerforum_tp_add_read_record($USER->id, $postid);
        } else {
            // unread
            peerforum_tp_delete_read_records($USER->id, $postid);
        }
    }
}

$searchform = peerforum_search_form($course);

$peerforumnode = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
if (empty($peerforumnode)) {
    $peerforumnode = $PAGE->navbar;
} else {
    $peerforumnode->make_active();
}
$node = $peerforumnode->add(format_string($discussion->get_name()), $discussionviewurl);
$node->display = false;
if ($node && $post->get_id() != $discussion->get_first_post_id()) {
    $node->add(format_string($post->get_subject()), $PAGE->url);
}

$isnestedv2displaymode = $displaymode == PEERFORUM_MODE_NESTED_V2;
$PAGE->set_title("$course->shortname: " . format_string($discussion->get_name()));
$PAGE->set_heading($course->fullname);
if ($isnestedv2displaymode) {
    $PAGE->add_body_class('nested-v2-display-mode reset-style');
    $settingstrigger = $OUTPUT->render_from_template('mod_peerforum/settings_drawer_trigger', null);
    $PAGE->add_header_action($settingstrigger);
} else {
    $PAGE->set_button(peerforum_search_form($course));
}

echo $OUTPUT->header();
if (!$isnestedv2displaymode) {
    echo $OUTPUT->heading(format_string($peerforum->get_name()), 2);
    echo $OUTPUT->heading(format_string($discussion->get_name()), 3, 'discussionname');
}

$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$discussionrenderer = $rendererfactory->get_discussion_renderer($peerforum, $discussion, $displaymode);
$orderpostsby = $displaymode == PEERFORUM_MODE_FLATNEWEST ? 'created DESC' : 'created ASC';
$replies = $postvault->get_replies_to_post($USER, $post, $capabilitymanager->can_view_any_private_reply($USER), $orderpostsby);

if ($move == -1 and confirm_sesskey()) {
    $peerforumname = format_string($peerforum->get_name(), true);
    echo $OUTPUT->notification(get_string('discussionmoved', 'peerforum', $peerforumname), 'notifysuccess');
}

echo $discussionrenderer->render($USER, $post, $replies);

$peersnotification = false;

echo $OUTPUT->footer();

if ($istracked && !$CFG->peerforum_usermarksread) {
    if ($displaymode == PEERFORUM_MODE_THREADED) {
        peerforum_tp_add_read_record($USER->id, $post->get_id());
    } else {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, array_merge([$post], array_values($replies)));
        peerforum_tp_mark_posts_read($USER, $postids);
    }
}
