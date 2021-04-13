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
 * Displays an overview of the relationships between students.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('../../user/lib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require_once('./lib.php');

$urlfactory = mod_peerforum\local\container::get_url_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$nominationsvault = $vaultfactory->get_relationship_nomination_vault();
$rankingsvault = $vaultfactory->get_relationship_ranking_vault();

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
$url = new moodle_url('/blocks/peerblock/relationships.php', $urlparams);
$PAGE->set_url($url);

// Manage users.
$userid = $canviewalltabs ? $userid : $USER->id;

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
echo $OUTPUT->tabtree($row, 'managerelations');

$confscale = array(
        2 => 'Totally my feelings',
        1 => 'A solid decision',
        0 => 'Totally random',
);

$rankings = $rankingsvault->get_from_user_id($userid, $courseid, false, false);
$nominations = $nominationsvault->get_from_user_id($userid, $courseid, false);
$userids = [];

foreach($nominations as $n) {
    $userids[$n->userid] = true;
    $userids[$n->otheruserid] = true;
}
foreach($rankings as $r) {
    $userids[$r->userid] = true;
    $userids[$r->otheruserid] = true;
}
$users = user_get_users_by_id(array_keys($userids));

/*------------------------------- NOMINATIONS -------------------------------*/
uasort($nominations, function($a, $b) {
    return $a->userid <=> $b->userid;
});

echo html_writer::tag('h3', 'Student nominations');
$table = new flexible_table('userselfnominations');
$table->set_attribute('class', 'table table-responsive table-striped');
$table->define_columns(array(
        'fullname',
        'nominee',
        'nomination',
        'confidence',
));
$table->define_headers(array(
        'Student',
        'Nominee',
        'Nomination',
        'Confidence',
));
$table->column_style_all('text-align', 'center');
$table->column_style('fullname', 'text-align', 'left');
$table->column_style('nominee', 'text-align', 'left');
$table->define_baseurl($url);
$table->define_header_column('fullname');
$table->column_suppress('fullname');
$table->setup();

foreach ($nominations as $nomination) {
    $row = array();
    $row[] = html_writer::link(
            $urlfactory->get_user_summary_url($users[$nomination->userid], $courseid),
            fullname($users[$nomination->userid])
    );
    $row[] = html_writer::link(
            $urlfactory->get_user_summary_url($users[$nomination->otheruserid], $courseid),
            fullname($users[$nomination->otheruserid])
    );
    $row[] = 'Like ' . ($nomination->nomination > 0 ? 'most' : 'least');
    $row[] = $confscale[$nomination->confidence];

    $table->add_data($row);
}

$table->finish_output();

/*------------------------------- RANKINGS -------------------------------*/
uasort($rankings, function($a, $b) {
    return $a->userid <=> $b->userid;
});

echo html_writer::tag('h3', 'Student rankings');
$table = new flexible_table('userselfrankings');
$table->set_attribute('class', 'table table-responsive table-striped');
$table->define_columns(array(
        'fullname',
        'rankee',
        'rank',
));
$table->define_headers(array(
        'Student',
        'Rankee',
        'Rank',
));
$table->column_style_all('text-align', 'center');
$table->column_style('fullname', 'text-align', 'left');
$table->column_style('rankee', 'text-align', 'left');
$table->define_baseurl($url);
$table->define_header_column('fullname');
$table->column_suppress('fullname');
$table->setup();

foreach ($rankings as $ranking) {
    $row = array();
    $row[] = html_writer::link(
            $urlfactory->get_user_summary_url($users[$ranking->userid], $courseid),
            fullname($users[$ranking->userid])
    );
    $row[] = html_writer::link(
            $urlfactory->get_user_summary_url($users[$ranking->otheruserid], $courseid),
            fullname($users[$ranking->otheruserid])
    );
    $row[] = $ranking->ranking ? $ranking->ranking . ' / 5' : '-';

    $table->add_data($row);
}

$table->finish_output();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();