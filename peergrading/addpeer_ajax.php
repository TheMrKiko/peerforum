<?php
/**
 * This page receives ajax peergrade submissions
 *
 * @package    mod
 * @subpackage peerforum
 * @copyright  2016 Jessica Ribeiro
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../config.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');

$PAGE->set_url('/peergrading/addpeer.php', array());

require_login(null, false, null, false, true);

$peerid = required_param('peerid', PARAM_INT);
//$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$peerfav = required_param('peerfav', PARAM_INT); //1 if fav, 0 if unfav

if ($peerid != UNSET_STUDENT || $peerid != UNSET_STUDENT_SELECT) {


    $relationship_info = $DB->get_record('peerforum_relationships', array('iduser' => $userid));

    if ($peerfav) {

        $current_fav = $relationship_info->peersfav;
        $current_fav = explode(";", $current_fav);
        array_push($peerid, $current_fav);

        $newfav = implode(";", $current_fav);

        $data = new stdclass();
        $data->id = $relationship_info->id;
        $data->peerfav = $newfav;

        $DB->update_record("peerforum_relationships", $data);
    } else {

        $current_unfav = $relationship_info->peersunfav;
        $current_unfav = explode(";", $current_unfav);
        array_push($peerid, $current_fav);

        $newunfav = implode(";", $current_unfav);

        $data = new stdclass();
        $data->courseid = $relationship_info->id;
        $data->peerfav = $newunfav;

        $DB->update_record("peerforum_relationships", $data);
    }

}

$peersnames = null;
foreach ($peernames as $y => $value) {
    $peersnames .= $peernames[$y];
}

$students_assigned = get_students_can_be_assigned_id($itemid);

$students_assign = $students_assigned;

$students = get_students_name($students_assign);

$selectstudentrandom = get_string('selectstudentrandom', 'peerforum');
$assignstudentstr = get_string('assignstudentstr', 'peerforum');

$studentsarray = array(UNSET_STUDENT_SELECT => $assignstudentstr, UNSET_STUDENT => $selectstudentrandom) + $students;

$studentattrs = array('class' => 'menuassignpeer studentinput', 'id' => 'menuassignpeer' . $itemid);
$students_html =
        html_writer::select($studentsarray, 'menuassignpeer' . $itemid, $studentsarray[UNSET_STUDENT_SELECT], false, $studentattrs);

$students_assigned_rmv = get_students_assigned($courseid, $itemid);
$students_rmv = get_students_name($students_assigned_rmv);

$removestudentstr = get_string('removestudent', 'peerforum');
$studentsarray_rmv = array(UNSET_STUDENT_SELECT => $removestudentstr, UNSET_STUDENT => $selectstudentrandom) + $students_rmv;

$studentattrs_rmv = array('class' => 'menuremovepeer studentinput', 'id' => 'menuremovepeer' . $itemid);
$students_rmv_html =
        html_writer::select($studentsarray_rmv, 'menuremovepeer' . $itemid, $studentsarray_rmv[UNSET_STUDENT_SELECT], false,
                $studentattrs_rmv);

$result = true;

echo json_encode(array('result' => $result, 'peerid' => $peerid, 'peerfav' => $peerfav, 'userid' => $userid,
        'addpeerinfo' => $addpeerhtml));
