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
 * A scheduled task for peerforum cron.
 *
 * @package    mod_peerforum
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/peerforum/lib.php');

/**
 * The main scheduled task for the peerforum.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var The list of courses which contain posts to be sent.
     */
    protected $courses = [];

    /**
     * @var The list of peerforums which contain posts to be sent.
     */
    protected $peerforums = [];

    /**
     * @var The list of discussions which contain posts to be sent.
     */
    protected $discussions = [];

    /**
     * @var The list of posts to be sent.
     */
    protected $posts = [];

    /**
     * @var The list of post authors.
     */
    protected $users = [];

    /**
     * @var The list of subscribed users.
     */
    protected $subscribedusers = [];

    /**
     * @var The list of digest users.
     */
    protected $digestusers = [];

    /**
     * @var The list of adhoc data for sending.
     */
    protected $adhocdata = [];

    /**
     * @var The list of adhoc peergrade data for sending.
     */
    protected $adhocpgdata = [];

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_peerforum');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute() {
        global $CFG, $DB;

        $timenow = time();

        // Delete any really old posts in the digest queue.
        $weekago = $timenow - (7 * 24 * 3600);
        $this->log_start("Removing old digest records from 7 days ago.");
        $DB->delete_records_select('peerforum_queue', "timemodified < ?", array($weekago));
        $this->log_finish("Removed all old digest records.");

        $endtime = $timenow - $CFG->maxeditingtime;
        $starttime = $endtime - (2 * DAYSECS);
        $this->log_start("Fetching unmailed posts.");
        if (!$posts = $this->get_unmailed_posts($starttime, $endtime, $timenow)) {
            $this->log_finish("No posts found.", 1);
        }
        $this->log_finish("Done");

        $this->log_start("Fetching unmailed peergrades.");
        if (!$peergrades = $this->get_unmailed_peergrades($posts)) {
            $this->log_finish("No peergrades found.", 1);
            $peergrades = array();
        }
        $this->log_finish("Done");

        $this->log_start("Fetching delayed posts.");
        if (!$delayedposts = $this->get_delayed_posts()) {
            $this->log_finish("No delayed posts found.", 1);
            $delayedposts = array();
        }
        $this->log_finish("Done");

        // Process post data and turn into adhoc tasks.
        $this->process_post_data($posts, $delayedposts, $peergrades);

        if (empty($posts)) {
            return false;
        }

        // Mark posts as read.
        list($in, $params) = $DB->get_in_or_equal(array_keys($posts));
        $DB->set_field_select('peerforum_posts', 'mailed', 1, "id {$in}", $params);
    }

    /**
     * Process all posts and convert to appropriated hoc tasks.
     *
     * @param \stdClass[] $posts
     * @param \stdClass[] $delayedposts
     * @param \stdClass[] $peergrades
     */
    protected function process_post_data($posts, $delayedposts, $peergrades) {
        $discussionids = [];
        $peerforumids = [];
        $courseids = [];

        $this->log_start("Processing post information");

        $start = microtime(true);
        foreach ($posts + $delayedposts as $id => $post) {
            $discussionids[$post->discussion] = true;
            $peerforumids[$post->peerforum] = true;
            $courseids[$post->course] = true;
            $this->add_data_for_post($post);
            $this->posts[$id] = $post;
        }
        foreach ($peergrades as $id => $peergrade) {
            $this->add_data_for_peergrade($peergrade);
        }
        $this->log_finish(sprintf("Processed %s posts", count($this->posts)));

        if (empty($this->posts)) {
            $this->log("No posts found. Returning early.");
            return;
        }

        // Please note, this order is intentional.
        // The peerforum cache makes use of the course.
        $this->log_start("Filling caches");

        $start = microtime(true);
        $this->log_start("Filling course cache", 1);
        $this->fill_course_cache(array_keys($courseids));
        $this->log_finish("Done", 1);

        $this->log_start("Filling peerforum cache", 1);
        $this->fill_peerforum_cache(array_keys($peerforumids));
        $this->log_finish("Done", 1);

        $this->log_start("Filling discussion cache", 1);
        $this->fill_discussion_cache(array_keys($discussionids));
        $this->log_finish("Done", 1);

        $this->log_start("Filling user subscription cache", 1);
        $this->fill_user_subscription_cache();
        $this->log_finish("Done", 1);

        $this->log_start("Filling digest cache", 1);
        $this->fill_digest_cache();
        $this->log_finish("Done", 1);

        $this->log_finish("All caches filled");

        $this->log_start("Filtering delayed posts");
        $this->filter_tasks_to_delay($delayedposts);
        $this->log_finish("Done");

        $this->log_start("Queueing user tasks.");
        $this->queue_user_tasks();
        $this->log_finish("All tasks queued.");
    }

    /**
     * Fill the course cache.
     *
     * @param int[] $courseids
     */
    protected function fill_course_cache($courseids) {
        global $DB;

        list($in, $params) = $DB->get_in_or_equal($courseids);
        $this->courses = $DB->get_records_select('course', "id $in", $params);
    }

    /**
     * Fill the peerforum cache.
     *
     * @param int[] $peerforumids
     */
    protected function fill_peerforum_cache($peerforumids) {
        global $DB;

        $requiredfields = [
                'id',
                'course',
                'forcesubscribe',
                'type',
                'hidereplies',
                'peergradeassessed',
                'peergradescale',
        ];
        list($in, $params) = $DB->get_in_or_equal($peerforumids);
        $this->peerforums = $DB->get_records_select('peerforum', "id $in", $params, '', implode(', ', $requiredfields));
        foreach ($this->peerforums as $id => $peerforum) {
            \mod_peerforum\subscriptions::fill_subscription_cache($id);
            \mod_peerforum\subscriptions::fill_discussion_subscription_cache($id);
        }
    }

    /**
     * Fill the discussion cache.
     *
     * @param int[] $discussionids
     */
    protected function fill_discussion_cache($discussionids) {
        global $DB;

        if (empty($discussionids)) {
            $this->discussion = [];
        } else {

            $requiredfields = [
                    'id',
                    'groupid',
                    'firstpost',
                    'timestart',
                    'timeend',
            ];

            list($in, $params) = $DB->get_in_or_equal($discussionids);
            $this->discussions = $DB->get_records_select(
                    'peerforum_discussions', "id $in", $params, '', implode(', ', $requiredfields));
        }
    }

    /**
     * Fill the cache of user digest preferences.
     */
    protected function fill_digest_cache() {
        global $DB;

        if (empty($this->users)) {
            return;
        }
        // Get the list of peerforum subscriptions for per-user per-peerforum maildigest settings.
        list($in, $params) = $DB->get_in_or_equal(array_keys($this->users));
        $digestspreferences = $DB->get_recordset_select(
                'peerforum_digests', "userid $in", $params, '', 'id, userid, peerforum, maildigest');
        foreach ($digestspreferences as $digestpreference) {
            if (!isset($this->digestusers[$digestpreference->peerforum])) {
                $this->digestusers[$digestpreference->peerforum] = [];
            }
            $this->digestusers[$digestpreference->peerforum][$digestpreference->userid] = $digestpreference->maildigest;
        }
        $digestspreferences->close();
    }

    /**
     * Add dsta for the current peerforum post to the structure of adhoc data.
     *
     * @param \stdClass $post
     */
    protected function add_data_for_post($post) {
        if (!isset($this->adhocdata[$post->course])) {
            $this->adhocdata[$post->course] = [];
        }

        if (!isset($this->adhocdata[$post->course][$post->peerforum])) {
            $this->adhocdata[$post->course][$post->peerforum] = [];
        }

        if (!isset($this->adhocdata[$post->course][$post->peerforum][$post->discussion])) {
            $this->adhocdata[$post->course][$post->peerforum][$post->discussion] = [];
        }

        $this->adhocdata[$post->course][$post->peerforum][$post->discussion][$post->id] = $post->id;
    }

    /**
     * Add dsta for the current peerforum peergrade to the structure of adhoc data.
     *
     * @param \stdClass $peergrade
     */
    protected function add_data_for_peergrade($peergrade) {
        if (!isset($this->adhocpgdata[$peergrade->itemid])) {
            $this->adhocpgdata[$peergrade->itemid] = [];
        }

        $this->adhocpgdata[$peergrade->itemid][$peergrade->userid] = $peergrade->id;
    }

    /**
     * Fill the cache of user subscriptions.
     */
    protected function fill_user_subscription_cache() {
        foreach ($this->peerforums as $peerforum) {
            $cm = get_fast_modinfo($this->courses[$peerforum->course])->instances['peerforum'][$peerforum->id];
            $modcontext = \context_module::instance($cm->id);

            $this->subscribedusers[$peerforum->id] = [];
            if ($users =
                    \mod_peerforum\subscriptions::fetch_subscribed_users($peerforum, 0, $modcontext, 'u.id, u.maildigest', true)) {
                foreach ($users as $user) {
                    // This user is subscribed to this peerforum.
                    $this->subscribedusers[$peerforum->id][$user->id] = $user->id;
                    if (!isset($this->users[$user->id])) {
                        // Store minimal user info.
                        $this->users[$user->id] = $user;
                    }
                }
                // Release memory.
                unset($users);
            }
        }
    }

    /**
     * Remove from queue the user tasks to delay.
     *
     * @param array $delayedposts
     */
    protected function filter_tasks_to_delay($delayedposts = array()) {
        global $DB;
        $newposttodelay = [];
        $counts = [
                'delayed' => 0,
                'released' => 0,
                'continued' => 0,
                'total' => 0,
        ];

        $poststructure = $this->adhocdata;
        cron_setup_user();
        foreach ($poststructure as $courseid => $peerforumids) {
            $course = $this->courses[$courseid];

            foreach ($peerforumids as $peerforumid => $discussionids) {
                $peerforum = $this->peerforums[$peerforumid];
                $cm = get_fast_modinfo($course)->instances['peerforum'][$peerforumid];

                foreach ($discussionids as $discussionid => $postids) {
                    $discussion = $this->discussions[$discussionid];

                    foreach ($postids as $postid) {
                        $post = $this->posts[$postid];
                        $counts['total']++;

                        [$replyhidden, $x] = peerforum_user_can_see_reply($peerforum, $post, null, $cm);
                        if ($replyhidden) {
                            unset($this->adhocdata[$courseid][$peerforumid][$discussionid][$postid]);
                            if (!isset($delayedposts[$postid])) {
                                $newposttodelay[] = [
                                        'postid' => $postid,
                                ];
                                $counts['delayed']++;
                                continue;
                            }
                            $counts['continued']++;
                        } else if (isset($delayedposts[$postid])) {
                            $DB->delete_records('peerforum_delayed_post', [
                                    'postid' => $postid,
                            ]);
                            $counts['released']++;
                        }
                    }
                    if (empty($poststructure[$courseid][$peerforumid][$discussionid])) {
                        unset($poststructure[$courseid][$peerforumid][$discussionid]);
                        continue;
                    }
                }
                if (empty($poststructure[$courseid][$peerforumid])) {
                    unset($poststructure[$courseid][$peerforumid]);
                    continue;
                }
            }
            if (empty($poststructure[$courseid])) {
                unset($poststructure[$courseid]);
                continue;
            }
        }
        $DB->insert_records('peerforum_delayed_post', $newposttodelay);
        $this->log(
                sprintf(
                        "Checked %d posts, %d new now delayed, %d now released and %d continue delayed.",
                        $counts['total'],
                        $counts['delayed'],
                        $counts['released'],
                        $counts['continued'],
                ), 1);
    }

    /**
     * Queue the user tasks.
     */
    protected function queue_user_tasks() {
        global $CFG, $DB;

        $timenow = time();
        $sitetimezone = \core_date::get_server_timezone();
        $counts = [
                'digests' => 0,
                'individuals' => 0,
                'users' => 0,
                'ignored' => 0,
                'messages' => 0,
                'assigns' => 0,
        ];
        $this->log("Processing " . count($this->users) . " users", 1);
        foreach ($this->users as $user) {
            $usercounts = [
                    'digests' => 0,
                    'messages' => 0,
                    'assigns' => 0,
            ];

            $send = false;
            // Setup this user so that the capabilities are cached, and environment matches receiving user.
            cron_setup_user($user);

            list($individualpostdata, $digestpostdata, $assignmentspostdata) = $this->fetch_posts_for_user($user);

            if (!empty($digestpostdata)) {
                // Insert all of the records for the digest.
                $DB->insert_records('peerforum_queue', $digestpostdata);
                $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->digestmailtime * 3600);

                if ($digesttime < $timenow) {
                    // Digest time is in the past. Get a new time for tomorrow.
                    $digesttime = usergetmidnight($timenow + DAYSECS, $sitetimezone) + ($CFG->digestmailtime * 3600);
                }

                $task = new \mod_peerforum\task\send_user_digests();
                $task->set_userid($user->id);
                $task->set_component('mod_peerforum');
                $task->set_next_run_time($digesttime);
                \core\task\manager::reschedule_or_queue_adhoc_task($task);
                $usercounts['digests']++;
                $send = true;
            }

            if (!empty($individualpostdata)) {
                $usercounts['messages'] += count($individualpostdata);

                $task = new \mod_peerforum\task\send_user_notifications();
                $task->set_userid($user->id);
                $task->set_custom_data($individualpostdata);
                $task->set_component('mod_peerforum');
                \core\task\manager::queue_adhoc_task($task);
                $counts['individuals']++;
                $send = true;
            }

            if (!empty($assignmentspostdata)) {
                $usercounts['assigns'] += count($assignmentspostdata);

                $task = new \mod_peerforum\task\send_user_assignments();
                $task->set_userid($user->id);
                $task->set_custom_data($assignmentspostdata);
                $task->set_component('mod_peerforum');
                \core\task\manager::queue_adhoc_task($task);
                $counts['individuals']++;
                $send = true;
            }

            if ($send) {
                $counts['users']++;
                $counts['messages'] += $usercounts['messages'];
                $counts['digests'] += $usercounts['digests'];
                $counts['assigns'] += $usercounts['assigns'];
            } else {
                $counts['ignored']++;
            }

            $this->log(sprintf("Queued %d digests, %d messages and %d assigns for %s",
                    $usercounts['digests'],
                    $usercounts['messages'],
                    $usercounts['assigns'],
                    $user->id
            ), 2);
        }
        $this->log(
                sprintf(
                        "Queued %d digests, %d individual tasks for %d post mails, and %d assigns. " .
                        "Unique users: %d (%d ignored)",
                        $counts['digests'],
                        $counts['individuals'],
                        $counts['messages'],
                        $counts['assigns'],
                        $counts['users'],
                        $counts['ignored']
                ), 1);
    }

    /**
     * Fetch posts for this user.
     *
     * @param \stdClass $user The user to fetch posts for.
     */
    protected function fetch_posts_for_user($user) {
        // We maintain a mapping of user groups for each peerforum.
        $usergroups = [];
        $digeststructure = [];
        $assignmentstosend = [];

        $poststructure = $this->adhocdata;
        $poststosend = [];
        foreach ($poststructure as $courseid => $peerforumids) {
            $course = $this->courses[$courseid];
            foreach ($peerforumids as $peerforumid => $discussionids) {
                $peerforum = $this->peerforums[$peerforumid];
                $maildigest = peerforum_get_user_maildigest_bulk($this->digestusers, $user, $peerforumid);

                if (!isset($this->subscribedusers[$peerforumid][$user->id])) {
                    // This user has no subscription of any kind to this peerforum.
                    // Do not send them any posts at all.
                    unset($poststructure[$courseid][$peerforumid]);
                    continue;
                }

                $subscriptiontime = \mod_peerforum\subscriptions::fetch_discussion_subscription($peerforum->id, $user->id);

                $cm = get_fast_modinfo($course)->instances['peerforum'][$peerforumid];
                foreach ($discussionids as $discussionid => $postids) {
                    $discussion = $this->discussions[$discussionid];
                    if (!\mod_peerforum\subscriptions::is_subscribed($user->id, $peerforum, $discussionid, $cm)) {
                        // The user does not subscribe to this peerforum as a whole, or to this specific discussion.
                        unset($poststructure[$courseid][$peerforumid][$discussionid]);
                        continue;
                    }

                    if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
                        // This discussion has a groupmode set (SEPARATEGROUPS or VISIBLEGROUPS).
                        // Check whether the user can view it based on their groups.
                        if (!isset($usergroups[$peerforum->id])) {
                            $usergroups[$peerforum->id] = groups_get_all_groups($courseid, $user->id, $cm->groupingid);
                        }

                        if (!isset($usergroups[$peerforum->id][$discussion->groupid])) {
                            // This user is not a member of this group, or the group no longer exists.

                            $modcontext = \context_module::instance($cm->id);
                            if (!has_capability('moodle/site:accessallgroups', $modcontext, $user)) {
                                // This user does not have the accessallgroups and is not a member of the group.
                                // Do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS.
                                unset($poststructure[$courseid][$peerforumid][$discussionid]);
                                continue;
                            }
                        }
                    }

                    foreach ($postids as $postid) {
                        $post = $this->posts[$postid];
                        if (isset($this->adhocpgdata[$postid][$user->id])) {
                            $assignmentstosend[] = $this->adhocpgdata[$postid][$user->id];
                        }
                        if ($subscriptiontime) {
                            // Skip posts if the user subscribed to the discussion after it was created.
                            $subscribedafter = isset($subscriptiontime[$post->discussion]);
                            $subscribedafter = $subscribedafter && ($subscriptiontime[$post->discussion] > $post->created);
                            if ($subscribedafter) {
                                // The user subscribed to the discussion/peerforum after this post was created.
                                unset($poststructure[$courseid][$peerforumid][$discussionid][$postid]);
                                continue;
                            }
                        }

                        if ($maildigest > 0) {
                            // This user wants the mails to be in digest form.
                            $digeststructure[] = (object) [
                                    'userid' => $user->id,
                                    'discussionid' => $discussion->id,
                                    'postid' => $post->id,
                                    'timemodified' => $post->created,
                            ];
                            unset($poststructure[$courseid][$peerforumid][$discussionid][$postid]);
                            continue;
                        } else {
                            // Add this post to the list of postids to be sent.
                            $poststosend[] = $postid;
                        }
                    }
                }

                if (empty($poststructure[$courseid][$peerforumid])) {
                    // This user is not subscribed to any discussions in this peerforum at all.
                    unset($poststructure[$courseid][$peerforumid]);
                    continue;
                }
            }
            if (empty($poststructure[$courseid])) {
                // This user is not subscribed to any peerforums in this course.
                unset($poststructure[$courseid]);
            }
        }

        return [$poststosend, $digeststructure, $assignmentstosend];
    }

    /**
     * Returns a list of all new posts that have not been mailed yet
     *
     * @param int $starttime posts created after this time
     * @param int $endtime posts created before this
     * @param int $now used for timed discussions only
     * @return array
     */
    protected function get_unmailed_posts($starttime, $endtime, $now = null) {
        global $CFG, $DB;

        $params = array();
        $params['mailed'] = PEERFORUM_MAILED_PENDING;
        $params['ptimestart'] = $starttime;
        $params['ptimeend'] = $endtime;
        $params['mailnow'] = 1;

        if (!empty($CFG->peerforum_enabletimedposts)) {
            if (empty($now)) {
                $now = time();
            }
            $timedsql = "AND (d.timestart < :dtimestart AND (d.timeend = 0 OR d.timeend > :dtimeend))";
            $params['dtimestart'] = $now;
            $params['dtimeend'] = $now;
        } else {
            $timedsql = "";
        }

        return $DB->get_records_sql(
                "SELECT
                    p.id,
                    p.discussion,
                    d.peerforum,
                    d.course,
                    p.created,
                    p.parent,
                    p.userid
                  FROM {peerforum_posts} p
                  JOIN {peerforum_discussions} d ON d.id = p.discussion
                 WHERE p.mailed = :mailed
                AND p.created >= :ptimestart
                   AND (p.created < :ptimeend OR p.mailnow = :mailnow)
                $timedsql
                 ORDER BY p.modified ASC",
                $params);
    }

    /**
     * Returns a list of all new peergrades for the posts.
     *
     * @param $posts
     * @return array
     */
    protected function get_unmailed_peergrades($posts) {
        global $DB;

        if (empty($posts)) {
            return array();
        }
        list($insql, $params) = $DB->get_in_or_equal(array_keys($posts));

        return $DB->get_records_sql(
                "SELECT p.id, p.userid, p.itemid
                       FROM {peerforum_time_assigned} p
                      WHERE p.itemid $insql",
                $params);
    }

    /**
     * Returns a list of all posts that are delayed.
     *
     * @return array
     */
    protected function get_delayed_posts() {
        global $DB;

        return $DB->get_records_sql(
                "SELECT
                    p.id,
                    p.discussion,
                    d.peerforum,
                    d.course,
                    p.created,
                    p.parent,
                    p.userid
                  FROM {peerforum_delayed_post} l
                  JOIN {peerforum_posts} p ON p.id = l.postid
                  JOIN {peerforum_discussions} d ON d.id = p.discussion
                 ORDER BY p.modified ASC");
    }
}
