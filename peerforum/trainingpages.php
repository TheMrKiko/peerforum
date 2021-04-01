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
 * A page to display a list of training pages
 *
 * @package    core_peergrade
 * @category   peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$managerfactory = mod_peerforum\local\container::get_manager_factory();
$legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$urlfactory = mod_peerforum\local\container::get_url_factory();
$peerforumvault = $vaultfactory->get_peerforum_vault();
$trainingpagesvault = $vaultfactory->get_training_page_vault();
$discussionsvault = $vaultfactory->get_discussion_vault();

$cmid = optional_param('id', 0, PARAM_INT);
$peerforumid = optional_param('pf', 0, PARAM_INT);

if (!$cmid && !$peerforumid) {
    print_error('missingparameter');
}

if ($cmid) {
    $peerforum = $peerforumvault->get_from_course_module_id($cmid);
    if ($peerforum === null) {
        throw new \moodle_exception('Unable to find peerforum with cmid ' . $cmid);
    }
} else {
    $peerforum = $peerforumvault->get_from_id($peerforumid);
    if (empty($peerforum)) {
        throw new \moodle_exception('Unable to find peerforum with id ' . $peerforumid);
    }
}

$capabilitymanager = $managerfactory->get_capability_manager($peerforum);
$PAGE->set_url('/mod/peerforum/trainingpages.php', array(
        'pf' => $peerforumid,
        'id' => $cmid,
));

$course = $peerforum->get_course_record();
$coursemodule = $peerforum->get_course_module_record();
$cm = \cm_info::create($coursemodule);

require_course_login($course, true, $cm);

if (!$capabilitymanager->can_edit_training_pages($USER)) {
    print_error('cannoteditposts', 'peerforum');
}

$strname = get_string('name');
$strex = 'Exercises';
$strdisc = 'Discussion';
$stredit = 'Edit';

$strparentname = 'Training pages manager';
$PAGE->navbar->add($strparentname);

$PAGE->set_title("{$course->shortname}: {$strparentname}");
$PAGE->set_heading($peerforum->get_name());

echo $OUTPUT->header();
echo $OUTPUT->heading($strparentname, 2);

$button = new single_button($urlfactory->get_training_new_url($peerforum), 'Add a new training page', 'get');

$button->primary = true;
$button->class = 'py-3';
echo $OUTPUT->render($button);

$trainingpages = $trainingpagesvault->get_from_peerforum_id($peerforum->get_id());
$discussions = $discussionsvault->get_all_discussions_in_peerforum($peerforum);
if (!$trainingpages) {
    $msg = get_string('nopeergrades', 'peerforum');
    echo html_writer::tag('div', $msg, array('class' => 'mdl-align'));
} else {

    $table = new html_table;
    $table->caption = "Existent pages";
    $table->attributes['class'] = 'generalbox table';
    $table->head = array(
            $strname,
            $strex,
            $strdisc,
            '',
    );
    $table->colclasses = array('', 'text-center align-middle', 'text-center align-middle', '');
    $table->data = array();

    foreach ($trainingpages as $trainingpage) {
        $row = new html_table_row();
        $row->attributes['class'] = 'peergradeitemheader';

        $link = html_writer::link($urlfactory->get_training_url($trainingpage), $trainingpage->name);
        $row->cells[] = new html_table_cell($link);
        current($row->cells)->header = true;

        $row->cells[] = $trainingpage->exercises;
        $row->cells[] = !empty($discussions[$trainingpage->discussion]) ? $discussions[$trainingpage->discussion]->get_name() : '-';
        $row->cells[] = html_writer::link($urlfactory->get_training_edit_url($trainingpage), $stredit);
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
