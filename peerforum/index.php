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
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->libdir . '/rsslib.php');

$id = optional_param('id', 0, PARAM_INT);                   // Course id
$subscribe = optional_param('subscribe', null, PARAM_INT);  // Subscribe/Unsubscribe all peerforums

$url = new moodle_url('/mod/peerforum/index.php', array('id' => $id));
if ($subscribe !== null) {
    require_sesskey();
    $url->param('subscribe', $subscribe);
}
$PAGE->set_url($url);

if ($id) {
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
} else {
    $course = get_site();
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');
$coursecontext = context_course::instance($course->id);

unset($SESSION->fromdiscussion);

$params = array(
        'context' => context_course::instance($course->id)
);
$event = \mod_peerforum\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strpeerforums = get_string('peerforums', 'peerforum');
$strpeerforum = get_string('peerforum', 'peerforum');
$strdescription = get_string('description');
$strdiscussions = get_string('discussions', 'peerforum');
$strsubscribed = get_string('subscribed', 'peerforum');
$strunreadposts = get_string('unreadposts', 'peerforum');
$strtracking = get_string('tracking', 'peerforum');
$strmarkallread = get_string('markallread', 'peerforum');
$strtrackforum = get_string('trackforum', 'peerforum');
$strnotrackforum = get_string('notrackforum', 'peerforum');
$strsubscribe = get_string('subscribe', 'peerforum');
$strunsubscribe = get_string('unsubscribe', 'peerforum');
$stryes = get_string('yes');
$strno = get_string('no');
$strrss = get_string('rss');
$stremaildigest = get_string('emaildigest');

$searchform = peerforum_search_form($course);

// Start of the table for General PeerForums.
$generaltable = new html_table();
$generaltable->head = array($strpeerforum, $strdescription, $strdiscussions);
$generaltable->align = array('left', 'left', 'center');

if ($usetracking = peerforum_tp_can_track_peerforums()) {
    $untracked = peerforum_tp_get_untracked_peerforums($USER->id, $course->id);

    $generaltable->head[] = $strunreadposts;
    $generaltable->align[] = 'center';

    $generaltable->head[] = $strtracking;
    $generaltable->align[] = 'center';
}

// Fill the subscription cache for this course and user combination.
\mod_peerforum\subscriptions::fill_subscription_cache_for_course($course->id, $USER->id);

$usesections = course_format_uses_sections($course->format);

$table = new html_table();

// Parse and organise all the peerforums.  Most peerforums are course modules but
// some special ones are not.  These get placed in the general peerforums
// category with the peerforums in section 0.

$peerforums = $DB->get_records_sql("
    SELECT f.*,
           d.maildigest
      FROM {peerforum} f
 LEFT JOIN {peerforum_digests} d ON d.peerforum = f.id AND d.userid = ?
     WHERE f.course = ?
    ", array($USER->id, $course->id));

$generalpeerforums = array();
$learningpeerforums = array();
$modinfo = get_fast_modinfo($course);
$showsubscriptioncolumns = false;

foreach ($modinfo->get_instances_of('peerforum') as $peerforumid => $cm) {
    if (!$cm->uservisible or !isset($peerforums[$peerforumid])) {
        continue;
    }

    $peerforum = $peerforums[$peerforumid];

    if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
        // Shouldn't happen.
        continue;
    }

    if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
        // User can't view this one - skip it.
        continue;
    }

    // Determine whether subscription options should be displayed.
    $peerforum->cansubscribe = mod_peerforum\subscriptions::is_subscribable($peerforum);
    $peerforum->cansubscribe = $peerforum->cansubscribe || has_capability('mod/peerforum:managesubscriptions', $context);
    $peerforum->issubscribed = mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforum, null, $cm);

    $showsubscriptioncolumns = $showsubscriptioncolumns || $peerforum->issubscribed || $peerforum->cansubscribe;

    // Fill two type array - order in modinfo is the same as in course.
    if ($peerforum->type == 'news' or $peerforum->type == 'social') {
        $generalpeerforums[$peerforum->id] = $peerforum;

    } else if ($course->id == SITEID or empty($cm->sectionnum)) {
        $generalpeerforums[$peerforum->id] = $peerforum;

    } else {
        $learningpeerforums[$peerforum->id] = $peerforum;
    }
}

if ($showsubscriptioncolumns) {
    // The user can subscribe to at least one peerforum.
    $generaltable->head[] = $strsubscribed;
    $generaltable->align[] = 'center';

    $generaltable->head[] = $stremaildigest . ' ' . $OUTPUT->help_icon('emaildigesttype', 'mod_peerforum');
    $generaltable->align[] = 'center';

}

if ($show_rss = (($showsubscriptioncolumns || $course->id == SITEID) &&
        isset($CFG->enablerssfeeds) && isset($CFG->peerforum_enablerssfeeds) &&
        $CFG->enablerssfeeds && $CFG->peerforum_enablerssfeeds)) {
    $generaltable->head[] = $strrss;
    $generaltable->align[] = 'center';
}

// Do course wide subscribe/unsubscribe if requested
if (!is_null($subscribe)) {
    if (isguestuser() or !$showsubscriptioncolumns) {
        // There should not be any links leading to this place, just redirect.
        redirect(
                new moodle_url('/mod/peerforum/index.php', array('id' => $id)),
                get_string('subscribeenrolledonly', 'peerforum'),
                null,
                \core\output\notification::NOTIFY_ERROR
        );
    }
    // Can proceed now, the user is not guest and is enrolled
    foreach ($modinfo->get_instances_of('peerforum') as $peerforumid => $cm) {
        $peerforum = $peerforums[$peerforumid];
        $modcontext = context_module::instance($cm->id);
        $cansub = false;

        if (has_capability('mod/peerforum:viewdiscussion', $modcontext)) {
            $cansub = true;
        }
        if ($cansub && $cm->visible == 0 &&
                !has_capability('mod/peerforum:managesubscriptions', $modcontext)) {
            $cansub = false;
        }
        if (!\mod_peerforum\subscriptions::is_forcesubscribed($peerforum)) {
            $subscribed = \mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforum, null, $cm);
            $canmanageactivities = has_capability('moodle/course:manageactivities', $coursecontext, $USER->id);
            if (($canmanageactivities || \mod_peerforum\subscriptions::is_subscribable($peerforum)) && $subscribe && !$subscribed &&
                    $cansub) {
                \mod_peerforum\subscriptions::subscribe_user($USER->id, $peerforum, $modcontext, true);
            } else if (!$subscribe && $subscribed) {
                \mod_peerforum\subscriptions::unsubscribe_user($USER->id, $peerforum, $modcontext, true);
            }
        }
    }
    $returnto = peerforum_go_back_to(new moodle_url('/mod/peerforum/index.php', array('id' => $course->id)));
    $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));
    if ($subscribe) {
        redirect(
                $returnto,
                get_string('nowallsubscribed', 'peerforum', $shortname),
                null,
                \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
                $returnto,
                get_string('nowallunsubscribed', 'peerforum', $shortname),
                null,
                \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

if ($generalpeerforums) {
    // Process general peerforums.
    foreach ($generalpeerforums as $peerforum) {
        $cm = $modinfo->instances['peerforum'][$peerforum->id];
        $context = context_module::instance($cm->id);

        $count = peerforum_count_discussions($peerforum, $cm, $course);

        if ($usetracking) {
            if ($peerforum->trackingtype == PEERFORUM_TRACKING_OFF) {
                $unreadlink = '-';
                $trackedlink = '-';

            } else {
                if (isset($untracked[$peerforum->id])) {
                    $unreadlink = '-';
                } else if ($unread = peerforum_tp_count_peerforum_unread_posts($cm, $course)) {
                    $unreadlink = '<span class="unread"><a href="view.php?f=' . $peerforum->id . '#unread">' . $unread . '</a>';
                    $icon = $OUTPUT->pix_icon('t/markasread', $strmarkallread);
                    $unreadlink .= '<a title="' . $strmarkallread . '" href="markposts.php?f=' .
                            $peerforum->id . '&amp;mark=read&amp;sesskey=' . sesskey() . '">' . $icon . '</a></span>';
                    // $unreadlink .= '<a title="' . $strmarkallread . '" href="markposts.php?f=' .
                    //         $peerforum->id . '&amp;mark=read">' . $icon . '</a></span>';
                } else {
                    $unreadlink = '<span class="read">0</span>';
                }

                if (($peerforum->trackingtype == PEERFORUM_TRACKING_FORCED) && ($CFG->peerforum_allowforcedreadtracking)) {
                    $trackedlink = $stryes;
                    // NOTE Peer is not to be replaced here
                } else if ($peerforum->trackingtype === PEERFORUM_TRACKING_OFF || ($USER->trackforums == 0)) {
                    $trackedlink = '-';
                } else {
                    $aurl = new moodle_url('/mod/peerforum/settracking.php', array(
                            'id' => $peerforum->id,
                            'sesskey' => sesskey(),
                    ));
                    if (!isset($untracked[$peerforum->id])) {
                        $trackedlink = $OUTPUT->single_button($aurl, $stryes, 'post', array('title' => $strnotrackforum));
                    } else {
                        $trackedlink = $OUTPUT->single_button($aurl, $strno, 'post', array('title' => $strtrackforum));
                    }
                }
            }
        }

        $peerforum->intro = shorten_text(format_module_intro('peerforum', $peerforum, $cm->id), $CFG->peerforum_shortpost);
        $peerforumname = format_string($peerforum->name, true);

        if ($cm->visible) {
            $style = '';
        } else {
            $style = 'class="dimmed"';
        }
        $peerforumlink = "<a href=\"view.php?f=$peerforum->id\" $style>" . format_string($peerforum->name, true) . "</a>";
        $discussionlink = "<a href=\"view.php?f=$peerforum->id\" $style>" . $count . "</a>";

        $row = array($peerforumlink, $peerforum->intro, $discussionlink);
        if ($usetracking) {
            $row[] = $unreadlink;
            $row[] = $trackedlink;    // Tracking.
        }

        if ($showsubscriptioncolumns) {
            $row[] = peerforum_get_subscribe_link($peerforum, $context, array('subscribed' => $stryes,
                    'unsubscribed' => $strno, 'forcesubscribed' => $stryes,
                    'cantsubscribe' => '-'), false, false, true);
            $row[] = peerforum_index_get_peerforum_subscription_selector($peerforum);
        }

        // If this peerforum has RSS activated, calculate it.
        if ($show_rss) {
            if ($peerforum->rsstype and $peerforum->rssarticles) {
                //Calculate the tooltip text
                if ($peerforum->rsstype == 1) {
                    $tooltiptext = get_string('rsssubscriberssdiscussions', 'peerforum');
                } else {
                    $tooltiptext = get_string('rsssubscriberssposts', 'peerforum');
                }

                if (!isloggedin() && $course->id == SITEID) {
                    $userid = guest_user()->id;
                } else {
                    $userid = $USER->id;
                }
                //Get html code for RSS link
                $row[] = rss_get_link($context->id, $userid, 'mod_peerforum', $peerforum->id, $tooltiptext);
            } else {
                $row[] = '&nbsp;';
            }
        }

        $generaltable->data[] = $row;
    }
}

// Start of the table for Learning PeerForums
$learningtable = new html_table();
$learningtable->head = array($strpeerforum, $strdescription, $strdiscussions);
$learningtable->align = array('left', 'left', 'center');

if ($usetracking) {
    $learningtable->head[] = $strunreadposts;
    $learningtable->align[] = 'center';

    $learningtable->head[] = $strtracking;
    $learningtable->align[] = 'center';
}

if ($showsubscriptioncolumns) {
    $learningtable->head[] = $strsubscribed;
    $learningtable->align[] = 'center';

    $learningtable->head[] = $stremaildigest . ' ' . $OUTPUT->help_icon('emaildigesttype', 'mod_peerforum');
    $learningtable->align[] = 'center';
}

if ($show_rss = (($showsubscriptioncolumns || $course->id == SITEID) &&
        isset($CFG->enablerssfeeds) && isset($CFG->peerforum_enablerssfeeds) &&
        $CFG->enablerssfeeds && $CFG->peerforum_enablerssfeeds)) {
    $learningtable->head[] = $strrss;
    $learningtable->align[] = 'center';
}

// Now let's process the learning peerforums.
if ($course->id != SITEID) {    // Only real courses have learning peerforums
    // 'format_.'$course->format only applicable when not SITEID (format_site is not a format)
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    // Add extra field for section number, at the front
    array_unshift($learningtable->head, $strsectionname);
    array_unshift($learningtable->align, 'center');

    if ($learningpeerforums) {
        $currentsection = '';
        foreach ($learningpeerforums as $peerforum) {
            $cm = $modinfo->instances['peerforum'][$peerforum->id];
            $context = context_module::instance($cm->id);

            $count = peerforum_count_discussions($peerforum, $cm, $course);

            if ($usetracking) {
                if ($peerforum->trackingtype == PEERFORUM_TRACKING_OFF) {
                    $unreadlink = '-';
                    $trackedlink = '-';

                } else {
                    if (isset($untracked[$peerforum->id])) {
                        $unreadlink = '-';
                    } else if ($unread = peerforum_tp_count_peerforum_unread_posts($cm, $course)) {
                        $unreadlink = '<span class="unread"><a href="view.php?f=' . $peerforum->id . '#unread">' . $unread . '</a>';
                        $icon = $OUTPUT->pix_icon('t/markasread', $strmarkallread);
                        $unreadlink .= '<a title="' . $strmarkallread . '" href="markposts.php?f=' .
                                $peerforum->id . '&amp;mark=read&sesskey=' . sesskey() . '">' . $icon . '</a></span>';
                        // $unreadlink .= '<a title="' . $strmarkallread . '" href="markposts.php?f=' .
                        //        $peerforum->id . '&amp;mark=read">' . $icon . '</a></span>';
                    } else {
                        $unreadlink = '<span class="read">0</span>';
                    }

                    if (($peerforum->trackingtype == PEERFORUM_TRACKING_FORCED) && ($CFG->peerforum_allowforcedreadtracking)) {
                        $trackedlink = $stryes;
                        // NOTE Peer is not to be replaced here
                    } else if ($peerforum->trackingtype === PEERFORUM_TRACKING_OFF || ($USER->trackforums == 0)) {
                        $trackedlink = '-';
                    } else {
                        $aurl = new moodle_url('/mod/peerforum/settracking.php', array('id' => $peerforum->id));
                        if (!isset($untracked[$peerforum->id])) {
                            $trackedlink = $OUTPUT->single_button($aurl, $stryes, 'post', array('title' => $strnotrackforum));
                        } else {
                            $trackedlink = $OUTPUT->single_button($aurl, $strno, 'post', array('title' => $strtrackforum));
                        }
                    }
                }
            }

            $peerforum->intro = shorten_text(format_module_intro('peerforum', $peerforum, $cm->id), $CFG->peerforum_shortpost);

            if ($cm->sectionnum != $currentsection) {
                $printsection = get_section_name($course, $cm->sectionnum);
                if ($currentsection) {
                    $learningtable->data[] = 'hr';
                }
                $currentsection = $cm->sectionnum;
            } else {
                $printsection = '';
            }

            $peerforumname = format_string($peerforum->name, true);

            if ($cm->visible) {
                $style = '';
            } else {
                $style = 'class="dimmed"';
            }
            $peerforumlink = "<a href=\"view.php?f=$peerforum->id\" $style>" . format_string($peerforum->name, true) . "</a>";
            $discussionlink = "<a href=\"view.php?f=$peerforum->id\" $style>" . $count . "</a>";

            $row = array($printsection, $peerforumlink, $peerforum->intro, $discussionlink);
            if ($usetracking) {
                $row[] = $unreadlink;
                $row[] = $trackedlink;    // Tracking.
            }

            if ($showsubscriptioncolumns) {
                $row[] = peerforum_get_subscribe_link($peerforum, $context, array('subscribed' => $stryes,
                        'unsubscribed' => $strno, 'forcesubscribed' => $stryes,
                        'cantsubscribe' => '-'), false, false, true);
                $row[] = peerforum_index_get_peerforum_subscription_selector($peerforum);
            }

            //If this peerforum has RSS activated, calculate it
            if ($show_rss) {
                if ($peerforum->rsstype and $peerforum->rssarticles) {
                    //Calculate the tolltip text
                    if ($peerforum->rsstype == 1) {
                        $tooltiptext = get_string('rsssubscriberssdiscussions', 'peerforum');
                    } else {
                        $tooltiptext = get_string('rsssubscriberssposts', 'peerforum');
                    }
                    //Get html code for RSS link
                    $row[] = rss_get_link($context->id, $USER->id, 'mod_peerforum', $peerforum->id, $tooltiptext);
                } else {
                    $row[] = '&nbsp;';
                }
            }

            $learningtable->data[] = $row;
        }
    }
}

// Output the page.
$PAGE->navbar->add($strpeerforums);
$PAGE->set_title("$course->shortname: $strpeerforums");
$PAGE->set_heading($course->fullname);
$PAGE->set_button($searchform);
echo $OUTPUT->header();

if (!isguestuser() && isloggedin() && $showsubscriptioncolumns) {
    // Show the subscribe all options only to non-guest, enrolled users.
    echo $OUTPUT->box_start('subscription');

    $subscriptionlink = new moodle_url('/mod/peerforum/index.php', [
            'id' => $course->id,
            'sesskey' => sesskey(),
    ]);

    // Subscribe all.
    $subscriptionlink->param('subscribe', 1);
    echo html_writer::tag('div', html_writer::link($subscriptionlink, get_string('allsubscribe', 'peerforum')), [
            'class' => 'helplink',
    ]);

    // Unsubscribe all.
    $subscriptionlink->param('subscribe', 0);
    echo html_writer::tag('div', html_writer::link($subscriptionlink, get_string('allunsubscribe', 'peerforum')), [
            'class' => 'helplink',
    ]);

    echo $OUTPUT->box_end();
    echo $OUTPUT->box('&nbsp;', 'clearer');
}

if ($generalpeerforums) {
    echo $OUTPUT->heading(get_string('generalpeerforums', 'peerforum'), 2);
    echo html_writer::table($generaltable);
}

if ($learningpeerforums) {
    echo $OUTPUT->heading(get_string('learningpeerforums', 'peerforum'), 2);
    echo html_writer::table($learningtable);
}

echo $OUTPUT->footer();

/**
 * Get the content of the peerforum subscription options for this peerforum.
 *
 * @param stdClass $peerforum The peerforum to return options for
 * @return  string
 */
function peerforum_index_get_peerforum_subscription_selector($peerforum) {
    global $OUTPUT, $PAGE;

    if ($peerforum->cansubscribe || $peerforum->issubscribed) {
        if ($peerforum->maildigest === null) {
            $peerforum->maildigest = -1;
        }

        $renderer = $PAGE->get_renderer('mod_peerforum');
        return $OUTPUT->render($renderer->render_digest_options($peerforum, $peerforum->maildigest));
    } else {
        // This user can subscribe to some peerforums. Add the empty fields.
        return '';
    }
};

