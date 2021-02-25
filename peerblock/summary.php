<?php
/**
 * @package    core_peergrade
 * @copyright  2016 Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Careful when changing. Repeated in lf/lib.php.
define('MANAGEPOSTS_MODE_SEEALL', 1);
define('MANAGEPOSTS_MODE_SEEGRADED', 2);
define('MANAGEPOSTS_MODE_SEENOTGRADED', 3);
define('MANAGEPOSTS_MODE_SEENOTEXPIRED', 4);
define('MANAGEPOSTS_MODE_SEEEXPIRED', 5);

define('MANAGEGRADERS_MODE_SEEALL', 1);
define('MANAGEGRADERS_MODE_SEENOTEXPIRED', 2);
define('MANAGEGRADERS_MODE_SEEEXPIRED', 3);
define('MANAGEGRADERS_MODE_SEENOTGRADED', 4);
define('MANAGEGRADERS_MODE_SEEGRADED', 5);

define('VIEWPEERGRADES_MODE_SEEALL', 1);
define('VIEWPEERGRADES_MODE_SEEWARNINGS', 2);
define('VIEWPEERGRADES_MODE_SEEOUTLIERS', 3);

define('RELATIONSHIPS_MODE_NOMINATIONS', 1);
define('RELATIONSHIPS_MODE_RANKINGS', 2);

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$display = optional_param('display', 1, PARAM_INT);

$PAGE->requires->css('/peergrading/style.css');

if (isset($userid) && empty($courseid)) {
    $context = context_user::instance($userid);
} else if (!empty($courseid) && $courseid != SITEID) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}
$PAGE->set_context($context);

$courseid = (empty($courseid)) ? SITEID : $courseid;
require_login($courseid);

$canviewalltabs = true;
if (!has_capability('mod/peerforum:professorpeergrade', $context)) {
    $userid = $USER->id;
    $canviewalltabs = false;
}

$url = new moodle_url('/blocks/peerblock/summary.php');
$urlparams = array(
        'display' => $display,
        'userid' => $userid,
        'courseid' => $courseid,
);
$PAGE->set_url($url, $urlparams);

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
$manageconflicts = get_string('manageconflicts', 'block_peerblock');
$managegradersposts = get_string('managegraders_posts', 'block_peerblock');
$viewgradersstats = get_string('viewgradersstats', 'block_peerblock');
$managerelations = get_string('managerelations', 'block_peerblock');
$threadingstats = get_string('threadingstats', 'block_peerblock');
$peerranking = get_string('peer_ranking', 'block_peerblock');
$managetraining = get_string('managetraining', 'block_peerblock');

echo $OUTPUT->box_start();

$row[] = new tabobject('topeergrade',
        new moodle_url($url, array('display' => MANAGEPOSTS_MODE_SEEALL) + $urlparams), $poststopeergrade);
$row[] = new tabobject('peergraded',
        new moodle_url($url, array('display' => MANAGEPOSTS_MODE_SEEGRADED) + $urlparams), $postspeergraded);
$row[] = new tabobject('expired',
        new moodle_url($url, array('display' => MANAGEPOSTS_MODE_SEEEXPIRED) + $urlparams), $postsexpired);

$userfilter = $userid ? array('userid' => $userid) : array();

if ($display == MANAGEPOSTS_MODE_SEEALL) {
    // Posts to peer grade.
    $tabtitle = $poststopeergrade;
    $filters = array('ended' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEGRADED) {
    // Posts peergraded.
    $tabtitle = $postspeergraded;
    $filters = array('peergradednot' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEEXPIRED) {
    // Posts expired.
    $tabtitle = $postsexpired;
    $filters = array('expired' => 1) + $userfilter;

}

// TODO Peers to Rank!

echo $OUTPUT->tabtree($row, $display);

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();

$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
$items = $pgmanager->get_items_from_filters($filters);

if (empty($items)) {
    $postoutput = 'No posts to show.';
} else {

    $postids = array_map(static function($item) {
        return $item->itemid;
    }, $items);
    $postvault = $vaultfactory->get_post_vault();
    $posts = array_map(static function($postid) use ($postvault){
        return $postvault->get_from_id($postid);
    }, array_unique($postids));

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

    $postsrenderer = $rendererfactory->get_user_peerforum_posts_report_renderer(true);
    $postoutput = $postsrenderer->render(
            $USER,
            $peerforums,
            $discussions,
            $posts
    );
}
echo $OUTPUT->heading(format_string($tabtitle), 2);
echo $postoutput;
echo $OUTPUT->box_end();
echo $OUTPUT->footer();