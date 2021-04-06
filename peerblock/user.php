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
 * Displays stats about peergrades.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require_once('./lib.php');

$urlfactory = mod_peerforum\local\container::get_url_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$postsdatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory()->get_post_data_mapper();
$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

// Build context objects.
$courseid = $courseid ?: SITEID;
if (!empty($userid)) {
    $usercontext = context_user::instance($userid);
}
if ($courseid != SITEID) {
    $coursecontext = context_course::instance($courseid);
} else {
    $coursecontext = context_system::instance();
}

$PAGE->set_context($usercontext ?? $coursecontext);

// Must be logged in.
require_login($courseid);

$canviewalltabs = has_capability('mod/peerforum:professorpeergrade', $coursecontext, null, false);
// Check if the person can be here.
if (!$canviewalltabs) {
    print_error('error');
}

// Build url.
$urlparams = array(
        'userid' => $userid,
        'courseid' => $courseid,
);
$url = new moodle_url('/blocks/peerblock/user.php', $urlparams);
$PAGE->set_url($url);

// Manage users.
$userid = $canviewalltabs ? $userid : $USER->id;
$userfilter = $userid ? array('userid' => $userid) : array();

$row = get_peerblock_tabs($urlparams, $canviewalltabs, $userid == $USER->id);

$blockname = get_string('pluginname', 'block_peerblock');
$subtitle = 'User';
$pagetitle = $blockname;

// Output the page.
if (!empty($usercontext)) {
    $pagetitle .= ': ' . $subtitle;
    $burl = $courseid != SITEID ? new moodle_url($url, array('userid' => 0)) : null;
    $PAGE->navbar->add($blockname, $burl);
    $PAGE->navbar->add($subtitle);
} else {
    $PAGE->set_heading($blockname);
    $PAGE->navbar->add($blockname);
}
$PAGE->set_title(format_string($pagetitle));

echo $OUTPUT->header();
echo $OUTPUT->box_start('posts-list');
echo $OUTPUT->tabtree($row, 'viewgradersstats');

$table = new flexible_table('userpgstatistics');
$table->set_attribute('class', 'generalboxtable table table-striped');

$table->define_columns(array(
        'fullname',
        'nid',
        'npeergraded',
        'nexpired',
        'nended',
        'performance',
        'ublocked'
));
$table->define_headers(array(
        'User',
        'Assigned',
        'Peer graded',
        'Expired',
        'Ended',
        'Performance',
        'Block'
));
$table->column_style_all('text-align', 'center');
$table->column_style('fullname', 'text-align', 'left');

$table->sortable(true, 'firstname');
$table->is_persistent(false);
$table->define_baseurl($url);
$table->text_sorting('block');
$table->define_header_column('fullname');
$table->setup();

$group = array('userid');
$count = array('id', 'peergraded', 'expired', 'ended');
$perfalias =
        'nid AS performance';
// Gets posts from filters.
$items = $pgmanager->get_items_from_filters($userfilter, $perfalias, $table->get_sql_sort(), $group, $count);

foreach ($items as $item) {
    $user = user_picture::unalias($item, ['deleted'], 'userid');
    $userblocked = !empty($item->ublocked);
    $blockurl = $pgmanager->get_block_user_url($user->id, $coursecontext->id);
    $row = array();
    $rowclass = $userblocked ? 'bg-warning' : '';

    $subjcell = html_writer::link(
            $urlfactory->get_user_summary_url($user, $courseid),
            fullname($user),
    );
    $row[] = $subjcell;
    $row[] = $item->nid;
    $row[] = $item->npeergraded;
    $row[] = $item->nexpired;
    $row[] = $item->nended;
    $row[] = number_format($item->performance, 1 ) . '%';

    $singlebutton = new single_button($blockurl, (!$userblocked ? 'B' : 'Unb') . 'lock');
    $row[] = $OUTPUT->render($singlebutton);
    $table->add_data($row, $rowclass);
}
$table->finish_output();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
