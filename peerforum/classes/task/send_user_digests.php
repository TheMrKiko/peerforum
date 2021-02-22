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
 * This file defines an adhoc task to send notifications.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\task;

defined('MOODLE_INTERNAL') || die();

use html_writer;

require_once($CFG->dirroot . '/mod/peerforum/lib.php');

/**
 * Adhoc task to send moodle peerforum digests for the specified user.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_user_digests extends \core\task\adhoc_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var \stdClass   A shortcut to $USER.
     */
    protected $recipient;

    /**
     * @var bool[]  Whether the user can view fullnames for each peerforum.
     */
    protected $viewfullnames = [];

    /**
     * @var bool[]  Whether the user can post in each peerforum.
     */
    protected $canpostto = [];

    /**
     * @var \stdClass[] Courses with posts them.
     */
    protected $courses = [];

    /**
     * @var \stdClass[] PeerForums with posts them.
     */
    protected $peerforums = [];

    /**
     * @var \stdClass[] Discussions with posts them.
     */
    protected $discussions = [];

    /**
     * @var \stdClass[] The posts to be sent.
     */
    protected $posts = [];

    /**
     * @var \stdClass[] The various authors.
     */
    protected $users = [];

    /**
     * @var \stdClass[] A list of any per-peerforum digest preference that this user holds.
     */
    protected $peerforumdigesttypes = [];

    /**
     * @var bool    Whether the user has requested HTML or not.
     */
    protected $allowhtml = true;

    /**
     * @var string  The subject of the message.
     */
    protected $postsubject = '';

    /**
     * @var string  The plaintext content of the whole message.
     */
    protected $notificationtext = '';

    /**
     * @var string  The HTML content of the whole message.
     */
    protected $notificationhtml = '';

    /**
     * @var string  The plaintext content for the current discussion being processed.
     */
    protected $discussiontext = '';

    /**
     * @var string  The HTML content for the current discussion being processed.
     */
    protected $discussionhtml = '';

    /**
     * @var int     The number of messages sent in this digest.
     */
    protected $sentcount = 0;

    /**
     * @var \renderer[][] A cache of the different types of renderer, stored both by target (HTML, or Text), and type.
     */
    protected $renderers = [
            'html' => [],
            'text' => [],
    ];

    /**
     * @var int[] A list of post IDs to be marked as read for this user.
     */
    protected $markpostsasread = [];

    /**
     * Send out messages.
     */
    public function execute() {
        $starttime = time();

        $this->recipient = \core_user::get_user($this->get_userid());
        $this->log_start("Sending peerforum digests for {$this->recipient->username} ({$this->recipient->id})");

        if (empty($this->recipient->mailformat) || $this->recipient->mailformat != 1) {
            // This user does not want to receive HTML.
            $this->allowhtml = false;
        }

        // Fetch all of the data we need to mail these posts.
        $this->prepare_data($starttime);

        if (empty($this->posts) || empty($this->discussions) || empty($this->peerforums)) {
            $this->log_finish("No messages found to send.");
            return;
        }

        // Add the message headers.
        $this->add_message_header();

        foreach ($this->discussions as $discussion) {
            // Raise the time limit for each discussion.
            \core_php_time_limit::raise(120);

            // Grab the data pertaining to this discussion.
            $peerforum = $this->peerforums[$discussion->peerforum];
            $course = $this->courses[$peerforum->course];
            $cm = get_fast_modinfo($course)->instances['peerforum'][$peerforum->id];
            $modcontext = \context_module::instance($cm->id);
            $coursecontext = \context_course::instance($course->id);

            if (empty($this->posts[$discussion->id])) {
                // Somehow there are no posts.
                // This should not happen but better safe than sorry.
                continue;
            }

            if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                // The course is hidden and the user does not have access to it.
                // Permissions may have changed since it was queued.
                continue;
            }

            if (!peerforum_user_can_see_discussion($peerforum, $discussion, $modcontext, $this->recipient)) {
                // User cannot see this discussion.
                // Permissions may have changed since it was queued.
                continue;
            }

            if (!\mod_peerforum\subscriptions::is_subscribed($this->recipient->id, $peerforum, $discussion->id, $cm)) {
                // The user does not subscribe to this peerforum as a whole, or to this specific discussion.
                continue;
            }

            // Fetch additional values relating to this peerforum.
            if (!isset($this->canpostto[$discussion->id])) {
                $this->canpostto[$discussion->id] = peerforum_user_can_post(
                        $peerforum, $discussion, $this->recipient, $cm, $course, $modcontext);
            }

            if (!isset($this->viewfullnames[$peerforum->id])) {
                $this->viewfullnames[$peerforum->id] =
                        has_capability('moodle/site:viewfullnames', $modcontext, $this->recipient->id);
            }

            // Set the discussion storage values.
            $discussionpostcount = 0;
            $this->discussiontext = '';
            $this->discussionhtml = '';

            // Add the header for this discussion.
            $this->add_discussion_header($discussion, $peerforum, $course);
            $this->log_start("Adding messages in discussion {$discussion->id} (peerforum {$peerforum->id})", 1);

            // Add all posts in this peerforum.
            foreach ($this->posts[$discussion->id] as $post) {
                $author = $this->get_post_author($post->userid, $course, $peerforum, $cm, $modcontext);
                if (empty($author)) {
                    // Unable to find the author. Skip to avoid errors.
                    continue;
                }

                if (!peerforum_user_can_see_post($peerforum, $discussion, $post, $this->recipient, $cm)) {
                    // User cannot see this post.
                    // Permissions may have changed since it was queued.
                    continue;
                }

                $this->add_post_body($author, $post, $discussion, $peerforum, $cm, $course);
                $discussionpostcount++;
            }

            // Add the peerforum footer.
            $this->add_discussion_footer($discussion, $peerforum, $course);

            // Add the data for this discussion to the notification body.
            if ($discussionpostcount) {
                $this->sentcount += $discussionpostcount;
                $this->notificationtext .= $this->discussiontext;
                $this->notificationhtml .= $this->discussionhtml;
                $this->log_finish("Added {$discussionpostcount} messages to discussion {$discussion->id}", 1);
            } else {
                $this->log_finish("No messages found in discussion {$discussion->id} - skipped.", 1);
            }
        }

        if ($this->sentcount) {
            // This digest has at least one post and should therefore be sent.
            if ($this->send_mail()) {
                $this->log_finish("Digest sent with {$this->sentcount} messages.");
                if (get_user_preferences('forum_markasreadonnotification', 1, $this->recipient->id) == 1) {
                    peerforum_tp_mark_posts_read($this->recipient, $this->markpostsasread);
                }
            } else {
                $this->log_finish("Issue sending digest. Skipping.");
            }
        } else {
            $this->log_finish("No messages found to send.");
        }

        // We have finishied all digest emails, update $CFG->digestmailtimelast.
        set_config('digestmailtimelast', $starttime);
    }

    /**
     * Prepare the data for this run.
     *
     * Note: This will also remove posts from the queue.
     *
     * @param int $timenow
     */
    protected function prepare_data(int $timenow) {
        global $DB;

        $sql = "SELECT p.*, f.id AS peerforum, f.course
                  FROM {peerforum_queue} q
            INNER JOIN {peerforum_posts} p ON p.id = q.postid
            INNER JOIN {peerforum_discussions} d ON d.id = p.discussion
            INNER JOIN {peerforum} f ON f.id = d.peerforum
                 WHERE q.userid = :userid
                   AND q.timemodified < :timemodified
              ORDER BY d.id, q.timemodified ASC";

        $queueparams = [
                'userid' => $this->recipient->id,
                'timemodified' => $timenow,
        ];

        $posts = $DB->get_recordset_sql($sql, $queueparams);
        $discussionids = [];
        $peerforumids = [];
        $courseids = [];
        $userids = [];
        foreach ($posts as $post) {
            $discussionids[] = $post->discussion;
            $peerforumids[] = $post->peerforum;
            $courseids[] = $post->course;
            $userids[] = $post->userid;
            unset($post->peerforum);
            if (!isset($this->posts[$post->discussion])) {
                $this->posts[$post->discussion] = [];
            }
            $this->posts[$post->discussion][$post->id] = $post;
        }
        $posts->close();

        if (empty($discussionids)) {
            // All posts have been removed since the task was queued.
            $this->empty_queue($this->recipient->id, $timenow);
            return;
        }

        list($in, $params) = $DB->get_in_or_equal($discussionids);
        $this->discussions = $DB->get_records_select('peerforum_discussions', "id {$in}", $params);

        list($in, $params) = $DB->get_in_or_equal($peerforumids);
        $this->peerforums = $DB->get_records_select('peerforum', "id {$in}", $params);

        list($in, $params) = $DB->get_in_or_equal($courseids);
        $this->courses = $DB->get_records_select('course', "id $in", $params);

        list($in, $params) = $DB->get_in_or_equal($userids);
        $this->users = $DB->get_records_select('user', "id $in", $params);

        $this->fill_digest_cache();

        $this->empty_queue($this->recipient->id, $timenow);
    }

    /**
     * Empty the queue of posts for this user.
     *
     * @param int $userid user id which queue elements are going to be removed.
     * @param int $timemodified up time limit of the queue elements to be removed.
     */
    protected function empty_queue(int $userid, int $timemodified): void {
        global $DB;

        $DB->delete_records_select('peerforum_queue', "userid = :userid AND timemodified < :timemodified", [
                'userid' => $userid,
                'timemodified' => $timemodified,
        ]);
    }

    /**
     * Fill the cron digest cache.
     */
    protected function fill_digest_cache() {
        global $DB;

        $this->peerforumdigesttypes = $DB->get_records_menu('peerforum_digests', [
                'userid' => $this->recipient->id,
        ], '', 'peerforum, maildigest');
    }

    /**
     * Fetch and initialise the post author.
     *
     * @param int $userid The id of the user to fetch
     * @param \stdClass $course
     * @param \stdClass $peerforum
     * @param \stdClass $cm
     * @param \context $context
     * @return  \stdClass
     */
    protected function get_post_author($userid, $course, $peerforum, $cm, $context) {
        if (!isset($this->users[$userid])) {
            // This user no longer exists.
            return false;
        }

        $user = $this->users[$userid];

        if (!isset($user->groups)) {
            // Initialise the groups list.
            $user->groups = [];
        }

        if (!isset($user->groups[$peerforum->id])) {
            $user->groups[$peerforum->id] = groups_get_all_groups($course->id, $user->id, $cm->groupingid);
        }

        // Clone the user object to prevent leaks between messages.
        return (object) (array) $user;
    }

    /**
     * Add the header to this message.
     */
    protected function add_message_header() {
        $site = get_site();

        // Set the subject of the message.
        $this->postsubject = get_string('digestmailsubject', 'peerforum', format_string($site->shortname, true));

        // And the content of the header in body.
        $headerdata = (object) [
                'sitename' => format_string($site->fullname, true),
                'userprefs' => (new \moodle_url('/user/peerforum.php', [
                        'id' => $this->recipient->id,
                        'course' => $site->id,
                ]))->out(false),
        ];

        $this->notificationtext .= get_string('digestmailheader', 'peerforum', $headerdata) . "\n";

        if ($this->allowhtml) {
            $headerdata->userprefs = html_writer::link($headerdata->userprefs, get_string('digestmailprefs', 'peerforum'), [
                    'target' => '_blank',
            ]);

            $this->notificationhtml .= html_writer::tag('p', get_string('digestmailheader', 'peerforum', $headerdata));
            $this->notificationhtml .= html_writer::empty_tag('br');
            $this->notificationhtml .= html_writer::empty_tag('hr', [
                    'size' => 1,
                    'noshade' => 'noshade',
            ]);
        }
    }

    /**
     * Add the header for this discussion.
     *
     * @param \stdClass $discussion The discussion to add the footer for
     * @param \stdClass $peerforum The peerforum that the discussion belongs to
     * @param \stdClass $course The course that the peerforum belongs to
     */
    protected function add_discussion_header($discussion, $peerforum, $course) {
        global $CFG;

        $shortname = format_string($course->shortname, true, [
                'context' => \context_course::instance($course->id),
        ]);

        $strpeerforums = get_string('peerforums', 'peerforum');

        $this->discussiontext .= "\n=====================================================================\n\n";
        $this->discussiontext .= "$shortname -> $strpeerforums -> " . format_string($peerforum->name, true);
        if ($discussion->name != $peerforum->name) {
            $this->discussiontext .= " -> " . format_string($discussion->name, true);
        }
        $this->discussiontext .= "\n";
        $this->discussiontext .= new \moodle_url('/mod/peerforum/discuss.php', [
                'd' => $discussion->id,
        ]);
        $this->discussiontext .= "\n";

        if ($this->allowhtml) {
            $this->discussionhtml .= "<p><font face=\"sans-serif\">" .
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$shortname</a> -> " .
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/peerforum/index.php?id=$course->id\">$strpeerforums</a> -> " .
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/peerforum/view.php?f=$peerforum->id\">" .
                    format_string($peerforum->name, true) . "</a>";
            if ($discussion->name == $peerforum->name) {
                $this->discussionhtml .= "</font></p>";
            } else {
                $this->discussionhtml .=
                        " -> <a target=\"_blank\" href=\"$CFG->wwwroot/mod/peerforum/discuss.php?d=$discussion->id\">" .
                        format_string($discussion->name, true) . "</a></font></p>";
            }
            $this->discussionhtml .= '<p>';
        }

    }

    /**
     * Add the body of this post.
     *
     * @param \stdClass $author The author of the post
     * @param \stdClass $post The post being sent
     * @param \stdClass $discussion The discussion that the post is in
     * @param \stdClass $peerforum The peerforum that the discussion belongs to
     * @param \cminfo $cm The cminfo object for the peerforum
     * @param \stdClass $course The course that the peerforum belongs to
     */
    protected function add_post_body($author, $post, $discussion, $peerforum, $cm, $course) {
        global $CFG;

        $canreply = $this->canpostto[$discussion->id];

        $data = new \mod_peerforum\output\peerforum_post_email(
                $course,
                $cm,
                $peerforum,
                $discussion,
                $post,
                $author,
                $this->recipient,
                $canreply
        );

        // Override the viewfullnames value.
        $data->viewfullnames = $this->viewfullnames[$peerforum->id];

        // Determine the type of digest being sent.
        $maildigest = $this->get_maildigest($peerforum->id);

        $textrenderer = $this->get_renderer($maildigest);
        $this->discussiontext .= $textrenderer->render($data);
        $this->discussiontext .= "\n";
        if ($this->allowhtml) {
            $htmlrenderer = $this->get_renderer($maildigest, true);
            $this->discussionhtml .= $htmlrenderer->render($data);
            $this->log("Adding post {$post->id} in format {$maildigest} with HTML", 2);
        } else {
            $this->log("Adding post {$post->id} in format {$maildigest} without HTML", 2);
        }

        if ($maildigest == 1 && !$CFG->peerforum_usermarksread) {
            // Create an array of postid's for this user to mark as read.
            $this->markpostsasread[] = $post->id;
        }

    }

    /**
     * Add the footer for this discussion.
     *
     * @param \stdClass $discussion The discussion to add the footer for
     */
    protected function add_discussion_footer($discussion) {
        global $CFG;

        if ($this->allowhtml) {
            $footerlinks = [];

            $peerforum = $this->peerforums[$discussion->peerforum];
            if (\mod_peerforum\subscriptions::is_forcesubscribed($peerforum)) {
                // This peerforum is force subscribed. The user cannot unsubscribe.
                $footerlinks[] = get_string("everyoneissubscribed", "peerforum");
            } else {
                $footerlinks[] = "<a href=\"$CFG->wwwroot/mod/peerforum/subscribe.php?id=$peerforum->id\">" .
                        get_string("unsubscribe", "peerforum") . "</a>";
            }
            $footerlinks[] = "<a href='{$CFG->wwwroot}/mod/peerforum/index.php?id={$peerforum->course}'>" .
                    get_string("digestmailpost", "peerforum") . '</a>';

            $this->discussionhtml .= "\n<div class='mdl-right'><font size=\"1\">" .
                    implode('&nbsp;', $footerlinks) . '</font></div>';
            $this->discussionhtml .= '<hr size="1" noshade="noshade" /></p>';
        }
    }

    /**
     * Get the peerforum digest type for the specified peerforum, failing back to
     * the default setting for the current user if not specified.
     *
     * @param int $peerforumid
     * @return  int
     */
    protected function get_maildigest($peerforumid) {
        $maildigest = -1;

        if (isset($this->peerforumdigesttypes[$peerforumid])) {
            $maildigest = $this->peerforumdigesttypes[$peerforumid];
        }

        if ($maildigest === -1 && !empty($this->recipient->maildigest)) {
            $maildigest = $this->recipient->maildigest;
        }

        if ($maildigest === -1) {
            // There is no maildigest type right now.
            $maildigest = 1;
        }

        return $maildigest;
    }

    /**
     * Send the composed message to the user.
     */
    protected function send_mail() {
        // Headers to help prevent auto-responders.
        $userfrom = \core_user::get_noreply_user();
        $userfrom->customheaders = array(
                "Precedence: Bulk",
                'X-Auto-Response-Suppress: All',
                'Auto-Submitted: auto-generated',
        );

        $eventdata = new \core\message\message();
        $eventdata->courseid = SITEID;
        $eventdata->component = 'mod_peerforum';
        $eventdata->name = 'digests';
        $eventdata->userfrom = $userfrom;
        $eventdata->userto = $this->recipient;
        $eventdata->subject = $this->postsubject;
        $eventdata->fullmessage = $this->notificationtext;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = $this->notificationhtml;
        $eventdata->notification = 1;
        $eventdata->smallmessage = get_string('smallmessagedigest', 'peerforum', $this->sentcount);

        return message_send($eventdata);
    }

    /**
     * Helper to fetch the required renderer, instantiating as required.
     *
     * @param int $maildigest The type of mail digest being sent
     * @param bool $html Whether to fetch the HTML renderer
     * @return  \core_renderer
     */
    protected function get_renderer($maildigest, $html = false) {
        global $PAGE;

        $type = $maildigest == 2 ? 'emaildigestbasic' : 'emaildigestfull';
        $target = $html ? 'htmlemail' : 'textemail';

        if (!isset($this->renderers[$target][$type])) {
            $this->renderers[$target][$type] = $PAGE->get_renderer('mod_peerforum', $type, $target);
        }

        return $this->renderers[$target][$type];
    }
}
