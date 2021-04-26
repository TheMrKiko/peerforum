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

/**
 * @param moodle_url $url
 * @param bool $isprofessor
 * @param bool $isself
 * @return array
 */
function get_peerblock_tabs($url, $isprofessor = false, $isself = true) {
    // Strings.
    $poststopeergrade = get_string('poststopeergrade', 'block_peerblock');
    $postspeergraded = get_string('postspeergraded', 'block_peerblock');
    $postsexpired = get_string('postsexpired', 'block_peerblock');
    $viewpeergrades = get_string('viewpeergrades', 'block_peerblock');
    $manageposts = get_string('manageposts', 'block_peerblock');
    $postsassigned = get_string('postsassigned', 'block_peerblock');
    $manageconflicts = get_string('manageconflicts', 'block_peerblock');
    $managegradersposts = get_string('managegraders_posts', 'block_peerblock');
    $viewgradersstats = get_string('viewgradersstats', 'block_peerblock');
    $viewgradesgraph = get_string('viewgradesgraph', 'block_peerblock');
    $managerelations = get_string('managerelations', 'block_peerblock');
    $threadingstats = get_string('threadingstats', 'block_peerblock');
    $peerranking = get_string('peer_ranking', 'block_peerblock');
    $managetraining = get_string('managetraining', 'block_peerblock');

    $display = $url->get_param('display') ?: MANAGEPOSTS_MODE_SEEALL;
    $params = array(
            'userid' => $url->get_param('userid') ?: 0,
            'courseid' => $url->get_param('courseid') ?: 0,
    );

    $row[] = new tabobject('manageposts', new moodle_url('/blocks/peerblock/summary.php',
                    $params + array('display' => $display, 'expanded' => true, )), $postsassigned);
    if ($isprofessor) {
        $row[] = new tabobject('peergrades',
                new moodle_url('/blocks/peerblock/short.php',
                        $params + array('display' => $display)), $viewpeergrades);
    }
    if (!$isprofessor && $isself) {
        $row[] = new tabobject('peerranking',
                new moodle_url('/blocks/peerblock/rankings.php',
                        $params), $peerranking);
    }
    if ($isprofessor) {
        $row[] = new tabobject('viewgradersstats',
                new moodle_url('/blocks/peerblock/user.php',
                        $params), $viewgradersstats);
    }
    if ($isprofessor) {
        $row[] = new tabobject('viewgradesgraph',
                new moodle_url('/blocks/peerblock/graph.php',
                        $params), $viewgradesgraph);
    }
    if ($isprofessor) {
        $row[] = new tabobject('managerelations',
                new moodle_url('/blocks/peerblock/relationships.php',
                        $params), $managerelations);
    }
    return $row;
}

/**
 * @return array
 */
function get_peerblock_select_options() {
    return array(
            MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
    );
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

function set_peergradepanel_page($courseid, $userid, $url, $tab, $onlyforprofs, $onlyforself, $stdscanviewothers = false) {
    global $DB, $CFG, $PAGE, $USER, $OUTPUT;

    $PAGE->set_url($url);

    if ($courseid == SITEID) {
        print_error('invalidcourseid');
    }

    require_login($courseid, false);

    $coursecontext = context_course::instance($courseid, MUST_EXIST);
    $isprofessor = has_capability('mod/peerforum:professorpeergrade', $coursecontext, $USER);
    $iscurrentuser = ($USER->id == $userid);

    // Check if the person can be here.
    if (($onlyforprofs && !$isprofessor) || ($onlyforself && !$iscurrentuser) ||
            (!$stdscanviewothers && !$isprofessor && !$iscurrentuser) || isguestuser()) {
        print_error('error');
    }

    $course = get_course($courseid, false);

    if ($userid) {
        require_once($CFG->dirroot . '/user/lib.php');

        $user = !$iscurrentuser ? $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST) : $USER;
        $usercontext = context_user::instance($userid, MUST_EXIST);

        // Check if the requested user is the guest user.
        if (isguestuser($user)) {
            // May as well just bail aggressively here.
            print_error('invaliduserid');
        }

        if (!user_can_view_profile($user, $course, $usercontext)) {
            print_error('cannotviewusersposts', 'peerforum');
        }

        // Make sure the user has not been deleted.
        if ($user->deleted) {
            $PAGE->set_title(get_string('userdeleted'));
            $PAGE->set_context(context_system::instance());
            echo $OUTPUT->header();
            echo $OUTPUT->heading($PAGE->title);
            echo $OUTPUT->footer();
            die;
        }
    }

    $coursefullname = format_string($course->fullname, true, array('context' => $coursecontext));

    $a = new stdClass;
    $a->coursename = $coursefullname;
    if ($userid) {
        $userfullname = fullname($user);
        $a->fullname = $userfullname;
        $pagetitle = get_string('pgbyuserincourse', 'block_peerblock', $a);
    } else {
        $pagetitle = get_string('pgincourse', 'block_peerblock', $a);
    }

    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($coursefullname);

    if ($userid) {
        $PAGE->navigation->extend_for_user($user);
        $usernode = $PAGE->navigation->find('user' . $userid, null);
        $usernode->make_active();

        if ($isprofessor || $stdscanviewothers) {
            $nuurl = new moodle_url($url, array('userid' => 0));
            $PAGE->set_button(html_writer::link($nuurl, 'Clear selected user'));
        }
    }

    $PAGE->navbar->add('Peer grading');

    echo $OUTPUT->header();

    if ($userid) {
        $userheading = array(
                'heading' => fullname($user),
                'user' => $user,
                'usercontext' => $usercontext
        );
        echo $OUTPUT->context_header($userheading, 2);
    }

    $row = get_peerblock_tabs($url, $isprofessor, $iscurrentuser);
    echo $OUTPUT->tabtree($row, $tab);
}
