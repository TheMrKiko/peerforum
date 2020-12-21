<?php

/**
 * @package    core_peergrade
 * @copyright  2016 Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot . '/peergrade/lib.php');

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

global $PAGE;
$context = optional_param('context', null, PARAM_INT);
$PAGE->set_context($context);

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$display = required_param('display', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);
$postauthor = required_param('postauthor', PARAM_INT);

$PAGE->set_url('/peergrading/studentassign.php',
        array('userid' => $userid, 'courseid' => $courseid, 'display' => $display, 'itemid' => $itemid,
                'postauthor' => $postauthor));

require_login($courseid);

//Select student to assign post to peergrade
if (isset($_POST["assignstd" . $itemid])) {

    $student_id = $_POST['menustds' . $itemid];

    global $DB;
    $all_students = null;

    if ($student_id != UNSET_STUDENT) {
        $student_id = str_replace(".", "", $student_id);
    }

    if ($student_id == UNSET_STUDENT) {

        //Get all available students & relevant info
        $all_students = get_students_can_be_assigned_w_ptpg($courseid, $itemid, $postauthor);

        foreach ($all_students as $a => $value) {

            //peergradesdone
            $peergradedone = $all_students[$a]->postspeergradedone;
            $array_done = explode(";", $peergradedone);
            $numpeerdone = (count($array_done) - 1);

            //peergradestodo
            $topeergrade = $all_students[$a]->numpoststopeergrade;

            //sum both values
            $sum = $topeergrade + $numpeerdone;

            //update sum record
            $user = $all_students[$a]->userid;
            $info = $DB->get_record("peerforum_peergrade_users", array('iduser' => $user));
            $data = new stdClass();
            $data->id = $info->id;
            $data->gradesum = $sum;
            $DB->update_record("peerforum_peergrade_users", $data);
        }

        $all_students = get_students_can_be_assigned_w_ptpg($courseid, $itemid, $postauthor);

        // Asc sort
        usort($all_students, function($a, $b) {
            return $a->gradesum > $b->gradesum;
        });

        $peerforum_data = $DB->get_record("peerforum", array('course' => $courseid));

        if ($all_students != null) {
            //Check if advanced attribution is defined, if not just jump this part
            if ($peerforum_data->threaded_grading) {

                //Get the discussion topic of this submission
                $discussion_info = $DB->get_record("peerforum_posts", array('id' => $itemid));
                $discussion = $discussion_info->discussion;

                $post_info = $DB->get_record("peerforum_discussions", array('id' => $discussion));
                $topic_name = $post_info->name;

                //Check if the type is 1 or 2
                $list = $DB->get_record("peerforum_peergrade_subject", array('name' => $topic_name));

                if ($list->type == 1) {
                    //Loop through the (already ordered) given list until it finds a studetns which the grading type is 2
                    foreach ($all_students as $i => $value) {

                        $thisstudenttopics = $all_students[$i]->topicsassigned;
                        $listtopics = explode(';', $thisstudenttopics);

                        foreach ($listtopics as $j => $value) {
                            if ($listtopics[$j] == $topic_name && $all_students[$i]->peergradetype == 1) {
                                $studentChoosen = ($all_students[$i]);
                                break 2;
                            }
                        }
                    }
                } else { //tipo 2
                    //Loop through the (already ordered) given list until it finds a studetns which the grading type is 2
                    foreach ($all_students as $k => $value) {
                        if ($all_students[$k]->peergradetype == 2) {
                            $studentChoosen = ($all_students[$k]);
                            break;
                        }
                    }
                }
            } else {
                $studentChoosen = ($all_students[0]);
            }

            $student_id = $studentChoosen->userid; //userid
        }
    }

    if ($student_id != 0) {

        $peergraders = $DB->get_record('peerforum_posts', array('id' => $itemid))->peergraders;
        $peers = explode(';', $peergraders);

        $peers = array_filter($peers);
        array_push($peers, $student_id);
        $peers = array_filter($peers);

        $peers_updated = implode(';', $peers);

        $data = new stdClass();
        $data->id = $itemid;
        $data->peergraders = $peers_updated;

        $DB->update_record("peerforum_posts", $data);
        $peers_info = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $student_id));

        if (!empty($peers_info)) {
            $poststograde = $peers_info->poststopeergrade;

            $numpostsassigned = $peers_info->numpostsassigned;
            $numpoststopeergrade = $peers_info->numpoststopeergrade;
            $numposts = $numpostsassigned + 1;
            $numgrade = $numpoststopeergrade + 1;

            $posts = explode(';', $poststograde);

            adjust_database();

            $posts = array_filter($posts);
            array_push($posts, $itemid);
            $posts = array_filter($posts);

            $posts_updated = array();
            $posts_updated = implode(';', $posts);

            $data2 = new stdClass();
            $data2->id = $peers_info->id;
            $data2->poststopeergrade = $posts_updated;
            $data2->numpostsassigned = $numposts;
            $data2->numpoststopeergrade = $numgrade;
            $DB->update_record("peerforum_peergrade_users", $data2);

            $time = new stdclass();
            $time->courseid = $courseid;
            $time->postid = $itemid;
            $time->userid = $student_id;
            $time->timeassigned = time();
            $time->timemodified = time();

            $DB->insert_record("peerforum_time_assigned", $time);

        } else {
            $data2 = new stdClass();
            $data2->courseid = $courseid;
            $data2->iduser = $student_id;
            $data2->poststopeergrade = $itemid;
            $data2->postspeergradedone = null;
            $data2->postsblocked = null;
            $data2->postsexpired = null;
            $data2->numpostsassigned = 0;
            $data2->numpoststopeergrade = 0;

            $time = new stdclass();
            $time->courseid = $courseid;
            $time->postid = $itemid;
            $time->userid = $student_id;
            $time->timeassigned = time();
            $time->timemodified = time();

            $DB->insert_record("peerforum_time_assigned", $time);

            $DB->insert_record("peerforum_peergrade_users", $data2);
        }

        //Finally notify user
        send_peergrade_notification($student_id);
    }

}

$returnurl = new moodle_url('/peergrading/index.php', array('userid' => $userid, 'courseid' => $courseid, 'display' => $display));

redirect($returnurl);
?>
