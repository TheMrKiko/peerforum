<?php

define('AJAX_SCRIPT', true);
require_once('../config.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/peergrading/lib.php');

$PAGE->set_url('/peergrading/removepeer.php', array());

require_login(null, false, null, false, true);

$userid = required_param('userid', PARAM_INT);
$peerid = required_param('peerid', PARAM_INT);

global $DB, $COURSE;

if ($peerid != UNSET_STUDENT) {
    //Update DB!
} else {
    print_error("No peer id sent!");
}

/*
update_less_peerforum_users_assigned($itemid, $peerid);

$students_assigned = get_students_can_be_assigned_id($itemid);

$students = get_students_name($students_assigned);

$selectstudentrandom = get_string('selectstudentrandom', 'peerforum');
$assignstudentstr = get_string('assignstudentstr', 'peerforum');

$studentsarray = array(UNSET_STUDENT_SELECT => $assignstudentstr, UNSET_STUDENT => $selectstudentrandom) + $students;

$studentattrs = array('class'=>'menuassignpeer studentinput','id'=>'menuassignpeer'.$itemid);
$students_html = html_writer::select($studentsarray, 'menuassignpeer'.$itemid, $studentsarray[UNSET_STUDENT_SELECT], false, $studentattrs);

$students_assigned_rmv = get_students_assigned($courseid, $itemid);
$students_rmv = get_students_name($students_assigned_rmv);

$removestudentstr = get_string('removestudent', 'peerforum');
$studentsarray_rmv = array(UNSET_STUDENT_SELECT => $removestudentstr, UNSET_STUDENT => $selectstudentrandom) + $students_rmv;

$studentattrs_rmv = array('class'=>'menuremovepeer studentinput','id'=>'menuremovepeer'.$itemid);
$students_rmv_html = html_writer::select($studentsarray_rmv, 'menuremovepeer'.$itemid, $studentsarray_rmv[UNSET_STUDENT_SELECT], false, $studentattrs_rmv);

$peers_topeergrade = get_post_peergraders($itemid);

$peers_assigned = array();
$peernames = array();
$peerids = null;
$post_grades = $DB->get_records('peerforum_peergrade', array('itemid' => $itemid));

foreach ($peers_topeergrade as $key => $value) {
    if(in_array($peers_topeergrade[$key], $post_grades->userid)){
        $color = '#339966';
    } else {
        $color = '#cc3300';
    }
    $peer_name = get_student_name($peers_topeergrade[$key]);

    array_push($peernames, html_writer::tag('span', $peer_name, array('id' => 'peersassigned'.$itemid, 'style'=> 'color:'.$color.';')));
    array_push($peernames, html_writer::tag('span',  '; ', array('style'=> 'color:grey;')));
}

$show_assignparent = 0;

$peersnames = null;
foreach ($peernames as $y => $value) {
    $peersnames .= $peernames[$y];
}

if($peernames == null){
    array_push($peernames, html_writer::tag('span', ' None', array('id' => 'peersassigned'.$itemid, 'style'=> 'color: grey;')));
    $peersnames .= $peernames[0];
    $show_assignparent = 1;
}*/

$result = true;

echo json_encode(array('result' => $result, 'peerid' => $peerid, 'userid' => $userid));
