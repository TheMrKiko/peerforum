<?php
//See this peergrade in detail

require_once('../config.php');
if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

$itemid = required_param('itemid', PARAM_INT);
$peergraderid = required_param('peergraderid', PARAM_INT);

$url = new moodle_url('/peergrading/peergrade.php', array('itemid' => $itemid,
        'peergraderid' => $peergraderid));

require_login();
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);

$strtime = get_string('time');
$strpeergrade = get_string('peergrade', 'peerforum');
$strfeedback = get_string('feedback');

echo $OUTPUT->header();

global $DB;

//Retriving information
$peergrade = $DB->get_record("peerforum_peergrade", array('itemid' => $itemid, 'userid' => $peergraderid));

if (!$peergrade) {
    $msg = get_string('nopeergrades', 'peerforum');
    echo html_writer::tag('div', $msg, array('class' => 'mdl-align'));
} else {

    $peergradescalemenu = make_grades_menu($peergrade->peergradescaleid);
    $table = new html_table;
    $table->cellpadding = 3;
    $table->cellspacing = 3;
    $table->attributes['class'] = 'generalbox peergradetable';
    $table->head = array(
            '',
            html_writer::span($strpeergrade),
            html_writer::span($strfeedback),
            html_writer::span($strtime)
    );
    $table->colclasses = array('', 'peergrade', 'feedback', 'time');
    $table->data = array();

    // If the scale was changed after peergrades were submitted some peergrades may have a value above the current maximum.
    // We can't just do count($scalemenu) - 1 as custom scales start at index 1, not 0.
    $maxpeergrade = max(array_keys($peergradescalemenu));

    $row = new html_table_row();
    $row->attributes['class'] = 'peergradeitemheader';

    if ($peergrade->peergrade > $maxpeergrade) {
        $peergrade->peergrade = $maxpeergrade;
    }

    $row->cells[] = '';
    $row->cells[] = $peergradescalemenu[round($peergrade->peergrade)];
    $row->cells[] = $peergrade->feedback;
    $row->cells[] = userdate($peergrade->timemodified);
    $table->data[] = $row;

    echo html_writer::table($table);
}

echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();
