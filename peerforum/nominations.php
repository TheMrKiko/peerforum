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
 * Edit and save a new post to a discussion
 * Custom functions to allow peergrading of PeerForum posts
 *
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$peerforumid = required_param('peerforum', PARAM_INT);

$PAGE->set_url('/mod/nominations.php', array(
        'peerforum' => $peerforumid,
));
// These page_params will be passed as hidden variables later in the form.
$pageparams = array('peerforum' => $peerforumid);

$sitecontext = context_system::instance();

$entityfactory = mod_peerforum\local\container::get_entity_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$urlfactory = mod_peerforum\local\container::get_url_factory();

$peerforumvault = $vaultfactory->get_peerforum_vault();
$peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();

require_login(0, false);   // Script is useless unless they're logged in.

if (!empty($peerforumid)) {
    // User is starting a new discussion in a peerforum.
    $peerforumentity = $peerforumvault->get_from_id($peerforumid);
    if (empty($peerforumentity)) {
        print_error('invalidpeerforumid', 'peerforum');
    }

    $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
    $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);
    $course = $peerforumentity->get_course_record();
    if (!$cm = get_coursemodule_from_instance("peerforum", $peerforum->id, $course->id)) {
        print_error("invalidcoursemodule");
    }

    // Retrieve the contexts.
    $modcontext = $peerforumentity->get_context();
    $coursecontext = context_course::instance($course->id);

    /*if (!has_capability('mod/peerforum:studentpeergrade', $modcontext)) {
        print_error('cannoteditposts', 'peerforum');
    }*/

    $userid = $USER->id;
    $nominationvault = $vaultfactory->get_relationship_nomination_vault();
    $nominationsfull = $nominationvault->get_from_user_id($userid, $course->id);
    $nominations = $nominationsfull['otheruserid'] ?? array();
    $nominations['1'] = $nominations['1'] ?? array();
    $nominations['-1'] = $nominations['-1'] ?? array();
    $ids = $nominationsfull['id'] ?? array();
    $ids['1'] = $ids['1'] ?? array();
    $ids['-1'] = $ids['-1'] ?? array();

    $PAGE->set_cm($cm, $course, $peerforum);

} else {
    print_error('unknowaction');
}

require_login($course, false, $cm);

$unamefields = get_all_user_name_fields(true, 'u');
$allstudents = get_users_by_capability($modcontext, 'mod/peerforum:studentpeergrade', 'u.id,' . $unamefields);

foreach ($allstudents as $i => $u) {
    if ($i == $userid) {
        unset($allstudents[$i]);
        continue;
    }
    $allstudents[$i] = fullname($u);
}
$allstudents = array(0 => 'Select one...') + $allstudents;

// Instantiate relationships_form.
$mform = new mod_peerforum_nominations_form('nominations.php', [
        'peerforum' => $peerforum,
        'fieldsatatime' => $peerforumentity->is_peernominationsaddfields(),
        'minfields' => $peerforumentity->get_peernominationsfields() ?: 1,
        'allstudents' => $allstudents,
        'inilmfields' => count($nominations['1']),
        'inillfields' => count($nominations['-1']),
]);

$mform->set_data(
        array(
                'nominations' => $nominations,
                'ids' => $ids,
        ) +

        $pageparams
);

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {

    redirect($urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity));

} else if ($mform->is_submitted() && $fromform = $mform->get_data()) {

    $noms = array();
    $noms['otheruser'] = $fromform->nominations;
    $noms['id'] = $fromform->ids;

    $data = new stdClass();
    $data->userid = $userid;
    $data->course = $course;
    $data->nominations = $noms;
    $data->peerforum = $peerforum;
    $data->fullinfo = $nominationsfull;

    if (peerforum_edit_nominations($data, $mform)) {
        redirect($urlfactory->get_peerforum_view_url_from_peerforum($peerforumentity),
        'Submitted, thank you! You can now peer grade.',
        null,
        \core\notification::SUCCESS,
        );

    } else {
        print_error("couldnotadd", "peerforum", $errordestination);
    }
    exit;

}

$strparentname = 'Peer nominations';
$title = get_string('relquest', 'peerforum');

$PAGE->navbar->add($strparentname);

$PAGE->set_title("{$course->shortname}: {$strparentname}");
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo get_string('questionnaire_instructions', 'peerforum');

$mform->display();

echo $OUTPUT->footer();
