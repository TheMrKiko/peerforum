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

$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$nominationsvault = $vaultfactory->get_relationship_nomination_vault();
$rankingsvault = $vaultfactory->get_relationship_ranking_vault();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/relationships.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
));

set_peergradepanel_page($courseid, $userid, $url, 'managerelations', true, false);

echo $OUTPUT->box_start('posts-list');

$confscale = array(
        2 => 'Totally my feelings',
        1 => 'A solid decision',
        0 => 'Totally random',
);

$rankings = $rankingsvault->get_from_user_id($userid, $courseid, false, false);
$nominations = $nominationsvault->get_from_user_id($userid, $courseid, false);
$othernominations = $userid ? $nominationsvault->get_from_otheruser_id($userid, $courseid, false) : array();
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
    return $a->confidence <=> $b->confidence;
});

uasort($nominations, function($a, $b) {
    return $a->nomination <=> $b->nomination;
});

uasort($nominations, function($a, $b) {
    return $a->userid <=> $b->userid;
});

uasort($othernominations, function($a, $b) {
    return $a->confidence <=> $b->confidence;
});

uasort($othernominations, function($a, $b) {
    return $a->nomination <=> $b->nomination;
});

uasort($othernominations, function($a, $b) {
    return $a->userid <=> $b->userid;
});

echo html_writer::tag('h3', 'Student nominations');
$table = new flexible_table('userselfnominations');

othertable:
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
            new moodle_url($url, array('userid' => $nomination->userid)),
            fullname($users[$nomination->userid])
    );
    $row[] = html_writer::link(
            new moodle_url($url, array('userid' => $nomination->otheruserid)),
            fullname($users[$nomination->otheruserid])
    );
    $row[] = 'Like ' . ($nomination->nomination > 0 ? 'most' : 'least');
    $row[] = $confscale[$nomination->confidence];

    $table->add_data($row);
}

$table->finish_output();

$nominations = $othernominations;
if (!empty($nominations)) {
    $othernominations = array();

    echo html_writer::tag('h3', 'Students who nominated');
    $table = new flexible_table('userothernominations');

    goto othertable;
}

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
            new moodle_url($url, array('userid' => $ranking->userid)),
            fullname($users[$ranking->userid])
    );
    $row[] = html_writer::link(
            new moodle_url($url, array('userid' => $ranking->otheruserid)),
            fullname($users[$ranking->otheruserid])
    );
    $row[] = $ranking->ranking !== null ? $ranking->ranking . ' / 5' : '-';

    $table->add_data($row);
}

$table->finish_output();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();