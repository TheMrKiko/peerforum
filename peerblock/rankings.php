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
 * Renders the rankings for peers.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$managerfactory = mod_peerforum\local\container::get_manager_factory();
$urlfactory = mod_peerforum\local\container::get_url_factory();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/rankings.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
));

set_peergradepanel_page($courseid, $userid, $url, 'peerranking', false, true);

$rankingvault = $vaultfactory->get_relationship_ranking_vault();
$rankingfull = $rankingvault->get_from_user_id($userid, $courseid);
$users = $rankingfull['otheruserid'] ?? array();
$rankings = $rankingfull['ranking'] ?? array();
$ids = $rankingfull['id'] ?? array();
$userids = array_keys($users);

if (count($userids) >= 5) {
    require_once('../../user/lib.php');

    $studentobjs = user_get_users_by_id($userids);
    $students = array();
    foreach ($studentobjs as $student) {
        $students[$student->id] = fullname($student);
    }

    $scale = array(RATING_UNSET_RATING => 'Select one...') + range(0, 5);

    // Instantiate relationships_form.
    $mform = new \block_peerblock\rankings_form('rankings.php', [
            'students' => $students,
            'scale' => $scale,
    ]);

    $mform->set_data(
            array(
                    'rankings' => $rankings,
                    'ids' => $ids,
            ) +

            $url->params()
    );

    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {

        redirect($urlfactory->get_course_url_from_courseid($courseid));

    } else if ($mform->is_submitted() && $fromform = $mform->get_data()) {

        $ranks = array();
        $ranks['ranking'] = $fromform->rankings;
        $ranks['id'] = $fromform->ids;

        $data = new stdClass();
        $data->rankings = $ranks;

        if (peerblock_edit_rankings($data, $mform)) {
            \core\notification::success('Submitted, thank you! Go make more friends (or foes).');

        } else {
            print_error("couldnotadd", "peerforum");
            exit;

        }

    }
}

if (isset($mform) && !$mform->is_submitted()) {
    echo $OUTPUT->heading(format_string(get_string('rankinghelp', 'block_peerblock')), 5);
    $mform->display();
} else {
    echo 'You have no peers to rank.';
}

echo $OUTPUT->footer();
