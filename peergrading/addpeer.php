<?php

require_once('../config.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/peergrading/lib.php');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$display = required_param('display', PARAM_INT);

$PAGE->set_url('/peergrading/addpeer.php', array());

require_login($courseid);

$user = $_GET['user']; //User that responded to questionnaire
$status = $_GET['status'];

if (isset($_POST["assignnoms"])) {

    $student_id = (int) $_POST['menunoms'];

    global $DB;

    $info = $DB->get_record('peerforum_relationships', array('courseid' => $courseid, 'iduser' => $user));

    if (!empty($info) || !empty($student_id)) {

        //Add new entry to fav
        if ($status == 0) {

            $array_peers = explode(";", $info->peersfav);

            if (!array_search($student_id, $array_peers)) {
                array_push($array_peers, $student_id);
                $array_peers = implode(";", $array_peers);

                $data = new stdClass();
                $data->id = $info->id;
                $data->peersfav = $array_peers;
                $DB->update_record("peerforum_relationships", $data);
            }

        } //UnFav
        else if ($status == 1) {

            $array_peers = explode(";", $info->peersunfav);

            if (!array_search($student_id, $array_peers)) {
                array_push($array_peers, $student_id);

                $array_peers = implode(";", $array_peers);

                $data = new stdClass();
                $data->id = $info->id;
                $data->peersunfav = $array_peers;

                $DB->update_record("peerforum_relationships", $data);
            }
        }
    }
}

$returnurl = new moodle_url('/peergrading/index.php', array('userid' => $userid, 'courseid' => $courseid, 'display' => $display));
redirect($returnurl);
