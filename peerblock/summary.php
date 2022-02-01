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
 * Displays a list of posts with peergrades.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$display = optional_param('display', 1, PARAM_INT);
$expanded = optional_param('expanded', true, PARAM_BOOL);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 8, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/summary.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
        'display' => $display,
        'expanded' => $expanded,
));

set_peergradepanel_page($courseid, $userid, $url, 'manageposts', false, false);

$filters = peerblock_get_items_basefilters($courseid);

// Build filter options.
if ($display == MANAGEPOSTS_MODE_SEEALL) {
    // All posts.
    $filters += array();

} else if ($display == MANAGEPOSTS_MODE_SEENOTGRADED) {
    // Posts to peer grade.
    $filters += array('ended' => 0);

} else if ($display == MANAGEPOSTS_MODE_SEEGRADED) {
    // Posts peergraded.
    $filters += array('peergradednot' => 0);

} else if ($display == MANAGEPOSTS_MODE_SEEEXPIRED) {
    // Posts expired.
    $filters += array('expired' => 1);

}

// Manage users.
$filters += $userid ? array('userid' => $userid) : array();

// Output the page.
$PAGE->requires->js_amd_inline("
    require(['jquery', 'mod_peerforum/posts_list'], function($, View) {
        View.init($('.posts-list'));
    });"
);

$options = get_peerblock_select_options();
echo $OUTPUT->box_start('posts-list');
echo $OUTPUT->render(new single_select($url, 'display', $options, $display, false));

$url = new moodle_url($url, array(
        'page' => $page,
        'perpage' => $perpage,
));

// Gets posts from filters.
$items = $pgmanager->get_items_from_filters($filters);
$totalposts = 0;

if (!empty($items)) {
    $postids = array_map(static function($item) {
        return $item->itemid;
    }, $items);
    $postvault = $vaultfactory->get_post_vault();
    $posts = $postvault->get_from_ids(array_unique($postids));

    $discussionids = array_reduce($posts, function($carry, $post) {
        $did = $post->get_discussion_id();
        if (!in_array($did, $carry)) {
            $carry[] = $did;
        }
        return $carry;
    }, array());
    $discussions = $vaultfactory->get_discussion_vault()->get_from_ids($discussionids);

    $peerforumids = array_reduce($discussions, function($carry, $discussion) {
        $fid = $discussion->get_peerforum_id();
        if (!in_array($fid, $carry)) {
            $carry[] = $fid;
        }
        return $carry;
    }, array());
    $peerforums = $vaultfactory->get_peerforum_vault()->get_from_ids($peerforumids);

    foreach ($peerforums as $peerforum) {
        $pfcontext = $peerforum->get_context();
        if ($userid != $USER->id && $peerforum->is_remainanonymous() &&
                !has_capability('mod/peerforum:professorpeergrade', $pfcontext, null, false)) {
            $posts = array_filter($posts, function($post) use ($peerforum, $discussions) {
                $did = $post->get_discussion_id();
                return $discussions[$did]->get_peerforum_id() != $peerforum->get_id();
            });
        }
    }

    if (!empty($posts)) {
        // Sort posts.
        krsort($posts);
        $totalposts += count($posts);

        $rendererfactory = mod_peerforum\local\container::get_renderer_factory();
        $postsrenderer = $rendererfactory->get_user_peerforum_posts_report_renderer(true);
        $postoutput = $postsrenderer->render(
                $USER,
                $peerforums,
                $discussions,
                array_slice($posts, ($page * $perpage), $perpage, true)
        );
    }
}

if (!empty($postoutput)) {
    echo $OUTPUT->paging_bar($totalposts, $page, $perpage, $url);
    echo $postoutput;
    echo $OUTPUT->paging_bar($totalposts, $page, $perpage, $url);
} else {
    echo 'No posts to show.';
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
