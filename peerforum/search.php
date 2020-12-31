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

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);                  // course id
$search = trim(optional_param('search', '', PARAM_NOTAGS));  // search string
$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = optional_param('perpage', 10, PARAM_INT);   // how many per page
$showform = optional_param('showform', 0, PARAM_INT);   // Just show the form

$user = trim(optional_param('user', '', PARAM_NOTAGS));    // Names to search for
$userid = trim(optional_param('userid', 0, PARAM_INT));      // UserID to search for
$peerforumid = trim(optional_param('peerforumid', 0, PARAM_INT));      // PeerForumID to search for
$subject = trim(optional_param('subject', '', PARAM_NOTAGS)); // Subject
$phrase = trim(optional_param('phrase', '', PARAM_NOTAGS));  // Phrase
$words = trim(optional_param('words', '', PARAM_NOTAGS));   // Words
$fullwords = trim(optional_param('fullwords', '', PARAM_NOTAGS)); // Whole words
$notwords = trim(optional_param('notwords', '', PARAM_NOTAGS));   // Words we don't want
$tags = optional_param_array('tags', [], PARAM_TEXT);

$timefromrestrict = optional_param('timefromrestrict', 0, PARAM_INT); // Use starting date
$fromday = optional_param('fromday', 0, PARAM_INT);      // Starting date
$frommonth = optional_param('frommonth', 0, PARAM_INT);      // Starting date
$fromyear = optional_param('fromyear', 0, PARAM_INT);      // Starting date
$fromhour = optional_param('fromhour', 0, PARAM_INT);      // Starting date
$fromminute = optional_param('fromminute', 0, PARAM_INT);      // Starting date
if ($timefromrestrict) {
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $gregorianfrom = $calendartype->convert_to_gregorian($fromyear, $frommonth, $fromday);
    $datefrom = make_timestamp($gregorianfrom['year'], $gregorianfrom['month'], $gregorianfrom['day'], $fromhour, $fromminute);
} else {
    $datefrom = optional_param('datefrom', 0, PARAM_INT);      // Starting date
}

$timetorestrict = optional_param('timetorestrict', 0, PARAM_INT); // Use ending date
$today = optional_param('today', 0, PARAM_INT);      // Ending date
$tomonth = optional_param('tomonth', 0, PARAM_INT);      // Ending date
$toyear = optional_param('toyear', 0, PARAM_INT);      // Ending date
$tohour = optional_param('tohour', 0, PARAM_INT);      // Ending date
$tominute = optional_param('tominute', 0, PARAM_INT);      // Ending date
if ($timetorestrict) {
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $gregorianto = $calendartype->convert_to_gregorian($toyear, $tomonth, $today);
    $dateto = make_timestamp($gregorianto['year'], $gregorianto['month'], $gregorianto['day'], $tohour, $tominute);
} else {
    $dateto = optional_param('dateto', 0, PARAM_INT);      // Ending date
}
$starredonly = optional_param('starredonly', false, PARAM_BOOL); // Include only favourites.

$PAGE->set_pagelayout('standard');
$PAGE->set_url($FULLME); //TODO: this is very sloppy --skodak

if (empty($search)) {   // Check the other parameters instead
    if (!empty($words)) {
        $search .= ' ' . $words;
    }
    if (!empty($userid)) {
        $search .= ' userid:' . $userid;
    }
    if (!empty($peerforumid)) {
        $search .= ' peerforumid:' . $peerforumid;
    }
    if (!empty($user)) {
        $search .= ' ' . peerforum_clean_search_terms($user, 'user:');
    }
    if (!empty($subject)) {
        $search .= ' ' . peerforum_clean_search_terms($subject, 'subject:');
    }
    if (!empty($fullwords)) {
        $search .= ' ' . peerforum_clean_search_terms($fullwords, '+');
    }
    if (!empty($notwords)) {
        $search .= ' ' . peerforum_clean_search_terms($notwords, '-');
    }
    if (!empty($phrase)) {
        $search .= ' "' . $phrase . '"';
    }
    if (!empty($datefrom)) {
        $search .= ' datefrom:' . $datefrom;
    }
    if (!empty($dateto)) {
        $search .= ' dateto:' . $dateto;
    }
    if (!empty($tags)) {
        $search .= ' tags:' . implode(',', $tags);
    }
    if (!empty($starredonly)) {
        $search .= ' starredonly:on';
    }
    $individualparams = true;
} else {
    $individualparams = false;
}

if ($search) {
    $search = peerforum_clean_search_terms($search);
}

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);

$params = array(
        'context' => $PAGE->context,
        'other' => array('searchterm' => $search)
);

$event = \mod_peerforum\event\course_searched::create($params);
$event->trigger();

$strpeerforums = get_string("modulenameplural", "peerforum");
$strsearch = get_string("search", "peerforum");
$strsearchresults = get_string("searchresults", "peerforum");
$strpage = get_string("page");

if (!$search || $showform) {

    $PAGE->navbar->add($strpeerforums, new moodle_url('/mod/peerforum/index.php', array('id' => $course->id)));
    $PAGE->navbar->add(get_string('advancedsearch', 'peerforum'));

    $PAGE->set_title($strsearch);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();

    peerforum_print_big_search_form($course);
    echo $OUTPUT->footer();
    exit;
}

/// We need to do a search now and print results

$searchterms = str_replace('peerforumid:', 'instance:', $search);
$searchterms = explode(' ', $searchterms);

$searchform = peerforum_search_form($course, $search);

$PAGE->navbar->add($strsearch, new moodle_url('/mod/peerforum/search.php', array('id' => $course->id)));
$PAGE->navbar->add($strsearchresults);
if (!$posts = peerforum_search_posts($searchterms, $course->id, $page * $perpage, $perpage, $totalcount)) {
    $PAGE->set_title($strsearchresults);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strpeerforums, 2);
    echo $OUTPUT->heading($strsearchresults, 3);
    echo $OUTPUT->heading(get_string("noposts", "peerforum"), 4);

    if (!$individualparams) {
        $words = $search;
    }

    peerforum_print_big_search_form($course);

    echo $OUTPUT->footer();
    exit;
}

//including this here to prevent it being included if there are no search results
require_once($CFG->dirroot . '/ratingpeer/lib.php');

//set up the ratingpeers information that will be the same for all posts
$ratingpeeroptions = new stdClass();
$ratingpeeroptions->component = 'mod_peerforum';
$ratingpeeroptions->ratingpeerarea = 'post';
$ratingpeeroptions->userid = $USER->id;
$ratingpeeroptions->returnurl = $PAGE->url->out(false);
$rm = new ratingpeer_manager();

$PAGE->set_title($strsearchresults);
$PAGE->set_heading($course->fullname);
$PAGE->set_button($searchform);
echo $OUTPUT->header();
echo '<div class="reportlink">';

$params = [
        'id' => $course->id,
        'user' => $user,
        'userid' => $userid,
        'peerforumid' => $peerforumid,
        'subject' => $subject,
        'phrase' => $phrase,
        'words' => $words,
        'fullwords' => $fullwords,
        'notwords' => $notwords,
        'dateto' => $dateto,
        'datefrom' => $datefrom,
        'showform' => 1,
        'starredonly' => $starredonly
];
$url = new moodle_url("/mod/peerforum/search.php", $params);
foreach ($tags as $tag) {
    $url .= "&tags[]=$tag";
}
echo html_writer::link($url, get_string('advancedsearch', 'peerforum') . '...');

echo '</div>';

echo $OUTPUT->heading($strpeerforums, 2);
echo $OUTPUT->heading("$strsearchresults: $totalcount", 3);

$url = new moodle_url('search.php', array('search' => $search, 'id' => $course->id, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

//added to implement highlighting of search terms found only in HTML markup
//fiedorow - 9/2/2005
$strippedsearch = str_replace('user:', '', $search);
$strippedsearch = str_replace('subject:', '', $strippedsearch);
$strippedsearch = str_replace('&quot;', '', $strippedsearch);
$searchterms = explode(' ', $strippedsearch);    // Search for words independently
foreach ($searchterms as $key => $searchterm) {
    if (preg_match('/^\-/', $searchterm)) {
        unset($searchterms[$key]);
    } else {
        $searchterms[$key] = preg_replace('/^\+/', '', $searchterm);
    }
}
$strippedsearch = implode(' ', $searchterms);    // Rebuild the string
$entityfactory = mod_peerforum\local\container::get_entity_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();

$discussionvault = $vaultfactory->get_discussion_vault();
$discussionids = array_keys(array_reduce($posts, function($carry, $post) {
    $carry[$post->discussion] = true;
    return $carry;
}, []));
$discussions = $discussionvault->get_from_ids($discussionids);
$discussionsbyid = array_reduce($discussions, function($carry, $discussion) {
    $carry[$discussion->get_id()] = $discussion;
    return $carry;
}, []);

$peerforumvault = $vaultfactory->get_peerforum_vault();
$peerforumids = array_keys(array_reduce($discussions, function($carry, $discussion) {
    $carry[$discussion->get_peerforum_id()] = true;
    return $carry;
}, []));
$peerforums = $peerforumvault->get_from_ids($peerforumids);
$peerforumsbyid = array_reduce($peerforums, function($carry, $peerforum) {
    $carry[$peerforum->get_id()] = $peerforum;
    return $carry;
}, []);

$postids = array_map(function($post) {
    return $post->id;
}, $posts);

$poststorender = [];

foreach ($posts as $post) {

    // Replace the simple subject with the three items peerforum name -> thread name -> subject
    // (if all three are appropriate) each as a link.
    if (!isset($discussionsbyid[$post->discussion])) {
        print_error('invaliddiscussionid', 'peerforum');
    }

    $discussion = $discussionsbyid[$post->discussion];
    if (!isset($peerforumsbyid[$discussion->get_peerforum_id()])) {
        print_error('invalidpeerforumid', 'peerforum');
    }

    $peerforum = $peerforumsbyid[$discussion->get_peerforum_id()];
    $capabilitymanager = $managerfactory->get_capability_manager($peerforum);
    $postentity = $entityfactory->get_post_from_stdclass($post);

    if (!$capabilitymanager->can_view_post($USER, $discussion, $postentity)) {
        // Don't render posts that the user can't view.
        continue;
    }

    if ($postentity->is_deleted()) {
        // Don't render deleted posts.
        continue;
    }

    $poststorender[] = $postentity;
}

$renderer = $rendererfactory->get_posts_search_results_renderer($searchterms);
echo $renderer->render(
        $USER,
        $peerforumsbyid,
        $discussionsbyid,
        $poststorender
);

echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

echo $OUTPUT->footer();

/**
 * Print a full-sized search form for the specified course.
 *
 * @param stdClass $course The Course that will be searched.
 * @return void The function prints the form.
 */
function peerforum_print_big_search_form($course) {
    global $PAGE, $words, $subject, $phrase, $user, $fullwords, $notwords, $datefrom,
           $dateto, $peerforumid, $tags, $starredonly;

    $renderable = new \mod_peerforum\output\big_search_form($course, $user);
    $renderable->set_words($words);
    $renderable->set_phrase($phrase);
    $renderable->set_notwords($notwords);
    $renderable->set_fullwords($fullwords);
    $renderable->set_datefrom($datefrom);
    $renderable->set_dateto($dateto);
    $renderable->set_subject($subject);
    $renderable->set_user($user);
    $renderable->set_peerforumid($peerforumid);
    $renderable->set_tags($tags);
    $renderable->set_starredonly($starredonly);

    $output = $PAGE->get_renderer('mod_peerforum');
    echo $output->render($renderable);
}

/**
 * This function takes each word out of the search string, makes sure they are at least
 * two characters long and returns an string of the space-separated search
 * terms.
 *
 * @param string $words String containing space-separated strings to search for.
 * @param string $prefix String to prepend to the each token taken out of $words.
 * @return string The filtered search terms, separated by spaces.
 * @todo Take the hardcoded limit out of this function and put it into a user-specified parameter.
 */
function peerforum_clean_search_terms($words, $prefix = '') {
    $searchterms = explode(' ', $words);
    foreach ($searchterms as $key => $searchterm) {
        if (strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        } else if ($prefix) {
            $searchterms[$key] = $prefix . $searchterm;
        }
    }
    return trim(implode(' ', $searchterms));
}

/**
 * Retrieve a list of the peerforums that this user can view.
 *
 * @param stdClass $course The Course to use.
 * @return array A set of formatted peerforum names stored against the peerforum id.
 */
function peerforum_menu_list($course) {
    $menu = array();

    $modinfo = get_fast_modinfo($course);
    if (empty($modinfo->instances['peerforum'])) {
        return $menu;
    }

    foreach ($modinfo->instances['peerforum'] as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        $context = context_module::instance($cm->id);
        if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
            continue;
        }
        $menu[$cm->instance] = format_string($cm->name);
    }

    return $menu;
}
