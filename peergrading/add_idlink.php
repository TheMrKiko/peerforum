<?php

require_once('../config.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/peergrading/lib.php');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$display = required_param('display', PARAM_INT);

$PAGE->set_url('/peergrading/add_idlink.php', array());

require_login($courseid);

if (isset($_POST['idvalue'])) {

    $link_id = $_POST['idvalue'];
    $id = $_GET['id'];

    global $DB;

    if (!empty($info) || !empty($id)) {

        $data = new stdClass();
        $data->id = $id;
        $data->idlink = $link_id;
        $DB->update_record("peerforum_peergrade_subject", $data);
    }

}

$returnurl = new moodle_url('/peergrading/index.php', array('userid' => $userid, 'courseid' => $courseid, 'display' => $display));
redirect($returnurl);
