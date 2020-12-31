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
 * Privacy Subsystem implementation for mod_peerforum.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\privacy;

use core_grades\component_gradeitem as gradeitem;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper as request_helper;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;
use tool_dataprivacy\context_instance;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/grading/lib.php');

/**
 * Implementation of the privacy subsystem plugin provider for the peerforum activity module.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin has data.
        \core_privacy\local\metadata\provider,

        // This plugin currently implements the original plugin\provider interface.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider,

        // This plugin has some sitewide user preferences to export.
        \core_privacy\local\request\user_preference_provider {

    use subcontext_info;

    /**
     * Returns meta data about this system.
     *
     * @param collection $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        // The 'peerforum' table does not store any specific user data.
        $items->add_database_table('peerforum_digests', [
                'peerforum' => 'privacy:metadata:peerforum_digests:peerforum',
                'userid' => 'privacy:metadata:peerforum_digests:userid',
                'maildigest' => 'privacy:metadata:peerforum_digests:maildigest',
        ], 'privacy:metadata:peerforum_digests');

        // The 'peerforum_discussions' table stores the metadata about each peerforum discussion.
        $items->add_database_table('peerforum_discussions', [
                'name' => 'privacy:metadata:peerforum_discussions:name',
                'userid' => 'privacy:metadata:peerforum_discussions:userid',
                'assessed' => 'privacy:metadata:peerforum_discussions:assessed',
                'timemodified' => 'privacy:metadata:peerforum_discussions:timemodified',
                'usermodified' => 'privacy:metadata:peerforum_discussions:usermodified',
        ], 'privacy:metadata:peerforum_discussions');

        // The 'peerforum_discussion_subs' table stores information about which discussions a user is subscribed to.
        $items->add_database_table('peerforum_discussion_subs', [
                'discussionid' => 'privacy:metadata:peerforum_discussion_subs:discussionid',
                'preference' => 'privacy:metadata:peerforum_discussion_subs:preference',
                'userid' => 'privacy:metadata:peerforum_discussion_subs:userid',
        ], 'privacy:metadata:peerforum_discussion_subs');

        // The 'peerforum_posts' table stores the metadata about each peerforum discussion.
        $items->add_database_table('peerforum_posts', [
                'discussion' => 'privacy:metadata:peerforum_posts:discussion',
                'parent' => 'privacy:metadata:peerforum_posts:parent',
                'created' => 'privacy:metadata:peerforum_posts:created',
                'modified' => 'privacy:metadata:peerforum_posts:modified',
                'subject' => 'privacy:metadata:peerforum_posts:subject',
                'message' => 'privacy:metadata:peerforum_posts:message',
                'userid' => 'privacy:metadata:peerforum_posts:userid',
                'privatereplyto' => 'privacy:metadata:peerforum_posts:privatereplyto',
        ], 'privacy:metadata:peerforum_posts');

        // The 'peerforum_queue' table contains user data, but it is only a temporary cache of other data.
        // We should not need to export it as it does not allow profiling of a user.

        // The 'peerforum_read' table stores data about which peerforum posts have been read by each user.
        $items->add_database_table('peerforum_read', [
                'userid' => 'privacy:metadata:peerforum_read:userid',
                'discussionid' => 'privacy:metadata:peerforum_read:discussionid',
                'postid' => 'privacy:metadata:peerforum_read:postid',
                'firstread' => 'privacy:metadata:peerforum_read:firstread',
                'lastread' => 'privacy:metadata:peerforum_read:lastread',
        ], 'privacy:metadata:peerforum_read');

        // The 'peerforum_subscriptions' table stores information about which peerforums a user is subscribed to.
        $items->add_database_table('peerforum_subscriptions', [
                'userid' => 'privacy:metadata:peerforum_subscriptions:userid',
                'peerforum' => 'privacy:metadata:peerforum_subscriptions:peerforum',
        ], 'privacy:metadata:peerforum_subscriptions');

        // The 'peerforum_subscriptions' table stores information about which peerforums a user is subscribed to.
        $items->add_database_table('peerforum_track_prefs', [
                'userid' => 'privacy:metadata:peerforum_track_prefs:userid',
                'peerforumid' => 'privacy:metadata:peerforum_track_prefs:peerforumid',
        ], 'privacy:metadata:peerforum_track_prefs');

        // The 'peerforum_queue' table stores temporary data that is not exported/deleted.
        $items->add_database_table('peerforum_queue', [
                'userid' => 'privacy:metadata:peerforum_queue:userid',
                'discussionid' => 'privacy:metadata:peerforum_queue:discussionid',
                'postid' => 'privacy:metadata:peerforum_queue:postid',
                'timemodified' => 'privacy:metadata:peerforum_queue:timemodified'
        ], 'privacy:metadata:peerforum_queue');

        // The 'peerforum_grades' table stores grade data.
        $items->add_database_table('peerforum_grades', [
                'userid' => 'privacy:metadata:peerforum_grades:userid',
                'peerforum' => 'privacy:metadata:peerforum_grades:peerforum',
                'grade' => 'privacy:metadata:peerforum_grades:grade',
        ], 'privacy:metadata:peerforum_grades');

        // PeerForum posts can be tagged and rated.
        $items->link_subsystem('core_tag', 'privacy:metadata:core_tag');
        $items->link_subsystem('core_rating', 'privacy:metadata:core_rating');

        // There are several user preferences.
        $items->add_user_preference('maildigest', 'privacy:metadata:preference:maildigest');
        $items->add_user_preference('autosubscribe', 'privacy:metadata:preference:autosubscribe');
        $items->add_user_preference('trackforums', 'privacy:metadata:preference:trackforums');
        $items->add_user_preference('markasreadonnotification', 'privacy:metadata:preference:markasreadonnotification');
        $items->add_user_preference('peerforum_discussionlistsortorder',
                'privacy:metadata:preference:peerforum_discussionlistsortorder');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of peerforum, that is any peerforum where the user has made any post, rated any content, or has any preferences.
     *
     * @param int $userid The user to search.
     * @return  contextlist $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $params = [
                'modname' => 'peerforum',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
        ];

        // Discussion creators.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                 WHERE d.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // Post authors.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                  JOIN {peerforum_posts} p ON p.discussion = d.id
                 WHERE p.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // PeerForum digest records.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_digests} dig ON dig.peerforum = f.id
                 WHERE dig.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // PeerForum subscriptions.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_subscriptions} sub ON sub.peerforum = f.id
                 WHERE sub.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // Discussion subscriptions.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussion_subs} dsub ON dsub.peerforum = f.id
                 WHERE dsub.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // Discussion tracking preferences.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_track_prefs} pref ON pref.peerforumid = f.id
                 WHERE pref.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // Discussion read records.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_read} hasread ON hasread.peerforumid = f.id
                 WHERE hasread.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        // Rating authors.
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_peerforum', 'post', 'p.id', $userid, true);
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                  JOIN {peerforum_posts} p ON p.discussion = d.id
                  {$ratingsql->join}
                 WHERE {$ratingsql->userwhere}
        ";
        $params += $ratingsql->params;
        $contextlist->add_from_sql($sql, $params);

        // PeerForum grades.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_grades} fg ON fg.peerforum = f.id
                 WHERE fg.userid = :userid
        ";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
                'instanceid' => $context->instanceid,
                'modulename' => 'peerforum',
        ];

        // Discussion authors.
        $sql = "SELECT d.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // PeerForum authors.
        $sql = "SELECT p.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                  JOIN {peerforum_posts} p ON d.id = p.discussion
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // PeerForum post ratings.
        $sql = "SELECT p.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
                  JOIN {peerforum_posts} p ON d.id = p.discussion
                 WHERE cm.id = :instanceid";
        \core_rating\privacy\provider::get_users_in_context_from_sql($userlist, 'rat', 'mod_peerforum', 'post', $sql, $params);

        // PeerForum Digest settings.
        $sql = "SELECT dig.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_digests} dig ON dig.peerforum = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // PeerForum Subscriptions.
        $sql = "SELECT sub.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_subscriptions} sub ON sub.peerforum = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Discussion subscriptions.
        $sql = "SELECT dsub.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_discussion_subs} dsub ON dsub.peerforum = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Read Posts.
        $sql = "SELECT hasread.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_read} hasread ON hasread.peerforumid = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Tracking Preferences.
        $sql = "SELECT pref.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_track_prefs} pref ON pref.peerforumid = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // PeerForum grades.
        $sql = "SELECT fg.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_grades} fg ON fg.peerforum = f.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $user = \core_user::get_user($userid);

        switch ($user->maildigest) {
            case 1:
                $digestdescription = get_string('emaildigestcomplete');
                break;
            case 2:
                $digestdescription = get_string('emaildigestsubjects');
                break;
            case 0:
            default:
                $digestdescription = get_string('emaildigestoff');
                break;
        }
        writer::export_user_preference('mod_peerforum', 'maildigest', $user->maildigest, $digestdescription);

        switch ($user->autosubscribe) {
            case 0:
                $subscribedescription = get_string('autosubscribeno');
                break;
            case 1:
            default:
                $subscribedescription = get_string('autosubscribeyes');
                break;
        }
        writer::export_user_preference('mod_peerforum', 'autosubscribe', $user->autosubscribe, $subscribedescription);

        switch ($user->trackforums) {
            case 0:
                $trackforumdescription = get_string('trackforumsno');
                break;
            case 1:
            default:
                $trackforumdescription = get_string('trackforumsyes');
                break;
        }
        writer::export_user_preference('mod_peerforum', 'trackforums', $user->trackforums, $trackforumdescription);

        $markasreadonnotification = get_user_preferences('markasreadonnotification', null, $user->id);
        if (null !== $markasreadonnotification) {
            switch ($markasreadonnotification) {
                case 0:
                    $markasreadonnotificationdescription = get_string('markasreadonnotificationno', 'mod_peerforum');
                    break;
                case 1:
                default:
                    $markasreadonnotificationdescription = get_string('markasreadonnotificationyes', 'mod_peerforum');
                    break;
            }
            writer::export_user_preference('mod_peerforum', 'markasreadonnotification', $markasreadonnotification,
                    $markasreadonnotificationdescription);
        }

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $discussionlistvault = $vaultfactory->get_discussions_in_peerforum_vault();
        $discussionlistsortorder = get_user_preferences('peerforum_discussionlistsortorder',
                $discussionlistvault::SORTORDER_LASTPOST_DESC);
        switch ($discussionlistsortorder) {
            case $discussionlistvault::SORTORDER_LASTPOST_DESC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbylastpostdesc',
                        'mod_peerforum');
                break;
            case $discussionlistvault::SORTORDER_LASTPOST_ASC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbylastpostasc',
                        'mod_peerforum');
                break;
            case $discussionlistvault::SORTORDER_CREATED_DESC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbycreateddesc',
                        'mod_peerforum');
                break;
            case $discussionlistvault::SORTORDER_CREATED_ASC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbycreatedasc',
                        'mod_peerforum');
                break;
            case $discussionlistvault::SORTORDER_REPLIES_DESC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbyrepliesdesc',
                        'mod_peerforum');
                break;
            case $discussionlistvault::SORTORDER_REPLIES_ASC:
                $discussionlistsortorderdescription = get_string('discussionlistsortbyrepliesasc',
                        'mod_peerforum');
                break;
        }
        writer::export_user_preference('mod_peerforum', 'peerforum_discussionlistsortorder',
                $discussionlistsortorder, $discussionlistsortorderdescription);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params = $contextparams;

        // Digested peerforums.
        $sql = "SELECT
                    c.id AS contextid,
                    dig.maildigest AS maildigest
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_digests} dig ON dig.peerforum = f.id
                 WHERE (
                    dig.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;
        $digests = $DB->get_records_sql_menu($sql, $params);

        // PeerForum subscriptions.
        $sql = "SELECT
                    c.id AS contextid,
                    sub.userid AS subscribed
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_subscriptions} sub ON sub.peerforum = f.id
                 WHERE (
                    sub.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;
        $subscriptions = $DB->get_records_sql_menu($sql, $params);

        // Tracked peerforums.
        $sql = "SELECT
                    c.id AS contextid,
                    pref.userid AS tracked
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_track_prefs} pref ON pref.peerforumid = f.id
                 WHERE (
                    pref.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;
        $tracked = $DB->get_records_sql_menu($sql, $params);

        // PeerForum grades.
        $sql = "SELECT
                    c.id AS contextid,
                    fg.grade AS grade,
                    f.grade_peerforum AS gradetype
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {peerforum} f ON f.id = cm.instance
                  JOIN {peerforum_grades} fg ON fg.peerforum = f.id
                 WHERE (
                    fg.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;
        $grades = $DB->get_records_sql_menu($sql, $params);

        $sql = "SELECT
                    c.id AS contextid,
                    f.*,
                    cm.id AS cmid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {peerforum} f ON f.id = cm.instance
                 WHERE (
                    c.id {$contextsql}
                )
        ";

        $params += $contextparams;

        // Keep a mapping of peerforumid to contextid.
        $mappings = [];

        $peerforums = $DB->get_recordset_sql($sql, $params);
        foreach ($peerforums as $peerforum) {
            $mappings[$peerforum->id] = $peerforum->contextid;

            $context = \context::instance_by_id($mappings[$peerforum->id]);

            // Store the main peerforum data.
            $data = request_helper::get_context_data($context, $user);
            writer::with_context($context)
                    ->export_data([], $data);
            request_helper::export_context_files($context, $user);

            // Store relevant metadata about this peerforum instance.
            if (isset($digests[$peerforum->contextid])) {
                static::export_digest_data($userid, $peerforum, $digests[$peerforum->contextid]);
            }
            if (isset($subscriptions[$peerforum->contextid])) {
                static::export_subscription_data($userid, $peerforum, $subscriptions[$peerforum->contextid]);
            }
            if (isset($tracked[$peerforum->contextid])) {
                static::export_tracking_data($userid, $peerforum, $tracked[$peerforum->contextid]);
            }
            if (isset($grades[$peerforum->contextid])) {
                static::export_grading_data($userid, $peerforum, $grades[$peerforum->contextid]);
            }
        }
        $peerforums->close();

        if (!empty($mappings)) {
            // Store all discussion data for this peerforum.
            static::export_discussion_data($userid, $mappings);

            // Store all post data for this peerforum.
            static::export_all_posts($userid, $mappings);
        }
    }

    /**
     * Store all information about all discussions that we have detected this user to have access to.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param array $mappings A list of mappings from peerforumid => contextid.
     * @return  array       Which peerforums had data written for them.
     */
    protected static function export_discussion_data(int $userid, array $mappings) {
        global $DB;

        // Find all of the discussions, and discussion subscriptions for this peerforum.
        list($peerforuminsql, $peerforumparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $sql = "SELECT
                    d.*,
                    g.name as groupname,
                    dsub.preference
                  FROM {peerforum} f
                  JOIN {peerforum_discussions} d ON d.peerforum = f.id
             LEFT JOIN {groups} g ON g.id = d.groupid
             LEFT JOIN {peerforum_discussion_subs} dsub ON dsub.discussion = d.id AND dsub.userid = :dsubuserid
             LEFT JOIN {peerforum_posts} p ON p.discussion = d.id
                 WHERE f.id ${peerforuminsql}
                   AND (
                        d.userid    = :discussionuserid OR
                        p.userid    = :postuserid OR
                        dsub.id IS NOT NULL
                   )
        ";

        $params = [
                'postuserid' => $userid,
                'discussionuserid' => $userid,
                'dsubuserid' => $userid,
        ];
        $params += $peerforumparams;

        // Keep track of the peerforums which have data.
        $peerforumswithdata = [];

        $discussions = $DB->get_recordset_sql($sql, $params);
        foreach ($discussions as $discussion) {
            // No need to take timestart into account as the user has some involvement already.
            // Ignore discussion timeend as it should not block access to user data.
            $peerforumswithdata[$discussion->peerforum] = true;
            $context = \context::instance_by_id($mappings[$discussion->peerforum]);

            // Store related metadata for this discussion.
            static::export_discussion_subscription_data($userid, $context, $discussion);

            $discussiondata = (object) [
                    'name' => format_string($discussion->name, true),
                    'pinned' => transform::yesno((bool) $discussion->pinned),
                    'timemodified' => transform::datetime($discussion->timemodified),
                    'usermodified' => transform::datetime($discussion->usermodified),
                    'creator_was_you' => transform::yesno($discussion->userid == $userid),
            ];

            // Store the discussion content.
            writer::with_context($context)
                    ->export_data(static::get_discussion_area($discussion), $discussiondata);

            // PeerForum discussions do not have any files associately directly with them.
        }

        $discussions->close();

        return $peerforumswithdata;
    }

    /**
     * Store all information about all posts that we have detected this user to have access to.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param array $mappings A list of mappings from peerforumid => contextid.
     * @return  array       Which peerforums had data written for them.
     */
    protected static function export_all_posts(int $userid, array $mappings) {
        global $DB;

        $commonsql = "SELECT p.discussion AS id, f.id AS peerforumid, d.name, d.groupid
                        FROM {peerforum} f
                        JOIN {peerforum_discussions} d ON d.peerforum = f.id
                        JOIN {peerforum_posts} p ON p.discussion = d.id";

        // All discussions with posts authored by the user or containing private replies to the user.
        list($peerforuminsql1, $peerforumparams1) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $sql1 = "{$commonsql}
                       WHERE f.id {$peerforuminsql1}
                         AND (p.userid = :postuserid OR p.privatereplyto = :privatereplyrecipient)";

        // All discussions with the posts marked as read by the user.
        list($peerforuminsql2, $peerforumparams2) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $sql2 = "{$commonsql}
                        JOIN {peerforum_read} fr ON fr.postid = p.id
                       WHERE f.id {$peerforuminsql2}
                         AND fr.userid = :readuserid";

        // All discussions with ratings provided by the user.
        list($peerforuminsql3, $peerforumparams3) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_peerforum', 'post', 'p.id', $userid, true);
        $sql3 = "{$commonsql}
                 {$ratingsql->join}
                       WHERE f.id {$peerforuminsql3}
                         AND {$ratingsql->userwhere}";

        $sql = "SELECT *
                  FROM ({$sql1} UNION {$sql2} UNION {$sql3}) united
              GROUP BY id, peerforumid, name, groupid";

        $params = [
                'postuserid' => $userid,
                'readuserid' => $userid,
                'privatereplyrecipient' => $userid,
        ];
        $params += $peerforumparams1;
        $params += $peerforumparams2;
        $params += $peerforumparams3;
        $params += $ratingsql->params;

        $discussions = $DB->get_records_sql($sql, $params);
        foreach ($discussions as $discussion) {
            $context = \context::instance_by_id($mappings[$discussion->peerforumid]);
            static::export_all_posts_in_discussion($userid, $context, $discussion);
        }
    }

    /**
     * Store all information about all posts that we have detected this user to have access to.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \context $context The instance of the peerforum context.
     * @param \stdClass $discussion The discussion whose data is being exported.
     */
    protected static function export_all_posts_in_discussion(int $userid, \context $context, \stdClass $discussion) {
        global $DB, $USER;

        $discussionid = $discussion->id;

        // Find all of the posts, and post subscriptions for this peerforum.
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_peerforum', 'post', 'p.id', $userid);
        $sql = "SELECT
                    p.*,
                    d.peerforum AS peerforumid,
                    fr.firstread,
                    fr.lastread,
                    fr.id AS readflag,
                    rat.id AS hasratings
                    FROM {peerforum_discussions} d
                    JOIN {peerforum_posts} p ON p.discussion = d.id
               LEFT JOIN {peerforum_read} fr ON fr.postid = p.id AND fr.userid = :readuserid
            {$ratingsql->join} AND {$ratingsql->userwhere}
                   WHERE d.id = :discussionid
                     AND (
                            p.privatereplyto = 0
                         OR p.privatereplyto = :privatereplyrecipient
                         OR p.userid = :privatereplyauthor
                     )
        ";

        $params = [
                'discussionid' => $discussionid,
                'readuserid' => $userid,
                'privatereplyrecipient' => $userid,
                'privatereplyauthor' => $userid,
        ];
        $params += $ratingsql->params;

        // Keep track of the peerforums which have data.
        $structure = (object) [
                'children' => [],
        ];

        $posts = $DB->get_records_sql($sql, $params);
        foreach ($posts as $post) {
            $post->hasdata = (isset($post->hasdata)) ? $post->hasdata : false;
            $post->hasdata = $post->hasdata || !empty($post->hasratings);
            $post->hasdata = $post->hasdata || $post->readflag;
            $post->hasdata = $post->hasdata || ($post->userid == $USER->id);
            $post->hasdata = $post->hasdata || ($post->privatereplyto == $USER->id);

            if (0 == $post->parent) {
                $structure->children[$post->id] = $post;
            } else {
                if (empty($posts[$post->parent]->children)) {
                    $posts[$post->parent]->children = [];
                }
                $posts[$post->parent]->children[$post->id] = $post;
            }

            // Set all parents.
            if ($post->hasdata) {
                $curpost = $post;
                while ($curpost->parent != 0) {
                    $curpost = $posts[$curpost->parent];
                    $curpost->hasdata = true;
                }
            }
        }

        $discussionarea = static::get_discussion_area($discussion);
        $discussionarea[] = get_string('posts', 'mod_peerforum');
        static::export_posts_in_structure($userid, $context, $discussionarea, $structure);
    }

    /**
     * Export all posts in the provided structure.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \context $context The instance of the peerforum context.
     * @param array $parentarea The subcontext of the parent.
     * @param \stdClass $structure The post structure and all of its children
     */
    protected static function export_posts_in_structure(int $userid, \context $context, $parentarea, \stdClass $structure) {
        foreach ($structure->children as $post) {
            if (!$post->hasdata) {
                // This tree has no content belonging to the user. Skip it and all children.
                continue;
            }

            $postarea = array_merge($parentarea, static::get_post_area($post));

            // Store the post content.
            static::export_post_data($userid, $context, $postarea, $post);

            if (isset($post->children)) {
                // Now export children of this post.
                static::export_posts_in_structure($userid, $context, $postarea, $post);
            }
        }
    }

    /**
     * Export all data in the post.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \context $context The instance of the peerforum context.
     * @param array $postarea The subcontext of the parent.
     * @param \stdClass $post The post structure and all of its children
     */
    protected static function export_post_data(int $userid, \context $context, $postarea, $post) {
        // Store related metadata.
        static::export_read_data($userid, $context, $postarea, $post);

        $postdata = (object) [
                'subject' => format_string($post->subject, true),
                'created' => transform::datetime($post->created),
                'modified' => transform::datetime($post->modified),
                'author_was_you' => transform::yesno($post->userid == $userid),
        ];

        if (!empty($post->privatereplyto)) {
            $postdata->privatereply = transform::yesno(true);
        }

        $postdata->message = writer::with_context($context)
                ->rewrite_pluginfile_urls($postarea, 'mod_peerforum', 'post', $post->id, $post->message);

        $postdata->message = format_text($postdata->message, $post->messageformat, (object) [
                'para' => false,
                'trusted' => $post->messagetrust,
                'context' => $context,
        ]);

        writer::with_context($context)
                // Store the post.
                ->export_data($postarea, $postdata)

                // Store the associated files.
                ->export_area_files($postarea, 'mod_peerforum', 'post', $post->id);

        if ($post->userid == $userid) {
            // Store all ratings against this post as the post belongs to the user. All ratings on it are ratings of their content.
            \core_rating\privacy\provider::export_area_ratings($userid, $context, $postarea, 'mod_peerforum', 'post', $post->id,
                    false);

            // Store all tags against this post as the tag belongs to the user.
            \core_tag\privacy\provider::export_item_tags($userid, $context, $postarea, 'mod_peerforum', 'peerforum_posts',
                    $post->id);

            // Export all user data stored for this post from the plagiarism API.
            $coursecontext = $context->get_course_context();
            \core_plagiarism\privacy\provider::export_plagiarism_user_data($userid, $context, $postarea, [
                    'cmid' => $context->instanceid,
                    'course' => $coursecontext->instanceid,
                    'peerforum' => $post->peerforumid,
                    'discussionid' => $post->discussion,
                    'postid' => $post->id,
            ]);
        }

        // Check for any ratings that the user has made on this post.
        \core_rating\privacy\provider::export_area_ratings($userid,
                $context,
                $postarea,
                'mod_peerforum',
                'post',
                $post->id,
                $userid,
                true
        );
    }

    /**
     * Store data about daily digest preferences
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \stdClass $peerforum The peerforum whose data is being exported.
     * @param int $maildigest The mail digest setting for this peerforum.
     * @return  bool        Whether any data was stored.
     */
    protected static function export_digest_data(int $userid, \stdClass $peerforum, int $maildigest) {
        if (null !== $maildigest) {
            // The user has a specific maildigest preference for this peerforum.
            $a = (object) [
                    'peerforum' => format_string($peerforum->name, true),
            ];

            switch ($maildigest) {
                case 0:
                    $a->type = get_string('emaildigestoffshort', 'mod_peerforum');
                    break;
                case 1:
                    $a->type = get_string('emaildigestcompleteshort', 'mod_peerforum');
                    break;
                case 2:
                    $a->type = get_string('emaildigestsubjectsshort', 'mod_peerforum');
                    break;
            }

            writer::with_context(\context_module::instance($peerforum->cmid))
                    ->export_metadata([], 'digestpreference', $maildigest,
                            get_string('privacy:digesttypepreference', 'mod_peerforum', $a));

            return true;
        }

        return false;
    }

    /**
     * Store data about whether the user subscribes to peerforum.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \stdClass $peerforum The peerforum whose data is being exported.
     * @param int $subscribed if the user is subscribed
     * @return  bool        Whether any data was stored.
     */
    protected static function export_subscription_data(int $userid, \stdClass $peerforum, int $subscribed) {
        if (null !== $subscribed) {
            // The user is subscribed to this peerforum.
            writer::with_context(\context_module::instance($peerforum->cmid))
                    ->export_metadata([], 'subscriptionpreference', 1,
                            get_string('privacy:subscribedtopeerforum', 'mod_peerforum'));

            return true;
        }

        return false;
    }

    /**
     * Store data about whether the user subscribes to this particular discussion.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \context_module $context The instance of the peerforum context.
     * @param \stdClass $discussion The discussion whose data is being exported.
     * @return  bool        Whether any data was stored.
     */
    protected static function export_discussion_subscription_data(int $userid, \context_module $context, \stdClass $discussion) {
        $area = static::get_discussion_area($discussion);
        if (null !== $discussion->preference) {
            // The user has a specific subscription preference for this discussion.
            $a = (object) [];

            switch ($discussion->preference) {
                case \mod_peerforum\subscriptions::PEERFORUM_DISCUSSION_UNSUBSCRIBED:
                    $a->preference = get_string('unsubscribed', 'mod_peerforum');
                    break;
                default:
                    $a->preference = get_string('subscribed', 'mod_peerforum');
                    break;
            }

            writer::with_context($context)
                    ->export_metadata(
                            $area,
                            'subscriptionpreference',
                            $discussion->preference,
                            get_string('privacy:discussionsubscriptionpreference', 'mod_peerforum', $a)
                    );

            return true;
        }

        return true;
    }

    /**
     * Store peerforum read-tracking data about a particular peerforum.
     *
     * This is whether a peerforum has read-tracking enabled or not.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \stdClass $peerforum The peerforum whose data is being exported.
     * @param int $tracke if the user is subscribed
     * @return  bool        Whether any data was stored.
     */
    protected static function export_tracking_data(int $userid, \stdClass $peerforum, int $tracked) {
        if (null !== $tracked) {
            // The user has a main preference to track all peerforums, but has opted out of this one.
            writer::with_context(\context_module::instance($peerforum->cmid))
                    ->export_metadata([], 'trackreadpreference', 0, get_string('privacy:readtrackingdisabled', 'mod_peerforum'));

            return true;
        }

        return false;
    }

    protected static function export_grading_data(int $userid, \stdClass $peerforum, int $grade) {
        global $USER;
        if (null !== $grade) {
            $context = \context_module::instance($peerforum->cmid);
            $exportpath = array_merge([],
                    [get_string('privacy:metadata:peerforum_grades', 'mod_peerforum')]);
            $gradingmanager = get_grading_manager($context, 'mod_peerforum', 'peerforum');
            $controller = $gradingmanager->get_active_controller();

            // Check for advanced grading and retrieve that information.
            if (isset($controller)) {
                $gradeduser = \core_user::get_user($userid);
                // Fetch the gradeitem instance.
                $gradeitem = gradeitem::instance($controller->get_component(), $context, $controller->get_area());
                $grade = $gradeitem->get_grade_for_user($gradeduser, $USER);
                $controllercontext = $controller->get_context();
                \core_grading\privacy\provider::export_item_data($controllercontext, $grade->id, $exportpath);
            } else {
                self::export_grade_data($grade, $context, $peerforum, $exportpath);
            }
            // The user has a grade for this peerforum.
            writer::with_context(\context_module::instance($peerforum->cmid))
                    ->export_metadata($exportpath, 'gradingenabled', 1,
                            get_string('privacy:metadata:peerforum_grades:grade', 'mod_peerforum'));

            return true;
        }

        return false;
    }

    protected static function export_grade_data(int $grade, \context $context, \stdClass $peerforum, array $path) {
        $gradedata = (object) [
                'peerforum' => $peerforum->name,
                'grade' => $grade,
        ];

        writer::with_context($context)
                ->export_data($path, $gradedata);
    }

    /**
     * Store read-tracking information about a particular peerforum post.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @param \context_module $context The instance of the peerforum context.
     * @param array $postarea The subcontext for this post.
     * @param \stdClass $post The post whose data is being exported.
     * @return  bool        Whether any data was stored.
     */
    protected static function export_read_data(int $userid, \context_module $context, array $postarea, \stdClass $post) {
        if (null !== $post->firstread) {
            $a = (object) [
                    'firstread' => $post->firstread,
                    'lastread' => $post->lastread,
            ];

            writer::with_context($context)
                    ->export_metadata(
                            $postarea,
                            'postread',
                            (object) [
                                    'firstread' => $post->firstread,
                                    'lastread' => $post->lastread,
                            ],
                            get_string('privacy:postwasread', 'mod_peerforum', $a)
                    );

            return true;
        }

        return false;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('peerforum', $context->instanceid)) {
            return;
        }

        $peerforumid = $cm->instance;

        $DB->delete_records('peerforum_track_prefs', ['peerforumid' => $peerforumid]);
        $DB->delete_records('peerforum_subscriptions', ['peerforum' => $peerforumid]);
        $DB->delete_records('peerforum_grades', ['peerforum' => $peerforumid]);
        $DB->delete_records('peerforum_read', ['peerforumid' => $peerforumid]);
        $DB->delete_records('peerforum_digests', ['peerforum' => $peerforumid]);

        // Delete advanced grading information.
        $gradingmanager = get_grading_manager($context, 'mod_peerforum', 'peerforum');
        $controller = $gradingmanager->get_active_controller();
        if (isset($controller)) {
            \core_grading\privacy\provider::delete_instance_data($context);
        }

        $DB->delete_records('peerforum_grades', ['peerforum' => $peerforumid]);

        // Delete all discussion items.
        $DB->delete_records_select(
                'peerforum_queue',
                "discussionid IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforum)",
                [
                        'peerforum' => $peerforumid,
                ]
        );

        $DB->delete_records_select(
                'peerforum_posts',
                "discussion IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforum)",
                [
                        'peerforum' => $peerforumid,
                ]
        );

        $DB->delete_records('peerforum_discussion_subs', ['peerforum' => $peerforumid]);
        $DB->delete_records('peerforum_discussions', ['peerforum' => $peerforumid]);

        // Delete all files from the posts.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_peerforum', 'post');
        $fs->delete_area_files($context->id, 'mod_peerforum', 'attachment');

        // Delete all ratings in the context.
        \core_rating\privacy\provider::delete_ratings($context, 'mod_peerforum', 'post');

        // Delete all Tags.
        \core_tag\privacy\provider::delete_item_tags($context, 'mod_peerforum', 'peerforum_posts');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = $user->id;
        foreach ($contextlist as $context) {
            // Get the course module.
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            $peerforum = $DB->get_record('peerforum', ['id' => $cm->instance]);

            $DB->delete_records('peerforum_track_prefs', [
                    'peerforumid' => $peerforum->id,
                    'userid' => $userid,
            ]);
            $DB->delete_records('peerforum_subscriptions', [
                    'peerforum' => $peerforum->id,
                    'userid' => $userid,
            ]);
            $DB->delete_records('peerforum_read', [
                    'peerforumid' => $peerforum->id,
                    'userid' => $userid,
            ]);

            $DB->delete_records('peerforum_digests', [
                    'peerforum' => $peerforum->id,
                    'userid' => $userid,
            ]);

            // Delete all discussion items.
            $DB->delete_records_select(
                    'peerforum_queue',
                    "userid = :userid AND discussionid IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforum)",
                    [
                            'userid' => $userid,
                            'peerforum' => $peerforum->id,
                    ]
            );

            $DB->delete_records('peerforum_discussion_subs', [
                    'peerforum' => $peerforum->id,
                    'userid' => $userid,
            ]);

            // Handle any advanced grading method data first.
            $grades = $DB->get_records('peerforum_grades', ['peerforum' => $peerforum->id, 'userid' => $user->id]);
            $gradingmanager = get_grading_manager($context, 'peerforum_grades', 'peerforum');
            $controller = $gradingmanager->get_active_controller();
            foreach ($grades as $grade) {
                // Delete advanced grading information.
                if (isset($controller)) {
                    \core_grading\privacy\provider::delete_instance_data($context, $grade->id);
                }
            }
            // Advanced grading methods have been cleared, lets clear our module now.
            $DB->delete_records('peerforum_grades', [
                    'peerforum' => $peerforum->id,
                    'userid' => $userid,
            ]);

            // Do not delete discussion or peerforum posts.
            // Instead update them to reflect that the content has been deleted.
            $postsql = "userid = :userid AND discussion IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforum)";
            $postidsql = "SELECT fp.id FROM {peerforum_posts} fp WHERE {$postsql}";
            $postparams = [
                    'peerforum' => $peerforum->id,
                    'userid' => $userid,
            ];

            // Update the subject.
            $DB->set_field_select('peerforum_posts', 'subject', '', $postsql, $postparams);

            // Update the message and its format.
            $DB->set_field_select('peerforum_posts', 'message', '', $postsql, $postparams);
            $DB->set_field_select('peerforum_posts', 'messageformat', FORMAT_PLAIN, $postsql, $postparams);

            // Mark the post as deleted.
            $DB->set_field_select('peerforum_posts', 'deleted', 1, $postsql, $postparams);

            // Note: Do _not_ delete ratings of other users. Only delete ratings on the users own posts.
            // Ratings are aggregate fields and deleting the rating of this post will have an effect on the rating
            // of any post.
            \core_rating\privacy\provider::delete_ratings_select($context, 'mod_peerforum', 'post',
                    "IN ($postidsql)", $postparams);

            // Delete all Tags.
            \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_peerforum', 'peerforum_posts',
                    "IN ($postidsql)", $postparams);

            // Delete all files from the posts.
            $fs = get_file_storage();
            $fs->delete_area_files_select($context->id, 'mod_peerforum', 'post', "IN ($postidsql)", $postparams);
            $fs->delete_area_files_select($context->id, 'mod_peerforum', 'attachment', "IN ($postidsql)", $postparams);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $peerforum = $DB->get_record('peerforum', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['peerforumid' => $peerforum->id], $userinparams);

        $DB->delete_records_select('peerforum_track_prefs', "peerforumid = :peerforumid AND userid {$userinsql}", $params);
        $DB->delete_records_select('peerforum_subscriptions', "peerforum = :peerforumid AND userid {$userinsql}", $params);
        $DB->delete_records_select('peerforum_read', "peerforumid = :peerforumid AND userid {$userinsql}", $params);
        $DB->delete_records_select(
                'peerforum_queue',
                "userid {$userinsql} AND discussionid IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforumid)",
                $params
        );
        $DB->delete_records_select('peerforum_discussion_subs', "peerforum = :peerforumid AND userid {$userinsql}", $params);

        // Do not delete discussion or peerforum posts.
        // Instead update them to reflect that the content has been deleted.
        $postsql = "userid {$userinsql} AND discussion IN (SELECT id FROM {peerforum_discussions} WHERE peerforum = :peerforumid)";
        $postidsql = "SELECT fp.id FROM {peerforum_posts} fp WHERE {$postsql}";

        // Update the subject.
        $DB->set_field_select('peerforum_posts', 'subject', '', $postsql, $params);

        // Update the subject and its format.
        $DB->set_field_select('peerforum_posts', 'message', '', $postsql, $params);
        $DB->set_field_select('peerforum_posts', 'messageformat', FORMAT_PLAIN, $postsql, $params);

        // Mark the post as deleted.
        $DB->set_field_select('peerforum_posts', 'deleted', 1, $postsql, $params);

        // Note: Do _not_ delete ratings of other users. Only delete ratings on the users own posts.
        // Ratings are aggregate fields and deleting the rating of this post will have an effect on the rating
        // of any post.
        \core_rating\privacy\provider::delete_ratings_select($context, 'mod_peerforum', 'post', "IN ($postidsql)", $params);

        // Delete all Tags.
        \core_tag\privacy\provider::delete_item_tags_select($context, 'mod_peerforum', 'peerforum_posts', "IN ($postidsql)",
                $params);

        // Delete all files from the posts.
        $fs = get_file_storage();
        $fs->delete_area_files_select($context->id, 'mod_peerforum', 'post', "IN ($postidsql)", $params);
        $fs->delete_area_files_select($context->id, 'mod_peerforum', 'attachment', "IN ($postidsql)", $params);

        list($sql, $params) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['peerforum'] = $peerforum->id;
        // Delete advanced grading information.
        $grades = $DB->get_records_select('peerforum_grades', "peerforum = :peerforum AND userid $sql", $params);
        $gradeids = array_keys($grades);
        $gradingmanager = get_grading_manager($context, 'mod_peerforum', 'peerforum');
        $controller = $gradingmanager->get_active_controller();
        if (isset($controller)) {
            // Careful here, if no gradeids are provided then all data is deleted for the context.
            if (!empty($gradeids)) {
                \core_grading\privacy\provider::delete_data_for_instances($context, $gradeids);
            }
        }
    }
}
