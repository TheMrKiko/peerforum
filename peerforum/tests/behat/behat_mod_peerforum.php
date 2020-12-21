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
 * Steps definitions related with the peerforum activity.
 *
 * @package    mod_peerforum
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * PeerForum-related steps definitions.
 *
 * @package    mod_peerforum
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_peerforum extends behat_base {

    /**
     * Adds a topic to the peerforum specified by it's name. Useful for the Announcements and blog-style peerforums.
     *
     * @Given /^I add a new topic to "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum with:$/
     * @param string $peerforumname
     * @param TableNode $table
     */
    public function i_add_a_new_topic_to_peerforum_with($peerforumname, TableNode $table) {
        $this->add_new_discussion($peerforumname, $table, get_string('addanewtopic', 'peerforum'));
    }

    /**
     * Adds a Q&A discussion to the Q&A-type peerforum specified by it's name with the provided table data.
     *
     * @Given /^I add a new question to "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum with:$/
     * @param string $peerforumname
     * @param TableNode $table
     */
    public function i_add_a_new_question_to_peerforum_with($peerforumname, TableNode $table) {
        $this->add_new_discussion($peerforumname, $table, get_string('addanewquestion', 'peerforum'));
    }

    /**
     * Adds a discussion to the peerforum specified by it's name with the provided table data (usually Subject and Message). The
     * step begins from the peerforum's course page.
     *
     * @Given /^I add a new discussion to "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum with:$/
     * @param string $peerforumname
     * @param TableNode $table
     */
    public function i_add_a_peerforum_discussion_to_peerforum_with($peerforumname, TableNode $table) {
        $this->add_new_discussion($peerforumname, $table, get_string('addanewdiscussion', 'peerforum'));
    }

    /**
     * Adds a discussion to the peerforum specified by it's name with the provided table data (usually Subject and Message).
     * The step begins from the peerforum's course page.
     *
     * @Given /^I add a new discussion to "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum inline with:$/
     * @param string $peerforumname
     * @param TableNode $table
     */
    public function i_add_a_peerforum_discussion_to_peerforum_inline_with($peerforumname, TableNode $table) {
        $this->add_new_discussion_inline($peerforumname, $table, get_string('addanewdiscussion', 'peerforum'));
    }

    /**
     * Adds a reply to the specified post of the specified peerforum. The step begins from the peerforum's page or from the
     * peerforum's course page.
     *
     * @Given /^I reply "(?P<post_subject_string>(?:[^"]|\\")*)" post from "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum
     *         with:$/
     *
     * @param string $postname The subject of the post
     * @param string $peerforumname The peerforum name
     * @param TableNode $table
     */
    public function i_reply_post_from_peerforum_with($postsubject, $peerforumname, TableNode $table) {

        // Navigate to peerforum.
        $this->goto_main_post_reply($postsubject);

        // Fill form and post.
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', $table);

        $this->execute('behat_forms::press_button', get_string('posttopeerforum', 'peerforum'));
        $this->execute('behat_general::i_wait_to_be_redirected');
    }

    /**
     * Inpage Reply - adds a reply to the specified post of the specified peerforum. The step begins from the peerforum's page or
     * from the peerforum's course page.
     *
     * @Given /^I reply "(?P<post_subject_string>(?:[^"]|\\")*)" post from "(?P<peerforum_name_string>(?:[^"]|\\")*)" peerforum
     *         using an inpage reply with:$/
     *
     * @param string $postsubject The subject of the post
     * @param string $peerforumname The peerforum name
     * @param TableNode $table
     */
    public function i_reply_post_from_peerforum_using_an_inpage_reply_with($postsubject, $peerforumname, TableNode $table) {

        // Navigate to peerforum.
        $this->execute('behat_general::click_link', $this->escape($peerforumname));
        $this->execute('behat_general::click_link', $this->escape($postsubject));
        $this->execute('behat_general::click_link', get_string('reply', 'peerforum'));

        // Fill form and post.
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', $table);

        $this->execute('behat_forms::press_button', get_string('posttopeerforum', 'mod_peerforum'));
    }

    /**
     * Navigates to a particular discussion page
     *
     * @Given /^I navigate to post "(?P<post_subject_string>(?:[^"]|\\")*)" in "(?P<peerforum_name_string>(?:[^"]|\\")*)"
     *         peerforum$/
     *
     * @param string $postsubject The subject of the post
     * @param string $peerforumname The peerforum name
     */
    public function i_navigate_to_post_in_peerforum($postsubject, $peerforumname) {

        // Navigate to peerforum discussion.
        $this->execute('behat_general::click_link', $this->escape($peerforumname));
        $this->execute('behat_general::click_link', $this->escape($postsubject));
    }

    /**
     * Opens up the action menu for the discussion
     *
     * @Given /^I click on "(?P<post_subject_string>(?:[^"]|\\")*)" action menu$/
     * @param string $discussion The subject of the discussion
     */
    public function i_click_on_action_menu($discussion) {
        $this->execute('behat_general::i_click_on_in_the', [
                "[data-container='discussion-tools'] [data-toggle='dropdown']", "css_element",
                "//tr[contains(concat(' ', normalize-space(@class), ' '), ' discussion ') and contains(.,'$discussion')]",
                "xpath_element"
        ]);
    }

    /**
     * Creates new discussions within peerforums of a given course.
     *
     * @Given the following peerforum discussions exist in course :coursename:
     * @param string $coursename The full name of the course where the peerforums exist.
     * @param TableNode $discussionsdata The discussion posts to be created.
     */
    public function the_following_peerforum_discussions_exist(string $coursename, TableNode $discussionsdata) {
        global $DB;

        $courseid = $this->get_course_id($coursename);
        $peerforumgenerator = behat_util::get_data_generator()->get_plugin_generator('mod_peerforum');

        // Add the discussions to the relevant peerforum.
        foreach ($discussionsdata->getHash() as $discussioninfo) {
            $discussioninfo['course'] = $courseid;
            $discussioninfo['peerforum'] = $this->get_peerforum_id($courseid, $discussioninfo['peerforum']);
            $discussioninfo['userid'] = $this->get_user_id($discussioninfo['user']);

            // Prepare data for any attachments.
            if (!empty($discussioninfo['attachments']) || !empty($discussioninfo['inlineattachments'])) {
                $discussioninfo['attachment'] = 1;
                $cm = get_coursemodule_from_instance('peerforum', $discussioninfo['peerforum']);
            }

            // Prepare data for groups if needed.
            if (!empty($discussioninfo['group'])) {
                $discussioninfo['groupid'] = $this->get_group_id($courseid, $discussioninfo['group']);
                unset($discussioninfo['group']);
            }

            // Create the discussion post.
            $discussion = $peerforumgenerator->create_discussion($discussioninfo);
            $postid = $DB->get_field('peerforum_posts', 'id', ['discussion' => $discussion->id]);

            // Override the creation and modified timestamps as required.
            if (!empty($discussioninfo['created']) || !empty($discussioninfo['modified'])) {
                $discussiondata = [
                        'id' => $discussion->id,
                        'timemodified' => empty($discussioninfo['modified']) ? $discussioninfo['created'] :
                                $discussioninfo['modified'],
                ];

                $DB->update_record('peerforum_discussions', $discussiondata);

                $postdata = [
                        'id' => $postid,
                        'modified' => empty($discussioninfo['modified']) ? $discussioninfo['created'] : $discussioninfo['modified'],
                ];

                if (!empty($discussioninfo['created'])) {
                    $postdata['created'] = $discussioninfo['created'];
                }

                $DB->update_record('peerforum_posts', $postdata);
            }

            // Create attachments to the discussion post if required.
            if (!empty($discussioninfo['attachments'])) {
                $attachments = array_map('trim', explode(',', $discussioninfo['attachments']));
                $this->create_post_attachments($postid, $discussioninfo['userid'], $attachments, $cm, 'attachment');
            }

            // Create inline attachments to the discussion post if required.
            if (!empty($discussioninfo['inlineattachments'])) {
                $inlineattachments = array_map('trim', explode(',', $discussioninfo['inlineattachments']));
                $this->create_post_attachments($postid, $discussioninfo['userid'], $inlineattachments, $cm, 'post');
            }
        }
    }

    /**
     * Creates replies to discussions within peerforums of a given course.
     *
     * @Given the following peerforum replies exist in course :coursename:
     * @param string $coursename The full name of the course where the peerforums exist.
     * @param TableNode $repliesdata The reply posts to be created.
     */
    public function the_following_peerforum_replies_exist(string $coursename, TableNode $repliesdata) {
        global $DB;

        $courseid = $this->get_course_id($coursename);
        $peerforumgenerator = behat_util::get_data_generator()->get_plugin_generator('mod_peerforum');

        // Add the replies to the relevant discussions.
        foreach ($repliesdata->getHash() as $replyinfo) {
            $replyinfo['course'] = $courseid;
            $replyinfo['peerforum'] = $this->get_peerforum_id($courseid, $replyinfo['peerforum']);
            $replyinfo['userid'] = $this->get_user_id($replyinfo['user']);

            [
                    'discussionid' => $replyinfo['discussion'],
                    'parentid' => $replyinfo['parent'],
            ] = $this->get_base_discussion($replyinfo['peerforum'], $replyinfo['discussion']);

            // Prepare data for any attachments.
            if (!empty($replyinfo['attachments']) || !empty($replyinfo['inlineattachments'])) {
                $replyinfo['attachment'] = 1;
                $cm = get_coursemodule_from_instance('peerforum', $replyinfo['peerforum']);
            }

            // Create the reply post.
            $reply = $peerforumgenerator->create_post($replyinfo);

            // Create attachments to the post if required.
            if (!empty($replyinfo['attachments'])) {
                $attachments = array_map('trim', explode(',', $replyinfo['attachments']));
                $this->create_post_attachments($reply->id, $replyinfo['userid'], $attachments, $cm, 'attachment');
            }

            // Create inline attachments to the post if required.
            if (!empty($replyinfo['inlineattachments'])) {
                $inlineattachments = array_map('trim', explode(',', $replyinfo['inlineattachments']));
                $this->create_post_attachments($reply->id, $replyinfo['userid'], $inlineattachments, $cm, 'post');
            }
        }
    }

    /**
     * Checks if the user can subscribe to the peerforum.
     *
     * @Given /^I can subscribe to this peerforum$/
     */
    public function i_can_subscribe_to_this_peerforum() {
        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }

        $this->execute('behat_general::assert_page_contains_text', [get_string('subscribe', 'mod_peerforum')]);

        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }
    }

    /**
     * Checks if the user can unsubscribe from the peerforum.
     *
     * @Given /^I can unsubscribe from this peerforum$/
     */
    public function i_can_unsubscribe_from_this_peerforum() {
        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }

        $this->execute('behat_general::assert_page_contains_text', [get_string('unsubscribe', 'mod_peerforum')]);

        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }
    }

    /**
     * Subscribes to the peerforum.
     *
     * @Given /^I subscribe to this peerforum$/
     */
    public function i_subscribe_to_this_peerforum() {
        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }

        $this->execute('behat_general::click_link', [get_string('subscribe', 'mod_peerforum')]);
    }

    /**
     * Unsubscribes from the peerforum.
     *
     * @Given /^I unsubscribe from this peerforum$/
     */
    public function i_unsubscribe_from_this_peerforum() {
        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [get_string('actionsmenu'), 'link']);
        }

        $this->execute('behat_general::click_link', [get_string('unsubscribe', 'mod_peerforum')]);
    }

    /**
     * Fetch user ID from its username.
     *
     * @param string $username The username.
     * @return int The user ID.
     * @throws Exception
     */
    protected function get_user_id($username) {
        global $DB;

        if (!$userid = $DB->get_field('user', 'id', ['username' => $username])) {
            throw new Exception("A user with username '{$username}' does not exist");
        }
        return $userid;
    }

    /**
     * Fetch course ID using course name.
     *
     * @param string $coursename The name of the course.
     * @return int The course ID.
     * @throws Exception
     */
    protected function get_course_id(string $coursename): int {
        global $DB;

        if (!$courseid = $DB->get_field('course', 'id', ['fullname' => $coursename])) {
            throw new Exception("A course with name '{$coursename}' does not exist");
        }

        return $courseid;
    }

    /**
     * Fetch peerforum ID using peerforum name.
     *
     * @param int $courseid The course ID the peerforum exists within.
     * @param string $peerforumname The name of the peerforum.
     * @return int The peerforum ID.
     * @throws Exception
     */
    protected function get_peerforum_id(int $courseid, string $peerforumname): int {
        global $DB;

        $conditions = [
                'course' => $courseid,
                'name' => $peerforumname,
        ];

        if (!$peerforumid = $DB->get_field('peerforum', 'id', $conditions)) {
            throw new Exception("A peerforum with name '{$peerforumname}' does not exist in the provided course");
        }

        return $peerforumid;
    }

    /**
     * Fetch Group ID using group name.
     *
     * @param int $courseid The course ID the peerforum exists within.
     * @param string $groupname The short name of the group.
     * @return int The group ID.
     * @throws Exception
     */
    protected function get_group_id(int $courseid, string $groupname): int {
        global $DB;

        if ($groupname === 'All participants') {
            return -1;
        }

        $conditions = [
                'courseid' => $courseid,
                'idnumber' => $groupname,
        ];

        if (!$groupid = $DB->get_field('groups', 'id', $conditions)) {
            throw new Exception("A group with name '{$groupname}' does not exist in the provided course");
        }

        return $groupid;
    }

    /**
     * Fetch discussion ID and first post ID by discussion name.
     *
     * @param int $peerforumid The peerforum ID where the discussion resides.
     * @param string $name The name of the discussion.
     * @return array The discussion ID and first post ID.
     * @throws dml_exception If the discussion name is not unique within the peerforum (or doesn't exist).
     */
    protected function get_base_discussion(int $peerforumid, string $name): array {
        global $DB;

        $conditions = [
                'name' => $name,
                'peerforum' => $peerforumid,
        ];

        $result = $DB->get_record("peerforum_discussions", $conditions, 'id, firstpost', MUST_EXIST);

        return [
                'discussionid' => $result->id,
                'parentid' => $result->firstpost,
        ];
    }

    /**
     * Create one or more attached or inline attachments to a peerforum post.
     *
     * @param int $postid The ID of the peerforum post.
     * @param int $userid The user ID creating the attachment.
     * @param array $attachmentnames Names of all attachments to be created.
     * @param stdClass $cm The context module of the peerforum.
     * @param string $filearea The file area being written to, eg 'attachment' or 'post' (inline).
     */
    protected function create_post_attachments(int $postid, int $userid, array $attachmentnames, stdClass $cm,
            string $filearea): void {
        $filestorage = get_file_storage();

        foreach ($attachmentnames as $attachmentname) {
            $filestorage->create_file_from_string(
                    [
                            'contextid' => context_module::instance($cm->id)->id,
                            'component' => 'mod_peerforum',
                            'filearea' => $filearea,
                            'itemid' => $postid,
                            'filepath' => '/',
                            'filename' => $attachmentname,
                            'userid' => $userid,
                    ],
                    "File content {$attachmentname}"
            );
        }
    }

    /**
     * Returns the steps list to add a new discussion to a peerforum.
     *
     * Abstracts add a new topic and add a new discussion, as depending
     * on the peerforum type the button string changes.
     *
     * @param string $peerforumname
     * @param TableNode $table
     * @param string $buttonstr
     */
    protected function add_new_discussion($peerforumname, TableNode $table, $buttonstr) {

        // Navigate to peerforum.
        $this->execute('behat_general::click_link', $this->escape($peerforumname));
        $this->execute('behat_general::click_link', $buttonstr);
        $this->execute('behat_forms::press_button', get_string('showadvancededitor'));

        $this->fill_new_discussion_form($table);
    }

    /**
     * Returns the steps list to add a new discussion to a peerforum inline.
     *
     * Abstracts add a new topic and add a new discussion, as depending
     * on the peerforum type the button string changes.
     *
     * @param string $peerforumname
     * @param TableNode $table
     * @param string $buttonstr
     */
    protected function add_new_discussion_inline($peerforumname, TableNode $table, $buttonstr) {

        // Navigate to peerforum.
        $this->execute('behat_general::click_link', $this->escape($peerforumname));
        $this->execute('behat_general::click_link', $buttonstr);
        $this->fill_new_discussion_form($table);
    }

    /**
     * Fill in the peerforum's post form and submit. It assumes you've already navigated and enabled the form for view.
     *
     * @param TableNode $table
     * @throws coding_exception
     */
    protected function fill_new_discussion_form(TableNode $table) {
        // Fill form and post.
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', $table);
        $this->execute('behat_forms::press_button', get_string('posttopeerforum', 'peerforum'));
        $this->execute('behat_general::i_wait_to_be_redirected');
    }

    /**
     * Go to the default reply to post page.
     * This is used instead of navigating through 4-5 different steps and to solve issues where JS would be required to click
     * on the advanced button
     *
     * @param $postsubject
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function goto_main_post_reply($postsubject) {
        global $DB;
        $post = $DB->get_record("peerforum_posts", array("subject" => $postsubject), 'id', MUST_EXIST);
        $url = new moodle_url('/mod/peerforum/post.php', ['reply' => $post->id]);
        $this->execute('behat_general::i_visit', [$url]);
    }
}
