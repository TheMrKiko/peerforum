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
 * @package   mod_peerforum
 * @copyright 2014 Andrew Robert Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Deprecated a very long time ago.

/**
 * @deprecated since Moodle 1.1 - please do not use this function any more.
 */
function peerforum_count_unrated_posts() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Since Moodle 1.5.

/**
 * @deprecated since Moodle 1.5 - please do not use this function any more.
 */
function peerforum_tp_count_discussion_read_records() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 1.5 - please do not use this function any more.
 */
function peerforum_get_user_discussions() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Since Moodle 1.6.

/**
 * @deprecated since Moodle 1.6 - please do not use this function any more.
 */
function peerforum_tp_count_peerforum_posts() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 1.6 - please do not use this function any more.
 */
function peerforum_tp_count_peerforum_read_records() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Since Moodle 1.7.

/**
 * @deprecated since Moodle 1.7 - please do not use this function any more.
 */
function peerforum_get_open_modes() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Since Moodle 1.9.

/**
 * @deprecated since Moodle 1.9 MDL-13303 - please do not use this function any more.
 */
function peerforum_get_child_posts() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 1.9 MDL-13303 - please do not use this function any more.
 */
function peerforum_get_discussion_posts() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Since Moodle 2.0.

/**
 * @deprecated since Moodle 2.0 MDL-21657 - please do not use this function any more.
 */
function peerforum_get_ratings() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.0 MDL-14632 - please do not use this function any more.
 */
function peerforum_get_tracking_link() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.0 MDL-14113 - please do not use this function any more.
 */
function peerforum_tp_count_discussion_unread_posts() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.0 MDL-23479 - please do not use this function any more.
 */
function peerforum_convert_to_roles() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.0 MDL-14113 - please do not use this function any more.
 */
function peerforum_tp_get_read_records() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.0 MDL-14113 - please do not use this function any more.
 */
function peerforum_tp_get_discussion_read_records() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Deprecated in 2.3.

/**
 * @deprecated since Moodle 2.3 MDL-33166 - please do not use this function any more.
 */
function peerforum_user_enrolled() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Deprecated in 2.4.

/**
 * @deprecated since Moodle 2.4 use peerforum_user_can_see_post() instead
 */
function peerforum_user_can_view_post() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

// Deprecated in 2.6.

/**
 * PEERFORUM_TRACKING_ON - deprecated alias for PEERFORUM_TRACKING_FORCED.
 *
 * @deprecated since 2.6
 */
define('PEERFORUM_TRACKING_ON', 2);

/**
 * @deprecated since Moodle 2.6
 * @see shorten_text()
 */
function peerforum_shorten_post($message) {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. '
            . 'Please use shorten_text($message, $CFG->peerforum_shortpost) instead.');
}

// Deprecated in 2.8.

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::is_subscribed() instead
 */
function peerforum_is_subscribed() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more.');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::subscribe_user() instead
 */
function peerforum_subscribe() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::subscribe_user() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::unsubscribe_user() instead
 */
function peerforum_unsubscribe() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::unsubscribe_user() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::fetch_subscribed_users() instead
 */
function peerforum_subscribed_users() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::fetch_subscribed_users() instead');
}

/**
 * Determine whether the peerforum is force subscribed.
 *
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::is_forcesubscribed() instead
 */
function peerforum_is_forcesubscribed($peerforum) {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::is_forcesubscribed() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::set_subscription_mode() instead
 */
function peerforum_forcesubscribe($peerforumid, $value = 1) {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::set_subscription_mode() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::get_subscription_mode() instead
 */
function peerforum_get_forcesubscribed($peerforum) {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::set_subscription_mode() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::is_subscribed in combination wtih
 * \mod_peerforum\subscriptions::fill_subscription_cache_for_course instead.
 */
function peerforum_get_subscribed_peerforums() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::is_subscribed(), and '
            . \mod_peerforum\subscriptions::class . '::fill_subscription_cache_for_course() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::get_unsubscribable_peerforums() instead
 */
function peerforum_get_optional_subscribed_peerforums() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::get_unsubscribable_peerforums() instead');
}

/**
 * @deprecated since Moodle 2.8 use \mod_peerforum\subscriptions::get_potential_subscribers() instead
 */
function peerforum_get_potential_subscribers() {
    throw new coding_exception(__FUNCTION__ . '() can not be used any more. Please use '
            . \mod_peerforum\subscriptions::class . '::get_potential_subscribers() instead');
}

/**
 * Builds and returns the body of the email notification in plain text.
 *
 * @param object $course
 * @param object $cm
 * @param object $peerforum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @param boolean $bare
 * @param string $replyaddress The inbound address that a user can reply to the generated e-mail with. [Since 2.8].
 * @return string The email body in plain text format.
 * @uses CONTEXT_MODULE
 * @deprecated since Moodle 3.0 use \mod_peerforum\output\peerforum_post_email instead
 */
function peerforum_make_mail_text($course, $cm, $peerforum, $discussion, $post, $userfrom, $userto, $bare = false,
        $replyaddress = null) {
    global $PAGE;
    $renderable = new \mod_peerforum\output\peerforum_post_email(
            $course,
            $cm,
            $peerforum,
            $discussion,
            $post,
            $userfrom,
            $userto,
            peerforum_user_can_post($peerforum, $discussion, $userto, $cm, $course)
    );

    $modcontext = context_module::instance($cm->id);
    $renderable->viewfullnames = has_capability('moodle/site:viewfullnames', $modcontext, $userto->id);

    if ($bare) {
        $renderer = $PAGE->get_renderer('mod_peerforum', 'emaildigestfull', 'textemail');
    } else {
        $renderer = $PAGE->get_renderer('mod_peerforum', 'email', 'textemail');
    }

    debugging("peerforum_make_mail_text() has been deprecated, please use the \mod_peerforum\output\peerforum_post_email renderable instead.",
            DEBUG_DEVELOPER);

    return $renderer->render($renderable);
}

/**
 * Builds and returns the body of the email notification in html format.
 *
 * @param object $course
 * @param object $cm
 * @param object $peerforum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @param string $replyaddress The inbound address that a user can reply to the generated e-mail with. [Since 2.8].
 * @return string The email text in HTML format
 * @deprecated since Moodle 3.0 use \mod_peerforum\output\peerforum_post_email instead
 */
function peerforum_make_mail_html($course, $cm, $peerforum, $discussion, $post, $userfrom, $userto, $replyaddress = null) {
    return peerforum_make_mail_post($course,
            $cm,
            $peerforum,
            $discussion,
            $post,
            $userfrom,
            $userto,
            peerforum_user_can_post($peerforum, $discussion, $userto, $cm, $course)
    );
}

/**
 * Given the data about a posting, builds up the HTML to display it and
 * returns the HTML in a string.  This is designed for sending via HTML email.
 *
 * @param object $course
 * @param object $cm
 * @param object $peerforum
 * @param object $discussion
 * @param object $post
 * @param object $userfrom
 * @param object $userto
 * @param bool $ownpost
 * @param bool $reply
 * @param bool $link
 * @param bool $rate
 * @param string $footer
 * @return string
 * @deprecated since Moodle 3.0 use \mod_peerforum\output\peerforum_post_email instead
 */
function peerforum_make_mail_post($course, $cm, $peerforum, $discussion, $post, $userfrom, $userto,
        $ownpost = false, $reply = false, $link = false, $rate = false, $footer = "") {
    global $PAGE;
    $renderable = new \mod_peerforum\output\peerforum_post_email(
            $course,
            $cm,
            $peerforum,
            $discussion,
            $post,
            $userfrom,
            $userto,
            $reply);

    $modcontext = context_module::instance($cm->id);
    $renderable->viewfullnames = has_capability('moodle/site:viewfullnames', $modcontext, $userto->id);

    // Assume that this is being used as a standard peerforum email.
    $renderer = $PAGE->get_renderer('mod_peerforum', 'email', 'htmlemail');

    debugging("peerforum_make_mail_post() has been deprecated, please use the \mod_peerforum\output\peerforum_post_email renderable instead.",
            DEBUG_DEVELOPER);

    return $renderer->render($renderable);
}

/**
 * Removes properties from user record that are not necessary for sending post notifications.
 *
 * @param stdClass $user
 * @return void, $user parameter is modified
 * @deprecated since Moodle 3.7
 */
function peerforum_cron_minimise_user_record(stdClass $user) {
    debugging("peerforum_cron_minimise_user_record() has been deprecated and has not been replaced.",
            DEBUG_DEVELOPER);

    // We store large amount of users in one huge array,
    // make sure we do not store info there we do not actually need
    // in mail generation code or messaging.

    unset($user->institution);
    unset($user->department);
    unset($user->address);
    unset($user->city);
    unset($user->url);
    unset($user->currentlogin);
    unset($user->description);
    unset($user->descriptionformat);
}

/**
 * Function to be run periodically according to the scheduled task.
 *
 * Finds all posts that have yet to be mailed out, and mails them out to all subscribers as well as other maintance
 * tasks.
 *
 * @deprecated since Moodle 3.7
 */
function peerforum_cron() {
    debugging("peerforum_cron() has been deprecated and replaced with new tasks. Please uses these instead.",
            DEBUG_DEVELOPER);
}

/**
 * Prints a peerforum discussion
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $peerforum
 * @param stdClass $discussion
 * @param stdClass $post
 * @param int $mode
 * @param mixed $canreply
 * @param bool $canrate
 * @uses CONTEXT_MODULE
 * @uses PEERFORUM_MODE_FLATNEWEST
 * @uses PEERFORUM_MODE_FLATOLDEST
 * @uses PEERFORUM_MODE_THREADED
 * @uses PEERFORUM_MODE_NESTED
 * @deprecated since Moodle 3.7
 */
function peerforum_print_discussion($course, $cm, $peerforum, $discussion, $post, $mode, $canreply = null, $canrate = false) {
    debugging('peerforum_print_discussion() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\discussion instead.', DEBUG_DEVELOPER);

    global $USER, $CFG;

    require_once($CFG->dirroot . '/rating/lib.php');

    $ownpost = (isloggedin() && $USER->id == $post->userid);

    $modcontext = context_module::instance($cm->id);
    if ($canreply === null) {
        $reply = peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext);
    } else {
        $reply = $canreply;
    }

    // $cm holds general cache for peerforum functions
    $cm->cache = new stdClass;
    $cm->cache->groups = groups_get_all_groups($course->id, 0, $cm->groupingid);
    $cm->cache->usersgroups = array();

    $posters = array();

    // preload all posts - TODO: improve...
    if ($mode == PEERFORUM_MODE_FLATNEWEST) {
        $sort = "p.created DESC";
    } else {
        $sort = "p.created ASC";
    }

    $peerforumtracked = peerforum_tp_is_tracked($peerforum);
    $posts = peerforum_get_all_discussion_posts($discussion->id, $sort, $peerforumtracked);
    $post = $posts[$post->id];

    foreach ($posts as $pid => $p) {
        $posters[$p->userid] = $p->userid;
    }

    // preload all groups of ppl that posted in this discussion
    if ($postersgroups = groups_get_all_groups($course->id, $posters, $cm->groupingid, 'gm.id, gm.groupid, gm.userid')) {
        foreach ($postersgroups as $pg) {
            if (!isset($cm->cache->usersgroups[$pg->userid])) {
                $cm->cache->usersgroups[$pg->userid] = array();
            }
            $cm->cache->usersgroups[$pg->userid][$pg->groupid] = $pg->groupid;
        }
        unset($postersgroups);
    }

    //load ratings
    if ($peerforum->assessed != RATING_AGGREGATE_NONE) {
        $ratingoptions = new stdClass;
        $ratingoptions->context = $modcontext;
        $ratingoptions->component = 'mod_peerforum';
        $ratingoptions->ratingarea = 'post';
        $ratingoptions->items = $posts;
        $ratingoptions->aggregate = $peerforum->assessed;//the aggregation method
        $ratingoptions->scaleid = $peerforum->scale;
        $ratingoptions->userid = $USER->id;
        if ($peerforum->type == 'single' or !$discussion->id) {
            $ratingoptions->returnurl = "$CFG->wwwroot/mod/peerforum/view.php?id=$cm->id";
        } else {
            $ratingoptions->returnurl = "$CFG->wwwroot/mod/peerforum/discuss.php?d=$discussion->id";
        }
        $ratingoptions->assesstimestart = $peerforum->assesstimestart;
        $ratingoptions->assesstimefinish = $peerforum->assesstimefinish;

        $rm = new rating_manager();
        $posts = $rm->get_ratings($ratingoptions);
    }

    $post->peerforum = $peerforum->id;   // Add the peerforum id to the post object, later used by peerforum_print_post
    $post->peerforumtype = $peerforum->type;

    $post->subject = format_string($post->subject);

    $postread = !empty($post->postread);

    peerforum_print_post_start($post);
    peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, false,
            '', '', $postread, true, $peerforumtracked);

    switch ($mode) {
        case PEERFORUM_MODE_FLATOLDEST :
        case PEERFORUM_MODE_FLATNEWEST :
        default:
            peerforum_print_posts_flat($course, $cm, $peerforum, $discussion, $post, $mode, $reply, $peerforumtracked, $posts);
            break;

        case PEERFORUM_MODE_THREADED :
            peerforum_print_posts_threaded($course, $cm, $peerforum, $discussion, $post, 0, $reply, $peerforumtracked, $posts);
            break;

        case PEERFORUM_MODE_NESTED :
            peerforum_print_posts_nested($course, $cm, $peerforum, $discussion, $post, $reply, $peerforumtracked, $posts);
            break;
    }
    peerforum_print_post_end($post);
}

/**
 * Return a static array of posts that are open.
 *
 * @return array
 * @deprecated since Moodle 3.7
 */
function peerforum_post_nesting_cache() {
    debugging('peerforum_post_nesting_cache() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    static $nesting = array();
    return $nesting;
}

/**
 * Return true for the first time this post was started
 *
 * @param int $id The id of the post to start
 * @return bool
 * @deprecated since Moodle 3.7
 */
function peerforum_should_start_post_nesting($id) {
    debugging('peerforum_should_start_post_nesting() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    $cache = peerforum_post_nesting_cache();
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = 1;
        return true;
    } else {
        $cache[$id]++;
        return false;
    }
}

/**
 * Return true when all the opens are nested with a close.
 *
 * @param int $id The id of the post to end
 * @return bool
 * @deprecated since Moodle 3.7
 */
function peerforum_should_end_post_nesting($id) {
    debugging('peerforum_should_end_post_nesting() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    $cache = peerforum_post_nesting_cache();
    if (!array_key_exists($id, $cache)) {
        return true;
    } else {
        $cache[$id]--;
        if ($cache[$id] == 0) {
            unset($cache[$id]);
            return true;
        }
    }
    return false;
}

/**
 * Start a peerforum post container
 *
 * @param object $post The post to print.
 * @param bool $return Return the string or print it
 * @return string
 * @deprecated since Moodle 3.7
 */
function peerforum_print_post_start($post, $return = false) {
    debugging('peerforum_print_post_start() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    $output = '';

    if (peerforum_should_start_post_nesting($post->id)) {
        $attributes = [
                'id' => 'p' . $post->id,
                'tabindex' => -1,
                'class' => 'relativelink'
        ];
        $output .= html_writer::start_tag('article', $attributes);
    }
    if ($return) {
        return $output;
    }
    echo $output;
    return;
}

/**
 * End a peerforum post container
 *
 * @param object $post The post to print.
 * @param bool $return Return the string or print it
 * @return string
 * @deprecated since Moodle 3.7
 */
function peerforum_print_post_end($post, $return = false) {
    debugging('peerforum_print_post_end() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    $output = '';

    if (peerforum_should_end_post_nesting($post->id)) {
        $output .= html_writer::end_tag('article');
    }
    if ($return) {
        return $output;
    }
    echo $output;
    return;
}

/**
 * Print a peerforum post
 * This function should always be surrounded with calls to peerforum_print_post_start
 * and peerforum_print_post_end to create the surrounding container for the post.
 * Replies can be nested before peerforum_print_post_end and should reflect the structure of
 * thread.
 *
 * @param object $post The post to print.
 * @param object $discussion
 * @param object $peerforum
 * @param object $cm
 * @param object $course
 * @param boolean $ownpost Whether this post belongs to the current user.
 * @param boolean $reply Whether to print a 'reply' link at the bottom of the message.
 * @param boolean $link Just print a shortened version of the post as a link to the full post.
 * @param string $footer Extra stuff to print after the message.
 * @param string $highlight Space-separated list of terms to highlight.
 * @param int $post_read true, false or -99. If we already know whether this user
 *          has read this post, pass that in, otherwise, pass in -99, and this
 *          function will work it out.
 * @param boolean $dummyifcantsee When peerforum_user_can_see_post says that
 *          the current user can't see this post, if this argument is true
 *          (the default) then print a dummy 'you can't see this post' post.
 *          If false, don't output anything at all.
 * @param bool|null $istracked
 * @return void
 * @global object
 * @global object
 * @uses PEERFORUM_MODE_THREADED
 * @uses PORTFOLIO_FORMAT_PLAINHTML
 * @uses PORTFOLIO_FORMAT_FILE
 * @uses PORTFOLIO_FORMAT_RICHHTML
 * @uses PORTFOLIO_ADD_TEXT_LINK
 * @uses CONTEXT_MODULE
 * @deprecated since Moodle 3.7
 */
function peerforum_print_post($post, $discussion, $peerforum, &$cm, $course, $ownpost = false, $reply = false, $link = false,
        $footer = "", $highlight = "", $postisread = null, $dummyifcantsee = true, $istracked = null, $return = false) {
    debugging('peerforum_print_post() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    global $USER, $CFG, $OUTPUT;

    require_once($CFG->libdir . '/filelib.php');

    // String cache
    static $str;
    // This is an extremely hacky way to ensure we only print the 'unread' anchor
    // the first time we encounter an unread post on a page. Ideally this would
    // be moved into the caller somehow, and be better testable. But at the time
    // of dealing with this bug, this static workaround was the most surgical and
    // it fits together with only printing th unread anchor id once on a given page.
    static $firstunreadanchorprinted = false;

    $modcontext = context_module::instance($cm->id);

    $post->course = $course->id;
    $post->peerforum = $peerforum->id;
    $post->message =
            file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $modcontext->id, 'mod_peerforum', 'post', $post->id);
    if (!empty($CFG->enableplagiarism)) {
        require_once($CFG->libdir . '/plagiarismlib.php');
        $post->message .= plagiarism_get_links(array('userid' => $post->userid,
                'content' => $post->message,
                'cmid' => $cm->id,
                'course' => $post->course,
                'peerforum' => $post->peerforum));
    }

    // caching
    if (!isset($cm->cache)) {
        $cm->cache = new stdClass;
    }

    if (!isset($cm->cache->caps)) {
        $cm->cache->caps = array();
        $cm->cache->caps['mod/peerforum:viewdiscussion'] = has_capability('mod/peerforum:viewdiscussion', $modcontext);
        $cm->cache->caps['moodle/site:viewfullnames'] = has_capability('moodle/site:viewfullnames', $modcontext);
        $cm->cache->caps['mod/peerforum:editanypost'] = has_capability('mod/peerforum:editanypost', $modcontext);
        $cm->cache->caps['mod/peerforum:splitdiscussions'] = has_capability('mod/peerforum:splitdiscussions', $modcontext);
        $cm->cache->caps['mod/peerforum:deleteownpost'] = has_capability('mod/peerforum:deleteownpost', $modcontext);
        $cm->cache->caps['mod/peerforum:deleteanypost'] = has_capability('mod/peerforum:deleteanypost', $modcontext);
        $cm->cache->caps['mod/peerforum:viewanyrating'] = has_capability('mod/peerforum:viewanyrating', $modcontext);
        $cm->cache->caps['mod/peerforum:exportpost'] = has_capability('mod/peerforum:exportpost', $modcontext);
        $cm->cache->caps['mod/peerforum:exportownpost'] = has_capability('mod/peerforum:exportownpost', $modcontext);
    }

    if (!isset($cm->uservisible)) {
        $cm->uservisible = \core_availability\info_module::is_user_visible($cm, 0, false);
    }

    if ($istracked && is_null($postisread)) {
        $postisread = peerforum_tp_is_post_read($USER->id, $post);
    }

    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm, false)) {
        // Do _not_ check the deleted flag - we need to display a different UI.
        $output = '';
        if (!$dummyifcantsee) {
            if ($return) {
                return $output;
            }
            echo $output;
            return;
        }

        $output .= html_writer::start_tag('div', array('class' => 'peerforumpost clearfix',
                'aria-label' => get_string('hiddenpeerforumpost', 'peerforum')));
        $output .= html_writer::start_tag('header', array('class' => 'row header'));
        $output .= html_writer::tag('div', '', array('class' => 'left picture', 'role' => 'presentation')); // Picture.
        if ($post->parent) {
            $output .= html_writer::start_tag('div', array('class' => 'topic'));
        } else {
            $output .= html_writer::start_tag('div', array('class' => 'topic starter'));
        }
        $output .= html_writer::tag('div', get_string('peerforumsubjecthidden', 'peerforum'), array('class' => 'subject',
                'role' => 'header',
                'id' => ('headp' . $post->id))); // Subject.
        $authorclasses = array('class' => 'author');
        $output .= html_writer::tag('address', get_string('peerforumauthorhidden', 'peerforum'), $authorclasses); // Author.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('header'); // Header.
        $output .= html_writer::start_tag('div', array('class' => 'row'));
        $output .= html_writer::tag('div', '&nbsp;', array('class' => 'left side')); // Groups
        $output .= html_writer::tag('div', get_string('peerforumbodyhidden', 'peerforum'), array('class' => 'content')); // Content
        $output .= html_writer::end_tag('div'); // row
        $output .= html_writer::end_tag('div'); // peerforumpost

        if ($return) {
            return $output;
        }
        echo $output;
        return;
    }

    if (!empty($post->deleted)) {
        // Note: Posts marked as deleted are still returned by the above peerforum_user_can_post because it is required for
        // nesting of posts.
        $output = '';
        if (!$dummyifcantsee) {
            if ($return) {
                return $output;
            }
            echo $output;
            return;
        }
        $output .= html_writer::start_tag('div', [
                'class' => 'peerforumpost clearfix',
                'aria-label' => get_string('peerforumbodydeleted', 'peerforum'),
        ]);

        $output .= html_writer::start_tag('header', array('class' => 'row header'));
        $output .= html_writer::tag('div', '', array('class' => 'left picture', 'role' => 'presentation'));

        $classes = ['topic'];
        if (!empty($post->parent)) {
            $classes[] = 'starter';
        }
        $output .= html_writer::start_tag('div', ['class' => implode(' ', $classes)]);

        // Subject.
        $output .= html_writer::tag('div', get_string('peerforumsubjectdeleted', 'peerforum'), [
                'class' => 'subject',
                'role' => 'header',
                'id' => ('headp' . $post->id)
        ]);

        // Author.
        $output .= html_writer::tag('address', '', ['class' => 'author']);

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('header'); // End header.
        $output .= html_writer::start_tag('div', ['class' => 'row']);
        $output .= html_writer::tag('div', '&nbsp;', ['class' => 'left side']); // Groups.
        $output .= html_writer::tag('div', get_string('peerforumbodydeleted', 'peerforum'), ['class' => 'content']); // Content.
        $output .= html_writer::end_tag('div'); // End row.
        $output .= html_writer::end_tag('div'); // End peerforumpost.

        if ($return) {
            return $output;
        }
        echo $output;
        return;
    }

    if (empty($str)) {
        $str = new stdClass;
        $str->edit = get_string('edit', 'peerforum');
        $str->delete = get_string('delete', 'peerforum');
        $str->reply = get_string('reply', 'peerforum');
        $str->parent = get_string('parent', 'peerforum');
        $str->pruneheading = get_string('pruneheading', 'peerforum');
        $str->prune = get_string('prune', 'peerforum');
        $str->displaymode = get_user_preferences('peerforum_displaymode', $CFG->peerforum_displaymode);
        $str->markread = get_string('markread', 'peerforum');
        $str->markunread = get_string('markunread', 'peerforum');
    }

    $discussionlink = new moodle_url('/mod/peerforum/discuss.php', array('d' => $post->discussion));

    // Build an object that represents the posting user
    $postuser = new stdClass;
    $postuserfields = explode(',', user_picture::fields());
    $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
    $postuser->id = $post->userid;
    $postuser->fullname = fullname($postuser, $cm->cache->caps['moodle/site:viewfullnames']);
    $postuser->profilelink = new moodle_url('/user/view.php', array('id' => $post->userid, 'course' => $course->id));

    // Prepare the groups the posting user belongs to
    if (isset($cm->cache->usersgroups)) {
        $groups = array();
        if (isset($cm->cache->usersgroups[$post->userid])) {
            foreach ($cm->cache->usersgroups[$post->userid] as $gid) {
                $groups[$gid] = $cm->cache->groups[$gid];
            }
        }
    } else {
        $groups = groups_get_all_groups($course->id, $post->userid, $cm->groupingid);
    }

    // Prepare the attachements for the post, files then images
    list($attachments, $attachedimages) = peerforum_print_attachments($post, $cm, 'separateimages');

    // Determine if we need to shorten this post
    $shortenpost = ($link && (strlen(strip_tags($post->message)) > $CFG->peerforum_longpost));

    // Prepare an array of commands
    $commands = array();

    // Add a permalink.
    $permalink = new moodle_url($discussionlink);
    $permalink->set_anchor('p' . $post->id);
    $commands[] = array('url' => $permalink, 'text' => get_string('permalink', 'peerforum'), 'attributes' => ['rel' => 'bookmark']);

    // SPECIAL CASE: The front page can display a news item post to non-logged in users.
    // Don't display the mark read / unread controls in this case.
    if ($istracked && $CFG->peerforum_usermarksread && isloggedin()) {
        $url = new moodle_url($discussionlink, array('postid' => $post->id, 'mark' => 'unread'));
        $text = $str->markunread;
        if (!$postisread) {
            $url->param('mark', 'read');
            $text = $str->markread;
        }
        if ($str->displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p' . $post->id);
        }
        $commands[] = array('url' => $url, 'text' => $text, 'attributes' => ['rel' => 'bookmark']);
    }

    // Zoom in to the parent specifically
    if ($post->parent) {
        $url = new moodle_url($discussionlink);
        if ($str->displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p' . $post->parent);
        }
        $commands[] = array('url' => $url, 'text' => $str->parent, 'attributes' => ['rel' => 'bookmark']);
    }

    // Hack for allow to edit news posts those are not displayed yet until they are displayed
    $age = time() - $post->created;
    if (!$post->parent && $peerforum->type == 'news' && $discussion->timestart > time()) {
        $age = 0;
    }

    if ($peerforum->type == 'single' and $discussion->firstpost == $post->id) {
        if (has_capability('moodle/course:manageactivities', $modcontext)) {
            // The first post in single simple is the peerforum description.
            $commands[] = array('url' => new moodle_url('/course/modedit.php',
                    array('update' => $cm->id, 'sesskey' => sesskey(), 'return' => 1)), 'text' => $str->edit);
        }
    } else if (($ownpost && $age < $CFG->maxeditingtime) || $cm->cache->caps['mod/peerforum:editanypost']) {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php', array('edit' => $post->id)), 'text' => $str->edit);
    }

    if ($cm->cache->caps['mod/peerforum:splitdiscussions'] && $post->parent && $peerforum->type != 'single') {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php', array('prune' => $post->id)), 'text' => $str->prune,
                'title' => $str->pruneheading);
    }

    if ($peerforum->type == 'single' and $discussion->firstpost == $post->id) {
        // Do not allow deleting of first post in single simple type.
    } else if (($ownpost && $age < $CFG->maxeditingtime && $cm->cache->caps['mod/peerforum:deleteownpost']) ||
            $cm->cache->caps['mod/peerforum:deleteanypost']) {
        $commands[] =
                array('url' => new moodle_url('/mod/peerforum/post.php', array('delete' => $post->id)), 'text' => $str->delete);
    }

    if ($reply) {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php#mformpeerforum', array('reply' => $post->id)),
                'text' => $str->reply);
    }

    if ($CFG->enableportfolios &&
            ($cm->cache->caps['mod/peerforum:exportpost'] || ($ownpost && $cm->cache->caps['mod/peerforum:exportownpost']))) {
        $p = array('postid' => $post->id);
        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('peerforum_portfolio_caller', array('postid' => $post->id), 'mod_peerforum');
        if (empty($attachments)) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }

        $porfoliohtml = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
        if (!empty($porfoliohtml)) {
            $commands[] = $porfoliohtml;
        }
    }
    // Finished building commands

    // Begin output

    $output = '';

    if ($istracked) {
        if ($postisread) {
            $peerforumpostclass = ' read';
        } else {
            $peerforumpostclass = ' unread';
            // If this is the first unread post printed then give it an anchor and id of unread.
            if (!$firstunreadanchorprinted) {
                $output .= html_writer::tag('a', '', array('id' => 'unread'));
                $firstunreadanchorprinted = true;
            }
        }
    } else {
        // ignore trackign status if not tracked or tracked param missing
        $peerforumpostclass = '';
    }

    $topicclass = '';
    if (empty($post->parent)) {
        $topicclass = ' firstpost starter';
    }

    if (!empty($post->lastpost)) {
        $peerforumpostclass .= ' lastpost';
    }

    // Flag to indicate whether we should hide the author or not.
    $authorhidden = peerforum_is_author_hidden($post, $peerforum);
    $postbyuser = new stdClass;
    $postbyuser->post = $post->subject;
    $postbyuser->user = $postuser->fullname;
    $discussionbyuser = get_string('postbyuser', 'peerforum', $postbyuser);
    // Begin peerforum post.
    $output .= html_writer::start_div('peerforumpost clearfix' . $peerforumpostclass . $topicclass,
            ['aria-label' => $discussionbyuser]);
    // Begin header row.
    $output .= html_writer::start_tag('header', ['class' => 'row header clearfix']);

    // User picture.
    if (!$authorhidden) {
        $picture = $OUTPUT->user_picture($postuser, ['courseid' => $course->id]);
        $output .= html_writer::div($picture, 'left picture', ['role' => 'presentation']);
        $topicclass = 'topic' . $topicclass;
    }

    // Begin topic column.
    $output .= html_writer::start_div($topicclass);
    $postsubject = $post->subject;
    if (empty($post->subjectnoformat)) {
        $postsubject = format_string($postsubject);
    }
    $output .= html_writer::div($postsubject, 'subject', ['role' => 'heading', 'aria-level' => '1', 'id' => ('headp' . $post->id)]);

    if ($authorhidden) {
        $bytext = userdate_htmltime($post->created);
    } else {
        $by = new stdClass();
        $by->date = userdate_htmltime($post->created);
        $by->name = html_writer::link($postuser->profilelink, $postuser->fullname);
        $bytext = get_string('bynameondate', 'peerforum', $by);
    }
    $bytextoptions = [
            'class' => 'author'
    ];
    $output .= html_writer::tag('address', $bytext, $bytextoptions);
    // End topic column.
    $output .= html_writer::end_div();

    // End header row.
    $output .= html_writer::end_tag('header');

    // Row with the peerforum post content.
    $output .= html_writer::start_div('row maincontent clearfix');
    // Show if author is not hidden or we have groups.
    if (!$authorhidden || $groups) {
        $output .= html_writer::start_div('left');
        $groupoutput = '';
        if ($groups) {
            $groupoutput = print_group_picture($groups, $course->id, false, true, true);
        }
        if (empty($groupoutput)) {
            $groupoutput = '&nbsp;';
        }
        $output .= html_writer::div($groupoutput, 'grouppictures');
        $output .= html_writer::end_div(); // Left side.
    }

    $output .= html_writer::start_tag('div', array('class' => 'no-overflow'));
    $output .= html_writer::start_tag('div', array('class' => 'content'));

    $options = new stdClass;
    $options->para = false;
    $options->trusted = $post->messagetrust;
    $options->context = $modcontext;
    if ($shortenpost) {
        // Prepare shortened version by filtering the text then shortening it.
        $postclass = 'shortenedpost';
        $postcontent = format_text($post->message, $post->messageformat, $options);
        $postcontent = shorten_text($postcontent, $CFG->peerforum_shortpost);
        $postcontent .= html_writer::link($discussionlink, get_string('readtherest', 'peerforum'));
        $postcontent .= html_writer::tag('div', '(' . get_string('numwords', 'moodle', count_words($post->message)) . ')',
                array('class' => 'post-word-count'));
    } else {
        // Prepare whole post
        $postclass = 'fullpost';
        $postcontent = format_text($post->message, $post->messageformat, $options, $course->id);
        if (!empty($highlight)) {
            $postcontent = highlight($highlight, $postcontent);
        }
        if (!empty($peerforum->displaywordcount)) {
            $postcontent .= html_writer::tag('div', get_string('numwords', 'moodle', count_words($postcontent)),
                    array('class' => 'post-word-count'));
        }
        $postcontent .= html_writer::tag('div', $attachedimages, array('class' => 'attachedimages'));
    }

    if (\core_tag_tag::is_enabled('mod_peerforum', 'peerforum_posts')) {
        $postcontent .= $OUTPUT->tag_list(core_tag_tag::get_item_tags('mod_peerforum', 'peerforum_posts', $post->id), null,
                'peerforum-tags');
    }

    // Output the post content
    $output .= html_writer::tag('div', $postcontent, array('class' => 'posting ' . $postclass));
    $output .= html_writer::end_tag('div'); // Content
    $output .= html_writer::end_tag('div'); // Content mask
    $output .= html_writer::end_tag('div'); // Row

    $output .= html_writer::start_tag('nav', array('class' => 'row side'));
    $output .= html_writer::tag('div', '&nbsp;', array('class' => 'left'));
    $output .= html_writer::start_tag('div', array('class' => 'options clearfix'));

    if (!empty($attachments)) {
        $output .= html_writer::tag('div', $attachments, array('class' => 'attachments'));
    }

    // Output ratings
    if (!empty($post->rating)) {
        $output .= html_writer::tag('div', $OUTPUT->render($post->rating), array('class' => 'peerforum-post-rating'));
    }

    // Output the commands
    $commandhtml = array();
    foreach ($commands as $command) {
        if (is_array($command)) {
            $attributes = ['class' => 'nav-item nav-link'];
            if (isset($command['attributes'])) {
                $attributes = array_merge($attributes, $command['attributes']);
            }
            $commandhtml[] = html_writer::link($command['url'], $command['text'], $attributes);
        } else {
            $commandhtml[] = $command;
        }
    }
    $output .= html_writer::tag('div', implode(' ', $commandhtml), array('class' => 'commands nav'));

    // Output link to post if required
    if ($link) {
        if (peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext)) {
            $langstring = 'discussthistopic';
        } else {
            $langstring = 'viewthediscussion';
        }
        if ($post->replies == 1) {
            $replystring = get_string('repliesone', 'peerforum', $post->replies);
        } else {
            $replystring = get_string('repliesmany', 'peerforum', $post->replies);
        }
        if (!empty($discussion->unread) && $discussion->unread !== '-') {
            $replystring .= ' <span class="sep">/</span> <span class="unread">';
            $unreadlink = new moodle_url($discussionlink, null, 'unread');
            if ($discussion->unread == 1) {
                $replystring .= html_writer::link($unreadlink, get_string('unreadpostsone', 'peerforum'));
            } else {
                $replystring .= html_writer::link($unreadlink, get_string('unreadpostsnumber', 'peerforum', $discussion->unread));
            }
            $replystring .= '</span>';
        }

        $output .= html_writer::start_tag('div', array('class' => 'link'));
        $output .= html_writer::link($discussionlink, get_string($langstring, 'peerforum'));
        $output .= '&nbsp;(' . $replystring . ')';
        $output .= html_writer::end_tag('div'); // link
    }

    // Output footer if required
    if ($footer) {
        $output .= html_writer::tag('div', $footer, array('class' => 'footer'));
    }

    // Close remaining open divs
    $output .= html_writer::end_tag('div'); // content
    $output .= html_writer::end_tag('nav'); // row
    $output .= html_writer::end_tag('div'); // peerforumpost

    // Mark the peerforum post as read if required
    if ($istracked && !$CFG->peerforum_usermarksread && !$postisread) {
        peerforum_tp_mark_post_read($USER->id, $post);
    }

    if ($return) {
        return $output;
    }
    echo $output;
    return;
}

/**
 * @param object $course
 * @param object $cm
 * @param object $peerforum
 * @param object $discussion
 * @param object $post
 * @param object $mode
 * @param bool $reply
 * @param bool $peerforumtracked
 * @param array $posts
 * @return void
 * @global object
 * @global object
 * @uses PEERFORUM_MODE_FLATNEWEST
 * @deprecated since Moodle 3.7
 */
function peerforum_print_posts_flat($course, &$cm, $peerforum, $discussion, $post, $mode, $reply, $peerforumtracked, $posts) {
    debugging('peerforum_print_posts_flat() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    global $USER, $CFG;

    $link = false;

    foreach ($posts as $post) {
        if (!$post->parent) {
            continue;
        }
        $post->subject = format_string($post->subject);
        $ownpost = ($USER->id == $post->userid);

        $postread = !empty($post->postread);

        peerforum_print_post_start($post);
        peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                '', '', $postread, true, $peerforumtracked);
        peerforum_print_post_end($post);
    }
}

/**
 * @return void
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @todo Document this function
 *
 * @deprecated since Moodle 3.7
 */
function peerforum_print_posts_threaded($course, &$cm, $peerforum, $discussion, $parent, $depth, $reply, $peerforumtracked,
        $posts) {
    debugging('peerforum_print_posts_threaded() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    global $USER, $CFG;

    $link = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        $modcontext = context_module::instance($cm->id);
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $modcontext);

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if ($depth > 0) {
                $ownpost = ($USER->id == $post->userid);
                $post->subject = format_string($post->subject);

                $postread = !empty($post->postread);

                peerforum_print_post_start($post);
                peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                        '', '', $postread, true, $peerforumtracked);
                peerforum_print_post_end($post);
            } else {
                if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm, true)) {
                    if (peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm, false)) {
                        // This post has been deleted but still exists and may have children.
                        $subject = get_string('privacy:request:delete:post:subject', 'mod_peerforum');
                        $byline = '';
                    } else {
                        // The user can't see this post at all.
                        echo "</div>\n";
                        continue;
                    }
                } else {
                    $by = new stdClass();
                    $by->name = fullname($post, $canviewfullnames);
                    $by->date = userdate_htmltime($post->modified);
                    $byline = ' ' . get_string("bynameondate", "peerforum", $by);
                    $subject = format_string($post->subject, true);
                }

                if ($peerforumtracked) {
                    if (!empty($post->postread)) {
                        $style = '<span class="peerforumthread read">';
                    } else {
                        $style = '<span class="peerforumthread unread">';
                    }
                } else {
                    $style = '<span class="peerforumthread">';
                }

                echo $style;
                echo "<a name='{$post->id}'></a>";
                echo html_writer::link(new moodle_url('/mod/peerforum/discuss.php', [
                        'd' => $post->discussion,
                        'parent' => $post->id,
                ]), $subject);
                echo $byline;
                echo "</span>";
            }

            peerforum_print_posts_threaded($course, $cm, $peerforum, $discussion, $post, $depth - 1, $reply, $peerforumtracked,
                    $posts);
            echo "</div>\n";
        }
    }
}

/**
 * @return void
 * @global object
 * @global object
 * @todo Document this function
 * @deprecated since Moodle 3.7
 */
function peerforum_print_posts_nested($course, &$cm, $peerforum, $discussion, $parent, $reply, $peerforumtracked, $posts) {
    debugging('peerforum_print_posts_nested() has been deprecated, ' .
            'please use \mod_peerforum\local\renderers\posts instead.', DEBUG_DEVELOPER);
    global $USER, $CFG;

    $link = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if (!isloggedin()) {
                $ownpost = false;
            } else {
                $ownpost = ($USER->id == $post->userid);
            }

            $post->subject = format_string($post->subject);
            $postread = !empty($post->postread);

            peerforum_print_post_start($post);
            peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                    '', '', $postread, true, $peerforumtracked);
            peerforum_print_posts_nested($course, $cm, $peerforum, $discussion, $post, $reply, $peerforumtracked, $posts);
            peerforum_print_post_end($post);
            echo "</div>\n";
        }
    }
}

/**
 * Prints the discussion view screen for a peerforum.
 *
 * @param object $course The current course object.
 * @param object $peerforum PeerForum to be printed.
 * @param int $maxdiscussions
 * @param string $displayformat The display format to use (optional).
 * @param string $sort Sort arguments for database query (optional).
 * @param int $currentgroup
 * @param int $groupmode Group mode of the peerforum (optional).
 * @param int $page Page mode, page to display (optional).
 * @param int $perpage The maximum number of discussions per page(optional)
 * @param stdClass $cm
 * @deprecated since Moodle 3.7
 */
function peerforum_print_latest_discussions($course, $peerforum, $maxdiscussions = -1, $displayformat = 'plain', $sort = '',
        $currentgroup = -1, $groupmode = -1, $page = -1, $perpage = 100, $cm = null) {
    debugging('peerforum_print_latest_discussions has been deprecated.', DEBUG_DEVELOPER);
    global $CFG, $USER, $OUTPUT;

    require_once($CFG->dirroot . '/course/lib.php');

    if (!$cm) {
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
    }
    $context = context_module::instance($cm->id);

    if (empty($sort)) {
        $sort = peerforum_get_default_sort_order();
    }

    $olddiscussionlink = false;

    // Sort out some defaults.
    if ($perpage <= 0) {
        $perpage = 0;
        $page = -1;
    }

    if ($maxdiscussions == 0) {
        // All discussions - backwards compatibility.
        $page = -1;
        $perpage = 0;
        if ($displayformat == 'plain') {
            $displayformat = 'header';  // Abbreviate display by default.
        }

    } else if ($maxdiscussions > 0) {
        $page = -1;
        $perpage = $maxdiscussions;
    }

    $fullpost = false;
    if ($displayformat == 'plain') {
        $fullpost = true;
    }

    // Decide if current user is allowed to see ALL the current discussions or not.
    // First check the group stuff.
    if ($currentgroup == -1 or $groupmode == -1) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        $currentgroup = groups_get_activity_group($cm);
    }

    // Cache.
    $groups = array();

    // If the user can post discussions, then this is a good place to put the
    // button for it. We do not show the button if we are showing site news
    // and the current user is a guest.

    $canstart = peerforum_user_can_post_discussion($peerforum, $currentgroup, $groupmode, $cm, $context);
    if (!$canstart and $peerforum->type !== 'news') {
        if (isguestuser() or !isloggedin()) {
            $canstart = true;
        }
        if (!is_enrolled($context) and !is_viewing($context)) {
            // Allow guests and not-logged-in to see the button - they are prompted to log in after clicking the link
            // normal users with temporary guest access see this button too, they are asked to enrol instead
            // do not show the button to users with suspended enrolments here.
            $canstart = enrol_selfenrol_available($course->id);
        }
    }

    if ($canstart) {
        switch ($peerforum->type) {
            case 'news':
            case 'blog':
                $buttonadd = get_string('addanewtopic', 'peerforum');
                break;
            case 'qanda':
                $buttonadd = get_string('addanewquestion', 'peerforum');
                break;
            default:
                $buttonadd = get_string('addanewdiscussion', 'peerforum');
                break;
        }
        $button = new single_button(new moodle_url('/mod/peerforum/post.php', ['peerforum' => $peerforum->id]), $buttonadd, 'get');
        $button->class = 'singlebutton peerforumaddnew';
        $button->formid = 'newdiscussionform';
        echo $OUTPUT->render($button);

    } else if (isguestuser() or !isloggedin() or $peerforum->type == 'news' or
            $peerforum->type == 'qanda' and !has_capability('mod/peerforum:addquestion', $context) or
            $peerforum->type != 'qanda' and !has_capability('mod/peerforum:startdiscussion', $context)) {
        // No button and no info.
        $ignore = true;
    } else if ($groupmode and !has_capability('moodle/site:accessallgroups', $context)) {
        // Inform users why they can not post new discussion.
        if (!$currentgroup) {
            if (!has_capability('mod/peerforum:canposttomygroups', $context)) {
                echo $OUTPUT->notification(get_string('cannotadddiscussiongroup', 'peerforum'));
            } else {
                echo $OUTPUT->notification(get_string('cannotadddiscussionall', 'peerforum'));
            }
        } else if (!groups_is_member($currentgroup)) {
            echo $OUTPUT->notification(get_string('cannotadddiscussion', 'peerforum'));
        }
    }

    // Get all the recent discussions we're allowed to see.

    $getuserlastmodified = ($displayformat == 'header');

    $discussions = peerforum_get_discussions($cm, $sort, $fullpost, null, $maxdiscussions, $getuserlastmodified, $page, $perpage);
    if (!$discussions) {
        echo '<div class="peerforumnodiscuss">';
        if ($peerforum->type == 'news') {
            echo '(' . get_string('nonews', 'peerforum') . ')';
        } else if ($peerforum->type == 'qanda') {
            echo '(' . get_string('noquestions', 'peerforum') . ')';
        } else {
            echo '(' . get_string('nodiscussions', 'peerforum') . ')';
        }
        echo "</div>\n";
        return;
    }

    $canseeprivatereplies = has_capability('mod/peerforum:readprivatereplies', $context);
    // If we want paging.
    if ($page != -1) {
        // Get the number of discussions found.
        $numdiscussions = peerforum_get_discussions_count($cm);

        // Show the paging bar.
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$peerforum->id");
        if ($numdiscussions > 1000) {
            // Saves some memory on sites with very large peerforums.
            $replies = peerforum_count_discussion_replies($peerforum->id, $sort, $maxdiscussions, $page, $perpage,
                    $canseeprivatereplies);
        } else {
            $replies = peerforum_count_discussion_replies($peerforum->id, "", -1, -1, 0, $canseeprivatereplies);
        }

    } else {
        $replies = peerforum_count_discussion_replies($peerforum->id, "", -1, -1, 0, $canseeprivatereplies);

        if ($maxdiscussions > 0 and $maxdiscussions <= count($discussions)) {
            $olddiscussionlink = true;
        }
    }

    $canviewparticipants = course_can_view_participants($context);
    $canviewhiddentimedposts = has_capability('mod/peerforum:viewhiddentimedposts', $context);

    $strdatestring = get_string('strftimerecentfull');

    // Check if the peerforum is tracked.
    if ($cantrack = peerforum_tp_can_track_peerforums($peerforum)) {
        $peerforumtracked = peerforum_tp_is_tracked($peerforum);
    } else {
        $peerforumtracked = false;
    }

    if ($peerforumtracked) {
        $unreads = peerforum_get_discussions_unread($cm);
    } else {
        $unreads = array();
    }

    if ($displayformat == 'header') {
        echo '<table cellspacing="0" class="peerforumheaderlist">';
        echo '<thead class="text-left">';
        echo '<tr>';
        echo '<th class="header topic" scope="col">' . get_string('discussion', 'peerforum') . '</th>';
        echo '<th class="header author" scope="col">' . get_string('startedby', 'peerforum') . '</th>';
        if ($groupmode > 0) {
            echo '<th class="header group" scope="col">' . get_string('group') . '</th>';
        }
        if (has_capability('mod/peerforum:viewdiscussion', $context)) {
            echo '<th class="header replies" scope="col">' . get_string('replies', 'peerforum') . '</th>';
            // If the peerforum can be tracked, display the unread column.
            if ($cantrack) {
                echo '<th class="header replies" scope="col">' . get_string('unread', 'peerforum');
                if ($peerforumtracked) {
                    echo '<a title="' . get_string('markallread', 'peerforum') .
                            '" href="' . $CFG->wwwroot . '/mod/peerforum/markposts.php?f=' .
                            $peerforum->id . '&amp;mark=read&amp;return=/mod/peerforum/view.php&amp;sesskey=' . sesskey() . '">' .
                            $OUTPUT->pix_icon('t/markasread', get_string('markallread', 'peerforum')) . '</a>';
                }
                echo '</th>';
            }
        }
        echo '<th class="header lastpost" scope="col">' . get_string('lastpost', 'peerforum') . '</th>';
        if ((!is_guest($context, $USER) && isloggedin()) && has_capability('mod/peerforum:viewdiscussion', $context)) {
            if (\mod_peerforum\subscriptions::is_subscribable($peerforum)) {
                echo '<th class="header discussionsubscription" scope="col">';
                echo peerforum_get_discussion_subscription_icon_preloaders();
                echo '</th>';
            }
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
    }

    foreach ($discussions as $discussion) {
        if ($peerforum->type == 'qanda' && !has_capability('mod/peerforum:viewqandawithoutposting', $context) &&
                !peerforum_user_has_posted($peerforum->id, $discussion->discussion, $USER->id)) {
            $canviewparticipants = false;
        }

        if (!empty($replies[$discussion->discussion])) {
            $discussion->replies = $replies[$discussion->discussion]->replies;
            $discussion->lastpostid = $replies[$discussion->discussion]->lastpostid;
        } else {
            $discussion->replies = 0;
        }

        // SPECIAL CASE: The front page can display a news item post to non-logged in users.
        // All posts are read in this case.
        if (!$peerforumtracked) {
            $discussion->unread = '-';
        } else if (empty($USER)) {
            $discussion->unread = 0;
        } else {
            if (empty($unreads[$discussion->discussion])) {
                $discussion->unread = 0;
            } else {
                $discussion->unread = $unreads[$discussion->discussion];
            }
        }

        if (isloggedin()) {
            $ownpost = ($discussion->userid == $USER->id);
        } else {
            $ownpost = false;
        }
        // Use discussion name instead of subject of first post.
        $discussion->subject = $discussion->name;

        switch ($displayformat) {
            case 'header':
                if ($groupmode > 0) {
                    if (isset($groups[$discussion->groupid])) {
                        $group = $groups[$discussion->groupid];
                    } else {
                        $group = $groups[$discussion->groupid] = groups_get_group($discussion->groupid);
                    }
                } else {
                    $group = -1;
                }
                peerforum_print_discussion_header($discussion, $peerforum, $group, $strdatestring, $cantrack, $peerforumtracked,
                        $canviewparticipants, $context, $canviewhiddentimedposts);
                break;
            default:
                $link = false;

                if ($discussion->replies) {
                    $link = true;
                } else {
                    $modcontext = context_module::instance($cm->id);
                    $link = peerforum_user_can_see_discussion($peerforum, $discussion, $modcontext, $USER);
                }

                $discussion->peerforum = $peerforum->id;

                peerforum_print_post_start($discussion);
                peerforum_print_post($discussion, $discussion, $peerforum, $cm, $course, $ownpost, 0, $link, false,
                        '', null, true, $peerforumtracked);
                peerforum_print_post_end($discussion);
                break;
        }
    }

    if ($displayformat == "header") {
        echo '</tbody>';
        echo '</table>';
    }

    if ($olddiscussionlink) {
        if ($peerforum->type == 'news') {
            $strolder = get_string('oldertopics', 'peerforum');
        } else {
            $strolder = get_string('olderdiscussions', 'peerforum');
        }
        echo '<div class="peerforumolddiscuss">';
        echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/view.php?f=' . $peerforum->id . '&amp;showall=1">';
        echo $strolder . '</a> ...</div>';
    }

    if ($page != -1) {
        // Show the paging bar.
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$peerforum->id");
    }
}

/**
 * Count the number of replies to the specified post.
 *
 * @param object $post
 * @param bool $children
 * @return int
 * @deprecated since Moodle 3.7
 * @todo MDL-65252 This will be removed in Moodle 3.11
 */
function peerforum_count_replies($post, $children = true) {
    global $USER;
    debugging('peerforum_count_replies has been deprecated. Please use the Post vault instead.', DEBUG_DEVELOPER);

    if (!$children) {
        return $DB->count_records('peerforum_posts', array('parent' => $post->id));
    }

    $entityfactory = mod_peerforum\local\container::get_entity_factory();
    $postentity = $entityfactory->get_post_from_stdclass($post);

    $vaultfactory = mod_peerforum\local\container::get_vault_factory();
    $postvault = $vaultfactory->get_post_vault();

    return $postvault->get_reply_count_for_post_id_in_discussion_id(
            $USER,
            $postentity->get_id(),
            $postentity->get_discussion_id(),
            true
    );
}

/**
 * @deprecated since Moodle 3.8
 */
function peerforum_scale_used() {
    throw new coding_exception('peerforum_scale_used() can not be used anymore. Plugins can implement ' .
            '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Return grade for given user or all users.
 *
 * @param object $peerforum
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 * @deprecated since Moodle 3.8
 */
function peerforum_get_user_grades($peerforum, $userid = 0) {
    global $CFG;

    require_once($CFG->dirroot . '/rating/lib.php');

    $ratingoptions = (object) [
            'component' => 'mod_peerforum',
            'ratingarea' => 'post',
            'contextid' => $contextid,

            'modulename' => 'peerforum',
            'moduleid  ' => $peerforum->id,
            'userid' => $userid,
            'aggregationmethod' => $peerforum->assessed,
            'scaleid' => $peerforum->scale,
            'itemtable' => 'peerforum_posts',
            'itemtableusercolumn' => 'userid',
    ];

    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}
