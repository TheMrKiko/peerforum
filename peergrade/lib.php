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
 * Additional functions for peergrading in PeerForums
 *
 * @package    core_peergrade
 * @subpackage peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('PEERGRADE_UNSET_PEERGRADE', -999);
define('PEERGRADE_UNSET_FEEDBACK', '');
define('PEERGRADE_AGGREGATE_NONE', 0); // No peergrades.
define('PEERGRADE_AGGREGATE_AVERAGE', 1);
define('PEERGRADE_AGGREGATE_COUNT', 2);
define('PEERGRADE_AGGREGATE_MAXIMUM', 3);
define('PEERGRADE_AGGREGATE_MINIMUM', 4);
define('PEERGRADE_AGGREGATE_SUM', 5);

define('PEERGRADE_DEFAULT_SCALE', 5);
define('UNSET_STUDENT', -1);
define('UNSET_STUDENT_SELECT', -2);

define('PEERGRADE_OUTLIER_IN', 1);
define('PEERGRADE_OUTLIER_WARNING', 2);
define('PEERGRADE_OUTLIER_OUT', 3);

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

function update_post_peergraders($postid, $peergraders) {
    global $DB;

    $post = $DB->get_record('peerforum_posts', array('id' => $postid));
    $post_graders = $post->peergraders;

    $post_graders = explode(';', $post_graders);
    $post_graders = array_filter($post_graders);

    $peergraders = array_filter($peergraders);

    foreach ($peergraders as $key => $value) {
        array_push($post_graders, $peergraders[$key]);
    }

    $post_graders = array_filter($post_graders);
    $post_graders_upd = implode(';', $post_graders);

    $data = new stdClass();
    $data->id = $postid;
    $data->peergraders = $post_graders_upd;

    $DB->update_record('peerforum_posts', $data);

}

function update_graders($array_peergraders, $postid, $courseid) {
    global $DB;

    foreach ($array_peergraders as $i => $value) {
        $userid = $array_peergraders[$i];
        $existing_info = $DB->get_record('peerforum_peergrade_users', array('courseid' => $courseid, 'iduser' => $userid));

        $existing_posts = $existing_info->poststopeergrade;

        $data = new stdClass;
        $data->courseid = $courseid;
        $data->iduser = $userid;

        if (empty($existing_info)) {
            $data->poststopeergrade = $postid;
            $data->postspeergradedone = null;
            $data->postsblocked = null;
            $data->postsexpired = null;

            $data->numpostsassigned = 1;
            $data->numpoststopeergrade = 1;

            $DB->insert_record('peerforum_peergrade_users', $data);
        } else {
            $array_posts = array();
            $posts = explode(';', $existing_posts);
            $posts = array_filter($posts);

            adjust_database();

            foreach ($posts as $post => $value) {
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
}

function assign_random($courseid, $array_users, $postauthor, $postid, $peerid) {
    global $DB;
    $array_peergraders = array();

    $peers = $DB->get_record('peerforum_posts', array('id' => $postid))->peergraders;
    $peers = explode(';', $peers);
    $peers = array_filter($peers);

    if (in_array($peerid, $array_users)) {
        $keyy = array_search($peerid, $array_users);
        unset($array_users[$keyy]);
        $array_users = array_filter($array_users);
    }

    if (in_array($postauthor, $array_users)) {
        $keyy = array_search($postauthor, $array_users);
        unset($array_users[$keyy]);
        $array_users = array_filter($array_users);
    }

    $array_users = array_values($array_users);
    $count_peers = count($array_users);

    $random = rand(0, $count_peers - 1);

    if (!empty($array_users)) {

        $peer = $array_users[$random];

        $conflicts = $DB->get_records('peerforum_peergrade_conflict', array('courseid' => $courseid));

        $conflict = 0;

        foreach ($conflicts as $id => $value) {
            $students = explode(';', $conflicts[$id]->idstudents);
            $students = array_filter($students);

            if (in_array(-1, $students)) {
                $a = array_search(-1, $students);
                unset($students[$a]);
                $sts = implode(';', $students);
                $data = new stdClass();
                $data->id = $conflicts[$id]->id;
                $data->idstudents = $sts;
                $DB->update_record('peerforum_peergrade_conflict', $data);
            }

            if (in_array($peer, $students) && in_array($postauthor, $students)) {
                $conflict = 1;
                break;
            }
        }

        if ($conflict == 0) {
            $key = array_search($peer, $array_users);
            unset($array_users[$key]);
            $array_users_upd = array_filter($array_users);

            assign_random($courseid, $array_users_upd, $postauthor, $postid, $peerid);
        }
        if ($conflict == 1) {
            $key = array_search($peer, $array_users);
            unset($array_users[$key]);
            $array_users_upd = array_filter($array_users);

            assign_random($courseid, $array_users_upd, $postauthor, $postid, $peerid);
        }
    }

    update_graders($array_peergraders, $postid, $courseid);
    update_post_peergraders($postid, $array_peergraders);
}

function assign_one_peergrader($postid, $courseid, $peerid) {
    global $DB, $PAGE;

    $post = $DB->get_record('peerforum_posts', array('id' => $postid));
    $postauthor = $post->userid;

    $enroledusers = get_students_enroled($courseid);

    if (in_array($postauthor, $enroledusers)) {
        $key = array_search($postauthor, $enroledusers);
        unset($enroledusers[$key]);
        $enroledusers = array_filter($enroledusers);
    }

    $array_users = array();

    foreach ($enroledusers as $id => $value) {
        array_push($array_users, $id);
    }

    $count_peers = count($array_users);
    assign_random($courseid, $array_users, $postauthor, $postid, $peerid);
}

function get_discussions_name($course, $peerforum) {
    global $DB;

    $sql = "SELECT p.name
         FROM {peerforum_discussions} p
         WHERE p.course = $course AND p.peerforum = $peerforum";

    $data = $DB->get_records_sql($sql);
    $topics = array();

    foreach ($data as $key => $value) {
        array_push($topics, $key);
    }
    return $topics;
}
/**
 * The peergrade assignment class represents a peergrade assignment to a single user
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade_assignment {

    /**
     * @var stdClass The context in which this peergrade exists
     */
    public $context;

    /**
     * @var string The component using peergrades. For example "mod_peerforum"
     */
    public $component;

    /**
     * @var string The peergrade area to associate this peergrade assignment with
     *             This allows a plugin to peergrade more than one thing by specifying different peergrade areas
     */
    public $peergradearea = null;

    /**
     * @var int The id of the item (forum post, glossary item etc) being peergraded
     */
    public $itemid;

    /**
     * @var int The id of this peergrade within the peergrade assignment table
     */
    public $id;

    /**
     * @var int The id of the user who submitted the peergrade
     */
    public $userid;

    /**
     * @var stdClass The detailed information of the user to whom the peergrade was assigned to
     */
    public $userinfo = null;

    /**
     * @var int The time the peer grade was assigned
     */
    public $timeassigned = null;

    /**
     * @var int The time the peer grade
     */
    public $timemodified = null;

    /**
     * @var int The time the peer grade will expire, expired or as to expire.
     */
    public $timeexpired = null;

    /**
     * @var stdclass The peergrade options with more details
     */
    public $peergradeoptions;

    /**
     * @var stdclass settings for this peergrade. Necessary to render the peergrade.
     */
    public $settings = null;

    /**
     * @var int If the assignment was already peergraded and the id of the peergrade (if yes)
     */
    public $peergraded = 0;

    /**
     * @var bool If the user is blocked from peer grading the item
     */
    public $blocked = 0;

    /**
     * @var bool If the peergrading has ended
     */
    public $ended = 0;

    /**
     * @var bool If the user let the peer grade expire
     */
    public $expired = 0;

    /**
     * @var int The nomination id related to this assignment
     */
    public $nomination = 0;

    /**
     * @var int The nomination value related to this assignment
     */
    public $nominationvalue = 0;

    /**
     * Constructor.
     *
     * @param stdClass $options {
     *            peergradeoptions => The peergrade options with more details [required]
     *            id => The id of this peergrade assignment [required]
     *            userid  => int The id of the user who this was assigned [required]
     *            userinfo  => stdClass The detailed information of the user to whom the peergrade was assigned to [required]
     *            itemid  => int the id of the associated item (forum post, glossary item etc) [required]
     *            timeassigned  => int The time the peer grade was assigned [required]
     *            ended => If the peergrading has ended [required]
     *            blocked => If the user is blocked from peer grading this item [required]
     *            peergraded => If the assignment was already peergraded and the id of the peergrade (if yes) [required]
     *            expired => If the user let the peer grade expire [required]
     *            timemodified  => int The time the peer grade [optional]
     *            timeexpired  => int The time the peer grade expired or was to expire [optional]
     *            nomination  => int The nomination id related to this assignment [optional]
     *            nominationvalue  => int The nomination value related to this assignment [optional]
     * }
     */
    public function __construct($options) {
        $this->peergradeoptions = $options->peergradeoptions;
        $this->context = $this->peergradeoptions->context;
        $this->component = $this->peergradeoptions->component;
        $this->peergradearea = $this->peergradeoptions->peergradearea;
        $this->itemid = $options->itemid;
        $this->userid = $options->userid;
        $this->ended = $options->ended;
        $this->blocked = $options->blocked;
        $this->peergraded = $options->peergraded;
        $this->expired = $options->expired;

        if (isset($options->id)) {
            $this->id = $options->id;
        }
        if (isset($options->userinfo)) {
            $this->userinfo = $options->userinfo;
        }
        if (isset($options->timeassigned)) {
            $this->timeassigned = $options->timeassigned;
        }
        if (isset($options->timemodified)) {
            $this->timemodified = $options->timemodified;
        }
        if (isset($options->timeexpired)) {
            $this->timeexpired = $options->timeexpired;
        }
        if (isset($options->nomination)) {
            $this->nomination = $options->nomination;
        }
        if (isset($options->nominationvalue)) {
            $this->nominationvalue = $options->nominationvalue;
        }
        if (isset($this->peergradeoptions->settings)) {
            $this->settings = $this->peergradeoptions->settings;
        }
    }

    /**
     * Update this peergrade in the database
     *
     */
    public function assign() {
        global $DB;

        $time = time();
        $timetilexpire = $this->peergradeoptions->timetoexpire * DAYSECS;
        $params = array();
        $params['contextid'] = $this->context->id;
        $params['component'] = $this->component;
        $params['peergradearea'] = $this->peergradearea;
        $params['itemid'] = $this->itemid;
        $params['userid'] = $this->userid;

        $sql = "SELECT r.id
                  FROM {peerforum_time_assigned} r
                 WHERE r.contextid = :contextid AND
                       r.userid = :userid AND
                       r.itemid = :itemid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea";
        $records = $DB->get_records_sql($sql, $params);

        if (!empty($records)) {
            return false;
        }

        $data = new stdClass;
        // Insert a new peergrade.
        $data->postid = $this->itemid;
        $data->itemid = $this->itemid;
        $data->userid = $this->userid;
        $data->courseid = 0; //you want to remove this
        $data->timeassigned = $time;
        $data->timemodified = $time;
        $data->timeexpired = $time + $timetilexpire;
        $data->ended = $this->ended;
        $data->expired = $this->expired;
        $data->blocked = $this->blocked;
        $data->peergraded = $this->peergraded;
        $data->contextid = $this->context->id;
        $data->component = $this->component;
        $data->nomination = $this->nomination;
        $data->peergradearea = $this->peergradearea;

        return $DB->insert_record('peerforum_time_assigned', $data);
    }

    public function update_peergrade($peergradeid) {
        global $DB;

        $time = time();

        $data = new stdClass;
        $data->id = $this->id;
        $data->peergraded = $peergradeid;
        $data->timemodified = $time;
        $data->ended = 1;
        $DB->update_record('peerforum_time_assigned', $data);
    }

    public function get_time_to_expire($formatted = true) {
        $time = time();
        $timewhenexpires = $this->timeexpired;
        $difference = $timewhenexpires - $time;
        return $formatted ? format_time($difference) : $difference;

    }
} // End peergrade assignment class definition.

/**
 * The peergrade class represents a single peergrade by a single user
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade implements renderable {

    /**
     * @var stdClass|context The context in which this peergrade exists
     */
    public $context;

    /**
     * @var string The component using peergrades. For example "mod_peerforum"
     */
    public $component;

    /**
     * @var string The peergrade area to associate this peergrade with
     *             This allows a plugin to peergrade more than one thing by specifying different peergrade areas
     */
    public $peergradearea = null;

    /**
     * @var int The id of the item (forum post, glossary item etc) being peergraded
     */
    public $itemid;

    /**
     * @var int The id peergradescale (1-5, 0-100) that was in use when the peergrade was submitted
     */
    public $peergradescaleid;

    /**
     * @var int The id of the user who submitted the peergrade
     */
    public $userid; // When empty peer grade, also empty user.

    /**
     * @var int The time the peer grade was first submitted
     */
    public $timecreated;

    /**
     * @var stdclass settings for this peergrade. Necessary to render the peergrade.
     */
    public $settings;

    /**
     * @var int The Id of this peergrade within the peergrade table. This is only set if the peergrade already exists
     */
    public $id = null;

    /**
     * @var int The aggregate of the combined peergrades for the associated item. This is only set if the peergrade already exists
     */
    public $aggregate = null;

    /**
     * @var int The total number of peergrades for the associated item. This is only set if the peergrade already exists
     */
    public $count = 0;

    /**
     * @var int The peergrade the associated user gave the associated item. This is only set if the peergrade already exists
     */
    public $peergrade = null;

    /**
     * @var int The time the associated item was created
     */
    public $itemtimecreated = null;

    /**
     * @var int The id of the user made the item
     */
    public $itemuserid = null;

    /**
     * @var string The feedback of a peergraded post
     */
    public $feedback = PEERGRADE_UNSET_FEEDBACK;

    /**
     * @var peergrade_assignment[] The list of assignments related to this item
     */
    public $usersassigned = array();

    /**
     * @var bool If the peergrading has ended
     */
    public $ended = null;

    /**
     * @var bool If the user is blocked from peer grading at all
     */
    public $userblocked = false;

    /**
     * Constructor.
     *
     * @param stdClass $options {
     *            context => context context to use for the peergrade [required]
     *            component => component using peergrades ie mod_peerforum [required]
     *            peergradearea => peergradearea to associate this peergrade with [required]
     *            itemid  => int the id of the associated item (forum post, glossary item etc) [required]
     *            peergradescaleid => int The peergradescale in use when the peergrade was submitted [required]
     *            userid  => int The id of the user who submitted the peergrade [required]
     *            timecreated  => int The time the peer grade was first submitted [required]
     *            settings => Settings for the peergrade object [optional]
     *            id => The id of this peergrade (if the peergrade is from the db) [optional]
     *            aggregate => The aggregate for the peergrade [optional]
     *            count => The number of peergrades [optional]
     *            peergrade => The peergrade given by the user [optional]
     *            feedback => The feedback given by the user [optional]
     *            usersassigned => The list of assignments related to this item [optional]
     *            userblocked => If the user is blocked from peer grading [optional]
     * }
     */
    public function __construct($options) {
        $this->context = $options->context;
        $this->component = $options->component;
        $this->peergradearea = $options->peergradearea;
        $this->itemid = $options->itemid;
        $this->peergradescaleid = $options->peergradescaleid;
        $this->userid = $options->userid;

        if (isset($options->settings)) {
            $this->settings = $options->settings;
        }
        if (isset($options->id)) {
            $this->id = $options->id;
        }
        if (isset($options->aggregate)) {
            $this->aggregate = $options->aggregate;
        }
        if (isset($options->count)) {
            $this->count = $options->count;
        }
        if (isset($options->peergrade)) {
            $this->peergrade = $options->peergrade;
        }
        if (isset($options->feedback)) {
            $this->feedback = $options->feedback;
        }
        if (isset($options->timecreated)) {
            $this->timecreated = $options->timecreated;
        }
        if (isset($options->itemuserid)) {
            $this->itemuserid = $options->itemuserid;
        }
        if (isset($options->usersassigned)) {
            $this->usersassigned = $options->usersassigned;
            if (!empty($this->usersassigned)) {
                $this->ended = true;
                foreach ($this->usersassigned as $userassign) {
                    if (!$userassign->ended) {
                        $this->ended = false;
                    }
                }
            }
        }
        if (isset($options->userblocked)) {
            $this->userblocked = $options->userblocked;
        }
    }

    /**
     * Update this peergrade in the database
     *
     * @param int $peergrade the integer value of this peergrade
     */
    public function update_peergrade($peergrade, $feedback = null) {
        global $DB, $COURSE;

        $time = time();

        $data = new stdClass;
        $data->peergrade = $peergrade;
        $data->timemodified = $time;
        $data->feedback = $feedback;

        $item = new stdclass();
        $item->id = $this->itemid;
        $items = array($item);

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $this->context;
        $peergradeoptions->component = $this->component;
        $peergradeoptions->peergradearea = $this->peergradearea;
        $peergradeoptions->items = $items;
        $peergradeoptions->aggregate = PEERGRADE_AGGREGATE_AVERAGE; // We dont actually care what aggregation method is applied.
        $peergradeoptions->peergradescaleid = $this->peergradescaleid;
        $peergradeoptions->userid = $this->userid;

        $rm = new peergrade_manager();
        $items = $rm->get_peergrades($peergradeoptions);
        $firstitem = $items[0]->peergrade;

        // Obj does not exist in DB.
        if (empty($firstitem->id)) {
            // Insert a new peergrade.
            $data->contextid = $this->context->id;
            $data->component = $this->component;
            $data->peergradearea = $this->peergradearea;
            $data->peergrade = $peergrade;
            $data->peergradescaleid = $this->peergradescaleid;
            $data->peergradescale = 0;
            $data->userid = $this->userid;
            $data->itemid = $this->itemid;
            $data->feedback = $feedback;
            $data->timecreated = $time;
            $data->timemodified = $time;
            $data->peergraderid = 0;
            $data->scaleid = 0;

            $id = $DB->insert_record('peerforum_peergrade', $data);
            $firstitem->get_self_assignment($this->userid)->update_peergrade($id);
            $rank = $DB->get_records('peerforum_relationship_rank', array(
                    'userid' => $this->userid,
                    'otheruserid' => $this->itemuserid,
                    ));
            $noms = $DB->get_records('peerforum_relationship_nomin', array(
                    'userid' => $this->userid,
                    'otheruserid' => $this->itemuserid,
            ));
            if (empty($rank) && empty($noms)) {
                $DB->insert_record('peerforum_relationship_rank', (object) array(
                        'n' => 0,
                        'course' => $COURSE->id,
                        'userid' => $this->userid,
                        'otheruserid' => $this->itemuserid,
                ));
            }
        } else {
            // Update the peergrade.
            $data->id = $firstitem->id;
            $DB->update_record('peerforum_peergrade', $data);
        }
    }

    /**
     * Retreive the integer value of this peergrade
     *
     * @return int the integer value of this peergrade object
     */
    public function get_peergrade() {
        return $this->peergrade;
    }

    /**
     * Returns this peergrades aggregate value as a string.
     *
     * @return string peergrades aggregate value
     */
    public function get_aggregate_string() {

        $aggregate = $this->aggregate;
        $method = $this->settings->aggregationmethod;

        // Only display aggregate if aggregation method isn't COUNT.
        $aggregatestr = '';
        if (is_numeric($aggregate) && $method != PEERGRADE_AGGREGATE_COUNT) {
            if ($method != PEERGRADE_AGGREGATE_SUM && !$this->settings->peergradescale->isnumeric) {

                // Round aggregate as we're using it as an index.
                $aggregatestr .= $this->settings->peergradescale->peergradescaleitems[round($aggregate)];
            } else { // Aggregation is SUM or the peergradescale is numeric.
                $aggregatestr .= round($aggregate, 1);
            }
        }

        return $aggregatestr;
    }

    /**
     * Returns the time until the post expires.
     *
     * @param bool $formatted
     * @return string
     */
    public function get_time_to_expire($formatted = true) {
        if ($this->get_self_assignment() === null) {
            return null;
        }

        return $this->get_self_assignment()->get_time_to_expire($formatted);
    }

    /**
     * Returns the max of time till all expires.
     *
     * @return int
     */
    public function get_max_time_to_expire() {
        $mtt = 0;
        foreach ($this->usersassigned as $assign) {
            $tte = $assign->get_time_to_expire(false);
            $mtt = max($mtt, $tte);
        }
        return $mtt;
    }

    /**
     * Returns the peer grade assignment to this user, if was assigned.
     *
     * @param int|null $userid
     * @return peergrade_assignment|null
     */
    public function get_self_assignment($userid = null): ?peergrade_assignment {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        return $this->usersassigned[$userid] ?? null;
    }

    /**
     * Count number of assignments that will expire soon
     *
     * @param int $howsoon in seconds
     * @return int
     */
    public function get_expiring_soon($howsoon = 3 * HOURSECS): int {
        if (empty($this->usersassigned)) {
            return 0;
        }

        $time = time();
        $willexpiresoon = 0;
        $timetilalmostexpire = $time + $howsoon;
        foreach ($this->usersassigned as $userassign) {
            if (!$userassign->ended && $userassign->timeexpired < $timetilalmostexpire) {
                $willexpiresoon++;
            }
        }
        return $willexpiresoon;
    }

    /**
     * If there is any peer grade activity for this item.
     *
     * @return bool
     */
    public function exists(): bool {
        return !empty($this->usersassigned);
    }

    /**
     * Returns if the user can edit the grade already submitted.
     *
     * @return bool
     */
    public function can_edit() {
        if ($this->get_self_assignment() === null || empty($this->timecreated)) {
            return false;
        }

        global $USER, $CFG;
        $userid = $USER->id;

        // You only can peergrade your item.
        if ($this->userid != $userid) {
            return false;
        }

        $time = time();
        $timecreated = $this->timecreated;
        $timetoedit = $CFG->maxeditingtime;
        $timewhenstopsediting = $timecreated + $timetoedit;

        return $timewhenstopsediting > $time;
    }

    public function verify_exclusivity($postauthor, $grader, $courseid) {
        global $DB;

        $conflicts = $DB->get_records('peerforum_peergrade_conflict', array('courseid' => $courseid));
        $conflict = 0;

        foreach ($conflicts as $id => $value) {
            $students = explode(';', $conflicts[$id]->idstudents);
            $students = array_filter($students);

            if (in_array($grader, $students) && in_array($postauthor, $students)) {
                $conflict = 1;
                break;
            }
        }
        return $conflict;
    }

    /**
     * Returns true if the user is able to peergrade this peergrade object
     *
     * @param int $userid Current user assumed if left empty
     * @return bool true if the user is able to peergrade this peergrade object
     */
    public function user_can_peergrade($userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        // You can't peergrade your item.
        if ($this->itemuserid == $userid) {
            return false;
        }

        // You can't peergrade if you don't have the system cap and the pugin cap and depends on the final grade mode.
        if (!$this->settings->pluginpermissions->peergrade) {
            return false;
        }

        // You can't peergrade if the item was outside of the assessment times.
        $timestart = $this->settings->assesstimestart;
        $timefinish = $this->settings->assesstimefinish;
        $timecreated = $this->itemtimecreated;
        if (!empty($timestart) && !empty($timefinish) && ($timecreated < $timestart || $timecreated > $timefinish)) {
            return false;
        }

        // You can't peergrade if it was not assigned to you.
        if ($this->get_self_assignment() === null) {
            return false;
        }

        // You can't peergrade if the peer grading is over or expired.
        if ($this->is_ended() || $this->is_ended_for_user()) {
            return false;
        }

        // You can't peergrade if you are blocked. For now, there is no block of assigns.
        if ($this->userblocked || $this->get_self_assignment()->blocked) {
            return false;
        } // TODO exclusive! and delete verify_exclusivity().
        return true;
    }

    /**
     * Returns true if the user is able to peergrade this peergrade object
     *
     * @return bool true if the user is able to peergrade this peergrade object
     */
    public function is_ended() {
        if ($this->ended) {
            return true;
        }

        if ($this->is_expired_for_user()) {
            return true;
        }

        // If it should be finished at min graders.
        // The cron should check this but just in case it does not in time.
        if ($this->settings->finishpeergrade) {
            if ($this->has_min_grades()) {
                $this->ended = true;
                return $this->ended;
            }
        }

        return false;
    }

    /**
     * Returns true if the user let the item expire.
     *
     * @return bool true if the user let the item expire.
     */
    public function is_expired_for_user() {
        if (!$this->get_self_assignment()) {
            return false; // The user does not have anything to expire.
        }

        if ($this->get_self_assignment()->expired) {
            return true;
        }

        if ($this->get_peergrade()) {
            return false; // If the user peergraded, then not expired.
        }

        // If it should be finished at min graders.
        if ($this->settings->finishpeergrade) {
            if ($this->has_min_grades()) {
                return false;
            }
        }

        $time = time();
        // The cron should check this but just in case it does not in time.
        $timeexpired = $this->get_self_assignment()->timeexpired;
        if ($time > $timeexpired) {
            $this->get_self_assignment()->expired = true;
            return true;
        }

        return false;
    }

    /**
     * If the peergrading is ended for the user.
     *
     * @return bool true if the peergrading is ended for the user.
     */
    public function is_ended_for_user() {
        return $this->get_self_assignment()->ended ?? false;
    }

    /**
     * If the minimun of peer grades were given to this item.
     *
     * @return bool
     */
    public function has_min_grades(): bool {
        return $this->count >= $this->settings->minpeergraders ?? false;
    }

    /**
     * Returns true if the user is able to view the aggregate for this peergrade object.
     * Probably best used always with @see can_peergrades_be_shown(): when not active.
     *
     * @param bool $checkpgshown If this function should check for the canpeergradebeshown
     * @param int|null $userid If left empty the current user is assumed.
     * @return bool true if the user is able to view the aggregate for this peergrade object
     */
    public function user_can_view_aggregate($checkpgshown = true, $userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        if ($checkpgshown && !$this->can_peergrades_be_shown()) {

            return false;
        }

        // If the item doesnt belong to anyone or its another user's items and they can see the aggregate on items they don't own.
        // Note that viewany doesnt mean you can see the aggregate or peergrades of your own items.
        if ((empty($this->itemuserid) or $this->itemuserid != $userid)
                && $this->settings->permissions->viewany
                && $this->settings->pluginpermissions->viewany) {

            return true;
        }

        // If its the current user's item and they have permission to view the aggregate on their own items.
        if ($this->itemuserid == $userid
                && $this->settings->permissions->view
                && $this->settings->pluginpermissions->view) {

            return true;
        }

        return false;
    }

    /**
     * Returns true if the user is able to view all the peergrades for this peergrade object.
     * Probably best used always with @see can_peergrades_be_shown(): when not active.
     *
     * @param array|null $peergrades The specific peergrade(s) to check
     * @param bool $checkpgshown If this function should check for the canpeergradebeshown
     * @param int|null $userid If left empty the current user is assumed.
     * @return bool true if the user is able to view all the peergrades for this peergrade object
     */
    public function user_can_view_peergrades($peergrades = array(), $checkpgshown = true, $userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        foreach ($peergrades as $pg) {
            if ($pg->userid == $userid || $this->settings->permissions->professor) {
                return true; // If the user is the author, it can at least see some.
            }
        }

        if ($checkpgshown && !$this->can_peergrades_be_shown()) {
            // We may want to verify this outside this function for better error report.
            return false;
        }

        // If they can see the peergrades on all items.
        if ($this->settings->permissions->viewall && $this->settings->pluginpermissions->viewall) {

            return true;
        }

        // If its the current user's item and they have permission to view the peergrades on their own items.
        if ($this->itemuserid == $userid
                && $this->settings->permissions->viewsome
                && $this->settings->pluginpermissions->viewsome) {

            return true;
        }

        return false;
    }

    /**
     * Checks for the whenpeergradevisible setting.
     * Probably best used always with @see user_can_view_peergrades()
     * and @see user_can_view_aggregate()
     *
     * @return bool
     */
    public function can_peergrades_be_shown(): bool {
        return $this->settings->whenpeergradevisible === PEERFORUM_GRADEVISIBLE_ALWAYS
                || ($this->get_peergrade() && !$this->can_edit())
                || ($this->settings->whenpeergradevisible === PEERFORUM_GRADEVISIBLE_MINGRADERS &&
                        $this->has_min_grades() && !$this->get_self_assignment())
                || $this->is_ended();
    }

    /**
     * Returns a URL to view all of the peergrades for the item this peergrade is for.
     *
     * If this is a peergrade of a post then this URL will take the user to a page that shows all of the peergrades for the post
     * (this one included).
     *
     * @param bool $popup whether of not the URL should be loaded in a popup
     * @return moodle_url URL to view all of the peergrades for the item this peergrade is for.
     */
    public function get_view_peergrades_url($popup = false) {
        $attributes = array(
                'contextid' => $this->context->id,
                'component' => $this->component,
                'peergradearea' => $this->peergradearea,
                'itemid' => $this->itemid,
                'peergradescaleid' => $this->settings->peergradescale->id
        );
        if ($popup) {
            $attributes['popup'] = 1;
        }
        return new moodle_url('/peergrade/index.php', $attributes);
    }

    /**
     * Returns a URL that can be used to peergrade the associated item.
     *
     * @param int|null $peergrade The peergrade to give the item, if null then no peergrade param is added.
     * @param string|null $feedback The feedback to give the item, if null then no feedback param is added.
     * @param moodle_url|string $returnurl The URL to return to.
     * @return moodle_url can be used to peergrade the associated item.
     */
    public function get_peergrade_url($peergrade = null, $feedback = null, $returnurl = null) {
        if (empty($returnurl)) {
            if (!empty($this->settings->returnurl)) {
                $returnurl = $this->settings->returnurl;
            } else {
                global $PAGE;
                $returnurl = $PAGE->url;
            }
        }

        $args = array(
                'contextid' => $this->context->id,
                'component' => $this->component,
                'peergradearea' => $this->peergradearea,
                'itemid' => $this->itemid,
                'peergradescaleid' => $this->settings->peergradescale->id,
                'returnurl' => $returnurl,
                'peergradeduserid' => $this->itemuserid,
                'aggregation' => $this->settings->aggregationmethod,
                'finalgrademode' => $this->settings->finalgrademode,
                'sesskey' => sesskey()
        );
        if ($peergrade !== null) {
            $args['peergrade'] = $peergrade;
        }

        if (!empty($feedback)) {
            $args['feedback'] = $feedback;
        }

        return new moodle_url('/peergrade/peergrade.php', $args);
    }

    /**
     * Returns a URL that can be used to peergrade the associated item.
     *
     * @param int|null $peergrade The peergrade to give the item, if null then no peergrade param is added.
     * @param string|null $feedback The feedback to give the item, if null then no feedback param is added.
     * @param moodle_url|string $returnurl The URL to return to.
     * @return moodle_url can be used to peergrade the associated item.
     */
    public function get_assign_url($action = null, $assigneduserid = null, $returnanchor = null, $returnurl = null) {
        if (empty($returnurl)) {
            if (!empty($this->settings->returnurl)) {
                $returnurl = $this->settings->returnurl;
            } else {
                global $PAGE;
                $returnurl = $PAGE->url;
            }
            $returnurl = new moodle_url($returnurl);
            if (!empty($returnanchor)) {
                $returnurl->set_anchor($returnanchor);
            }
        }

        $args = array(
                'contextid' => $this->context->id,
                'component' => $this->component,
                'peergradearea' => $this->peergradearea,
                'itemid' => $this->itemid,
                'peergradeduserid' => $this->itemuserid,
                'returnurl' => $returnurl,
                'timetoexpire' => $this->settings->timetoexpire,
                'sesskey' => sesskey()
        );
        if ($action !== null) {
            $args['action'] = $action;
        }

        if ($assigneduserid !== null) {
            $args['assigneduserid'] = $assigneduserid;
        }

        return new moodle_url('/peergrade/assign.php', $args);
    }


} // End peergrade class definition.

/**
 * The peergrade_manager class provides the ability to retrieve sets of peergrades from the database
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade_manager {

    /**
     * @var array An array of calculated peergradescale options to save us generate them for each request.
     */
    protected $peergradescales = array();

    /**
     * Delete one or more peergrades. Specify either a peergrade id, an item id or just the context id.
     *
     * @param stdClass $options {
     *            contextid => int the context in which the peergrades exist [required]
     *            peergradeid => int the id of an individual peergrade to delete [optional]
     *            userid => int delete the peergrades submitted by this user. May be used in conjuction with itemid [optional]
     *            itemid => int delete all peergrades attached to this item [optional]
     *            component => string The component to delete peergrades from [optional]
     *            peergradearea => string The peergradearea to delete peergrades from [optional]
     * }
     * @global moodle_database $DB
     */
    public function delete_peergrades($options, $propagate = true) {
        global $DB;

        if (empty($options->contextid)) {
            throw new coding_exception('The context option is a required option when deleting peergrades.');
        }

        $conditions = array('contextid' => $options->contextid);
        $possibleconditions = array(
                'peergradeid' => 'id',
                'userid' => 'userid',
                'itemid' => 'itemid',
                'component' => 'component',
                'peergradearea' => 'peergradearea'
        );
        foreach ($possibleconditions as $option => $field) {
            if (isset($options->{$option})) {
                $conditions[$field] = $options->{$option};
            }
        }

        $DB->delete_records('peerforum_peergrade', $conditions);

        if ($propagate) {
            $this->delete_assignments($options, false);
        }
    }


    /**
     * Delete one or more assignments. Specify either a assignments id, an item id or just the context id.
     *
     * @param stdClass $options {
     *            contextid => int the context in which the assignments exist [required]
     *            peergradeid => int the id of an individual assignments to delete [optional]
     *            userid => int delete the assignments to this user. May be used in conjuction with itemid [optional]
     *            itemid => int delete all assignments attached to this item [optional]
     *            component => string The component to delete assignments from [optional]
     *            peergradearea => string The peergradearea to delete assignments from [optional]
     * }
     * @global moodle_database $DB
     */
    public function delete_assignments($options, $propagate = true) {
        global $DB;

        if (empty($options->contextid)) {
            throw new coding_exception('The context option is a required option when deleting assignments.');
        }

        $conditions = array('contextid' => $options->contextid);
        $possibleconditions = array(
                'assignid' => 'id',
                'userid' => 'userid',
                'itemid' => 'itemid',
                'component' => 'component',
                'peergradearea' => 'peergradearea'
        );
        foreach ($possibleconditions as $option => $field) {
            if (isset($options->{$option})) {
                $conditions[$field] = $options->{$option};
            }
        }

        $DB->delete_records('peerforum_time_assigned', $conditions);

        if ($propagate) {
            $this->delete_peergrades($options, false);
        }
    }

    /**
     * Block or unblock a user.
     *
     * @param stdClass $options {
     *            userid => int delete the assignments to this user.
     * }
     * @global moodle_database $DB
     */
    public function toggle_user_block($options) {
        global $DB;

        if (empty($options->userid)) {
            throw new coding_exception('The userid option is a required option when blocking users.');
        }
        $conditions = array('userid' => $options->userid);

        $userblock = $DB->get_record('peerforum_user_block', $conditions);
        if (empty($userblock)) {
            $DB->insert_record('peerforum_user_block', $conditions);
        } else {
            $DB->delete_records('peerforum_user_block', array('id' => $userblock->id));
        }
    }

    public function get_id() {

        global $DB;

        $sql = "SELECT p.id AS id_max
                  FROM {peerforum_peergrade} p
                 ORDER BY p.id DESC LIMIT 1";
        $id = $DB->get_records_sql($sql);

        if (empty($id)) {
            return 0;
        } else {
            return $id['0']->id_max;

        }
    }

    /**
     * Returns an array of peergrades for a given item (forum post, glossary entry etc).
     *
     * This returns all users peergrades for a single item
     *
     * @param stdClass $options {
     *            context => context the context in which the peergrades exists [required]
     *            component => component using peergrades ie mod_peerforum [required]
     *            peergradearea => peergradearea to associate this peergrade with [required]
     *            itemid  =>  int the id of the associated item (forum post, glossary item etc) [required]
     *            sort    => string SQL sort by clause [optional]
     * }
     * @return array an array of peergrades
     */
    public function get_all_peergrades_for_item($options) {
        global $DB;

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when getting peergrades for an item.');
        }
        if (!isset($options->itemid)) {
            throw new coding_exception('The itemid option is a required option when getting peergrades for an item.');
        }
        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when getting peergrades for an item.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required option when getting peergrades for an item.');
        }

        $sortclause = '';
        if (!empty($options->sort)) {
            $sortclause = "ORDER BY $options->sort";
        }

        $params = array(
                'contextid' => $options->context->id,
                'itemid' => $options->itemid,
                'component' => $options->component,
                'peergradearea' => $options->peergradearea,
        );

        $userfields = user_picture::fields('u', ['deleted'], 'userid');
        $sql = "SELECT r.id, r.peergrade, r.feedback, r.itemid, r.userid, r.timemodified, r.component, r.peergradearea, $userfields
                  FROM {peerforum_peergrade} r
             LEFT JOIN {user} u ON r.userid = u.id
                 WHERE r.contextid = :contextid AND
                       r.itemid  = :itemid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
                       {$sortclause}";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Adds peergrade objects to an array of items (forum posts, glossary entries etc). PeerGrade objects are available at
     * $item->peergrade
     *
     * @param stdClass $options {
     *      context          => context the context in which the peergrades exists [required]
     *      component        => the component name ie mod_peerforum [required]
     *      peergradearea    => the peergradearea we are interested in [required]
     *      items            => array items like forum posts or glossary items. Each item needs an 'id' ie $items[0]->id [required]
     *      aggregate        => int aggregation method to apply. PEERGRADE_AGGREGATE_AVERAGE, PEERGRADE_AGGREGATE_MAXIMUM etc
     *         [required]
     *      peergradescaleid => int the scale from which the user can select a peergrade [required]
     *      userid           => int the id of the current user [optional]
     *      returnurl        => string the url to return the user to after submitting a peergrade. Null for ajax requests [optional]
     *      assesstimestart  => int only allow peergrade of items created after this timestamp [optional]
     *      assesstimefinish => int only allow peergrade of items created before this timestamp [optional]
     * @return array the array of items with their peergrades attached at $items[i]->peergrade
     */
    public function get_peergrades($options) {
        global $DB, $USER;

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when getting peergrades.');
        }

        if (!isset($options->component)) {
            throw new coding_exception('The component option is a required option when getting peergrades.');
        }

        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is a required option when getting peergrades.');
        }

        if (!isset($options->peergradescaleid)) {
            throw new coding_exception('The peergradescaleid option is a required option when getting peergrades.');
        }

        if (!isset($options->items)) {
            throw new coding_exception('The items option is a required option when getting peergrades.');
        } else if (empty($options->items)) {
            return array();
        }

        if (!isset($options->aggregate)) {
            throw new coding_exception('The aggregate option is a required option when getting peergrades.');
        } else if ($options->aggregate == PEERGRADE_AGGREGATE_NONE) {
            // PeerGrades are not enabled.
            return $options->items;
        }
        $aggregatestr = $this->get_aggregation_method($options->aggregate);

        // Default the userid to the current user if it is not set.
        if (empty($options->userid)) {
            $userid = $USER->id;
        } else {
            $userid = $options->userid;
        }

        // Get the item table name, the item id field, and the item user field for the given peergrade item
        // from the related component.
        list($type, $name) = core_component::normalize_component($options->component);
        $default = array(null, 'id', 'userid');
        list($itemtablename, $itemidcol, $itemuseridcol) = plugin_callback($type,
                $name,
                'peergrade',
                'get_item_fields',
                array($options),
                $default);

        // Create an array of item IDs.
        $itemids = array();
        foreach ($options->items as $item) {
            $itemids[] = $item->{$itemidcol};
        }

        // Get the items from the database.
        list($itemidtest, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
        $params['contextid'] = $options->context->id;
        $params['userid'] = $userid;
        $params['component'] = $options->component;
        $params['peergradearea'] = $options->peergradearea;

        $sql = "SELECT r.id, r.itemid, r.userid, r.peergradescaleid, r.timecreated, r.feedback, r.peergrade AS userspeergrade
                  FROM {peerforum_peergrade} r
                 WHERE r.userid = :userid AND
                       r.contextid = :contextid AND
                       r.itemid {$itemidtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
              ORDER BY r.itemid";
        $userpeergrades = $DB->get_records_sql($sql, $params);

        $sql = "SELECT r.itemid, $aggregatestr(r.peergrade) AS aggrpeergrade, COUNT(r.peergrade) AS numpeergrades
                  FROM {peerforum_peergrade} r
                 WHERE r.contextid = :contextid AND
                       r.itemid {$itemidtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
              GROUP BY r.itemid, r.component, r.peergradearea, r.contextid
              ORDER BY r.itemid";
        $aggregatepeergrades = $DB->get_records_sql($sql, $params);

        $userfields = user_picture::fields('u', ['deleted'], 'userid');
        $sql = "SELECT r.id, r.itemid, r.userid, r.peergraded, r.ended, r.expired, r.blocked, r.nomination, r.timeassigned,
                       r.timemodified, r.timeexpired, n.nomination AS nominationvalue, $userfields
                  FROM {peerforum_time_assigned} r
             LEFT JOIN {user} u ON r.userid = u.id
             LEFT JOIN {peerforum_relationship_nomin} n ON r.nomination = n.id
                 WHERE r.contextid = :contextid AND
                       r.itemid {$itemidtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea";
        $usersassigned = $DB->get_records_sql($sql, $params);

        $userblocked = $DB->get_record('peerforum_user_block', array('userid' => $userid));

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $options->context;
        $peergradeoptions->component = $options->component;
        $peergradeoptions->peergradearea = $options->peergradearea;
        $peergradeoptions->settings = $this->generate_peergrade_settings_object($options);
        foreach ($options->items as $item) {
            $founduserpeergrade = false;
            foreach ($userpeergrades as $userpeergrade) {
                // Look for an existing peergrade from this user of this item.
                if ($item->{$itemidcol} == $userpeergrade->itemid) {
                    // Note: rec->scaleid = the id of scale at the time the peergrade was submitted.
                    // It may be different from the current scale id.
                    $peergradeoptions->peergradescaleid = $userpeergrade->peergradescaleid;
                    $peergradeoptions->userid = $userpeergrade->userid;
                    $peergradeoptions->feedback = $userpeergrade->feedback;
                    $peergradeoptions->timecreated = $userpeergrade->timecreated;
                    $peergradeoptions->id = $userpeergrade->id;
                    $peergradeoptions->peergrade = min($userpeergrade->userspeergrade,
                            $peergradeoptions->settings->peergradescale->max);

                    $founduserpeergrade = true;
                    break;
                }
            }
            if (!$founduserpeergrade) {
                $peergradeoptions->peergradescaleid = null;
                $peergradeoptions->userid = null;
                $peergradeoptions->feedback = null;
                $peergradeoptions->timecreated = null;
                $peergradeoptions->id = null;
                $peergradeoptions->peergrade = null;
            }

            if (array_key_exists($item->{$itemidcol}, $aggregatepeergrades)) {
                $rec = $aggregatepeergrades[$item->{$itemidcol}];
                $peergradeoptions->itemid = $item->{$itemidcol};
                $peergradeoptions->aggregate = min($rec->aggrpeergrade, $peergradeoptions->settings->peergradescale->max);
                $peergradeoptions->count = $rec->numpeergrades;
            } else {
                $peergradeoptions->itemid = $item->{$itemidcol};
                $peergradeoptions->aggregate = null;
                $peergradeoptions->count = 0;
            }

            if (!empty($usersassigned)) {
                $usersopts = array();
                foreach ($usersassigned as $userassign) {
                    if ($item->{$itemidcol} != $userassign->itemid) {
                        continue;
                    }
                    $assignoptions = new stdClass();
                    $assignoptions->id = $userassign->id;
                    $assignoptions->userid = $userassign->userid;
                    $assignoptions->userinfo = user_picture::unalias($userassign, ['deleted'], 'userid');
                    $assignoptions->itemid = $item->{$itemidcol};
                    $assignoptions->ended = $userassign->ended;
                    $assignoptions->expired = $userassign->expired;
                    $assignoptions->blocked = $userassign->blocked;
                    $assignoptions->peergraded = $userassign->peergraded;
                    $assignoptions->nomination = $userassign->nomination;
                    $assignoptions->nominationvalue = $userassign->nominationvalue;
                    $assignoptions->timeassigned = $userassign->timeassigned;
                    $assignoptions->timemodified = $userassign->timemodified;
                    $assignoptions->timeexpired = $userassign->timeexpired;
                    $assignoptions->peergradeoptions = $peergradeoptions;
                    $assign = new peergrade_assignment($assignoptions);

                    $usersopts[$userassign->userid] = $assign;
                }
                $peergradeoptions->usersassigned = !empty($usersopts) ? $usersopts : null;
            }
            $peergradeoptions->userblocked = !empty($userblocked);

            $peergrade = new peergrade($peergradeoptions);
            $peergrade->itemtimecreated = $this->get_item_time_created($item);
            if (!empty($item->{$itemuseridcol})) {
                $peergrade->itemuserid = $item->{$itemuseridcol};
            }
            $item->peergrade = $peergrade;
        }

        return $options->items;
    }

    /**
     * Generates a peergrade settings object based upon the options it is provided.
     *
     * @param stdClass $options {
     *      context           => context the context in which the peergrades exists [required]
     *      component         => string The component the items belong to [required]
     *      peergradearea        => string The peergradearea the items belong to [required]
     *      aggregate         => int Aggregation method to apply. PEERGRADE_AGGREGATE_AVERAGE, PEERGRADE_AGGREGATE_MAXIMUM etc
     *         [required]
     *      peergradescaleid  => int the scale from which the user can select a peergrade [required]
     *      returnurl         => string the url to return the user to after submitting a peergrade. Null for ajax requests
     *         [optional]
     *      assesstimestart   => int only allow peergrade of items created after this timestamp [optional]
     *      assesstimefinish  => int only allow peergrade of items created before this timestamp [optional]
     *      plugintype        => string plugin type ie 'mod' Used to find the permissions callback [optional]
     *      pluginname        => string plugin name ie 'peerforum' Used to find the permissions callback [optional]
     *      timetoexpire      => settings data [optional]
     *      finishpeergrade   => settings data [optional]
     *      enablefeedback    => settings data [optional]
     *      showpeergrades    => settings data [optional]
     *      minpeergraders    => settings data [optional]
     *      expirepost        => settings data [optional]
     *      remainanonymous   => settings data [optional]
     *      peergradevisibility => settings data [optional]
     *      whenpeergradevisible => settings data [optional]
     *      finalgrademode => settings data [optional]
     * }
     * @return stdClass peergrade settings object
     */
    protected function generate_peergrade_settings_object($options) {

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when generate a peergrade settings object.');
        }
        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when generate a peergrade settings object.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required option when generate a peergrade settings object.');
        }
        if (!isset($options->aggregate)) {
            throw new coding_exception('The aggregate option is now a required option when generate a peergrade settings object.');
        }
        if (!isset($options->peergradescaleid)) {
            throw new coding_exception('The peergradescaleid option is now a required option when generate a peergrade settings object.');
        }

        global $DB;
        $peerforumid = $DB->get_record('course_modules', array('id' => $options->context->instanceid))->instance;
        // Settings that are common to all peergrades objects in this context.
        $settings = new stdClass;
        $settings->peergradescale = $this->generate_peergrade_peergradescale_object($options->peergradescaleid,
                $peerforumid); // The peergradescale to use now. // TODO wtf is this extra arg!!!
        $settings->aggregationmethod = $options->aggregate;
        $settings->assesstimestart = null;
        $settings->assesstimefinish = null;

        list($type, $name) = core_component::normalize_component($options->component);
        $pluginextrasettingsarray = plugin_callback($type,
                $name,
                'peergrade',
                'extrasettings',
                array($options->context->id, $options->component, $options->peergradearea),
                array());

        // Collect options into the settings object.
        if (!empty($options->assesstimestart)) {
            $settings->assesstimestart = $options->assesstimestart;
        }
        if (!empty($options->assesstimefinish)) {
            $settings->assesstimefinish = $options->assesstimefinish;
        }
        if (!empty($options->returnurl)) {
            $settings->returnurl = $options->returnurl;
        }

        $settings->timetoexpire = $options->timetoexpire ?? $pluginextrasettingsarray['timetoexpire'];
        $settings->finishpeergrade = $options->finishpeergrade ?? $pluginextrasettingsarray['finishpeergrade'];
        $settings->enablefeedback = $options->enablefeedback ?? $pluginextrasettingsarray['enablefeedback'];
        $settings->showpeergrades = $options->showpeergrades ?? $pluginextrasettingsarray['showpeergrades'];
        $settings->minpeergraders = $options->minpeergraders ?? $pluginextrasettingsarray['minpeergraders'];
        $settings->peergradevisibility = $options->peergradevisibility ?? $pluginextrasettingsarray['peergradevisibility'];
        $settings->expirepost = $options->expirepost ?? $pluginextrasettingsarray['expirepost'];
        $settings->remainanonymous = $options->remainanonymous ?? $pluginextrasettingsarray['remainanonymous'];
        $settings->whenpeergradevisible = $options->whenpeergradevisible ?? $pluginextrasettingsarray['whenpeergradevisible'];
        $settings->finalgrademode = $options->finalgrademode ?? $pluginextrasettingsarray['finalgrademode'];
        $settings->seeoutliers = $options->seeoutliers ?? $pluginextrasettingsarray['seeoutliers'];
        $settings->outlierdetection = $options->outlierdetection ?? $pluginextrasettingsarray['outlierdetection'];
        $settings->outdetectvalue = $options->outdetectvalue ?? $pluginextrasettingsarray['outdetectvalue'];
        $settings->warningoutliers = $options->warningoutliers ?? $pluginextrasettingsarray['warningoutliers'];
        $settings->blockoutliers = $options->blockoutliers ?? $pluginextrasettingsarray['blockoutliers'];

        // Check site capabilities.
        $settings->permissions = new stdClass;
        // Can view the aggregate of peergrades of their own items.
        $settings->permissions->view = has_capability('mod/peerforum:viewpeergrade', $options->context);
        // Can view the aggregate of peergrades of other people's items.
        $settings->permissions->viewany = has_capability('mod/peerforum:viewanypeergrade', $options->context);
        // Can view the individual peergrades given to their own items.
        $settings->permissions->viewsome = has_capability('mod/peerforum:viewsomepeergrades', $options->context);
        // Can view individual peergrades.
        $settings->permissions->viewall = has_capability('mod/peerforum:viewallpeergrades', $options->context);
        // Can submit peergrades. // TODO remove and deprecate!
        $settings->permissions->peergrade = has_capability('mod/peerforum:peergrade', $options->context);
        // These are not used anywhere.
        // Can submit peergrades as professor.
        $settings->permissions->professor = has_capability('mod/peerforum:professorpeergrade', $options->context);
        // Can submit peergrades as student.
        $settings->permissions->student = has_capability('mod/peerforum:studentpeergrade', $options->context, null, false);

        // Check module capabilities
        // This is mostly for backwards compatability with old modules that previously implemented their own peergrades.
        $pluginpermissionsarray = $this->get_plugin_permissions_array($options->context->id,
                $options->component,
                $options->peergradearea);
        $settings->pluginpermissions = new stdClass;
        $settings->pluginpermissions->view = $pluginpermissionsarray['view'];
        $settings->pluginpermissions->viewany = $pluginpermissionsarray['viewany'];
        $settings->pluginpermissions->viewsome = $pluginpermissionsarray['viewsome'];
        $settings->pluginpermissions->viewall = $pluginpermissionsarray['viewall'];
        $settings->pluginpermissions->professor = $pluginpermissionsarray['professor'];
        $settings->pluginpermissions->student = $pluginpermissionsarray['student'];
        $settings->pluginpermissions->peergrade =
                $this->check_peergrade_permission($pluginpermissionsarray, $settings->finalgrademode);

        return $settings;
    }

    /**
     * Generates a scale object that can be returned
     *
     * @param int $peergradescaleid scale-type identifier
     * @return stdClass scale for peergrades
     * @global moodle_database $DB moodle database object
     */
    protected function generate_peergrade_peergradescale_object($peergradescaleid, $peerforumid) {
        global $CFG, $DB, $PAGE;
        if (!array_key_exists('s' . $peergradescaleid, $this->peergradescales)) {
            $peergradescale = new stdClass;
            $peergradescale->id = $peergradescaleid;
            $peergradescale->name = null;
            $peergradescale->courseid = null;
            $peergradescale->peergradescaleitems = array();
            $peergradescale->isnumeric = true;
            $peergradescale->max = $peergradescaleid;

            if ($peergradescaleid < 0) {
                // It is a proper scale (not numeric).
                $peergradescalerecord = $DB->get_record('scale', array('id' => abs($peergradescaleid)));
                if ($peergradescalerecord) {
                    // We need to generate an array with string keys starting at 1.
                    $peergradescalearray = explode(',', $peergradescalerecord->peergradescale);
                    $c = count($peergradescalearray);
                    for ($i = 0; $i < $c; $i++) {
                        // Treat index as a string to allow sorting without changing the value.
                        $peergradescale->peergradescaleitems[(string) ($i + 1)] = $peergradescalearray[$i];
                    }
                    krsort($peergradescale->peergradescaleitems); // Have the highest grade scale item appear first.
                    $peergradescale->isnumeric = false;
                    $peergradescale->name = $peergradescalerecord->name;
                    $peergradescale->courseid = $peergradescalerecord->courseid;
                    $peergradescale->max = count($peergradescale->peergradescaleitems);
                }
            } else {
                // Generate an array of values for numeric scales.
                $peergradescalerecord = $DB->get_record('peerforum', array('id' => $peerforumid))->peergradescale;
                $DB->set_field('peerforum', 'peergradescale', $peergradescalerecord, null);
                $peergradescale->id = $peergradescalerecord;
                for ($i = 0; $i <= (int) $peergradescaleid; $i++) {
                    $peergradescale->peergradescaleitems[(string) $i] = $i;
                }
            }
            $this->peergradescales['s' . $peergradescaleid] = $peergradescale;
        }
        return $this->peergradescales['s' . $peergradescaleid];
    }

    /**
     * Gets the time the given item was created
     *
     * TODO: MDL-31511 - Find a better solution for this, its not ideal to test for fields really we should be
     * asking the component the item belongs to what field to look for or even the value we
     * are looking for.
     *
     * @param stdClass $item
     * @return int|null return null if the created time is unavailable, otherwise return a timestamp
     */
    protected function get_item_time_created($item) {
        if (!empty($item->created)) {
            return $item->created; // The forum_posts table has created instead of timecreated.
        } else if (!empty($item->timecreated)) {
            return $item->timecreated;
        } else {
            return null;
        }
    }

    /**
     * Returns an array of grades calculated by aggregating item peergrades.
     *
     * @param stdClass $options {
     *      userid => int the id of the user whose items were peergraded, NOT the user who submitted peergrades. 0 to update all.
     *         [required]
     *      aggregationmethod => int the aggregation method to apply when calculating grades ie PEERGRADE_AGGREGATE_AVERAGE
     *         [required]
     *      peergradescaleid => int the scale from which the user can select a peergrade. Used for bounds checking. [required]
     *      itemtable => int the table containing the items [required]
     *      itemtableusercolum => int the column of the user table containing the item owner's user id [required]
     *      component => The component for the peergrades [required]
     *      peergradearea => The peergradearea for the peergrades [required]
     *      contextid => int the context in which the peergraded items exist [optional]
     *      modulename => string the name of the module [optional]
     *      moduleid => int the id of the module instance [optional]
     * }
     * @return array the array of the user's grades
     */
    public function get_user_grades($options) {
        global $DB;

        $contextid = null;

        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when getting user grades from peergrades.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required opt when getting user grades from peergrades.');
        }

        // If the calling code doesn't supply a context id we'll have to figure it out.
        if (!empty($options->contextid)) {
            $contextid = $options->contextid;
        } else if (!empty($options->modulename) && !empty($options->moduleid)) {
            $modulename = $options->modulename;
            $moduleid   = intval($options->moduleid);

            // Going direct to the db for the context id seems wrong.
            $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
            $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel)";
            $sql = "SELECT cm.* $ctxselect
                      FROM {course_modules} cm
                 LEFT JOIN {modules} mo ON mo.id = cm.module
                 LEFT JOIN {{$modulename}} m ON m.id = cm.instance $ctxjoin
                     WHERE mo.name=:modulename AND
                           m.id=:moduleid";
            $params = array('modulename' => $modulename, 'moduleid' => $moduleid, 'contextlevel' => CONTEXT_MODULE);
            $contextrecord = $DB->get_record_sql($sql, $params, '*', MUST_EXIST);
            $contextid = $contextrecord->ctxid;
        }

        $params = array();
        $params['contextid']  = $contextid;
        $params['component']  = $options->component;
        $params['peergradearea'] = $options->peergradearea;
        $itemtable            = $options->itemtable;
        $itemtableusercolumn  = $options->itemtableusercolumn;
        $peergradescaleid     = $options->peergradescaleid;
        $aggregationstring    = $this->get_aggregation_method($options->aggregationmethod);

        // If userid is not 0 we only want the grade for a single user.
        $singleuserwhere = '';
        if ($options->userid != 0) {
            $params['userid1'] = intval($options->userid);
            $singleuserwhere = "AND i.{$itemtableusercolumn} = :userid1";
        }

        $context = context::instance_by_id($contextid);

        $stdjoin = get_with_capability_join($context,  'mod/peerforum:studentpeergrade', 'r.userid');
        // MDL-24648 The where line used to be "WHERE (r.contextid is null or r.contextid=:contextid)".
        // r.contextid will be null for users who haven't been rated yet.
        // No longer including users who haven't been rated to reduce memory requirements.
        $sql = "SELECT u.id as id, u.id AS userid, $aggregationstring(r.peergrade) AS rawgrade, 0 as type
                  FROM {user} u
             LEFT JOIN {{$itemtable}} i ON u.id=i.{$itemtableusercolumn}
             LEFT JOIN {peerforum_peergrade} r ON r.itemid=i.id
                       $stdjoin->joins
                 WHERE r.contextid = :contextid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
                       $singleuserwhere
              GROUP BY u.id";
        $results0 = $DB->get_records_sql($sql, $params + $stdjoin->params);

        $profjoin = get_with_capability_join($context, 'mod/peerforum:professorpeergrade', 'r.userid');
        $sql = "SELECT u.id as id, u.id AS userid, $aggregationstring(r.peergrade) AS rawgrade, 1 as type
                  FROM {user} u
             LEFT JOIN {{$itemtable}} i ON u.id=i.{$itemtableusercolumn}
             LEFT JOIN {peerforum_peergrade} r ON r.itemid=i.id
                       $profjoin->joins
                 WHERE r.contextid = :contextid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
                       $singleuserwhere
              GROUP BY u.id";
        $results1 = $DB->get_records_sql($sql, $params + $profjoin->params);
        $results = array_merge($results0, $results1);

        $studentgivenpeergrades = array();
        $professorgivenpeergrades = array();
        if ($results) {

            $peergradescale = null;
            $max = 0;
            if ($options->peergradescaleid >= 0) {
                // Numeric.
                $max = $options->peergradescaleid;
            } else {
                // Custom scales.
                $peergradescale = $DB->get_record('scale', array('id' => -$options->peergradescaleid));
                if ($peergradescale) {
                    $peergradescale = explode(',', $peergradescale->scale);
                    $max = count($peergradescale);
                } else {
                    debugging('peergrade_manager::get_user_grades() received a scale ID that doesnt exist');
                }
            }

            // It could throw off the grading if count and sum returned a rawgrade higher than scale
            // so to prevent it we review the results and ensure that rawgrade does not exceed the scale.
            // If it does we set rawgrade = scale (i.e. full credit).
            foreach ($results as $rid => $result) {
                if ($options->peergradescaleid >= 0) {
                    // Numeric.
                    if ($result->rawgrade > $options->peergradescaleid) {
                        $results[$rid]->rawgrade = $options->peergradescaleid;
                    }
                } else {
                    // Scales.
                    if (!empty($peergradescale) && $result->rawgrade > $max) {
                        $results[$rid]->rawgrade = $max;
                    }
                }
            }

            foreach ($results as $result) {
                if ($result->type === '0') {
                    unset($result->type);
                    $studentgivenpeergrades[$result->id] = $result;
                } else if ($result->type === '1') {
                    unset($result->type);
                    $professorgivenpeergrades[$result->id] = $result;
                }
            }
        }

        return array($studentgivenpeergrades, $professorgivenpeergrades);
    }

    /**
     * Returns array of aggregate types. Used by peergrades.
     *
     * @return array aggregate types
     */
    public function get_aggregate_types() {
        return array(PEERGRADE_AGGREGATE_NONE => get_string('peeraggregatenone', 'peerforum'),
                PEERGRADE_AGGREGATE_AVERAGE => get_string('peeraggregateavg', 'peerforum'),
                PEERGRADE_AGGREGATE_COUNT => get_string('peeraggregatecount', 'peerforum'),
                PEERGRADE_AGGREGATE_MAXIMUM => get_string('peeraggregatemax', 'peerforum'),
                PEERGRADE_AGGREGATE_MINIMUM => get_string('peeraggregatemin', 'peerforum'),
                PEERGRADE_AGGREGATE_SUM => get_string('peeraggregatesum', 'peerforum'));
    }

    /**
     * Converts an aggregation method constant into something that can be included in SQL
     *
     * @param int $aggregate An aggregation constant. For example, PEERGRADE_AGGREGATE_AVERAGE.
     * @return string an SQL aggregation method
     */
    public function get_aggregation_method($aggregate) {
        $aggregatestr = null;
        switch ($aggregate) {
            case PEERGRADE_AGGREGATE_AVERAGE:
                $aggregatestr = 'AVG';
                break;
            case PEERGRADE_AGGREGATE_COUNT:
                $aggregatestr = 'COUNT';
                break;
            case PEERGRADE_AGGREGATE_MAXIMUM:
                $aggregatestr = 'MAX';
                break;
            case PEERGRADE_AGGREGATE_MINIMUM:
                $aggregatestr = 'MIN';
                break;
            case PEERGRADE_AGGREGATE_SUM:
                $aggregatestr = 'SUM';
                break;
            default:
                $aggregatestr = 'AVG'; // Default to this to avoid real breakage - MDL-22270.
                debugging('Incorrect call to get_aggregation_method(), incorrect aggregate method ' . $aggregate, DEBUG_DEVELOPER);
        }
        return $aggregatestr;
    }

    /**
     * Looks for a callback like forum_peergrade_permissions() to retrieve permissions from the plugin whose items are being
     * peergraded
     *
     * @param int $contextid The current context id
     * @param string $component the name of the component that is using peergrades ie 'mod_peerforum'
     * @param string $peergradearea The area the peergrade is associated with
     * @return array peergrade related permissions
     */
    public function get_plugin_permissions_array($contextid, $component, $peergradearea): array {
        $pluginpermissionsarray = null;
        // Deny by default.
        $defaultpluginpermissions = array(
                'view' => false, 'viewany' => false, 'viewsome' => false, 'viewall' => false,
                'student' => false, 'professor' => false,
                'peergrade' => false // This one is a hack and must be replaced eventually!
        );
        if (!empty($component)) {
            list($type, $name) = core_component::normalize_component($component);
            $pluginpermissionsarray = plugin_callback($type,
                    $name,
                    'peergrade',
                    'permissions',
                    array($contextid, $component, $peergradearea),
                    $defaultpluginpermissions);
        } else {
            $pluginpermissionsarray = $defaultpluginpermissions;
        }
        return $pluginpermissionsarray;
    }

    /**
     * Returns the grading permissions based on the final grade mode. Useful because there are several.
     *
     * @param array $pluginpermissionsarray
     * @param int $finalgrademode
     * @return bool
     */
    public function check_peergrade_permission(array $pluginpermissionsarray, int $finalgrademode): bool {
        switch ($finalgrademode) {
            case PEERFORUM_MODE_PROFESSOR:
                // You can't rate if you don't have the system cap and the plugin cap (cause now are the same).
                if (!$pluginpermissionsarray['professor']) {
                    return false;
                }
                break;
            case PEERFORUM_MODE_STUDENT:
                // You can't rate if you don't have the system cap and the plugin cap (cause now are the same).
                if (!$pluginpermissionsarray['student']) {
                    return false;
                }
                break;
            case PEERFORUM_MODE_PROFESSORSTUDENT:
                // You can't rate if you don't have the system cap and the plugin cap (cause now are the same).
                return $pluginpermissionsarray['professor'] || $pluginpermissionsarray['student'];
        }

        return true;
    }

    /**
     * Validates a submitted peergrade
     *
     * @param array $params submitted data
     *      context => object the context in which the peergraded items exists [required]
     *      component => The component the peergrade belongs to [required]
     *      peergradearea => The peergradearea the peergrade is associated with [required]
     *      itemid => int the ID of the object being peergraded [required]
     *      peergradescaleid => int the scale from which the user can select a peergrade. Used for bounds checking. [required]
     *      peergrade => int the submitted peergrade
     *      peergradeduserid => int the id of the user whose items have been peergraded. 0 to update all. [required]
     *      aggregation => int the aggregation method to apply when calculating grades ie PEERGRADE_AGGREGATE_AVERAGE [optional]
     * @return boolean true if the peergrade is valid, false if callback not found, throws peergrade_exception if peergrade is
     *         invalid
     */
    public function check_peergrade_is_valid($params) {

        if (!isset($params['context'])) {
            throw new coding_exception('The context option is a required option when checking peergrade validity.');
        }
        if (!isset($params['component'])) {
            throw new coding_exception('The component option is now a required option when checking peergrade validity');
        }
        if (!isset($params['peergradearea'])) {
            throw new coding_exception('The peergradearea option is now a required option when checking peergrade validity');
        }
        if (!isset($params['itemid'])) {
            throw new coding_exception('The itemid option is now a required option when checking peergrade validity');
        }
        if (!isset($params['peergradescaleid'])) {
            throw new coding_exception('The peergradescaleid option is now a required option when checking peergrade validity');
        }
        if (!isset($params['peergradeduserid'])) {
            throw new coding_exception('The peergradeduserid option is now a required option when checking peergrade validity');
        }
        if (!isset($params['feedback'])) {
            throw new coding_exception('The feedback option is now a required option when checking peergrade validity');
        }

        // This does not check for permissions and if it is assigned.

        list($plugintype, $pluginname) = core_component::normalize_component($params['component']);

        // This looks for a function like peerforum_peergrade_validate() in mod_peerforum lib.php
        // wrapping the params array in another array as call_user_func_array() expands arrays into multiple arguments.
        $isvalid = plugin_callback($plugintype, $pluginname, 'peergrade', 'validate', array($params), null);

        // If null then the callback does not exist.
        if ($isvalid === null) {
            $isvalid = false;
            debugging('peergrade validation callback not found for component ' . clean_param($component, PARAM_ALPHANUMEXT));
        }
        return $isvalid;
    }

    public function initialise_assignpeer_javascript(moodle_page $page) {
        global $CFG;

        // Only needs to be initialized once.
        static $done = false;
        if ($done) {
            return true;
        }

        $module = array('name' => 'core_peerforum_assignpeer',
                'fullpath' => '/mod/peerforum/assignpeer.js',
                'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

        $page->requires->js_init_call('M.core_peerforum_assignpeer.init', null, false, $module);
        $done = true;

        return true;
    }

    public function initialise_assignpeersparent_javascript(moodle_page $page) {
        global $CFG;

        // Only needs to be initialized once.
        static $done = false;
        if ($done) {
            return true;
        }

        $module = array('name' => 'core_peerforum_assignpeersparent',
                'fullpath' => '/mod/peerforum/assignpeersparent.js',
                'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

        $page->requires->js_init_call('M.core_peerforum_assignpeersparent.init', null, false, $module);
        $done = true;

        return true;
    }

    public function initialise_removepeer_javascript(moodle_page $page) {
        global $CFG;

        // Only needs to be initialized once.
        static $done = false;
        if ($done) {
            return true;
        }

        $module = array('name' => 'core_peerforum_removepeer',
                'fullpath' => '/mod/peerforum/removepeer.js',
                'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

        $page->requires->js_init_call('M.core_peerforum_removepeer.init', null, false, $module);
        $done = true;

        return true;
    }

    /**
     * Initialises JavaScript to enable AJAX peergrades on the provided page
     *
     * @param moodle_page $page
     * @return true always returns true
     */
    public function initialise_peergrade_javascript(moodle_page $page) {
        global $CFG;

        // Only needs to be initialized once.
        static $done = false;
        if ($done) {
            return true;
        }

        $module = array('name' => 'core_peergrade',
                'fullpath' => '/peergrade/module.js',
                'requires' => array('node', 'event', 'overlay', 'io-base', 'json'));

        $page->requires->js_init_call('M.core_peergrade.init', null, false, $module);
        $done = true;

        return true;
    }

    /**
     * Returns a string that describes the aggregation method that was provided.
     *
     * @param string $aggregationmethod
     * @return string describes the aggregation method that was provided
     */
    public function get_aggregate_label($aggregationmethod) {
        $aggregatelabel = '';
        switch ($aggregationmethod) {
            case PEERGRADE_AGGREGATE_AVERAGE :
                $aggregatelabel .= get_string("peeraggregateavg", "peerforum");
                break;
            case PEERGRADE_AGGREGATE_COUNT :
                $aggregatelabel .= get_string("peeraggregatecount", "peerforum");
                break;
            case PEERGRADE_AGGREGATE_MAXIMUM :
                $aggregatelabel .= get_string("peeraggregatemax", "peerforum");
                break;
            case PEERGRADE_AGGREGATE_MINIMUM :
                $aggregatelabel .= get_string("peeraggregatemin", "peerforum");
                break;
            case PEERGRADE_AGGREGATE_SUM :
                $aggregatelabel .= get_string("peeraggregatesum", "peerforum");
                break;
        }
        $aggregatelabel .= get_string('labelsep', 'langconfig');
        return $aggregatelabel;
    }

    /**
     * Adds a new peergrade
     *
     * @param stdClass $cm course module object
     * @param stdClass $context context object
     * @param string $component component name
     * @param string $peergradearea peergrade area
     * @param int $itemid the item id
     * @param int $peergradescaleid the scale id
     * @param int $userpeergrade the user peergrade
     * @param int $peergradeduserid the peergraded user id
     * @param int $aggregationmethod the aggregation method
     * @param string $feedback the feedback
     * @since Moodle 3.2
     */
    public function add_peergrade($cm, $context, $component, $peergradearea, $itemid, $peergradescaleid, $userpeergrade,
            $peergradeduserid, $aggregationmethod, $feedback) {
        global $CFG, $DB, $USER;

        $result = new stdClass;
        $result->id = $itemid;
        // Check the module peergrade permissions.
        // Doing this check here rather than within peergrade_manager::get_peergrades() so we can return a error response.
        $pluginpermissionsarray = $this->get_plugin_permissions_array($context->id, $component, $peergradearea);

        if (!$pluginpermissionsarray['peergrade']) {
            $result->error = 'peergradepermissiondenied';
            return $result;
        } else {
            $params = array(
                    'context' => $context,
                    'component' => $component,
                    'peergradearea' => $peergradearea,
                    'itemid' => $itemid,
                    'peergradescaleid' => $peergradescaleid,
                    'peergrade' => $userpeergrade,
                    'feedback' => $feedback,
                    'peergradeduserid' => $peergradeduserid,
                    'aggregation' => $aggregationmethod
            );
            if (!$this->check_peergrade_is_valid($params)) {
                $result->error = 'peergradeinvalid';
                return $result;
            }
        }

        // Peergrade options used to update the peergrade then retrieve the aggregate.
        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $context;
        $peergradeoptions->peergradearea = $peergradearea;
        $peergradeoptions->component = $component;
        $peergradeoptions->itemid = $itemid;
        $peergradeoptions->peergradescaleid = $peergradescaleid;
        $peergradeoptions->feedback = $feedback;
        $peergradeoptions->userid = $USER->id;
        $peergradeoptions->itemuserid = $peergradeduserid;

        $peergrade = new peergrade($peergradeoptions);
        $peergrade->update_peergrade($userpeergrade, $feedback);

        // Future possible enhancement: add a setting to turn grade updating off for those who don't want them in gradebook.
        // Note that this would need to be done in both peergrade.php and peergrade_ajax.php.
        if ($context->contextlevel == CONTEXT_MODULE) {
            // Tell the module that its grades have changed.
            $modinstance = $DB->get_record($cm->modname, array('id' => $cm->instance));
            if ($modinstance) {
                $modinstance->cmidnumber = $cm->id; // MDL-12961.
                $functionname = $cm->modname . '_update_grades';
                require_once($CFG->dirroot . "/mod/{$cm->modname}/lib.php");
                if (function_exists($functionname)) {
                    $functionname($modinstance, $peergradeduserid);
                }
            }
        }

        // Object to return to client as JSON.
        $result->success = true;

        // Need to retrieve the updated item to get its new aggregate value.
        $item = new stdClass;
        $item->id = $itemid;

        // Most of $peergradeoptions variables were previously set.
        $peergradeoptions->items = array($item);
        $peergradeoptions->aggregate = $aggregationmethod;

        $items = $this->get_peergrades($peergradeoptions);
        $firstpeergrade = $items[0]->peergrade;

        // See if the user has permission to see the peergrade aggregate.
        if ($firstpeergrade->user_can_view_aggregate()) {

            // For custom peergradescales return text not the value.
            // This peergradescales weirdness will go away when peergradescales are refactored.
            $peergradescalearray = null;
            $aggregatetoreturn = round($firstpeergrade->aggregate, 1);

            // Output a dash if aggregation method == COUNT as the count is output next to the aggregate anyway.
            if ($firstpeergrade->settings->aggregationmethod == PEERGRADE_AGGREGATE_COUNT or $firstpeergrade->count == 0) {
                $aggregatetoreturn = ' - ';
            } else if ($firstpeergrade->settings->peergradescale->id < 0) { // If its non-numeric peergradescale.
                // Dont use the peergradescale item if the aggregation method
                // is sum as adding items from a custom peergradescale makes no sense.
                if ($firstpeergrade->settings->aggregationmethod != PEERGRADE_AGGREGATE_SUM) {
                    $peergradescalerecord = $DB->get_record('scale', array('id' => -$firstpeergrade->settings->peergradescale->id));
                    if ($peergradescalerecord) {
                        $peergradescalearray = explode(',', $peergradescalerecord->peergradescale);
                        $aggregatetoreturn = $peergradescalearray[$aggregatetoreturn - 1];
                    }
                }
            }

            $result->aggregate = $aggregatetoreturn;
            $result->count = $firstpeergrade->count;
            $result->itemid = $itemid;
            $result->feedback = $feedback;
        }
        return $result;
    }

    public function get_possible_peergraders($peergradeoptions) {
        global $DB;

        $users = get_users_by_capability($peergradeoptions->context, 'mod/peerforum:studentpeergrade', 'u.id AS userid');
        // Get the items from the database.
        list($useridtest, $params) = $DB->get_in_or_equal(
                array_map(function($u) {
                    return $u->userid;
                }, $users), SQL_PARAMS_NAMED);

        $userfields = user_picture::fields('s', ['deleted']);
        $sql = "SELECT $userfields
                  FROM {user} s
             LEFT JOIN {peerforum_user_block} b ON s.id = b.userid
                 WHERE s.id {$useridtest} AND
                       b.id IS NULL";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * From a list of peer grades, adds a outliers property to each in $peergrade->outlier, based on the options.
     *
     * @param stdClass[] $peergrades
     * @param $peergradesettings
     */
    public function check_peergrade_outliers(array $peergrades, $peergradesettings) {
        if (empty($peergrades)) {
            return array();
        }

        $outdetectvalue = $peergradesettings->outdetectvalue;
        $warningoutliers = $peergradesettings->warningoutliers;

        $vpeergrades = array_map(static function($p) {
            return $p->peergrade;
        }, $peergrades);

        // Average.
        $average = array_sum($vpeergrades) / count($vpeergrades);

        // Mode.
        $cvpeergrades = array_count_values($vpeergrades);
        asort($cvpeergrades);
        $mode = count(array_unique($vpeergrades)) < count($vpeergrades) ? array_keys($cvpeergrades, max($cvpeergrades)) : array();

        if ($peergradesettings->outlierdetection == 'standard deviation') {
            // Standard deviation.
            $arrvariance = array_map(function($i) use ($average) {
                return ($i - $average) ** 2;
            }, $vpeergrades);
            $sd = count($vpeergrades) > 1 ? sqrt(array_sum($arrvariance)/count($vpeergrades)) : 0;

            if ($peergradesettings->outdetectvalue > 0) {

                if ($warningoutliers) {
                    $warningoutliers *= $sd;
                }

                $sd *= $peergradesettings->outdetectvalue;
            }

            $minpeergrade = $average - $sd;
            $maxpeergrade = $average + $sd;
        } else if ($peergradesettings->outlierdetection == 'grade points') {

            $minpeergrade = $average - $outdetectvalue;
            $maxpeergrade = $average + $outdetectvalue;
        }

        // Warning interval with threshold.
        if ($warningoutliers) {
            $minwarning = $average - $warningoutliers;
            $maxwarning = $average + $warningoutliers;
        }

        foreach ($peergrades as &$peergrade) {
            $grade = $peergrade->peergrade;

            if ($grade < $minpeergrade || $grade > $maxpeergrade) {
                $peergrade->outlier = PEERGRADE_OUTLIER_OUT;
            } else if (!in_array($grade, $mode) && $warningoutliers && ($grade < $minwarning || $grade > $maxwarning)) {
                $peergrade->outlier = PEERGRADE_OUTLIER_WARNING;
            } else {
                $peergrade->outlier = PEERGRADE_OUTLIER_IN;
            }
        }
        unset($peergrade);

        return $peergrades;
    }

    /**
     * Adds a new peergrade
     *
     * @param $peergradeoptions
     * @return array
     */
    public function assign_peergraders($peergradeoptions) {
        global $DB;

        if (!$peergradeoptions->gradeprofessorpost &&
                has_capability('mod/peerforum:professorpeergrade',
                        $peergradeoptions->context,
                        $peergradeoptions->itemuserid)) {
            return array();
        }

        $users = get_users_by_capability($peergradeoptions->context, 'mod/peerforum:studentpeergrade', 'u.id AS userid');
        // Get the items from the database.
        list($useridtest, $params) = $DB->get_in_or_equal(
                array_map(function ($u) {
                    return $u->userid;
                }, $users), SQL_PARAMS_NAMED);

        $coursecontext = $peergradeoptions->context->get_course_context();

        $params['contextid'] = $peergradeoptions->context->id;
        $params['component'] = $peergradeoptions->component;
        $params['peergradearea'] = $peergradeoptions->peergradearea;
        $params['itemuserid'] = $peergradeoptions->itemuserid;
        $params['courseid'] = $coursecontext->instanceid;

        $sql = "SELECT r.userid, r.peergraded, COUNT(r.id) - SUM(r.ended) AS numcurrent, SUM(r.expired) AS sexpied,
                       MAX(r.timeassigned) AS lastassign, COUNT(p.id) AS numpeergrades, COUNT(r.id) AS numassigned,
                       COUNT(r.id) + COUNT(r.id) - SUM(r.ended) AS workload
                  FROM {peerforum_time_assigned} r
             LEFT JOIN {peerforum_peergrade} p ON r.peergraded = p.id
                 WHERE r.contextid = :contextid AND
                       r.userid {$useridtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
              GROUP BY r.userid, r.component, r.peergradearea, r.contextid
              ORDER BY workload ASC, numcurrent ASC, numpeergrades ASC, sexpied ASC, lastassign ASC, r.userid";
        $usersassigned = $DB->get_records_sql($sql, $params);

        $sql = "SELECT r.userid, r.peergraded, COUNT(r.id) - SUM(r.ended) AS numcurrent, SUM(r.expired) AS sexpied,
                       MAX(r.timeassigned) AS lastassign, COUNT(p.id) AS numpeergrades, n.id AS nominationid, n.nomination,
                       COUNT(r.id) AS numassigned, COUNT(r.id) + COUNT(r.id) - SUM(r.ended) AS workload
                  FROM {peerforum_time_assigned} r
             LEFT JOIN {peerforum_relationship_nomin} n ON r.userid = n.userid
             LEFT JOIN {peerforum_peergrade} p ON r.peergraded = p.id
                 WHERE r.contextid = :contextid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea AND
                       n.course = :courseid AND
                       n.otheruserid = :itemuserid
              GROUP BY r.userid, r.component, r.peergradearea, r.contextid
              ORDER BY workload ASC, numcurrent ASC, numpeergrades ASC, sexpied ASC, lastassign ASC, r.userid";
        $usersnominated = $DB->get_records_sql($sql, $params);

        $usersblocked = $DB->get_records('peerforum_user_block', null, '', 'userid');

        $emptyusers = array_filter($users, function($us) use ($usersassigned) {
            return !isset($usersassigned[$us->userid]) || empty($usersassigned[$us->userid]);
        });

        $assignedbefore = array();
        if ($peergradeoptions->autoassignreplies && isset($peergradeoptions->itemfamily) && !empty($peergradeoptions->itemfamily)) {
            foreach ($peergradeoptions->itemfamily as $olditem) {
                if ($peergradeoptions->itemuserid == $olditem->userid && $olditem->id != $peergradeoptions->itemid) {
                    $assignedbefore = $DB->get_records('peerforum_time_assigned', array('itemid' => $olditem->id)) ?? array();
                    if (!empty($assignedbefore)) {
                        break;
                    }
                }
            }
        }

        $lmusers = array();
        $llusers = array();
        $nomusersassigned = array();
        foreach ($usersnominated as $k => $usernominated) {
            if ($usernominated->nomination == '1') {
                $lmusers[$k] = $usernominated;
            } else if ($usernominated->nomination == '-1') {
                $llusers[$k] = $usernominated;
            }
        }

        $nlmusers = count($lmusers);
        $nllusers = count($llusers);
        if (random_int(1, 5) <= $nlmusers) {
            $lmu = $lmusers[array_key_first($lmusers)];
            $nomusersassigned[$lmu->userid] = $lmu;
        }
        if (random_int(1, 5) <= $nllusers) {
            $llu = $llusers[array_key_first($llusers)];
            $nomusersassigned[$llu->userid] = $llu;
        }

        $usersassigned = $assignedbefore + $nomusersassigned + $emptyusers + $usersassigned;

        $gradersalreadyassigned = array();
        foreach ($usersassigned as $userassigned) {
            if (count($gradersalreadyassigned) == $peergradeoptions->maxpeergraders) {
                break;
            }
            if (isset($gradersalreadyassigned[$userassigned->userid])) {
                continue;
            }
            if ($userassigned->userid == $peergradeoptions->itemuserid) {
                continue;
            }
            if (isset($usersblocked[$userassigned->userid])) {
                continue;
            }
            $userid = $userassigned->userid;

            $nominationid = $usersnominated[$userid]->nominationid ?? 0;
            $nominationvalue = $usersnominated[$userid]->nomination ?? 0;

            $assignoptions = new stdClass();
            $assignoptions->userid = $userid;
            $assignoptions->itemid = $peergradeoptions->itemid;
            $assignoptions->ended = 0;
            $assignoptions->expired = 0;
            $assignoptions->blocked = 0;
            $assignoptions->nomination = $nominationid;
            $assignoptions->nominationvalue = $nominationvalue;
            $assignoptions->peergraded = 0;
            $assignoptions->peergradeoptions = $peergradeoptions;
            $assign = new peergrade_assignment($assignoptions);
            if ($assign->assign()) {
                $gradersalreadyassigned[$userid] = $userid;
            }
        }
        return $gradersalreadyassigned;
    }

    /**
     * Get peergrades created since a given time.
     *
     * @param stdClass $context context object
     * @param string $component component name
     * @param int $since the time to check
     * @return array list of peergrades db records since the given timelimit
     * @since Moodle 3.2
     */
    public function get_component_peergrades_since($context, $component, $since) {
        global $DB, $USER;

        $peergradessince = array();
        $where = 'contextid = ? AND component = ? AND (timecreated > ? OR timemodified > ?)';
        $peergrades = $DB->get_records_select('peerforum_peergrade', $where, array($context->id, $component, $since, $since));
        // Check area by area if we have permissions.
        $permissions = array();
        $rm = new peergrade_manager();

        foreach ($peergrades as $peergrade) {
            // Check if the permission array for the area is cached.
            if (!isset($permissions[$peergrade->peergradearea])) {
                $permissions[$peergrade->peergradearea] = $rm->get_plugin_permissions_array($context->id, $component,
                        $peergrade->peergradearea);
            }

            if (($permissions[$peergrade->peergradearea]['view'] and $peergrade->userid == $USER->id) or
                    ($permissions[$peergrade->peergradearea]['viewany'] or $permissions[$peergrade->peergradearea]['viewall'])) {
                $peergradessince[$peergrade->id] = $peergrade;
            }
        }
        return $peergradessince;
    }

    /**
     * @param array $filters
     * @param string $xal extra alias
     * @param string $sortsql
     * @param array $group
     * @param array $count
     * @return array
     */
    public function get_items_from_filters(array $filters = array(), $xal = '', $sortsql = '', $group = array(), $count = array()) {
        global $DB;
        $where = [];
        $alias = 's';
        if (isset($filters['userid'])) {
            $userid = $filters['userid'];
            $where[] = "{$alias}.userid = {$userid}";
        }
        if (isset($filters['ended'])) {
            $ended = $filters['ended'];
            $where[] = "{$alias}.ended = {$ended}";
        }
        if (isset($filters['expired'])) {
            $expired = $filters['expired'];
            $where[] = "{$alias}.expired = {$expired}";
        }
        if (isset($filters['peergradednot'])) {
            $peergraded = $filters['peergradednot'];
            $where[] = "{$alias}.peergraded <> {$peergraded}";
        }
        $wheres = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $count = array_reduce(
                $count, static function ($prev, $c) use ($alias) {
                    return $prev . ", COUNT(NULLIF({$alias}.{$c},0)) AS n{$c}";
                }, '');
        $groupal = array_map(static function ($g) use ($alias) {
            return "{$alias}.{$g}";
        }, $group);
        $groupal = implode(', ', $groupal);
        $groups = !empty($groupal) ? 'GROUP BY ' . $groupal : '';
        $groupsl = !empty($group) ? implode(', ', $group) . ',' : '';
        $sortsql = !empty($sortsql) ? $sortsql . ',' : '';
        $extraalias = !empty($xal) ? ',' . $xal : '';
        $userfields = user_picture::fields('u', ['deleted'], 'useridu');

        $sql = "SELECT {$groupsl} a.* {$extraalias}
                FROM (
                SELECT  {$alias}.* {$count}, b.id AS ublocked, {$userfields}
                  FROM {peerforum_time_assigned} {$alias}
             LEFT JOIN {user} u ON {$alias}.userid = u.id
             LEFT JOIN {peerforum_user_block} b ON {$alias}.userid = b.userid
                       {$wheres}
                       {$groups}
                     ) a
              ORDER BY {$sortsql} timeassigned DESC";
        return $DB->get_records_sql($sql);
    }

    /**
     * Returns a URL to view all of the peergrades for the item this peergrade is for.
     *
     * If this is a peergrade of a post then this URL will take the user to a page that shows all of the peergrades for the post
     * (this one included).
     *
     * @param bool $popup whether of not the URL should be loaded in a popup
     * @return moodle_url URL to view all of the peergrades for the item this peergrade is for.
     */
    public function get_block_user_url($userid, $contextid, $returnurl = null) {
        if (empty($returnurl)) {
                global $PAGE;
                $returnurl = $PAGE->url;
        }
        $args = array(
                'blockeduserid' => $userid,
                'contextid' => $contextid,
                'returnurl' => $returnurl,
                'sesskey' => sesskey(),
        );

        return new moodle_url('/peergrade/block.php', $args);
    }
} // End peergrade_manager class definition.

/**
 * The peergrade_exception class for exceptions specific to the peergrades system
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade_exception extends moodle_exception {
    /**
     * @var string The message to accompany the thrown exception
     */
    public $message;

    /**
     * Generate exceptions that can be easily identified as coming from the peergrades system
     *
     * @param string $errorcode the error code to generate
     * @param string $component
     * @throws coding_exception
     */
    public function __construct($errorcode, $component = 'error') {
        $this->errorcode = $errorcode;
        $this->message = get_string($errorcode, $component);
    }
}
