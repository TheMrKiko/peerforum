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

/**
 * Adhoc task to send user peerforum notifications for assignments.
 *
 * @package    mod_peerforum
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_user_assignments extends \core\task\adhoc_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var \stdClass   A shortcut to $USER.
     */
    protected $recipient;

    /**
     * @var \stdClass[] List of courses the messages are in, indexed by courseid.
     */
    protected $courses = [];

    /**
     * @var \stdClass[] List of peerforums the messages are in, indexed by courseid.
     */
    protected $peerforums = [];

    /**
     * @var int[] List of IDs for peerforums in each course.
     */
    protected $coursepeerforums = [];

    /**
     * @var \stdClass[] List of discussions the messages are in, indexed by peerforumid.
     */
    protected $discussions = [];

    /**
     * @var \stdClass[] List of IDs for discussions in each peerforum.
     */
    protected $peerforumdiscussions = [];

    /**
     * @var \stdClass[] List of posts the messages are in, indexed by discussionid.
     */
    protected $posts = [];

    /**
     * Send out messages.
     */
    public function execute() {
        // Raise the time limit for each discussion.
        \core_php_time_limit::raise(120);

        $this->recipient = \core_user::get_user($this->get_userid());

        $data = $this->get_custom_data();

        $this->prepare_data((array) $data);

        $errorcount = 0;
        $sentcount = 0;
        $this->log_start("Sending messages to {$this->recipient->username} ({$this->recipient->id})");
        foreach ($this->courses as $course) {
            foreach ($this->coursepeerforums[$course->id] as $peerforumid) {
                $peerforum = $this->peerforums[$peerforumid];

                $cm = get_fast_modinfo($course)->instances['peerforum'][$peerforumid];
                $modcontext = \context_module::instance($cm->id);

                foreach (array_values($this->peerforumdiscussions[$peerforumid]) as $discussionid) {
                    $discussion = $this->discussions[$discussionid];

                    foreach ($this->posts[$discussionid] as $post) {

                        if ($this->send_assign($course, $peerforum, $discussion, $post, $cm, $modcontext)) {
                            $this->log("Assign of post {$post->id} sent", 1);
                            $sentcount++;
                        } else {
                            $this->log("Failed to send post assign of {$post->id}", 1);
                            $errorcount++;
                        }
                    }
                }
            }
        }

        $this->log_finish("Sent {$sentcount} messages with {$errorcount} failures");
    }

    /**
     * Prepare all data for this run.
     *
     * Take all post ids, and fetch the relevant authors, discussions, peerforums, and courses for them.
     *
     * @param int[] $pgids The list of post IDs
     */
    protected function prepare_data(array $pgids) {
        global $DB;

        if (empty($pgids)) {
            return;
        }

        list($in, $params) = $DB->get_in_or_equal(array_values($pgids));
        $sql = "SELECT p.*, f.id AS peerforum, f.course
                  FROM {peerforum_time_assigned} t
            INNER JOIN {peerforum_posts} p ON t.itemid = p.id
            INNER JOIN {peerforum_discussions} d ON d.id = p.discussion
            INNER JOIN {peerforum} f ON f.id = d.peerforum
                 WHERE t.id {$in}";

        $posts = $DB->get_recordset_sql($sql, $params);
        $discussionids = [];
        $peerforumids = [];
        $courseids = [];
        foreach ($posts as $post) {
            $discussionids[] = $post->discussion;
            $peerforumids[] = $post->peerforum;
            $courseids[] = $post->course;
            unset($post->peerforum);
            if (!isset($this->posts[$post->discussion])) {
                $this->posts[$post->discussion] = [];
            }
            $this->posts[$post->discussion][$post->id] = $post;
        }
        $posts->close();

        if (empty($discussionids)) {
            // All posts have been removed since the task was queued.
            return;
        }

        // Fetch all discussions.
        list($in, $params) = $DB->get_in_or_equal(array_values($discussionids));
        $this->discussions = $DB->get_records_select('peerforum_discussions', "id {$in}", $params);
        foreach ($this->discussions as $discussion) {
            if (empty($this->peerforumdiscussions[$discussion->peerforum])) {
                $this->peerforumdiscussions[$discussion->peerforum] = [];
            }
            $this->peerforumdiscussions[$discussion->peerforum][] = $discussion->id;
        }

        // Fetch all peerforums.
        list($in, $params) = $DB->get_in_or_equal(array_values($peerforumids));
        $this->peerforums = $DB->get_records_select('peerforum', "id {$in}", $params);
        foreach ($this->peerforums as $peerforum) {
            if (empty($this->coursepeerforums[$peerforum->course])) {
                $this->coursepeerforums[$peerforum->course] = [];
            }
            $this->coursepeerforums[$peerforum->course][] = $peerforum->id;
        }

        // Fetch all courses.
        list($in, $params) = $DB->get_in_or_equal(array_values($courseids));
        $this->courses = $DB->get_records_select('course', "id $in", $params);
    }

    /**
     * Send the specified assign for the current user.
     *
     * @param \stdClass $course
     * @param \stdClass $peerforum
     * @param \stdClass $discussion
     * @param \stdClass $post
     * @param \stdClass $cm
     * @param \context $context
     */
    protected function send_assign($course, $peerforum, $discussion, $post, $cm, $context) {
        $message = get_string('peergradenotifmessage', 'peerforum', (object) [
                'user' => fullname($post->userid),
                'peerforumname' => format_string($peerforum->name, true) . ": " . $discussion->name,
        ]);
        $contexturl = new \moodle_url('/mod/peerforum/discuss.php', ['d' => $discussion->id], "p{$post->id}");

        $eventdata = new \core\message\message();
        $eventdata->courseid = $course->id;
        $eventdata->component = 'mod_peerforum';
        $eventdata->name = 'peergradeassigns';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $this->recipient;
        $eventdata->subject = 'You have a new post to peer grade!';
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $message;
        $eventdata->notification = 1;
        $eventdata->smallmessage = $message;
        $eventdata->contexturl = $contexturl->out();
        $eventdata->contexturlname = 'See post';

        return message_send($eventdata);
    }
}
