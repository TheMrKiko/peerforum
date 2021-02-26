<?php

/**
 * @package    block
 * @subpackage peerblock
 * @copyright  2016 Jessica Ribeiro
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

function get_peerblock_tabs(array $params = array()) {
    $postsassigned = get_string('postsassigned', 'block_peerblock');
    $peerranking = get_string('peer_ranking', 'block_peerblock');

    $row[] = new tabobject('manageposts', new moodle_url('/blocks/peerblock/summary.php',
                    $params + array('display' => MANAGEPOSTS_MODE_SEEALL)), $postsassigned);
    $row[] = new tabobject('peerranking',
            new moodle_url('/blocks/peerblock/rankings.php',
                    $params), $peerranking);
    return $row;
}

/**
 * @param $fromform
 * @param $mform
 * @param $nominationsfull
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function peerblock_edit_rankings($data, $mform) {
    global $DB;

    $rankings = \mod_peerforum\local\vaults\training_page::turn_outside_in($data->rankings, array('userid'));

    foreach ($rankings as $ranking) {
        if (!$ranking->id) {
            continue;
        }
        $DB->set_field('peerforum_relationship_rank', 'ranking', $ranking->ranking, array('id' => $ranking->id));
    }

    return true;
}

/**
 * Return the number of posts a user has to grade in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return int number of posts to grade.
 * @global object
 */
function get_num_posts_to_grade($userid, $courseid) {
    global $DB;

    //get all the posts to peergrade
    $sql = "SELECT p.iduser, p.poststopeergrade
            FROM {peerforum_peergrade_users} p
            WHERE p.iduser = $userid AND p.courseid = $courseid";

    $all_posts = $DB->get_records_sql($sql);

    $posts_to_peergrade = array();

    if (!empty($all_posts[$userid]->poststopeergrade)) {
        $posts_to_peergrade = explode(";", ($all_posts[$userid]->poststopeergrade));
        $posts_to_peergrade = array_filter($posts_to_peergrade);
    }

    $num_to_grade = count($posts_to_peergrade);

    return $num_to_grade;
}

/**
 * Return the time of the oldest post in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return DateTime time of the oldest post.
 * @global object
 */
function get_time_old_post($userid, $courseid) {
    global $DB;

    //get all the posts to peergrade
    $sql = "SELECT p.iduser, p.poststopeergrade
            FROM {peerforum_peergrade_users} p
            WHERE p.iduser = $userid AND p.courseid = $courseid";

    $all_posts = $DB->get_records_sql($sql);

    $posts = array();
    if (!empty($all_posts[$userid]->poststopeergrade)) {
        $posts = explode(";", ($all_posts[$userid]->poststopeergrade));
        $posts = array_filter($posts);
        $first_key = key($posts);

        $old_post = $posts[$first_key];

        $time = get_time_expire($old_post, $userid);

        return $time;
    } else {
        return null;
    }
}

/**
 * Return the number of peers a student has not ranked yet
 *
 * @param int $userid
 * @return int number of peers unranked.
 * @global object
 */
function get_num_peers_to_rank($userid, $courseid) {
    global $DB;

    $students_to_rank = 0;
    $student = $DB->get_record("peerforum_relationships", array('iduser' => $userid));

    if (!empty($student)) {
        $peers_ranked = $student->studentsranked;
        $array_ranks = explode(";", $peers_ranked);

        $a = $DB->get_record("peerforum_peergrade_users", array('iduser' => $userid));
        if (!empty($a)) { //avoid notices in case of non student
            $students_graded = $a->postspeergradedone;
            if (!empty($students_graded)) {
                $array_students_graded = explode(";", $students_graded);
                $rankableid = array(); //array to avoid n posts from the same person count as n ranks

                for ($i = 0; $i < count($array_students_graded); $i++) {
                    $peerpost = $DB->get_record("peerforum_posts", array('id' => $array_students_graded[$i]));
                    $peerid = $peerpost->userid;
                    if (!in_array($peerid, $array_ranks) && !in_array($peerid, $rankableid)) {
                        array_push($rankableid, $peerid);
                        $students_to_rank++;
                    }
                }
            }
        }
    }

    return $students_to_rank;
}

/**
 * Return the number of posts with peergrading in effect
 *
 * @param int $courseid
 * @return int total number of posts to peergrade.
 * @global object
 */
function get_active_peergrading_posts($courseid) {
    global $DB;

    $posts = get_all_posts_info($courseid);
    $active_posts = get_posts_not_expired($posts);

    return count($active_posts);
}

/**
 * Return the number of posts expiring soon
 *
 * @param int $courseid
 * @return int number of posts expiring.
 * @global object
 */
function get_posts_about_to_expire($courseid, $peerforumid) {
    global $DB;

    $posts_expiring = 0;

    $posts = get_all_posts_info($courseid);
    $active_posts = get_posts_not_expired($posts);
    $peerforum = $DB->get_record("peerforum", array('course' => $peerforumid));

    foreach ($active_posts as $key => $value) {

        $peergraders = $active_posts[$key]->peergraders;
        $expiring_peers = 0;
        if (!empty($peergraders)) {
            foreach ($peergraders as $i => $value) {
                $exp_peer = verify_post_almost_expired($active_posts[$key]->postid, $peerforum, $peergraders[$i]->id, $courseid);
                if ($exp_peer->almost_expired) {
                    $expiring_peers++;
                } else { //if at least one is not having the post expiring soon, we wont count the post, no need to check other students left
                    break;
                }
            }
        }
        if ($expiring_peers == count($peergraders)) {
            $posts_expiring++;
        }
    }

    return $posts_expiring;
}
