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
 * A class representing a single peergrade and containing some static methods for manipulating peergrades
 *
 * @package    core_peergrade
 * @subpackage peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The peergrade class represents a single peergrade by a single user
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */

//Additional functions for peergrading in PeerForums

define('PEERGRADE_UNSET_ANSWER', -1);

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

function remove_fav_students($peerid, $userid) {
    global $DB;

    $user = $DB->get_record('peerforum_relationships', array('iduser' => $userid));
    $user_fav_students = $user->peersfav;

    $fav_students = explode(';', $user_fav_students);

    $toremove = array_search($peerid, $fav_students);
    unset($fav_students[$toremove]);
    array_values($fav_students); //needed?

    $fav_students_upd = implode(';', $fav_students);

    $data = new stdClass();
    $data->id = $user->id;
    $data->peersfav = $fav_students_upd;

    $DB->update_record('peerforum_relationships', $data);

}

function remove_unfav_students($peerid, $userid) {
    global $DB;

    $user = $DB->get_record('peerforum_relationships', array('iduser' => $userid));
    $user_unfav_students = $user->peersunfav;

    $unfav_students = explode(';', $user_unfav_students);

    $toremove = array_search($peerid, $unfav_students);
    unset($unfav_students[$toremove]);
    array_values($unfav_students); //needed?

    $unfav_students_upd = implode(';', $unfav_students);

    $data = new stdClass();
    $data->id = $user->id;
    $data->peersunfav = $unfav_students_upd;

    $DB->update_record('peerforum_relationships', $data);

}

function add_unfav_students($peerid, $userid) {
    global $DB;

    $user = $DB->get_record('peerforum_relationships', array('iduser' => $userid));
    $user_unfav_students = $user->peersunfav;

    $unfav_students = explode(';', $user_unfav_students);
    array_push($unfav_students, $peerid);

    $unfav_students_upd = implode(';', $unfav_students);

    $data = new stdClass();
    $data->id = $user->id;
    $data->peersunfav = $unfav_students_upd;

    $DB->update_record('peerforum_relationships', $data);

}

function add_fav_students($peerid, $userid) {
    global $DB;

    $user = $DB->get_record('peerforum_relationships', array('iduser' => $userid));
    $user_fav_students = $user->peersfav;

    $fav_students = explode(';', $user_fav_students);

    array_push($fav_students, $peerid);

    $fav_students_upd = implode(';', $fav_students);

    $data = new stdClass();
    $data->id = $user->id;
    $data->peersfav = $fav_students_upd;

    $DB->update_record('peerforum_relationships', $data);

}

/*
 function update_least_fav_students($array_peergraders, $postid, $courseid) {
     global $DB;

     foreach($array_peergraders as $i => $value){
         $userid = $array_peergraders[$i];
         $existing_info = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser'=>$userid));

         $existing_posts = $existing_info->poststopeergrade;

         $data = new stdClass;
         $data->courseid = $courseid;
         $data->iduser = $userid;

         if(empty($existing_info)){
             $data->poststopeergrade = $postid;
             $data->postspeergradedone = NULL;
             $data->postsblocked = NULL;
             $data->postsexpired = NULL;

             $data->numpostsassigned = 1;
             $data->numpoststopeergrade = 1;

             $DB->insert_record('peerforum_peergrade_users', $data);
         }
         else{
             $array_posts = array();
             $posts = explode(';', $existing_posts);
             $posts = array_filter($posts);

             adjust_database();

             foreach($posts as $post => $value){
                 array_push($array_posts, $posts[$post]);
             }

             array_push($array_posts, $postid);


             $array_posts = array_filter($array_posts);
             $posts = implode(';', $array_posts);

             $data->poststopeergrade = $posts;
             $data->numpoststopeergrade = count($array_posts);
             $data->id = $existing_info->id;

             $DB->update_record('peerforum_peergrade_users', $data);
         }
     }
  }*7

/**
 * Initialises JavaScript to enable AJAX on the provided page
 *
 * @param moodle_page $page
 * @return true always returns true
 */
function initialise_addpeer_javascript(moodle_page $page) {
    global $CFG;

    // Only needs to be initialized once.
    static $done = false;
    if ($done) {
        return true;
    }

    $module = array('name' => 'core_peergrading_addpeer',
            'fullpath' => '/peergrading/addpeer.js',
            'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

    $page->requires->js_init_call('M.core_peergrading_addpeer.init', null, false, $module);
    $done = true;

    return true;
}

function initialise_removepeer_javascript(moodle_page $page) {
    global $CFG;

    // Only needs to be initialized once.
    static $done = false;
    if ($done) {
        return true;
    }

    $module = array('name' => 'core_peergrading_removepeer',
            'fullpath' => '/peergrading/removepeer.js',
            'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

    $page->requires->js_init_call('M.core_peergrading_removepeer.init', null, false, $module);
    $done = true;

    return true;
}
