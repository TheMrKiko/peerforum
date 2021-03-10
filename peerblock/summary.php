<?php
/**
 * @package    core_peergrade
 * @copyright  2016 Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$display = optional_param('display', 1, PARAM_INT);

if (isset($userid) && empty($courseid)) {
    $context = context_user::instance($userid);
} else if (!empty($courseid) && $courseid != SITEID) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}
$PAGE->set_context($context);

$logcourseid = (empty($courseid)) ? SITEID : $courseid;
require_login($logcourseid);

$canviewalltabs = false;
if (has_capability('mod/peerforum:professorpeergrade', $context, null, false)) {
    $canviewalltabs = true;
}

$urlparams = array(
        'userid' => $userid,
        'courseid' => $courseid,
);
$url = new moodle_url('/blocks/peerblock/summary.php', $urlparams);
$PAGE->set_url($url, array('display' => $display, ));

// Output the page.
$pagetitle = get_string('pluginname', 'block_peerblock');

$PAGE->navbar->add($pagetitle);
$PAGE->set_title(format_string($pagetitle));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();

// Strings.
$poststopeergrade = get_string('poststopeergrade', 'block_peerblock');
$postspeergraded = get_string('postspeergraded', 'block_peerblock');
$postsexpired = get_string('postsexpired', 'block_peerblock');
$viewpeergrades = get_string('viewpeergrades', 'block_peerblock');
$manageposts = get_string('manageposts', 'block_peerblock');
$postsassigned = get_string('postsassigned', 'block_peerblock');
$manageconflicts = get_string('manageconflicts', 'block_peerblock');
$managegradersposts = get_string('managegraders_posts', 'block_peerblock');
$viewgradersstats = get_string('viewgradersstats', 'block_peerblock');
$managerelations = get_string('managerelations', 'block_peerblock');
$threadingstats = get_string('threadingstats', 'block_peerblock');
$peerranking = get_string('peer_ranking', 'block_peerblock');
$managetraining = get_string('managetraining', 'block_peerblock');
echo $OUTPUT->box_start('posts-list');

$options = array(
        MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
        MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
        MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
        MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
);

$userfilter = $userid ? array('userid' => $userid) : array();

if ($display == MANAGEPOSTS_MODE_SEEALL) {
    // All posts.
    $filters = $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEENOTGRADED) {
    // Posts to peer grade.
    $filters = array('ended' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEGRADED) {
    // Posts peergraded.
    $filters = array('peergradednot' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEEXPIRED) {
    // Posts expired.
    $filters = array('expired' => 1) + $userfilter;

}
$row = get_peerblock_tabs($urlparams, $canviewalltabs, $userid == $USER->id);
echo $OUTPUT->tabtree($row, 'manageposts');
echo $OUTPUT->render(new single_select($url, 'display', $options, $display, false));

$PAGE->requires->js_amd_inline("
    require(['jquery', 'mod_peerforum/posts_list'], function($, View) {
        View.init($('.posts-list'));
    });"
);

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();

$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
$items = $pgmanager->get_items_from_filters($filters);

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
        krsort($posts);
        $postsrenderer = $rendererfactory->get_user_peerforum_posts_report_renderer(true);
        $postoutput = $postsrenderer->render(
                $USER,
                $peerforums,
                $discussions,
                $posts
        );
    }
}

echo $postoutput = !empty($postoutput) ? $postoutput : 'No posts to show.';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
