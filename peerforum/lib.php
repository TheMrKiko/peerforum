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
 * Custom functions that allow peergrading in PeerForums
 * 
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once(__DIR__ . '/deprecatedlib.php');
require_once($CFG->libdir . '/filelib.php');
// require_once($CFG->libdir . '/eventslib.php');
/*require_once($CFG->dirroot . '/peergrade/lib.php');
require_once($CFG->dirroot . '/ratingpeer/lib.php');*/

/// CONSTANTS ///////////////////////////////////////////////////////////

define('PEERFORUM_MODE_FLATOLDEST', 1);
define('PEERFORUM_MODE_FLATNEWEST', -1);
define('PEERFORUM_MODE_THREADED', 2);
define('PEERFORUM_MODE_NESTED', 3);
define('PEERFORUM_MODE_NESTED_V2', 4);

define('PEERFORUM_CHOOSESUBSCRIBE', 0);
define('PEERFORUM_FORCESUBSCRIBE', 1);
define('PEERFORUM_INITIALSUBSCRIBE', 2);
define('PEERFORUM_DISALLOWSUBSCRIBE', 3);

/**
 * PEERFORUM_TRACKING_OFF - Tracking is not available for this peerforum.
 */
define('PEERFORUM_TRACKING_OFF', 0);

/**
 * PEERFORUM_TRACKING_OPTIONAL - Tracking is based on user preference.
 */
define('PEERFORUM_TRACKING_OPTIONAL', 1);

/**
 * PEERFORUM_TRACKING_FORCED - Tracking is on, regardless of user setting.
 * Treated as PEERFORUM_TRACKING_OPTIONAL if $CFG->peerforum_allowforcedreadtracking is off.
 */
define('PEERFORUM_TRACKING_FORCED', 2);

define('PEERFORUM_MAILED_PENDING', 0);
define('PEERFORUM_MAILED_SUCCESS', 1);
define('PEERFORUM_MAILED_ERROR', 2);

if (!defined('PEERFORUM_CRON_USER_CACHE')) {
    /** Defines how many full user records are cached in peerforum cron. */
    define('PEERFORUM_CRON_USER_CACHE', 5000);
}

/**
 * PEERFORUM_POSTS_ALL_USER_GROUPS - All the posts in groups where the user is enrolled.
 */
define('PEERFORUM_POSTS_ALL_USER_GROUPS', -2);

define('PEERFORUM_DISCUSSION_PINNED', 1);
define('PEERFORUM_DISCUSSION_UNPINNED', 0);

//---------- New functions of PeerForum ----------//

define('PEERFORUM_MODE_PROFESSOR', 1);
define('PEERFORUM_MODE_STUDENT', 2);
define('PEERFORUM_MODE_PROFESSORSTUDENT', 3);

define('MANAGEPOSTS_MODE_SEEALL', 1);
define('MANAGEPOSTS_MODE_SEEGRADED', 2);
define('MANAGEPOSTS_MODE_SEENOTGRADED', 3);
define('MANAGEPOSTS_MODE_SEENOTEXPIRED', 4);
define('MANAGEPOSTS_MODE_SEEEXPIRED', 5);

define('MANAGEGRADERS_MODE_SEEALL', 1);
define('MANAGEGRADERS_MODE_SEENOTEXPIRED', 2);
define('MANAGEGRADERS_MODE_SEEEXPIRED', 3);
define('MANAGEGRADERS_MODE_SEENOTGRADED', 4);
define('MANAGEGRADERS_MODE_SEEGRADED', 5);

define('VIEWPEERGRADES_MODE_SEEALL', 1);
define('VIEWPEERGRADES_MODE_SEEWARNINGS', 2);
define('VIEWPEERGRADES_MODE_SEEOUTLIERS', 3);

define('RELATIONSHIPS_MODE_NOMINATIONS', 1);
define('RELATIONSHIPS_MODE_RANKINGS', 2);

/**
 * Updates the database
 *
 * @return
 * @global object
 * @global object
 */
function adjust_database() {
    global $DB, $COURSE;

    $all_enrolled = get_all_enroled_id($COURSE->id);

    $all_users = $DB->get_records('peerforum_peergrade_users', array('courseid' => $COURSE->id));

    foreach ($all_users as $i => $value) {
        $id_user = $all_users[$i]->iduser;

        if (!in_array($id_user, $all_enrolled)) {
            $DB->delete_records('peerforum_peergrade_users', array('courseid' => $COURSE->id, 'iduser' => $id_user));
        }
    }

    $posts_users = $DB->get_records('peerforum_peergrade_users');

    foreach ($posts_users as $key => $value) {

        $poststopeergrade = $posts_users[$key]->poststopeergrade;
        $postspeergradedone = $posts_users[$key]->postspeergradedone;
        $postsblocked = $posts_users[$key]->postsblocked;
        $postsexpired = $posts_users[$key]->postsexpired;

        $numpostsassigned = $posts_users[$key]->numpostsassigned;

        $poststopeergrade = explode(';', $poststopeergrade);
        $poststopeergrade = array_filter($poststopeergrade);

        if (!empty($poststopeergrade)) {
            $num_poststopeergrade = count($poststopeergrade);
        } else {
            $num_poststopeergrade = 0;
        }

        $postspeergradedone = explode(';', $postspeergradedone);
        $postspeergradedone = array_filter($postspeergradedone);

        if (!empty($postspeergradedone)) {
            $num_postspeergradedone = count($postspeergradedone);
        } else {
            $num_postspeergradedone = 0;
        }

        $postsblocked = explode(';', $postsblocked);
        $postsblocked = array_filter($postsblocked);

        if (!empty($postsblocked)) {
            $num_postsblocked = count($postsblocked);
        } else {
            $num_postsblocked = 0;
        }

        $postsexpired = explode(';', $postsexpired);
        $postsexpired = array_filter($postsexpired);

        if (!empty($postsexpired)) {
            $num_postsexpired = count($postsexpired);
        } else {
            $num_postsexpired = 0;
        }

        $total_posts = $num_postspeergradedone + $num_poststopeergrade + $num_postsblocked + $num_postsexpired;

        if ($numpostsassigned == 0 && $total_posts != 0) {
            $numpostsassigned = $total_posts;
            $data = new stdClass();
            $data->id = $posts_users[$key]->id;
            $data->numpostsassigned = $numpostsassigned;
            $DB->update_record('peerforum_peergrade_users', $data);

        } else if ($numpostsassigned != $total_posts) {
            $numpostsassigned = $total_posts;
            $data = new stdClass();
            $data->id = $posts_users[$key]->id;
            $data->numpostsassigned = $numpostsassigned;
            $DB->update_record('peerforum_peergrade_users', $data);
        }
    }
}

/**
 * Returns all the users enrolled in a course
 *
 * @param int $courseid
 * @return array of all enrolled users in a course.
 * @global object
 */
function get_all_enroled_id($courseid) {
    global $DB;

    //get all enroled users in course
    $sql = "SELECT DISTINCT u.id
            FROM mdl_course c
            JOIN mdl_context ct ON c.id = ct.instanceid
            JOIN mdl_role_assignments ra ON ra.contextid = ct.id
            JOIN mdl_user u ON u.id = ra.userid
            JOIN mdl_role r ON r.id = ra.roleid
            WHERE c.id = $courseid AND r.id = 5 OR r.id = 3 OR r.id = 2";

    $enroled_sql = $DB->get_records_sql($sql);

    $enroled = array();
    foreach ($enroled_sql as $key => $value) {
        $id = $enroled_sql[$key]->id;
        $enroled[$id] = $id;
    }

    return $enroled;
}

/**
 * Returns all the information about all the posts in a course
 *
 * @param int $courseid
 * @return array with posts' information.
 * @global object
 */
function get_all_posts_info($courseid) {
    global $DB;

    $discussions_course = $DB->get_records('peerforum_discussions', array('course' => $courseid));

    $sql = "SELECT pp.id, pp.userid, pp.subject, pp.peergraders, pp.discussion
            FROM {peerforum_discussions} as pd
            INNER JOIN {peerforum_posts} as pp ON pp.discussion = pd.id
            WHERE pd.course = $courseid AND pp.peergraders !=0 OR pp.peergraders != NULL";

    $posts = $DB->get_records_sql($sql);

    $all_posts = array();

    foreach ($posts as $postid => $values) {

        $post_author_id = $DB->get_record('peerforum_posts', array('id' => $postid))->userid;
        $post_author = $DB->get_record('user', array('id' => $post_author_id));

        $info_post = new stdClass;
        $info_post->postid = $postid;
        $info_post->authorid = $post_author_id;
        $info_post->authorname = $post_author->firstname . ' ' . $post_author->lastname;
        $info_post->subject = $posts[$postid]->subject;
        $info_post->discussion = $posts[$postid]->discussion;
        $peergraders = explode(";", $posts[$postid]->peergraders);
        $info_post->peergraders = array();

        //$info_post->peergraders = array_filter($peergraders);

        foreach ($peergraders as $id => $value) {
            $info_peer = new stdClass();
            $author = $DB->get_record('user', array('id' => $peergraders[$id]));
            $name = $author->firstname . ' ' . $author->lastname;
            $info_peer->id = $peergraders[$id];
            $info_peer->authorname = $name;

            array_push($info_post->peergraders, $info_peer);
        }

        array_push($all_posts, $info_post);
    }
    return $all_posts;
}

/**
 * Returns all the peergrades in a course
 *
 * @param int $courseid
 * @return array with peergrades.
 * @global object
 */
function get_all_peergrades($courseid) {
    global $DB;

    //get all the posts
    $sql = "SELECT p.iduser, p.poststopeergrade, p.postspeergradedone
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    //get all the grades and feedbacks
    $sql2 = "SELECT p.id, p.itemid, p.peergrade, p.userid, p.feedback
            FROM {peerforum_peergrade} p";

    $posts_grades = $DB->get_records_sql($sql2);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $all_posts[$userid] = array();

        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $topeergrade = explode(";", $posts[$userid]->poststopeergrade);
        $info_post->poststopeergrade = array_filter($topeergrade);

        $donepeergrade = explode(";", $posts[$userid]->postspeergradedone);
        $info_post->postspeergradedone = array_filter($donepeergrade);

        if (!empty($info_post->postspeergradedone)) {
            $info_post->postsdonegrade = array();
            $info_post->postsdonefeedback = array();

            foreach ($info_post->postspeergradedone as $i => $value) {
                $postid = $info_post->postspeergradedone[$i];

                foreach ($posts_grades as $d => $value) {
                    if (!empty($posts_grades[$d])) {
                        if ($posts_grades[$d]->itemid == $postid) {
                            $info_post->postsdonegrade[$postid] = $posts_grades[$d]->peergrade;
                            $info_post->postsdonefeedback[$postid] = $posts_grades[$d]->feedback;
                        }
                    }
                }
            }
        }
        array_push($all_posts[$userid], $info_post);
    }
    return $all_posts;
}

/**
 * Returns all the peergrades given in a course
 *
 * @param int $courseid
 * @return array all the peergrades.
 * @global object
 */
function get_all_peergrades_done($courseid) {
    global $DB;

    $info_user = $DB->get_records('peerforum_peergrade_users', array('courseid' => $courseid));

    $data_user = array();

    foreach ($info_user as $key => $value) {
        if ($info_user[$key]->postspeergradedone != null) {
            $userid = $info_user[$key]->iduser;

            $posts = explode(';', $info_user[$key]->postspeergradedone);
            $posts = array_filter($posts);
            $data_posts = array();

            foreach ($posts as $k => $value) {
                $data = new stdClass();
                $postid = $posts[$k];

                $info_post = $DB->get_record('peerforum_peergrade', array('itemid' => $postid, 'userid' => $userid));
                if (!empty($info_post)) {
                    $data->grade = $info_post->peergrade;
                    $data->feedback = $info_post->feedback;
                    $data->time = $info_post->timemodified;

                    $data_posts[$postid] = $data;
                }

                $info_post_blocked = $DB->get_record('peerforum_blockedgrades', array('itemid' => $postid, 'userid' => $userid));
                if (!empty($info_post_blocked)) {
                    $data->grade = $info_post_blocked->peergrade;
                    $data->feedback = $info_post_blocked->feedback;
                    $data->time = $info_post_blocked->timemodified;

                    $data_posts[$postid] = $data;
                }
            }
            $user_db = $DB->get_record('user', array('id' => $userid));
            $info = new stdClass();
            $info->authorname = $user_db->firstname . ' ' . $user_db->lastname;
            $info->posts = $data_posts;
            $data_user[$userid] = $info;
        }
    }
    return $data_user;
}

/**
 * Returns all the grades of all the posts
 *
 * @param int $courseid
 * @return array all the posts with all te.
 * @global object
 */
function get_posts_grades($courseid) {
    global $DB;

    $info = $DB->get_records('peerforum_peergrade');

    $students_enrol = get_students_enroled($courseid);

    $students_enroled = array();
    foreach ($students_enrol as $k => $value) {
        $id = $students_enrol[$k]->id;
        $students_enroled[$id] = $id;
    }

    $array_posts = array();

    foreach ($info as $i => $value) {

        if (in_array($info[$i]->userid, $students_enroled)) {
            $postid = $info[$i]->itemid;

            if (empty($array_posts[$postid])) {
                $array_posts[$postid] = array();
            }

            $post = new stdClass();
            $post->user = $info[$i]->userid;
            $post->postid = $info[$i]->itemid;
            $post->peergrade = $info[$i]->peergrade;
            $post->feedback = $info[$i]->feedback;
            array_push($array_posts[$postid], $post);

        } else {
            continue;
        }
    }
    return $array_posts;
}

/**
 * Returns all the students alphabetically ordered
 *
 * @param int $info_students
 * @return array all the students alphabetically ordered.
 * @global object
 */
function order_students_by_name($info_students) {
    global $DB;

    foreach ($info_students as $key => $value) {
        $iduser = $info_students[$key]->iduser;

        $user_name = $DB->get_record('user', array('id' => $iduser));
        $info_students[$key]->name = $user_name->firstname . ' ' . $user_name->lastname;
    }

    uasort($info_students, function($a, $b) {
        return strcmp($a->name, $b->name);
    });
    return $info_students;
}

/**
 * Returns all the students enrolled in a course
 *
 * @param int $courseid
 * @return array all the students enrolled in a course.
 * @global object
 */
function get_students_enroled($courseid) {
    global $DB;

    //get all enroled users in course
    $sql = "SELECT u.id, c.id as courseid, u.id as userid
            FROM mdl_course c
            JOIN mdl_context ct ON c.id = ct.instanceid
            JOIN mdl_role_assignments ra ON ra.contextid = ct.id
            JOIN mdl_user u ON u.id = ra.userid
            JOIN mdl_role r ON r.id = ra.roleid
            WHERE c.id = $courseid AND r.id = 5";

    $enroled = $DB->get_records_sql($sql);

    return $enroled;
}

/**
 * Returns array of peerforum grade modes
 *
 * @return array
 */
function peerforum_get_final_grade_modes() {
    return array(PEERFORUM_MODE_PROFESSOR => get_string('onlyprofessorpeergrade', 'peerforum'),
            PEERFORUM_MODE_STUDENT => get_string('onlystudentpeergrade', 'peerforum'),
            PEERFORUM_MODE_PROFESSORSTUDENT => get_string('professorstudentpeergrade', 'peerforum'));
}

/**
 * Assign a user to peer grade a post
 *
 * @param stdClass $user
 * @param int $postid
 * @param int $courseid
 * @param int $peerforumid
 * @return array all the peergraders assigned to peergrade the post.
 * @global object
 * @global object
 * @global object
 */
function assign_peergraders($user, $postid, $courseid, $peerforumid) {
    global $CFG, $DB, $PAGE;

    $peerforum = $DB->get_record('peerforum', array('id' => $peerforumid));
    $post = $DB->get_record('peerforum_posts', array('id' => $postid));

    $postauthor = $post->userid;
    $postparent = $post->parent;

    $grandparent = $DB->get_record('peerforum_posts', array('id' => $postparent));

    $this_context = context_course::instance($courseid);
    //Check if the POST AUTHOR is a student
    $isstudent = current(get_user_roles($this_context, $postauthor))->shortname == 'student' ? true : false;

    adjust_database();

    //If this post was made by a student and its not a reply to the root post, auto assign
    if ($isstudent && $postparent != 0 && $peerforum->autoassignreplies) {

        while ($postparent != 0) {
            $parentpeergraders = $DB->get_record('peerforum_posts', array('id' => $postparent))->peergraders;
            if (!empty($parentpeergraders)) {
                $parentpeergraders = explode(";", $parentpeergraders);
                $peers_id = array();

                for ($i = 0; $i < count($parentpeergraders); $i++) {
                    $id = $parentpeergraders[$i];
                    $peers_id[$id] = $id;
                }

                update_peergraders($peers_id, $postid, $courseid, $peerforumid);
                return $peers_id;
            } else {
                $postparent = $DB->get_record('peerforum_posts', array('id' => $postparent))->parent;
            }
        }
    }

    if ($isstudent) {
        $student = $user->id;

        $postdiscussion = $DB->get_record('peerforum_posts', array('id' => $postid))->discussion;
        $peerforumid = $DB->get_record('peerforum_discussions', array('id' => $postdiscussion))->peerforum;
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforumid));

        //$array_peers = get_students_can_be_assigned($courseid, $postid, $student, $peerforumid);
        //Get all available students & relevant info
        $all_students = get_students_can_be_assigned_w_ptpg($courseid, $postid, $postauthor, $peerforumid);

        $max_peers = $peerforum->selectpeergraders;
        $min_peers = $peerforum->minpeergraders;
        $min_std = min(array_keys($all_students));
        $max_std = max(array_keys($all_students));
        $num_enrolled = count($all_students);

        if ($num_enrolled < $max_peers) {
            $max_peers = count($all_students);
        }

        //usort($array_peers, "cmp");

        update_gradingsum();

        $all_students = get_students_can_be_assigned_w_ptpg($courseid, $postid, $postauthor, $peerforumid);

        // Asc sort
        usort($all_students, function($a, $b) {
            return $a->gradesum > $b->gradesum;
        });

        $peers = array();
        $studentChoosen = null;

        $peerforum_data = $DB->get_record("peerforum", array('course' => $courseid));
        if ($all_students != null) {

            for ($y = 0; $y < $max_peers; $y++) {
                //Check if advanced attribution is defined, if not just jump this part
                if ($peerforum_data->threaded_grading) {

                    //Get the discussion topic of this submission
                    $discussion_info = $DB->get_record("peerforum_posts", array('id' => $postid));
                    $discussion = $discussion_info->discussion;

                    $post_info = $DB->get_record("peerforum_discussions", array('id' => $discussion));

                    if ($post_info->type == 1) {
                        //Loop through the (already ordered) given list until it finds a studetns which the grading type is 2
                        foreach ($all_students as $i => $value) {

                            $thisstudenttopics = $all_students[$i]->topicsassigned;
                            $listtopics = explode(';', $thisstudenttopics);

                            foreach ($listtopics as $j => $value) {
                                if ($listtopics[$j] == $post_info->name && $all_students[$i]->peergradetype == 1) {
                                    $studentChoosen = ($all_students[$i]);
                                    array_push($peers, $studentChoosen);
                                    break 2;
                                }
                            }
                        }
                    } else { //tipo 2
                        //Loop through the (already ordered) given list until it finds a studetns which the grading type is 2
                        foreach ($all_students as $k => $value) {
                            if ($all_students[$k]->peergradetype == 2) {
                                $studentChoosen = ($all_students[$k]);
                                array_push($peers, $studentChoosen);
                                break;
                            }
                        }
                    }
                } else {
                    $peers = array_slice($all_students, 0, $max_peers);
                    break;
                }

                $student_id = $studentChoosen->userid;
                foreach ($all_students as $x => $value) {
                    if ($all_students[$x]->userid == $student_id) {
                        unset($all_students[$x]);
                        array_values($all_students);
                    }
                }
            }

            $peers_id = array();

            foreach ($peers as $key => $value) {
                $id = $peers[$key]->userid;
                $peers_id[$id] = $id;
            }

            update_peergraders($peers_id, $postid, $courseid, $peerforumid);

            return $peers_id;
        } else {
            return null;
        }
        // TODO: Delete?
        /*$peers_obj = array_slice($array_peers, 0, $max_peers);

    $peers = array();

    foreach ($peers_obj as $key => $value) {
        $id = $peers_obj[$key]->id;
        $peers[$id] = $id;
    }

    update_peergraders($peers, $postid, $courseid, $peerforumid);

    return $peers;*/
    } else { //end if student
        return null;
    }
}

/**
 * Update the peergraders of a post in the database
 *
 * @param array $array_peergraders
 * @param int $postid
 * @param int $courseid
 * @return
 * @global object
 */
function update_peergraders($array_peergraders, $postid, $courseid) {
    global $DB;

    foreach ($array_peergraders as $i => $value) {
        $userid = $array_peergraders[$i];
        $existing_info = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $userid));

        $data = new stdClass;
        $data->courseid = $courseid;
        $data->iduser = $userid;

        if (empty($existing_info)) {
            $data->poststopeergrade = $postid;
            $data->postspeergradedone = null;
            $data->postsblocked = null;
            $data->postsexpired = null;
            $data->numpostsassigned = 1;

            $DB->insert_record('peerforum_peergrade_users', $data);
        } else {
            $existing_posts = $existing_info->poststopeergrade;

            //$array_posts = array();
            $posts = explode(';', $existing_posts);
            $posts = array_filter($posts);
            $num = $existing_info->numpostsassigned;

            adjust_database();

            array_push($posts, $postid);

            $array_posts = array_filter($posts);
            $array_posts = implode(';', $array_posts);

            $data->poststopeergrade = $array_posts;
            $data->id = $existing_info->id;
            $data->numpostsassigned = $num + 1;

            $DB->update_record('peerforum_peergrade_users', $data);
        }

        $time = new stdclass();
        $time->courseid = $courseid;
        $time->postid = $postid;
        $time->userid = $userid;
        $time->timeassigned = time();
        $time->timemodified = time();

        $DB->insert_record('peerforum_time_assigned', $time);
    }
}

/**
 * Returns the id's of all students that can be assign to a post
 *
 * @param int $postid
 * @return array of students' id that can be assigned
 * @global object
 */
function get_students_can_be_assigned_id($postid) {
    global $DB;

    $post_info = $DB->get_record('peerforum_users_assigned', array('postid' => $postid));
    $not_assigned = $post_info->not_assigned_users;
    $not_assigned = explode(';', $not_assigned);
    $not_assigned = array_filter($not_assigned);

    $students_assign = array();

    foreach ($not_assigned as $key => $value) {
        $id = $not_assigned[$key];
        $students_assign[$id] = $id;
    }

    return $students_assign;
}

/**
 * Inserts a new peergraders in the peerforum_posts database
 *
 * @param int $itemid
 * @param int $peerid
 * @return
 * @global object
 */
function update_peergrader_of_peerforum_posts($itemid, $peerid) {
    global $DB;

    $peergraders = $DB->get_record('peerforum_posts', array('id' => $itemid))->peergraders;

    $peers = explode(';', $peergraders);

    $peers = array_filter($peers);
    array_push($peers, $peerid);
    $peers = array_filter($peers);

    $peers_updated = implode(';', $peers);

    $data = new stdClass();
    $data->id = $itemid;
    $data->peergraders = $peers_updated;

    $DB->update_record("peerforum_posts", $data);
}

/**
 * Remove an assigned user to a post
 *
 * @param int $itemid
 * @param int $peerid
 * @return
 * @global object
 */
function update_less_peerforum_users_assigned($itemid, $peerid) {
    global $DB;

    $postinfo = $DB->get_record('peerforum_users_assigned', array('postid' => $itemid));

    if (empty($postinfo)) {
        $data = new stdClass();
        $data->not_assigned_users = $peerid;
        $data->not_can_grade_users = $peerid;
        $data->postid = $itemid;

        $DB->insert_record('peerforum_users_assigned', $data);
    } else {
        $post_assigned_users = $postinfo->assigned_users;
        $post_assigned_users = explode(';', $post_assigned_users);

        $post_not_assigned_users = $postinfo->not_assigned_users;
        $post_not_assigned_users = explode(';', $post_not_assigned_users);

        //insert
        array_push($post_not_assigned_users, $peerid);
        $post_not_assigned_users = array_filter($post_not_assigned_users);
        $post_not_assigned_users = implode(';', $post_not_assigned_users);

        //remove
        $key = array_search($peerid, $post_assigned_users);
        unset($post_assigned_users[$key]);
        $post_assigned_users = array_filter($post_assigned_users);
        $post_assigned_users = implode(';', $post_assigned_users);

        $data = new stdClass();
        $data->id = $postinfo->id;
        $data->assigned_users = $post_assigned_users;
        $data->not_assigned_users = $post_not_assigned_users;

        $DB->update_record('peerforum_users_assigned', $data);
    }
}

/**
 * Assign a user to a post
 *
 * @param int $itemid
 * @param int $peerid
 * @return
 * @global object
 */
function update_more_peerforum_users_assigned($itemid, $peerid) {
    global $DB;

    $postinfo = $DB->get_record('peerforum_users_assigned', array('postid' => $itemid));
    if (empty($postinfo)) {
        $data = new stdClass();
        $data->assigned_users = $peerid;
        $data->can_grade_users = $peerid;
        $data->postid = $itemid;

        $DB->insert_record('peerforum_users_assigned', $data);
    } else {
        $assigned_users = $postinfo->assigned_users;
        $assigned_users = explode(';', $assigned_users);

        $not_assigned_users = $postinfo->not_assigned_users;
        $not_assigned_users = explode(';', $not_assigned_users);

        //insert
        array_push($assigned_users, $peerid);
        $assigned_users = array_filter($assigned_users);
        $assigned_users = implode(';', $assigned_users);

        //remove
        $key = array_search($peerid, $not_assigned_users);
        unset($not_assigned_users[$key]);
        $not_assigned_users = array_filter($not_assigned_users);
        $not_assigned_users = implode(';', $not_assigned_users);

        $data = new stdClass();
        $data->id = $postinfo->id;
        $data->assigned_users = $assigned_users;
        $data->not_assigned_users = $not_assigned_users;

        $DB->update_record('peerforum_users_assigned', $data);
    }
}

/**
 * Remove a student from peergrading a post
 *
 * @param int $itemid
 * @param int $peerid
 * @return
 * @global object
 */
function remove_peer_from_peerforum_posts($itemid, $peerid) {
    global $DB;

    $peergraders = $DB->get_record('peerforum_posts', array('id' => $itemid))->peergraders;
    $peers = explode(';', $peergraders);
    $peers = array_filter($peers);

    //remove student from post peergraders
    if (in_array($peerid, $peers)) {
        $key = array_search($peerid, $peers);
        unset($peers[$key]);
        $peers = array_filter($peers);
        $peers_updated = implode(';', $peers);

        $data = new stdClass();
        $data->id = $itemid;
        $data->peergraders = $peers_updated;

        $DB->update_record("peerforum_posts", $data);
    }
}

/**
 * Insert a new student in the peergrade_users
 *
 * @param stdClass $peer_info
 * @return
 * @global object
 */
function update_peer_peergrade_users($peer_info) {
    global $DB;

    $posts_blocked = $peer_info->postsblocked;
    $posts_topeergrade = $peer_info->poststopeergrade;

    $blocked = explode(';', $posts_blocked);
    $blocked = array_filter($blocked);

    $topeergrade = explode(';', $posts_topeergrade);
    $topeergrade = array_filter($topeergrade);

    //verify if post is blocked
    if (in_array($itemid, $blocked)) {
        $key = array_search($itemid, $blocked);
        unset($blocked[$key]);
        $blocked = array_filter($blocked);
        $blocked_updated = implode(';', $blocked);

        $data2 = new stdClass();
        $data2->id = $peer_info->id;
        $data2->postsblocked = $blocked_updated;

        $DB->update_record("peerforum_peergrade_users", $data2);
    }

    //verify if post is assigned to peergrade
    if (in_array($itemid, $topeergrade)) {
        $key = array_search($itemid, $topeergrade);
        unset($topeergrade[$key]);
        $topeergrade = array_filter($topeergrade);
        $topeergrade_updated = implode(';', $topeergrade);

        $numpostsassigned = $peer_info->numpostsassigned;
        $numposts = $numpostsassigned - 1;

        $data2 = new stdClass();
        $data2->id = $peer_info->id;
        $data2->poststopeergrade = $topeergrade_updated;
        $data2->numpostsassigned = $numposts;

        $DB->update_record("peerforum_peergrade_users", $data2);
    }

    //remove post from student to peer grade
    $DB->delete_records("peerforum_time_assigned", array('postid' => $itemid, 'userid' => $peer_info->iduser));
}

/**
 * Insert a new peergrader information in the database
 *
 * @param stdClass $peers_info
 * @param int $itemid
 * @param int optional $peerforumid
 * @return
 * @global object
 */
function update_peergrader_of_peergrade_users($peers_info, $itemid) {
    global $DB;

    $poststograde = $peers_info->poststopeergrade;

    $posts = explode(';', $poststograde);

    $posts = array_filter($posts);
    array_push($posts, $itemid);
    $posts = array_filter($posts);

    $posts_updated = array();
    $posts_updated = implode(';', $posts);

    $numpostsassigned = $peers_info->numpostsassigned;

    $numposts = $numpostsassigned + 1;

    $data = new stdClass();
    $data->id = $peers_info->id;
    $data->poststopeergrade = $posts_updated;
    $data->numpostsassigned = $numposts;

    $DB->update_record("peerforum_peergrade_users", $data);
}

/**
 * Returns the students that can be assigned to peer grade a post
 *
 * @param int $courseid
 * @param int $postid
 * @param int $postauthor
 * @param int optional $peerforumid
 * @return array with students that can be assigned.
 * @global object
 */
function get_students_can_be_assigned($courseid, $postid, $postauthor, $peerforumid = null) {
    global $DB;

    $can_be_assigned = array();

    $students = get_students_enroled($courseid);

    adjust_database();

    // Verify students that was already assigned to the post
    $peergraders_db = $DB->get_record('peerforum_posts', array('id' => $postid));

    if (!empty($peergraders_db)) {
        $peergraders = $peergraders_db->peergraders;
        $peergraders = explode(';', $peergraders);
        $peergraders = array_filter($peergraders);

        foreach ($students as $id => $value) {
            $student = $students[$id]->id;

            if (!in_array($student, $peergraders)) {

                $peergraders_info =
                        $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $student));

                if (!empty($peergraders_info)) {
                    $topeergrade = $peergraders_info->poststopeergrade;
                    $blocked = $peergraders_info->postsblocked;
                    $donepeergrade = $peergraders_info->postspeergradedone;

                    $num_posts = $peergraders_info->numpostsassigned;

                    $posts_tograde = explode(';', $topeergrade);
                    $posts_tograde = array_filter($posts_tograde);
                    $block = explode(';', $blocked);
                    $block = array_filter($block);
                    $posts_graded = explode(';', $donepeergrade);
                    $posts_graded = array_filter($posts_graded);

                    // Can peergrade
                    if (!(in_array($postid, $posts_tograde)) && !(in_array($postid, $block)) &&
                            !(in_array($postid, $posts_graded))) {
                        $std = new stdClass();
                        $std->id = $students[$id]->id;
                        $std->numpostsassigned = $num_posts;

                        $can_be_assigned[$id] = $std;
                        continue;
                    } // Cannot peergrade
                    else if ((in_array($postid, $posts_tograde)) || (in_array($postid, $block)) ||
                            (in_array($postid, $posts_graded))) {
                        continue;

                    }
                } else {
                    // Can peergrade
                    $std = new stdClass();
                    $std->id = $students[$id]->id;
                    $std->numpostsassigned = 0;
                    $can_be_assigned[$id] = $std;
                    continue;
                }
            } else {
                // Cannot peergrade (is in array of peergraders)
                continue;
            }
        }
    } else if (empty($peergraders)) {

        foreach ($students as $id => $value) {
            $num_posts_user =
                    $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $students[$id]->id));
            if (!empty($num_posts_user)) {
                $num_posts = $num_posts_user->numpostsassigned;
            } else {
                $num_posts = 0;
            }
            $std = new stdClass();
            $std->id = $students[$id]->id;
            $std->numpostsassigned = $num_posts;
            $can_be_assigned[$id] = $std;

        }
    }

    // Not assigned to the post
    foreach ($students as $id => $value) {
        if (!in_array($students[$id]->id, $peergraders)) {
            if (!in_array($students[$id]->id, $can_be_assigned)) {
                $num_posts_user = $DB->get_record('peerforum_peergrade_users',
                        array('courseid' => $courseid, 'iduser' => $students[$id]->id));
                if (!empty($num_posts_user)) {
                    $num_posts = $num_posts_user->numpostsassigned;
                } else {
                    $num_posts = 0;
                }
                $std = new stdClass();
                $std->id = $students[$id]->id;
                $std->numpostsassigned = $num_posts;
                $can_be_assigned[$id] = $std;

            }
        }
    }

    foreach ($can_be_assigned as $key => $value) {
        if ($can_be_assigned[$key]->id == $postauthor) {
            unset($can_be_assigned[$key]);
        }
    }

    // Verify conflicts
    $conflicts = $DB->get_records('peerforum_peergrade_conflict', array('courseid' => $courseid));

    foreach ($conflicts as $key => $value) {
        $conflictstds = $conflicts[$key]->idstudents;
        $conflictstds = explode(';', $conflictstds);
        $conflictstds = array_filter($conflictstds);

        foreach ($can_be_assigned as $k => $value) {
            if (in_array($postauthor, $conflictstds)) {
                $id = $can_be_assigned[$k]->id;

                if (in_array($id, $conflictstds)) {
                    unset($can_be_assigned[$k]);
                }
            }
        }
    }
    return $can_be_assigned;
}

/**
 * Compares two stdClass
 *
 * @param stdClass $a
 * @param stdClass $b
 * @return int
 */
function cmp($a, $b) {
    return strcmp($a->numpostsassigned, $b->numpostsassigned);
}

/**
 * Returns the users not assigned to a given post
 *
 * @param int $postid
 * @param array $usersassigned
 * @param int $courseid
 * @param int $postauthor
 * @return array of users not assigned to a post
 */
function get_users_not_assigned($postid, $usersassigned, $courseid, $postauthor) {
    $users_enrolled = get_students_enroled_id($courseid);

    $usersassigned = explode(';', $usersassigned);

    // Remove users already assigned
    foreach ($usersassigned as $k => $value) {
        $key = array_search($usersassigned[$k], $users_enrolled);
        unset($users_enrolled[$key]);
    }

    $key = array_search($postauthor, $usersassigned);
    unset($usersassigned[$key]);

    $usersnotassigned = array_filter($users_enrolled);

    $usersnotassigned = implode(';', $usersnotassigned);

    return $usersnotassigned;
}

/**
 * Insert the peergraders of a post in the database
 *
 * @param int $postid
 * @param array $all_peergraders
 * @param int $courseid
 * @param int $postauthor
 * @return
 * @global object
 */
function insert_peergraders($postid, $all_peergraders, $courseid, $postauthor) {
    global $DB;

    $data = new stdClass();
    $data->id = $postid;
    $data->peergraders = $all_peergraders;
    $DB->update_record('peerforum_posts', $data);

    $data2 = new stdClass();
    $data2->postid = $postid;
    $data2->assigned_users = $all_peergraders;
    $data2->can_grade_users = $all_peergraders;

    $usersnotassigned = get_users_not_assigned($postid, $all_peergraders, $courseid, $postauthor);

    $data2->not_assigned_users = $usersnotassigned;
    $DB->insert_record('peerforum_users_assigned', $data2);
}

/**
 * Returns the time when a given post will expire to a given user
 *
 * @param int $postid
 * @param int $userid
 * @return DateTime time when a post will expire.
 * @global object
 * @global object
 */
function get_time_expire($postid, $userid) {
    global $CFG, $DB;
    // When the post was assigned
    $time_assign = time_assigned($postid, $userid);

    if (!empty($time_assign)) {

        $time_assigned_db = usergetdate($time_assign);

        $date_time_assigned = new stdClass();
        $date_time_assigned->year = $time_assigned_db['year'];
        $date_time_assigned->mon = $time_assigned_db['mon'];
        $date_time_assigned->mday = $time_assigned_db['mday'];
        $date_time_assigned->hours = $time_assigned_db['hours'];
        $date_time_assigned->minutes = $time_assigned_db['minutes'];
        $date_time_assigned->seconds = $time_assigned_db['seconds'];

        $time_assigned =
                new DateTime("$date_time_assigned->year-$date_time_assigned->mon-$date_time_assigned->mday $date_time_assigned->hours:$date_time_assigned->minutes:$date_time_assigned->seconds");

        $postdiscussion = $DB->get_record('peerforum_posts', array('id' => $postid))->discussion;
        $peerforumid = $DB->get_record('peerforum_discussions', array('id' => $postdiscussion))->peerforum;
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforumid));

        // How much time the user have to peergrade
        $time_to_peergrade = $peerforum->timetopeergrade;
        $time = 'P' . $time_to_peergrade . 'D';

        // When the time to peergrade ends
        $time_finish = $time_assigned;
        $time_finish->add(new DateInterval("$time"));

        // Current time
        $time_current_db = usergetdate(time());

        $date_time_current = new stdClass();
        $date_time_current->year = $time_current_db['year'];
        $date_time_current->mon = $time_current_db['mon'];
        $date_time_current->mday = $time_current_db['mday'];
        $date_time_current->hours = $time_current_db['hours'];
        $date_time_current->minutes = $time_current_db['minutes'];
        $date_time_current->seconds = $time_current_db['seconds'];

        $time_current =
                new DateTime("$date_time_current->year-$date_time_current->mon-$date_time_current->mday $date_time_current->hours:$date_time_current->minutes:$date_time_current->seconds");

        // Time period to peergrade
        $time_interval = date_diff($time_finish, $time_current);

        if ($time_interval->invert == 0) {
            return null;
        } else {
            return $time_interval;
        }

    } else {
        return 0;
    }
}

/**
 * Returns the time when a given post was assigned to a given user
 *
 * @param int $postid
 * @param int $userid
 * @return int time when a post was assigned assigned.
 * @global object
 */
function time_assigned($postid, $userid) {
    global $DB;

    $time = $DB->get_record('peerforum_time_assigned', array('postid' => $postid, 'userid' => $userid));

    if (!empty($time)) {
        return $time->timeassigned;
    } else {
        return null;
    }
}

/**
 * Returns the posts not peergraded of a given user
 *
 * @param int $userid
 * @param int $courseid
 * @return array of not peergraded posts.
 * @global object
 */
function peerforum_get_user_posts_to_peergrade($userid, $courseid) {
    global $DB;

    // Get all the posts
    $sql = "SELECT p.id, p.poststopeergrade
            FROM {peerforum_peergrade_users} p
            WHERE p.iduser = $userid AND p.courseid = $courseid";

    $all_posts = $DB->get_records_sql($sql);

    $posts_to_peergrade = array();

    // Verify which posts the user have to peergrade
    foreach ($all_posts as $postid => $value) {
        if (!empty($all_posts[$postid]->poststopeergrade)) {
            $posts_to_peergrade = explode(";", ($all_posts[$postid]->poststopeergrade));
            $posts_to_peergrade = array_filter($posts_to_peergrade);
        }
    }
    return $posts_to_peergrade;
}

/**
 * Returns the posts already peergraded of a given user
 *
 * @param int $userid
 * @param int $courseid
 * @return array of peergraded posts.
 * @global object
 */
function peerforum_get_user_posts_peergraded($userid, $courseid) {
    global $DB;

    // Get all the posts
    $sql = "SELECT p.id, p.postspeergradedone
            FROM {peerforum_peergrade_users} p
            WHERE p.iduser = $userid AND p.courseid = $courseid";

    $all_posts = $DB->get_records_sql($sql);

    $posts_peergraded = array();

    // Verify which posts the user have already peergraded
    foreach ($all_posts as $postid => $value) {
        if (!empty($all_posts[$postid]->postspeergradedone)) {
            $posts_peergraded = explode(";", ($all_posts[$postid]->postspeergradedone));
            $posts_peergraded = array_filter($posts_peergraded);
        }
    }
    return $posts_peergraded;
}

/**
 * Returns the expired posts of a given user
 *
 * @param int $userid
 * @param int $courseid
 * @return array of expired posts.
 * @global object
 */
function peerforum_get_user_posts_expired($userid, $courseid) {
    global $DB;

    // Get all the posts
    $sql = "SELECT p.id, p.postsexpired
            FROM {peerforum_peergrade_users} p
            WHERE p.iduser = $userid AND p.courseid = $courseid";

    $all_posts = $DB->get_records_sql($sql);

    $posts_expired = array();

    // Verify which posts the user have already peergraded
    foreach ($all_posts as $postid => $value) {
        if (!empty($all_posts[$postid]->postsexpired)) {
            $posts_expired = explode(";", ($all_posts[$postid]->postsexpired));
            $posts_expired = array_filter($posts_expired);
        }
    }
    return $posts_expired;
}

/**
 * Returns array of peerforum posts filters
 *
 * @return array
 */
function peerforum_get_manage_posts_filters() {
    return array(MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
            MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTEXPIRED => get_string('managepostsmodeseenotexpired', 'peerforum'));
}

/**
 * Returns array of peerforum relationships filters
 *
 * @return array
 */
function peerforum_get_manage_relations_filters($rankings, $nominations) {
    if ($rankings && $nominations) {
        return array(RELATIONSHIPS_MODE_NOMINATIONS => get_string('managerealtionshipsmodenominations', 'peerforum'),
                RELATIONSHIPS_MODE_RANKINGS => get_string('managerealtionshipsmoderankings', 'peerforum'));
    }
}

/**
 * Returns array of peerforum graders filters
 *
 * @return array
 */
function peerforum_get_graders_posts_filters() {
    return array(MANAGEGRADERS_MODE_SEEALL => get_string('managegradersmodeseeall', 'peerforum'),
            MANAGEGRADERS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTGRADED => get_string('managegradersmodeseenotgraded', 'peerforum'),
            MANAGEGRADERS_MODE_SEEEXPIRED => get_string('managegradersmodeseeexpired', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTEXPIRED => get_string('managegradersmodeseenotexpired', 'peerforum')
    );
}

/**
 * Returns the status of a post for a given user (blocked/peergraded)
 *
 * @param int $postid
 * @param int $userid
 * @param int $courseid
 * @return int 0 -> post is not peergraded / 1 -> post is blocked
 * @global object
 */
function get_post_status($postid, $userid, $courseid) {
    global $DB;

    $posts_user = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $userid));

    if (!empty($posts_user)) {
        $posts_blocked = $posts_user->postsblocked;
        $posts_topeergrade = $posts_user->poststopeergrade;

        adjust_database();

        if (!empty($posts_blocked)) {
            $blocked = explode(';', $posts_blocked);
            $blocked = array_filter($blocked);
            if (in_array($postid, $blocked)) {
                return 1;
            }
        }
        if (!empty($posts_topeergrade)) {
            $topeergrade = explode(';', $posts_topeergrade);
            $topeergrade = array_filter($topeergrade);
            if (in_array($postid, $topeergrade)) {
                return 0;
            }
        }
    }
}

/**
 * Verify if a given peergrade was given to a post by a peergrader (user)
 *
 * @param int $postid
 * @param int $peergrader
 * @return int 0 -> peergrade do not exist / 1 -> peergrade exists
 * @global object
 */
function verify_peergrade($postid, $peergrader) {
    global $DB;

    $peergrade = $DB->get_record('peerforum_peergrade', array('itemid' => $postid, 'userid' => $peergrader));

    if (empty($peergrade)) {
        return 0;
    } else {
        return 1;
    }
}

/**
 * Returns the posts graded
 *
 * @param array $posts
 * @return array of posts already peergraded
 */
function get_posts_graded($posts) {
    $array_posts = array();

    foreach ($posts as $i => $value) {
        $postid = $posts[$i]->postid;
        $count_graders = count($posts[$i]->peergraders);
        $peergraders = $posts[$i]->peergraders;

        if (!empty($peergraders)) {
            for ($k = 0; $k < $count_graders; $k++) {
                $peergraderid = $posts[$i]->peergraders[$k]->id;
                $peergradedone = verify_peergrade($postid, $peergraderid);

                if ($peergradedone == 0) {
                    foreach ($peergraders as $x => $value) {
                        $id_user = $peergraders[$x]->id;
                        if ($peergraderid == $id_user) {
                            unset($peergraders[$x]);
                            break;
                        }
                    }
                }
            }
            $peergraders = array_values($peergraders);

            $posts[$i]->peergraders = $peergraders;
            array_push($array_posts, $posts[$i]);
        }
    }

    foreach ($array_posts as $y => $value) {
        if (empty($array_posts[$y]->peergraders)) {
            unset($array_posts[$y]);
        }
    }

    return $array_posts;
}

/**
 * Returns the posts not graded
 *
 * @param array $posts
 * @return array of posts not peergraded
 */
function get_posts_not_graded($posts) {
    $array_posts = array();

    foreach ($posts as $i => $value) {
        $postid = $posts[$i]->postid;
        $count_graders = count($posts[$i]->peergraders);
        $peergraders = $posts[$i]->peergraders;

        for ($k = 0; $k < $count_graders; $k++) {
            $peergraderid = $posts[$i]->peergraders[$k]->id;
            $peergradedone = verify_peergrade($postid, $peergraderid);

            if ($peergradedone == 1) {
                foreach ($peergraders as $x => $value) {
                    $id_user = $peergraders[$x]->id;
                    if ($peergraderid == $id_user) {
                        unset($peergraders[$x]);
                        break;
                    }
                }
            }
        }
        $peergraders = array_values($peergraders);

        $posts[$i]->peergraders = $peergraders;
        array_push($array_posts, $posts[$i]);
    }

    foreach ($array_posts as $y => $value) {
        if (empty($array_posts[$y]->peergraders)) {
            unset($array_posts[$y]);
        }
    }

    return $array_posts;
}

/**
 * Returns the posts already expired
 *
 * @param array $posts
 * @return array of posts expired
 */
function get_posts_expired($posts) {
    $array_posts = array();

    foreach ($posts as $i => $value) {
        $postid = $posts[$i]->postid;
        $count_graders = count($posts[$i]->peergraders);
        $peergraders = $posts[$i]->peergraders;

        for ($k = 0; $k < $count_graders; $k++) {
            $peergraderid = $posts[$i]->peergraders[$k]->id;
            $time_expire = get_time_expire($postid, $peergraderid);

            if ($time_expire->invert == 1) {
                //    array_push($array_posts, $posts[$i]);
                $key = array_search($peergraderid, $peergraders);
                unset($peergraders[$key]);
                $peergraders = array_values($peergraders);
            }
        }

        $posts[$i]->peergraders = $peergraders;
        if (!empty($peergraders)) {
            array_push($array_posts, $posts[$i]);
        }
    }
    return $array_posts;
}

/**
 * Returns the posts not expired
 *
 * @param array $posts
 * @return array of posts not expired
 */
function get_posts_not_expired($posts) {

    $array_posts = array();

    foreach ($posts as $i => $value) {
        $postid = $posts[$i]->postid;
        $count_graders = count($posts[$i]->peergraders);
        $peergraders = $posts[$i]->peergraders;

        for ($k = 0; $k < $count_graders; $k++) {
            $peergraderid = $posts[$i]->peergraders[$k]->id;
            $time_expire = get_time_expire($postid, $peergraderid);

            if (empty($time_expire) || $time_expire->invert == 0) {
                $key = array_search($peergraderid, $peergraders);
                unset($peergraders[$key]);
                $peergraders = array_values($peergraders);

            }
        }

        $posts[$i]->peergraders = $peergraders;

        if (!empty($peergraders)) {
            array_push($array_posts, $posts[$i]);
        }
    }
    return $array_posts;
}

/**
 * Returns array of peerforum graders filters
 *
 * @return array
 */
function peerforum_get_manage_graders_filters() {
    return array(MANAGEGRADERS_MODE_SEEEXPIRED => get_string('managegradersmodeseeexpired', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTGRADED => get_string('managegradersmodeseenotgraded', 'peerforum'));
}

/**
 * Returns array of peerforum view peergrades filters
 *
 * @return array
 */
function peerforum_get_view_peergrades_filters() {
    return array(VIEWPEERGRADES_MODE_SEEALL => get_string('viewpeergradesmodeseeall', 'peerforum'),
            VIEWPEERGRADES_MODE_SEEWARNINGS => get_string('viewpeergradesmodeseewarnings', 'peerforum'),
            VIEWPEERGRADES_MODE_SEEOUTLIERS => get_string('viewpeergradesmodeseeoutliers', 'peerforum'));
}

/**
 * Returns the status of a user (blocked or unblocked)
 *
 * @param int $iduser
 * @param int $courseid
 * @return int status of a user (0 -> unblocked , 1 -> blocked).
 * @global object
 */
function get_student_status($userid, $courseid) {
    global $DB;

    $status_db = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $userid));

    if (!empty($status_db)) {
        return $status_db->userblocked;
    }
}

/**
 * Returns all the posts expired of a user in a course for the Peer Grade panel
 *
 * @param int $infograder
 * @param int $courseid
 * @return stdClass $infograder with the posts expired updated.
 * @global object
 */
function get_posts_expired_infograder($infograder, $courseid) {
    global $DB;

    foreach ($infograder as $id => $value) {
        $posts_to_print = array();
        $authorid = $infograder[$id][0]->authorid;
        $postsexpired =
                $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $authorid))->postsexpired;
        $posts = array();
        if (!empty($postsexpired)) {
            $postsexpired = explode(';', $postsexpired);
            $postsexpired = array_filter($postsexpired);

            $infograder[$id][0]->poststopeergrade = $postsexpired;
        }
    }
    return $infograder;
}

/**
 * Updates all the posts expired in the database
 *
 * @return
 * @global object
 */
function update_all_posts_expired() {
    global $DB;
    adjust_database();

    $users = $DB->get_records('peerforum_peergrade_users');

    if (!empty($users)) {
        foreach ($users as $key => $value) {
            $userid = $users[$key]->iduser;
            $poststopeergrade = $users[$key]->poststopeergrade;

            if (!empty($poststopeergrade)) {
                $poststopeergrade = explode(';', $poststopeergrade);
                $poststopeergrade = array_filter($poststopeergrade);

                foreach ($poststopeergrade as $id => $value) {
                    $post = peerforum_get_post_full($poststopeergrade[$id]);
                    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion));
                    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum));
                    $course = $DB->get_record('course', array('id' => $peerforum->course));

                    verify_post_expired($post->id, $peerforum, $userid, $course->id);
                }
            }
        }
    }
}

/**
 * Verifies if a post already expired and returns time information about the post
 *
 * @param int $postid
 * @param stdClass $peerforum
 * @param int $userid
 * @param int $courseid
 * @return stdClass time information about the post.
 * @global object
 * @global object
 */
function verify_post_expired($postid, $peerforum, $userid, $courseid) {
    global $DB, $PAGE;

    //verify if the user can peergrade in a period of time
    $time_assign = get_time_assigned($postid, $userid);

    if (!empty($time_assign)) {
        $time_assigned_db = usergetdate($time_assign);

        $time_to_peergrade = $peerforum->timetopeergrade;

        $date_time_assigned = new stdClass();
        $date_time_assigned->year = $time_assigned_db['year'];
        $date_time_assigned->mon = $time_assigned_db['mon'];
        $date_time_assigned->mday = $time_assigned_db['mday'];
        $date_time_assigned->hours = $time_assigned_db['hours'];
        $date_time_assigned->minutes = $time_assigned_db['minutes'];
        $date_time_assigned->seconds = $time_assigned_db['seconds'];

        $time_assigned =
                new DateTime("$date_time_assigned->year-$date_time_assigned->mon-$date_time_assigned->mday $date_time_assigned->hours:$date_time_assigned->minutes:$date_time_assigned->seconds");

        $time = 'P' . $time_to_peergrade . 'D';

        $time_finish = $time_assigned;

        $time_finish->add(new DateInterval("$time"));

        $time_current_db = usergetdate(time());

        $date_time_current = new stdClass();
        $date_time_current->year = $time_current_db['year'];
        $date_time_current->mon = $time_current_db['mon'];
        $date_time_current->mday = $time_current_db['mday'];
        $date_time_current->hours = $time_current_db['hours'];
        $date_time_current->minutes = $time_current_db['minutes'];
        $date_time_current->seconds = $time_current_db['seconds'];

        $time_current =
                new DateTime("$date_time_current->year-$date_time_current->mon-$date_time_current->mday $date_time_current->hours:$date_time_current->minutes:$date_time_current->seconds");

        $time_interval = date_diff($time_finish, $time_current);

        $post_expired = true;

        $data = new stdclass();

        if (has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
            $data->post_expired = false;
            $data->time_interval = $time_interval;
            $data->time_current = $time_current;

        } else if ($time_current <= $time_finish && $time_interval->invert > 0) {
            $data->post_expired = false;
            $data->time_interval = $time_interval;
            $data->time_current = $time_current;

        } else {
            $data->post_expired = true;
            $data->time_interval = 0;
            $data->time_current = 0;

            update_post_expired($postid, $userid, $courseid);
        }

        return $data;
    } else {
        return null;
    }
}

/**
 * Updates a post which expired in the database
 *
 * @param int $postid
 * @param int $userid
 * @param int $courseid
 * @return
 * @global object
 */
function update_post_expired($postid, $userid, $courseid) {
    global $DB;

    adjust_database();

    $posts_user = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $userid));

    if (user_has_role_assignment($userid, 5)) {
        $isstudent = true;
    } else {
        $isstudent = false;
    }

    if (!empty($posts_user)) {

        if ($isstudent) {

            $data = new stdClass();
            $data->id = $posts_user->id;

            if (!empty($posts_user)) {

                $poststopeergrade = $posts_user->poststopeergrade;
                $poststopeergrade = explode(';', $poststopeergrade);
                $poststopeergrade = array_filter($poststopeergrade);

                if (in_array($postid, $poststopeergrade)) {
                    $key = array_search($postid, $poststopeergrade);
                    unset($poststopeergrade[$key]);
                    $poststopeergrade = array_filter($poststopeergrade);

                    $poststopeergrade = implode(';', $poststopeergrade);
                    $data->poststopeergrade = $poststopeergrade;

                    $DB->update_record("peerforum_peergrade_users", $data);

                    $posts_expired = $posts_user->postsexpired;
                    $posts_expired = explode(';', $posts_expired);
                    $posts_expired = array_filter($posts_expired);

                    if (!in_array($postid, $posts_expired)) {
                        array_push($posts_expired, $postid);
                        $posts_expired = array_filter($posts_expired);
                        $posts_expired = implode(';', $posts_expired);

                        $data->postsexpired = $posts_expired;

                        $DB->update_record('peerforum_peergrade_users', $data);
                    }
                }
            }
        }
    }
}

/**
 * Returns the mode of an array of int's
 *
 * @param array $array
 * @return int mode
 */
function mode($array) {
    if (count(array_unique($array)) < count($array)) {
        // Array has duplicates
        $values = array_count_values($array);
        $mode = array_search(max($values), $values);
    } else {
        $mode = null;
    }

    return $mode;
}

/**
 * Returns the average of an array of int's
 *
 * @param array $array
 * @return float average
 */
function average($array) {
    if (!count($array)) {
        return 0;
    }

    $sum = 0;
    for ($i = 0; $i < count($array); $i++) {
        $sum += $array[$i];
    }

    return $sum / count($array);
}

/**
 * Returns the square of value - mean of an array
 *
 * @param array $array
 * @return float mean
 */
function sd_square($x, $mean) {
    return pow($x - $mean, 2);
}

/**
 * Returns the standard deviation (uses sd_square) of an array
 *
 * @param array $array
 * @return float standard deviation
 */
function standart_deviation($array) {
    $div = (count($array) - 1);
    if ($div != 0) {
        // square root of sum of squares devided by N-1
        return sqrt(array_sum(array_map("sd_square", $array, array_fill(0, count($array), (array_sum($array) / count($array))))) /
                $div);
    }
}

/**
 * Returns the grading related permissions
 *
 * @param int $contextid
 * @param string $component
 * @param string $gradingarea
 * @return array with permissions
 */
function peerforum_grading_permissions($contextid, $component, $gradingarea) {

    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_peerforum' || $gradingarea != 'post') {
        // We don't know about this component/ratingpeerarea so just return null to get the
        // default restrictive permissions.
        return null;
    }
    return array(
            'view' => has_capability('mod/peerforum:viewgrade', $context),
            'viewany' => has_capability('mod/peerforum:viewanygrade', $context),
            'viewall' => has_capability('mod/peerforum:viewallgrades', $context),
            'grade' => has_capability('mod/peerforum:grade', $context)
    );
}

/**
 * Returns the name of all students enrolled in a given course
 *
 * @param int $courseid
 * @return array with student names enrolled to a course
 * @global object
 */
function get_students_enroled_name($courseid) {
    global $DB;
    $students = get_students_enroled($courseid);

    $all_students = array(); //[id]->nome completo

    $sql = "SELECT u.id, concat(u.firstname, ' ', u.lastname) as name
            FROM mdl_user u";

    $result = $DB->get_records_sql($sql);

    foreach ($students as $id => $value) {
        $all_students[$id] = $result[$id]->name;
    }

    return $all_students;
}

/**
 * Returns the time when a post was assigned to peergrade to a given user
 *
 * @param int $courseid
 * @return int time when post was assigned to the user.
 * @global object
 */
function get_time_assigned($postid, $userid) {
    global $DB;

    $time = $DB->get_record('peerforum_time_assigned', array('postid' => $postid, 'userid' => $userid));

    if (!empty($time)) {
        return $time->timeassigned;
    } else {
        return null;
    }
}

/**
 * Verifies if a user can peer grade, depending on the final grade mode (only student, only teacher, student and teacher)
 *
 * @param int $isstudent
 * @param int $final_grade_mode
 * @return boolean true if the user can peer grade the post.
 */
function can_user_peergrade_opt($isstudent, $final_grade_mode) {
    if (($isstudent && $final_grade_mode != 1) || (!$isstudent && $final_grade_mode != 2) || $final_grade_mode == 3) {
        return true;
    } else {
        return false;
    }
}

/**
 * Verifies if a user can still edit a post
 *
 * @param int $itemid
 * @param int $userid
 * @return int 0 -> user cannot edit (time expired) / 1 -> user can edit.
 * @global object
 * @global object
 */
function verify_time_to_edit($itemid, $userid) {
    global $CFG, $DB;

    $peergrade_timecreated_db = $DB->get_record('peerforum_peergrade', array('itemid' => $itemid, 'userid' => $userid));

    if (!empty($peergrade_timecreated_db)) {
        $peergrade_timecreated = $peergrade_timecreated_db->timecreated;

        if ((time() - $peergrade_timecreated) < $CFG->maxeditingtime) {
            $time_to_edit = 1;
        } else {
            $time_to_edit = 0;
        }
    } else {
        $time_to_edit = 0;
    }

    return $time_to_edit;
}

/**
 * Returns an array of users that must peergrade a given post
 *
 * @param int $postid
 * @return array of post' peergraders.
 * @global object
 */
function get_post_peergraders($postid) {
    global $DB;

    $post = $DB->get_record('peerforum_posts', array('id' => $postid));
    $peergraders = $post->peergraders;

    $peers = explode(';', $peergraders);
    $peers = array_filter($peers);

    return $peers;
}

/**
 * Returns an array of users assigned to peer grade a given post
 *
 * @param int $itemid
 * @return array of user's assigned.
 * @global object
 */
function get_peers_assigned_to_post($itemid) {
    global $DB;

    $peers_topeergrade = $DB->get_record('peerforum_users_assigned', array('postid' => $itemid))->assigned_users;

    $peers_topeergrade = explode(';', $peers_topeergrade);
    $peers_topeergrade = array_filter($peers_topeergrade);

    return $peers_topeergrade;
}

/**
 * Returns an array of students' names
 *
 * @param array $students
 * @return array of students' names.
 */
function get_students_name($students) {
    $assigned_students = array();

    foreach ($students as $key => $value) {
        $assigned_students[$key] = get_student_name($students[$key]);
    }

    return $assigned_students;
}

/**
 * Returns the name of a given student
 *
 * @param int $userid
 * @return string of student's name.
 * @global object
 */
function get_student_name($userid) {
    global $DB;

    $sql = "SELECT u.id, concat(u.firstname, ' ', u.lastname) as name
            FROM mdl_user u
            WHERE u.id = $userid";

    $student = $DB->get_records_sql($sql);

    return $student[$userid]->name;
}

/**
 * Returns an array of students assigned to a post
 *
 * @param int $postid
 * @return array of student's assigned.
 * @global object
 */
function get_assigned_users($postid) {
    global $DB;

    $info = $DB->get_record('peerforum_users_assigned', array('postid' => $postid));

    $assigned = array();

    if (!empty($info)) {
        $users_assigned = $info->assigned_users;
        $users_assigned = explode(';', $users_assigned);
        $users_assigned = array_filter($users_assigned);

        foreach ($users_assigned as $key => $value) {
            $id = $users_assigned[$key];
            $assigned[$id] = $users_assigned[$key];
        }
    }
    return $assigned;
}

/**
 * Returns an array of students not assigned to a post
 *
 * @param int $postid
 * @return array of student's not assigned.
 * @global object
 */
function get_not_assigned_users($postid) {
    global $DB;

    $info = $DB->get_record('peerforum_users_assigned', array('postid' => $postid));

    $not_assigned = array();

    if (!empty($info)) {
        $users_not_assigned = $info->not_assigned_users;
        $users_not_assigned = explode(';', $users_not_assigned);
        $users_not_assigned = array_filter($users_not_assigned);

        foreach ($users_not_assigned as $key => $value) {
            $id = $users_not_assigned[$key];
            $not_assigned[$id] = $users_not_assigned[$key];
        }
    }
    return $not_assigned;
}

/**
 * Returns an array of students assigned to a post in a given course
 *
 * @param int $courseid
 * @param int $postid
 * @return array of student's assigned to a post in a course.
 * @global object
 */
function get_students_assigned($courseid, $postid) {
    global $DB;

    $peergraders = $DB->get_record('peerforum_posts', array('id' => $postid))->peergraders;

    $peers = explode(';', $peergraders);
    $peers = array_filter($peers);

    $assigned = array();

    adjust_database();

    foreach ($peers as $i => $value) {
        $id = $peers[$i];
        //verify if post was not already peer graded by this student, cannot remove student if post was already peer graded by him
        $posts_done_db = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $id));

        if (!empty($posts_done_db)) {
            $posts_done = $posts_done_db->postspeergradedone;

            $posts = explode(';', $posts_done);
            $posts = array_filter($posts);

            if (!in_array($postid, $posts)) {
                $assigned[$id] = $peers[$i];
            }
        }
    }
    return $assigned;
}

/**
 * Verifies if a given user can peer grade a post
 *
 * @param int $already_peergraded_by_user
 * @param int $editpostid
 * @param int $post_topeergrade
 * @return boolean true if the user can peer grade the post/ false otherwise.
 */
function can_user_peergrade($already_peergraded_by_user, $editpostid, $post_topeergrade) {
    if ((!$already_peergraded_by_user && $editpostid == -2) || ($already_peergraded_by_user && $editpostid == -2) ||
            (!$already_peergraded_by_user && $editpostid == -1) ||
            ($already_peergraded_by_user && $editpostid == $post_topeergrade)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Returns the peergrader's names ofa given post to peer grade
 *
 * @param array $peers_topeergrade
 * @param int $post_topeergrade
 * @return array of peergrader's names.
 * @global object
 */
function get_peersnames($peers_topeergrade, $post_topeergrade) {
    global $DB;

    $peers_assigned = array();
    $last_key = max(array_keys($peers_topeergrade));
    $peernames = array();
    $peerids = null;

    foreach ($peers_topeergrade as $key => $value) {
        $graded =
                $DB->get_record('peerforum_peergrade', array('itemid' => $post_topeergrade, 'userid' => $peers_topeergrade[$key]));
        if (!empty($graded)) {
            $color = '#339966';
        } else {
            $color = '#cc3300';
        }

        $peer_name = get_student_name($peers_topeergrade[$key]);

        if ($key != $last_key) {
            array_push($peernames, html_writer::tag('span', $peer_name,
                    array('id' => 'peersassigned' . $post_topeergrade, 'style' => 'color:' . $color . ';')));
            array_push($peernames, html_writer::tag('span', '; ', array('style' => 'color:grey;')));

        } else {
            array_push($peernames, html_writer::tag('span', $peer_name, array('style' => 'color:' . $color . ';')));
        }
    }
    return $peernames;
}

/**
 * Returns the time left of a given time interval
 *
 * @param stdClass $time_interval
 * @return string with time formatted.
 */
function get_time_left($time_interval) {
    $days = $time_interval->d;
    $months = $time_interval->m;
    $years = $time_interval->y;

    if (!empty($years)) {
        $time_left = $time_interval->d . 'y:' . $time_interval->d . 'M:' . $time_interval->d . 'd:' . $time_interval->h . 'h:' .
                $time_interval->i . 'm';
    } else if (!empty($months)) {
        $time_left = $time_interval->d . 'M:' . $time_interval->d . 'd:' . $time_interval->h . 'h:' . $time_interval->i . 'm';
    } else if (!empty($days)) {
        $time_left = $time_interval->d . 'd:' . $time_interval->h . 'h:' . $time_interval->i . 'm';
    } else {
        $time_left = $time_interval->h . 'h:' . $time_interval->i . 'm';
    }

    return $time_left;
}

/**
 * Validates a submitted peergrade
 *
 * @param array $params submitted data
 *            context => object the context in which the peergraded items exists [required]
 *            component => The component for this module - should always be mod_peerforum [required]
 *            peergradearea => object the context in which the peergraded items exists [required]
 *            itemid => int the ID of the object being peergraded [required]
 *            scaleid => int the scale from which the user can select a peergrade. Used for bounds checking. [required]
 *            ratingpeer => int the submitted peergrade [required]
 *            ratedpeeruserid => int the id of the user whose items have been peergraded. NOT the user who submitted the
 *         peergrading. 0 to update all. [required] aggregation => int the aggregation method to apply when calculating grades ie
 *         PEERGRADE_AGGREGATE_AVERAGE [required]
 * @return boolean true if the peergrade is valid. Will throw peergrade_exception if not
 */
function peerforum_peergrade_validate($params) {
    global $DB, $USER;

    // Check the component is mod_peerforum
    if ($params['component'] != 'mod_peerforum') {
        throw new peergrade_exception('invalidcomponent');
    }

    // Check the peergradearea is post (the only ratingpeer area in peerforum)
    if ($params['peergradearea'] != 'post') {
        throw new peergrade_exception('invalidpeergradearea');
    }

    // Check the ratedpeeruserid is not the current user .. you can't ratepeer your own posts
    if ($params['peergradeduserid'] == $USER->id) {
        throw new peergrade_exception('nopermissiontopeergrade');
    }

    // Fetch all the related records ... we need to do this anyway to call peerforum_user_can_see_post
    $post = $DB->get_record('peerforum_posts', array('id' => $params['itemid'], 'userid' => $params['peergradeduserid']), '*',
            MUST_EXIST);
    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Make sure the context provided is the context of the peerforum
    if ($context->id != $params['context']->id) {
        throw new peergrade_exception('invalidcontext');
    }

    if ($peerforum->peergradescale != $params['peergradescaleid']) {
        //the scale being submitted doesnt match the one in the database
        throw new peergrade_exception('invalidscaleid');
    }

    // check the item we're ratingpeer was created in the assessable time window
    if (!empty($peerforum->assesstimestart) && !empty($peerforum->assesstimefinish)) {
        if ($post->created < $peerforum->assesstimestart || $post->created > $peerforum->assesstimefinish) {
            throw new peergrade_exception('notavailable');
        }
    }

    //check that the submitted ratingpeer is valid for the scale

    // lower limit
    if ($params['peergrade'] < 0 && $params['peergrade'] != PEERGRADE_UNSET_PEERGRADE) {
        throw new peergrade_exception('invalidnum4');
    }

    // upper limit

    if ($peerforum->peergradescale < 0) {
        //its a custom scale
        $peergradescalerecord = $DB->get_record('peergradescale', array('id' => -$peerforum->peergradescale));

        if ($peergradescalerecord) {
            $peergradescalearray = explode(',', $peergradescalerecord->peergradescale);
            if ($params['peergrade'] > count($peergradescalearray)) {
                throw new peergrade_exception('invalidnum');
            }
        } else {
            throw new peergrade_exception('invalidscaleid');
        }
    } else if ($params['peergrade'] > $peerforum->peergradescale) {
        //if its numeric and submitted peergrade is above maximum
        throw new peergrade_exception('invalidnum8');
    }

    // Make sure groups allow this user to see the item they're ratingpeer
    if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
        if (!groups_group_exists($discussion->groupid)) { // Can't find group
            throw new peergrade_exception('cannotfindgroup');//something is wrong
        }

        if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // do not allow ratingpeer of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
            throw new peergrade_exception('notmemberofgroup');
        }
    }

    // perform some final capability checks
    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, $USER, $cm)) {
        throw new peergrade_exception('nopermissiontoratepeer');
    }

    return true;
}

/**
 * Returns the students that can be removed from peer grading a post
 *
 * @param int $courseid
 * @param int $postid
 * @param int $postauthor
 * @param int optional $peerforumid
 * @return array with students that can be removed.
 * @global object
 */
function get_students_can_be_removed($courseid, $postid, $postauthor, $peerforumid = null) {
    global $DB;

    $peergraders_db = $DB->get_record('peerforum_posts', array('id' => $postid, 'userid' => $postauthor));

    if (!empty($peergraders_db)) {
        $peergraders = $peergraders_db->peergraders;
        $peergraders = explode(';', $peergraders);
        $peergraders = array_filter($peergraders);

        return $peergraders;
    } else {
        return null;
    }
}

//------------------------------------------------//

/**
 * Return the grades of all the users.
 *
 * @param object $peerforum
 * @param int $userid optional user id
 * @return array grades, null if none
 * @global object
 */
function peerforum_get_user__grades($peerforum, $userid = 0) {
    global $CFG;
    // OLD require_once($CFG->dirroot . '/rating/lib.php');
    require_once($CFG->dirroot . '/ratingpeer/lib.php');

    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

    $rm = new rating_manager();
    return $rm->get_user_grades((object) [
            'component' => 'mod_peerforum',
            'ratingpeerarea' => 'post',
            'contextid' => \context_module::instance($cm->id)->id,

            'modulename' => 'peerforum',
            'moduleid  ' => $peerforum->id,
            'userid' => $userid,
            'aggregationmethod' => $peerforum->assessed,
            'scaleid' => $peerforum->scale,
            'itemtable' => 'peerforum_posts',
            'itemtableusercolumn' => 'userid',
    ]);
}

/**
 * Returns user's/student's grades
 *
 * @param object $peerforum
 * @param int $userid
 * @return array the array of the user's grades
 * @global object
 */
function peerforum_get_user_students_peergrades($peerforum, $userid = 0) {
    global $CFG;
    require_once($CFG->dirroot . '/peergrade/lib.php');

    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

    $rm = new rating_manager();
    return $rm->get_user_grades((object) [
            'component' => 'mod_peerforum',
            'peergradearea' => 'post',
            'contextid' => \context_module::instance($cm->id)->id,

            'modulename' => 'peerforum',
            'moduleid  ' => $peerforum->id,
            'userid' => $userid,
            'aggregationmethod' => $peerforum->peergradeassessed,
            'peergradescaleid' => $peerforum->peergradescale,
            'itemtable' => 'peerforum_posts',
            'itemtableusercolumn' => 'userid',
    ]);
}

/**
 * Returns the peergrades given by the professors to a user
 *
 * @param stdClass $peerforum
 * @param int $userid
 * @return stdClass grade
 * @global object
 */
function peerforum_get_user_professors_peergrades($peerforum, $userid = 0) {
    global $CFG;
    require_once($CFG->dirroot . '/peergrade/lib.php');

    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

    $rm = new rating_manager();
    return $rm->get_user_grades((object) [
            'component' => 'mod_peerforum',
            'peergradearea' => 'post',
            'contextid' => \context_module::instance($cm->id)->id,

            'modulename' => 'peerforum',
            'moduleid  ' => $peerforum->id,
            'userid' => $userid,
            'aggregationmethod' => $peerforum->peergradeassessed,
            'peergradescaleid' => $peerforum->peergradescale,
            'itemtable' => 'peerforum_posts',
            'itemtableusercolumn' => 'userid',
    ]);
}

/**
 * Returns the time when the post was created
 *
 * @param int $postid
 * @return int time when post was created.
 * @global object
 */
function time_created($postid) {
    global $DB;

    $sql = "SELECT p.id, p.created
              FROM {peerforum_posts} p
             WHERE p.id = $postid ";
    $post_time_created = $DB->get_records_sql($sql);

    return $post_time_created[$postid]->created;

}

/**
 * Returns the id of a given user
 *
 * @param int $iduser
 * @return int id of a user.
 * @global object
 */
function get_id($iduser) {
    global $DB;

    $sql = "SELECT p.iduser, p.id
              FROM {peerforum_peergrade_users} p
             WHERE p.iduser = $iduser";
    $id_sql = $DB->get_records_sql($sql);

    return $id_sql[$iduser]->id;
}

/**
 * Returns a random number
 *
 * @param array $array
 * @param int $quantity
 * @param int $selected
 * @return int a random number.
 */
function randomGen($array, $quantity, $selected) {
    //$numbers = range($min, $max);
    $numbers = array_rand($array, $selected);
    shuffle($numbers);
    return array_slice($numbers, 0, $quantity);
}

/**
 * Returns all the professors enrolled in a course
 *
 * @param int $courseid
 * @return array all the professors enrolled in a course.
 * @global object
 */
function get_professors_enroled($courseid) {
    global $DB;

    //get all enroled users in course
    $sql = "SELECT u.id, c.id as courseid, u.id as userid
            FROM mdl_course c
            JOIN mdl_context ct ON c.id = ct.instanceid
            JOIN mdl_role_assignments ra ON ra.contextid = ct.id
            JOIN mdl_user u ON u.id = ra.userid
            JOIN mdl_role r ON r.id = ra.roleid
            WHERE c.id = $courseid AND r.id = 3";

    $enroled = $DB->get_records_sql($sql);

    return $enroled;
}

/**
 * Returns all the students' id enrolled in a course
 *
 * @param int $courseid
 * @return array all the students' id enrolled in a course.
 * @global object
 */
function get_students_enroled_id($courseid) {
    global $DB;

    //get all enroled users in course
    $sql = "SELECT u.id
            FROM mdl_course c
            JOIN mdl_context ct ON c.id = ct.instanceid
            JOIN mdl_role_assignments ra ON ra.contextid = ct.id
            JOIN mdl_user u ON u.id = ra.userid
            JOIN mdl_role r ON r.id = ra.roleid
            WHERE c.id = $courseid AND r.id = 5";

    $enroled_sql = $DB->get_records_sql($sql);

    $enroled = array();
    foreach ($enroled_sql as $key => $value) {
        $id = $enroled_sql[$key]->id;
        $enroled[$id] = $id;
    }

    return $enroled;
}

/// STANDARD FUNCTIONS ///////////////////////////////////////////////////////////

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $peerforum add peerforum instance
 * @param mod_peerforum_mod_form $mform
 * @return int intance id
 */
function peerforum_add_instance($peerforum, $mform = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/peerforum/locallib.php');

    $peerforum->timemodified = time();

    if (empty($peerforum->assessed)) {
        $peerforum->assessed = 0;
    }

    if (empty($peerforum->ratingpeertime) or empty($peerforum->assessed)) {
        $peerforum->assesstimestart = 0;
        $peerforum->assesstimefinish = 0;
    }

    $peerforum->id = $DB->insert_record('peerforum', $peerforum);
    $modcontext = context_module::instance($peerforum->coursemodule);

    if ($peerforum->type == 'single') {  // Create related discussion.
        $discussion = new stdClass();
        $discussion->course = $peerforum->course;
        $discussion->peerforum = $peerforum->id;
        $discussion->name = $peerforum->name;
        $discussion->assessed = $peerforum->assessed;
        $discussion->message = $peerforum->intro;
        $discussion->messageformat = $peerforum->introformat;
        $discussion->messagetrust = trusttext_trusted(context_course::instance($peerforum->course));
        $discussion->mailnow = false;
        $discussion->groupid = -1;

        $message = '';

        $discussion->id = peerforum_add_discussion($discussion, null, $message);

        if ($mform and $draftid = file_get_submitted_draft_itemid('introeditor')) {
            // Ugly hack - we need to copy the files somehow.
            $discussion = $DB->get_record('peerforum_discussions', array('id' => $discussion->id), '*', MUST_EXIST);
            $post = $DB->get_record('peerforum_posts', array('id' => $discussion->firstpost), '*', MUST_EXIST);

            $options = array('subdirs' => true); // Use the same options as intro field!
            $post->message =
                    file_save_draft_area_files($draftid, $modcontext->id, 'mod_peerforum', 'post', $post->id, $options,
                            $post->message);
            $DB->set_field('peerforum_posts', 'message', $post->message, array('id' => $post->id));
        }
    }

    peerforum_update_calendar($peerforum, $peerforum->coursemodule);
    peerforum_grade_item_update($peerforum);

    $completiontimeexpected = !empty($peerforum->completionexpected) ? $peerforum->completionexpected : null;
    \core_completion\api::update_completion_date_event($peerforum->coursemodule, 'peerforum', $peerforum->id,
            $completiontimeexpected);

    return $peerforum->id;
}

/**
 * Handle changes following the creation of a peerforum instance.
 * This function is typically called by the course_module_created observer.
 *
 * @param object $context the peerforum context
 * @param stdClass $peerforum The peerforum object
 * @return void
 */
function peerforum_instance_created($context, $peerforum) {
    if ($peerforum->forcesubscribe == PEERFORUM_INITIALSUBSCRIBE) {
        $users = \mod_peerforum\subscriptions::get_potential_subscribers($context, 0, 'u.id, u.email');
        foreach ($users as $user) {
            \mod_peerforum\subscriptions::subscribe_user($user->id, $peerforum, $context);
        }
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $peerforum peerforum instance (with magic quotes)
 * @return bool success
 * @global object
 */
function peerforum_update_instance($peerforum, $mform) {
    global $DB, $OUTPUT, $USER, $COURSE;

    require_once($CFG->dirroot . '/mod/peerforum/locallib.php');

    $peerforum->timemodified = time();
    $peerforum->id = $peerforum->instance;

    if (empty($peerforum->assessed)) {
        $peerforum->assessed = 0;
    }

    if (empty($peerforum->ratingpeertime) or empty($peerforum->assessed)) {
        $peerforum->assesstimestart = 0;
        $peerforum->assesstimefinish = 0;
    }

    if (empty($peerforum->peergradetime) or empty($peerforum->peergradeassessed)) {
        $peerforum->peergradeassesstimestart = 0;
        $peerforum->peergradeassesstimefinish = 0;
    }

    $oldpeerforum = $DB->get_record('peerforum', array('id' => $peerforum->id));

    // MDL-3942 - if the aggregation type or scale (i.e. max grade) changes then recalculate the grades for the entire peerforum
    // if  scale changes - do we need to recheck the ratingpeers, if ratingpeers higher than scale how do we want to respond?
    // for count and sum aggregation types the grade we check to make sure they do not exceed the scale (i.e. max score) when calculating the grade
    $updategrades = false;

    if ($oldpeerforum->assessed <> $peerforum->assessed) {
        // Whether this peerforum is rated.
        $updategrades = true;
    }

    if ($oldpeerforum->scale <> $peerforum->scale) {
        // The scale currently in use.
        $updategrades = true;
    }

    if (empty($oldpeerforum->grade_peerforum) || $oldpeerforum->grade_peerforum <> $peerforum->grade_peerforum) {
        // The whole peerforum grading.
        $updategrades = true;
    }

    if ($updategrades) {
        peerforum_update_grades($peerforum); // Recalculate grades for the peerforum.
    }

    if ($peerforum->type == 'single') {  // Update related discussion and post.
        $discussions = $DB->get_records('peerforum_discussions', array('peerforum' => $peerforum->id), 'timemodified ASC');
        if (!empty($discussions)) {
            if (count($discussions) > 1) {
                echo $OUTPUT->notification(get_string('warnformorepost', 'peerforum'));
            }
            $discussion = array_pop($discussions);
        } else {
            // try to recover by creating initial discussion - MDL-16262
            $discussion = new stdClass();
            $discussion->course = $peerforum->course;
            $discussion->peerforum = $peerforum->id;
            $discussion->name = $peerforum->name;
            $discussion->assessed = $peerforum->assessed;
            $discussion->message = $peerforum->intro;
            $discussion->messageformat = $peerforum->introformat;
            $discussion->messagetrust = true;
            $discussion->mailnow = false;
            $discussion->groupid = -1;

            $message = '';

            peerforum_add_discussion($discussion, null, $message);

            if (!$discussion = $DB->get_record('peerforum_discussions', array('peerforum' => $peerforum->id))) {
                print_error('cannotadd', 'peerforum');
            }
        }
        if (!$post = $DB->get_record('peerforum_posts', array('id' => $discussion->firstpost))) {
            print_error('cannotfindfirstpost', 'peerforum');
        }

        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
        $modcontext = context_module::instance($cm->id, MUST_EXIST);

        $post = $DB->get_record('peerforum_posts', array('id' => $discussion->firstpost), '*', MUST_EXIST);
        $post->subject = $peerforum->name;
        $post->message = $peerforum->intro;
        $post->messageformat = $peerforum->introformat;
        $post->messagetrust = trusttext_trusted($modcontext);
        $post->modified = $peerforum->timemodified;
        $post->userid = $USER->id;    // MDL-18599, so that current teacher can take ownership of activities.

        if ($mform and $draftid = file_get_submitted_draft_itemid('introeditor')) {
            // Ugly hack - we need to copy the files somehow.
            $options = array('subdirs' => true); // Use the same options as intro field!
            $post->message =
                    file_save_draft_area_files($draftid, $modcontext->id, 'mod_peerforum', 'post', $post->id, $options,
                            $post->message);
        }

        \mod_peerforum\local\entities\post::add_message_counts($post);
        $DB->update_record('peerforum_posts', $post);
        $discussion->name = $peerforum->name;
        $DB->update_record('peerforum_discussions', $discussion);
    }

    $DB->update_record('peerforum', $peerforum);

    $modcontext = context_module::instance($peerforum->coursemodule);
    if (($peerforum->forcesubscribe == PEERFORUM_INITIALSUBSCRIBE) &&
            ($oldpeerforum->forcesubscribe <> $peerforum->forcesubscribe)) {
        $users = \mod_peerforum\subscriptions::get_potential_subscribers($modcontext, 0, 'u.id, u.email', '');
        foreach ($users as $user) {
            \mod_peerforum\subscriptions::subscribe_user($user->id, $peerforum, $modcontext);
        }
    }

    peerforum_update_calendar($peerforum, $peerforum->coursemodule);
    //Processing of threaded grading

    //Get actual configutations
    $peerforum_info = $DB->get_record("peerforum", array('course' => $COURSE->id));

    $subject_names = get_discussions_name($COURSE->id, $peerforum->id);

    //Check what if random distribution was previously activated
    $subjects_type1 =
            $DB->get_records("peerforum_discussions", array('type' => 1, 'course' => $COURSE->id, 'peerforum' => $peerforum->id));

    if (count($subjects_type1) == count($subject_names)) {
        $current_rand_dist = 1;
    } else {
        $current_rand_dist = 0;
    }

    $fromform = $mform->get_data();
    if (!empty($fromform)) {
        //WARNING: Does not have in consideration if previously random distribution was set or not.
        //CASE 1: Threaded grading on with N topics given to be assigned
        if ($fromform->threaded_grading && !empty($fromform->topicstoattribute)) {

            //Topics + type
            $topics = $fromform->topicstoattribute;
            $topics_choosen = array_id_to_name($topics, $peerforum->id);
            $type = $fromform->typestoattribute;

            foreach ($topics_choosen as $i => $value) {
                $record = $DB->get_record("peerforum_discussions",
                        array('name' => (string) $topics_choosen[$i], 'course' => $COURSE->id, 'peerforum' => $peerforum->id));

                $data = new stdClass();
                $data->id = $record->id;
                $data->type = $type + 1;
                $DB->update_record('peerforum_discussions', $data);

            }

            //Assign students to specificied topic(s)
            // 0 -> 1
            if ($type == 0) {
                foreach ($topics_choosen as $key => $value) {
                    $student_type_1 =
                            $DB->get_records("peerforum_peergrade_users", array('courseid' => $COURSE->id, 'peergradetype' => 1));

                    if (empty($student_type_1)) {
                        $students_assigned = 0;
                    } else {
                        $new = array();
                        foreach ($student_type_1 as $i => $value) {
                            if ($student_type_1[$i]->userblocked == 0 &&
                                    $student_type_1[$i]->topicsassigned == $topics_choosen[$key]) {
                                array_push($new, $student_type_1[$i]);
                            }
                        }
                        $students_assigned = count($new);
                    }

                    if ($fromform->numberofstudents == 1) {
                        $students_to_assign = 6;
                    } else {
                        $students_to_assign = ($fromform->numberofstudents * 5);
                    }

                    $diff = abs($students_to_assign - $students_assigned);

                    if ($students_assigned < $students_to_assign) {

                        $available_students = $DB->get_records("peerforum_peergrade_users",
                                array('courseid' => $COURSE->id, 'peergradetype' => 2));

                        if (count($available_students) <= 9) {
                            print_error("Cannot assign more students to a specfic topic.");
                        } else {
                            //Add students
                            $rand_students = array_rand($available_students, $diff);
                            foreach ($rand_students as $j => $value) {
                                if ($rand_students[$j]->userblocked == 0) {
                                    $data = new stdClass();
                                    $data->id = $rand_students[$j];
                                    $data->peergradetype = 1;
                                    $data->topicsassigned = $topics_choosen[$key];
                                    $DB->update_record("peerforum_peergrade_users", $data);
                                }
                            }
                        }
                    } else if ($students_assigned > $students_to_assign) {
                        //Remove students
                        $removable_students = array_slice($new, $students_to_assign);

                        foreach ($removable_students as $k => $value) {
                            $data = new stdClass();
                            $data->id = $removable_students[$k]->id;
                            $data->peergradetype = 2;
                            $data->topicsassigned = "";
                            $DB->update_record("peerforum_peergrade_users", $data);
                        }
                    } else {
                        echo 'Error';
                    }
                }
            } //closes if ($type == 0)

            if ($type == 1) {
                foreach ($topics_choosen as $key => $value) {
                    $student_type_1 =
                            $DB->get_records("peerforum_peergrade_users", array('courseid' => $COURSE->id, 'peergradetype' => 1));

                    if (!empty($student_type_1)) {

                        $new = array();
                        foreach ($student_type_1 as $i => $value) {
                            if ($student_type_1[$i]->userblocked == 0 &&
                                    $student_type_1[$i]->topicsassigned == $topics_choosen[$key]) {
                                array_push($new, $student_type_1[$i]);
                            }
                        }

                        if (!empty($new)) {
                            foreach ($new as $k => $value) {
                                $data = new stdClass();
                                $data->id = $new[$k]->id;
                                $data->peergradetype = 2;
                                $data->topicsassigned = "";
                                $DB->update_record("peerforum_peergrade_users", $data);
                            }
                        }
                    }
                }
            } //end type 1

        } //CASE 2: Threaded grading ON, apply random distribution on a peerforum with preivous configurations!
        else if ($fromform->threaded_grading && $fromform->random_distribution &&
                $fromform->random_distribution != $current_rand_dist) {

            // WARNING: Doesn't exclude blocked students, however they won't be assigned later
            $all_students = $DB->get_records("peerforum_peergrade_users", array('courseid' => $COURSE->id));
            $students_info = remove_blocked_students($all_students);

            $studentsavailable = count($students_info);

            //Change all discussions' topic type
            foreach ($subject_names as $i => $value) {

                $record = $DB->get_record("peerforum_discussions",
                        array('name' => (string) $subject_names[$i], 'course' => $COURSE->id, 'peerforum' => $peerforum->id));

                $data = new stdClass();
                $data->id = $record->id;
                $data->type = 1;
                $DB->update_record("peerforum_discussions", $data);
            }

            //Foreach topic get N students and assign them
            $index = 1;
            while ($studentsavailable > 0) {
                foreach ($subject_names as $key => $value) {
                    if ($studentsavailable > 0) {
                        $student = $students_info[$index];

                        $data = new stdClass();
                        $data->id = $student->id;
                        $data->peergradetype = 1;
                        $data->topicsassigned =
                                $subject_names[$key]; //Here we just replace whatever is there, but if new topics are created, this value should be updated
                        $DB->update_record("peerforum_peergrade_users", $data);

                        $studentsavailable--;
                        $index++;

                    } else {
                        break;
                    }
                }
            }
        } //CASE 3: Threaded grading wihtout any configurations. Reset to the initial state or if no topics are selected
        else if ($fromform->threaded_grading && !$fromform->random_distribution) {

            $discussions = $DB->get_records("peerforum_discussions", array('course' => $COURSE->id, 'peerforum' => $peerforum->id));

            //All topics and students back to the default
            foreach ($discussions as $key => $value) {
                $data = new stdClass();
                $data->id = $discussions[$key]->id;
                $data->type = 2;

                $DB->update_record("peerforum_discussions", $data);
            }

            $students_type_1 =
                    $DB->get_records("peerforum_peergrade_users", array('courseid' => $COURSE->id, 'peergradetype' => 1));

            foreach ($students_type_1 as $i => $value) {
                $data2 = new stdClass();
                $data2->id = $students_type_1[$i]->id;
                $data2->peergradetype = 2;
                $data2->topicsassigned = null;

                $DB->update_record("peerforum_peergrade_users", $data2);
            }
        }
    }

    peerforum_grade_item_update($peerforum);

    $completiontimeexpected = !empty($peerforum->completionexpected) ? $peerforum->completionexpected : null;
    \core_completion\api::update_completion_date_event($peerforum->coursemodule, 'peerforum', $peerforum->id,
            $completiontimeexpected);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id peerforum instance id
 * @return bool success
 * @global object
 */
function peerforum_delete_instance($id) {
    global $DB;

    if (!$peerforum = $DB->get_record('peerforum', array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id)) {
        return false;
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        return false;
    }

    $context = context_module::instance($cm->id);

    // now get rid of all files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    $result = true;

    \core_completion\api::update_completion_date_event($cm->id, 'peerforum', $peerforum->id, null);

    // Delete digest and subscription preferences.
    $DB->delete_records('peerforum_digests', array('peerforum' => $peerforum->id));
    $DB->delete_records('peerforum_subscriptions', array('peerforum' => $peerforum->id));
    $DB->delete_records('peerforum_discussion_subs', array('peerforum' => $peerforum->id));

    if ($discussions = $DB->get_records('peerforum_discussions', array('peerforum' => $peerforum->id))) {
        foreach ($discussions as $discussion) {
            if (!peerforum_delete_discussion($discussion, true, $course, $cm, $peerforum)) {
                $result = false;
            }
        }
    }

    peerforum_tp_delete_read_records(-1, -1, -1, $peerforum->id);

    peerforum_grade_item_delete($peerforum);

    // We must delete the module record after we delete the grade item.
    if (!$DB->delete_records('peerforum', array('id' => $peerforum->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Indicates API features that the peerforum supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 */
function peerforum_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_RATE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;

        default:
            return null;
    }
}

/**
 * Obtains the automatic completion state for this peerforum based on any conditions
 * in peerforum settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 * @global object
 * @global object
 */
function peerforum_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get peerforum details
    if (!($peerforum = $DB->get_record('peerforum', array('id' => $cm->instance)))) {
        throw new Exception("Can't find peerforum {$cm->instance}");
    }

    $result = $type; // Default return value

    $postcountparams = array('userid' => $userid, 'peerforumid' => $peerforum->id);
    $postcountsql = "
SELECT
    COUNT(1)
FROM
    {peerforum_posts} fp
    INNER JOIN {peerforum_discussions} fd ON fp.discussion=fd.id
WHERE
    fp.userid=:userid AND fd.peerforum=:peerforumid";

    if ($peerforum->completiondiscussions) {
        $value = $peerforum->completiondiscussions <=
                $DB->count_records('peerforum_discussions', array('peerforum' => $peerforum->id, 'userid' => $userid));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    if ($peerforum->completionreplies) {
        $value = $peerforum->completionreplies <=
                $DB->get_field_sql($postcountsql . ' AND fp.parent<>0', $postcountparams);
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    if ($peerforum->completionposts) {
        $value = $peerforum->completionposts <= $DB->get_field_sql($postcountsql, $postcountparams);
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}

/**
 * Create a message-id string to use in the custom headers of peerforum notification emails
 *
 * message-id is used by email clients to identify emails and to nest conversations
 *
 * @param int $postid The ID of the peerforum post we are notifying the user about
 * @param int $usertoid The ID of the user being notified
 * @return string A unique message-id
 */
function peerforum_get_email_message_id($postid, $usertoid) {
    return generate_email_messageid(hash('sha256', $postid . 'to' . $usertoid));
}

/**
 *
 * @param object $course
 * @param object $user
 * @param object $mod TODO this is not used in this function, refactor
 * @param object $peerforum
 * @return object A standard object with 2 variables: info (number of posts for this user) and time (last modified)
 */
function peerforum_user_outline($course, $user, $mod, $peerforum) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $gradeinfo = '';
    $gradetime = 0;

    $grades = grade_get_grades($course->id, 'mod', 'peerforum', $peerforum->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        // Item 0 is the rating.
        $grade = reset($grades->items[0]->grades);
        $gradetime = max($gradetime, grade_get_date_for_user_grade($grade, $user));
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $gradeinfo .= get_string('gradeforrating', 'peerforum', $grade) . html_writer::empty_tag('br');
        } else {
            $gradeinfo .= get_string('gradeforratinghidden', 'peerforum') . html_writer::empty_tag('br');
        }
    }

    // Item 1 is the whole-peerforum grade.
    if (!empty($grades->items[1]->grades)) {
        $grade = reset($grades->items[1]->grades);
        $gradetime = max($gradetime, grade_get_date_for_user_grade($grade, $user));
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $gradeinfo .= get_string('gradeforwholepeerforum', 'peerforum', $grade) . html_writer::empty_tag('br');
        } else {
            $gradeinfo .= get_string('gradeforwholepeerforumhidden', 'peerforum') . html_writer::empty_tag('br');
        }
    }

    $count = peerforum_count_user_posts($peerforum->id, $user->id);
    if ($count && $count->postcount > 0) {
        $info = get_string("numposts", "peerforum", $count->postcount);
        $time = $count->lastpost;

        if ($gradeinfo) {
            $info .= ', ' . $gradeinfo;
            $time = max($time, $gradetime);
        }

        return (object) [
                'info' => $info,
                'time' => $time,
        ];
    } else if ($gradeinfo) {
        return (object) [
                'info' => $gradeinfo,
                'time' => $gradetime,
        ];
    }

    return null;
}

/**
 * @param object $coure
 * @param object $user
 * @param object $mod
 * @param object $peerforum
 * @global object
 * @global object
 */
function peerforum_user_complete($course, $user, $mod, $peerforum) {
    global $CFG, $USER;
    require_once("$CFG->libdir/gradelib.php");

    $getgradeinfo = function($grades, string $type) use ($course): string {
        global $OUTPUT;

        if (empty($grades)) {
            return '';
        }

        $result = '';
        $grade = reset($grades);
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $result .= $OUTPUT->container(get_string("gradefor{$type}", "peerforum", $grade));
            if ($grade->str_feedback) {
                $result .= $OUTPUT->container(get_string('feedback') . ': ' . $grade->str_feedback);
            }
        } else {
            $result .= $OUTPUT->container(get_string("gradefor{$type}hidden", "peerforum"));
        }

        return $result;
    };

    $grades = grade_get_grades($course->id, 'mod', 'peerforum', $peerforum->id, $user->id);

    // Item 0 is the rating.
    if (!empty($grades->items[0]->grades)) {
        echo $getgradeinfo($grades->items[0]->grades, 'rating');
    }

    // Item 1 is the whole-peerforum grade.
    if (!empty($grades->items[1]->grades)) {
        echo $getgradeinfo($grades->items[1]->grades, 'wholepeerforum');
    }

    if ($posts = peerforum_get_user_posts($peerforum->id, $user->id)) {
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
        $context = context_module::instance($cm->id);
        $discussions = peerforum_get_user_involved_discussions($peerforum->id, $user->id);
        $posts = array_filter($posts, function($post) use ($discussions) {
            return isset($discussions[$post->discussion]);
        });
        $entityfactory = mod_peerforum\local\container::get_entity_factory();
        $rendererfactory = mod_peerforum\local\container::get_renderer_factory();
        $postrenderer = $rendererfactory->get_posts_renderer();

        echo $postrenderer->render(
                $USER,
                [$peerforum->id => $entityfactory->get_peerforum_from_stdclass($peerforum, $context, $cm, $course)],
                array_map(function($discussion) use ($entityfactory) {
                    return $entityfactory->get_discussion_from_stdclass($discussion);
                }, $discussions),
                array_map(function($post) use ($entityfactory) {
                    return $entityfactory->get_post_from_stdclass($post);
                }, $posts)
        );
    } else {
        echo "<p>" . get_string("noposts", "peerforum") . "</p>";
    }
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function peerforum_filter_user_groups_discussions() {
    throw new coding_exception('peerforum_filter_user_groups_discussions() can not be used any more and is obsolete.');
}

/**
 * Returns whether the discussion group is visible by the current user or not.
 *
 * @param cm_info $cm The discussion course module
 * @param int $discussiongroupid The discussion groupid
 * @return bool
 * @since Moodle 2.8, 2.7.1, 2.6.4
 */
function peerforum_is_user_group_discussion(cm_info $cm, $discussiongroupid) {

    if ($discussiongroupid == -1 || $cm->effectivegroupmode != SEPARATEGROUPS) {
        return true;
    }

    if (isguestuser()) {
        return false;
    }

    if (has_capability('moodle/site:accessallgroups', context_module::instance($cm->id)) ||
            in_array($discussiongroupid, $cm->get_modinfo()->get_groups($cm->groupingid))) {
        return true;
    }

    return false;
}

/**
 * @deprecated since Moodle 3.3, when the block_course_overview block was removed.
 */
function peerforum_print_overview() {
    throw new coding_exception('peerforum_print_overview() can not be used any more and is obsolete.');
}

/**
 * Given a course and a date, prints a summary of all the new
 * messages posted in the course since that date
 *
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool success
 * @uses VISIBLEGROUPS
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 */
function peerforum_print_recent_activity($course, $viewfullnames, $timestart) {
    global $USER, $DB, $OUTPUT;

    // do not use log table if possible, it may be huge and is expensive to join with other tables

    $allnamefields = user_picture::fields('u', null, 'duserid');
    if (!$posts = $DB->get_records_sql("SELECT p.*,
                                              f.course, f.type AS peerforumtype, f.name AS peerforumname, f.intro, f.introformat, f.duedate,
                                              f.cutoffdate, f.assessed AS peerforumassessed, f.assesstimestart, f.assesstimefinish,
                                              f.scale, f.grade_peerforum, f.maxbytes, f.maxattachments, f.forcesubscribe,
                                              f.trackingtype, f.rsstype, f.rssarticles, f.timemodified, f.warnafter, f.blockafter,
                                              f.blockperiod, f.completiondiscussions, f.completionreplies, f.completionposts,
                                              f.displaywordcount, f.lockdiscussionafter, f.grade_peerforum_notify,
                                              d.name AS discussionname, d.firstpost, d.userid AS discussionstarter,
                                              d.assessed AS discussionassessed, d.timemodified, d.usermodified, d.peerforum, d.groupid,
                                              d.timestart, d.timeend, d.pinned, d.timelocked,
                                              $allnamefields
                                         FROM {peerforum_posts} p
                                              JOIN {peerforum_discussions} d ON d.id = p.discussion
                                              JOIN {peerforum} f             ON f.id = d.peerforum
                                              JOIN {user} u              ON u.id = p.userid
                                        WHERE p.created > ? AND f.course = ? AND p.deleted <> 1
                                     ORDER BY p.id ASC", array($timestart, $course->id))) { // order by initial posting date
        return false;
    }

    $modinfo = get_fast_modinfo($course);

    $strftimerecent = get_string('strftimerecent');

    $managerfactory = mod_peerforum\local\container::get_manager_factory();
    $entityfactory = mod_peerforum\local\container::get_entity_factory();

    $discussions = [];
    $capmanagers = [];
    $printposts = [];
    foreach ($posts as $post) {
        if (!isset($modinfo->instances['peerforum'][$post->peerforum])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['peerforum'][$post->peerforum];
        if (!$cm->uservisible) {
            continue;
        }

        // Get the discussion. Cache if not yet available.
        if (!isset($discussions[$post->discussion])) {
            // Build the discussion record object from the post data.
            $discussionrecord = (object) [
                    'id' => $post->discussion,
                    'course' => $post->course,
                    'peerforum' => $post->peerforum,
                    'name' => $post->discussionname,
                    'firstpost' => $post->firstpost,
                    'userid' => $post->discussionstarter,
                    'groupid' => $post->groupid,
                    'assessed' => $post->discussionassessed,
                    'timemodified' => $post->timemodified,
                    'usermodified' => $post->usermodified,
                    'timestart' => $post->timestart,
                    'timeend' => $post->timeend,
                    'pinned' => $post->pinned,
                    'timelocked' => $post->timelocked
            ];
            // Build the discussion entity from the factory and cache it.
            $discussions[$post->discussion] = $entityfactory->get_discussion_from_stdclass($discussionrecord);
        }
        $discussionentity = $discussions[$post->discussion];

        // Get the capability manager. Cache if not yet available.
        if (!isset($capmanagers[$post->peerforum])) {
            $context = context_module::instance($cm->id);
            $coursemodule = $cm->get_course_module_record();
            // Build the peerforum record object from the post data.
            $peerforumrecord = (object) [
                    'id' => $post->peerforum,
                    'course' => $post->course,
                    'type' => $post->peerforumtype,
                    'name' => $post->peerforumname,
                    'intro' => $post->intro,
                    'introformat' => $post->introformat,
                    'duedate' => $post->duedate,
                    'cutoffdate' => $post->cutoffdate,
                    'assessed' => $post->peerforumassessed,
                    'assesstimestart' => $post->assesstimestart,
                    'assesstimefinish' => $post->assesstimefinish,
                    'scale' => $post->scale,
                    'grade_peerforum' => $post->grade_peerforum,
                    'maxbytes' => $post->maxbytes,
                    'maxattachments' => $post->maxattachments,
                    'forcesubscribe' => $post->forcesubscribe,
                    'trackingtype' => $post->trackingtype,
                    'rsstype' => $post->rsstype,
                    'rssarticles' => $post->rssarticles,
                    'timemodified' => $post->timemodified,
                    'warnafter' => $post->warnafter,
                    'blockafter' => $post->blockafter,
                    'blockperiod' => $post->blockperiod,
                    'completiondiscussions' => $post->completiondiscussions,
                    'completionreplies' => $post->completionreplies,
                    'completionposts' => $post->completionposts,
                    'displaywordcount' => $post->displaywordcount,
                    'lockdiscussionafter' => $post->lockdiscussionafter,
                    'grade_peerforum_notify' => $post->grade_peerforum_notify
            ];
            // Build the peerforum entity from the factory.
            $peerforumentity = $entityfactory->get_peerforum_from_stdclass($peerforumrecord, $context, $coursemodule, $course);
            // Get the capability manager of this peerforum and cache it.
            $capmanagers[$post->peerforum] = $managerfactory->get_capability_manager($peerforumentity);
        }
        $capabilitymanager = $capmanagers[$post->peerforum];

        // Get the post entity.
        $postentity = $entityfactory->get_post_from_stdclass($post);

        // Check if the user can view the post.
        if ($capabilitymanager->can_view_post($USER, $discussionentity, $postentity)) {
            $printposts[] = $post;
        }
    }
    unset($posts);

    if (!$printposts) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newpeerforumposts', 'peerforum') . ':', 6);
    $list = html_writer::start_tag('ul', ['class' => 'unlist']);

    foreach ($printposts as $post) {
        $subjectclass = empty($post->parent) ? ' bold' : '';
        $authorhidden = peerforum_is_author_hidden($post, (object) ['type' => $post->peerforumtype]);

        $list .= html_writer::start_tag('li');
        $list .= html_writer::start_div('head');
        $list .= html_writer::div(userdate_htmltime($post->modified, $strftimerecent), 'date');
        if (!$authorhidden) {
            $list .= html_writer::div(fullname($post, $viewfullnames), 'name');
        }
        $list .= html_writer::end_div(); // Head.

        $list .= html_writer::start_div('info' . $subjectclass);
        $discussionurl = new moodle_url('/mod/peerforum/discuss.php', ['d' => $post->discussion]);
        if (!empty($post->parent)) {
            $discussionurl->param('parent', $post->parent);
            $discussionurl->set_anchor('p' . $post->id);
        }
        $post->subject = break_up_long_words(format_string($post->subject, true));
        $list .= html_writer::link($discussionurl, $post->subject, ['rel' => 'bookmark']);
        $list .= html_writer::end_div(); // Info.
        $list .= html_writer::end_tag('li');
    }

    $list .= html_writer::end_tag('ul');
    echo $list;

    return true;
}

/**
 * Update activity grades.
 *
 * @param object $peerforum
 * @param int $userid specific user only, 0 means all
 */
function peerforum_update_grades($peerforum, $userid = 0): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $ratepeergrades = null; $peergradesstudents = null; $peergradesprofessors = null;
    if ($peerforum->peergradeassessed && $peerforum->assessed) {

        $ratepeergrades = peerforum_get_user__grades($peerforum, $userid);

        $peergradesstudents = peerforum_get_user_students_peergrades($peerforum, $userid);

        $peergradesprofessors = peerforum_get_user_professors_peergrades($peerforum, $userid);
    }

    $peerforumgrades = null;
    if ($peerforum->grade_peerforum) {
        $sql = <<<EOF
SELECT
    g.userid,
    0 as datesubmitted,
    g.grade as rawgrade,
    g.timemodified as dategraded
  FROM {peerforum} f
  JOIN {peerforum_grades} g ON g.peerforum = f.id
 WHERE f.id = :peerforumid
EOF;

        $params = [
                'peerforumid' => $peerforum->id,
        ];

        if ($userid) {
            $sql .= " AND g.userid = :userid";
            $params['userid'] = $userid;
        }

        $peerforumgrades = [];
        if ($grades = $DB->get_recordset_sql($sql, $params)) {
            foreach ($grades as $userid => $grade) {
                if ($grade->rawgrade != -1) {
                    $peerforumgrades[$userid] = $grade;
                }
            }
            $grades->close();
        }
    }

    peerforum_grade_item_update($peerforum, $ratepeergrades, $peerforumgrades, $peergradesprofessors, $peergradesstudents);
}

/**
 * Create/update grade items for given peerforum.
 *
 * @param stdClass $peerforum PeerForum object with extra cmidnumber
 * @param mixed $grades Optional array/object of grade(s); 'reset' means reset grades in gradebook
 */
function peerforum_grade_item_update($peerforum, $ratepeergrades = null, $peerforumgrades = null, $peergradesprofessors = null, $peergradesstudents = null) {
    //function peerforum_grade_item_update($peerforum, $peergradesprofessors = null, $peerforumgrades = null, $peergradesstudents = null, $ratepeergrades = null) {
    global $CFG;
    require_once("{$CFG->libdir}/gradelib.php");

    $a = new stdclass();
    $a->peerforumname = clean_param($peerforum->name, PARAM_NOTAGS);

    // Update the peer grade professor.
    $item1 = [
            'itemname' => get_string('gradeitemprofessorpeergrade', 'peerforum', $a),
            'idnumber' => $peerforum->cmidnumber,
    ];

    if (!$peerforum->peergradeassessed || $peerforum->peergradescale == 0) {
        $item1['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->finalgrademode == 1) {
        $item1['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->peergradescale > 0) {
        $item1['gradetype'] = GRADE_TYPE_VALUE;
        $item1['grademax'] = $peerforum->professorpercentage;
        $item1['grademin'] = 0;
    } else if ($peerforum->peergradescale < 0) {
        $item1['gradetype'] = GRADE_TYPE_SCALE;
        $item1['scaleid'] = -$peerforum->peergradescale;
    }

    if ($peergradesprofessors === 'reset') {
        $item1['reset'] = true;
        $peergradesprofessors = null;
    }

    // Itemnumber 3 is the rating.
    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 3, $peergradesprofessors, $item1);

    // Update the peer grade student.
    $item2 = [
            'itemname' => get_string('gradeitemstudentpeergrade', 'peerforum', $a),
            'idnumber' => $peerforum->cmidnumber,
    ];

    if (!$peerforum->peergradeassessed || $peerforum->peergradescale == 0) {
        $item2['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->finalgrademode == 1) {
        $item2['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->peergradescale > 0) {
        $item2['gradetype'] = GRADE_TYPE_VALUE;
        $item2['grademax'] = $peerforum->studentpercentage;
        $item2['grademin'] = 0;
    } else if ($peerforum->peergradescale < 0) {
        $item2['gradetype'] = GRADE_TYPE_SCALE;
        $item2['scaleid'] = -$peerforum->peergradescale;
    }

    if ($peergradesstudents === 'reset') {
        $item2['reset'] = true;
        $peergradesstudents = null;
    }

    // Itemnumber 2 is the student peer grade.
    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 2, $peergradesstudents, $item2);
    
    // Update the rating.
    $item = [
            'itemname' => get_string('gradeitemratepeer', 'peerforum', $a),
            'idnumber' => $peerforum->cmidnumber,
    ];

    if (!$peerforum->assessed || $peerforum->scale == 0) {
        $item['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->scale > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $peerforum->scale;
        $item['grademin'] = 0;
    } else if ($peerforum->scale < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid'] = -$peerforum->scale;
    }

    if ($ratepeergrades === 'reset') {
        $item['reset'] = true;
        $ratepeergrades = null;
    }
    // Itemnumber 0 is the rating.
    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 0, $ratepeergrades, $item);

    // Whole peerforum grade.
    $item = [
            'itemname' => get_string('gradeitemnameforwholepeerforum', 'peerforum', $peerforum),
        // Note: We do not need to store the idnumber here.
    ];

    if (!$peerforum->grade_peerforum) {
        $item['gradetype'] = GRADE_TYPE_NONE;
    } else if ($peerforum->grade_peerforum > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $peerforum->grade_peerforum;
        $item['grademin'] = 0;
    } else if ($peerforum->grade_peerforum < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid'] = $peerforum->grade_peerforum * -1;
    }

    if ($peerforumgrades === 'reset') {
        $item['reset'] = true;
        $peerforumgrades = null;
    }
    // Itemnumber 1 is the whole peerforum grade.
    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 1, $peerforumgrades, $item);
}

/**
 * Delete grade item for given peerforum.
 *
 * @param stdClass $peerforum PeerForum object
 */
function peerforum_grade_item_delete($peerforum) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 0, null, ['deleted' => 1]);
    grade_update('mod/peerforum', $peerforum->course, 'mod', 'peerforum', $peerforum->id, 1, null, ['deleted' => 1]);
}

/**
 * Checks if scale is being used by any instance of peerforum.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any peerforum
 */
function peerforum_scale_used_anywhere(int $scaleid): bool {
    global $DB;

    if (empty($scaleid)) {
        return false;
    }

    return $DB->record_exists('peerforum', ['scale' => $scaleid * -1]);
}

// SQL FUNCTIONS ///////////////////////////////////////////////////////////

/**
 * Gets a post with all info ready for peerforum_print_post
 * Most of these joins are just to get the peerforum id
 *
 * @param int $postid
 * @return mixed array of posts or false
 * @global object
 * @global object
 */
function peerforum_get_post_full($postid) {
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');
    return $DB->get_record_sql("SELECT p.*, d.peerforum, $allnames, u.email, u.picture, u.imagealt
                             FROM {peerforum_posts} p
                                  JOIN {peerforum_discussions} d ON p.discussion = d.id
                                  LEFT JOIN {user} u ON p.userid = u.id
                            WHERE p.id = ?", array($postid));
}

/**
 * Gets all posts in discussion including top parent.
 *
 * @param int $discussionid The Discussion to fetch.
 * @param string $sort The sorting to apply.
 * @param bool $tracking Whether the user tracks this peerforum.
 * @return  array                   The posts in the discussion.
 */
function peerforum_get_all_discussion_posts($discussionid, $sort, $tracking = false) {
    global $CFG, $DB, $USER;

    $tr_sel = "";
    $tr_join = "";
    $params = array();

    if ($tracking) {
        $tr_sel = ", fr.id AS postread";
        $tr_join = "LEFT JOIN {peerforum_read} fr ON (fr.postid = p.id AND fr.userid = ?)";
        $params[] = $USER->id;
    }

    $allnames = get_all_user_name_fields(true, 'u');
    $params[] = $discussionid;
    if (!$posts = $DB->get_records_sql("SELECT p.*, $allnames, u.email, u.picture, u.imagealt $tr_sel
                                     FROM {peerforum_posts} p
                                          LEFT JOIN {user} u ON p.userid = u.id
                                          $tr_join
                                    WHERE p.discussion = ?
                                 ORDER BY $sort", $params)) {
        return array();
    }

    foreach ($posts as $pid => $p) {
        if ($tracking) {
            if (peerforum_tp_is_post_old($p)) {
                $posts[$pid]->postread = true;
            }
        }
        if (!$p->parent) {
            continue;
        }
        if (!isset($posts[$p->parent])) {
            continue; // parent does not exist??
        }
        if (!isset($posts[$p->parent]->children)) {
            $posts[$p->parent]->children = array();
        }
        $posts[$p->parent]->children[$pid] =& $posts[$pid];
    }

    // Start with the last child of the first post.
    $post = &$posts[reset($posts)->id];

    $lastpost = false;
    while (!$lastpost) {
        if (!isset($post->children)) {
            $post->lastpost = true;
            $lastpost = true;
        } else {
            // Go to the last child of this post.
            $post = &$posts[end($post->children)->id];
        }
    }

    return $posts;
}

/**
 * An array of peerforum objects that the user is allowed to read/search through.
 *
 * @param int $userid
 * @param int $courseid if 0, we look for peerforums throughout the whole site.
 * @return array of peerforum objects, or false if no matches
 *         PeerForum objects have the following attributes:
 *         id, type, course, cmid, cmvisible, cmgroupmode, accessallgroups,
 *         viewhiddentimedposts
 * @global object
 * @global object
 * @global object
 */
function peerforum_get_readable_peerforums($userid, $courseid = 0) {

    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/course/lib.php');

    if (!$peerforummod = $DB->get_record('modules', array('name' => 'peerforum'))) {
        print_error('notinstalled', 'peerforum');
    }

    if ($courseid) {
        $courses = $DB->get_records('course', array('id' => $courseid));
    } else {
        // If no course is specified, then the user can see SITE + his courses.
        $courses1 = $DB->get_records('course', array('id' => SITEID));
        $courses2 = enrol_get_users_courses($userid, true, array('modinfo'));
        $courses = array_merge($courses1, $courses2);
    }
    if (!$courses) {
        return array();
    }

    $readablepeerforums = array();

    foreach ($courses as $course) {

        $modinfo = get_fast_modinfo($course);

        if (empty($modinfo->instances['peerforum'])) {
            // hmm, no peerforums?
            continue;
        }

        $coursepeerforums = $DB->get_records('peerforum', array('course' => $course->id));

        foreach ($modinfo->instances['peerforum'] as $peerforumid => $cm) {
            if (!$cm->uservisible or !isset($coursepeerforums[$peerforumid])) {
                continue;
            }
            $context = context_module::instance($cm->id);
            $peerforum = $coursepeerforums[$peerforumid];
            $peerforum->context = $context;
            $peerforum->cm = $cm;

            if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
                continue;
            }

            /// group access
            if (groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS and
                    !has_capability('moodle/site:accessallgroups', $context)) {

                $peerforum->onlygroups = $modinfo->get_groups($cm->groupingid);
                $peerforum->onlygroups[] = -1;
            }

            /// hidden timed discussions
            $peerforum->viewhiddentimedposts = true;
            if (!empty($CFG->peerforum_enabletimedposts)) {
                if (!has_capability('mod/peerforum:viewhiddentimedposts', $context)) {
                    $peerforum->viewhiddentimedposts = false;
                }
            }

            /// qanda access
            if ($peerforum->type == 'qanda'
                    && !has_capability('mod/peerforum:viewqandawithoutposting', $context)) {

                // We need to check whether the user has posted in the qanda peerforum.
                $peerforum->onlydiscussions = array();  // Holds discussion ids for the discussions
                // the user is allowed to see in this peerforum.
                if ($discussionspostedin = peerforum_discussions_user_has_posted_in($peerforum->id, $USER->id)) {
                    foreach ($discussionspostedin as $d) {
                        $peerforum->onlydiscussions[] = $d->id;
                    }
                }
            }

            $readablepeerforums[$peerforum->id] = $peerforum;
        }

        unset($modinfo);

    } // End foreach $courses

    return $readablepeerforums;
}

/**
 * Returns a list of posts found using an array of search terms.
 *
 * @param array $searchterms array of search terms, e.g. word +word -word
 * @param int $courseid if 0, we search through the whole site
 * @param int $limitfrom
 * @param int $limitnum
 * @param int &$totalcount
 * @param string $extrasql
 * @return array|bool Array of posts found or false
 * @global object
 * @global object
 * @global object
 */
function peerforum_search_posts($searchterms, $courseid = 0, $limitfrom = 0, $limitnum = 50,
        &$totalcount, $extrasql = '') {
    global $CFG, $DB, $USER;
    require_once($CFG->libdir . '/searchlib.php');

    $peerforums = peerforum_get_readable_peerforums($USER->id, $courseid);

    if (count($peerforums) == 0) {
        $totalcount = 0;
        return false;
    }

    $now = floor(time() / 60) * 60; // DB Cache Friendly.

    $fullaccess = array();
    $where = array();
    $params = array();

    foreach ($peerforums as $peerforumid => $peerforum) {
        $select = array();

        if (!$peerforum->viewhiddentimedposts) {
            $select[] =
                    "(d.userid = :userid{$peerforumid} OR (d.timestart < :timestart{$peerforumid} AND (d.timeend = 0 OR d.timeend > :timeend{$peerforumid})))";
            $params = array_merge($params,
                    array('userid' . $peerforumid => $USER->id, 'timestart' . $peerforumid => $now,
                            'timeend' . $peerforumid => $now));
        }

        $cm = $peerforum->cm;
        $context = $peerforum->context;

        if ($peerforum->type == 'qanda'
                && !has_capability('mod/peerforum:viewqandawithoutposting', $context)) {
            if (!empty($peerforum->onlydiscussions)) {
                list($discussionid_sql, $discussionid_params) =
                        $DB->get_in_or_equal($peerforum->onlydiscussions, SQL_PARAMS_NAMED, 'qanda' . $peerforumid . '_');
                $params = array_merge($params, $discussionid_params);
                $select[] = "(d.id $discussionid_sql OR p.parent = 0)";
            } else {
                $select[] = "p.parent = 0";
            }
        }

        if (!empty($peerforum->onlygroups)) {
            list($groupid_sql, $groupid_params) =
                    $DB->get_in_or_equal($peerforum->onlygroups, SQL_PARAMS_NAMED, 'grps' . $peerforumid . '_');
            $params = array_merge($params, $groupid_params);
            $select[] = "d.groupid $groupid_sql";
        }

        if ($select) {
            $selects = implode(" AND ", $select);
            $where[] = "(d.peerforum = :peerforum{$peerforumid} AND $selects)";
            $params['peerforum' . $peerforumid] = $peerforumid;
        } else {
            $fullaccess[] = $peerforumid;
        }
    }

    if ($fullaccess) {
        list($fullid_sql, $fullid_params) = $DB->get_in_or_equal($fullaccess, SQL_PARAMS_NAMED, 'fula');
        $params = array_merge($params, $fullid_params);
        $where[] = "(d.peerforum $fullid_sql)";
    }

    $favjoin = "";
    if (in_array('starredonly:on', $searchterms)) {
        $usercontext = context_user::instance($USER->id);
        $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
        list($favjoin, $favparams) = $ufservice->get_join_sql_by_type('mod_peerforum', 'discussions',
                "favourited", "d.id");

        $searchterms = array_values(array_diff($searchterms, array('starredonly:on')));
        $params = array_merge($params, $favparams);
        $extrasql .= " AND favourited.itemid IS NOT NULL AND favourited.itemid != 0";
    }

    $selectdiscussion = "(" . implode(" OR ", $where) . ")";

    $messagesearch = '';
    $searchstring = '';

    // Need to concat these back together for parser to work.
    foreach ($searchterms as $searchterm) {
        if ($searchstring != '') {
            $searchstring .= ' ';
        }
        $searchstring .= $searchterm;
    }

    // We need to allow quoted strings for the search. The quotes *should* be stripped
    // by the parser, but this should be examined carefully for security implications.
    $searchstring = str_replace("\\\"", "\"", $searchstring);
    $parser = new search_parser();
    $lexer = new search_lexer($parser);

    if ($lexer->parse($searchstring)) {
        $parsearray = $parser->get_parsed_array();

        $tagjoins = '';
        $tagfields = [];
        $tagfieldcount = 0;
        if ($parsearray) {
            foreach ($parsearray as $token) {
                if ($token->getType() == TOKEN_TAGS) {
                    for ($i = 0; $i <= substr_count($token->getValue(), ','); $i++) {
                        // Queries can only have a limited number of joins so set a limit sensible users won't exceed.
                        if ($tagfieldcount > 10) {
                            continue;
                        }
                        $tagjoins .= " LEFT JOIN {tag_instance} ti_$tagfieldcount
                                        ON p.id = ti_$tagfieldcount.itemid
                                            AND ti_$tagfieldcount.component = 'mod_peerforum'
                                            AND ti_$tagfieldcount.itemtype = 'peerforum_posts'";
                        $tagjoins .= " LEFT JOIN {tag} t_$tagfieldcount ON t_$tagfieldcount.id = ti_$tagfieldcount.tagid";
                        $tagfields[] = "t_$tagfieldcount.rawname";
                        $tagfieldcount++;
                    }
                }
            }
            list($messagesearch, $msparams) = search_generate_SQL($parsearray, 'p.message', 'p.subject',
                    'p.userid', 'u.id', 'u.firstname',
                    'u.lastname', 'p.modified', 'd.peerforum',
                    $tagfields);

            $params = ($msparams ? array_merge($params, $msparams) : $params);
        }
    }

    $fromsql = "{peerforum_posts} p
                  INNER JOIN {peerforum_discussions} d ON d.id = p.discussion
                  INNER JOIN {user} u ON u.id = p.userid $tagjoins $favjoin";

    $selectsql = ($messagesearch ? $messagesearch . " AND " : "") .
            " p.discussion = d.id
               AND p.userid = u.id
               AND $selectdiscussion
                   $extrasql";

    $countsql = "SELECT COUNT(*)
                   FROM $fromsql
                  WHERE $selectsql";

    $allnames = get_all_user_name_fields(true, 'u');
    $searchsql = "SELECT p.*,
                         d.peerforum,
                         $allnames,
                         u.email,
                         u.picture,
                         u.imagealt
                    FROM $fromsql
                   WHERE $selectsql
                ORDER BY p.modified DESC";

    $totalcount = $DB->count_records_sql($countsql, $params);

    return $DB->get_records_sql($searchsql, $params, $limitfrom, $limitnum);
}

/**
 * Get all the posts for a user in a peerforum suitable for peerforum_print_post
 *
 * @return array
 * @global object
 * @uses CONTEXT_MODULE
 * @global object
 */
function peerforum_get_user_posts($peerforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($peerforumid, $userid);

    if (!empty($CFG->peerforum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('peerforum', $peerforumid);
        if (!has_capability('mod/peerforum:viewhiddentimedposts', context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    $allnames = get_all_user_name_fields(true, 'u');
    return $DB->get_records_sql("SELECT p.*, d.peerforum, $allnames, u.email, u.picture, u.imagealt
                              FROM {peerforum} f
                                   JOIN {peerforum_discussions} d ON d.peerforum = f.id
                                   JOIN {peerforum_posts} p       ON p.discussion = d.id
                                   JOIN {user} u              ON u.id = p.userid
                             WHERE f.id = ?
                                   AND p.userid = ?
                                   $timedsql
                          ORDER BY p.modified ASC", $params);
}

/**
 * Get all the discussions user participated in
 *
 * @param int $peerforumid
 * @param int $userid
 * @return array Array or false
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 */
function peerforum_get_user_involved_discussions($peerforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($peerforumid, $userid);
    if (!empty($CFG->peerforum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('peerforum', $peerforumid);
        if (!has_capability('mod/peerforum:viewhiddentimedposts', context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    return $DB->get_records_sql("SELECT DISTINCT d.*
                              FROM {peerforum} f
                                   JOIN {peerforum_discussions} d ON d.peerforum = f.id
                                   JOIN {peerforum_posts} p       ON p.discussion = d.id
                             WHERE f.id = ?
                                   AND p.userid = ?
                                   $timedsql", $params);
}

/**
 * Get all the posts for a user in a peerforum suitable for peerforum_print_post
 *
 * @param int $peerforumid
 * @param int $userid
 * @return array of counts or false
 * @global object
 * @global object
 */
function peerforum_count_user_posts($peerforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($peerforumid, $userid);
    if (!empty($CFG->peerforum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('peerforum', $peerforumid);
        if (!has_capability('mod/peerforum:viewhiddentimedposts', context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    return $DB->get_record_sql("SELECT COUNT(p.id) AS postcount, MAX(p.modified) AS lastpost
                             FROM {peerforum} f
                                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                                  JOIN {peerforum_posts} p       ON p.discussion = d.id
                                  JOIN {user} u              ON u.id = p.userid
                            WHERE f.id = ?
                                  AND p.userid = ?
                                  $timedsql", $params);
}

/**
 * Given a log entry, return the peerforum post details for it.
 *
 * @param object $log
 * @return array|null
 * @global object
 * @global object
 */
function peerforum_get_post_from_log($log) {
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');
    if ($log->action == "add post") {

        return $DB->get_record_sql("SELECT p.*, f.type AS peerforumtype, d.peerforum, d.groupid, $allnames, u.email, u.picture
                                 FROM {peerforum_discussions} d,
                                      {peerforum_posts} p,
                                      {peerforum} f,
                                      {user} u
                                WHERE p.id = ?
                                  AND d.id = p.discussion
                                  AND p.userid = u.id
                                  AND u.deleted <> '1'
                                  AND f.id = d.peerforum", array($log->info));

    } else if ($log->action == "add discussion") {

        return $DB->get_record_sql("SELECT p.*, f.type AS peerforumtype, d.peerforum, d.groupid, $allnames, u.email, u.picture
                                 FROM {peerforum_discussions} d,
                                      {peerforum_posts} p,
                                      {peerforum} f,
                                      {user} u
                                WHERE d.id = ?
                                  AND d.firstpost = p.id
                                  AND p.userid = u.id
                                  AND u.deleted <> '1'
                                  AND f.id = d.peerforum", array($log->info));
    }
    return null;
}

/**
 * Given a discussion id, return the first post from the discussion
 *
 * @param int $dicsussionid
 * @return array
 * @global object
 * @global object
 */
function peerforum_get_firstpost_from_discussion($discussionid) {
    global $CFG, $DB;

    return $DB->get_record_sql("SELECT p.*
                             FROM {peerforum_discussions} d,
                                  {peerforum_posts} p
                            WHERE d.id = ?
                              AND d.firstpost = p.id ", array($discussionid));
}

/**
 * Returns an array of counts of replies to each discussion
 *
 * @param int $peerforumid
 * @param string $peerforumsort
 * @param int $limit
 * @param int $page
 * @param int $perpage
 * @param boolean $canseeprivatereplies Whether the current user can see private replies.
 * @return  array
 */
function peerforum_count_discussion_replies($peerforumid, $peerforumsort = "", $limit = -1, $page = -1, $perpage = 0,
        $canseeprivatereplies = false) {
    global $CFG, $DB, $USER;

    if ($limit > 0) {
        $limitfrom = 0;
        $limitnum = $limit;
    } else if ($page != -1) {
        $limitfrom = $page * $perpage;
        $limitnum = $perpage;
    } else {
        $limitfrom = 0;
        $limitnum = 0;
    }

    if ($peerforumsort == "") {
        $orderby = "";
        $groupby = "";

    } else {
        $orderby = "ORDER BY $peerforumsort";
        $groupby = ", " . strtolower($peerforumsort);
        $groupby = str_replace('desc', '', $groupby);
        $groupby = str_replace('asc', '', $groupby);
    }

    $params = ['peerforumid' => $peerforumid];

    if (!$canseeprivatereplies) {
        $privatewhere = ' AND (p.privatereplyto = :currentuser1 OR p.userid = :currentuser2 OR p.privatereplyto = 0)';
        $params['currentuser1'] = $USER->id;
        $params['currentuser2'] = $USER->id;
    } else {
        $privatewhere = '';
    }

    if (($limitfrom == 0 and $limitnum == 0) or $peerforumsort == "") {
        $sql = "SELECT p.discussion, COUNT(p.id) AS replies, MAX(p.id) AS lastpostid
                  FROM {peerforum_posts} p
                       JOIN {peerforum_discussions} d ON p.discussion = d.id
                 WHERE p.parent > 0 AND d.peerforum = :peerforumid
                       $privatewhere
              GROUP BY p.discussion";
        return $DB->get_records_sql($sql, $params);

    } else {
        $sql = "SELECT p.discussion, (COUNT(p.id) - 1) AS replies, MAX(p.id) AS lastpostid
                  FROM {peerforum_posts} p
                       JOIN {peerforum_discussions} d ON p.discussion = d.id
                 WHERE d.peerforum = :peerforumid
                       $privatewhere
              GROUP BY p.discussion $groupby $orderby";
        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }
}

/**
 * @param object $peerforum
 * @param object $cm
 * @param object $course
 * @return mixed
 * @global object
 * @global object
 * @staticvar array $cache
 * @global object
 */
function peerforum_count_discussions($peerforum, $cm, $course) {
    global $CFG, $DB, $USER;

    static $cache = array();

    $now = floor(time() / 60) * 60; // DB Cache Friendly.

    $params = array($course->id);

    if (!isset($cache[$course->id])) {
        if (!empty($CFG->peerforum_enabletimedposts)) {
            $timedsql = "AND d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)";
            $params[] = $now;
            $params[] = $now;
        } else {
            $timedsql = "";
        }

        $sql = "SELECT f.id, COUNT(d.id) as dcount
                  FROM {peerforum} f
                       JOIN {peerforum_discussions} d ON d.peerforum = f.id
                 WHERE f.course = ?
                       $timedsql
              GROUP BY f.id";

        if ($counts = $DB->get_records_sql($sql, $params)) {
            foreach ($counts as $count) {
                $counts[$count->id] = $count->dcount;
            }
            $cache[$course->id] = $counts;
        } else {
            $cache[$course->id] = array();
        }
    }

    if (empty($cache[$course->id][$peerforum->id])) {
        return 0;
    }

    $groupmode = groups_get_activity_groupmode($cm, $course);

    if ($groupmode != SEPARATEGROUPS) {
        return $cache[$course->id][$peerforum->id];
    }

    if (has_capability('moodle/site:accessallgroups', context_module::instance($cm->id))) {
        return $cache[$course->id][$peerforum->id];
    }

    require_once($CFG->dirroot . '/course/lib.php');

    $modinfo = get_fast_modinfo($course);

    $mygroups = $modinfo->get_groups($cm->groupingid);

    // add all groups posts
    $mygroups[-1] = -1;

    list($mygroups_sql, $params) = $DB->get_in_or_equal($mygroups);
    $params[] = $peerforum->id;

    if (!empty($CFG->peerforum_enabletimedposts)) {
        $timedsql = "AND d.timestart < $now AND (d.timeend = 0 OR d.timeend > $now)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

    $sql = "SELECT COUNT(d.id)
              FROM {peerforum_discussions} d
             WHERE d.groupid $mygroups_sql AND d.peerforum = ?
                   $timedsql";

    return $DB->get_field_sql($sql, $params);
}

/**
 * Get all discussions in a peerforum
 *
 * @param object $cm
 * @param string $peerforumsort
 * @param bool $fullpost
 * @param int $unused
 * @param int $limit
 * @param bool $userlastmodified
 * @param int $page
 * @param int $perpage
 * @param int $groupid if groups enabled, get discussions for this group overriding the current group.
 *                     Use PEERFORUM_POSTS_ALL_USER_GROUPS for all the user groups
 * @param int $updatedsince retrieve only discussions updated since the given time
 * @return array
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 */
function peerforum_get_discussions($cm, $peerforumsort = "", $fullpost = true, $unused = -1, $limit = -1,
        $userlastmodified = false, $page = -1, $perpage = 0, $groupid = -1,
        $updatedsince = 0) {
    global $CFG, $DB, $USER;

    $timelimit = '';

    $now = floor(time() / 60) * 60;
    $params = array($cm->instance);

    $modcontext = context_module::instance($cm->id);

    if (!has_capability('mod/peerforum:viewdiscussion', $modcontext)) { /// User must have perms to view discussions
        return array();
    }

    if (!empty($CFG->peerforum_enabletimedposts)) { /// Users must fulfill timed posts

        if (!has_capability('mod/peerforum:viewhiddentimedposts', $modcontext)) {
            $timelimit = " AND ((d.timestart <= ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
            if (isloggedin()) {
                $timelimit .= " OR d.userid = ?";
                $params[] = $USER->id;
            }
            $timelimit .= ")";
        }
    }

    if ($limit > 0) {
        $limitfrom = 0;
        $limitnum = $limit;
    } else if ($page != -1) {
        $limitfrom = $page * $perpage;
        $limitnum = $perpage;
    } else {
        $limitfrom = 0;
        $limitnum = 0;
    }

    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode) {

        if (empty($modcontext)) {
            $modcontext = context_module::instance($cm->id);
        }

        // Special case, we received a groupid to override currentgroup.
        if ($groupid > 0) {
            $course = get_course($cm->course);
            if (!groups_group_visible($groupid, $course, $cm)) {
                // User doesn't belong to this group, return nothing.
                return array();
            }
            $currentgroup = $groupid;
        } else if ($groupid === -1) {
            $currentgroup = groups_get_activity_group($cm);
        } else {
            // Get discussions for all groups current user can see.
            $currentgroup = null;
        }

        if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $modcontext)) {
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = ? OR d.groupid = -1)";
                $params[] = $currentgroup;
            } else {
                $groupselect = "";
            }

        } else {
            // Separate groups.

            // Get discussions for all groups current user can see.
            if ($currentgroup === null) {
                $mygroups = array_keys(groups_get_all_groups($cm->course, $USER->id, $cm->groupingid, 'g.id'));
                if (empty($mygroups)) {
                    $groupselect = "AND d.groupid = -1";
                } else {
                    list($insqlgroups, $inparamsgroups) = $DB->get_in_or_equal($mygroups);
                    $groupselect = "AND (d.groupid = -1 OR d.groupid $insqlgroups)";
                    $params = array_merge($params, $inparamsgroups);
                }
            } else if ($currentgroup) {
                $groupselect = "AND (d.groupid = ? OR d.groupid = -1)";
                $params[] = $currentgroup;
            } else {
                $groupselect = "AND d.groupid = -1";
            }
        }
    } else {
        $groupselect = "";
    }
    if (empty($peerforumsort)) {
        $peerforumsort = peerforum_get_default_sort_order();
    }
    if (empty($fullpost)) {
        $postdata = "p.id, p.subject, p.modified, p.discussion, p.userid, p.created";
    } else {
        $postdata = "p.*";
    }

    if (empty($userlastmodified)) {  // We don't need to know this
        $umfields = "";
        $umtable = "";
    } else {
        $umfields = ', ' . get_all_user_name_fields(true, 'um', null, 'um') . ', um.email AS umemail, um.picture AS umpicture,
                        um.imagealt AS umimagealt';
        $umtable = " LEFT JOIN {user} um ON (d.usermodified = um.id)";
    }

    $updatedsincesql = '';
    if (!empty($updatedsince)) {
        $updatedsincesql = 'AND d.timemodified > ?';
        $params[] = $updatedsince;
    }

    $discussionfields = "d.id as discussionid, d.course, d.peerforum, d.name, d.firstpost, d.groupid, d.assessed," .
            " d.timemodified, d.usermodified, d.timestart, d.timeend, d.pinned, d.timelocked";

    $allnames = get_all_user_name_fields(true, 'u');
    $sql = "SELECT $postdata, $discussionfields,
                   $allnames, u.email, u.picture, u.imagealt $umfields
              FROM {peerforum_discussions} d
                   JOIN {peerforum_posts} p ON p.discussion = d.id
                   JOIN {user} u ON p.userid = u.id
                   $umtable
             WHERE d.peerforum = ? AND p.parent = 0
                   $timelimit $groupselect $updatedsincesql
          ORDER BY $peerforumsort, d.id DESC";

    return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
}

/**
 * Gets the neighbours (previous and next) of a discussion.
 *
 * The calculation is based on the timemodified when time modified or time created is identical
 * It will revert to using the ID to sort consistently. This is better tha skipping a discussion.
 *
 * For blog-style peerforums, the calculation is based on the original creation time of the
 * blog post.
 *
 * Please note that this does not check whether or not the discussion passed is accessible
 * by the user, it simply uses it as a reference to find the neighbours. On the other hand,
 * the returned neighbours are checked and are accessible to the current user.
 *
 * @param object $cm The CM record.
 * @param object $discussion The discussion record.
 * @param object $peerforum The peerforum instance record.
 * @return array That always contains the keys 'prev' and 'next'. When there is a result
 *               they contain the record with minimal information such as 'id' and 'name'.
 *               When the neighbour is not found the value is false.
 */
function peerforum_get_discussion_neighbours($cm, $discussion, $peerforum) {
    global $CFG, $DB, $USER;

    if ($cm->instance != $discussion->peerforum or $discussion->peerforum != $peerforum->id or $peerforum->id != $cm->instance) {
        throw new coding_exception('Discussion is not part of the same peerforum.');
    }

    $neighbours = array('prev' => false, 'next' => false);
    $now = floor(time() / 60) * 60;
    $params = array();

    $modcontext = context_module::instance($cm->id);
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm);

    // Users must fulfill timed posts.
    $timelimit = '';
    if (!empty($CFG->peerforum_enabletimedposts)) {
        if (!has_capability('mod/peerforum:viewhiddentimedposts', $modcontext)) {
            $timelimit = ' AND ((d.timestart <= :tltimestart AND (d.timeend = 0 OR d.timeend > :tltimeend))';
            $params['tltimestart'] = $now;
            $params['tltimeend'] = $now;
            if (isloggedin()) {
                $timelimit .= ' OR d.userid = :tluserid';
                $params['tluserid'] = $USER->id;
            }
            $timelimit .= ')';
        }
    }

    // Limiting to posts accessible according to groups.
    $groupselect = '';
    if ($groupmode) {
        if ($groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $modcontext)) {
            if ($currentgroup) {
                $groupselect = 'AND (d.groupid = :groupid OR d.groupid = -1)';
                $params['groupid'] = $currentgroup;
            }
        } else {
            if ($currentgroup) {
                $groupselect = 'AND (d.groupid = :groupid OR d.groupid = -1)';
                $params['groupid'] = $currentgroup;
            } else {
                $groupselect = 'AND d.groupid = -1';
            }
        }
    }

    $params['peerforumid'] = $cm->instance;
    $params['discid1'] = $discussion->id;
    $params['discid2'] = $discussion->id;
    $params['discid3'] = $discussion->id;
    $params['discid4'] = $discussion->id;
    $params['disctimecompare1'] = $discussion->timemodified;
    $params['disctimecompare2'] = $discussion->timemodified;
    $params['pinnedstate1'] = (int) $discussion->pinned;
    $params['pinnedstate2'] = (int) $discussion->pinned;
    $params['pinnedstate3'] = (int) $discussion->pinned;
    $params['pinnedstate4'] = (int) $discussion->pinned;

    $sql = "SELECT d.id, d.name, d.timemodified, d.groupid, d.timestart, d.timeend
              FROM {peerforum_discussions} d
              JOIN {peerforum_posts} p ON d.firstpost = p.id
             WHERE d.peerforum = :peerforumid
               AND d.id <> :discid1
                   $timelimit
                   $groupselect";
    $comparefield = "d.timemodified";
    $comparevalue = ":disctimecompare1";
    $comparevalue2 = ":disctimecompare2";
    if (!empty($CFG->peerforum_enabletimedposts)) {
        // Here we need to take into account the release time (timestart)
        // if one is set, of the neighbouring posts and compare it to the
        // timestart or timemodified of *this* post depending on if the
        // release date of this post is in the future or not.
        // This stops discussions that appear later because of the
        // timestart value from being buried under discussions that were
        // made afterwards.
        $comparefield = "CASE WHEN d.timemodified < d.timestart
                                THEN d.timestart ELSE d.timemodified END";
        if ($discussion->timemodified < $discussion->timestart) {
            // Normally we would just use the timemodified for sorting
            // discussion posts. However, when timed discussions are enabled,
            // then posts need to be sorted base on the later of timemodified
            // or the release date of the post (timestart).
            $params['disctimecompare1'] = $discussion->timestart;
            $params['disctimecompare2'] = $discussion->timestart;
        }
    }
    $orderbydesc = peerforum_get_default_sort_order(true, $comparefield, 'd', false);
    $orderbyasc = peerforum_get_default_sort_order(false, $comparefield, 'd', false);

    if ($peerforum->type === 'blog') {
        $subselect = "SELECT pp.created
                   FROM {peerforum_discussions} dd
                   JOIN {peerforum_posts} pp ON dd.firstpost = pp.id ";

        $subselectwhere1 = " WHERE dd.id = :discid3";
        $subselectwhere2 = " WHERE dd.id = :discid4";

        $comparefield = "p.created";

        $sub1 = $subselect . $subselectwhere1;
        $comparevalue = "($sub1)";

        $sub2 = $subselect . $subselectwhere2;
        $comparevalue2 = "($sub2)";

        $orderbydesc = "d.pinned, p.created DESC";
        $orderbyasc = "d.pinned, p.created ASC";
    }

    $prevsql = $sql . " AND ( (($comparefield < $comparevalue) AND :pinnedstate1 = d.pinned)
                         OR ($comparefield = $comparevalue2 AND (d.pinned = 0 OR d.pinned = :pinnedstate4) AND d.id < :discid2)
                         OR (d.pinned = 0 AND d.pinned <> :pinnedstate2))
                   ORDER BY CASE WHEN d.pinned = :pinnedstate3 THEN 1 ELSE 0 END DESC, $orderbydesc, d.id DESC";

    $nextsql = $sql . " AND ( (($comparefield > $comparevalue) AND :pinnedstate1 = d.pinned)
                         OR ($comparefield = $comparevalue2 AND (d.pinned = 1 OR d.pinned = :pinnedstate4) AND d.id > :discid2)
                         OR (d.pinned = 1 AND d.pinned <> :pinnedstate2))
                   ORDER BY CASE WHEN d.pinned = :pinnedstate3 THEN 1 ELSE 0 END DESC, $orderbyasc, d.id ASC";

    $neighbours['prev'] = $DB->get_record_sql($prevsql, $params, IGNORE_MULTIPLE);
    $neighbours['next'] = $DB->get_record_sql($nextsql, $params, IGNORE_MULTIPLE);
    return $neighbours;
}

/**
 * Get the sql to use in the ORDER BY clause for peerforum discussions.
 *
 * This has the ordering take timed discussion windows into account.
 *
 * @param bool $desc True for DESC, False for ASC.
 * @param string $compare The field in the SQL to compare to normally sort by.
 * @param string $prefix The prefix being used for the discussion table.
 * @param bool $pinned sort pinned posts to the top
 * @return string
 */
function peerforum_get_default_sort_order($desc = true, $compare = 'd.timemodified', $prefix = 'd', $pinned = true) {
    global $CFG;

    if (!empty($prefix)) {
        $prefix .= '.';
    }

    $dir = $desc ? 'DESC' : 'ASC';

    if ($pinned == true) {
        $pinned = "{$prefix}pinned DESC,";
    } else {
        $pinned = '';
    }

    $sort = "{$prefix}timemodified";
    if (!empty($CFG->peerforum_enabletimedposts)) {
        $sort = "CASE WHEN {$compare} < {$prefix}timestart
                 THEN {$prefix}timestart
                 ELSE {$compare}
                 END";
    }
    return "$pinned $sort $dir";
}

/**
 *
 * @param object $cm
 * @return array
 * @global object
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 * @global object
 * @global object
 */
function peerforum_get_discussions_unread($cm) {
    global $CFG, $DB, $USER;

    $now = floor(time() / 60) * 60;
    $cutoffdate = $now - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);

    $params = array();
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm);

    if ($groupmode) {
        $modcontext = context_module::instance($cm->id);

        if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $modcontext)) {
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = :currentgroup OR d.groupid = -1)";
                $params['currentgroup'] = $currentgroup;
            } else {
                $groupselect = "";
            }

        } else {
            //separate groups without access all
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = :currentgroup OR d.groupid = -1)";
                $params['currentgroup'] = $currentgroup;
            } else {
                $groupselect = "AND d.groupid = -1";
            }
        }
    } else {
        $groupselect = "";
    }

    if (!empty($CFG->peerforum_enabletimedposts)) {
        $timedsql = "AND d.timestart < :now1 AND (d.timeend = 0 OR d.timeend > :now2)";
        $params['now1'] = $now;
        $params['now2'] = $now;
    } else {
        $timedsql = "";
    }

    $sql = "SELECT d.id, COUNT(p.id) AS unread
              FROM {peerforum_discussions} d
                   JOIN {peerforum_posts} p     ON p.discussion = d.id
                   LEFT JOIN {peerforum_read} r ON (r.postid = p.id AND r.userid = $USER->id)
             WHERE d.peerforum = {$cm->instance}
                   AND p.modified >= :cutoffdate AND r.id is NULL
                   $groupselect
                   $timedsql
          GROUP BY d.id";
    $params['cutoffdate'] = $cutoffdate;

    if ($unreads = $DB->get_records_sql($sql, $params)) {
        foreach ($unreads as $unread) {
            $unreads[$unread->id] = $unread->unread;
        }
        return $unreads;
    } else {
        return array();
    }
}

/**
 * @param object $cm
 * @return array
 * @global object
 * @uses CONEXT_MODULE
 * @uses VISIBLEGROUPS
 * @global object
 * @global object
 */
function peerforum_get_discussions_count($cm) {
    global $CFG, $DB, $USER;

    $now = floor(time() / 60) * 60;
    $params = array($cm->instance);
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm);

    if ($groupmode) {
        $modcontext = context_module::instance($cm->id);

        if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $modcontext)) {
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = ? OR d.groupid = -1)";
                $params[] = $currentgroup;
            } else {
                $groupselect = "";
            }

        } else {
            //seprate groups without access all
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = ? OR d.groupid = -1)";
                $params[] = $currentgroup;
            } else {
                $groupselect = "AND d.groupid = -1";
            }
        }
    } else {
        $groupselect = "";
    }

    $timelimit = "";

    if (!empty($CFG->peerforum_enabletimedposts)) {

        $modcontext = context_module::instance($cm->id);

        if (!has_capability('mod/peerforum:viewhiddentimedposts', $modcontext)) {
            $timelimit = " AND ((d.timestart <= ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
            if (isloggedin()) {
                $timelimit .= " OR d.userid = ?";
                $params[] = $USER->id;
            }
            $timelimit .= ")";
        }
    }

    $sql = "SELECT COUNT(d.id)
              FROM {peerforum_discussions} d
                   JOIN {peerforum_posts} p ON p.discussion = d.id
             WHERE d.peerforum = ? AND p.parent = 0
                   $groupselect $timelimit";

    return $DB->get_field_sql($sql, $params);
}

// OTHER FUNCTIONS ///////////////////////////////////////////////////////////

/**
 * @param int $courseid
 * @param string $type
 * @global object
 * @global object
 */
function peerforum_get_course_peerforum($courseid, $type) {
    // How to set up special 1-per-course peerforums
    global $CFG, $DB, $OUTPUT, $USER;

    if ($peerforums = $DB->get_records_select("peerforum", "course = ? AND type = ?", array($courseid, $type), "id ASC")) {
        // There should always only be ONE, but with the right combination of
        // errors there might be more.  In this case, just return the oldest one (lowest ID).
        foreach ($peerforums as $peerforum) {
            return $peerforum;   // ie the first one
        }
    }

    // Doesn't exist, so create one now.
    $peerforum = new stdClass();
    $peerforum->course = $courseid;
    $peerforum->type = "$type";
    if (!empty($USER->htmleditor)) {
        $peerforum->introformat = $USER->htmleditor;
    }
    switch ($peerforum->type) {
        case "news":
            $peerforum->name = get_string("namenews", "peerforum");
            $peerforum->intro = get_string("intronews", "peerforum");
            $peerforum->introformat = FORMAT_HTML;
            $peerforum->forcesubscribe = PEERFORUM_FORCESUBSCRIBE;
            $peerforum->assessed = 0;
            if ($courseid == SITEID) {
                $peerforum->name = get_string("sitenews");
                $peerforum->forcesubscribe = 0;
            }
            break;
        case "social":
            $peerforum->name = get_string("namesocial", "peerforum");
            $peerforum->intro = get_string("introsocial", "peerforum");
            $peerforum->introformat = FORMAT_HTML;
            $peerforum->assessed = 0;
            $peerforum->forcesubscribe = 0;
            break;
        case "blog":
            $peerforum->name = get_string('blogpeerforum', 'peerforum');
            $peerforum->intro = get_string('introblog', 'peerforum');
            $peerforum->introformat = FORMAT_HTML;
            $peerforum->assessed = 0;
            $peerforum->forcesubscribe = 0;
            break;
        default:
            echo $OUTPUT->notification("That peerforum type doesn't exist!");
            return false;
            break;
    }

    $peerforum->timemodified = time();
    $peerforum->id = $DB->insert_record("peerforum", $peerforum);

    if (!$module = $DB->get_record("modules", array("name" => "peerforum"))) {
        echo $OUTPUT->notification("Could not find peerforum module!!");
        return false;
    }
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->instance = $peerforum->id;
    $mod->section = 0;
    include_once("$CFG->dirroot/course/lib.php");
    if (!$mod->coursemodule = add_course_module($mod)) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
    $sectionid = course_add_cm_to_section($courseid, $mod->coursemodule, 0);
    return $DB->get_record("peerforum", array("id" => "$peerforum->id"));
}

/**
 * Print a peerforum post
 *
 * @param object $post The post to print.
 * @param object $discussion
 * @param object $peerforum
 * @param object $cm
 * @param object $course
 * @param boolean $ownpost Whether this post belongs to the current user.
 * @param boolean $reply Whether to print a 'reply' link at the bottom of the message.
 * @param boolean $link Just print a shortened version of the post as a link to the full post.
 * @param string $footer Extra stuff to print after the message.
 * @param string $highlight Space-separated list of terms to highlight.
 * @param int $post_read true, false or -99. If we already know whether this user
 *          has read this post, pass that in, otherwise, pass in -99, and this
 *          function will work it out.
 * @param boolean $dummyifcantsee When peerforum_user_can_see_post says that
 *          the current user can't see this post, if this argument is true
 *          (the default) then print a dummy 'you can't see this post' post.
 *          If false, don't output anything at all.
 * @param bool|null $istracked
 * @return void
 * @global object
 * @global object
 * @uses PEERFORUM_MODE_THREADED
 * @uses PORTFOLIO_FORMAT_PLAINHTML
 * @uses PORTFOLIO_FORMAT_FILE
 * @uses PORTFOLIO_FORMAT_RICHHTML
 * @uses PORTFOLIO_ADD_TEXT_LINK
 * @uses CONTEXT_MODULE
 */
function peerforum_print_poste($post, $discussion, $peerforum, &$cm, $course, $ownpost = false, $reply = false, $link = false,
        $footer = "", $highlight = "", $postisread = null, $dummyifcantsee = true, $istracked = null, $return = false,
        $peergrade = true, $showincontext = false, $to_peergrade_block = true, $url_block = null, $actual_page = null) {

    global $USER, $CFG, $OUTPUT, $DB, $PAGE;

    require_once($CFG->libdir . '/filelib.php');

    if ($actual_page != null) {
        $data_page = new stdClass();
        $data_page->id = $post->id;
        $data_page->page = $actual_page;
        $DB->update_record('peerforum_posts', $data_page);
    }

    // get 'page' from url
    $actual_url = $_SERVER['REQUEST_URI'];
    $values = parse_url($actual_url, PHP_URL_QUERY);
    $getvalues = explode('&', $values);

    $currentpage = 0;

    foreach ($getvalues as $i => $values) {
        $val = explode('=', $getvalues[$i]);
        if ($val[0] == 'page') {
            $currentpage = $val[1];
        }
    }

    $peerforum_db = $DB->get_record('peerforum', array('id' => $peerforum->id));

    $allowpeergrade = $peerforum_db->allowpeergrade;

    if ($allowpeergrade) {
        $peergrade = true;
    }
    if (!$allowpeergrade) {
        $peergrade = false;
    }

    // String cache
    static $str;

    $modcontext = context_module::instance($cm->id);

    $post->course = $course->id;
    $post->peerforum = $peerforum->id;
    $post->message =
            file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $modcontext->id, 'mod_peerforum', 'post', $post->id);
    if (!empty($CFG->enableplagiarism)) {
        require_once($CFG->libdir . '/plagiarismlib.php');
        $post->message .= plagiarism_get_links(array('userid' => $post->userid,
                'content' => $post->message,
                'cmid' => $cm->id,
                'course' => $post->course,
                'peerforum' => $post->peerforum));
    }

    // caching
    if (!isset($cm->cache)) {
        $cm->cache = new stdClass;
    }

    if (!isset($cm->cache->caps)) {
        $cm->cache->caps = array();
        $cm->cache->caps['mod/peerforum:viewdiscussion'] = has_capability('mod/peerforum:viewdiscussion', $modcontext);
        $cm->cache->caps['moodle/site:viewfullnames'] = has_capability('moodle/site:viewfullnames', $modcontext);
        $cm->cache->caps['mod/peerforum:editanypost'] = has_capability('mod/peerforum:editanypost', $modcontext);
        $cm->cache->caps['mod/peerforum:splitdiscussions'] = has_capability('mod/peerforum:splitdiscussions', $modcontext);
        $cm->cache->caps['mod/peerforum:deleteownpost'] = has_capability('mod/peerforum:deleteownpost', $modcontext);
        $cm->cache->caps['mod/peerforum:deleteanypost'] = has_capability('mod/peerforum:deleteanypost', $modcontext);
        $cm->cache->caps['mod/peerforum:viewanyratingpeer'] = has_capability('mod/peerforum:viewanyratingpeer', $modcontext);
        $cm->cache->caps['mod/peerforum:exportpost'] = has_capability('mod/peerforum:exportpost', $modcontext);
        $cm->cache->caps['mod/peerforum:exportownpost'] = has_capability('mod/peerforum:exportownpost', $modcontext);
    }

    if (!isset($cm->uservisible)) {
        $cm->uservisible = \core_availability\info_module::is_user_visible($cm, 0, false);
    }

    if ($istracked && is_null($postisread)) {
        $postisread = peerforum_tp_is_post_read($USER->id, $post);
    }

    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
        $output = '';
        if (!$dummyifcantsee) {
            if ($return) {
                return $output;
            }
            echo $output;
            return;
        }
        $output .= html_writer::tag('a', '', array('id' => 'p' . $post->id));
        $output .= html_writer::start_tag('div', array('class' => 'peerforumpost clearfix',
                'role' => 'region',
                'aria-label' => get_string('hiddenpeerforumpost', 'peerforum')));
        $output .= html_writer::start_tag('div', array('class' => 'row header'));
        $output .= html_writer::tag('div', '', array('class' => 'left picture')); // Picture
        if ($post->parent) {
            $output .= html_writer::start_tag('div', array('class' => 'topic'));
        } else {
            $output .= html_writer::start_tag('div', array('class' => 'topic starter'));
        }
        $output .= html_writer::tag('div', get_string('peerforumsubjecthidden', 'peerforum'), array('class' => 'subject',
                'role' => 'header')); // Subject.
        $output .= html_writer::tag('div', get_string('peerforumauthorhidden', 'peerforum'), array('class' => 'author',
                'role' => 'header')); // Author.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div'); // row
        $output .= html_writer::start_tag('div', array('class' => 'row'));
        $output .= html_writer::tag('div', '&nbsp;', array('class' => 'left side')); // Groups
        $output .= html_writer::tag('div', get_string('peerforumbodyhidden', 'peerforum'), array('class' => 'content')); // Content
        $output .= html_writer::end_tag('div'); // row
        $output .= html_writer::end_tag('div'); // peerforumpost

        if ($return) {
            return $output;
        }
        echo $output;
        return;
    }

    if (empty($str)) {
        $str = new stdClass;
        $str->edit = get_string('edit', 'peerforum');
        $str->delete = get_string('delete', 'peerforum');
        $str->reply = get_string('reply', 'peerforum');
        $str->parent = get_string('parent', 'peerforum');
        $str->pruneheading = get_string('pruneheading', 'peerforum');
        $str->prune = get_string('prune', 'peerforum');
        $str->displaymode = get_user_preferences('peerforum_displaymode', $CFG->peerforum_displaymode);
        $str->markread = get_string('markread', 'peerforum');
        $str->markunread = get_string('markunread', 'peerforum');
        $str->peergrade = get_string('peergrade', 'peerforum');
        $str->post = get_string('showpost', 'peerforum');
    }

    $discussionlink = new moodle_url('/mod/peerforum/discuss.php', array('d' => $post->discussion));

    // Build an object that represents the posting user
    $postuser = new stdClass;
    $postuserfields = explode(',', user_picture::fields());
    $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
    $postuser->id = $post->userid;
    $postuser->fullname = fullname($postuser, $cm->cache->caps['moodle/site:viewfullnames']);
    $postuser->profilelink = new moodle_url('/user/view.php', array('id' => $post->userid, 'course' => $course->id));

    // Prepare the groups the posting user belongs to
    if (isset($cm->cache->usersgroups)) {
        $groups = array();
        if (isset($cm->cache->usersgroups[$post->userid])) {
            foreach ($cm->cache->usersgroups[$post->userid] as $gid) {
                $groups[$gid] = $cm->cache->groups[$gid];
            }
        }
    } else {
        $groups = groups_get_all_groups($course->id, $post->userid, $cm->groupingid);
    }

    // Prepare the attachements for the post, files then images
    list($attachments, $attachedimages) = peerforum_print_attachments($post, $cm, 'separateimages');

    // Determine if we need to shorten this post
    $shortenpost = ($link && (strlen(strip_tags($post->message)) > $CFG->peerforum_longpost));

    // Prepare an array of commands
    $commands = array();

    // SPECIAL CASE: The front page can display a news item post to non-logged in users.
    // Don't display the mark read / unread controls in this case.
    if ($istracked && $CFG->peerforum_usermarksread && isloggedin()) {
        $url = new moodle_url($discussionlink, array('postid' => $post->id, 'mark' => 'unread'));
        $text = $str->markunread;
        if (!$postisread) {
            $url->param('mark', 'read');
            $text = $str->markread;
        }
        if ($str->displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p' . $post->id);
        }
        $commands[] = array('url' => $url, 'text' => $text);
    }

    // Zoom in to the parent specifically
    if ($post->parent) {
        $url = new moodle_url($discussionlink);
        if ($str->displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p' . $post->parent);
        }
        $commands[] = array('url' => $url, 'text' => $str->parent);
    }

    // Show post in context
    if ($showincontext) {
        $url = new moodle_url($discussionlink);
        if ($str->displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->id);
        } else {
            $url->set_anchor('p' . $post->id);
        }
        $commands[] = array('url' => $url, 'text' => $str->post);
    }

    // Hack for allow to edit news posts those are not displayed yet until they are displayed
    $age = time() - $post->created;
    if (!$post->parent && $peerforum->type == 'news' && $discussion->timestart > time()) {
        $age = 0;
    }

    if ($peerforum->type == 'single' and $discussion->firstpost == $post->id) {
        if (has_capability('moodle/course:manageactivities', $modcontext)) {
            // The first post in single simple is the peerforum description.
            $commands[] = array('url' => new moodle_url('/course/modedit.php',
                    array('update' => $cm->id, 'sesskey' => sesskey(), 'return' => 1)), 'text' => $str->edit);
        }
    } else if (($ownpost && $age < $CFG->maxeditingtime) || $cm->cache->caps['mod/peerforum:editanypost']) {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php', array('edit' => $post->id)), 'text' => $str->edit);
    }

    if ($cm->cache->caps['mod/peerforum:splitdiscussions'] && $post->parent && $peerforum->type != 'single') {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php', array('prune' => $post->id)), 'text' => $str->prune,
                'title' => $str->pruneheading);
    }

    if ($peerforum->type == 'single' and $discussion->firstpost == $post->id) {
        // Do not allow deleting of first post in single simple type.
    } else if (($ownpost && $age < $CFG->maxeditingtime && $cm->cache->caps['mod/peerforum:deleteownpost']) ||
            $cm->cache->caps['mod/peerforum:deleteanypost']) {
        $commands[] =
                array('url' => new moodle_url('/mod/peerforum/post.php', array('delete' => $post->id)), 'text' => $str->delete);
    }

    //if ($reply) {
    //    $commands[] = array('url'=>new moodle_url('/mod/peerforum/post.php#mformpeerforum', array('reply'=>$post->id)), 'text'=>$str->reply);
    //}
    if ($reply && !$showincontext) {
        $commands[] = array('url' => new moodle_url('/mod/peerforum/post.php#mformpeerforum',
                array('reply' => $post->id, 'page' => $currentpage)), 'text' => $str->reply);
    }

    if ($allowpeergrade) {
        $admins = get_admins();
        $isadmin = false;

        foreach ($admins as $admin) {
            if ($post->userid == $admin->id) {
                $isadmin = true;
                break;
            }
        }
        if (!($isadmin) && ($USER->id != $post->userid)) {
            $coursecontext = context_course::instance($course->id, MUST_EXIST);
        }
    }

    if ($CFG->enableportfolios &&
            ($cm->cache->caps['mod/peerforum:exportpost'] || ($ownpost && $cm->cache->caps['mod/peerforum:exportownpost']))) {
        $p = array('postid' => $post->id);
        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('peerforum_portfolio_caller', array('postid' => $post->id), 'mod_peerforum');
        if (empty($attachments)) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }

        $porfoliohtml = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
        if (!empty($porfoliohtml)) {
            $commands[] = $porfoliohtml;
        }
    }
    // Finished building commands

    // Begin output

    $output = '';

    if ($istracked) {
        if ($postisread) {
            $peerforumpostclass = ' read';
        } else {
            $peerforumpostclass = ' unread';
            $output .= html_writer::tag('a', '', array('name' => 'unread'));
        }
    } else {
        // ignore trackign status if not tracked or tracked param missing
        $peerforumpostclass = '';
    }

    $topicclass = '';
    if (empty($post->parent)) {
        $topicclass = ' firstpost starter';
    }

    if (!empty($post->lastpost)) {
        $peerforumpostclass .= ' lastpost';
    }

    $postbyuser = new stdClass;
    $postbyuser->post = $post->subject;
    $postbyuser->user = $postuser->fullname;
    $discussionbyuser = get_string('postbyuser', 'peerforum', $postbyuser);
    $output .= html_writer::tag('a', '', array('id' => 'p' . $post->id));
    $output .= html_writer::start_tag('div', array('class' => 'peerforumpost clearfix' . $peerforumpostclass . $topicclass,
            'role' => 'region',
            'aria-label' => $discussionbyuser));
    $output .= html_writer::start_tag('div', array('class' => 'row header clearfix'));
    $output .= html_writer::start_tag('div', array('class' => 'left picture'));
    $output .= $OUTPUT->user_picture($postuser, array('courseid' => $course->id));
    $output .= html_writer::end_tag('div');

    $output .= html_writer::start_tag('div', array('class' => 'topic' . $topicclass));

    $postsubject = $post->subject;
    if (empty($post->subjectnoformat)) {
        $postsubject = format_string($postsubject);
    }
    $output .= html_writer::tag('div', $postsubject, array('class' => 'subject',
            'role' => 'heading',
            'aria-level' => '2'));

    $by = new stdClass();
    $by->name = html_writer::link($postuser->profilelink, $postuser->fullname);
    $by->date = userdate($post->modified);
    $output .= html_writer::tag('div', get_string('bynameondate', 'peerforum', $by), array('class' => 'author',
            'role' => 'heading',
            'aria-level' => '2'));

    $output .= html_writer::end_tag('div'); //topic
    $output .= html_writer::end_tag('div'); //row

    $output .= html_writer::start_tag('div', array('class' => 'row maincontent clearfix'));
    $output .= html_writer::start_tag('div', array('class' => 'left'));

    $groupoutput = '';
    if ($groups) {
        $groupoutput = print_group_picture($groups, $course->id, false, true, true);
    }
    if (empty($groupoutput)) {
        $groupoutput = '&nbsp;';
    }
    $output .= html_writer::tag('div', $groupoutput, array('class' => 'grouppictures'));

    $output .= html_writer::end_tag('div'); //left side
    $output .= html_writer::start_tag('div', array('class' => 'no-overflow'));

    if ($peerforum->hidereplies) {
        if (can_see_reply($post, $peerforum)) {
            $output .= html_writer::start_tag('div', array('class' => 'content'));
            if (!empty($attachments)) {
                $output .= html_writer::tag('div', $attachments, array('class' => 'attachments'));
            }

            $options = new stdClass;
            $options->para = false;
            $options->trusted = $post->messagetrust;
            $options->context = $modcontext;
            if ($shortenpost) {
                // Prepare shortened version by filtering the text then shortening it.
                $postclass = 'shortenedpost';
                $postcontent = format_text($post->message, $post->messageformat, $options);
                $postcontent = shorten_text($postcontent, $CFG->peerforum_shortpost);
                $postcontent .= html_writer::link($discussionlink, get_string('readtherest', 'peerforum'));
                $postcontent .= html_writer::tag('div', '(' . get_string('numwords', 'moodle', count_words($post->message)) . ')',
                        array('class' => 'post-word-count'));
            } else {
                // Prepare whole post
                $postclass = 'fullpost';
                $postcontent = format_text($post->message, $post->messageformat, $options, $course->id);
                if (!empty($highlight)) {
                    $postcontent = highlight($highlight, $postcontent);
                }
                if (!empty($peerforum->displaywordcount)) {
                    $postcontent .= html_writer::tag('div', get_string('numwords', 'moodle', count_words($post->message)),
                            array('class' => 'post-word-count'));
                }
                $postcontent .= html_writer::tag('div', $attachedimages, array('class' => 'attachedimages'));
            }

            // Output the post content
            $output .= html_writer::tag('div', $postcontent, array('class' => 'posting ' . $postclass));
            $output .= html_writer::end_tag('div'); // Content

        } else {
            $output .= html_writer::tag('div', "Reply not available yet.", array('class' => 'content')); // Content
        }
    }

    $output .= html_writer::end_tag('div'); // Content mask
    $output .= html_writer::end_tag('div'); // Row

    $output .= html_writer::start_tag('div', array('class' => 'row side'));
    $output .= html_writer::tag('div', '&nbsp;', array('class' => 'left'));
    $output .= html_writer::start_tag('div', array('class' => 'options clearfix'));

    if (!empty($attachments)) {
        $output .= html_writer::tag('div', $attachments, array('class' => 'attachments'));
    }

    if (has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
        if ($peerforum_db->showpostid) {
            print_object('Post id: ' . $post->id);
        }
    }

    //fixes extra line on teacher post
    $student = is_user_student($post->userid);
    if ($student) {
        $output .= html_writer::tag('br', '');
    }

    // Output ratingpeers
    if (!empty($post->ratingpeer)) {
        $renderer = $PAGE->get_renderer('mod_peerforum');
        $post->ratingpeer->to_peergrade_block = $to_peergrade_block;
        $post->ratingpeer->peerforum = $post->peerforum;
        $post->ratingpeer->userid = $postuser->id;
        $output .= html_writer::tag('div', $renderer->render_ratingpeer($post->ratingpeer),
                array('class' => 'peerforum-post-ratingpeer'));
        if (has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
            $output .= html_writer::tag('br', ''); // Should produce <br />
        }
    }

    // Output peergrades PEERGRADE
    if (!empty($post->peergrade)) {
        $post->peergrade->to_peergrade_block = $to_peergrade_block;
        $post->peergrade->returnurl = $url_block;
        $post->peergrade->peerforum = $post->peerforum;
        $post->peergrade->userid = $postuser->id;

        $renderer = $PAGE->get_renderer('mod_peerforum');
        $output .= html_writer::tag('div', $renderer->render_peergrade($post->peergrade),
                array('class' => 'peerforum-post-peergrade'));
    }

    // Output the commands
    $commandhtml = array();
    foreach ($commands as $command) {
        if (is_array($command)) {
            $commandhtml[] = html_writer::link($command['url'], $command['text']);
        } else {
            $commandhtml[] = $command;
        }
    }
    $output .= html_writer::tag('div', implode(' | ', $commandhtml), array('class' => 'commands'));

    // Output link to post if required
    if ($link && peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext)) {
        if ($post->replies == 1) {
            $replystring = get_string('repliesone', 'peerforum', $post->replies);
        } else {
            $replystring = get_string('repliesmany', 'peerforum', $post->replies);
        }
        if (!empty($discussion->unread) && $discussion->unread !== '-') {
            $replystring .= ' <span class="sep">/</span> <span class="unread">';
            if ($discussion->unread == 1) {
                $replystring .= get_string('unreadpostsone', 'peerforum');
            } else {
                $replystring .= get_string('unreadpostsnumber', 'peerforum', $discussion->unread);
            }
            $replystring .= '</span>';
        }

        $output .= html_writer::start_tag('div', array('class' => 'link'));
        $output .= html_writer::link($discussionlink, get_string('discussthistopic', 'peerforum'));
        $output .= '&nbsp;(' . $replystring . ')';
        $output .= html_writer::end_tag('div'); // link
    }

    // Output footer if required
    if ($footer) {
        $output .= html_writer::tag('div', $footer, array('class' => 'footer'));
    }

    // Close remaining open divs
    $output .= html_writer::end_tag('div'); // content
    $output .= html_writer::end_tag('div'); // row
    $output .= html_writer::end_tag('div'); // peerforumpost

    // Mark the peerforum post as read if required
    if ($istracked && !$CFG->peerforum_usermarksread && !$postisread) {
        peerforum_tp_mark_post_read($USER->id, $post, $peerforum->id);
    }

    if ($return) {
        return $output;
    }
    echo $output;
    return;
}

/**
 * Return ratingpeer related permissions
 *
 * @param string $options the context id
 * @return array an associative array of the user's ratingpeer permissions
 */
function peerforum_ratingpeer_permissions($contextid, $component, $ratingpeerarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_peerforum' || $ratingpeerarea != 'post') {
        // We don't know about this component/ratingpeerarea so just return null to get the
        // default restrictive permissions.
        return null;
    }
    return array(
            'view' => has_capability('mod/peerforum:viewratingpeer', $context),
            'viewany' => has_capability('mod/peerforum:viewanyratingpeer', $context),
            'viewall' => has_capability('mod/peerforum:viewallratingpeer', $context),
            'ratepeer' => has_capability('mod/peerforum:ratepeer', $context)
    );
}

/**
 * Validates a submitted ratingpeer
 *
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            component => The component for this module - should always be mod_peerforum [required]
 *            ratingpeerarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int the scale from which the user can select a ratingpeer. Used for bounds checking. [required]
 *            ratingpeer => int the submitted ratingpeer [required]
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratingpeers. 0 to
 *         update all. [required] aggregation => int the aggregation method to apply when calculating grades ie
 *         RATING_AGGREGATE_AVERAGE [required]
 * @return boolean true if the ratingpeer is valid. Will throw ratingpeer_exception if not
 */
function peerforum_ratingpeer_validate($params) {
    global $DB, $USER;

    // Check the component is mod_peerforum
    if ($params['component'] != 'mod_peerforum') {
        throw new ratingpeer_exception('invalidcomponent');
    }

    // Check the ratingpeerarea is post (the only ratingpeer area in peerforum)
    if ($params['ratingpeerarea'] != 'post') {
        throw new ratingpeer_exception('invalidratingpeerarea');
    }

    // Check the ratedpeeruserid is not the current user .. you can't ratepeer your own posts
    if ($params['ratedpeeruserid'] == $USER->id) {
        throw new ratingpeer_exception('nopermissiontoratepeer');
    }

    // Fetch all the related records ... we need to do this anyway to call peerforum_user_can_see_post
    $post = $DB->get_record('peerforum_posts', array('id' => $params['itemid'], 'userid' => $params['ratedpeeruserid']), '*',
            MUST_EXIST);
    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Make sure the context provided is the context of the peerforum
    if ($context->id != $params['context']->id) {
        throw new ratingpeer_exception('invalidcontext');
    }

    if ($peerforum->scale != $params['scaleid']) {
        //the scale being submitted doesnt match the one in the database
        throw new ratingpeer_exception('invalidscaleid');
    }

    // check the item we're ratingpeer was created in the assessable time window
    if (!empty($peerforum->assesstimestart) && !empty($peerforum->assesstimefinish)) {
        if ($post->created < $peerforum->assesstimestart || $post->created > $peerforum->assesstimefinish) {
            throw new ratingpeer_exception('notavailable');
        }
    }

    //check that the submitted ratingpeer is valid for the scale

    // lower limit
    if ($params['ratingpeer'] < 0 && $params['ratingpeer'] != RATINGPEER_UNSET_RATINGPEER) {
        throw new ratingpeer_exception('invalidnum');
    }

    // upper limit
    if ($peerforum->scale < 0) {
        //its a custom scale
        $scalerecord = $DB->get_record('scale', array('id' => -$peerforum->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['ratingpeer'] > count($scalearray)) {
                throw new ratingpeer_exception('invalidnum');
            }
        } else {
            throw new ratingpeer_exception('invalidscaleid');
        }
    } else if ($params['ratingpeer'] > $peerforum->scale) {
        //if its numeric and submitted ratingpeer is above maximum
        throw new ratingpeer_exception('invalidnum');
    }

    // Make sure groups allow this user to see the item they're ratingpeer
    if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
        if (!groups_group_exists($discussion->groupid)) { // Can't find group
            throw new ratingpeer_exception('cannotfindgroup');//something is wrong
        }

        if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // do not allow ratingpeer of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
            throw new ratingpeer_exception('notmemberofgroup');
        }
    }

    // perform some final capability checks
    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, $USER, $cm)) {
        throw new ratingpeer_exception('nopermissiontoratepeer');
    }

    return true;
}

/**
 * Can the current user see ratingpeers for a given itemid?
 *
 * @param array $params submitted data
 *            contextid => int contextid [required]
 *            component => The component for this module - should always be mod_peerforum [required]
 *            ratingpeerarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int scale id [optional]
 * @return bool
 * @throws coding_exception
 * @throws ratingpeer_exception
 */
function mod_peerforum_ratingpeer_can_see_item_ratingpeers($params) {
    global $DB, $USER;

    // Check the component is mod_peerforum.
    if (!isset($params['component']) || $params['component'] != 'mod_peerforum') {
        throw new ratingpeer_exception('invalidcomponent');
    }

    // Check the ratingpeerarea is post (the only ratingpeer area in peerforum).
    if (!isset($params['ratingpeerarea']) || $params['ratingpeerarea'] != 'post') {
        throw new ratingpeer_exception('invalidratingpeerarea');
    }

    if (!isset($params['itemid'])) {
        throw new ratingpeer_exception('invaliditemid');
    }

    $post = $DB->get_record('peerforum_posts', array('id' => $params['itemid']), '*', MUST_EXIST);
    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);

    // Perform some final capability checks.
    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, $USER, $cm)) {
        return false;
    }

    return true;
}

/**
 * This function prints the overview of a discussion in the peerforum listing.
 * It needs some discussion information and some post information, these
 * happen to be combined for efficiency in the $post parameter by the function
 * that calls this one: peerforum_print_latest_discussions()
 *
 * @param object $post The post object (passed by reference for speed).
 * @param object $peerforum The peerforum object.
 * @param int $group Current group.
 * @param string $datestring Format to use for the dates.
 * @param boolean $cantrack Is tracking enabled for this peerforum.
 * @param boolean $peerforumtracked Is the user tracking this peerforum.
 * @param boolean $canviewparticipants True if user has the viewparticipants permission for this course
 * @param boolean $canviewhiddentimedposts True if user has the viewhiddentimedposts permission for this peerforum
 * @global object
 * @global object
 */
function peerforum_print_discussion_header(&$post, $peerforum, $group = -1, $datestring = "",
        $cantrack = true, $peerforumtracked = true, $canviewparticipants = true, $modcontext = null,
        $canviewhiddentimedposts = false) {

    global $COURSE, $USER, $CFG, $OUTPUT, $PAGE;

    static $rowcount;
    static $strmarkalldread;

    if (empty($modcontext)) {
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
        $modcontext = context_module::instance($cm->id);
    }

    if (!isset($rowcount)) {
        $rowcount = 0;
        $strmarkalldread = get_string('markalldread', 'peerforum');
    } else {
        $rowcount = ($rowcount + 1) % 2;
    }

    $post->subject = format_string($post->subject, true);

    $canviewfullnames = has_capability('moodle/site:viewfullnames', $modcontext);
    $timeddiscussion = !empty($CFG->peerforum_enabletimedposts) && ($post->timestart || $post->timeend);
    $timedoutsidewindow = '';
    if ($timeddiscussion && ($post->timestart > time() || ($post->timeend != 0 && $post->timeend < time()))) {
        $timedoutsidewindow = ' dimmed_text';
    }

    echo "\n\n";
    echo '<tr class="discussion r' . $rowcount . $timedoutsidewindow . '">';

    $topicclass = 'topic starter';
    if (PEERFORUM_DISCUSSION_PINNED == $post->pinned) {
        $topicclass .= ' pinned';
    }
    echo '<td class="' . $topicclass . '">';
    if (PEERFORUM_DISCUSSION_PINNED == $post->pinned) {
        echo $OUTPUT->pix_icon('i/pinned', get_string('discussionpinned', 'peerforum'), 'mod_peerforum');
    }
    $canalwaysseetimedpost = $USER->id == $post->userid || $canviewhiddentimedposts;
    if ($timeddiscussion && $canalwaysseetimedpost) {
        echo $PAGE->get_renderer('mod_peerforum')->timed_discussion_tooltip($post, empty($timedoutsidewindow));
    }

    echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . '">' . $post->subject . '</a>';
    echo "</td>\n";

    // Picture
    $postuser = new stdClass();
    $postuserfields = explode(',', user_picture::fields());
    $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
    $postuser->id = $post->userid;
    echo '<td class="author">';
    echo '<div class="media">';
    echo '<span class="float-left">';
    echo $OUTPUT->user_picture($postuser, array('courseid' => $peerforum->course));
    echo '</span>';
    // User name
    echo '<div class="media-body">';
    $fullname = fullname($postuser, $canviewfullnames);
    echo '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $post->userid . '&amp;course=' . $peerforum->course . '">' .
            $fullname .
            '</a>';
    echo '</div>';
    echo '</div>';
    echo "</td>\n";

    // Group picture
    if ($group !== -1) {  // Groups are active - group is a group data object or NULL
        echo '<td class="picture group">';
        if (!empty($group->picture) and empty($group->hidepicture)) {
            if ($canviewparticipants && $COURSE->groupmode) {
                $picturelink = true;
            } else {
                $picturelink = false;
            }
            print_group_picture($group, $peerforum->course, false, false, $picturelink);
        } else if (isset($group->id)) {
            if ($canviewparticipants && $COURSE->groupmode) {
                echo '<a href="' . $CFG->wwwroot . '/user/index.php?id=' . $peerforum->course . '&amp;group=' . $group->id . '">' .
                        $group->name . '</a>';
            } else {
                echo $group->name;
            }
        }
        echo "</td>\n";
    }

    if (has_capability('mod/peerforum:viewdiscussion', $modcontext)) {   // Show the column with replies
        echo '<td class="replies">';
        echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . '">';
        echo $post->replies . '</a>';
        echo "</td>\n";

        if ($cantrack) {
            echo '<td class="replies">';
            if ($peerforumtracked) {
                if ($post->unread > 0) {
                    echo '<span class="unread">';
                    echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . '#unread">';
                    echo $post->unread;
                    echo '</a>';
                    echo '<a title="' . $strmarkalldread . '" href="' . $CFG->wwwroot . '/mod/peerforum/markposts.php?f=' .
                            $peerforum->id . '&amp;d=' . $post->discussion .
                            '&amp;mark=read&amp;return=/mod/peerforum/view.php">' . $OUTPUT->pix_icon('t/markasread', $strmarkalldread) . '</a>';
                    /*echo '<a title="' . $strmarkalldread . '" href="' . $CFG->wwwroot . '/mod/peerforum/markposts.php?f=' .
                            $peerforum->id . '&amp;d=' . $post->discussion .
                            '&amp;mark=read&amp;return=/mod/peerforum/view.php&amp;sesskey=' .
                            sesskey() . '">' . $OUTPUT->pix_icon('t/markasread', $strmarkalldread) . '</a>';*/
                    echo '</span>';
                } else {
                    echo '<span class="read">';
                    echo $post->unread;
                    echo '</span>';
                }
            } else {
                echo '<span class="read">';
                echo '-';
                echo '</span>';
            }
            echo "</td>\n";
        }
    }

    echo '<td class="lastpost">';
    $usedate = (empty($post->timemodified)) ? $post->created : $post->timemodified;
    $parenturl = '';
    $usermodified = new stdClass();
    $usermodified->id = $post->usermodified;
    $usermodified = username_load_fields_from_object($usermodified, $post, 'um');

    // In QA peerforums we check that the user can view participants.
    if ($peerforum->type !== 'qanda' || $canviewparticipants) {
        echo '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $post->usermodified . '&amp;course=' . $peerforum->course . '">' .
                fullname($usermodified, $canviewfullnames) . '</a><br />';
        $parenturl = (empty($post->lastpostid)) ? '' : '&amp;parent=' . $post->lastpostid;
    }

    echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/discuss.php?d=' . $post->discussion . $parenturl . '">' .
            userdate_htmltime($usedate, $datestring) . '</a>';
    echo "</td>\n";

    // is_guest should be used here as this also checks whether the user is a guest in the current course.
    // Guests and visitors cannot subscribe - only enrolled users.
    if ((!is_guest($modcontext, $USER) && isloggedin()) && has_capability('mod/peerforum:viewdiscussion', $modcontext)) {
        // Discussion subscription.
        if (\mod_peerforum\subscriptions::is_subscribable($peerforum)) {
            echo '<td class="discussionsubscription">';
            echo peerforum_get_discussion_subscription_icon($peerforum, $post->discussion);
            echo '</td>';
        }
    }

    //Display link to access training pages
    if ($peerforum->training) {
        echo '<td class="discussiontraining">';
        echo '<a href="' . $CFG->wwwroot . '/peergrading/training/' . $post->subject . '.html">' . $post->subject . '</a>';
        echo "</td>\n";
    }

    echo "</tr>\n\n";

}

/**
 * Return the markup for the discussion subscription toggling icon.
 *
 * @param stdClass $peerforum The peerforum object.
 * @param int $discussionid The discussion to create an icon for.
 * @return string The generated markup.
 */
function peerforum_get_discussion_subscription_icon($peerforum, $discussionid, $returnurl = null, $includetext = false) {
    global $USER, $OUTPUT, $PAGE;

    if ($returnurl === null && $PAGE->url) {
        $returnurl = $PAGE->url->out();
    }

    $o = '';
    $subscriptionstatus = \mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforum, $discussionid);
    $subscriptionlink = new moodle_url('/mod/peerforum/subscribe.php', array(
            'sesskey' => sesskey(),
            'id' => $peerforum->id,
            'd' => $discussionid,
            'returnurl' => $returnurl,
    ));

    if ($includetext) {
        $o .= $subscriptionstatus ? get_string('subscribed', 'mod_peerforum') : get_string('notsubscribed', 'mod_peerforum');
    }

    if ($subscriptionstatus) {
        $output = $OUTPUT->pix_icon('t/subscribed', get_string('clicktounsubscribe', 'peerforum'), 'mod_peerforum');
        if ($includetext) {
            $output .= get_string('subscribed', 'mod_peerforum');
        }

        return html_writer::link($subscriptionlink, $output, array(
                'title' => get_string('clicktounsubscribe', 'peerforum'),
                'class' => 'discussiontoggle btn btn-link',
                'data-peerforumid' => $peerforum->id,
                'data-discussionid' => $discussionid,
                'data-includetext' => $includetext,
        ));

    } else {
        $output = $OUTPUT->pix_icon('t/unsubscribed', get_string('clicktosubscribe', 'peerforum'), 'mod_peerforum');
        if ($includetext) {
            $output .= get_string('notsubscribed', 'mod_peerforum');
        }

        return html_writer::link($subscriptionlink, $output, array(
                'title' => get_string('clicktosubscribe', 'peerforum'),
                'class' => 'discussiontoggle btn btn-link',
                'data-peerforumid' => $peerforum->id,
                'data-discussionid' => $discussionid,
                'data-includetext' => $includetext,
        ));
    }
}

/**
 * Return a pair of spans containing classes to allow the subscribe and
 * unsubscribe icons to be pre-loaded by a browser.
 *
 * @return string The generated markup
 */
function peerforum_get_discussion_subscription_icon_preloaders() {
    $o = '';
    $o .= html_writer::span('&nbsp;', 'preload-subscribe');
    $o .= html_writer::span('&nbsp;', 'preload-unsubscribe');
    return $o;
}

/**
 * Print the drop down that allows the user to select how they want to have
 * the discussion displayed.
 *
 * @param int $id peerforum id if $peerforumtype is 'single',
 *              discussion id for any other peerforum type
 * @param mixed $mode peerforum layout mode
 * @param string $peerforumtype optional
 */
function peerforum_print_mode_form($id, $mode, $peerforumtype = '') {
    global $OUTPUT;
    $useexperimentalui = get_user_preferences('peerforum_useexperimentalui', false);
    if ($peerforumtype == 'single') {
        $select = new single_select(
                new moodle_url("/mod/peerforum/view.php",
                        array('f' => $id)),
                'mode',
                peerforum_get_layout_modes($useexperimentalui),
                $mode,
                null,
                "mode"
        );
        $select->set_label(get_string('displaymode', 'peerforum'), array('class' => 'accesshide'));
        $select->class = "peerforummode";
    } else {
        $select = new single_select(
                new moodle_url("/mod/peerforum/discuss.php",
                        array('d' => $id)),
                'mode',
                peerforum_get_layout_modes($useexperimentalui),
                $mode,
                null,
                "mode"
        );
        $select->set_label(get_string('displaymode', 'peerforum'), array('class' => 'accesshide'));
    }
    echo $OUTPUT->render($select);
}

/**
 * @param object $course
 * @param string $search
 * @return string
 * @global object
 */
function peerforum_search_form($course, $search = '') {
    global $CFG, $PAGE;
    $peerforumsearch = new \mod_peerforum\output\quick_search_form($course->id, $search);
    $output = $PAGE->get_renderer('mod_peerforum');
    return $output->render($peerforumsearch);
}

/**
 * @global object
 * @global object
 */
function peerforum_set_return() {
    global $CFG, $SESSION;

    if (!isset($SESSION->fromdiscussion)) {
        $referer = get_local_referer(false);
        // If the referer is NOT a login screen then save it.
        if (!strncasecmp("$CFG->wwwroot/login", $referer, 300)) {
            $SESSION->fromdiscussion = $referer;
        }
    }
}

/**
 * @param string|\moodle_url $default
 * @return string
 * @global object
 */
function peerforum_go_back_to($default) {
    global $SESSION;

    if (!empty($SESSION->fromdiscussion)) {
        $returnto = $SESSION->fromdiscussion;
        unset($SESSION->fromdiscussion);
        return $returnto;
    } else {
        return $default;
    }
}

/**
 * Given a discussion object that is being moved to $peerforumto,
 * this function checks all posts in that discussion
 * for attachments, and if any are found, these are
 * moved to the new peerforum directory.
 *
 * @param object $discussion
 * @param int $peerforumfrom source peerforum id
 * @param int $peerforumto target peerforum id
 * @return bool success
 * @global object
 */
function peerforum_move_attachments($discussion, $peerforumfrom, $peerforumto) {
    global $DB;

    $fs = get_file_storage();

    $newcm = get_coursemodule_from_instance('peerforum', $peerforumto);
    $oldcm = get_coursemodule_from_instance('peerforum', $peerforumfrom);

    $newcontext = context_module::instance($newcm->id);
    $oldcontext = context_module::instance($oldcm->id);

    // loop through all posts, better not use attachment flag ;-)
    if ($posts = $DB->get_records('peerforum_posts', array('discussion' => $discussion->id), '', 'id, attachment')) {
        foreach ($posts as $post) {
            $fs->move_area_files_to_new_context($oldcontext->id,
                    $newcontext->id, 'mod_peerforum', 'post', $post->id);
            $attachmentsmoved = $fs->move_area_files_to_new_context($oldcontext->id,
                    $newcontext->id, 'mod_peerforum', 'attachment', $post->id);
            if ($attachmentsmoved > 0 && $post->attachment != '1') {
                // Weird - let's fix it
                $post->attachment = '1';
                $DB->update_record('peerforum_posts', $post);
            } else if ($attachmentsmoved == 0 && $post->attachment != '') {
                // Weird - let's fix it
                $post->attachment = '';
                $DB->update_record('peerforum_posts', $post);
            }
        }
    }

    return true;
}

/**
 * Returns attachments as formated text/html optionally with separate images
 *
 * @param object $post
 * @param object $cm
 * @param string $type html/text/separateimages
 * @return mixed string or array of (html text withouth images and image HTML)
 * @global object
 * @global object
 * @global object
 */
function peerforum_print_attachments($post, $cm, $type) {
    global $CFG, $DB, $USER, $OUTPUT;

    if (empty($post->attachment)) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    if (!in_array($type, array('separateimages', 'html', 'text'))) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    if (!$context = context_module::instance($cm->id)) {
        return $type !== 'separateimages' ? '' : array('', '');
    }
    $strattachment = get_string('attachment', 'peerforum');

    $fs = get_file_storage();

    $imagereturn = '';
    $output = '';

    $canexport = !empty($CFG->enableportfolios) && (has_capability('mod/peerforum:exportpost', $context) ||
                    ($post->userid == $USER->id && has_capability('mod/peerforum:exportownpost', $context)));

    if ($canexport) {
        require_once($CFG->libdir . '/portfoliolib.php');
    }

    // We retrieve all files according to the time that they were created.  In the case that several files were uploaded
    // at the sametime (e.g. in the case of drag/drop upload) we revert to using the filename.
    $files = $fs->get_area_files($context->id, 'mod_peerforum', 'attachment', $post->id, "filename", false);
    if ($files) {
        if ($canexport) {
            $button = new portfolio_add_button();
        }
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage =
                    $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
            $path = file_encode_url($CFG->wwwroot . '/pluginfile.php',
                    '/' . $context->id . '/mod_peerforum/attachment/' . $post->id . '/' . $filename);

            if ($type == 'html') {
                $output .= "<a href=\"$path\">$iconimage</a> ";
                $output .= "<a href=\"$path\">" . s($filename) . "</a>";
                if ($canexport) {
                    $button->set_callback_options('peerforum_portfolio_caller',
                            array('postid' => $post->id, 'attachment' => $file->get_id()), 'mod_peerforum');
                    $button->set_format_by_file($file);
                    $output .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                }
                $output .= "<br />";

            } else if ($type == 'text') {
                $output .= "$strattachment " . s($filename) . ":\n$path\n";

            } else { //'returnimages'
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
                    // Image attachments don't get printed as links
                    $imagereturn .= "<br /><img src=\"$path\" alt=\"\" />";
                    if ($canexport) {
                        $button->set_callback_options('peerforum_portfolio_caller',
                                array('postid' => $post->id, 'attachment' => $file->get_id()), 'mod_peerforum');
                        $button->set_format_by_file($file);
                        $imagereturn .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                    }
                } else {
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">" . s($filename) . "</a>", FORMAT_HTML, array('context' => $context));
                    if ($canexport) {
                        $button->set_callback_options('peerforum_portfolio_caller',
                                array('postid' => $post->id, 'attachment' => $file->get_id()), 'mod_peerforum');
                        $button->set_format_by_file($file);
                        $output .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                    }
                    $output .= '<br />';
                }
            }

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $output .= plagiarism_get_links(array('userid' => $post->userid,
                        'file' => $file,
                        'cmid' => $cm->id,
                        'course' => $cm->course,
                        'peerforum' => $cm->instance));
                $output .= '<br />';
            }
        }
    }

    if ($type !== 'separateimages') {
        return $output;

    } else {
        return array($output, $imagereturn);
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Lists all browsable file areas
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 * @package  mod_peerforum
 * @category files
 */
function peerforum_get_file_areas($course, $cm, $context) {
    return array(
            'attachment' => get_string('areaattachment', 'mod_peerforum'),
            'post' => get_string('areapost', 'mod_peerforum'),
    );
}

/**
 * File browsing support for peerforum module.
 *
 * @param stdClass $browser file browser object
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module
 * @param stdClass $context context module
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 * @package  mod_peerforum
 * @category files
 */
function peerforum_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    // filearea must contain a real area
    if (!isset($areas[$filearea])) {
        return null;
    }

    // Note that peerforum_user_can_see_post() additionally allows access for parent roles
    // and it explicitly checks qanda peerforum type, too. One day, when we stop requiring
    // course:managefiles, we will need to extend this.
    if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
        return null;
    }

    if (is_null($itemid)) {
        require_once($CFG->dirroot . '/mod/peerforum/locallib.php');
        return new peerforum_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
    }

    static $cached = array();
    // $cached will store last retrieved post, discussion and peerforum. To make sure that the cache
    // is cleared between unit tests we check if this is the same session
    if (!isset($cached['sesskey']) || $cached['sesskey'] != sesskey()) {
        $cached = array('sesskey' => sesskey());
    }

    if (isset($cached['post']) && $cached['post']->id == $itemid) {
        $post = $cached['post'];
    } else if ($post = $DB->get_record('peerforum_posts', array('id' => $itemid))) {
        $cached['post'] = $post;
    } else {
        return null;
    }

    if (isset($cached['discussion']) && $cached['discussion']->id == $post->discussion) {
        $discussion = $cached['discussion'];
    } else if ($discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion))) {
        $cached['discussion'] = $discussion;
    } else {
        return null;
    }

    if (isset($cached['peerforum']) && $cached['peerforum']->id == $cm->instance) {
        $peerforum = $cached['peerforum'];
    } else if ($peerforum = $DB->get_record('peerforum', array('id' => $cm->instance))) {
        $cached['peerforum'] = $peerforum;
    } else {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($context->id, 'mod_peerforum', $filearea, $itemid, $filepath, $filename))) {
        return null;
    }

    // Checks to see if the user can manage files or is the owner.
    // TODO MDL-33805 - Do not use userid here and move the capability check above.
    if (!has_capability('moodle/course:managefiles', $context) && $storedfile->get_userid() != $USER->id) {
        return null;
    }
    // Make sure groups allow this user to see this file
    if ($discussion->groupid > 0 && !has_capability('moodle/site:accessallgroups', $context)) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS && !groups_is_member($discussion->groupid)) {
            return null;
        }
    }

    // Make sure we're allowed to see it...
    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemid, true, true, false, false);
}

/**
 * Serves the peerforum attachments. Implements needed access control ;-)
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 * @package  mod_peerforum
 * @category files
 */
function peerforum_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $areas = peerforum_get_file_areas($course, $cm, $context);

    // filearea must contain a real area
    if (!isset($areas[$filearea])) {
        return false;
    }

    $postid = (int) array_shift($args);

    if (!$post = $DB->get_record('peerforum_posts', array('id' => $postid))) {
        return false;
    }

    if (!$discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion))) {
        return false;
    }

    if (!$peerforum = $DB->get_record('peerforum', array('id' => $cm->instance))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_peerforum/$filearea/$postid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure groups allow this user to see this file
    if ($discussion->groupid > 0) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS) {
            if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
                return false;
            }
        }
    }

    // Make sure we're allowed to see it...
    if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}

/**
 * If successful, this function returns the name of the file
 *
 * @param object $post is a full post record, including course and peerforum
 * @param object $peerforum
 * @param object $cm
 * @param mixed $mform
 * @param string $unused
 * @return bool
 * @global object
 */
function peerforum_add_attachment($post, $peerforum, $cm, $mform = null, $unused = null) {
    global $DB;

    if (empty($mform)) {
        return false;
    }

    if (empty($post->attachments)) {
        return true;   // Nothing to do
    }

    $context = context_module::instance($cm->id);

    $info = file_get_draft_area_info($post->attachments);
    $present = ($info['filecount'] > 0) ? '1' : '';
    file_save_draft_area_files($post->attachments, $context->id, 'mod_peerforum', 'attachment', $post->id,
            mod_peerforum_post_form::attachment_options($peerforum));

    $DB->set_field('peerforum_posts', 'attachment', $present, array('id' => $post->id));

    return true;
}

/**
 * Add a new post in an existing discussion.
 *
 * @param stdClass $post The post data
 * @param mixed $mform The submitted form
 * @param string $unused
 * @return int
 */
function peerforum_add_new_post($post, $mform, $unused = null) {
    global $USER, $CFG, $DB;

    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion));
    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum));
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
    $context = context_module::instance($cm->id);
    $privatereplyto = 0;

    // Check whether private replies should be enabled for this post.
    if ($post->parent) {
        $parent = $DB->get_record('peerforum_posts', array('id' => $post->parent));

        if (!empty($parent->privatereplyto)) {
            throw new \coding_exception('It should not be possible to reply to a private reply');
        }

        if (!empty($post->isprivatereply) && peerforum_user_can_reply_privately($context, $parent)) {
            $privatereplyto = $parent->userid;
        }
    }

    $post->created = $post->modified = time();
    $post->mailed = PEERFORUM_MAILED_PENDING;
    $post->userid = $USER->id;
    $post->privatereplyto = $privatereplyto;
    $post->attachment = "";
    $post->peergraders = 0;
    if (!isset($post->totalscore)) {
        $post->totalscore = 0;
    }
    if (!isset($post->mailnow)) {
        $post->mailnow = 0;
    }
    if (!isset($post->page)) {
        $post->page = 0;
    }

    \mod_peerforum\local\entities\post::add_message_counts($post);
    $post->id = $DB->insert_record("peerforum_posts", $post);
    $post->message = file_save_draft_area_files($post->itemid, $context->id, 'mod_peerforum', 'post', $post->id,
            mod_peerforum_post_form::editor_options($context, null), $post->message);
    $DB->set_field('peerforum_posts', 'message', $post->message, array('id' => $post->id));
    peerforum_add_attachment($post, $peerforum, $cm, $mform);

    // Update discussion modified date
    $DB->set_field("peerforum_discussions", "timemodified", $post->modified, array("id" => $post->discussion));
    $DB->set_field("peerforum_discussions", "usermodified", $post->userid, array("id" => $post->discussion));

    if (peerforum_tp_can_track_peerforums($peerforum) && peerforum_tp_is_tracked($peerforum)) {
        peerforum_tp_mark_post_read($post->userid, $post);
    }

    if (isset($post->tags)) {
        core_tag_tag::set_item_tags('mod_peerforum', 'peerforum_posts', $post->id, $context, $post->tags);
    }

    // Let Moodle know that assessable content is uploaded (eg for plagiarism detection)
    peerforum_trigger_content_uploaded_event($post, $cm, 'peerforum_add_new_post');

    return $post->id;
}

/**
 * Trigger post updated event.
 *
 * @param object $post peerforum post object
 * @param object $discussion discussion object
 * @param object $context peerforum context object
 * @param object $peerforum peerforum object
 * @return void
 * @since Moodle 3.8
 */
function peerforum_trigger_post_updated_event($post, $discussion, $context, $peerforum) {
    global $USER;

    $params = array(
            'context' => $context,
            'objectid' => $post->id,
            'other' => array(
                    'discussionid' => $discussion->id,
                    'peerforumid' => $peerforum->id,
                    'peerforumtype' => $peerforum->type,
            )
    );

    if ($USER->id !== $post->userid) {
        $params['relateduserid'] = $post->userid;
    }

    $event = \mod_peerforum\event\post_updated::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussion);
    $event->trigger();
}

/**
 * Update a post.
 *
 * @param stdClass $post The post to update
 * @param mixed $mform The submitted form
 * @param string $message
 * @return  bool
 */
function peerforum_update_post($post, $mform, &$message) {
    global $USER, $CFG, $DB;

    $discussion = $DB->get_record('peerforum_discussions', array('id' => $post->discussion));
    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum));
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
    $context = context_module::instance($cm->id);

    $post->modified = time();

    $DB->update_record('peerforum_posts', $post);

    if (!$post->parent) {   // Post is a discussion starter - update discussion title and times too
        $discussion->name = $post->subject;
        $discussion->timestart = $post->timestart;
        $discussion->timeend = $post->timeend;

        if (isset($post->pinned)) {
            $discussion->pinned = $post->pinned;
        }
    }
    $post->message = file_save_draft_area_files($post->itemid, $context->id, 'mod_peerforum', 'post', $post->id,
            mod_peerforum_post_form::editor_options($context, $post->id), $post->message);
    \mod_peerforum\local\entities\post::add_message_counts($post);
    // $DB->update_record('peerforum_posts', $post); Deleted and replaced with (down)
    $DB->set_field('peerforum_posts', 'message', $post->message, array('id' => $post->id));
    // Note: Discussion modified time/user are intentionally not updated, to enable them to track the latest new post.
    $DB->update_record('peerforum_discussions', $discussion);

    peerforum_add_attachment($post, $peerforum, $cm, $mform, $message);

    if ($peerforum->type == 'single' && $post->parent == '0') {
        // Updating first post of single discussion type -> updating peerforum intro.
        $peerforum->intro = $post->message;
        $peerforum->timemodified = time();
        $DB->update_record("peerforum", $peerforum);
    }

    if (isset($post->tags)) {
        core_tag_tag::set_item_tags('mod_peerforum', 'peerforum_posts', $post->id, $context, $post->tags);
    }

    if (peerforum_tp_can_track_peerforums($peerforum) && peerforum_tp_is_tracked($peerforum)) {
        peerforum_tp_mark_post_read($post->userid, $post, $post->peerforum);
    }

    // Let Moodle know that assessable content is uploaded (eg for plagiarism detection)
    peerforum_trigger_content_uploaded_event($post, $cm, 'peerforum_update_post');

    return true;
}

/**
 * Given an object containing all the necessary data,
 * create a new discussion and return the id
 *
 * @param object $post
 * @param mixed $mform
 * @param string $unused
 * @param int $userid
 * @return object
 */
function peerforum_add_discussion($discussion, $mform = null, $unused = null, $userid = null) {
    global $USER, $CFG, $DB;

    $timenow = isset($discussion->timenow) ? $discussion->timenow : time();

    if (is_null($userid)) {
        $userid = $USER->id;
    }

    // The first post is stored as a real post, and linked
    // to from the discuss entry.

    $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum));
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

    $post = new stdClass();
    $post->discussion = 0;
    $post->parent = 0;
    $post->privatereplyto = 0;
    $post->userid = $userid;
    $post->created = $timenow;
    $post->modified = $timenow;
    $post->mailed = PEERFORUM_MAILED_PENDING;
    $post->subject = $discussion->name;
    $post->message = $discussion->message;
    $post->messageformat = $discussion->messageformat;
    $post->messagetrust = $discussion->messagetrust;
    $post->attachments = isset($discussion->attachments) ? $discussion->attachments : null;
    $post->peerforum = $peerforum->id;     // speedup
    $post->course = $peerforum->course; // speedup
    $post->mailnow = $discussion->mailnow;
    $post->peergraders = 0;

    \mod_peerforum\local\entities\post::add_message_counts($post);
    $post->id = $DB->insert_record("peerforum_posts", $post);

    // TODO: Fix the calling code so that there always is a $cm when this function is called
    if (!empty($cm->id) && !empty($discussion->itemid)) {   // In "single simple discussions" this may not exist yet
        $context = context_module::instance($cm->id);
        $text = file_save_draft_area_files($discussion->itemid, $context->id, 'mod_peerforum', 'post', $post->id,
                mod_peerforum_post_form::editor_options($context, null), $post->message);
        $DB->set_field('peerforum_posts', 'message', $text, array('id' => $post->id));
    }

    // Now do the main entry for the discussion, linking to this first post

    $discussion->firstpost = $post->id;
    $discussion->timemodified = $timenow;
    $discussion->usermodified = $post->userid;
    $discussion->userid = $userid;
    $discussion->assessed = 0;

    $post->discussion = $DB->insert_record("peerforum_discussions", $discussion);

    // Finally, set the pointer on the post.
    $DB->set_field("peerforum_posts", "discussion", $post->discussion, array("id" => $post->id));

    if (!empty($cm->id)) {
        peerforum_add_attachment($post, $peerforum, $cm, $mform, $unused);
    }

    if (isset($discussion->tags)) {
        core_tag_tag::set_item_tags('mod_peerforum', 'peerforum_posts', $post->id, context_module::instance($cm->id),
                $discussion->tags);
    }

    if (peerforum_tp_can_track_peerforums($peerforum) && peerforum_tp_is_tracked($peerforum)) {
        peerforum_tp_mark_post_read($post->userid, $post);
    }

    // Let Moodle know that assessable content is uploaded (eg for plagiarism detection)
    if (!empty($cm->id)) {
        peerforum_trigger_content_uploaded_event($post, $cm, 'peerforum_add_discussion');
    }

    return $post->discussion;
}

/**
 * Deletes a discussion and handles all associated cleanup.
 *
 * @param object $discussion Discussion to delete
 * @param bool $fulldelete True when deleting entire peerforum
 * @param object $course Course
 * @param object $cm Course-module
 * @param object $peerforum PeerForum
 * @return bool
 * @global object
 */
function peerforum_delete_discussion($discussion, $fulldelete, $course, $cm, $peerforum) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    $result = true;

    if ($posts = $DB->get_records("peerforum_posts", array("discussion" => $discussion->id))) {
        foreach ($posts as $post) {
            $post->course = $discussion->course;
            $post->peerforum = $discussion->peerforum;
            if (!peerforum_delete_post($post, 'ignore', $course, $cm, $peerforum, $fulldelete)) {
                $result = false;
            }
        }
    }

    peerforum_tp_delete_read_records(-1, -1, $discussion->id);

    // Discussion subscriptions must be removed before discussions because of key constraints.
    $DB->delete_records('peerforum_discussion_subs', array('discussion' => $discussion->id));
    if (!$DB->delete_records("peerforum_discussions", array("id" => $discussion->id))) {
        $result = false;
    }

    // Update completion state if we are tracking completion based on number of posts
    // But don't bother when deleting whole thing
    if (!$fulldelete) {
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC &&
                ($peerforum->completiondiscussions || $peerforum->completionreplies || $peerforum->completionposts)) {
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $discussion->userid);
        }
    }

    $params = array(
            'objectid' => $discussion->id,
            'context' => context_module::instance($cm->id),
            'other' => array(
                    'peerforumid' => $peerforum->id,
            )
    );
    $event = \mod_peerforum\event\discussion_deleted::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussion);
    $event->trigger();

    return $result;
}

/**
 * Deletes a single peerforum post.
 *
 * @param object $post PeerForum post object
 * @param mixed $children Whether to delete children. If false, returns false
 *   if there are any children (without deleting the post). If true,
 *   recursively deletes all children. If set to special value 'ignore', deletes
 *   post regardless of children (this is for use only when deleting all posts
 *   in a disussion).
 * @param object $course Course
 * @param object $cm Course-module
 * @param object $peerforum PeerForum
 * @param bool $skipcompletion True to skip updating completion state if it
 *   would otherwise be updated, i.e. when deleting entire peerforum anyway.
 * @return bool
 * @global object
 */
function peerforum_delete_post($post, $children, $course, $cm, $peerforum, $skipcompletion = false) {
    global $DB, $CFG, $USER;
    require_once($CFG->libdir . '/completionlib.php');

    $context = context_module::instance($cm->id);

    if ($children !== 'ignore' && ($childposts = $DB->get_records('peerforum_posts', array('parent' => $post->id)))) {
        if ($children) {
            foreach ($childposts as $childpost) {
                peerforum_delete_post($childpost, true, $course, $cm, $peerforum, $skipcompletion);
            }
        } else {
            return false;
        }
    }

    // Delete ratingpeers.
    require_once($CFG->dirroot . '/ratingpeer/lib.php');
    $delopt = new stdClass;
    $delopt->contextid = $context->id;
    $delopt->component = 'mod_peerforum';
    $delopt->ratingpeerarea = 'post';
    $delopt->itemid = $post->id;
    $rm = new ratingpeer_manager();
    $rm->delete_ratingpeers($delopt);

    //Delete peergrades.
    $pm = new peergrade_manager();
    $pm->delete_peergrades($delopt);

    // Delete attachments.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_peerforum', 'attachment', $post->id);
    $fs->delete_area_files($context->id, 'mod_peerforum', 'post', $post->id);

    // Delete cached RSS feeds.
    if (!empty($CFG->enablerssfeeds)) {
        require_once($CFG->dirroot . '/mod/peerforum/rsslib.php');
        peerforum_rss_delete_file($peerforum);
    }

    if ($DB->delete_records("peerforum_posts", array("id" => $post->id))) {

        peerforum_tp_delete_read_records(-1, $post->id);

        // Just in case we are deleting the last post
        peerforum_discussion_update_last_post($post->discussion);

        // Update completion state if we are tracking completion based on number of posts
        // But don't bother when deleting whole thing

        if (!$skipcompletion) {
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC &&
                    ($peerforum->completiondiscussions || $peerforum->completionreplies || $peerforum->completionposts)) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $post->userid);
            }
        }

        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array(
                        'discussionid' => $post->discussion,
                        'peerforumid' => $peerforum->id,
                        'peerforumtype' => $peerforum->type,
                )
        );
        $post->deleted = 1;
        if ($post->userid !== $USER->id) {
            $params['relateduserid'] = $post->userid;
        }
        $event = \mod_peerforum\event\post_deleted::create($params);
        $event->add_record_snapshot('peerforum_posts', $post);
        $event->trigger();

        return true;
    }
    return false;
}

/**
 * Sends post content to plagiarism plugin
 *
 * @param object $post PeerForum post object
 * @param object $cm Course-module
 * @param string $name
 * @return bool
 */
function peerforum_trigger_content_uploaded_event($post, $cm, $name) {
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_peerforum', 'attachment', $post->id, "timemodified", false);
    $params = array(
            'context' => $context,
            'objectid' => $post->id,
            'other' => array(
                    'content' => $post->message,
                    'pathnamehashes' => array_keys($files),
                    'discussionid' => $post->discussion,
                    'triggeredfrom' => $name,
            )
    );
    $event = \mod_peerforum\event\assessable_uploaded::create($params);
    $event->trigger();
    return true;
}

/**
 * Given a new post, subscribes or unsubscribes as appropriate.
 * Returns some text which describes what happened.
 *
 * @param object $fromform The submitted form
 * @param stdClass $peerforum The peerforum record
 * @param stdClass $discussion The peerforum discussion record
 * @return string
 */
function peerforum_post_subscription($fromform, $peerforum, $discussion) {
    global $USER;

    if (\mod_peerforum\subscriptions::is_forcesubscribed($peerforum)) {
        return "";
    } else if (\mod_peerforum\subscriptions::subscription_disabled($peerforum)) {
        $subscribed = \mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforum);
        if ($subscribed &&
                !has_capability('moodle/course:manageactivities', context_course::instance($peerforum->course), $USER->id)) {
            // This user should not be subscribed to the peerforum.
            \mod_peerforum\subscriptions::unsubscribe_user($USER->id, $peerforum);
        }
        return "";
    }

    $info = new stdClass();
    $info->name = fullname($USER);
    $info->discussion = format_string($discussion->name);
    $info->peerforum = format_string($peerforum->name);

    if (isset($fromform->discussionsubscribe) && $fromform->discussionsubscribe) {
        if ($result = \mod_peerforum\subscriptions::subscribe_user_to_discussion($USER->id, $discussion)) {
            return html_writer::tag('p', get_string('discussionnowsubscribed', 'peerforum', $info));
        }
    } else {
        if ($result = \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($USER->id, $discussion)) {
            return html_writer::tag('p', get_string('discussionnownotsubscribed', 'peerforum', $info));
        }
    }

    return '';
}

/**
 * Generate and return the subscribe or unsubscribe link for a peerforum.
 *
 * @param object $peerforum the peerforum. Fields used are $peerforum->id and $peerforum->forcesubscribe.
 * @param object $context the context object for this peerforum.
 * @param array $messages text used for the link in its various states
 *      (subscribed, unsubscribed, forcesubscribed or cantsubscribe).
 *      Any strings not passed in are taken from the $defaultmessages array
 *      at the top of the function.
 * @param bool $cantaccessagroup
 * @param bool $unused1
 * @param bool $backtoindex
 * @param array $unused2
 * @return string
 */
function peerforum_get_subscribe_link($peerforum, $context, $messages = array(), $cantaccessagroup = false, $unused1 = true,
        $backtoindex = false, $unused2 = null) {
    global $CFG, $USER, $PAGE, $OUTPUT;
    $defaultmessages = array(
            'subscribed' => get_string('unsubscribe', 'peerforum'),
            'unsubscribed' => get_string('subscribe', 'peerforum'),
            'cantaccessgroup' => get_string('no'),
            'forcesubscribed' => get_string('everyoneissubscribed', 'peerforum'),
            'cantsubscribe' => get_string('disallowsubscribe', 'peerforum')
    );
    $messages = $messages + $defaultmessages;

    if (\mod_peerforum\subscriptions::is_forcesubscribed($peerforum)) {
        return $messages['forcesubscribed'];
    } else if (\mod_peerforum\subscriptions::subscription_disabled($peerforum) &&
            !has_capability('mod/peerforum:managesubscriptions', $context)) {
        return $messages['cantsubscribe'];
    } else if ($cantaccessagroup) {
        return $messages['cantaccessgroup'];
    } else {
        if (!is_enrolled($context, $USER, '', true)) {
            return '';
        }

        $subscribed = \mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforum);
        if ($subscribed) {
            $linktext = $messages['subscribed'];
            $linktitle = get_string('subscribestop', 'peerforum');
        } else {
            $linktext = $messages['unsubscribed'];
            $linktitle = get_string('subscribestart', 'peerforum');
        }

        $options = array();
        if ($backtoindex) {
            $backtoindexlink = '&amp;backtoindex=1';
            $options['backtoindex'] = 1;
        } else {
            $backtoindexlink = '';
        }

        $options['id'] = $peerforum->id;
        $options['sesskey'] = sesskey();
        $url = new moodle_url('/mod/peerforum/subscribe.php', $options);
        return $OUTPUT->single_button($url, $linktext, 'get', array('title' => $linktitle));
    }
}

/**
 * Returns true if user created new discussion already.
 *
 * @param int $peerforumid The peerforum to check for postings
 * @param int $userid The user to check for postings
 * @param int $groupid The group to restrict the check to
 * @return bool
 */
function peerforum_user_has_posted_discussion($peerforumid, $userid, $groupid = null) {
    global $CFG, $DB;

    $sql = "SELECT 'x'
              FROM {peerforum_discussions} d, {peerforum_posts} p
             WHERE d.peerforum = ? AND p.discussion = d.id AND p.parent = 0 AND p.userid = ?";

    $params = [$peerforumid, $userid];

    return $DB->record_exists_sql($sql, $params);
}

/**
 * @param int $peerforumid
 * @param int $userid
 * @return array
 * @global object
 * @global object
 */
function peerforum_discussions_user_has_posted_in($peerforumid, $userid) {
    global $CFG, $DB;

    $haspostedsql = "SELECT d.id AS id,
                            d.*
                       FROM {peerforum_posts} p,
                            {peerforum_discussions} d
                      WHERE p.discussion = d.id
                        AND d.peerforum = ?
                        AND p.userid = ?";

    return $DB->get_records_sql($haspostedsql, array($peerforumid, $userid));
}

/**
 * @param int $peerforumid
 * @param int $did
 * @param int $userid
 * @return bool
 * @global object
 * @global object
 */
function peerforum_user_has_posted($peerforumid, $did, $userid) {
    global $DB;

    if (empty($did)) {
        // posted in any peerforum discussion?
        $sql = "SELECT 'x'
                  FROM {peerforum_posts} p
                  JOIN {peerforum_discussions} d ON d.id = p.discussion
                 WHERE p.userid = :userid AND d.peerforum = :peerforumid";
        return $DB->record_exists_sql($sql, array('peerforumid' => $peerforumid, 'userid' => $userid));
    } else {
        return $DB->record_exists('peerforum_posts', array('discussion' => $did, 'userid' => $userid));
    }
}

/**
 * Returns creation time of the first user's post in given discussion
 *
 * @param int $did Discussion id
 * @param int $userid User id
 * @return int|bool post creation time stamp or return false
 * @global object $DB
 */
function peerforum_get_user_posted_time($did, $userid) {
    global $DB;

    $posttime = $DB->get_field('peerforum_posts', 'MIN(created)', array('userid' => $userid, 'discussion' => $did));
    if (empty($posttime)) {
        return false;
    }
    return $posttime;
}

/**
 * @param object $peerforum
 * @param object $currentgroup
 * @param int $unused
 * @param object $cm
 * @param object $context
 * @return bool
 * @global object
 */
function peerforum_user_can_post_discussion($peerforum, $currentgroup = null, $unused = -1, $cm = null, $context = null) {
    // $peerforum is an object
    global $USER;

    // shortcut - guest and not-logged-in users can not post
    if (isguestuser() or !isloggedin()) {
        return false;
    }

    if (!$cm) {
        debugging('missing cm', DEBUG_DEVELOPER);
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
    }

    if (!$context) {
        $context = context_module::instance($cm->id);
    }

    if (peerforum_is_cutoff_date_reached($peerforum)) {
        if (!has_capability('mod/peerforum:canoverridecutoff', $context)) {
            return false;
        }
    }

    if ($currentgroup === null) {
        $currentgroup = groups_get_activity_group($cm);
    }

    $groupmode = groups_get_activity_groupmode($cm);

    if ($peerforum->type == 'news') {
        $capname = 'mod/peerforum:addnews';
    } else if ($peerforum->type == 'qanda') {
        $capname = 'mod/peerforum:addquestion';
    } else {
        $capname = 'mod/peerforum:startdiscussion';
    }

    if (!has_capability($capname, $context)) {
        return false;
    }

    if ($peerforum->type == 'single') {
        return false;
    }

    if ($peerforum->type == 'eachuser') {
        if (peerforum_user_has_posted_discussion($peerforum->id, $USER->id)) {
            return false;
        }
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return groups_is_member($currentgroup);
    } else {
        // no group membership and no accessallgroups means no new discussions
        // reverted to 1.7 behaviour in 1.9+,  buggy in 1.8.0-1.9.0
        return false;
    }
}

/**
 * This function checks whether the user can reply to posts in a peerforum
 * discussion. Use peerforum_user_can_post_discussion() to check whether the user
 * can start discussions.
 *
 * @param object $peerforum peerforum object
 * @param object $discussion
 * @param object $user
 * @param object $cm
 * @param object $course
 * @param object $context
 * @return bool
 * @global object
 * @global object
 * @uses DEBUG_DEVELOPER
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 */
function peerforum_user_can_post($peerforum, $discussion, $user = null, $cm = null, $course = null, $context = null) {
    global $USER, $DB;
    if (empty($user)) {
        $user = $USER;
    }

    // shortcut - guest and not-logged-in users can not post
    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    if (!isset($discussion->groupid)) {
        debugging('incorrect discussion parameter', DEBUG_DEVELOPER);
        return false;
    }

    if (!$cm) {
        debugging('missing cm', DEBUG_DEVELOPER);
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
    }

    if (!$course) {
        debugging('missing course', DEBUG_DEVELOPER);
        if (!$course = $DB->get_record('course', array('id' => $peerforum->course))) {
            print_error('invalidcourseid');
        }
    }

    if (!$context) {
        $context = context_module::instance($cm->id);
    }

    if (peerforum_is_cutoff_date_reached($peerforum)) {
        if (!has_capability('mod/peerforum:canoverridecutoff', $context)) {
            return false;
        }
    }

    // Check whether the discussion is locked.
    if (peerforum_discussion_is_locked($peerforum, $discussion)) {
        if (!has_capability('mod/peerforum:canoverridediscussionlock', $context)) {
            return false;
        }
    }

    // normal users with temporary guest access can not post, suspended users can not post either
    if (!is_viewing($context, $user->id) and !is_enrolled($context, $user->id, '', true)) {
        return false;
    }

    if ($peerforum->type == 'news') {
        $capname = 'mod/peerforum:replynews';
    } else {
        $capname = 'mod/peerforum:replypost';
    }

    if (!has_capability($capname, $context, $user->id)) {
        return false;
    }

    if (!$groupmode = groups_get_activity_groupmode($cm, $course)) {
        return true;
    }

    if (has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($groupmode == VISIBLEGROUPS) {
        if ($discussion->groupid == -1) {
            // allow students to reply to all participants discussions - this was not possible in Moodle <1.8
            return true;
        }
        return groups_is_member($discussion->groupid);

    } else {
        //separate groups
        if ($discussion->groupid == -1) {
            return false;
        }
        return groups_is_member($discussion->groupid);
    }
}

/**
 * Check to ensure a user can view a timed discussion.
 *
 * @param object $discussion
 * @param object $user
 * @param object $context
 * @return boolean returns true if they can view post, false otherwise
 */
function peerforum_user_can_see_timed_discussion($discussion, $user, $context) {
    global $CFG;

    // Check that the user can view a discussion that is normally hidden due to access times.
    if (!empty($CFG->peerforum_enabletimedposts)) {
        $time = time();
        if (($discussion->timestart != 0 && $discussion->timestart > $time)
                || ($discussion->timeend != 0 && $discussion->timeend < $time)) {
            if (!has_capability('mod/peerforum:viewhiddentimedposts', $context, $user->id)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Check to ensure a user can view a group discussion.
 *
 * @param object $discussion
 * @param object $cm
 * @param object $context
 * @return boolean returns true if they can view post, false otherwise
 */
function peerforum_user_can_see_group_discussion($discussion, $cm, $context) {

    // If it's a grouped discussion, make sure the user is a member.
    if ($discussion->groupid > 0) {
        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode == SEPARATEGROUPS) {
            return groups_is_member($discussion->groupid) || has_capability('moodle/site:accessallgroups', $context);
        }
    }

    return true;
}

/**
 * @param object $peerforum
 * @param object $discussion
 * @param object $context
 * @param object $user
 * @return bool
 * @uses DEBUG_DEVELOPER
 * @global object
 * @global object
 */
function peerforum_user_can_see_discussion($peerforum, $discussion, $context, $user = null) {
    global $USER, $DB;

    if (empty($user) || empty($user->id)) {
        $user = $USER;
    }

    // retrieve objects (yuk)
    if (is_numeric($peerforum)) {
        debugging('missing full peerforum', DEBUG_DEVELOPER);
        if (!$peerforum = $DB->get_record('peerforum', array('id' => $peerforum))) {
            return false;
        }
    }
    if (is_numeric($discussion)) {
        debugging('missing full discussion', DEBUG_DEVELOPER);
        if (!$discussion = $DB->get_record('peerforum_discussions', array('id' => $discussion))) {
            return false;
        }
    }
    if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
        print_error('invalidcoursemodule');
    }

    if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
        return false;
    }

    if (!peerforum_user_can_see_timed_discussion($discussion, $user, $context)) {
        return false;
    }

    if (!peerforum_user_can_see_group_discussion($discussion, $cm, $context)) {
        return false;
    }

    return true;
}

/**
 * Check whether a user can see the specified post.
 *
 * @param \stdClass $peerforum The peerforum to chcek
 * @param \stdClass $discussion The discussion the post is in
 * @param \stdClass $post The post in question
 * @param \stdClass $user The user to test - if not specified, the current user is checked.
 * @param \stdClass $cm The Course Module that the peerforum is in (required).
 * @param bool $checkdeleted Whether to check the deleted flag on the post.
 * @return  bool
 */
function peerforum_user_can_see_post($peerforum, $discussion, $post, $user = null, $cm = null, $checkdeleted = true) {
    global $CFG, $USER, $DB;

    // retrieve objects (yuk)
    if (is_numeric($peerforum)) {
        debugging('missing full peerforum', DEBUG_DEVELOPER);
        if (!$peerforum = $DB->get_record('peerforum', array('id' => $peerforum))) {
            return false;
        }
    }

    if (is_numeric($discussion)) {
        debugging('missing full discussion', DEBUG_DEVELOPER);
        if (!$discussion = $DB->get_record('peerforum_discussions', array('id' => $discussion))) {
            return false;
        }
    }
    if (is_numeric($post)) {
        debugging('missing full post', DEBUG_DEVELOPER);
        if (!$post = $DB->get_record('peerforum_posts', array('id' => $post))) {
            return false;
        }
    }

    if (!isset($post->id) && isset($post->parent)) {
        $post->id = $post->parent;
    }

    if ($checkdeleted && !empty($post->deleted)) {
        return false;
    }

    if (!$cm) {
        debugging('missing cm', DEBUG_DEVELOPER);
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
    }

    // Context used throughout function.
    $modcontext = context_module::instance($cm->id);

    if (empty($user) || empty($user->id)) {
        $user = $USER;
    }

    $canviewdiscussion = (isset($cm->cache) && !empty($cm->cache->caps['mod/peerforum:viewdiscussion']))
            || has_capability('mod/peerforum:viewdiscussion', $modcontext, $user->id);
    if (!$canviewdiscussion && !has_all_capabilities(array('moodle/user:viewdetails', 'moodle/user:readuserposts'),
                    context_user::instance($post->userid))) {
        return false;
    }

    if (!peerforum_post_is_visible_privately($post, $cm)) {
        return false;
    }

    if (isset($cm->uservisible)) {
        if (!$cm->uservisible) {
            return false;
        }
    } else {
        if (!\core_availability\info_module::is_user_visible($cm, $user->id, false)) {
            return false;
        }
    }

    if (!peerforum_user_can_see_timed_discussion($discussion, $user, $modcontext)) {
        return false;
    }

    if (!peerforum_user_can_see_group_discussion($discussion, $cm, $modcontext)) {
        return false;
    }

    if ($peerforum->type == 'qanda') {
        if (has_capability('mod/peerforum:viewqandawithoutposting', $modcontext, $user->id) || $post->userid == $user->id
                || (isset($discussion->firstpost) && $discussion->firstpost == $post->id)) {
            return true;
        }
        $firstpost = peerforum_get_firstpost_from_discussion($discussion->id);
        if ($firstpost->userid == $user->id) {
            return true;
        }
        $userfirstpost = peerforum_get_user_posted_time($discussion->id, $user->id);
        return (($userfirstpost !== false && (time() - $userfirstpost >= $CFG->maxeditingtime)));
    }
    return true;
}

/**
 * Prints the discussion view screen for a peerforum.
 *
 * @param object $course The current course object.
 * @param object $peerforum PeerForum to be printed.
 * @param int $maxdiscussions .
 * @param string $displayformat The display format to use (optional).
 * @param string $sort Sort arguments for database query (optional).
 * @param int $groupmode Group mode of the peerforum (optional).
 * @param void $unused (originally current group)
 * @param int $page Page mode, page to display (optional).
 * @param int $perpage The maximum number of discussions per page(optional)
 * @param boolean $subscriptionstatus Whether the user is currently subscribed to the discussion in some fashion.
 *
 * @global object
 * @global object
 */
function peerforum_print_latest_discussionse($course, $peerforum, $maxdiscussions = -1, $displayformat = 'plain', $sort = '',
        $currentgroup = -1, $groupmode = -1, $page = -1, $perpage = 100, $cm = null) {
    global $CFG, $USER, $OUTPUT;

    if (!$cm) {
        if (!$cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course)) {
            print_error('invalidcoursemodule');
        }
    }
    $context = context_module::instance($cm->id);

    if (empty($sort)) {
        $sort = peerforum_get_default_sort_order();
    }

    $olddiscussionlink = false;

    // Sort out some defaults
    if ($perpage <= 0) {
        $perpage = 0;
        $page = -1;
    }

    if ($maxdiscussions == 0) {
        // all discussions - backwards compatibility
        $page = -1;
        $perpage = 0;
        if ($displayformat == 'plain') {
            $displayformat = 'header';  // Abbreviate display by default
        }

    } else if ($maxdiscussions > 0) {
        $page = -1;
        $perpage = $maxdiscussions;
    }

    $fullpost = false;
    if ($displayformat == 'plain') {
        $fullpost = true;
    }

    // Decide if current user is allowed to see ALL the current discussions or not

    // First check the group stuff
    if ($currentgroup == -1 or $groupmode == -1) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        $currentgroup = groups_get_activity_group($cm);
    }

    $groups = array(); //cache

    // If the user can post discussions, then this is a good place to put the
    // button for it. We do not show the button if we are showing site news
    // and the current user is a guest.

    $canstart = peerforum_user_can_post_discussion($peerforum, $currentgroup, $groupmode, $cm, $context);
    if (!$canstart and $peerforum->type !== 'news') {
        if (isguestuser() or !isloggedin()) {
            $canstart = true;
        }
        if (!is_enrolled($context) and !is_viewing($context)) {
            // allow guests and not-logged-in to see the button - they are prompted to log in after clicking the link
            // normal users with temporary guest access see this button too, they are asked to enrol instead
            // do not show the button to users with suspended enrolments here
            $canstart = enrol_selfenrol_available($course->id);
        }
    }

    if ($canstart) {
        echo '<div class="singlebutton peerforumaddnew">';
        echo "<form id=\"newdiscussionform\" method=\"get\" action=\"$CFG->wwwroot/mod/peerforum/post.php\">";
        echo '<div>';
        echo "<input type=\"hidden\" name=\"peerforum\" value=\"$peerforum->id\" />";
        switch ($peerforum->type) {
            case 'news':
            case 'blog':
                $buttonadd = get_string('addanewtopic', 'peerforum');
                break;
            case 'qanda':
                $buttonadd = get_string('addanewquestion', 'peerforum');
                break;
            default:
                $buttonadd = get_string('addanewdiscussion', 'peerforum');
                break;
        }
        echo '<input type="submit" value="' . $buttonadd . '" />';
        echo '</div>';
        echo '</form>';
        echo "</div>\n";

    } else if (isguestuser() or !isloggedin() or $peerforum->type == 'news' or
            $peerforum->type == 'qanda' and !has_capability('mod/peerforum:addquestion', $context) or
            $peerforum->type != 'qanda' and !has_capability('mod/peerforum:startdiscussion', $context)) {
        // no button and no info

    } else if ($groupmode and !has_capability('moodle/site:accessallgroups', $context)) {
        // inform users why they can not post new discussion
        if (!$currentgroup) {
            echo $OUTPUT->notification(get_string('cannotadddiscussionall', 'peerforum'));
        } else if (!groups_is_member($currentgroup)) {
            echo $OUTPUT->notification(get_string('cannotadddiscussion', 'peerforum'));
        }
    }

    // Get all the recent discussions we're allowed to see

    $getuserlastmodified = ($displayformat == 'header');

    if (!$discussions =
            peerforum_get_discussions($cm, $sort, $fullpost, null, $maxdiscussions, $getuserlastmodified, $page, $perpage)) {
        echo '<div class="peerforumnodiscuss">';
        if ($peerforum->type == 'news') {
            echo '(' . get_string('nonews', 'peerforum') . ')';
        } else if ($peerforum->type == 'qanda') {
            echo '(' . get_string('noquestions', 'peerforum') . ')';
        } else {
            echo '(' . get_string('nodiscussions', 'peerforum') . ')';
        }
        echo "</div>\n";
        return;
    }

    // If we want paging
    if ($page != -1) {
        ///Get the number of discussions found
        $numdiscussions = peerforum_get_discussions_count($cm);

        ///Show the paging bar
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$peerforum->id");
        if ($numdiscussions > 1000) {
            // saves some memory on sites with very large peerforums
            $replies = peerforum_count_discussion_replies($peerforum->id, $sort, $maxdiscussions, $page, $perpage);
        } else {
            $replies = peerforum_count_discussion_replies($peerforum->id);
        }

    } else {
        $replies = peerforum_count_discussion_replies($peerforum->id);

        if ($maxdiscussions > 0 and $maxdiscussions <= count($discussions)) {
            $olddiscussionlink = true;
        }
    }

    $canviewparticipants = has_capability('moodle/course:viewparticipants', $context);
    $canviewhiddentimedposts = has_capability('mod/peerforum:viewhiddentimedposts', $context);

    $strdatestring = get_string('strftimerecentfull');

    // Check if the peerforum is tracked.
    if ($cantrack = peerforum_tp_can_track_peerforums($peerforum)) {
        $peerforumtracked = peerforum_tp_is_tracked($peerforum);
    } else {
        $peerforumtracked = false;
    }

    if ($peerforumtracked) {
        $unreads = peerforum_get_discussions_unread($cm);
    } else {
        $unreads = array();
    }

    if ($displayformat == 'header') {
        echo '<table cellspacing="0" class="peerforumheaderlist">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="header topic" scope="col">' . get_string('discussion', 'peerforum') . '</th>';
        echo '<th class="header author" colspan="2" scope="col">' . get_string('startedby', 'peerforum') . '</th>';
        if ($groupmode > 0) {
            echo '<th class="header group" scope="col">' . get_string('group') . '</th>';
        }
        if (has_capability('mod/peerforum:viewdiscussion', $context)) {
            echo '<th class="header replies" scope="col">' . get_string('replies', 'peerforum') . '</th>';
            // If the peerforum can be tracked, display the unread column.
            if ($cantrack) {
                echo '<th class="header replies" scope="col">' . get_string('unread', 'peerforum');
                if ($peerforumtracked) {
                    echo '<a title="' . get_string('markallread', 'peerforum') .
                            '" href="' . $CFG->wwwroot . '/mod/peerforum/markposts.php?f=' .
                            $peerforum->id . '&amp;mark=read&amp;returnpage=view.php">' .
                            '<img src="' . $OUTPUT->pix_url('t/markasread') . '" class="iconsmall" alt="' .
                            get_string('markallread', 'peerforum') . '" /></a>';
                }
                echo '</th>';
            }
        }
        echo '<th class="header lastpost" scope="col">' . get_string('lastpost', 'peerforum') . '</th>';
        if ((!is_guest($context, $USER) && isloggedin()) && has_capability('mod/peerforum:viewdiscussion', $context)) {
            if (\mod_peerforum\subscriptions::is_subscribable($peerforum)) {
                echo '<th class="header discussionsubscription" scope="col">';
                echo peerforum_get_discussion_subscription_icon_preloaders();
                echo '</th>';
            }
        }

        if ($peerforum->training) {
            echo '<th class="header trainingpages" scope="col">' . get_string('managetraining', 'block_peerblock') . '</th>';
            echo '</th>';
        }

        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
    }

    foreach ($discussions as $discussion) {
        if ($peerforum->type == 'qanda' && !has_capability('mod/peerforum:viewqandawithoutposting', $context) &&
                !peerforum_user_has_posted($peerforum->id, $discussion->discussion, $USER->id)) {
            $canviewparticipants = false;
        }

        if (!empty($replies[$discussion->discussion])) {
            $discussion->replies = $replies[$discussion->discussion]->replies;
            $discussion->lastpostid = $replies[$discussion->discussion]->lastpostid;
        } else {
            $discussion->replies = 0;
        }

        // SPECIAL CASE: The front page can display a news item post to non-logged in users.
        // All posts are read in this case.
        if (!$peerforumtracked) {
            $discussion->unread = '-';
        } else if (empty($USER)) {
            $discussion->unread = 0;
        } else {
            if (empty($unreads[$discussion->discussion])) {
                $discussion->unread = 0;
            } else {
                $discussion->unread = $unreads[$discussion->discussion];
            }
        }

        if (isloggedin()) {
            $ownpost = ($discussion->userid == $USER->id);
        } else {
            $ownpost = false;
        }
        // Use discussion name instead of subject of first post
        $discussion->subject = $discussion->name;

        switch ($displayformat) {
            case 'header':
                if ($groupmode > 0) {
                    if (isset($groups[$discussion->groupid])) {
                        $group = $groups[$discussion->groupid];
                    } else {
                        $group = $groups[$discussion->groupid] = groups_get_group($discussion->groupid);
                    }
                } else {
                    $group = -1;
                }
                peerforum_print_discussion_header($discussion, $peerforum, $group, $strdatestring, $cantrack, $peerforumtracked,
                        $canviewparticipants, $context, $canviewhiddentimedposts);
                break;
            default:
                $link = false;

                if ($discussion->replies) {
                    $link = true;
                } else {
                    $modcontext = context_module::instance($cm->id);
                    $link = peerforum_user_can_see_discussion($peerforum, $discussion, $modcontext, $USER);
                }

                $discussion->peerforum = $peerforum->id;

                peerforum_print_post($discussion, $discussion, $peerforum, $cm, $course, $ownpost, 0, $link, false,
                        '', null, true, $peerforumtracked, false, true, false, $to_peergrade_block, $url_block);

                break;
        }
    }

    if ($displayformat == "header") {
        echo '</tbody>';
        echo '</table>';
    }

    if ($olddiscussionlink) {
        if ($peerforum->type == 'news') {
            $strolder = get_string('oldertopics', 'peerforum');
        } else {
            $strolder = get_string('olderdiscussions', 'peerforum');
        }
        echo '<div class="peerforumolddiscuss">';
        echo '<a href="' . $CFG->wwwroot . '/mod/peerforum/view.php?f=' . $peerforum->id . '&amp;showall=1">';
        echo $strolder . '</a> ...</div>';
    }

    if ($page != -1) { ///Show the paging bar
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$peerforum->id");
    }
}

/**
 * Prints a peerforum discussion
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $peerforum
 * @param stdClass $discussion
 * @param stdClass $post
 * @param int $mode
 * @param mixed $canreply
 * @param bool $canratepeer
 * @uses CONTEXT_MODULE
 * @uses PEERFORUM_MODE_FLATNEWEST
 * @uses PEERFORUM_MODE_FLATOLDEST
 * @uses PEERFORUM_MODE_THREADED
 * @uses PEERFORUM_MODE_NESTED
 */
function peerforum_print_discussione($course, $cm, $peerforum, $discussion, $post, $mode, $canreply = null, $canratepeer = false,
        $cangrade = false, $showincontext = false, $to_peergrade_block = true, $url_block = null, $index = null, $start = null,
        $perpage = null, $enable_pagination = null) {
    global $USER, $CFG;

    require_once($CFG->dirroot . '/ratingpeer/lib.php');

    adjust_database();
    update_all_posts_expired();

    $ownpost = (isloggedin() && $USER->id == $post->userid);

    $modcontext = context_module::instance($cm->id);
    if ($canreply === null) {
        $reply = peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext);
    } else {
        $reply = $canreply;
    }

    // $cm holds general cache for peerforum functions
    $cm->cache = new stdClass;
    $cm->cache->groups = groups_get_all_groups($course->id, 0, $cm->groupingid);
    $cm->cache->usersgroups = array();

    $posters = array();

    // preload all posts - TODO: improve...
    if ($mode == PEERFORUM_MODE_FLATNEWEST) {
        $sort = "p.created DESC";
    } else {
        $sort = "p.created ASC";
    }

    $peerforumtracked = peerforum_tp_is_tracked($peerforum);
    $posts = peerforum_get_all_discussion_posts($discussion->id, $sort, $peerforumtracked);
    $post = $posts[$post->id];

    foreach ($posts as $pid => $p) {
        $posters[$p->userid] = $p->userid;
    }

    // preload all groups of ppl that posted in this discussion
    if ($postersgroups = groups_get_all_groups($course->id, $posters, $cm->groupingid, 'gm.id, gm.groupid, gm.userid')) {
        foreach ($postersgroups as $pg) {
            if (!isset($cm->cache->usersgroups[$pg->userid])) {
                $cm->cache->usersgroups[$pg->userid] = array();
            }
            $cm->cache->usersgroups[$pg->userid][$pg->groupid] = $pg->groupid;
        }
        unset($postersgroups);
    }

    //load ratingpeers
    if ($peerforum->assessed != RATINGPEER_AGGREGATE_NONE) {
        $ratingpeeroptions = new stdClass;
        $ratingpeeroptions->context = $modcontext;
        $ratingpeeroptions->component = 'mod_peerforum';
        $ratingpeeroptions->ratingpeerarea = 'post';
        $ratingpeeroptions->items = $posts;
        $ratingpeeroptions->aggregate = $peerforum->assessed;//the aggregation method
        $ratingpeeroptions->scaleid = $peerforum->scale;
        $ratingpeeroptions->userid = $USER->id;
        if ($peerforum->type == 'single' or !$discussion->id) {
            $ratingpeeroptions->returnurl = "$CFG->wwwroot/mod/peerforum/view.php?id=$cm->id";
        } else {
            $ratingpeeroptions->returnurl = "$CFG->wwwroot/mod/peerforum/discuss.php?d=$discussion->id";
        }
        $ratingpeeroptions->assesstimestart = $peerforum->assesstimestart;
        $ratingpeeroptions->assesstimefinish = $peerforum->assesstimefinish;

        $rm = new ratingpeer_manager();
        $posts = $rm->get_ratingpeers($ratingpeeroptions);
    }

    //load peergrades
    if ($peerforum->peergradeassessed != PEERGRADE_AGGREGATE_NONE) {

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $modcontext;
        $peergradeoptions->component = 'mod_peerforum';
        $peergradeoptions->peergradearea = 'post';
        $peergradeoptions->items = $posts;
        $peergradeoptions->aggregate = $peerforum->peergradeassessed;//the aggregation method
        $peergradeoptions->scaleid = $peerforum->scale;
        $peergradeoptions->peergradescaleid = $peerforum->peergradescale;
        $peergradeoptions->userid = $USER->id;
        $peergradeoptions->feedback = 'null_feedback';

        if ($peerforum->type == 'single' or !$discussion->id) {
            $peergradeoptions->returnurl = "$CFG->wwwroot/mod/peerforum/view.php?id=$cm->id";
        } else {
            if (!$to_peergrade_block) {
                $peergradeoptions->returnurl = "$CFG->wwwroot/mod/peerforum/discuss.php?d=$discussion->id";
            }
            if ($to_peergrade_block) {
                $peergradeoptions->returnurl = $url_block;

            }
        }
        $peergradeoptions->assesstimestart = $peerforum->assesstimestart;
        $peergradeoptions->assesstimefinish = $peerforum->assesstimefinish;

        $pm = new peergrade_manager();
        $posts = $pm->get_peergrades($peergradeoptions);
    }

    $post->peerforum = $peerforum->id;   // Add the peerforum id to the post object, later used by peerforum_print_post
    $post->peerforumtype = $peerforum->type;

    $post->subject = format_string($post->subject);

    $postread = !empty($post->postread);
    $post->discussionname = format_string($discussion->name);
    $post->peerforumname = format_string($peerforum->name);

    peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, false,
            '', '', $postread, true, $peerforumtracked, false, true, $showincontext, $to_peergrade_block, $url_block);

    // Pagination peerforum
    if ($enable_pagination) {

        if (!empty($posts[$post->id]->children)) {
            $total_posts = count($posts[$post->id]->children);
        } else {
            $total_posts = 0;
        }

        if (!empty($perpage)) {
            if (isset($posts[$post->id]->children)) {
                $posts[$post->id]->children = array_slice($posts[$post->id]->children, $start, $perpage, true);
            }
        }

        $actual_page = $start / $perpage;
    } else {
        $actual_page = 0;
    }

    if (!isset($index)) {
        switch ($mode) {
            case PEERFORUM_MODE_FLATOLDEST :
            case PEERFORUM_MODE_FLATNEWEST :
            default:
                peerforum_print_posts_flat($course, $cm, $peerforum, $discussion, $post, $mode, $reply, $peerforumtracked, $posts,
                        $showincontext, $to_peergrade_block, $url_block);
                break;

            case PEERFORUM_MODE_THREADED :
                peerforum_print_posts_threaded($course, $cm, $peerforum, $discussion, $post, 0, $reply, $peerforumtracked, $posts,
                        $showincontext, $to_peergrade_block, $url_block);
                break;

            case PEERFORUM_MODE_NESTED :
                peerforum_print_posts_nested($course, $cm, $peerforum, $discussion, $post, $reply, $peerforumtracked, $posts,
                        $showincontext, $to_peergrade_block, $url_block, $actual_page);
                break;
        }
    }
}

/**
 * @param object $course
 * @param object $cm
 * @param object $peerforum
 * @param object $discussion
 * @param object $post
 * @param object $mode
 * @param bool $reply
 * @param bool $peerforumtracked
 * @param array $posts
 * @return void
 * @global object
 * @global object
 * @uses PEERFORUM_MODE_FLATNEWEST
 */
function peerforum_print_posts_flate($course, &$cm, $peerforum, $discussion, $post, $mode, $reply, $peerforumtracked, $posts,
        $showincontext = false, $to_peergrade_block = true, $url_block = null) {
    global $USER, $CFG;

    $link = false;

    if ($mode == PEERFORUM_MODE_FLATNEWEST) {
        $sort = "ORDER BY created DESC";
    } else {
        $sort = "ORDER BY created ASC";
    }

    foreach ($posts as $post) {
        if (!$post->parent) {
            continue;
        }
        $post->subject = format_string($post->subject);
        $ownpost = ($USER->id == $post->userid);

        $postread = !empty($post->postread);

        peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                '', '', $postread, true, $peerforumtracked, false, true, $showincontext, $to_peergrade_block, $url_block);
    }
}

/**
 * @return void
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @todo Document this function
 *
 */
function peerforum_print_posts_threadede($course, &$cm, $peerforum, $discussion, $parent, $depth, $reply, $peerforumtracked, $posts,
        $showincontext = false, $to_peergrade_block = true, $url_block = null) {
    global $USER, $CFG;

    $link = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        $modcontext = context_module::instance($cm->id);
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $modcontext);

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if ($depth > 0) {
                $ownpost = ($USER->id == $post->userid);
                $post->subject = format_string($post->subject);

                $postread = !empty($post->postread);

                peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                        '', '', $postread, true, $peerforumtracked, false, true, $showincontext, $to_peergrade_block, $url_block);
            } else {
                if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
                    echo "</div>\n";
                    continue;
                }
                $by = new stdClass();
                $by->name = fullname($post, $canviewfullnames);
                $by->date = userdate($post->modified);

                if ($peerforumtracked) {
                    if (!empty($post->postread)) {
                        $style = '<span class="peerforumthread read">';
                    } else {
                        $style = '<span class="peerforumthread unread">';
                    }
                } else {
                    $style = '<span class="peerforumthread">';
                }
                echo $style . "<a name=\"$post->id\"></a>" .
                        "<a href=\"discuss.php?d=$post->discussion&amp;parent=$post->id\">" . format_string($post->subject, true) .
                        "</a> ";
                print_string("bynameondate", "peerforum", $by);
                echo "</span>";
            }

            peerforum_print_posts_threaded($course, $cm, $peerforum, $discussion, $post, $depth - 1, $reply, $peerforumtracked,
                    $posts, $showincontext, $to_peergrade_block, $url_block);
            echo "</div>\n";
        }
    }
}

/**
 * @return void
 * @global object
 * @global object
 * @todo Document this function
 */
function peerforum_print_posts_nestede($course, &$cm, $peerforum, $discussion, $parent, $reply, $peerforumtracked, $posts,
        $showincontext = false, $to_peergrade_block = true, $url_block = null, $actual_page = null) {
    global $USER, $CFG;

    $link = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if (!isloggedin()) {
                $ownpost = false;
            } else {
                $ownpost = ($USER->id == $post->userid);
            }

            $post->subject = format_string($post->subject);
            $postread = !empty($post->postread);

            peerforum_print_post($post, $discussion, $peerforum, $cm, $course, $ownpost, $reply, $link,
                    '', '', $postread, true, $peerforumtracked, false, true, $showincontext, $to_peergrade_block, $url_block,
                    $actual_page);
            peerforum_print_posts_nested($course, $cm, $peerforum, $discussion, $post, $reply, $peerforumtracked, $posts,
                    $showincontext, $to_peergrade_block, $url_block, $actual_page);
            echo "</div>\n";
        }
    }
}

/**
 * Returns all peerforum posts since a given time in specified peerforum.
 *
 * @todo Document this functions args
 * @global object
 * @global object
 * @global object
 * @global object
 */
function peerforum_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];
    $params = array($timestart, $cm->instance);

    if ($userid) {
        $userselect = "AND u.id = ?";
        $params[] = $userid;
    } else {
        $userselect = "";
    }

    if ($groupid) {
        $groupselect = "AND d.groupid = ?";
        $params[] = $groupid;
    } else {
        $groupselect = "";
    }

    $allnames = get_all_user_name_fields(true, 'u');
    if (!$posts = $DB->get_records_sql("SELECT p.*, f.type AS peerforumtype, d.peerforum, d.groupid,
                                              d.timestart, d.timeend, d.userid AS duserid,
                                              $allnames, u.email, u.picture, u.imagealt, u.email
                                         FROM {peerforum_posts} p
                                              JOIN {peerforum_discussions} d ON d.id = p.discussion
                                              JOIN {peerforum} f             ON f.id = d.peerforum
                                              JOIN {user} u              ON u.id = p.userid
                                        WHERE p.created > ? AND f.id = ?
                                              $userselect $groupselect
                                     ORDER BY p.id ASC", $params)) { // order by initial posting date
        return;
    }

    $groupmode = groups_get_activity_groupmode($cm, $course);
    $cm_context = context_module::instance($cm->id);
    $viewhiddentimed = has_capability('mod/peerforum:viewhiddentimedposts', $cm_context);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);

    $printposts = array();
    foreach ($posts as $post) {

        if (!empty($CFG->peerforum_enabletimedposts) and $USER->id != $post->duserid
                and (($post->timestart > 0 and $post->timestart > time()) or ($post->timeend > 0 and $post->timeend < time()))) {
            if (!$viewhiddentimed) {
                continue;
            }
        }

        if ($groupmode) {
            if ($post->groupid == -1 or $groupmode == VISIBLEGROUPS or $accessallgroups) {
                // oki (Open discussions have groupid -1)
            } else {
                // separate mode
                if (isguestuser()) {
                    // shortcut
                    continue;
                }

                if (!in_array($post->groupid, $modinfo->get_groups($cm->groupingid))) {
                    continue;
                }
            }
        }

        $printposts[] = $post;
    }

    if (!$printposts) {
        return;
    }

    $aname = format_string($cm->name, true);

    foreach ($printposts as $post) {
        $tmpactivity = new stdClass();

        $tmpactivity->type = 'peerforum';
        $tmpactivity->cmid = $cm->id;
        $tmpactivity->name = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $post->modified;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->id = $post->id;
        $tmpactivity->content->discussion = $post->discussion;
        $tmpactivity->content->subject = format_string($post->subject);
        $tmpactivity->content->parent = $post->parent;
        $tmpactivity->content->peerforumtype = $post->peerforumtype;

        $tmpactivity->user = new stdClass();
        $additionalfields = array('id' => 'userid', 'picture', 'imagealt', 'email');
        $additionalfields = explode(',', user_picture::fields());
        $tmpactivity->user = username_load_fields_from_object($tmpactivity->user, $post, null, $additionalfields);
        $tmpactivity->user->id = $post->userid;

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Outputs the peerforum post indicated by $activity.
 *
 * @param object $activity the activity object the peerforum resides in
 * @param int $courseid the id of the course the peerforum resides in
 * @param bool $detail not used, but required for compatibilty with other modules
 * @param int $modnames not used, but required for compatibilty with other modules
 * @param bool $viewfullnames not used, but required for compatibilty with other modules
 */
function peerforum_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $OUTPUT;

    $content = $activity->content;
    if ($content->parent) {
        $class = 'reply';
    } else {
        $class = 'discussion';
    }

    $tableoptions = [
            'border' => '0',
            'cellpadding' => '3',
            'cellspacing' => '0',
            'class' => 'peerforum-recent'
    ];
    $output = html_writer::start_tag('table', $tableoptions);
    $output .= html_writer::start_tag('tr');

    $post = (object) ['parent' => $content->parent];
    $peerforum = (object) ['type' => $content->peerforumtype];
    $authorhidden = peerforum_is_author_hidden($post, $peerforum);

    // Show user picture if author should not be hidden.
    if (!$authorhidden) {
        $pictureoptions = [
                'courseid' => $courseid,
                'link' => $authorhidden,
                'alttext' => $authorhidden,
        ];
        $picture = $OUTPUT->user_picture($activity->user, $pictureoptions);
        $output .= html_writer::tag('td', $picture, ['class' => 'userpicture', 'valign' => 'top']);
    }

    // Discussion title and author.
    $output .= html_writer::start_tag('td', ['class' => $class]);
    if ($content->parent) {
        $class = 'title';
    } else {
        // Bold the title of new discussions so they stand out.
        $class = 'title bold';
    }

    $output .= html_writer::start_div($class);
    if ($detail) {
        $aname = s($activity->name);
        $output .= $OUTPUT->image_icon('icon', $aname, $activity->type);
    }
    $discussionurl = new moodle_url('/mod/peerforum/discuss.php', ['d' => $content->discussion]);
    $discussionurl->set_anchor('p' . $activity->content->id);
    $output .= html_writer::link($discussionurl, $content->subject);
    $output .= html_writer::end_div();

    $timestamp = userdate_htmltime($activity->timestamp);
    if ($authorhidden) {
        $authornamedate = $timestamp;
    } else {
        $fullname = fullname($activity->user, $viewfullnames);
        $userurl = new moodle_url('/user/view.php');
        $userurl->params(['id' => $activity->user->id, 'course' => $courseid]);
        $by = new stdClass();
        $by->name = html_writer::link($userurl, $fullname);
        $by->date = $timestamp;
        $authornamedate = get_string('bynameondate', 'peerforum', $by);
    }
    $output .= html_writer::div($authornamedate, 'user');
    $output .= html_writer::end_tag('td');
    $output .= html_writer::end_tag('tr');
    $output .= html_writer::end_tag('table');

    echo $output;
}

/**
 * recursively sets the discussion field to $discussionid on $postid and all its children
 * used when pruning a post
 *
 * @param int $postid
 * @param int $discussionid
 * @return bool
 * @global object
 */
function peerforum_change_discussionid($postid, $discussionid) {
    global $DB;
    $DB->set_field('peerforum_posts', 'discussion', $discussionid, array('id' => $postid));
    if ($posts = $DB->get_records('peerforum_posts', array('parent' => $postid))) {
        foreach ($posts as $post) {
            peerforum_change_discussionid($post->id, $discussionid);
        }
    }
    return true;
}

/**
 * Prints the editing button on subscribers page
 *
 * @param int $courseid
 * @param int $peerforumid
 * @return string
 * @global object
 * @global object
 */
function peerforum_update_subscriptions_button($courseid, $peerforumid) {
    global $CFG, $USER;

    if (!empty($USER->subscriptionsediting)) {
        $string = get_string('managesubscriptionsoff', 'peerforum');
        $edit = "off";
    } else {
        $string = get_string('managesubscriptionson', 'peerforum');
        $edit = "on";
    }

    $subscribers = html_writer::start_tag('form', array('action' => $CFG->wwwroot . '/mod/peerforum/subscribers.php',
            'method' => 'get', 'class' => 'form-inline'));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => $string,
            'class' => 'btn btn-secondary'));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $peerforumid));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'edit', 'value' => $edit));
    $subscribers .= html_writer::end_tag('form');

    return $subscribers;
}

// Functions to do with read tracking.

/**
 * Mark posts as read.
 *
 * @param object $user object
 * @param array $postids array of post ids
 * @return boolean success
 * @global object
 * @global object
 */
function peerforum_tp_mark_posts_read($user, $postids) {
    global $CFG, $DB;

    if (!peerforum_tp_can_track_peerforums(false, $user)) {
        return true;
    }

    $status = true;

    $now = time();
    $cutoffdate = $now - ($CFG->peerforum_oldpostdays * 24 * 3600);

    if (empty($postids)) {
        return true;

    } else if (count($postids) > 200) {
        while ($part = array_splice($postids, 0, 200)) {
            $status = peerforum_tp_mark_posts_read($user, $part) && $status;
        }
        return $status;
    }

    list($usql, $postidparams) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'postid');

    $insertparams = array(
            'userid1' => $user->id,
            'userid2' => $user->id,
            'userid3' => $user->id,
            'firstread' => $now,
            'lastread' => $now,
            'cutoffdate' => $cutoffdate,
    );
    $params = array_merge($postidparams, $insertparams);

    if ($CFG->peerforum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = " . PEERFORUM_TRACKING_FORCED . "
                        OR (f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . " AND tf.id IS NULL))";
    } else {
        $trackingsql =
                "AND ((f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . "  OR f.trackingtype = " . PEERFORUM_TRACKING_FORCED . ")
                            AND tf.id IS NULL)";
    }

    // First insert any new entries.
    $sql = "INSERT INTO {peerforum_read} (userid, postid, discussionid, peerforumid, firstread, lastread)

            SELECT :userid1, p.id, p.discussion, d.peerforum, :firstread, :lastread
                FROM {peerforum_posts} p
                    JOIN {peerforum_discussions} d       ON d.id = p.discussion
                    JOIN {peerforum} f                   ON f.id = d.peerforum
                    LEFT JOIN {peerforum_track_prefs} tf ON (tf.userid = :userid2 AND tf.peerforumid = f.id)
                    LEFT JOIN {peerforum_read} fr        ON (
                            fr.userid = :userid3
                        AND fr.postid = p.id
                        AND fr.discussionid = d.id
                        AND fr.peerforumid = f.id
                    )
                WHERE p.id $usql
                    AND p.modified >= :cutoffdate
                    $trackingsql
                    AND fr.id IS NULL";

    $status = $DB->execute($sql, $params) && $status;

    // Then update all records.
    $updateparams = array(
            'userid' => $user->id,
            'lastread' => $now,
    );
    $params = array_merge($postidparams, $updateparams);
    $status = $DB->set_field_select('peerforum_read', 'lastread', $now, '
                userid      =  :userid
            AND lastread    <> :lastread
            AND postid      ' . $usql,
                    $params) && $status;

    return $status;
}

/**
 * Mark post as read.
 *
 * @param int $userid
 * @param int $postid
 * @global object
 * @global object
 */
function peerforum_tp_add_read_record($userid, $postid) {
    global $CFG, $DB;

    $now = time();
    $cutoffdate = $now - ($CFG->peerforum_oldpostdays * 24 * 3600);

    if (!$DB->record_exists('peerforum_read', array('userid' => $userid, 'postid' => $postid))) {
        $sql = "INSERT INTO {peerforum_read} (userid, postid, discussionid, peerforumid, firstread, lastread)

                SELECT ?, p.id, p.discussion, d.peerforum, ?, ?
                  FROM {peerforum_posts} p
                       JOIN {peerforum_discussions} d ON d.id = p.discussion
                 WHERE p.id = ? AND p.modified >= ?";
        return $DB->execute($sql, array($userid, $now, $now, $postid, $cutoffdate));

    } else {
        $sql = "UPDATE {peerforum_read}
                   SET lastread = ?
                 WHERE userid = ? AND postid = ?";
        return $DB->execute($sql, array($now, $userid, $userid));
    }
}

/**
 * If its an old post, do nothing. If the record exists, the maintenance will clear it up later.
 *
 * @param int $userid The ID of the user to mark posts read for.
 * @param object $post The post record for the post to mark as read.
 * @param mixed $unused
 * @return bool
 */
function peerforum_tp_mark_post_read($userid, $post, $unused = null) {
    if (!peerforum_tp_is_post_old($post)) {
        return peerforum_tp_add_read_record($userid, $post->id);
    } else {
        return true;
    }
}

/**
 * Marks a whole peerforum as read, for a given user
 *
 * @param object $user
 * @param int $peerforumid
 * @param int|bool $groupid
 * @return bool
 * @global object
 * @global object
 */
function peerforum_tp_mark_peerforum_read($user, $peerforumid, $groupid = false) {
    global $CFG, $DB;

    $cutoffdate = time() - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);

    $groupsel = "";
    $params = array($user->id, $peerforumid, $cutoffdate);

    if ($groupid !== false) {
        $groupsel = " AND (d.groupid = ? OR d.groupid = -1)";
        $params[] = $groupid;
    }

    $sql = "SELECT p.id
              FROM {peerforum_posts} p
                   LEFT JOIN {peerforum_discussions} d ON d.id = p.discussion
                   LEFT JOIN {peerforum_read} r        ON (r.postid = p.id AND r.userid = ?)
             WHERE d.peerforum = ?
                   AND p.modified >= ? AND r.id is NULL
                   $groupsel";

    if ($posts = $DB->get_records_sql($sql, $params)) {
        $postids = array_keys($posts);
        return peerforum_tp_mark_posts_read($user, $postids);
    }

    return true;
}

/**
 * Marks a whole discussion as read, for a given user
 *
 * @param object $user
 * @param int $discussionid
 * @return bool
 * @global object
 * @global object
 */
function peerforum_tp_mark_discussion_read($user, $discussionid) {
    global $CFG, $DB;

    $cutoffdate = time() - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);

    $sql = "SELECT p.id
              FROM {peerforum_posts} p
                   LEFT JOIN {peerforum_read} r ON (r.postid = p.id AND r.userid = ?)
             WHERE p.discussion = ?
                   AND p.modified >= ? AND r.id is NULL";

    if ($posts = $DB->get_records_sql($sql, array($user->id, $discussionid, $cutoffdate))) {
        $postids = array_keys($posts);
        return peerforum_tp_mark_posts_read($user, $postids);
    }

    return true;
}

/**
 * @param int $userid
 * @param object $post
 * @global object
 */
function peerforum_tp_is_post_read($userid, $post) {
    global $DB;
    return (peerforum_tp_is_post_old($post) ||
            $DB->record_exists('peerforum_read', array('userid' => $userid, 'postid' => $post->id)));
}

/**
 * @param object $post
 * @param int $time Defautls to time()
 * @global object
 */
function peerforum_tp_is_post_old($post, $time = null) {
    global $CFG;

    if (is_null($time)) {
        $time = time();
    }
    return ($post->modified < ($time - ($CFG->peerforum_oldpostdays * 24 * 3600)));
}

/**
 * Returns the count of records for the provided user and course.
 * Please note that group access is ignored!
 *
 * @param int $userid
 * @param int $courseid
 * @return array
 * @global object
 * @global object
 */
function peerforum_tp_get_course_unread_posts($userid, $courseid) {
    global $CFG, $DB;

    $now = floor(time() / 60) * 60; // DB cache friendliness.
    $cutoffdate = $now - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);
    $params = array($userid, $userid, $courseid, $cutoffdate, $userid);

    if (!empty($CFG->peerforum_enabletimedposts)) {
        $timedsql = "AND d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

    if ($CFG->peerforum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = " . PEERFORUM_TRACKING_FORCED . "
                            OR (f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . " AND tf.id IS NULL
                                AND (SELECT trackforums FROM {user} WHERE id = ?) = 1))";
    } else {
        $trackingsql =
                "AND ((f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . " OR f.trackingtype = " . PEERFORUM_TRACKING_FORCED . ")
                            AND tf.id IS NULL
                            AND (SELECT trackforums FROM {user} WHERE id = ?) = 1)";
    }

    $sql = "SELECT f.id, COUNT(p.id) AS unread
              FROM {peerforum_posts} p
                   JOIN {peerforum_discussions} d       ON d.id = p.discussion
                   JOIN {peerforum} f                   ON f.id = d.peerforum
                   JOIN {course} c                  ON c.id = f.course
                   LEFT JOIN {peerforum_read} r         ON (r.postid = p.id AND r.userid = ?)
                   LEFT JOIN {peerforum_track_prefs} tf ON (tf.userid = ? AND tf.peerforumid = f.id)
             WHERE f.course = ?
                   AND p.modified >= ? AND r.id is NULL
                   $trackingsql
                   $timedsql
          GROUP BY f.id";

    if ($return = $DB->get_records_sql($sql, $params)) {
        return $return;
    }

    return array();
}

/**
 * Returns the count of records for the provided user and peerforum and [optionally] group.
 *
 * @param object $cm
 * @param object $course
 * @param bool $resetreadcache optional, true to reset the function static $readcache var
 * @return int
 * @global object
 * @global object
 * @global object
 */
function peerforum_tp_count_peerforum_unread_posts($cm, $course, $resetreadcache = false) {
    global $CFG, $USER, $DB;

    static $readcache = array();

    if ($resetreadcache) {
        $readcache = array();
    }

    $peerforumid = $cm->instance;

    if (!isset($readcache[$course->id])) {
        $readcache[$course->id] = array();
        if ($counts = peerforum_tp_get_course_unread_posts($USER->id, $course->id)) {
            foreach ($counts as $count) {
                $readcache[$course->id][$count->id] = $count->unread;
            }
        }
    }

    if (empty($readcache[$course->id][$peerforumid])) {
        // no need to check group mode ;-)
        return 0;
    }

    $groupmode = groups_get_activity_groupmode($cm, $course);

    if ($groupmode != SEPARATEGROUPS) {
        return $readcache[$course->id][$peerforumid];
    }

    if (has_capability('moodle/site:accessallgroups', context_module::instance($cm->id))) {
        return $readcache[$course->id][$peerforumid];
    }

    require_once($CFG->dirroot . '/course/lib.php');

    $modinfo = get_fast_modinfo($course);

    $mygroups = $modinfo->get_groups($cm->groupingid);

    // add all groups posts
    $mygroups[-1] = -1;

    list ($groups_sql, $groups_params) = $DB->get_in_or_equal($mygroups);

    $now = floor(time() / 60) * 60; // DB Cache friendliness.
    $cutoffdate = $now - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);
    $params = array($USER->id, $peerforumid, $cutoffdate);

    if (!empty($CFG->peerforum_enabletimedposts)) {
        $timedsql = "AND d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

    $params = array_merge($params, $groups_params);

    $sql = "SELECT COUNT(p.id)
              FROM {peerforum_posts} p
                   JOIN {peerforum_discussions} d ON p.discussion = d.id
                   LEFT JOIN {peerforum_read} r   ON (r.postid = p.id AND r.userid = ?)
             WHERE d.peerforum = ?
                   AND p.modified >= ? AND r.id is NULL
                   $timedsql
                   AND d.groupid $groups_sql";

    return $DB->get_field_sql($sql, $params);
}

/**
 * Deletes read records for the specified index. At least one parameter must be specified.
 *
 * @param int $userid
 * @param int $postid
 * @param int $discussionid
 * @param int $peerforumid
 * @return bool
 * @global object
 */
function peerforum_tp_delete_read_records($userid = -1, $postid = -1, $discussionid = -1, $peerforumid = -1) {
    global $DB;
    $params = array();

    $select = '';
    if ($userid > -1) {
        if ($select != '') {
            $select .= ' AND ';
        }
        $select .= 'userid = ?';
        $params[] = $userid;
    }
    if ($postid > -1) {
        if ($select != '') {
            $select .= ' AND ';
        }
        $select .= 'postid = ?';
        $params[] = $postid;
    }
    if ($discussionid > -1) {
        if ($select != '') {
            $select .= ' AND ';
        }
        $select .= 'discussionid = ?';
        $params[] = $discussionid;
    }
    if ($peerforumid > -1) {
        if ($select != '') {
            $select .= ' AND ';
        }
        $select .= 'peerforumid = ?';
        $params[] = $peerforumid;
    }
    if ($select == '') {
        return false;
    } else {
        return $DB->delete_records_select('peerforum_read', $select, $params);
    }
}

/**
 * Get a list of peerforums not tracked by the user.
 *
 * @param int $userid The id of the user to use.
 * @param int $courseid The id of the course being checked.
 * @return mixed An array indexed by peerforum id, or false.
 * @global object
 * @global object
 */
function peerforum_tp_get_untracked_peerforums($userid, $courseid) {
    global $CFG, $DB;

    if ($CFG->peerforum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = " . PEERFORUM_TRACKING_OFF . "
                            OR (f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . " AND (ft.id IS NOT NULL
                                OR (SELECT trackforums FROM {user} WHERE id = ?) = 0)))";
    } else {
        $trackingsql = "AND (f.trackingtype = " . PEERFORUM_TRACKING_OFF . "
                            OR ((f.trackingtype = " . PEERFORUM_TRACKING_OPTIONAL . " OR f.trackingtype = " .
                PEERFORUM_TRACKING_FORCED . ")
                                AND (ft.id IS NOT NULL
                                    OR (SELECT trackforums FROM {user} WHERE id = ?) = 0)))";
    }

    $sql = "SELECT f.id
              FROM {peerforum} f
                   LEFT JOIN {peerforum_track_prefs} ft ON (ft.peerforumid = f.id AND ft.userid = ?)
             WHERE f.course = ?
                   $trackingsql";

    if ($peerforums = $DB->get_records_sql($sql, array($userid, $courseid, $userid))) {
        foreach ($peerforums as $peerforum) {
            $peerforums[$peerforum->id] = $peerforum;
        }
        return $peerforums;

    } else {
        return array();
    }
}

/**
 * Determine if a user can track peerforums and optionally a particular peerforum.
 * Checks the site settings, the user settings and the peerforum settings (if
 * requested).
 *
 * @param mixed $peerforum The peerforum object to test, or the int id (optional).
 * @param mixed $userid The user object to check for (optional).
 * @return boolean
 * @global object
 * @global object
 * @global object
 */
function peerforum_tp_can_track_peerforums($peerforum = false, $user = false) {
    global $USER, $CFG, $DB;

    // if possible, avoid expensive
    // queries
    if (empty($CFG->peerforum_trackreadposts)) {
        return false;
    }

    if ($user === false) {
        $user = $USER;
    }

    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    if ($peerforum === false) {
        if ($CFG->peerforum_allowforcedreadtracking) {
            // Since we can force tracking, assume yes without a specific peerforum.
            return true;
        } else {
            return (bool) $user->trackforums;
        }
    }

    // Work toward always passing an object...
    if (is_numeric($peerforum)) {
        debugging('Better use proper peerforum object.', DEBUG_DEVELOPER);
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforum), '', 'id,trackingtype');
    }

    $peerforumallows = ($peerforum->trackingtype == PEERFORUM_TRACKING_OPTIONAL);
    $peerforumforced = ($peerforum->trackingtype == PEERFORUM_TRACKING_FORCED);

    if ($CFG->peerforum_allowforcedreadtracking) {
        // If we allow forcing, then forced peerforums takes procidence over user setting.
        return ($peerforumforced || ($peerforumallows && (!empty($user->trackforums) && (bool) $user->trackforums)));
    } else {
        // If we don't allow forcing, user setting trumps.
        return ($peerforumforced || $peerforumallows) && !empty($user->trackforums);
    }
}

/**
 * Tells whether a specific peerforum is tracked by the user. A user can optionally
 * be specified. If not specified, the current user is assumed.
 *
 * @param mixed $peerforum If int, the id of the peerforum being checked; if object, the peerforum object
 * @param int $userid The id of the user being checked (optional).
 * @return boolean
 * @global object
 * @global object
 * @global object
 */
function peerforum_tp_is_tracked($peerforum, $user = false) {
    global $USER, $CFG, $DB;

    if ($user === false) {
        $user = $USER;
    }

    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    $cache = cache::make('mod_peerforum', 'peerforum_is_tracked');
    $peerforumid = is_numeric($peerforum) ? $peerforum : $peerforum->id;
    $key = $peerforumid . '_' . $user->id;
    if ($cachedvalue = $cache->get($key)) {
        return $cachedvalue == 'tracked';
    }

    // Work toward always passing an object...
    if (is_numeric($peerforum)) {
        debugging('Better use proper peerforum object.', DEBUG_DEVELOPER);
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforum));
    }

    if (!peerforum_tp_can_track_peerforums($peerforum, $user)) {
        return false;
    }

    $peerforumallows = ($peerforum->trackingtype == PEERFORUM_TRACKING_OPTIONAL);
    $peerforumforced = ($peerforum->trackingtype == PEERFORUM_TRACKING_FORCED);
    $userpref = $DB->get_record('peerforum_track_prefs', array('userid' => $user->id, 'peerforumid' => $peerforum->id));

    if ($CFG->peerforum_allowforcedreadtracking) {
        $istracked = $peerforumforced || ($peerforumallows && $userpref === false);
    } else {
        $istracked = ($peerforumallows || $peerforumforced) && $userpref === false;
    }

    // We have to store a string here because the cache API returns false
    // when it can't find the key which would be confused with our legitimate
    // false value. *sigh*.
    $cache->set($key, $istracked ? 'tracked' : 'not');

    return $istracked;
}

/**
 * @param int $peerforumid
 * @param int $userid
 * @global object
 * @global object
 */
function peerforum_tp_start_tracking($peerforumid, $userid = false) {
    global $USER, $DB;

    if ($userid === false) {
        $userid = $USER->id;
    }

    return $DB->delete_records('peerforum_track_prefs', array('userid' => $userid, 'peerforumid' => $peerforumid));
}

/**
 * @param int $peerforumid
 * @param int $userid
 * @global object
 * @global object
 */
function peerforum_tp_stop_tracking($peerforumid, $userid = false) {
    global $USER, $DB;

    if ($userid === false) {
        $userid = $USER->id;
    }

    if (!$DB->record_exists('peerforum_track_prefs', array('userid' => $userid, 'peerforumid' => $peerforumid))) {
        $track_prefs = new stdClass();
        $track_prefs->userid = $userid;
        $track_prefs->peerforumid = $peerforumid;
        $DB->insert_record('peerforum_track_prefs', $track_prefs);
    }

    return peerforum_tp_delete_read_records($userid, -1, -1, $peerforumid);
}

/**
 * Clean old records from the peerforum_read table.
 *
 * @return void
 * @global object
 * @global object
 */
function peerforum_tp_clean_read_records() {
    global $CFG, $DB;

    if (!isset($CFG->peerforum_oldpostdays)) {
        return;
    }
    // Look for records older than the cutoffdate that are still in the peerforum_read table.
    $cutoffdate = time() - ($CFG->peerforum_oldpostdays * 24 * 60 * 60);

    //first get the oldest tracking present - we need tis to speedup the next delete query
    $sql = "SELECT MIN(fp.modified) AS first
              FROM {peerforum_posts} fp
                   JOIN {peerforum_read} fr ON fr.postid=fp.id";
    if (!$first = $DB->get_field_sql($sql)) {
        // nothing to delete;
        return;
    }

    // now delete old tracking info
    $sql = "DELETE
              FROM {peerforum_read}
             WHERE postid IN (SELECT fp.id
                                FROM {peerforum_posts} fp
                               WHERE fp.modified >= ? AND fp.modified < ?)";
    $DB->execute($sql, array($first, $cutoffdate));
}

/**
 * Sets the last post for a given discussion
 *
 * @param into $discussionid
 * @return bool|int
 **@global object
 * @global object
 */
function peerforum_discussion_update_last_post($discussionid) {
    global $CFG, $DB;

    // Check the given discussion exists
    if (!$DB->record_exists('peerforum_discussions', array('id' => $discussionid))) {
        return false;
    }

    // Use SQL to find the last post for this discussion
    $sql = "SELECT id, userid, modified
              FROM {peerforum_posts}
             WHERE discussion=?
             ORDER BY modified DESC";

    // Lets go find the last post
    if (($lastposts = $DB->get_records_sql($sql, array($discussionid), 0, 1))) {
        $lastpost = reset($lastposts);
        $discussionobject = new stdClass();
        $discussionobject->id = $discussionid;
        $discussionobject->usermodified = $lastpost->userid;
        $discussionobject->timemodified = $lastpost->modified;
        $DB->update_record('peerforum_discussions', $discussionobject);
        return $lastpost->id;
    }

    // To get here either we couldn't find a post for the discussion (weird)
    // or we couldn't update the discussion record (weird x2)
    return false;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function peerforum_get_view_actions() {
    return array('view discussion', 'search', 'peerforum', 'peerforums', 'subscribers', 'view peerforum');
}

/**
 * List the options for peerforum subscription modes.
 * This is used by the settings page and by the mod_form page.
 *
 * @return array
 */
function peerforum_get_subscriptionmode_options() {
    $options = array();
    $options[PEERFORUM_CHOOSESUBSCRIBE] = get_string('subscriptionoptional', 'peerforum');
    $options[PEERFORUM_FORCESUBSCRIBE] = get_string('subscriptionforced', 'peerforum');
    $options[PEERFORUM_INITIALSUBSCRIBE] = get_string('subscriptionauto', 'peerforum');
    $options[PEERFORUM_DISALLOWSUBSCRIBE] = get_string('subscriptiondisabled', 'peerforum');
    return $options;
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function peerforum_get_post_actions() {
    return array('add discussion', 'add post', 'delete discussion', 'delete post', 'move discussion', 'prune post', 'update post');
}

/**
 * Returns a warning object if a user has reached the number of posts equal to
 * the warning/blocking setting, or false if there is no warning to show.
 *
 * @param int|stdClass $peerforum the peerforum id or the peerforum object
 * @param stdClass $cm the course module
 * @return stdClass|bool returns an object with the warning information, else
 *         returns false if no warning is required.
 */
function peerforum_check_throttling($peerforum, $cm = null) {
    global $CFG, $DB, $USER;

    if (is_numeric($peerforum)) {
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforum), '*', MUST_EXIST);
    }

    if (!is_object($peerforum)) {
        return false; // This is broken.
    }

    if (!$cm) {
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $peerforum->course, false, MUST_EXIST);
    }

    if (empty($peerforum->blockafter)) {
        return false;
    }

    if (empty($peerforum->blockperiod)) {
        return false;
    }

    $modcontext = context_module::instance($cm->id);
    if (has_capability('mod/peerforum:postwithoutthrottling', $modcontext)) {
        return false;
    }

    // Get the number of posts in the last period we care about.
    $timenow = time();
    $timeafter = $timenow - $peerforum->blockperiod;
    $numposts = $DB->count_records_sql('SELECT COUNT(p.id) FROM {peerforum_posts} p
                                        JOIN {peerforum_discussions} d
                                        ON p.discussion = d.id WHERE d.peerforum = ?
                                        AND p.userid = ? AND p.created > ?', array($peerforum->id, $USER->id, $timeafter));

    $a = new stdClass();
    $a->blockafter = $peerforum->blockafter;
    $a->numposts = $numposts;
    $a->blockperiod = get_string('secondstotime' . $peerforum->blockperiod);

    if ($peerforum->blockafter <= $numposts) {
        $warning = new stdClass();
        $warning->canpost = false;
        $warning->errorcode = 'peerforumblockingtoomanyposts';
        $warning->module = 'error';
        $warning->additional = $a;
        $warning->link = $CFG->wwwroot . '/mod/peerforum/view.php?f=' . $peerforum->id;

        return $warning;
    }

    if ($peerforum->warnafter <= $numposts) {
        $warning = new stdClass();
        $warning->canpost = true;
        $warning->errorcode = 'peerforumblockingalmosttoomanyposts';
        $warning->module = 'peerforum';
        $warning->additional = $a;
        $warning->link = null;

        return $warning;
    }
}

/**
 * Throws an error if the user is no longer allowed to post due to having reached
 * or exceeded the number of posts specified in 'Post threshold for blocking'
 * setting.
 *
 * @param stdClass $thresholdwarning the warning information returned
 *        from the function peerforum_check_throttling.
 * @since Moodle 2.5
 */
function peerforum_check_blocking_threshold($thresholdwarning) {
    if (!empty($thresholdwarning) && !$thresholdwarning->canpost) {
        print_error($thresholdwarning->errorcode,
                $thresholdwarning->module,
                $thresholdwarning->link,
                $thresholdwarning->additional);
    }
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string $type optional
 * @global object
 * @global object
 */
function peerforum_reset_gradebook($courseid, $type = '') {
    global $CFG, $DB;

    $wheresql = '';
    $params = array($courseid);
    if ($type) {
        $wheresql = "AND f.type=?";
        $params[] = $type;
    }

    $sql = "SELECT f.*, cm.idnumber as cmidnumber, f.course as courseid
              FROM {peerforum} f, {course_modules} cm, {modules} m
             WHERE m.name='peerforum' AND m.id=cm.module AND cm.instance=f.id AND f.course=? $wheresql";

    if ($peerforums = $DB->get_records_sql($sql, $params)) {
        foreach ($peerforums as $peerforum) {
            peerforum_grade_item_update($peerforum, 'reset', 'reset', 'reset', 'reset');
        }
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified peerforum
 * and clean up any related data.
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 * @global object
 * @global object
 */
function peerforum_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/ratingpeer/lib.php');

    $componentstr = get_string('modulenameplural', 'peerforum');
    $status = array();

    $params = array($data->courseid);

    $removeposts = false;
    $typesql = "";
    if (!empty($data->reset_peerforum_all)) {
        $removeposts = true;
        $typesstr = get_string('resetpeerforumsall', 'peerforum');
        $types = array();
    } else if (!empty($data->reset_peerforum_types)) {
        $removeposts = true;
        $types = array();
        $sqltypes = array();
        $peerforum_types_all = peerforum_get_peerforum_types_all();
        foreach ($data->reset_peerforum_types as $type) {
            if (!array_key_exists($type, $peerforum_types_all)) {
                continue;
            }
            $types[] = $peerforum_types_all[$type];
            $sqltypes[] = $type;
        }
        if (!empty($sqltypes)) {
            list($typesql, $typeparams) = $DB->get_in_or_equal($sqltypes);
            $typesql = " AND f.type " . $typesql;
            $params = array_merge($params, $typeparams);
        }
        $typesstr = get_string('resetpeerforums', 'peerforum') . ': ' . implode(', ', $types);
    }
    $alldiscussionssql = "SELECT fd.id
                            FROM {peerforum_discussions} fd, {peerforum} f
                           WHERE f.course=? AND f.id=fd.peerforum";

    $allpeerforumssql = "SELECT f.id
                            FROM {peerforum} f
                           WHERE f.course=?";

    $allpostssql = "SELECT fp.id
                            FROM {peerforum_posts} fp, {peerforum_discussions} fd, {peerforum} f
                           WHERE f.course=? AND f.id=fd.peerforum AND fd.id=fp.discussion";

    $peerforumssql = $peerforums = $rm = null;

    // Check if we need to get additional data.
    if ($removeposts || !empty($data->reset_peerforum_ratingpeers) || !empty($data->reset_peerforum_tags)) {
        // Set this up if we have to remove ratings.
        $rm = new ratingpeer_manager();
        $ratingpeerdeloptions = new stdClass;
        $ratingpeerdeloptions->component = 'mod_peerforum';
        $ratingpeerdeloptions->ratingpeerarea = 'post';

        // Get the peerforums for actions that require it.
        $peerforumssql = "$allpeerforumssql $typesql";
        $peerforums = $DB->get_records_sql($peerforumssql, $params);
    }

    if ($removeposts) {
        $discussionssql = "$alldiscussionssql $typesql";
        $postssql = "$allpostssql $typesql";

        // now get rid of all attachments
        $fs = get_file_storage();
        if ($peerforums) {
            foreach ($peerforums as $peerforumid => $unused) {
                if (!$cm = get_coursemodule_from_instance('peerforum', $peerforumid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_peerforum', 'attachment');
                $fs->delete_area_files($context->id, 'mod_peerforum', 'post');

                //remove ratingpeers
                $ratingpeerdeloptions->contextid = $context->id;
                $rm->delete_ratingpeers($ratingpeerdeloptions);

                //remove peergrades
                $peergradepeerdeloptions->contextid = $context->id;
                $rm->delete_peergrades($peergradepeerdeloptions);

                core_tag_tag::delete_instances('mod_peerforum', null, $context->id);
            }
        }

        // first delete all read flags
        $DB->delete_records_select('peerforum_read', "peerforumid IN ($peerforumssql)", $params);

        // remove tracking prefs
        $DB->delete_records_select('peerforum_track_prefs', "peerforumid IN ($peerforumssql)", $params);

        // remove posts from queue
        $DB->delete_records_select('peerforum_queue', "discussionid IN ($discussionssql)", $params);

        // all posts - initial posts must be kept in single simple discussion peerforums
        $DB->delete_records_select('peerforum_posts', "discussion IN ($discussionssql) AND parent <> 0",
                $params); // first all children
        $DB->delete_records_select('peerforum_posts', "discussion IN ($discussionssql AND f.type <> 'single') AND parent = 0",
                $params); // now the initial posts for non single simple

        // finally all discussions except single simple peerforums
        $DB->delete_records_select('peerforum_discussions', "peerforum IN ($peerforumssql AND f.type <> 'single')", $params);

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            if (empty($types)) {
                peerforum_reset_gradebook($data->courseid);
            } else {
                foreach ($types as $type) {
                    peerforum_reset_gradebook($data->courseid, $type);
                }
            }
        }

        $status[] = array('component' => $componentstr, 'item' => $typesstr, 'error' => false);
    }

    // remove all ratingpeers in this course's peerforums
    if (!empty($data->reset_peerforum_ratingpeers)) {
        if ($peerforums) {
            foreach ($peerforums as $peerforumid => $unused) {
                if (!$cm = get_coursemodule_from_instance('peerforum', $peerforumid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);

                //remove ratingpeers
                $ratingpeerdeloptions->contextid = $context->id;
                $rm->delete_ratingpeers($ratingpeerdeloptions);
            }
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            peerforum_reset_gradebook($data->courseid);
        }
    }

    // remove all peergrades in this course's peerforums
    if (!empty($data->reset_peerforum_peergrades)) {
        if ($peerforums) {
            foreach ($peerforums as $peerforumid => $unused) {
                if (!$cm = get_coursemodule_from_instance('peerforum', $peerforumid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);

                //remove peergrades
                $peergradedeloptions->contextid = $context->id;
                $rm->delete_peergrades($peergradedeloptions);
            }
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            peerforum_reset_gradebook($data->courseid);
        }
    }

    // Remove all the tags.
    if (!empty($data->reset_peerforum_tags)) {
        if ($peerforums) {
            foreach ($peerforums as $peerforumid => $unused) {
                if (!$cm = get_coursemodule_from_instance('peerforum', $peerforumid)) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_peerforum', null, $context->id);
            }
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('tagsdeleted', 'peerforum'), 'error' => false);
    }

    // remove all digest settings unconditionally - even for users still enrolled in course.
    if (!empty($data->reset_peerforum_digests)) {
        $DB->delete_records_select('peerforum_digests', "peerforum IN ($allpeerforumssql)", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('resetdigests', 'peerforum'), 'error' => false);
    }

    // remove all subscriptions unconditionally - even for users still enrolled in course
    if (!empty($data->reset_peerforum_subscriptions)) {
        $DB->delete_records_select('peerforum_subscriptions', "peerforum IN ($allpeerforumssql)", $params);
        $DB->delete_records_select('peerforum_discussion_subs', "peerforum IN ($allpeerforumssql)", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('resetsubscriptions', 'peerforum'), 'error' => false);
    }

    // remove all tracking prefs unconditionally - even for users still enrolled in course
    if (!empty($data->reset_peerforum_track_prefs)) {
        $DB->delete_records_select('peerforum_track_prefs', "peerforumid IN ($allpeerforumssql)", $params);
        $status[] = array('component' => $componentstr, 'item' => get_string('resettrackprefs', 'peerforum'), 'error' => false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates('peerforum', array('assesstimestart', 'assesstimefinish'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    return $status;
}

/**
 * Called by course/reset.php
 *
 * @param $mform form passed by reference
 */
function peerforum_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'peerforumheader', get_string('modulenameplural', 'peerforum'));

    $mform->addElement('checkbox', 'reset_peerforum_all', get_string('resetpeerforumsall', 'peerforum'));

    $mform->addElement('select', 'reset_peerforum_types', get_string('resetpeerforums', 'peerforum'),
            peerforum_get_peerforum_types_all(),
            array('multiple' => 'multiple'));
    $mform->setAdvanced('reset_peerforum_types');
    $mform->disabledIf('reset_peerforum_types', 'reset_peerforum_all', 'checked');

    $mform->addElement('checkbox', 'reset_peerforum_digests', get_string('resetdigests', 'peerforum'));
    $mform->setAdvanced('reset_peerforum_digests');

    $mform->addElement('checkbox', 'reset_peerforum_subscriptions', get_string('resetsubscriptions', 'peerforum'));
    $mform->setAdvanced('reset_peerforum_subscriptions');

    $mform->addElement('checkbox', 'reset_peerforum_track_prefs', get_string('resettrackprefs', 'peerforum'));
    $mform->setAdvanced('reset_peerforum_track_prefs');
    $mform->disabledIf('reset_peerforum_track_prefs', 'reset_peerforum_all', 'checked');

    $mform->addElement('checkbox', 'reset_peerforum_ratingpeers', get_string('deleteallratingpeers'));
    $mform->disabledIf('reset_peerforum_ratingpeers', 'reset_peerforum_all', 'checked');

    $mform->addElement('checkbox', 'reset_peerforum_peergrades', get_string('deleteallpeergrades'));
    $mform->disabledIf('reset_peerforum_peergrades', 'reset_peerforum_all', 'checked');

    $mform->addElement('checkbox', 'reset_peerforum_tags', get_string('removeallpeerforumtags', 'peerforum'));
    $mform->disabledIf('reset_peerforum_tags', 'reset_peerforum_all', 'checked');
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function peerforum_reset_course_form_defaults($course) {
    return array('reset_peerforum_all' => 1, 'reset_peerforum_digests' => 0, 'reset_peerforum_subscriptions' => 0,
            'reset_peerforum_track_prefs' => 0, 'reset_peerforum_ratingpeers' => 1);
}

/**
 * Returns array of peerforum layout modes
 *
 * @param bool $useexperimentalui use experimental layout modes or not
 * @return array
 */
function peerforum_get_layout_modes(bool $useexperimentalui = false) {
    $modes = [
            PEERFORUM_MODE_FLATOLDEST => get_string('modeflatoldestfirst', 'peerforum'),
            PEERFORUM_MODE_FLATNEWEST => get_string('modeflatnewestfirst', 'peerforum'),
            PEERFORUM_MODE_THREADED => get_string('modethreaded', 'peerforum')
    ];

    if ($useexperimentalui) {
        $modes[PEERFORUM_MODE_NESTED_V2] = get_string('modenestedv2', 'peerforum');
    } else {
        $modes[PEERFORUM_MODE_NESTED] = get_string('modenested', 'peerforum');
    }

    return $modes;
}

/**
 * Returns array of peerforum types chooseable on the peerforum editing form
 *
 * @return array
 */
function peerforum_get_peerforum_types() {
    return array('general' => get_string('generalpeerforum', 'peerforum'),
            'eachuser' => get_string('eachuserpeerforum', 'peerforum'),
            'single' => get_string('singlepeerforum', 'peerforum'),
            'qanda' => get_string('qandapeerforum', 'peerforum'),
            'blog' => get_string('blogpeerforum', 'peerforum'));
}

/**
 * Returns array of all peerforum layout modes
 *
 * @return array
 */
function peerforum_get_peerforum_types_all() {
    return array('news' => get_string('namenews', 'peerforum'),
            'social' => get_string('namesocial', 'peerforum'),
            'general' => get_string('generalpeerforum', 'peerforum'),
            'eachuser' => get_string('eachuserpeerforum', 'peerforum'),
            'single' => get_string('singlepeerforum', 'peerforum'),
            'qanda' => get_string('qandapeerforum', 'peerforum'),
            'blog' => get_string('blogpeerforum', 'peerforum'));
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function peerforum_get_extra_capabilities() {
    return ['moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate'];
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $peerforumnode The node to add module settings to
 */
function peerforum_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $peerforumnode) {
    global $USER, $PAGE, $CFG, $DB, $OUTPUT;

    if (empty($PAGE->cm->context)) {
        $PAGE->cm->context = context_module::instance($PAGE->cm->instance);
    }

    $vaultfactory = mod_peerforum\local\container::get_vault_factory();
    $managerfactory = mod_peerforum\local\container::get_manager_factory();
    $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
    $peerforumvault = $vaultfactory->get_peerforum_vault();
    $peerforumentity = $peerforumvault->get_from_id($PAGE->cm->instance);
    $peerforumobject = $legacydatamapperfactory->get_peerforum_data_mapper()->to_legacy_object($peerforumentity);

    $params = $PAGE->url->params();
    if (!empty($params['d'])) {
        $discussionid = $params['d'];
    }

    // Display all peerforum reports user has access to.
    if (isloggedin() && !isguestuser()) {
        $reportnames = array_keys(core_component::get_plugin_list('peerforumreport'));

        foreach ($reportnames as $reportname) {
            if (has_capability("peerforumreport/{$reportname}:view", $PAGE->cm->context)) {
                $reportlinkparams = [
                        'courseid' => $peerforumobject->course,
                        'peerforumid' => $peerforumobject->id,
                ];
                $reportlink = new moodle_url("/mod/peerforum/report/{$reportname}/index.php", $reportlinkparams);
                $peerforumnode->add(get_string('nodetitle', "peerforumreport_{$reportname}"), $reportlink,
                        navigation_node::TYPE_CONTAINER);
            }
        }
    }

    // For some actions you need to be enrolled, being admin is not enough sometimes here.
    $enrolled = is_enrolled($PAGE->cm->context, $USER, '', false);
    $activeenrolled = is_enrolled($PAGE->cm->context, $USER, '', true);

    $canmanage = has_capability('mod/peerforum:managesubscriptions', $PAGE->cm->context);
    $subscriptionmode = \mod_peerforum\subscriptions::get_subscription_mode($peerforumobject);
    $cansubscribe = $activeenrolled && !\mod_peerforum\subscriptions::is_forcesubscribed($peerforumobject) &&
            (!\mod_peerforum\subscriptions::subscription_disabled($peerforumobject) || $canmanage);

    if ($canmanage) {
        $mode = $peerforumnode->add(get_string('subscriptionmode', 'peerforum'), null, navigation_node::TYPE_CONTAINER);
        $mode->add_class('subscriptionmode');

        $allowchoice = $mode->add(get_string('subscriptionoptional', 'peerforum'), new moodle_url('/mod/peerforum/subscribe.php',
                array('id' => $peerforumobject->id, 'mode' => PEERFORUM_CHOOSESUBSCRIBE, 'sesskey' => sesskey())),
                navigation_node::TYPE_SETTING);
        $forceforever = $mode->add(get_string("subscriptionforced", "peerforum"), new moodle_url('/mod/peerforum/subscribe.php',
                array('id' => $peerforumobject->id, 'mode' => PEERFORUM_FORCESUBSCRIBE, 'sesskey' => sesskey())),
                navigation_node::TYPE_SETTING);
        $forceinitially = $mode->add(get_string("subscriptionauto", "peerforum"), new moodle_url('/mod/peerforum/subscribe.php',
                array('id' => $peerforumobject->id, 'mode' => PEERFORUM_INITIALSUBSCRIBE, 'sesskey' => sesskey())),
                navigation_node::TYPE_SETTING);
        $disallowchoice = $mode->add(get_string('subscriptiondisabled', 'peerforum'), new moodle_url('/mod/peerforum/subscribe.php',
                array('id' => $peerforumobject->id, 'mode' => PEERFORUM_DISALLOWSUBSCRIBE, 'sesskey' => sesskey())),
                navigation_node::TYPE_SETTING);

        switch ($subscriptionmode) {
            case PEERFORUM_CHOOSESUBSCRIBE : // 0
                $allowchoice->action = null;
                $allowchoice->add_class('activesetting');
                $allowchoice->icon = new pix_icon('t/selected', '', 'mod_peerforum');
                break;
            case PEERFORUM_FORCESUBSCRIBE : // 1
                $forceforever->action = null;
                $forceforever->add_class('activesetting');
                $forceforever->icon = new pix_icon('t/selected', '', 'mod_peerforum');
                break;
            case PEERFORUM_INITIALSUBSCRIBE : // 2
                $forceinitially->action = null;
                $forceinitially->add_class('activesetting');
                $forceinitially->icon = new pix_icon('t/selected', '', 'mod_peerforum');
                break;
            case PEERFORUM_DISALLOWSUBSCRIBE : // 3
                $disallowchoice->action = null;
                $disallowchoice->add_class('activesetting');
                $disallowchoice->icon = new pix_icon('t/selected', '', 'mod_peerforum');
                break;
        }

    } else if ($activeenrolled) {

        switch ($subscriptionmode) {
            case PEERFORUM_CHOOSESUBSCRIBE : // 0
                $notenode = $peerforumnode->add(get_string('subscriptionoptional', 'peerforum'));
                break;
            case PEERFORUM_FORCESUBSCRIBE : // 1
                $notenode = $peerforumnode->add(get_string('subscriptionforced', 'peerforum'));
                break;
            case PEERFORUM_INITIALSUBSCRIBE : // 2
                $notenode = $peerforumnode->add(get_string('subscriptionauto', 'peerforum'));
                break;
            case PEERFORUM_DISALLOWSUBSCRIBE : // 3
                $notenode = $peerforumnode->add(get_string('subscriptiondisabled', 'peerforum'));
                break;
        }
    }

    if ($cansubscribe) {
        if (\mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforumobject, null, $PAGE->cm)) {
            $linktext = get_string('unsubscribe', 'peerforum');
        } else {
            $linktext = get_string('subscribe', 'peerforum');
        }
        $url = new moodle_url('/mod/peerforum/subscribe.php', array('id' => $peerforumobject->id, 'sesskey' => sesskey()));
        $peerforumnode->add($linktext, $url, navigation_node::TYPE_SETTING);

        if (isset($discussionid)) {
            if (\mod_peerforum\subscriptions::is_subscribed($USER->id, $peerforumobject, $discussionid, $PAGE->cm)) {
                $linktext = get_string('unsubscribediscussion', 'peerforum');
            } else {
                $linktext = get_string('subscribediscussion', 'peerforum');
            }
            $url = new moodle_url('/mod/peerforum/subscribe.php', array(
                    'id' => $peerforumobject->id,
                    'sesskey' => sesskey(),
                    'd' => $discussionid,
                    'returnurl' => $PAGE->url->out(),
            ));
            $peerforumnode->add($linktext, $url, navigation_node::TYPE_SETTING);
        }
    }

    if (has_capability('mod/peerforum:viewsubscribers', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/peerforum/subscribers.php', array('id' => $peerforumobject->id));
        $peerforumnode->add(get_string('showsubscribers', 'peerforum'), $url, navigation_node::TYPE_SETTING);
    }

    if ($enrolled && ($peerforumobject)) { // keep tracking info for users with suspended enrolments
        if ($peerforumobject->trackingtype == PEERFORUM_TRACKING_OPTIONAL
                || ((!$CFG->peerforum_allowforcedreadtracking) && $peerforumobject->trackingtype == PEERFORUM_TRACKING_FORCED)) {
            if (peerforum_tp_is_tracked($peerforumobject)) {
                $linktext = get_string('notrackforum', 'peerforum');
            } else {
                $linktext = get_string('trackforum', 'peerforum');
            }
            $url = new moodle_url('/mod/peerforum/settracking.php', array(
                    'id' => $peerforumobject->id,
                    'sesskey' => sesskey(),
            ));
            $peerforumnode->add($linktext, $url, navigation_node::TYPE_SETTING);
        }
    }

    if (!isloggedin() && $PAGE->course->id == SITEID) {
        $userid = guest_user()->id;
    } else {
        $userid = $USER->id;
    }

    $hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);
    $enablerssfeeds = !empty($CFG->enablerssfeeds) && !empty($CFG->peerforum_enablerssfeeds);

    if ($enablerssfeeds && $peerforumobject->rsstype && $peerforumobject->rssarticles && $hascourseaccess) {

        if (!function_exists('rss_get_url')) {
            require_once("$CFG->libdir/rsslib.php");
        }

        if ($peerforumobject->rsstype == 1) {
            $string = get_string('rsssubscriberssdiscussions', 'peerforum');
        } else {
            $string = get_string('rsssubscriberssposts', 'peerforum');
        }

        $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $userid, "mod_peerforum", $peerforumobject->id));
        $peerforumnode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));
    }

    $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
    if ($capabilitymanager->can_export_peerforum($USER)) {
        $url = new moodle_url('/mod/peerforum/export.php', ['id' => $peerforumobject->id]);
        $peerforumnode->add(get_string('export', 'mod_peerforum'), $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * Adds information about unread messages, that is only required for the course view page (and
 * similar), to the course-module object.
 *
 * @param cm_info $cm Course-module object
 */
function peerforum_cm_info_view(cm_info $cm) {
    global $CFG;

    if (peerforum_tp_can_track_peerforums()) {
        if ($unread = peerforum_tp_count_peerforum_unread_posts($cm, $cm->get_course())) {
            $out = '<span class="unread"> <a href="' . $cm->url . '#unread">';
            if ($unread == 1) {
                $out .= get_string('unreadpostsone', 'peerforum');
            } else {
                $out .= get_string('unreadpostsnumber', 'peerforum', $unread);
            }
            $out .= '</a></span>';
            $cm->set_after_link($out);
        }
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function peerforum_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $peerforum_pagetype = array(
            'mod-peerforum-*' => get_string('page-mod-peerforum-x', 'peerforum'),
            'mod-peerforum-view' => get_string('page-mod-peerforum-view', 'peerforum'),
            'mod-peerforum-discuss' => get_string('page-mod-peerforum-discuss', 'peerforum')
    );
    return $peerforum_pagetype;
}

/**
 * Gets all of the courses where the provided user has posted in a peerforum.
 *
 * @param stdClass $user The user who's posts we are looking for
 * @param bool $discussionsonly If true only look for discussions started by the user
 * @param bool $includecontexts If set to trye contexts for the courses will be preloaded
 * @param int $limitfrom The offset of records to return
 * @param int $limitnum The number of records to return
 * @return array An array of courses
 * @global moodle_database $DB The database connection
 */
function peerforum_get_courses_user_posted_in($user, $discussionsonly = false, $includecontexts = true, $limitfrom = null,
        $limitnum = null) {
    global $DB;

    // If we are only after discussions we need only look at the peerforum_discussions
    // table and join to the userid there. If we are looking for posts then we need
    // to join to the peerforum_posts table.
    if (!$discussionsonly) {
        $subquery = "(SELECT DISTINCT fd.course
                         FROM {peerforum_discussions} fd
                         JOIN {peerforum_posts} fp ON fp.discussion = fd.id
                        WHERE fp.userid = :userid )";
    } else {
        $subquery = "(SELECT DISTINCT fd.course
                         FROM {peerforum_discussions} fd
                        WHERE fd.userid = :userid )";
    }

    $params = array('userid' => $user->id);

    // Join to the context table so that we can preload contexts if required.
    if ($includecontexts) {
        $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
        $params['contextlevel'] = CONTEXT_COURSE;
    } else {
        $ctxselect = '';
        $ctxjoin = '';
    }

    // Now we need to get all of the courses to search.
    // All courses where the user has posted within a peerforum will be returned.
    $sql = "SELECT c.* $ctxselect
            FROM {course} c
            $ctxjoin
            WHERE c.id IN ($subquery)";
    $courses = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    if ($includecontexts) {
        array_map('context_helper::preload_from_record', $courses);
    }
    return $courses;
}

/**
 * Gets all of the peerforums a user has posted in for one or more courses.
 *
 * @param stdClass $user
 * @param array $courseids An array of courseids to search or if not provided
 *                       all courses the user has posted within
 * @param bool $discussionsonly If true then only peerforums where the user has started
 *                       a discussion will be returned.
 * @param int $limitfrom The offset of records to return
 * @param int $limitnum The number of records to return
 * @return array An array of peerforums the user has posted within in the provided courses
 * @global moodle_database $DB
 */
function peerforum_get_peerforums_user_posted_in($user, array $courseids = null, $discussionsonly = false, $limitfrom = null,
        $limitnum = null) {
    global $DB;

    if (!is_null($courseids)) {
        list($coursewhere, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        $coursewhere = ' AND f.course ' . $coursewhere;
    } else {
        $coursewhere = '';
        $params = array();
    }
    $params['userid'] = $user->id;
    $params['peerforum'] = 'peerforum';

    if ($discussionsonly) {
        $join = 'JOIN {peerforum_discussions} ff ON ff.peerforum = f.id';
    } else {
        $join = 'JOIN {peerforum_discussions} fd ON fd.peerforum = f.id
                 JOIN {peerforum_posts} ff ON ff.discussion = fd.id';
    }

    $sql = "SELECT f.*, cm.id AS cmid
              FROM {peerforum} f
              JOIN {course_modules} cm ON cm.instance = f.id
              JOIN {modules} m ON m.id = cm.module
              JOIN (
                  SELECT f.id
                    FROM {peerforum} f
                    {$join}
                   WHERE ff.userid = :userid
                GROUP BY f.id
                   ) j ON j.id = f.id
             WHERE m.name = :peerforum
                 {$coursewhere}";

    $coursepeerforums = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    return $coursepeerforums;
}

/**
 * Returns posts made by the selected user in the requested courses.
 *
 * This method can be used to return all of the posts made by the requested user
 * within the given courses.
 * For each course the access of the current user and requested user is checked
 * and then for each post access to the post and peerforum is checked as well.
 *
 * This function is safe to use with usercapabilities.
 *
 * @param stdClass $user The user whose posts we want to get
 * @param array $courses The courses to search
 * @param bool $musthaveaccess If set to true errors will be thrown if the user
 *                             cannot access one or more of the courses to search
 * @param bool $discussionsonly If set to true only discussion starting posts
 *                              will be returned.
 * @param int $limitfrom The offset of records to return
 * @param int $limitnum The number of records to return
 * @return stdClass An object the following properties
 *               ->totalcount: the total number of posts made by the requested user
 *                             that the current user can see.
 *               ->courses: An array of courses the current user can see that the
 *                          requested user has posted in.
 *               ->peerforums: An array of peerforums relating to the posts returned in the
 *                         property below.
 *               ->posts: An array containing the posts to show for this request.
 * @global moodle_database $DB
 */
function peerforum_get_posts_by_user($user, array $courses, $musthaveaccess = false, $discussionsonly = false, $limitfrom = 0,
        $limitnum = 50) {
    global $DB, $USER, $CFG;

    $return = new stdClass;
    $return->totalcount = 0;    // The total number of posts that the current user is able to view
    $return->courses = array(); // The courses the current user can access
    $return->peerforums = array();  // The peerforums that the current user can access that contain posts
    $return->posts = array();   // The posts to display

    // First up a small sanity check. If there are no courses to check we can
    // return immediately, there is obviously nothing to search.
    if (empty($courses)) {
        return $return;
    }

    // A couple of quick setups
    $isloggedin = isloggedin();
    $isguestuser = $isloggedin && isguestuser();
    $iscurrentuser = $isloggedin && $USER->id == $user->id;

    // Checkout whether or not the current user has capabilities over the requested
    // user and if so they have the capabilities required to view the requested
    // users content.
    $usercontext = context_user::instance($user->id, MUST_EXIST);
    $hascapsonuser = !$iscurrentuser &&
            $DB->record_exists('role_assignments', array('userid' => $USER->id, 'contextid' => $usercontext->id));
    $hascapsonuser =
            $hascapsonuser && has_all_capabilities(array('moodle/user:viewdetails', 'moodle/user:readuserposts'), $usercontext);

    // Before we actually search each course we need to check the user's access to the
    // course. If the user doesn't have the appropraite access then we either throw an
    // error if a particular course was requested or we just skip over the course.
    foreach ($courses as $course) {
        $coursecontext = context_course::instance($course->id, MUST_EXIST);
        if ($iscurrentuser || $hascapsonuser) {
            // If it is the current user, or the current user has capabilities to the
            // requested user then all we need to do is check the requested users
            // current access to the course.
            // Note: There is no need to check group access or anything of the like
            // as either the current user is the requested user, or has granted
            // capabilities on the requested user. Either way they can see what the
            // requested user posted, although its VERY unlikely in the `parent` situation
            // that the current user will be able to view the posts in context.
            if (!is_viewing($coursecontext, $user) && !is_enrolled($coursecontext, $user)) {
                // Need to have full access to a course to see the rest of own info
                if ($musthaveaccess) {
                    print_error('errorenrolmentrequired', 'peerforum');
                }
                continue;
            }
        } else {
            // Check whether the current user is enrolled or has access to view the course
            // if they don't we immediately have a problem.
            if (!can_access_course($course)) {
                if ($musthaveaccess) {
                    print_error('errorenrolmentrequired', 'peerforum');
                }
                continue;
            }

            // If groups are in use and enforced throughout the course then make sure
            // we can meet in at least one course level group.
            // Note that we check if either the current user or the requested user have
            // the capability to access all groups. This is because with that capability
            // a user in group A could post in the group B peerforum. Grrrr.
            if (groups_get_course_groupmode($course) == SEPARATEGROUPS && $course->groupmodeforce
                    && !has_capability('moodle/site:accessallgroups', $coursecontext) &&
                    !has_capability('moodle/site:accessallgroups', $coursecontext, $user->id)) {
                // If its the guest user to bad... the guest user cannot access groups
                if (!$isloggedin or $isguestuser) {
                    // do not use require_login() here because we might have already used require_login($course)
                    if ($musthaveaccess) {
                        redirect(get_login_url());
                    }
                    continue;
                }
                // Get the groups of the current user
                $mygroups = array_keys(groups_get_all_groups($course->id, $USER->id, $course->defaultgroupingid, 'g.id, g.name'));
                // Get the groups the requested user is a member of
                $usergroups = array_keys(groups_get_all_groups($course->id, $user->id, $course->defaultgroupingid, 'g.id, g.name'));
                // Check whether they are members of the same group. If they are great.
                $intersect = array_intersect($mygroups, $usergroups);
                if (empty($intersect)) {
                    // But they're not... if it was a specific course throw an error otherwise
                    // just skip this course so that it is not searched.
                    if ($musthaveaccess) {
                        print_error("groupnotamember", '', $CFG->wwwroot . "/course/view.php?id=$course->id");
                    }
                    continue;
                }
            }
        }
        // Woo hoo we got this far which means the current user can search this
        // this course for the requested user. Although this is only the course accessibility
        // handling that is complete, the peerforum accessibility tests are yet to come.
        $return->courses[$course->id] = $course;
    }
    // No longer beed $courses array - lose it not it may be big
    unset($courses);

    // Make sure that we have some courses to search
    if (empty($return->courses)) {
        // If we don't have any courses to search then the reality is that the current
        // user doesn't have access to any courses is which the requested user has posted.
        // Although we do know at this point that the requested user has posts.
        if ($musthaveaccess) {
            print_error('permissiondenied');
        } else {
            return $return;
        }
    }

    // Next step: Collect all of the peerforums that we will want to search.
    // It is important to note that this step isn't actually about searching, it is
    // about determining which peerforums we can search by testing accessibility.
    $peerforums = peerforum_get_peerforums_user_posted_in($user, array_keys($return->courses), $discussionsonly);

    // Will be used to build the where conditions for the search
    $peerforumsearchwhere = array();
    // Will be used to store the where condition params for the search
    $peerforumsearchparams = array();
    // Will record peerforums where the user can freely access everything
    $peerforumsearchfullaccess = array();
    // DB caching friendly
    $now = floor(time() / 60) * 60;
    // For each course to search we want to find the peerforums the user has posted in
    // and providing the current user can access the peerforum create a search condition
    // for the peerforum to get the requested users posts.
    foreach ($return->courses as $course) {
        // Now we need to get the peerforums
        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->instances['peerforum'])) {
            // hmmm, no peerforums? well at least its easy... skip!
            continue;
        }
        // Iterate
        foreach ($modinfo->get_instances_of('peerforum') as $peerforumid => $cm) {
            if (!$cm->uservisible or !isset($peerforums[$peerforumid])) {
                continue;
            }
            // Get the peerforum in question
            $peerforum = $peerforums[$peerforumid];

            // This is needed for functionality later on in the peerforum code. It is converted to an object
            // because the cm_info is readonly from 2.6. This is a dirty hack because some other parts of the
            // code were expecting an writeable object. See {@link peerforum_print_post()}.
            $peerforum->cm = new stdClass();
            foreach ($cm as $key => $value) {
                $peerforum->cm->$key = $value;
            }

            // Check that either the current user can view the peerforum, or that the
            // current user has capabilities over the requested user and the requested
            // user can view the discussion
            if (!has_capability('mod/peerforum:viewdiscussion', $cm->context) &&
                    !($hascapsonuser && has_capability('mod/peerforum:viewdiscussion', $cm->context, $user->id))) {
                continue;
            }

            // This will contain peerforum specific where clauses
            $peerforumsearchselect = array();
            if (!$iscurrentuser && !$hascapsonuser) {
                // Make sure we check group access
                if (groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS and
                        !has_capability('moodle/site:accessallgroups', $cm->context)) {
                    $groups = $modinfo->get_groups($cm->groupingid);
                    $groups[] = -1;
                    list($groupid_sql, $groupid_params) =
                            $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED, 'grps' . $peerforumid . '_');
                    $peerforumsearchparams = array_merge($peerforumsearchparams, $groupid_params);
                    $peerforumsearchselect[] = "d.groupid $groupid_sql";
                }

                // hidden timed discussions
                if (!empty($CFG->peerforum_enabletimedposts) &&
                        !has_capability('mod/peerforum:viewhiddentimedposts', $cm->context)) {
                    $peerforumsearchselect[] =
                            "(d.userid = :userid{$peerforumid} OR (d.timestart < :timestart{$peerforumid} AND (d.timeend = 0 OR d.timeend > :timeend{$peerforumid})))";
                    $peerforumsearchparams['userid' . $peerforumid] = $user->id;
                    $peerforumsearchparams['timestart' . $peerforumid] = $now;
                    $peerforumsearchparams['timeend' . $peerforumid] = $now;
                }

                // qanda access
                if ($peerforum->type == 'qanda' && !has_capability('mod/peerforum:viewqandawithoutposting', $cm->context)) {
                    // We need to check whether the user has posted in the qanda peerforum.
                    $discussionspostedin = peerforum_discussions_user_has_posted_in($peerforum->id, $user->id);
                    if (!empty($discussionspostedin)) {
                        $peerforumonlydiscussions =
                                array();  // Holds discussion ids for the discussions the user is allowed to see in this peerforum.
                        foreach ($discussionspostedin as $d) {
                            $peerforumonlydiscussions[] = $d->id;
                        }
                        list($discussionid_sql, $discussionid_params) =
                                $DB->get_in_or_equal($peerforumonlydiscussions, SQL_PARAMS_NAMED, 'qanda' . $peerforumid . '_');
                        $peerforumsearchparams = array_merge($peerforumsearchparams, $discussionid_params);
                        $peerforumsearchselect[] = "(d.id $discussionid_sql OR p.parent = 0)";
                    } else {
                        $peerforumsearchselect[] = "p.parent = 0";
                    }

                }

                if (count($peerforumsearchselect) > 0) {
                    $peerforumsearchwhere[] =
                            "(d.peerforum = :peerforum{$peerforumid} AND " . implode(" AND ", $peerforumsearchselect) . ")";
                    $peerforumsearchparams['peerforum' . $peerforumid] = $peerforumid;
                } else {
                    $peerforumsearchfullaccess[] = $peerforumid;
                }
            } else {
                // The current user/parent can see all of their own posts
                $peerforumsearchfullaccess[] = $peerforumid;
            }
        }
    }

    // If we dont have any search conditions, and we don't have any peerforums where
    // the user has full access then we just return the default.
    if (empty($peerforumsearchwhere) && empty($peerforumsearchfullaccess)) {
        return $return;
    }

    // Prepare a where condition for the full access peerforums.
    if (count($peerforumsearchfullaccess) > 0) {
        list($fullidsql, $fullidparams) = $DB->get_in_or_equal($peerforumsearchfullaccess, SQL_PARAMS_NAMED, 'fula');
        $peerforumsearchparams = array_merge($peerforumsearchparams, $fullidparams);
        $peerforumsearchwhere[] = "(d.peerforum $fullidsql)";
    }

    // Prepare SQL to both count and search.
    // We alias user.id to useridx because we peerforum_posts already has a userid field and not aliasing this would break
    // oracle and mssql.
    $userfields = user_picture::fields('u', null, 'useridx');
    $countsql = 'SELECT COUNT(*) ';
    $selectsql = 'SELECT p.*, d.peerforum, d.name AS discussionname, ' . $userfields . ' ';
    $wheresql = implode(" OR ", $peerforumsearchwhere);

    if ($discussionsonly) {
        if ($wheresql == '') {
            $wheresql = 'p.parent = 0';
        } else {
            $wheresql = 'p.parent = 0 AND (' . $wheresql . ')';
        }
    }

    $sql = "FROM {peerforum_posts} p
            JOIN {peerforum_discussions} d ON d.id = p.discussion
            JOIN {user} u ON u.id = p.userid
           WHERE ($wheresql)
             AND p.userid = :userid ";
    $orderby = "ORDER BY p.modified DESC";
    $peerforumsearchparams['userid'] = $user->id;

    // Set the total number posts made by the requested user that the current user can see
    $return->totalcount = $DB->count_records_sql($countsql . $sql, $peerforumsearchparams);
    // Set the collection of posts that has been requested
    $return->posts = $DB->get_records_sql($selectsql . $sql . $orderby, $peerforumsearchparams, $limitfrom, $limitnum);

    // We need to build an array of peerforums for which posts will be displayed.
    // We do this here to save the caller needing to retrieve them themselves before
    // printing these peerforums posts. Given we have the peerforums already there is
    // practically no overhead here.
    foreach ($return->posts as $post) {
        if (!array_key_exists($post->peerforum, $return->peerforums)) {
            $return->peerforums[$post->peerforum] = $peerforums[$post->peerforum];
        }
    }

    return $return;
}

/**
 * Set the per-peerforum maildigest option for the specified user.
 *
 * @param stdClass $peerforum The peerforum to set the option for.
 * @param int $maildigest The maildigest option.
 * @param stdClass $user The user object. This defaults to the global $USER object.
 * @throws invalid_digest_setting thrown if an invalid maildigest option is provided.
 */
function peerforum_set_user_maildigest($peerforum, $maildigest, $user = null) {
    global $DB, $USER;

    if (is_number($peerforum)) {
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforum));
    }

    if ($user === null) {
        $user = $USER;
    }

    $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // User must be allowed to see this peerforum.
    require_capability('mod/peerforum:viewdiscussion', $context, $user->id);

    // Validate the maildigest setting.
    $digestoptions = peerforum_get_user_digest_options($user);

    if (!isset($digestoptions[$maildigest])) {
        throw new moodle_exception('invaliddigestsetting', 'mod_peerforum');
    }

    // Attempt to retrieve any existing peerforum digest record.
    $subscription = $DB->get_record('peerforum_digests', array(
            'userid' => $user->id,
            'peerforum' => $peerforum->id,
    ));

    // Create or Update the existing maildigest setting.
    if ($subscription) {
        if ($maildigest == -1) {
            $DB->delete_records('peerforum_digests', array('peerforum' => $peerforum->id, 'userid' => $user->id));
        } else if ($maildigest !== $subscription->maildigest) {
            // Only update the maildigest setting if it's changed.

            $subscription->maildigest = $maildigest;
            $DB->update_record('peerforum_digests', $subscription);
        }
    } else {
        if ($maildigest != -1) {
            // Only insert the maildigest setting if it's non-default.

            $subscription = new stdClass();
            $subscription->peerforum = $peerforum->id;
            $subscription->userid = $user->id;
            $subscription->maildigest = $maildigest;
            $subscription->id = $DB->insert_record('peerforum_digests', $subscription);
        }
    }
}

/**
 * Determine the maildigest setting for the specified user against the
 * specified peerforum.
 *
 * @param Array $digests An array of peerforums and user digest settings.
 * @param stdClass $user The user object containing the id and maildigest default.
 * @param int $peerforumid The ID of the peerforum to check.
 * @return int The calculated maildigest setting for this user and peerforum.
 */
function peerforum_get_user_maildigest_bulk($digests, $user, $peerforumid) {
    if (isset($digests[$peerforumid]) && isset($digests[$peerforumid][$user->id])) {
        $maildigest = $digests[$peerforumid][$user->id];
        if ($maildigest === -1) {
            $maildigest = $user->maildigest;
        }
    } else {
        $maildigest = $user->maildigest;
    }
    return $maildigest;
}

/**
 * Retrieve the list of available user digest options.
 *
 * @param stdClass $user The user object. This defaults to the global $USER object.
 * @return array The mapping of values to digest options.
 */
function peerforum_get_user_digest_options($user = null) {
    global $USER;

    // Revert to the global user object.
    if ($user === null) {
        $user = $USER;
    }

    $digestoptions = array();
    $digestoptions['0'] = get_string('emaildigestoffshort', 'mod_peerforum');
    $digestoptions['1'] = get_string('emaildigestcompleteshort', 'mod_peerforum');
    $digestoptions['2'] = get_string('emaildigestsubjectsshort', 'mod_peerforum');

    // We need to add the default digest option at the end - it relies on
    // the contents of the existing values.
    $digestoptions['-1'] = get_string('emaildigestdefault', 'mod_peerforum',
            $digestoptions[$user->maildigest]);

    // Resort the options to be in a sensible order.
    ksort($digestoptions);

    return $digestoptions;
}

/**
 * Determine the current context if one was not already specified.
 *
 * If a context of type context_module is specified, it is immediately
 * returned and not checked.
 *
 * @param int $peerforumid The ID of the peerforum
 * @param context_module $context The current context.
 * @return context_module The context determined
 */
function peerforum_get_context($peerforumid, $context = null) {
    global $PAGE;

    if (!$context || !($context instanceof context_module)) {
        // Find out peerforum context. First try to take current page context to save on DB query.
        if ($PAGE->cm && $PAGE->cm->modname === 'peerforum' && $PAGE->cm->instance == $peerforumid
                && $PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->context->instanceid == $PAGE->cm->id) {
            $context = $PAGE->context;
        } else {
            $cm = get_coursemodule_from_instance('peerforum', $peerforumid);
            $context = \context_module::instance($cm->id);
        }
    }

    return $context;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $peerforum peerforum object
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @since Moodle 2.9
 */
function peerforum_view($peerforum, $course, $cm, $context) {

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // Trigger course_module_viewed event.

    $params = array(
            'context' => $context,
            'objectid' => $peerforum->id
    );

    $event = \mod_peerforum\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('peerforum', $peerforum);
    $event->trigger();
}

/**
 * Trigger the discussion viewed event
 *
 * @param stdClass $modcontext module context object
 * @param stdClass $peerforum peerforum object
 * @param stdClass $discussion discussion object
 * @since Moodle 2.9
 */
function peerforum_discussion_view($modcontext, $peerforum, $discussion) {
    $params = array(
            'context' => $modcontext,
            'objectid' => $discussion->id,
    );

    $event = \mod_peerforum\event\discussion_viewed::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussion);
    $event->add_record_snapshot('peerforum', $peerforum);
    $event->trigger();
}

/**
 * Set the discussion to pinned and trigger the discussion pinned event
 *
 * @param stdClass $modcontext module context object
 * @param stdClass $peerforum peerforum object
 * @param stdClass $discussion discussion object
 * @since Moodle 3.1
 */
function peerforum_discussion_pin($modcontext, $peerforum, $discussion) {
    global $DB;

    $DB->set_field('peerforum_discussions', 'pinned', PEERFORUM_DISCUSSION_PINNED, array('id' => $discussion->id));

    $params = array(
            'context' => $modcontext,
            'objectid' => $discussion->id,
            'other' => array('peerforumid' => $peerforum->id)
    );

    $event = \mod_peerforum\event\discussion_pinned::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussion);
    $event->trigger();
}

/**
 * Set discussion to unpinned and trigger the discussion unpin event
 *
 * @param stdClass $modcontext module context object
 * @param stdClass $peerforum peerforum object
 * @param stdClass $discussion discussion object
 * @since Moodle 3.1
 */
function peerforum_discussion_unpin($modcontext, $peerforum, $discussion) {
    global $DB;

    $DB->set_field('peerforum_discussions', 'pinned', PEERFORUM_DISCUSSION_UNPINNED, array('id' => $discussion->id));

    $params = array(
            'context' => $modcontext,
            'objectid' => $discussion->id,
            'other' => array('peerforumid' => $peerforum->id)
    );

    $event = \mod_peerforum\event\discussion_unpinned::create($params);
    $event->add_record_snapshot('peerforum_discussions', $discussion);
    $event->trigger();
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function mod_peerforum_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (isguestuser($user)) {
        // The guest user cannot post, so it is not possible to view any posts.
        // May as well just bail aggressively here.
        return false;
    }
    $postsurl = new moodle_url('/mod/peerforum/user.php', array('id' => $user->id));
    if (!empty($course)) {
        $postsurl->param('course', $course->id);
    }
    $string = get_string('peerforumposts', 'mod_peerforum');
    $node = new core_user\output\myprofile\node('miscellaneous', 'peerforumposts', $string, null, $postsurl);
    $tree->add_node($node);

    $discussionssurl = new moodle_url('/mod/peerforum/user.php', array('id' => $user->id, 'mode' => 'discussions'));
    if (!empty($course)) {
        $discussionssurl->param('course', $course->id);
    }
    $string = get_string('myprofileotherdis', 'mod_peerforum');
    $node = new core_user\output\myprofile\node('miscellaneous', 'peerforumdiscussions', $string, null,
            $discussionssurl);
    $tree->add_node($node);

    return true;
}

/**
 * Checks whether the author's name and picture for a given post should be hidden or not.
 *
 * @param object $post The peerforum post.
 * @param object $peerforum The peerforum object.
 * @return bool
 * @throws coding_exception
 */
function peerforum_is_author_hidden($post, $peerforum) {
    if (!isset($post->parent)) {
        throw new coding_exception('$post->parent must be set.');
    }
    if (!isset($peerforum->type)) {
        throw new coding_exception('$peerforum->type must be set.');
    }
    if ($peerforum->type === 'single' && empty($post->parent)) {
        return true;
    }
    return false;
}

/**
 * Manage inplace editable saves.
 *
 * @param string $itemtype The type of item.
 * @param int $itemid The ID of the item.
 * @param mixed $newvalue The new value
 * @return  string
 */
function mod_peerforum_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    if ($itemtype === 'digestoptions') {
        // The itemid is the peerforumid.
        $peerforum = $DB->get_record('peerforum', array('id' => $itemid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $PAGE->set_context($context);
        require_login($course, false, $cm);
        peerforum_set_user_maildigest($peerforum, $newvalue);

        $renderer = $PAGE->get_renderer('mod_peerforum');
        return $renderer->render_digest_options($peerforum, $newvalue);
    }
}

/**
 * Determine whether the specified peerforum's cutoff date is reached.
 *
 * @param stdClass $peerforum The peerforum
 * @return bool
 */
function peerforum_is_cutoff_date_reached($peerforum) {
    $entityfactory = \mod_peerforum\local\container::get_entity_factory();
    $coursemoduleinfo = get_fast_modinfo($peerforum->course);
    $cminfo = $coursemoduleinfo->instances['peerforum'][$peerforum->id];
    $peerforumentity = $entityfactory->get_peerforum_from_stdclass(
            $peerforum,
            context_module::instance($cminfo->id),
            $cminfo->get_course_module_record(),
            $cminfo->get_course()
    );

    return $peerforumentity->is_cutoff_date_reached();
}

/**
 * Determine whether the specified peerforum's due date is reached.
 *
 * @param stdClass $peerforum The peerforum
 * @return bool
 */
function peerforum_is_due_date_reached($peerforum) {
    $entityfactory = \mod_peerforum\local\container::get_entity_factory();
    $coursemoduleinfo = get_fast_modinfo($peerforum->course);
    $cminfo = $coursemoduleinfo->instances['peerforum'][$peerforum->id];
    $peerforumentity = $entityfactory->get_peerforum_from_stdclass(
            $peerforum,
            context_module::instance($cminfo->id),
            $cminfo->get_course_module_record(),
            $cminfo->get_course()
    );

    return $peerforumentity->is_due_date_reached();
}

/**
 * Determine whether the specified discussion is time-locked.
 *
 * @param stdClass $peerforum The peerforum that the discussion belongs to
 * @param stdClass $discussion The discussion to test
 * @return  bool
 */
function peerforum_discussion_is_locked($peerforum, $discussion) {
    $entityfactory = \mod_peerforum\local\container::get_entity_factory();
    $coursemoduleinfo = get_fast_modinfo($peerforum->course);
    $cminfo = $coursemoduleinfo->instances['peerforum'][$peerforum->id];
    $peerforumentity = $entityfactory->get_peerforum_from_stdclass(
            $peerforum,
            context_module::instance($cminfo->id),
            $cminfo->get_course_module_record(),
            $cminfo->get_course()
    );
    $discussionentity = $entityfactory->get_discussion_from_stdclass($discussion);

    return $peerforumentity->is_discussion_locked($discussionentity);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param cm_info $cm course module data
 * @param int $from the time to check updates from
 * @param array $filter if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function peerforum_check_updates_since(cm_info $cm, $from, $filter = array()) {

    $context = $cm->context;
    $updates = new stdClass();
    if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
        return $updates;
    }

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check if there are new discussions in the peerforum.
    $updates->discussions = (object) array('updated' => false);
    $discussions = peerforum_get_discussions($cm, '', false, -1, -1, true, -1, 0, PEERFORUM_POSTS_ALL_USER_GROUPS, $from);
    if (!empty($discussions)) {
        $updates->discussions->updated = true;
        $updates->discussions->itemids = array_keys($discussions);
    }

    return $updates;
}

/**
 * Check if the user can create attachments in a peerforum.
 *
 * @param stdClass $peerforum peerforum object
 * @param stdClass $context context object
 * @return bool true if the user can create attachments, false otherwise
 * @since  Moodle 3.3
 */
function peerforum_can_create_attachment($peerforum, $context) {
    // If maxbytes == 1 it means no attachments at all.
    if (empty($peerforum->maxattachments) || $peerforum->maxbytes == 1 ||
            !has_capability('mod/peerforum:createattachment', $context)) {
        return false;
    }
    return true;
}

/**
 * Get icon mapping for font-awesome.
 *
 * @return  array
 */
function mod_peerforum_get_fontawesome_icon_map() {
    return [
            'mod_peerforum:i/pinned' => 'fa-map-pin',
            'mod_peerforum:t/selected' => 'fa-check',
            'mod_peerforum:t/subscribed' => 'fa-envelope-o',
            'mod_peerforum:t/unsubscribed' => 'fa-envelope-open-o',
            'mod_peerforum:t/star' => 'fa-star',
    ];
}

/**
 * Callback function that determines whether an action event should be showing its item count
 * based on the event type and the item count.
 *
 * @param calendar_event $event The calendar event.
 * @param int $itemcount The item count associated with the action event.
 * @return bool
 */
function mod_peerforum_core_calendar_event_action_shows_item_count(calendar_event $event, $itemcount = 0) {
    // Always show item count for peerforums if item count is greater than 1.
    // If only one action is required than it is obvious and we don't show it for other modules.
    return $itemcount > 1;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_peerforum_core_calendar_provide_event_action(calendar_event $event,
        \core_calendar\action_factory $factory,
        int $userid = 0) {
    global $DB, $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['peerforum'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $context = context_module::instance($cm->id);

    if (!has_capability('mod/peerforum:viewdiscussion', $context, $userid)) {
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    // Get action itemcount.
    $itemcount = 0;
    $peerforum = $DB->get_record('peerforum', array('id' => $cm->instance));
    $postcountsql = "
                SELECT
                    COUNT(1)
                  FROM
                    {peerforum_posts} fp
                    INNER JOIN {peerforum_discussions} fd ON fp.discussion=fd.id
                 WHERE
                    fp.userid=:userid AND fd.peerforum=:peerforumid";
    $postcountparams = array('userid' => $userid, 'peerforumid' => $peerforum->id);

    if ($peerforum->completiondiscussions) {
        $count = $DB->count_records('peerforum_discussions', array('peerforum' => $peerforum->id, 'userid' => $userid));
        $itemcount += ($peerforum->completiondiscussions >= $count) ? ($peerforum->completiondiscussions - $count) : 0;
    }

    if ($peerforum->completionreplies) {
        $count = $DB->get_field_sql($postcountsql . ' AND fp.parent<>0', $postcountparams);
        $itemcount += ($peerforum->completionreplies >= $count) ? ($peerforum->completionreplies - $count) : 0;
    }

    if ($peerforum->completionposts) {
        $count = $DB->get_field_sql($postcountsql, $postcountparams);
        $itemcount += ($peerforum->completionposts >= $count) ? ($peerforum->completionposts - $count) : 0;
    }

    // Well there is always atleast one actionable item (view peerforum, etc).
    $itemcount = $itemcount > 0 ? $itemcount : 1;

    return $factory->create_instance(
            get_string('view'),
            new \moodle_url('/mod/peerforum/view.php', ['id' => $cm->id]),
            $itemcount,
            true
    );
}

/**
 * Add a get_coursemodule_info function in case any peerforum type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function peerforum_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionposts, completiondiscussions, completionreplies';
    if (!$peerforum = $DB->get_record('peerforum', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $peerforum->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('peerforum', $peerforum, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completiondiscussions'] = $peerforum->completiondiscussions;
        $result->customdata['customcompletionrules']['completionreplies'] = $peerforum->completionreplies;
        $result->customdata['customcompletionrules']['completionposts'] = $peerforum->completionposts;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_peerforum_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
            || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completiondiscussions':
                if (!empty($val)) {
                    $descriptions[] = get_string('completiondiscussionsdesc', 'peerforum', $val);
                }
                break;
            case 'completionreplies':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionrepliesdesc', 'peerforum', $val);
                }
                break;
            case 'completionposts':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionpostsdesc', 'peerforum', $val);
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Check whether the peerforum post is a private reply visible to this user.
 *
 * @param stdClass $post The post to check.
 * @param cm_info $cm The context module instance.
 * @return  bool                Whether the post is visible in terms of private reply configuration.
 */
function peerforum_post_is_visible_privately($post, $cm) {
    global $USER;

    if (!empty($post->privatereplyto)) {
        // Allow the user to see the private reply if:
        // * they hold the permission;
        // * they are the author; or
        // * they are the intended recipient.
        $cansee = false;
        $cansee = $cansee || ($post->userid == $USER->id);
        $cansee = $cansee || ($post->privatereplyto == $USER->id);
        $cansee = $cansee || has_capability('mod/peerforum:readprivatereplies', context_module::instance($cm->id));
        return $cansee;
    }

    return true;
}

/**
 * Check whether the user can reply privately to the parent post.
 *
 * @param \context_module $context
 * @param \stdClass $parent
 * @return  bool
 */
function peerforum_user_can_reply_privately(\context_module $context, \stdClass $parent): bool {
    if ($parent->privatereplyto) {
        // You cannot reply privately to a post which is, itself, a private reply.
        return false;
    }

    return has_capability('mod/peerforum:postprivatereply', $context);
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The date must be after this date'],
 *     [1506741172, 'The date must be before this date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $peerforum The module instance to get the range from
 * @return array Returns an array with min and max date.
 */
function mod_peerforum_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $peerforum) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/peerforum/locallib.php');

    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == PEERFORUM_EVENT_TYPE_DUE) {
        if (!empty($peerforum->cutoffdate)) {
            $maxdate = [
                    $peerforum->cutoffdate,
                    get_string('cutoffdatevalidation', 'peerforum'),
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the peerforum module according to the
 * event that has been modified.
 *
 * It will set the timeclose value of the peerforum instance
 * according to the type of event provided.
 *
 * @param \calendar_event $event
 * @param stdClass $peerforum The module instance to get the range from
 * @throws \moodle_exception
 */
function mod_peerforum_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $peerforum) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/peerforum/locallib.php');

    if ($event->eventtype != PEERFORUM_EVENT_TYPE_DUE) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;

    // Something weird going on. The event is for a different module so
    // we should ignore it.
    if ($modulename != 'peerforum') {
        return;
    }

    if ($peerforum->id != $instanceid) {
        return;
    }

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == PEERFORUM_EVENT_TYPE_DUE) {
        if ($peerforum->duedate != $event->timestart) {
            $peerforum->duedate = $event->timestart;
            $peerforum->timemodified = time();
            // Persist the instance changes.
            $DB->update_record('peerforum', $peerforum);
            $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
            $event->trigger();
        }
    }
}

/**
 * Fetch the data used to display the discussions on the current page.
 *
 * @param \mod_peerforum\local\entities\peerforum $peerforum The peerforum entity
 * @param stdClass $user The user to render for
 * @param int[]|null $groupid The group to render
 * @param int|null $sortorder The sort order to use when selecting the discussions in the list
 * @param int|null $pageno The zero-indexed page number to use
 * @param int|null $pagesize The number of discussions to show on the page
 * @return  array                            The data to use for display
 */
function mod_peerforum_get_discussion_summaries(\mod_peerforum\local\entities\peerforum $peerforum, stdClass $user, ?int $groupid,
        ?int $sortorder,
        ?int $pageno = 0, ?int $pagesize = 0) {

    $vaultfactory = mod_peerforum\local\container::get_vault_factory();
    $discussionvault = $vaultfactory->get_discussions_in_peerforum_vault();
    $managerfactory = mod_peerforum\local\container::get_manager_factory();
    $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

    $groupids = mod_peerforum_get_groups_from_groupid($peerforum, $user, $groupid);

    if (null === $groupids) {
        return $discussions = $discussionvault->get_from_peerforum_id(
                $peerforum->get_id(),
                $capabilitymanager->can_view_hidden_posts($user),
                $user->id,
                $sortorder,
                $pagesize,
                $pageno * $pagesize);
    } else {
        return $discussions = $discussionvault->get_from_peerforum_id_and_group_id(
                $peerforum->get_id(),
                $groupids,
                $capabilitymanager->can_view_hidden_posts($user),
                $user->id,
                $sortorder,
                $pagesize,
                $pageno * $pagesize);
    }
}

/**
 * Get a count of all discussions in a peerforum.
 *
 * @param \mod_peerforum\local\entities\peerforum $peerforum The peerforum entity
 * @param stdClass $user The user to render for
 * @param int $groupid The group to render
 * @return  int                              The number of discussions in a peerforum
 */
function mod_peerforum_count_all_discussions(\mod_peerforum\local\entities\peerforum $peerforum, stdClass $user, ?int $groupid) {

    $managerfactory = mod_peerforum\local\container::get_manager_factory();
    $capabilitymanager = $managerfactory->get_capability_manager($peerforum);
    $vaultfactory = mod_peerforum\local\container::get_vault_factory();
    $discussionvault = $vaultfactory->get_discussions_in_peerforum_vault();

    $groupids = mod_peerforum_get_groups_from_groupid($peerforum, $user, $groupid);

    if (null === $groupids) {
        return $discussionvault->get_total_discussion_count_from_peerforum_id(
                $peerforum->get_id(),
                $capabilitymanager->can_view_hidden_posts($user),
                $user->id);
    } else {
        return $discussionvault->get_total_discussion_count_from_peerforum_id_and_group_id(
                $peerforum->get_id(),
                $groupids,
                $capabilitymanager->can_view_hidden_posts($user),
                $user->id);
    }
}

/**
 * Get the list of groups to show based on the current user and requested groupid.
 *
 * @param \mod_peerforum\local\entities\peerforum $peerforum The peerforum entity
 * @param stdClass $user The user viewing
 * @param int $groupid The groupid requested
 * @return  array                            The list of groups to show
 */
function mod_peerforum_get_groups_from_groupid(\mod_peerforum\local\entities\peerforum $peerforum, stdClass $user,
        ?int $groupid): ?array {

    $effectivegroupmode = $peerforum->get_effective_group_mode();
    if (empty($effectivegroupmode)) {
        // This peerforum is not in a group mode. Show all posts always.
        return null;
    }

    if (null == $groupid) {
        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);
        // No group was specified.
        $showallgroups = (VISIBLEGROUPS == $effectivegroupmode);
        $showallgroups = $showallgroups || $capabilitymanager->can_access_all_groups($user);
        if ($showallgroups) {
            // Return null to show all groups.
            return null;
        } else {
            // No group was specified. Only show the users current groups.
            return array_keys(
                    groups_get_all_groups(
                            $peerforum->get_course_id(),
                            $user->id,
                            $peerforum->get_course_module_record()->groupingid
                    )
            );
        }
    } else {
        // A group was specified. Just show that group.
        return [$groupid];
    }
}

/**
 * Return a list of all the user preferences used by mod_peerforum.
 *
 * @return array
 */
function mod_peerforum_user_preferences() {
    $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
    $discussionlistvault = $vaultfactory->get_discussions_in_peerforum_vault();

    $preferences = array();
    $preferences['peerforum_discussionlistsortorder'] = array(
            'null' => NULL_NOT_ALLOWED,
            'default' => $discussionlistvault::SORTORDER_LASTPOST_DESC,
            'type' => PARAM_INT,
            'choices' => array(
                    $discussionlistvault::SORTORDER_LASTPOST_DESC,
                    $discussionlistvault::SORTORDER_LASTPOST_ASC,
                    $discussionlistvault::SORTORDER_CREATED_DESC,
                    $discussionlistvault::SORTORDER_CREATED_ASC,
                    $discussionlistvault::SORTORDER_REPLIES_DESC,
                    $discussionlistvault::SORTORDER_REPLIES_ASC
            )
    );
    $preferences['peerforum_useexperimentalui'] = [
            'null' => NULL_NOT_ALLOWED,
            'default' => false,
            'type' => PARAM_BOOL
    ];

    return $preferences;
}

/**
 * Lists all gradable areas for the advanced grading methods gramework.
 *
 * @return array('string'=>'string') An array with area names as keys and descriptions as values
 */
function peerforum_grading_areas_list() {
    return [
            'peerforum' => get_string('grade_peerforum_header', 'peerforum'),
    ];
}

/**
 * Returns all the students alphabetically ordered
 *
 * @param array $student_data
 * @return array all the students alphabetically ordered.
 * @global object
 */
function get_students_ordered_by_name($student_data) {
    global $DB;
    $names = array();

    foreach ($student_data as $i => $value) {
        $id = $student_data[$i]->userid;
        $user_name = $DB->get_record('user', array('id' => $id));
        array_push($names, "$user_name->firstname $user_name->lastname");

    }

    uasort($names, function($a, $b) {
        return strcmp($a, $b);
    });

    return $names;
}

function remove_own_student($all_students, $userid) {
    global $DB;
    $user_name = $DB->get_record('user', array('id' => $userid));
    $user_name->name = $user_name->firstname . ' ' . $user_name->lastname;

    foreach ($all_students as $key => $value) {
        if ($all_students[$key] == $user_name->name) {
            unset($all_students[$key]);
            array_values($all_students);
        }
    }

    return $all_students;
}

function advanced_peergrading_enabled($cm, $courseid) {
    $info = get_moduleinfo_data($cm, $course);
    if ($info->adv_peergrading) {
        return true;
    } else {
        return false;
    }
}

//TODO: Remove
function get_all_subjects($list) {
    $all_names = array();
    foreach ($list as $key => $value) {
        array_push($all_names, $list[$key]->name);
    }
    return $all_names;
}

function array_id_to_name($list, $peerforumid) {
    global $DB, $COURSE;

    $names = array();
    $subject_names = $DB->get_records("peerforum_discussions", array('course' => $COURSE->id, 'peerforum' => $peerforumid));

    foreach ($subject_names as $key => $value) {
        array_push($names, $subject_names[$key]->name);
    }
    $newlist = array();
    foreach ($list as $key => $value) {
        array_push($newlist, $names[$list[$key]]);
    }
    return $newlist;
}

function student_answered_questionnaire($user) {
    ///Get all records from 'table' where foo = bar
    global $DB;

    $result = $DB->get_record_sql("SELECT COUNT(1) as count FROM {peerforum_relationships} p WHERE iduser = $user");
    $total = $result->count;

    return $total;
}

function array_has_unique_values($a) {
    return count($a) === count(array_flip($a));
}

function arrays_have_unique_values($a, $b) {
    return array_intersect($a, $b);

}

/**
 * Returns the students that can be assigned to peer grade a post
 *
 * @param int $courseid
 * @param int $postid
 * @param int $postauthor
 * @param int optional $peerforumid
 * @return array with students that can be assigned.
 * @global object
 */
function get_students_can_be_assigned_w_ptpg($courseid, $postid, $postauthor, $peerforumid = null) {
    global $DB;

    $can_be_assigned = array();

    $students = get_students_enroled($courseid);

    adjust_database();

    // Verify students that was already assigned to the post
    $peergraders_db = $DB->get_record('peerforum_posts', array('id' => $postid));

    if (!empty($peergraders_db)) {
        $peergraders = $peergraders_db->peergraders;
        $peergraders = explode(';', $peergraders);
        $peergraders = array_filter($peergraders);

        foreach ($students as $id => $value) {
            $student = $students[$id]->id; //userid ou id

            if (!in_array($student, $peergraders)) {
                $peergraders_info =
                        $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $student));

                if (!empty($peergraders_info)) {
                    $topeergrade = $peergraders_info->poststopeergrade; //!!!
                    $blocked = $peergraders_info->postsblocked;
                    $donepeergrade = $peergraders_info->postspeergradedone;
                    $studentblocked = $peergraders_info->userblocked;

                    $num_posts = $peergraders_info->numpostsassigned;
                    $num_tograde = $peergraders_info->numpoststopeergrade; //new code
                    $topics = $peergraders_info->topicsassigned;     //new code
                    $type = $peergraders_info->peergradetype;     //new code
                    $sum = $peergraders_info->gradesum;     //new code

                    $posts_tograde = explode(';', $topeergrade);
                    $posts_tograde = array_filter($posts_tograde);
                    $block = explode(';', $blocked);
                    $block = array_filter($block);
                    $posts_graded = explode(';', $donepeergrade);
                    $posts_graded = array_filter($posts_graded);

                    // Can peergrade
                    if (!(in_array($postid, $posts_tograde)) && !(in_array($postid, $block)) &&
                            !(in_array($postid, $posts_graded)) && $studentblocked == 0) {
                        $std = new stdClass();
                        $std->userid = $students[$id]->id;
                        $std->numpostsassigned = $num_posts;
                        $std->numpoststopeergrade = $num_tograde; //new code
                        $std->postspeergradedone = $donepeergrade; //new code
                        $std->topicsassigned = $topics; //new code
                        $std->peergradetype = $type; //new code
                        $std->gradesum = $sum; //new code
                        $can_be_assigned[$id] = $std;
                        continue;
                    } // Cannot peergrade
                    else if ((in_array($postid, $posts_tograde)) || (in_array($postid, $block)) ||
                            (in_array($postid, $posts_graded)) && $studentblocked == 1) {
                        continue;

                    }
                } else {
                    // Can peergrade
                    $std = new stdClass();
                    $std->userid = $students[$id]->id;
                    $std->numpostsassigned = 0;
                    $std->numpoststopeergrade = 0; //new code
                    $std->postspeergradedone = 0; //new code
                    $std->gradesum = 0;
                    $std->topicsassigned = "";
                    $std->peergradetype = 2;
                    $can_be_assigned[$id] = $std;

                    continue;
                }
            } else {
                // Cannot peergrade (is in array of peergraders)
                continue;
            }
        }
    } else if (empty($peergraders)) {
        foreach ($students as $id => $value) {
            $num_posts_user =
                    $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $students[$id]->id));
            $studentblocked = $num_posts_user->userblocked;

            if ($studentblocked == 1) {
                continue; //does not need to compute anything else
            }

            if (!empty($num_posts_user)) {
                $num_tograde = $num_posts_user->numpoststopeergrade; //new code
                $donepeergrade = $num_posts_user->postspeergradedone; //new code
                $num_posts = $num_posts_user->numpostsassigned;
                $topics = $num_posts_user->topicsassigned; //new code
                $type = $num_posts_user->peergradetype; //new code
                $sum = $num_posts_user->gradesum; //new code
            } else {
                $num_posts = 0;
                $num_tograde = 0; //new code
                $donepeergrade = 0; //new code
                $topics = ""; //new code
                $type = 2; //new code
                $sum = 0;
            }
            $std = new stdClass();
            $std->userid = $students[$id]->id;
            $std->numpostsassigned = $num_posts;
            $std->numpoststopeergrade = $num_tograde; //new code
            $std->postspeergradedone = $donepeergrade; //new code
            $std->topicsassigned = $topics; //new code
            $std->peergradetype = $type; //new code
            $std->gradesum = $sum; //new code
            $can_be_assigned[$id] = $std;

        }
    }

    // Not assigned to the post
    foreach ($students as $id => $value) {
        if (!in_array($students[$id]->id, $peergraders)) {
            if (!in_array($students[$id]->id, $can_be_assigned)) {
                $num_posts_user = $DB->get_record('peerforum_peergrade_users',
                        array('courseid' => $courseid, 'iduser' => $students[$id]->id));
                $studentblocked = $num_posts_user->userblocked;

                if ($studentblocked == 1) {
                    continue; //does not need to compute anything else
                }

                if (!empty($num_posts_user)) {
                    $num_tograde = $num_posts_user->numpoststopeergrade; //new code
                    $donepeergrade = $num_posts_user->postspeergradedone; //new code
                    $num_posts = $num_posts_user->numpostsassigned;
                    $topics = $num_posts_user->topicsassigned; //new code
                    $type = $num_posts_user->peergradetype; //new code
                    $sum = $num_posts_user->gradesum; //new code
                } else {
                    $num_posts = 0;
                    $num_tograde = 0; //new code
                    $donepeergrade = 0; //new code
                    $topics = ""; //new code
                    $type = 2; //new code
                    $sum = 0;
                }

                $std = new stdClass();
                $std->userid = $students[$id]->id;
                $std->numpostsassigned = $num_posts;
                $std->numpoststopeergrade = $num_tograde; //new code
                $std->postspeergradedone = $donepeergrade; //new code
                $std->topicsassigned = $topics; //new code
                $std->peergradetype = $type; //new code
                $std->gradesum = $sum; //new code
                $can_be_assigned[$id] = $std;

            }
        }
    }

    foreach ($can_be_assigned as $key => $value) {
        if ($can_be_assigned[$key]->userid == $postauthor) {
            unset($can_be_assigned[$key]);
        }
    }

    // Verify conflicts
    $conflicts = $DB->get_records('peerforum_peergrade_conflict', array('courseid' => $courseid));

    foreach ($conflicts as $key => $value) {
        $conflictstds = $conflicts[$key]->idstudents;
        $conflictstds = explode(';', $conflictstds);
        $conflictstds = array_filter($conflictstds);

        foreach ($can_be_assigned as $k => $value) {
            if (in_array($postauthor, $conflictstds)) {
                $id = $can_be_assigned[$k]->id;

                if (in_array($id, $conflictstds)) {
                    unset($can_be_assigned[$k]);
                }
            }
        }
    }
    return $can_be_assigned;
}

function update_gradingsum() {
    global $DB;

    $all_students = $DB->get_records("peerforum_peergrade_users");

    foreach ($all_students as $a => $value) {

        //peergradesdone
        $peergradedone = $all_students[$a]->postspeergradedone;

        if (!empty($peergradedone)) {
            $array_done = explode(";", $peergradedone);
            $numpeerdone = (count($array_done));
        } else {
            $numpeerdone = 0;
        }

        //peergradestodo
        $topeergrade = $all_students[$a]->numpoststopeergrade;

        //sum both values
        $sum = $topeergrade + $numpeerdone;

        //update sum record
        $data = new stdClass();
        $data->id = $all_students[$a]->id;
        $data->gradesum = $sum;
        $DB->update_record("peerforum_peergrade_users", $data);
    }
}

function end_peergrade_post($postid, $peerforum) {
    global $DB;

    $finish_peergrade = $peerforum->finishpeergrade;

    if ($finish_peergrade) {
        $peergrades = $DB->get_records('peerforum_peergrade', array('itemid' => $postid));

        $num_peergrades = count($peergrades);

        $num_ends_peergrade = $peerforum->minpeergraders;

        if ($num_peergrades >= $num_ends_peergrade) {
            //peergrade ends to this post
            return 1;
        } else {
            //do not end peergrade to this post
            return 0;
        }
    }
    //do not end peergrade to this post
    return 0;
}


/**
 * Returns true if the time to peergrade a post has expired for all assigned students
 *
 * @param object $post
 * @param object $peerforum
 * @return bool if post expired for all graders
 */
function post_has_expired($post_topeergrade, $peerforum) {
    global $DB, $COURSE;

    $posthasexpired = false;
    $expiredgraders = 0;

    // Verify if post has expired for all assigned peergraders
    $post_info = $DB->get_record("peerforum_posts", array('id' => $post_topeergrade->parent));

    $peergraders = $post_info->peergraders;
    $peergraders = explode(";", $peergraders);

    if (!empty($peergraders)) {
        for ($i = 0; $i < count($peergraders); $i++) {
            $post_time = verify_post_expired($post_info->id, $peerforum, $peergraders[$i], $COURSE->id);
            if ($post_time->post_expired) {
                $expiredgraders++;
            }
        }
        if ($expiredgraders == count($peergraders)) {
            $posthasexpired = true;
        }
    }

    return $posthasexpired;
}

/**
 * Returns true if a given user is a student.
 *
 * @param int $userid
 * @return bool
 */
function is_user_student($userid) {
    global $COURSE;

    $context = context_course::instance($COURSE->id);
    $isStudent = current(get_user_roles($context, $userid))->shortname == 'student' ? true : false;
    if ($isStudent) {
        return true;
    } else {
        return false;
    }

}

function update_db() {
    global $DB, $COURSE;

    $courseid = $COURSE->id;
    try {
        //Amal
        $grader_info = $DB->get_record("peerforum_peergrade_users", array('iduser' => 1168, 'courseid' => $courseid));
        $data = new stdClass();
        $data->id = $grader_info->id;
        $data->poststopeergrade = "4280";
        $DB->update_record("peerforum_peergrade_users", $data);

        //Joao
        $grader_info2 = $DB->get_record("peerforum_peergrade_users", array('iduser' => 1182, 'courseid' => $courseid));
        $data = new stdClass();
        $data->id = $grader_info2->id;
        $data->poststopeergrade = "4280";
        $DB->update_record("peerforum_peergrade_users", $data);

        //Mafalda
        $grader_info3 = $DB->get_record("peerforum_peergrade_users", array('iduser' => 1098, 'courseid' => $courseid));
        $data = new stdClass();
        $data->id = $grader_info3->id;
        $data->poststopeergrade = "";
        $DB->update_record("peerforum_peergrade_users", $data);

        //Pedro
        $grader_info4 = $DB->get_record("peerforum_peergrade_users", array('iduser' => 1106, 'courseid' => $courseid));
        $data = new stdClass();
        $data->id = $grader_info4->id;
        $data->poststopeergrade = "";
        $DB->update_record("peerforum_peergrade_users", $data);

        //Phillipp
        $grader_info5 = $DB->get_record("peerforum_peergrade_users", array('iduser' => 1096, 'courseid' => $courseid));
        $data = new stdClass();
        $data->id = $grader_info5->id;
        $data->poststopeergrade = "4280";
        $DB->update_record("peerforum_peergrade_users", $data);

        return true;
    } catch (Exception $e) {
        echo 'Something went wrong: ' . $e->getMessage();
        return false;
    }
}

function can_see_peergrades_aggreagate($post, $peerforum) {
    global $DB, $COURSE, $PAGE, $USER;

    $postid = $post->id;
    $post_info = $DB->get_record("peerforum_posts", array('id' => $postid));

    $userid = $USER->id;

    $cContext = context_course::instance($COURSE->id);
    $isstudent = current(get_user_roles($cContext, $userid))->shortname == 'student' ? true : false;

    //if user seeing the page is teacher can see always
    if (!$isstudent) {
        return 1;
    } else {
        /*student can only see aggregate if:
        1) peergrading has ended
        2) post has expired
      */
        $minpeergraders = end_peergrade_post($postid, $peerforum);
        if ($minpeergraders) {
            return 1;
        } else {
            $expired = post_has_expired($post, $peerforum);
            if ($expired) {
                return 1;
            } else {
                return 0;
            }
        }
    }
}

/**
 * Sends a message notifing a user that has a new post to peergrade.
 *
 * @param object $user who is sending the message
 * @param int $userto id of user who is expected to recieve the message
 */
function send_peergrade_notification($userto) {
    global $DB;
    //TODO:change! to 5
    $userfrom = $DB->get_record("user", array('id' => 25));
    $sendto = $DB->get_record("user", array('id' => $userto));

    message_post_message($userfrom, $sendto, "You have a new post to peergrade.", FORMAT_HTML);

    return true;

}

/**
 * Returns true if the time to peergrade a post has expired for all assigned students
 *
 * @param object $post
 * @param object $peerforum
 * @param int $courseid
 * @return object
 */
function post_has_expired_info($post_topeergrade, $peerforum, $courseid) {
    global $DB;

    $posthasexpired = false;
    $expiredgraders = 0;

    $peergraders = $post_topeergrade->peergraders;
    $peergraders = explode(";", $peergraders);

    if (!empty($peergraders)) {
        for ($i = 0; $i < count($peergraders); $i++) {

            $post_time = verify_post_expired($post_topeergrade->id, $peerforum, $peergraders[$i], $courseid);

            if (!empty($post_time)) {
                if ($post_time->post_expired) {
                    $expiredgraders++;
                }
            }
        }
        if ($expiredgraders == count($peergraders)) {
            $posthasexpired = true;
        }
    }

    $post_info = new stdClass();
    $post_info->hasexpired = $posthasexpired;
    $post_info->time = $post_time;

    return $post_info;
}

/**
 * Verifies if a post is close to expiring and returns time information about the post
 *
 * @param int $postid
 * @param stdClass $peerforum
 * @param int $userid
 * @param int $courseid
 * @return stdClass time information about the post.
 * @global object
 * @global object
 */
function verify_post_almost_expired($postid, $peerforum, $userid, $courseid) {
    global $DB, $PAGE;

    //verify if the user can peergrade in a period of time
    $time_assign = get_time_assigned($postid, $userid);

    //TODO: Make this variable configurable
    $expiring = 12;

    if (!empty($time_assign)) {
        $time_assigned_db = usergetdate($time_assign);

        $time_to_peergrade = $peerforum->timetopeergrade;

        $date_time_assigned = new stdClass();
        $date_time_assigned->year = $time_assigned_db['year'];
        $date_time_assigned->mon = $time_assigned_db['mon'];
        $date_time_assigned->mday = $time_assigned_db['mday'];
        $date_time_assigned->hours = $time_assigned_db['hours'];
        $date_time_assigned->minutes = $time_assigned_db['minutes'];
        $date_time_assigned->seconds = $time_assigned_db['seconds'];

        $time_assigned =
                new DateTime("$date_time_assigned->year-$date_time_assigned->mon-$date_time_assigned->mday $date_time_assigned->hours:$date_time_assigned->minutes:$date_time_assigned->seconds");

        $time = 'P' . $time_to_peergrade . 'D';

        $time_finish = $time_assigned;

        $time_finish->add(new DateInterval("$time"));

        $time_current_db = usergetdate(time());

        $date_time_current = new stdClass();
        $date_time_current->year = $time_current_db['year'];
        $date_time_current->mon = $time_current_db['mon'];
        $date_time_current->mday = $time_current_db['mday'];
        $date_time_current->hours = $time_current_db['hours'];
        $date_time_current->minutes = $time_current_db['minutes'];
        $date_time_current->seconds = $time_current_db['seconds'];

        $time_current =
                new DateTime("$date_time_current->year-$date_time_current->mon-$date_time_current->mday $date_time_current->hours:$date_time_current->minutes:$date_time_current->seconds");

        $time_interval = date_diff($time_finish, $time_current);

        $post_expired = true;

        $data = new stdclass();

        if ($time_interval->d < 1 && $time_interval->h < $expiring && $time_interval->invert > 0) {
            $data->almost_expired = true;
            $data->postid = $postid;
            $data->time_interval = $time_interval->h;

        } else if ($time_interval->invert > 0) {
            $data->almost_expired = false;
            $data->postid = $postid;
            $data->time_interval = $time_interval->h;
        }

        return $data;
    } else {
        return null;
    }
}

function get_peers_unranked($userid, $courseid) {
    global $DB;

    //get id of students graded
    //get id of students ranked
    //add to array students graded not in ranked
    $students_unranked = 0;

    $student = $DB->get_record("peerforum_relationships", array('iduser' => $userid));

    if (!empty($student)) {
        $peers_ranked = $student->studentsranked;
        $array_ranks = explode(";", $peers_ranked);
        $user_info = $DB->get_record("peerforum_peergrade_users", array('iduser' => $userid));

        if (!empty($user_info)) { //avoid notices in case of non student
            $students_graded = $user_info->postspeergradedone;
            if (!empty($students_graded)) {
                $array_students_graded = explode(";", $students_graded);
                $rankableid = array(); //array to avoid n posts from the same person count as n ranks

                for ($i = 0; $i < count($array_students_graded); $i++) {
                    $peerpost = $DB->get_record("peerforum_posts", array('id' => $array_students_graded[$i]));
                    $peerid = $peerpost->userid;
                    if (!in_array($peerid, $array_ranks) && !in_array($peerid, $rankableid)) {
                        array_push($rankableid, $peerid);
                        $students_unranked++;
                    }
                }
            }
        }
    }

    return $students_unranked++;
}

/**
 * Returns all the all assigned posts to each student
 *
 * @param int $courseid
 * @return array assigned posts.
 * @global object
 */
function get_all_assigned_posts($courseid) {
    global $DB;
    //should return for each student [userid - xxxxx ,authorname - "Name Surname", posts - [a,b,c,d,e] , feedback - null,"something","more feedback", null, grade - null,3,4,null]
    //get all the posts
    $sql = "SELECT p.iduser, p.poststopeergrade, p.postspeergradedone, p.postsexpired
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    //get all the grades and feedbacks
    $sql2 = "SELECT p.id, p.itemid, p.peergrade, p.userid, p.feedback
            FROM {peerforum_peergrade} p";

    $posts_grades = $DB->get_records_sql($sql2);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $topeergrade = array();
        $donepeergrade = array();
        $expired = array();

        if (!empty($posts[$userid]->poststopeergrade)) {
            $topeergrade = explode(";", $posts[$userid]->poststopeergrade);
        }

        if (!empty($posts[$userid]->postspeergradedone)) {
            $donepeergrade = explode(";", $posts[$userid]->postspeergradedone);
        }

        if (!empty($posts[$userid]->postsexpired)) {
            $expired = explode(";", $posts[$userid]->postsexpired);
        }

        $all_posts_id = array_merge($topeergrade, $donepeergrade, $expired);

        usort($all_posts_id, function($a, $b) {
            return $a > $b;
        });

        $info_post->posts = $all_posts_id;

        if (!empty($info_post->posts)) {
            $info_post->postsgrade = array();
            $info_post->postsfeedback = array();

            foreach ($info_post->posts as $i => $value) {
                $postid = $info_post->posts[$i];
                array_push($info_post->postsgrade, "-");
                array_push($info_post->postsfeedback, "-");
                foreach ($posts_grades as $j => $value) {
                    if ($posts_grades[$j]->itemid == $postid && $posts_grades[$j]->userid == $userid) {
                        $info_post->postsgrade[$i] = $posts_grades[$j]->peergrade;
                        $info_post->postsfeedback[$i] = $posts_grades[$j]->feedback;
                    }
                }
            }

        }
        array_push($all_posts, $info_post);
    }

    return $all_posts;
}

/**
 * Returns expired posts for each student
 *
 * @param int $courseid
 * @return array with expired posts.
 * @global object
 */
function get_all_posts_expired($courseid) {
    global $DB;
    //get all the posts
    $sql = "SELECT p.iduser, p.postsexpired
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $expired = array();

        if (!empty($posts[$userid]->postsexpired)) {
            $expired = explode(";", $posts[$userid]->postsexpired);
        }

        $info_post->posts = $expired;

        if (!empty($info_post->posts)) {
            $info_post->postsgrade = array();
            $info_post->postsfeedback = array();

            foreach ($info_post->posts as $i => $value) {
                $postid = $info_post->posts[$i];
                array_push($info_post->postsgrade, "-");
                array_push($info_post->postsfeedback, "-");
            }

        }
        array_push($all_posts, $info_post);
    }
    return $all_posts;
}

/**
 * Returns active posts for each user
 *
 * @param int $courseid
 * @return array with active posts.
 * @global object
 */
function get_all_posts_not_expired($courseid) {
    global $DB;
    //get all the posts
    $sql = "SELECT p.iduser, p.poststopeergrade
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $topeergrade = array();

        if (!empty($posts[$userid]->poststopeergrade)) {
            $topeergrade = explode(";", $posts[$userid]->poststopeergrade);
        }

        $info_post->posts = $topeergrade;

        if (!empty($info_post->posts)) {
            $info_post->postsgrade = array();
            $info_post->postsfeedback = array();

            foreach ($info_post->posts as $i => $value) {
                $postid = $info_post->posts[$i];
                array_push($info_post->postsgrade, "-");
                array_push($info_post->postsfeedback, "-");
            }

        }
        array_push($all_posts, $info_post);
    }
    return $all_posts;
}

/**
 * Returns graded posts for each user
 *
 * @param int $courseid
 * @return array with graded posts.
 * @global object
 */
function get_all_posts_graded($courseid) {
    global $DB;
    //get all the posts
    $sql = "SELECT p.iduser, p.postspeergradedone
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    //get all the grades and feedbacks
    $sql2 = "SELECT p.id, p.itemid, p.peergrade, p.userid, p.feedback
            FROM {peerforum_peergrade} p";

    $posts_grades = $DB->get_records_sql($sql2);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $done = array();

        if (!empty($posts[$userid]->postspeergradedone)) {
            $done = explode(";", $posts[$userid]->postspeergradedone);
        }

        $info_post->posts = $done;

        if (!empty($info_post->posts)) {
            $info_post->postsgrade = array();
            $info_post->postsfeedback = array();

            foreach ($info_post->posts as $i => $value) {
                $postid = $info_post->posts[$i];
                array_push($info_post->postsgrade, "-");
                array_push($info_post->postsfeedback, "-");
                foreach ($posts_grades as $j => $value) {
                    if ($posts_grades[$j]->itemid == $postid && $posts_grades[$j]->userid == $userid) {
                        $info_post->postsgrade[$i] = $posts_grades[$j]->peergrade;
                        $info_post->postsfeedback[$i] = $posts_grades[$j]->feedback;
                    }
                }
            }

        }
        array_push($all_posts, $info_post);
    }
    return $all_posts;
}

/**
 * Returns posts not graded for each user
 *
 * @param int $courseid
 * @return array with ungraded posts.
 * @global object
 */
function get_all_posts_not_graded($courseid) {
    global $DB;
    //get all the posts
    $sql = "SELECT p.iduser, p.poststopeergrade, p.postsexpired
            FROM {peerforum_peergrade_users} p
            WHERE p.courseid = $courseid";

    $posts = $DB->get_records_sql($sql);

    $all_posts = array();

    foreach ($posts as $userid => $values) {
        $user_db = $DB->get_record('user', array('id' => $userid));

        $info_post = new stdClass;
        $info_post->authorid = $userid;
        $info_post->authorname = $user_db->firstname . ' ' . $user_db->lastname;

        $expired = array();

        if (!empty($posts[$userid]->postsexpired)) {
            $expired = explode(";", $posts[$userid]->postsexpired);
        }

        $topeergrade = array();

        if (!empty($posts[$userid]->poststopeergrade)) {
            $topeergrade = explode(";", $posts[$userid]->poststopeergrade);
        }

        $all_posts_id = array_merge($topeergrade, $expired);
        $info_post->posts = $all_posts_id;

        if (!empty($info_post->posts)) {
            $info_post->postsgrade = array();
            $info_post->postsfeedback = array();

            foreach ($info_post->posts as $i => $value) {
                $postid = $info_post->posts[$i];
                array_push($info_post->postsgrade, "-");
                array_push($info_post->postsfeedback, "-");
            }

        }
        array_push($all_posts, $info_post);
    }
    return $all_posts;
}

/**
 * Update the distribution of students throughout the exisiting discussion topics
 *
 * @param int $courseid
 * @global object
 */
function apply_random_distribution($courseid, $peerforumid) {
    global $DB;
    //Foreach topic get V students and assign them

    $students_info = $DB->get_records("peerforum_peergrade_users", array('courseid' => $courseid));
    //Get all discussion topics
    $discussiontopics = get_discussions_name($courseid, $peerforumid);

    $studentsavailable = count($students_info);

    $index = 1;
    while ($studentsavailable > 0) {
        foreach ($discussiontopics as $key => $value) {
            if ($studentsavailable > 0) {
                $student = $students_info[$index];

                $data = new stdClass();
                $data->id = $student->id;
                $data->peergradetype = 1;
                $data->topicsassigned =
                        $discussiontopics[$key]; //Here we just replace whatever is there, but if new topics are created, this value should be updated
                $DB->update_record("peerforum_peergrade_users", $data);

                $studentsavailable--;
                $index++;

            } else {
                break;
            }
        }
    }
}

/**
 * Update the distribution of students if a discussion topic is deleted
 * when threaded grading is active
 *
 * @param int $courseid
 * @global object
 */
function update_threaded_grading($courseid) {
    // TODO: tHIS
}

function remove_blocked_students($all_students) {

    $eligible_students = array();

    foreach ($all_students as $index => $value) {
        $blocked = $all_students[$index]->userblocked;

        if (!$blocked) {
            array_push($eligible_students, $all_students[$index]);
        }

    }

    return $eligible_students;
}
