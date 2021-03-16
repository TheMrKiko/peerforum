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
$url = new moodle_url('/blocks/peerblock/user.php', $urlparams);
$PAGE->set_url($url);

// Output the page.
$pagetitle = get_string('pluginname', 'block_peerblock');

// Check if the person can be here.
if (!$canviewalltabs) {
    print_error('error');
}

$PAGE->navbar->add($pagetitle);
$PAGE->set_title(format_string($pagetitle));
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

$userfilter = $userid ? array('userid' => $userid) : array();
$filters = $userfilter;

$row = get_peerblock_tabs($urlparams, $canviewalltabs, $userid == $USER->id);
echo $OUTPUT->tabtree($row, 'viewgradersstats');

$group = array('userid');
$count = array('id', 'peergraded', 'expired', 'ended');

$urlfactory = mod_peerforum\local\container::get_url_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$entityfactory = mod_peerforum\local\container::get_entity_factory();
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$postsdatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory()->get_post_data_mapper();

$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
$items = $pgmanager->get_items_from_filters($filters, $group, $count);

$table = new html_table;
$table->attributes['class'] = 'generalboxtable table table-striped';
$table->head = array(
        'User',
        'Assigned',
        'Peer graded',
        'Expired',
        'Ended',
);
$table->align = array(
        'center',
        'center',
        'center',
        'center',
        'center',
);

$table->data = array();

foreach ($items as $item) {
    $user = user_picture::unalias($item, ['deleted'], 'userid');
    $row = new html_table_row();

    $subjcell = new html_table_cell(html_writer::link(
            $urlfactory->get_user_summary_url($user),
            fullname($user),
    ));
    $subjcell->header = true;
    $row->cells[] = $subjcell;
    $row->cells[] = $item->nid;
    $row->cells[] = $item->npeergraded;
    $row->cells[] = $item->nexpired;
    $row->cells[] = $item->nended;

    $table->data[] = $row;
}
echo !empty($items) ? html_writer::table($table) : 'No users to show.';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
