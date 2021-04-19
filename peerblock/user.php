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

$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$postsdatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory()->get_post_data_mapper();
$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/user.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
));

set_peergradepanel_page($courseid, $userid, $url, 'viewgradersstats', true, false);

$coursecontext = context_course::instance($courseid);

// Manage users.
$userfilter = $userid ? array('userid' => $userid) : array();

echo $OUTPUT->box_start('posts-list');

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
$table->sortable(true);
$table->is_persistent(false);
$table->define_baseurl($url);
$table->define_header_column('fullname');
$table->setup();

$group = array('userid');
$count = array('id', 'peergraded', 'expired', 'ended');
$perfalias = 'CASE
                WHEN (npeergraded + nexpired) = 0
                THEN 0
                ELSE (npeergraded * 100) / (npeergraded + nexpired)
              END AS performance';
// Gets posts from filters.
$items = $pgmanager->get_items_from_filters($userfilter, $perfalias, $table->get_sql_sort(), $group, $count);

foreach ($items as $item) {
    $user = user_picture::unalias($item, ['deleted'], 'userid');
    $userblocked = !empty($item->ublocked);
    $blockurl = $pgmanager->get_block_user_url($user->id, $coursecontext->id);
    $row = array();
    $rowclass = $userblocked ? 'bg-danger' : '';

    $subjcell = html_writer::link(
            new moodle_url($url, array('userid' => $user->id)),
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
