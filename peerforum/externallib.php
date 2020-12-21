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
 * External peerforum API
 *
 * @package    mod_peerforum
 * @copyright  2012 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

use mod_peerforum\local\exporters\post as post_exporter;
use mod_peerforum\local\exporters\discussion as discussion_exporter;

class mod_peerforum_external extends external_api {

    /**
     * Describes the parameters for get_peerforum.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function get_peerforums_by_courses_parameters() {
        return new external_function_parameters (
                array(
                        'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course ID',
                                VALUE_REQUIRED, '', NULL_NOT_ALLOWED), 'Array of Course IDs', VALUE_DEFAULT, array()),
                )
        );
    }

    /**
     * Returns a list of peerforums in a provided list of courses,
     * if no list is provided all peerforums that the user can view
     * will be returned.
     *
     * @param array $courseids the course ids
     * @return array the peerforum details
     * @since Moodle 2.5
     */
    public static function get_peerforums_by_courses($courseids = array()) {
        global $CFG;

        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::get_peerforums_by_courses_parameters(), array('courseids' => $courseids));

        $courses = array();
        if (empty($params['courseids'])) {
            $courses = enrol_get_my_courses();
            $params['courseids'] = array_keys($courses);
        }

        // Array to store the peerforums to return.
        $arrpeerforums = array();
        $warnings = array();

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $courses);

            // Get the peerforums in this course. This function checks users visibility permissions.
            $peerforums = get_all_instances_in_courses("peerforum", $courses);
            foreach ($peerforums as $peerforum) {

                $course = $courses[$peerforum->course];
                $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id);
                $context = context_module::instance($cm->id);

                // Skip peerforums we are not allowed to see discussions.
                if (!has_capability('mod/peerforum:viewdiscussion', $context)) {
                    continue;
                }

                $peerforum->name = external_format_string($peerforum->name, $context->id);
                // Format the intro before being returning using the format setting.
                $options = array('noclean' => true);
                list($peerforum->intro, $peerforum->introformat) =
                        external_format_text($peerforum->intro, $peerforum->introformat, $context->id, 'mod_peerforum', 'intro',
                                null,
                                $options);
                $peerforum->introfiles = external_util::get_area_files($context->id, 'mod_peerforum', 'intro', false, false);
                // Discussions count. This function does static request cache.
                $peerforum->numdiscussions = peerforum_count_discussions($peerforum, $cm, $course);
                $peerforum->cmid = $peerforum->coursemodule;
                $peerforum->cancreatediscussions = peerforum_user_can_post_discussion($peerforum, null, -1, $cm, $context);
                $peerforum->istracked = peerforum_tp_is_tracked($peerforum);
                if ($peerforum->istracked) {
                    $peerforum->unreadpostscount = peerforum_tp_count_peerforum_unread_posts($cm, $course);
                }

                // Add the peerforum to the array to return.
                $arrpeerforums[$peerforum->id] = $peerforum;
            }
        }

        return $arrpeerforums;
    }

    /**
     * Describes the get_peerforum return value.
     *
     * @return external_single_structure
     * @since Moodle 2.5
     */
    public static function get_peerforums_by_courses_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'id' => new external_value(PARAM_INT, 'PeerForum id'),
                                'course' => new external_value(PARAM_INT, 'Course id'),
                                'type' => new external_value(PARAM_TEXT, 'The peerforum type'),
                                'name' => new external_value(PARAM_RAW, 'PeerForum name'),
                                'intro' => new external_value(PARAM_RAW, 'The peerforum intro'),
                                'introformat' => new external_format_value('intro'),
                                'introfiles' => new external_files('Files in the introduction text', VALUE_OPTIONAL),
                                'duedate' => new external_value(PARAM_INT, 'duedate for the user', VALUE_OPTIONAL),
                                'cutoffdate' => new external_value(PARAM_INT, 'cutoffdate for the user', VALUE_OPTIONAL),
                                'assessed' => new external_value(PARAM_INT, 'Aggregate type'),
                                'assesstimestart' => new external_value(PARAM_INT, 'Assess start time'),
                                'assesstimefinish' => new external_value(PARAM_INT, 'Assess finish time'),
                                'scale' => new external_value(PARAM_INT, 'Scale'),
                                'grade_peerforum' => new external_value(PARAM_INT, 'Whole peerforum grade'),
                                'grade_peerforum_notify' => new external_value(PARAM_INT,
                                        'Whether to send notifications to students upon grading by default'),
                                'maxbytes' => new external_value(PARAM_INT, 'Maximum attachment size'),
                                'maxattachments' => new external_value(PARAM_INT, 'Maximum number of attachments'),
                                'forcesubscribe' => new external_value(PARAM_INT, 'Force users to subscribe'),
                                'trackingtype' => new external_value(PARAM_INT, 'Subscription mode'),
                                'rsstype' => new external_value(PARAM_INT, 'RSS feed for this activity'),
                                'rssarticles' => new external_value(PARAM_INT, 'Number of RSS recent articles'),
                                'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                'warnafter' => new external_value(PARAM_INT, 'Post threshold for warning'),
                                'blockafter' => new external_value(PARAM_INT, 'Post threshold for blocking'),
                                'blockperiod' => new external_value(PARAM_INT, 'Time period for blocking'),
                                'completiondiscussions' => new external_value(PARAM_INT, 'Student must create discussions'),
                                'completionreplies' => new external_value(PARAM_INT, 'Student must post replies'),
                                'completionposts' => new external_value(PARAM_INT, 'Student must post discussions or replies'),
                                'cmid' => new external_value(PARAM_INT, 'Course module id'),
                                'numdiscussions' => new external_value(PARAM_INT, 'Number of discussions in the peerforum',
                                        VALUE_OPTIONAL),
                                'cancreatediscussions' => new external_value(PARAM_BOOL, 'If the user can create discussions',
                                        VALUE_OPTIONAL),
                                'lockdiscussionafter' => new external_value(PARAM_INT, 'After what period a discussion is locked',
                                        VALUE_OPTIONAL),
                                'istracked' => new external_value(PARAM_BOOL, 'If the user is tracking the peerforum',
                                        VALUE_OPTIONAL),
                                'unreadpostscount' => new external_value(PARAM_INT,
                                        'The number of unread posts for tracked peerforums',
                                        VALUE_OPTIONAL),
                        ), 'peerforum'
                )
        );
    }

    /**
     * Get the peerforum posts in the specified discussion.
     *
     * @param int $discussionid
     * @param string $sortby
     * @param string $sortdirection
     * @return  array
     */
    public static function get_discussion_posts(int $discussionid, ?string $sortby, ?string $sortdirection) {
        global $USER;
        // Validate the parameter.
        $params = self::validate_parameters(self::get_discussion_posts_parameters(), [
                'discussionid' => $discussionid,
                'sortby' => $sortby,
                'sortdirection' => $sortdirection,
        ]);
        $warnings = [];

        $vaultfactory = mod_peerforum\local\container::get_vault_factory();

        $discussionvault = $vaultfactory->get_discussion_vault();
        $discussion = $discussionvault->get_from_id($params['discussionid']);

        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_id($discussion->get_peerforum_id());
        $context = $peerforum->get_context();
        self::validate_context($context);

        $sortby = $params['sortby'];
        $sortdirection = $params['sortdirection'];
        $sortallowedvalues = ['id', 'created', 'modified'];
        $directionallowedvalues = ['ASC', 'DESC'];

        if (!in_array(strtolower($sortby), $sortallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $sortby . '),' .
                    'allowed values are: ' . implode(', ', $sortallowedvalues));
        }

        $sortdirection = strtoupper($sortdirection);
        if (!in_array($sortdirection, $directionallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortdirection parameter (value: ' . $sortdirection . '),' .
                    'allowed values are: ' . implode(',', $directionallowedvalues));
        }

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

        $postvault = $vaultfactory->get_post_vault();
        $posts = $postvault->get_from_discussion_id(
                $USER,
                $discussion->get_id(),
                $capabilitymanager->can_view_any_private_reply($USER),
                "{$sortby} {$sortdirection}"
        );

        $builderfactory = mod_peerforum\local\container::get_builder_factory();
        $postbuilder = $builderfactory->get_exported_posts_builder();

        $legacydatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory();

        return [
                'posts' => $postbuilder->build($USER, [$peerforum], [$discussion], $posts),
                'peerforumid' => $discussion->get_peerforum_id(),
                'courseid' => $discussion->get_course_id(),
                'ratinginfo' => \core_rating\external\util::get_rating_info(
                        $legacydatamapper->get_peerforum_data_mapper()->to_legacy_object($peerforum),
                        $peerforum->get_context(),
                        'mod_peerforum',
                        'post',
                        $legacydatamapper->get_post_data_mapper()->to_legacy_objects($posts)
                ),
                'warnings' => $warnings,
        ];
    }

    /**
     * Describe the post parameters.
     *
     * @return external_function_parameters
     */
    public static function get_discussion_posts_parameters() {
        return new external_function_parameters ([
                'discussionid' => new external_value(PARAM_INT, 'The ID of the discussion from which to fetch posts.',
                        VALUE_REQUIRED),
                'sortby' => new external_value(PARAM_ALPHA, 'Sort by this element: id, created or modified', VALUE_DEFAULT,
                        'created'),
                'sortdirection' => new external_value(PARAM_ALPHA, 'Sort direction: ASC or DESC', VALUE_DEFAULT, 'DESC')
        ]);
    }

    /**
     * Describe the post return format.
     *
     * @return external_single_structure
     */
    public static function get_discussion_posts_returns() {
        return new external_single_structure([
                'posts' => new external_multiple_structure(\mod_peerforum\local\exporters\post::get_read_structure()),
                'peerforumid' => new external_value(PARAM_INT, 'The peerforum id'),
                'courseid' => new external_value(PARAM_INT, 'The peerforum course id'),
                'ratinginfo' => \core_rating\external\util::external_ratings_structure(),
                'warnings' => new external_warnings()
        ]);
    }

    /**
     * Describes the parameters for get_peerforum_discussion_posts.
     *
     * @return external_function_parameters
     * @since Moodle 2.7
     */
    public static function get_peerforum_discussion_posts_parameters() {
        return new external_function_parameters (
                array(
                        'discussionid' => new external_value(PARAM_INT, 'discussion ID', VALUE_REQUIRED),
                        'sortby' => new external_value(PARAM_ALPHA,
                                'sort by this element: id, created or modified', VALUE_DEFAULT, 'created'),
                        'sortdirection' => new external_value(PARAM_ALPHA, 'sort direction: ASC or DESC', VALUE_DEFAULT, 'DESC')
                )
        );
    }

    /**
     * Returns a list of peerforum posts for a discussion
     *
     * @param int $discussionid the post ids
     * @param string $sortby sort by this element (id, created or modified)
     * @param string $sortdirection sort direction: ASC or DESC
     *
     * @return array the peerforum post details
     * @since Moodle 2.7
     * @todo MDL-65252 This will be removed in Moodle 3.11
     */
    public static function get_peerforum_discussion_posts($discussionid, $sortby = "created", $sortdirection = "DESC") {
        global $CFG, $DB, $USER, $PAGE;

        $posts = array();
        $warnings = array();

        // Validate the parameter.
        $params = self::validate_parameters(self::get_peerforum_discussion_posts_parameters(),
                array(
                        'discussionid' => $discussionid,
                        'sortby' => $sortby,
                        'sortdirection' => $sortdirection));

        // Compact/extract functions are not recommended.
        $discussionid = $params['discussionid'];
        $sortby = $params['sortby'];
        $sortdirection = $params['sortdirection'];

        $sortallowedvalues = array('id', 'created', 'modified');
        if (!in_array($sortby, $sortallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $sortby . '),' .
                    'allowed values are: ' . implode(',', $sortallowedvalues));
        }

        $sortdirection = strtoupper($sortdirection);
        $directionallowedvalues = array('ASC', 'DESC');
        if (!in_array($sortdirection, $directionallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortdirection parameter (value: ' . $sortdirection . '),' .
                    'allowed values are: ' . implode(',', $directionallowedvalues));
        }

        $discussion = $DB->get_record('peerforum_discussions', array('id' => $discussionid), '*', MUST_EXIST);
        $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($cm->id);
        self::validate_context($modcontext);

        // This require must be here, see mod/peerforum/discuss.php.
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        // Check they have the view peerforum capability.
        require_capability('mod/peerforum:viewdiscussion', $modcontext, null, true, 'noviewdiscussionspermission', 'peerforum');

        if (!$post = peerforum_get_post_full($discussion->firstpost)) {
            throw new moodle_exception('notexists', 'peerforum');
        }

        // This function check groups, qanda, timed discussions, etc.
        if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
            throw new moodle_exception('noviewdiscussionspermission', 'peerforum');
        }

        $canviewfullname = has_capability('moodle/site:viewfullnames', $modcontext);

        // We will add this field in the response.
        $canreply = peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext);

        $peerforumtracked = peerforum_tp_is_tracked($peerforum);

        $sort = 'p.' . $sortby . ' ' . $sortdirection;
        $allposts = peerforum_get_all_discussion_posts($discussion->id, $sort, $peerforumtracked);

        foreach ($allposts as $post) {
            if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm, false)) {
                $warning = array();
                $warning['item'] = 'post';
                $warning['itemid'] = $post->id;
                $warning['warningcode'] = '1';
                $warning['message'] = 'You can\'t see this post';
                $warnings[] = $warning;
                continue;
            }

            // Function peerforum_get_all_discussion_posts adds postread field.
            // Note that the value returned can be a boolean or an integer. The WS expects a boolean.
            if (empty($post->postread)) {
                $post->postread = false;
            } else {
                $post->postread = true;
            }

            $post->isprivatereply = !empty($post->privatereplyto);

            $post->canreply = $canreply;
            if (!empty($post->children)) {
                $post->children = array_keys($post->children);
            } else {
                $post->children = array();
            }

            if (!peerforum_user_can_see_post($peerforum, $discussion, $post, null, $cm)) {
                // The post is available, but has been marked as deleted.
                // It will still be available but filled with a placeholder.
                $post->userid = null;
                $post->userfullname = null;
                $post->userpictureurl = null;

                $post->subject = get_string('privacy:request:delete:post:subject', 'mod_peerforum');
                $post->message = get_string('privacy:request:delete:post:message', 'mod_peerforum');

                $post->deleted = true;
                $posts[] = $post;

                continue;
            }
            $post->deleted = false;

            if (peerforum_is_author_hidden($post, $peerforum)) {
                $post->userid = null;
                $post->userfullname = null;
                $post->userpictureurl = null;
            } else {
                $user = new stdclass();
                $user->id = $post->userid;
                $user = username_load_fields_from_object($user, $post, null, array('picture', 'imagealt', 'email'));
                $post->userfullname = fullname($user, $canviewfullname);

                $userpicture = new user_picture($user);
                $userpicture->size = 1; // Size f1.
                $post->userpictureurl = $userpicture->get_url($PAGE)->out(false);
            }

            $post->subject = external_format_string($post->subject, $modcontext->id);
            // Rewrite embedded images URLs.
            $options = array('trusted' => $post->messagetrust);
            list($post->message, $post->messageformat) =
                    external_format_text($post->message, $post->messageformat, $modcontext->id, 'mod_peerforum', 'post', $post->id,
                            $options);

            // List attachments.
            if (!empty($post->attachment)) {
                $post->attachments = external_util::get_area_files($modcontext->id, 'mod_peerforum', 'attachment', $post->id);
            }
            $messageinlinefiles = external_util::get_area_files($modcontext->id, 'mod_peerforum', 'post', $post->id);
            if (!empty($messageinlinefiles)) {
                $post->messageinlinefiles = $messageinlinefiles;
            }
            // Post tags.
            $post->tags = \core_tag\external\util::get_item_tags('mod_peerforum', 'peerforum_posts', $post->id);

            $posts[] = $post;
        }

        $result = array();
        $result['posts'] = $posts;
        $result['ratinginfo'] =
                \core_rating\external\util::get_rating_info($peerforum, $modcontext, 'mod_peerforum', 'post', $posts);
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_peerforum_discussion_posts return value.
     *
     * @return external_single_structure
     * @since Moodle 2.7
     */
    public static function get_peerforum_discussion_posts_returns() {
        return new external_single_structure(
                array(
                        'posts' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'id' => new external_value(PARAM_INT, 'Post id'),
                                                'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                'parent' => new external_value(PARAM_INT, 'Parent id'),
                                                'userid' => new external_value(PARAM_INT, 'User id'),
                                                'created' => new external_value(PARAM_INT, 'Creation time'),
                                                'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                'subject' => new external_value(PARAM_RAW, 'The post subject'),
                                                'message' => new external_value(PARAM_RAW, 'The post message'),
                                                'messageformat' => new external_format_value('message'),
                                                'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                'messageinlinefiles' => new external_files('post message inline files',
                                                        VALUE_OPTIONAL),
                                                'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                                'attachments' => new external_files('attachments', VALUE_OPTIONAL),
                                                'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                'children' => new external_multiple_structure(new external_value(PARAM_INT,
                                                        'children post id')),
                                                'canreply' => new external_value(PARAM_BOOL, 'The user can reply to posts?'),
                                                'postread' => new external_value(PARAM_BOOL, 'The post was read'),
                                                'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.',
                                                        VALUE_OPTIONAL),
                                                'deleted' => new external_value(PARAM_BOOL, 'This post has been removed.'),
                                                'isprivatereply' => new external_value(PARAM_BOOL, 'The post is a private reply'),
                                                'tags' => new external_multiple_structure(
                                                        \core_tag\external\tag_item_exporter::get_read_structure(), 'Tags',
                                                        VALUE_OPTIONAL
                                                ),
                                        ), 'post'
                                )
                        ),
                        'ratinginfo' => \core_rating\external\util::external_ratings_structure(),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Mark the get_peerforum_discussion_posts web service as deprecated.
     *
     * @return  bool
     */
    public static function get_peerforum_discussion_posts_is_deprecated() {
        return true;
    }

    /**
     * Describes the parameters for get_peerforum_discussions_paginated.
     *
     * @return external_function_parameters
     * @deprecated since 3.7
     * @since Moodle 2.8
     */
    public static function get_peerforum_discussions_paginated_parameters() {
        return new external_function_parameters (
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'peerforum instance id', VALUE_REQUIRED),
                        'sortby' => new external_value(PARAM_ALPHA,
                                'sort by this element: id, timemodified, timestart or timeend', VALUE_DEFAULT, 'timemodified'),
                        'sortdirection' => new external_value(PARAM_ALPHA, 'sort direction: ASC or DESC', VALUE_DEFAULT, 'DESC'),
                        'page' => new external_value(PARAM_INT, 'current page', VALUE_DEFAULT, -1),
                        'perpage' => new external_value(PARAM_INT, 'items per page', VALUE_DEFAULT, 0),
                )
        );
    }

    /**
     * Returns a list of peerforum discussions optionally sorted and paginated.
     *
     * @param int $peerforumid the peerforum instance id
     * @param string $sortby sort by this element (id, timemodified, timestart or timeend)
     * @param string $sortdirection sort direction: ASC or DESC
     * @param int $page page number
     * @param int $perpage items per page
     *
     * @return array the peerforum discussion details including warnings
     * @deprecated since 3.7
     * @since Moodle 2.8
     */
    public static function get_peerforum_discussions_paginated($peerforumid, $sortby = 'timemodified', $sortdirection = 'DESC',
            $page = -1, $perpage = 0) {
        global $CFG, $DB, $USER, $PAGE;

        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $warnings = array();
        $discussions = array();

        $params = self::validate_parameters(self::get_peerforum_discussions_paginated_parameters(),
                array(
                        'peerforumid' => $peerforumid,
                        'sortby' => $sortby,
                        'sortdirection' => $sortdirection,
                        'page' => $page,
                        'perpage' => $perpage
                )
        );

        // Compact/extract functions are not recommended.
        $peerforumid = $params['peerforumid'];
        $sortby = $params['sortby'];
        $sortdirection = $params['sortdirection'];
        $page = $params['page'];
        $perpage = $params['perpage'];

        $sortallowedvalues = array('id', 'timemodified', 'timestart', 'timeend');
        if (!in_array($sortby, $sortallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $sortby . '),' .
                    'allowed values are: ' . implode(',', $sortallowedvalues));
        }

        $sortdirection = strtoupper($sortdirection);
        $directionallowedvalues = array('ASC', 'DESC');
        if (!in_array($sortdirection, $directionallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortdirection parameter (value: ' . $sortdirection . '),' .
                    'allowed values are: ' . implode(',', $directionallowedvalues));
        }

        $peerforum = $DB->get_record('peerforum', array('id' => $peerforumid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $peerforum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($cm->id);
        self::validate_context($modcontext);

        // Check they have the view peerforum capability.
        require_capability('mod/peerforum:viewdiscussion', $modcontext, null, true, 'noviewdiscussionspermission', 'peerforum');

        $sort = 'd.pinned DESC, d.' . $sortby . ' ' . $sortdirection;
        $alldiscussions =
                peerforum_get_discussions($cm, $sort, true, -1, -1, true, $page, $perpage, PEERFORUM_POSTS_ALL_USER_GROUPS);

        if ($alldiscussions) {
            $canviewfullname = has_capability('moodle/site:viewfullnames', $modcontext);

            // Get the unreads array, this takes a peerforum id and returns data for all discussions.
            $unreads = array();
            if ($cantrack = peerforum_tp_can_track_peerforums($peerforum)) {
                if ($peerforumtracked = peerforum_tp_is_tracked($peerforum)) {
                    $unreads = peerforum_get_discussions_unread($cm);
                }
            }
            // The peerforum function returns the replies for all the discussions in a given peerforum.
            $canseeprivatereplies = has_capability('mod/peerforum:readprivatereplies', $modcontext);
            $canlock = has_capability('moodle/course:manageactivities', $modcontext, $USER);
            $replies = peerforum_count_discussion_replies($peerforumid, $sort, -1, $page, $perpage, $canseeprivatereplies);

            foreach ($alldiscussions as $discussion) {

                // This function checks for qanda peerforums.
                // Note that the peerforum_get_discussions returns as id the post id, not the discussion id so we need to do this.
                $discussionrec = clone $discussion;
                $discussionrec->id = $discussion->discussion;
                if (!peerforum_user_can_see_discussion($peerforum, $discussionrec, $modcontext)) {
                    $warning = array();
                    // Function peerforum_get_discussions returns peerforum_posts ids not peerforum_discussions ones.
                    $warning['item'] = 'post';
                    $warning['itemid'] = $discussion->id;
                    $warning['warningcode'] = '1';
                    $warning['message'] = 'You can\'t see this discussion';
                    $warnings[] = $warning;
                    continue;
                }

                $discussion->numunread = 0;
                if ($cantrack && $peerforumtracked) {
                    if (isset($unreads[$discussion->discussion])) {
                        $discussion->numunread = (int) $unreads[$discussion->discussion];
                    }
                }

                $discussion->numreplies = 0;
                if (!empty($replies[$discussion->discussion])) {
                    $discussion->numreplies = (int) $replies[$discussion->discussion]->replies;
                }

                $discussion->name = external_format_string($discussion->name, $modcontext->id);
                $discussion->subject = external_format_string($discussion->subject, $modcontext->id);
                // Rewrite embedded images URLs.
                $options = array('trusted' => $discussion->messagetrust);
                list($discussion->message, $discussion->messageformat) =
                        external_format_text($discussion->message, $discussion->messageformat,
                                $modcontext->id, 'mod_peerforum', 'post', $discussion->id, $options);

                // List attachments.
                if (!empty($discussion->attachment)) {
                    $discussion->attachments = external_util::get_area_files($modcontext->id, 'mod_peerforum', 'attachment',
                            $discussion->id);
                }
                $messageinlinefiles = external_util::get_area_files($modcontext->id, 'mod_peerforum', 'post', $discussion->id);
                if (!empty($messageinlinefiles)) {
                    $discussion->messageinlinefiles = $messageinlinefiles;
                }

                $discussion->locked = peerforum_discussion_is_locked($peerforum, $discussion);
                $discussion->canlock = $canlock;
                $discussion->canreply = peerforum_user_can_post($peerforum, $discussion, $USER, $cm, $course, $modcontext);

                if (peerforum_is_author_hidden($discussion, $peerforum)) {
                    $discussion->userid = null;
                    $discussion->userfullname = null;
                    $discussion->userpictureurl = null;

                    $discussion->usermodified = null;
                    $discussion->usermodifiedfullname = null;
                    $discussion->usermodifiedpictureurl = null;
                } else {
                    $picturefields = explode(',', user_picture::fields());

                    // Load user objects from the results of the query.
                    $user = new stdclass();
                    $user->id = $discussion->userid;
                    $user = username_load_fields_from_object($user, $discussion, null, $picturefields);
                    // Preserve the id, it can be modified by username_load_fields_from_object.
                    $user->id = $discussion->userid;
                    $discussion->userfullname = fullname($user, $canviewfullname);

                    $userpicture = new user_picture($user);
                    $userpicture->size = 1; // Size f1.
                    $discussion->userpictureurl = $userpicture->get_url($PAGE)->out(false);

                    $usermodified = new stdclass();
                    $usermodified->id = $discussion->usermodified;
                    $usermodified = username_load_fields_from_object($usermodified, $discussion, 'um', $picturefields);
                    // Preserve the id (it can be overwritten due to the prefixed $picturefields).
                    $usermodified->id = $discussion->usermodified;
                    $discussion->usermodifiedfullname = fullname($usermodified, $canviewfullname);

                    $userpicture = new user_picture($usermodified);
                    $userpicture->size = 1; // Size f1.
                    $discussion->usermodifiedpictureurl = $userpicture->get_url($PAGE)->out(false);
                }

                $discussions[] = $discussion;
            }
        }

        $result = array();
        $result['discussions'] = $discussions;
        $result['warnings'] = $warnings;
        return $result;

    }

    /**
     * Describes the get_peerforum_discussions_paginated return value.
     *
     * @return external_single_structure
     * @deprecated since 3.7
     * @since Moodle 2.8
     */
    public static function get_peerforum_discussions_paginated_returns() {
        return new external_single_structure(
                array(
                        'discussions' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'id' => new external_value(PARAM_INT, 'Post id'),
                                                'name' => new external_value(PARAM_RAW, 'Discussion name'),
                                                'groupid' => new external_value(PARAM_INT, 'Group id'),
                                                'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                                'usermodified' => new external_value(PARAM_INT,
                                                        'The id of the user who last modified'),
                                                'timestart' => new external_value(PARAM_INT, 'Time discussion can start'),
                                                'timeend' => new external_value(PARAM_INT, 'Time discussion ends'),
                                                'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                'parent' => new external_value(PARAM_INT, 'Parent id'),
                                                'userid' => new external_value(PARAM_INT, 'User who started the discussion id'),
                                                'created' => new external_value(PARAM_INT, 'Creation time'),
                                                'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                'subject' => new external_value(PARAM_RAW, 'The post subject'),
                                                'message' => new external_value(PARAM_RAW, 'The post message'),
                                                'messageformat' => new external_format_value('message'),
                                                'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                'messageinlinefiles' => new external_files('post message inline files',
                                                        VALUE_OPTIONAL),
                                                'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                                'attachments' => new external_files('attachments', VALUE_OPTIONAL),
                                                'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                'usermodifiedfullname' => new external_value(PARAM_TEXT, 'Post modifier full name'),
                                                'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.'),
                                                'usermodifiedpictureurl' => new external_value(PARAM_URL, 'Post modifier picture.'),
                                                'numreplies' => new external_value(PARAM_INT,
                                                        'The number of replies in the discussion'),
                                                'numunread' => new external_value(PARAM_INT, 'The number of unread discussions.'),
                                                'pinned' => new external_value(PARAM_BOOL, 'Is the discussion pinned'),
                                                'locked' => new external_value(PARAM_BOOL, 'Is the discussion locked'),
                                                'canreply' => new external_value(PARAM_BOOL,
                                                        'Can the user reply to the discussion'),
                                                'canlock' => new external_value(PARAM_BOOL, 'Can the user lock the discussion'),
                                        ), 'post'
                                )
                        ),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Describes the parameters for get_peerforum_discussions.
     *
     * @return external_function_parameters
     * @since Moodle 3.7
     */
    public static function get_peerforum_discussions_parameters() {
        return new external_function_parameters (
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'peerforum instance id', VALUE_REQUIRED),
                        'sortorder' => new external_value(PARAM_INT,
                                'sort by this element: numreplies, , created or timemodified', VALUE_DEFAULT, -1),
                        'page' => new external_value(PARAM_INT, 'current page', VALUE_DEFAULT, -1),
                        'perpage' => new external_value(PARAM_INT, 'items per page', VALUE_DEFAULT, 0),
                        'groupid' => new external_value(PARAM_INT, 'group id', VALUE_DEFAULT, 0),
                )
        );
    }

    /**
     * Returns a list of peerforum discussions optionally sorted and paginated.
     *
     * @param int $peerforumid the peerforum instance id
     * @param int $sortorder The sort order
     * @param int $page page number
     * @param int $perpage items per page
     * @param int $groupid the user course group
     *
     *
     * @return array the peerforum discussion details including warnings
     * @since Moodle 3.7
     */
    public static function get_peerforum_discussions(int $peerforumid, ?int $sortorder = -1, ?int $page = -1,
            ?int $perpage = 0, ?int $groupid = 0) {

        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $warnings = array();
        $discussions = array();

        $params = self::validate_parameters(self::get_peerforum_discussions_parameters(),
                array(
                        'peerforumid' => $peerforumid,
                        'sortorder' => $sortorder,
                        'page' => $page,
                        'perpage' => $perpage,
                        'groupid' => $groupid
                )
        );

        // Compact/extract functions are not recommended.
        $peerforumid = $params['peerforumid'];
        $sortorder = $params['sortorder'];
        $page = $params['page'];
        $perpage = $params['perpage'];
        $groupid = $params['groupid'];

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $discussionlistvault = $vaultfactory->get_discussions_in_peerforum_vault();

        $sortallowedvalues = array(
                $discussionlistvault::SORTORDER_LASTPOST_DESC,
                $discussionlistvault::SORTORDER_LASTPOST_ASC,
                $discussionlistvault::SORTORDER_CREATED_DESC,
                $discussionlistvault::SORTORDER_CREATED_ASC,
                $discussionlistvault::SORTORDER_REPLIES_DESC,
                $discussionlistvault::SORTORDER_REPLIES_ASC
        );

        // If sortorder not defined set a default one.
        if ($sortorder == -1) {
            $sortorder = $discussionlistvault::SORTORDER_LASTPOST_DESC;
        }

        if (!in_array($sortorder, $sortallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortorder parameter (value: ' . $sortorder . '),' .
                    ' allowed values are: ' . implode(',', $sortallowedvalues));
        }

        $managerfactory = \mod_peerforum\local\container::get_manager_factory();
        $urlfactory = \mod_peerforum\local\container::get_url_factory();
        $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();

        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_id($peerforumid);
        if (!$peerforum) {
            throw new \moodle_exception("Unable to find peerforum with id {$peerforumid}");
        }
        $peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();
        $peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);

        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

        $course = $DB->get_record('course', array('id' => $peerforum->get_course_id()), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->get_id(), $course->id, false, MUST_EXIST);

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($cm->id);
        self::validate_context($modcontext);

        $canseeanyprivatereply = $capabilitymanager->can_view_any_private_reply($USER);

        // Check they have the view peerforum capability.
        if (!$capabilitymanager->can_view_discussions($USER)) {
            throw new moodle_exception('noviewdiscussionspermission', 'peerforum');
        }

        $alldiscussions = mod_peerforum_get_discussion_summaries($peerforum, $USER, $groupid, $sortorder, $page, $perpage);

        if ($alldiscussions) {
            $discussionids = array_keys($alldiscussions);

            $postvault = $vaultfactory->get_post_vault();
            $postdatamapper = $legacydatamapperfactory->get_post_data_mapper();
            // Return the reply count for each discussion in a given peerforum.
            $replies = $postvault->get_reply_count_for_discussion_ids($USER, $discussionids, $canseeanyprivatereply);
            // Return the first post for each discussion in a given peerforum.
            $firstposts = $postvault->get_first_post_for_discussion_ids($discussionids);

            // Get the unreads array, this takes a peerforum id and returns data for all discussions.
            $unreads = array();
            if ($cantrack = peerforum_tp_can_track_peerforums($peerforumrecord)) {
                if ($peerforumtracked = peerforum_tp_is_tracked($peerforumrecord)) {
                    $unreads = $postvault->get_unread_count_for_discussion_ids($USER, $discussionids, $canseeanyprivatereply);
                }
            }

            $canlock = $capabilitymanager->can_manage_peerforum($USER);

            $usercontext = context_user::instance($USER->id);
            $ufservice = core_favourites\service_factory::get_service_for_user_context($usercontext);

            $canfavourite = has_capability('mod/peerforum:cantogglefavourite', $modcontext, $USER);

            foreach ($alldiscussions as $discussionsummary) {
                $discussion = $discussionsummary->get_discussion();
                $firstpostauthor = $discussionsummary->get_first_post_author();
                $latestpostauthor = $discussionsummary->get_latest_post_author();

                // This function checks for qanda peerforums.
                $canviewdiscussion = $capabilitymanager->can_view_discussion($USER, $discussion);
                if (!$canviewdiscussion) {
                    $warning = array();
                    // Function peerforum_get_discussions returns peerforum_posts ids not peerforum_discussions ones.
                    $warning['item'] = 'post';
                    $warning['itemid'] = $discussion->get_id();
                    $warning['warningcode'] = '1';
                    $warning['message'] = 'You can\'t see this discussion';
                    $warnings[] = $warning;
                    continue;
                }

                $firstpost = $firstposts[$discussion->get_first_post_id()];
                $discussionobject = $postdatamapper->to_legacy_object($firstpost);
                // Fix up the types for these properties.
                $discussionobject->mailed = $discussionobject->mailed ? 1 : 0;
                $discussionobject->messagetrust = $discussionobject->messagetrust ? 1 : 0;
                $discussionobject->mailnow = $discussionobject->mailnow ? 1 : 0;
                $discussionobject->groupid = $discussion->get_group_id();
                $discussionobject->timemodified = $discussion->get_time_modified();
                $discussionobject->usermodified = $discussion->get_user_modified();
                $discussionobject->timestart = $discussion->get_time_start();
                $discussionobject->timeend = $discussion->get_time_end();
                $discussionobject->pinned = $discussion->is_pinned();

                $discussionobject->numunread = 0;
                if ($cantrack && $peerforumtracked) {
                    if (isset($unreads[$discussion->get_id()])) {
                        $discussionobject->numunread = (int) $unreads[$discussion->get_id()];
                    }
                }

                $discussionobject->numreplies = 0;
                if (!empty($replies[$discussion->get_id()])) {
                    $discussionobject->numreplies = (int) $replies[$discussion->get_id()];
                }

                $discussionobject->name = external_format_string($discussion->get_name(), $modcontext->id);
                $discussionobject->subject = external_format_string($discussionobject->subject, $modcontext->id);
                // Rewrite embedded images URLs.
                $options = array('trusted' => $discussionobject->messagetrust);
                list($discussionobject->message, $discussionobject->messageformat) =
                        external_format_text($discussionobject->message, $discussionobject->messageformat,
                                $modcontext->id, 'mod_peerforum', 'post', $discussionobject->id, $options);

                // List attachments.
                if (!empty($discussionobject->attachment)) {
                    $discussionobject->attachments = external_util::get_area_files($modcontext->id, 'mod_peerforum',
                            'attachment', $discussionobject->id);
                }
                $messageinlinefiles = external_util::get_area_files($modcontext->id, 'mod_peerforum', 'post',
                        $discussionobject->id);
                if (!empty($messageinlinefiles)) {
                    $discussionobject->messageinlinefiles = $messageinlinefiles;
                }

                $discussionobject->locked = $peerforum->is_discussion_locked($discussion);
                $discussionobject->canlock = $canlock;
                $discussionobject->starred = !empty($ufservice) ? $ufservice->favourite_exists('mod_peerforum', 'discussions',
                        $discussion->get_id(), $modcontext) : false;
                $discussionobject->canreply = $capabilitymanager->can_post_in_discussion($USER, $discussion);
                $discussionobject->canfavourite = $canfavourite;

                if (peerforum_is_author_hidden($discussionobject, $peerforumrecord)) {
                    $discussionobject->userid = null;
                    $discussionobject->userfullname = null;
                    $discussionobject->userpictureurl = null;

                    $discussionobject->usermodified = null;
                    $discussionobject->usermodifiedfullname = null;
                    $discussionobject->usermodifiedpictureurl = null;

                } else {
                    $discussionobject->userfullname = $firstpostauthor->get_full_name();
                    $discussionobject->userpictureurl = $urlfactory->get_author_profile_image_url($firstpostauthor, null, 2)
                            ->out(false);

                    $discussionobject->usermodifiedfullname = $latestpostauthor->get_full_name();
                    $discussionobject->usermodifiedpictureurl = $urlfactory->get_author_profile_image_url(
                            $latestpostauthor, null, 2)->out(false);
                }

                $discussions[] = (array) $discussionobject;
            }
        }
        $result = array();
        $result['discussions'] = $discussions;
        $result['warnings'] = $warnings;

        return $result;
    }

    /**
     * Describes the get_peerforum_discussions return value.
     *
     * @return external_single_structure
     * @since Moodle 3.7
     */
    public static function get_peerforum_discussions_returns() {
        return new external_single_structure(
                array(
                        'discussions' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'id' => new external_value(PARAM_INT, 'Post id'),
                                                'name' => new external_value(PARAM_RAW, 'Discussion name'),
                                                'groupid' => new external_value(PARAM_INT, 'Group id'),
                                                'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                                'usermodified' => new external_value(PARAM_INT,
                                                        'The id of the user who last modified'),
                                                'timestart' => new external_value(PARAM_INT, 'Time discussion can start'),
                                                'timeend' => new external_value(PARAM_INT, 'Time discussion ends'),
                                                'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                'parent' => new external_value(PARAM_INT, 'Parent id'),
                                                'userid' => new external_value(PARAM_INT, 'User who started the discussion id'),
                                                'created' => new external_value(PARAM_INT, 'Creation time'),
                                                'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                'subject' => new external_value(PARAM_RAW, 'The post subject'),
                                                'message' => new external_value(PARAM_RAW, 'The post message'),
                                                'messageformat' => new external_format_value('message'),
                                                'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                'messageinlinefiles' => new external_files('post message inline files',
                                                        VALUE_OPTIONAL),
                                                'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                                'attachments' => new external_files('attachments', VALUE_OPTIONAL),
                                                'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                'usermodifiedfullname' => new external_value(PARAM_TEXT, 'Post modifier full name'),
                                                'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.'),
                                                'usermodifiedpictureurl' => new external_value(PARAM_URL, 'Post modifier picture.'),
                                                'numreplies' => new external_value(PARAM_INT,
                                                        'The number of replies in the discussion'),
                                                'numunread' => new external_value(PARAM_INT, 'The number of unread discussions.'),
                                                'pinned' => new external_value(PARAM_BOOL, 'Is the discussion pinned'),
                                                'locked' => new external_value(PARAM_BOOL, 'Is the discussion locked'),
                                                'starred' => new external_value(PARAM_BOOL, 'Is the discussion starred'),
                                                'canreply' => new external_value(PARAM_BOOL,
                                                        'Can the user reply to the discussion'),
                                                'canlock' => new external_value(PARAM_BOOL, 'Can the user lock the discussion'),
                                                'canfavourite' => new external_value(PARAM_BOOL,
                                                        'Can the user star the discussion'),
                                        ), 'post'
                                )
                        ),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.9
     */
    public static function view_peerforum_parameters() {
        return new external_function_parameters(
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'peerforum instance id')
                )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $peerforumid the peerforum instance id
     * @return array of warnings and status result
     * @throws moodle_exception
     * @since Moodle 2.9
     */
    public static function view_peerforum($peerforumid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::view_peerforum_parameters(),
                array(
                        'peerforumid' => $peerforumid
                ));
        $warnings = array();

        // Request and permission validation.
        $peerforum = $DB->get_record('peerforum', array('id' => $params['peerforumid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($peerforum, 'peerforum');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/peerforum:viewdiscussion', $context, null, true, 'noviewdiscussionspermission', 'peerforum');

        // Call the peerforum/lib API.
        peerforum_view($peerforum, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.9
     */
    public static function view_peerforum_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.9
     */
    public static function view_peerforum_discussion_parameters() {
        return new external_function_parameters(
                array(
                        'discussionid' => new external_value(PARAM_INT, 'discussion id')
                )
        );
    }

    /**
     * Trigger the discussion viewed event.
     *
     * @param int $discussionid the discussion id
     * @return array of warnings and status result
     * @throws moodle_exception
     * @since Moodle 2.9
     */
    public static function view_peerforum_discussion($discussionid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::view_peerforum_discussion_parameters(),
                array(
                        'discussionid' => $discussionid
                ));
        $warnings = array();

        $discussion = $DB->get_record('peerforum_discussions', array('id' => $params['discussionid']), '*', MUST_EXIST);
        $peerforum = $DB->get_record('peerforum', array('id' => $discussion->peerforum), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($peerforum, 'peerforum');

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($cm->id);
        self::validate_context($modcontext);

        require_capability('mod/peerforum:viewdiscussion', $modcontext, null, true, 'noviewdiscussionspermission', 'peerforum');

        // Call the peerforum/lib API.
        peerforum_discussion_view($modcontext, $peerforum, $discussion);

        // Mark as read if required.
        if (!$CFG->peerforum_usermarksread && peerforum_tp_is_tracked($peerforum)) {
            peerforum_tp_mark_discussion_read($USER, $discussion->id);
        }

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.9
     */
    public static function view_peerforum_discussion_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function add_discussion_post_parameters() {
        return new external_function_parameters(
                array(
                        'postid' => new external_value(PARAM_INT, 'the post id we are going to reply to
                                                (can be the initial discussion post'),
                        'subject' => new external_value(PARAM_TEXT, 'new post subject'),
                        'message' => new external_value(PARAM_RAW,
                                'new post message (html assumed if messageformat is not provided)'),
                        'options' => new external_multiple_structure (
                                new external_single_structure(
                                        array(
                                                'name' => new external_value(PARAM_ALPHANUM,
                                                        'The allowed keys (value format) are:
                                        discussionsubscribe (bool); subscribe to the discussion?, default to true
                                        private (bool); make this reply private to the author of the parent post, default to false.
                                        inlineattachmentsid              (int); the draft file area id for inline attachments
                                        attachmentsid       (int); the draft file area id for attachments
                                        topreferredformat (bool); convert the message & messageformat to FORMAT_HTML, defaults to false
                            '),
                                                'value' => new external_value(PARAM_RAW, 'the value of the option,
                                                            this param is validated in the external function.'
                                                )
                                        )
                                ), 'Options', VALUE_DEFAULT, array()),
                        'messageformat' => new external_format_value('message', VALUE_DEFAULT)
                )
        );
    }

    /**
     * Create new posts into an existing discussion.
     *
     * @param int $postid the post id we are going to reply to
     * @param string $subject new post subject
     * @param string $message new post message (html assumed if messageformat is not provided)
     * @param array $options optional settings
     * @param string $messageformat The format of the message, defaults to FORMAT_HTML for BC
     * @return array of warnings and the new post id
     * @throws moodle_exception
     * @since Moodle 3.0
     */
    public static function add_discussion_post($postid, $subject, $message, $options = array(), $messageformat = FORMAT_HTML) {
        global $CFG, $USER;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        // Get all the factories that are required.
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $entityfactory = mod_peerforum\local\container::get_entity_factory();
        $datamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussiondatamapper = $datamapperfactory->get_discussion_data_mapper();
        $peerforumdatamapper = $datamapperfactory->get_peerforum_data_mapper();

        $params = self::validate_parameters(self::add_discussion_post_parameters(),
                array(
                        'postid' => $postid,
                        'subject' => $subject,
                        'message' => $message,
                        'options' => $options,
                        'messageformat' => $messageformat,
                )
        );

        $warnings = array();

        if (!$parent = peerforum_get_post_full($params['postid'])) {
            throw new moodle_exception('invalidparentpostid', 'peerforum');
        }

        if (!$discussion = $discussionvault->get_from_id($parent->discussion)) {
            throw new moodle_exception('notpartofdiscussion', 'peerforum');
        }

        // Request and permission validation.
        $peerforum = $peerforumvault->get_from_id($discussion->get_peerforum_id());
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);
        $course = $peerforum->get_course_record();
        $cm = $peerforum->get_course_module_record();

        $discussionrecord = $discussiondatamapper->to_legacy_object($discussion);
        $peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $coursecontext = \context_course::instance($peerforum->get_course_id());
        $discussionsubscribe = \mod_peerforum\subscriptions::get_user_default_subscription($peerforumrecord, $coursecontext,
                $cm, null);

        // Validate options.
        $options = array(
                'discussionsubscribe' => $discussionsubscribe,
                'private' => false,
                'inlineattachmentsid' => 0,
                'attachmentsid' => null,
                'topreferredformat' => false
        );
        foreach ($params['options'] as $option) {
            $name = trim($option['name']);
            switch ($name) {
                case 'discussionsubscribe':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'private':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'inlineattachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    break;
                case 'attachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    // Ensure that the user has permissions to create attachments.
                    if (!has_capability('mod/peerforum:createattachment', $context)) {
                        $value = 0;
                    }
                    break;
                case 'topreferredformat':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                default:
                    throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
            }
            $options[$name] = $value;
        }

        if (!$capabilitymanager->can_post_in_discussion($USER, $discussion)) {
            throw new moodle_exception('nopostpeerforum', 'peerforum');
        }

        $thresholdwarning = peerforum_check_throttling($peerforumrecord, $cm);
        peerforum_check_blocking_threshold($thresholdwarning);

        // If we want to force a conversion to the preferred format, let's do it now.
        if ($options['topreferredformat']) {
            // We always are going to honor the preferred format. We are creating a new post.
            $preferredformat = editors_get_preferred_format();
            // If the post is not HTML and the preferred format is HTML, convert to it.
            if ($params['messageformat'] != FORMAT_HTML and $preferredformat == FORMAT_HTML) {
                $params['message'] = format_text($params['message'], $params['messageformat'], ['filter' => false]);
            }
            $params['messageformat'] = $preferredformat;
        }

        // Create the post.
        $post = new stdClass();
        $post->discussion = $discussion->get_id();
        $post->parent = $parent->id;
        $post->subject = $params['subject'];
        $post->message = $params['message'];
        $post->messageformat = $params['messageformat'];
        $post->messagetrust = trusttext_trusted($context);
        $post->itemid = $options['inlineattachmentsid'];
        $post->attachments = $options['attachmentsid'];
        $post->isprivatereply = $options['private'];
        $post->deleted = 0;
        $fakemform = $post->attachments;
        if ($postid = peerforum_add_new_post($post, $fakemform)) {

            $post->id = $postid;

            // Trigger events and completion.
            $params = array(
                    'context' => $context,
                    'objectid' => $post->id,
                    'other' => array(
                            'discussionid' => $discussion->get_id(),
                            'peerforumid' => $peerforum->get_id(),
                            'peerforumtype' => $peerforum->get_type(),
                    )
            );
            $event = \mod_peerforum\event\post_created::create($params);
            $event->add_record_snapshot('peerforum_posts', $post);
            $event->add_record_snapshot('peerforum_discussions', $discussionrecord);
            $event->trigger();

            // Update completion state.
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) &&
                    ($peerforum->get_completion_replies() || $peerforum->get_completion_posts())) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }

            if ($options['discussionsubscribe']) {
                $settings = new stdClass();
                $settings->discussionsubscribe = $options['discussionsubscribe'];
                peerforum_post_subscription($settings, $peerforumrecord, $discussionrecord);
            }
        } else {
            throw new moodle_exception('couldnotadd', 'peerforum');
        }

        $builderfactory = \mod_peerforum\local\container::get_builder_factory();
        $exportedpostsbuilder = $builderfactory->get_exported_posts_builder();
        $postentity = $entityfactory->get_post_from_stdClass($post);
        $exportedposts = $exportedpostsbuilder->build($USER, [$peerforum], [$discussion], [$postentity]);
        $exportedpost = $exportedposts[0];

        $message = [];
        $message[] = [
                'type' => 'success',
                'message' => get_string("postaddedsuccess", "peerforum")
        ];

        $message[] = [
                'type' => 'success',
                'message' => get_string("postaddedtimeleft", "peerforum", format_time($CFG->maxeditingtime))
        ];

        $result = array();
        $result['postid'] = $postid;
        $result['warnings'] = $warnings;
        $result['post'] = $exportedpost;
        $result['messages'] = $message;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function add_discussion_post_returns() {
        return new external_single_structure(
                array(
                        'postid' => new external_value(PARAM_INT, 'new post id'),
                        'warnings' => new external_warnings(),
                        'post' => post_exporter::get_read_structure(),
                        'messages' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'type' => new external_value(PARAM_TEXT,
                                                        "The classification to be used in the client side", VALUE_REQUIRED),
                                                'message' => new external_value(PARAM_TEXT,
                                                        'untranslated english message to explain the warning', VALUE_REQUIRED)
                                        ), 'Messages'), 'list of warnings', VALUE_OPTIONAL
                        ),
                    //'alertmessage' => new external_value(PARAM_RAW, 'Success message to be displayed to the user.'),
                )
        );
    }

    /**
     * Toggle the favouriting value for the discussion provided
     *
     * @param int $discussionid The discussion we need to favourite
     * @param bool $targetstate The state of the favourite value
     * @return array The exported discussion
     */
    public static function toggle_favourite_state($discussionid, $targetstate) {
        global $DB, $PAGE, $USER;

        $params = self::validate_parameters(self::toggle_favourite_state_parameters(), [
                'discussionid' => $discussionid,
                'targetstate' => $targetstate
        ]);

        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        // Get the discussion vault and the corresponding discussion entity.
        $discussionvault = $vaultfactory->get_discussion_vault();
        $discussion = $discussionvault->get_from_id($params['discussionid']);

        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_id($discussion->get_peerforum_id());
        $peerforumcontext = $peerforum->get_context();
        self::validate_context($peerforumcontext);

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

        // Does the user have the ability to favourite the discussion?
        if (!$capabilitymanager->can_favourite_discussion($USER)) {
            throw new moodle_exception('cannotfavourite', 'peerforum');
        }
        $usercontext = context_user::instance($USER->id);
        $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
        $isfavourited = $ufservice->favourite_exists('mod_peerforum', 'discussions', $discussion->get_id(), $peerforumcontext);

        $favouritefunction = $targetstate ? 'create_favourite' : 'delete_favourite';
        if ($isfavourited != (bool) $params['targetstate']) {
            $ufservice->{$favouritefunction}('mod_peerforum', 'discussions', $discussion->get_id(), $peerforumcontext);
        }

        $exporterfactory = mod_peerforum\local\container::get_exporter_factory();
        $builder = mod_peerforum\local\container::get_builder_factory()->get_exported_discussion_builder();
        $favourited = ($builder->is_favourited($discussion, $peerforumcontext, $USER) ? [$discussion->get_id()] : []);
        $exporter = $exporterfactory->get_discussion_exporter($USER, $peerforum, $discussion, [], $favourited);
        return $exporter->export($PAGE->get_renderer('mod_peerforum'));
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function toggle_favourite_state_returns() {
        return discussion_exporter::get_read_structure();
    }

    /**
     * Defines the parameters for the toggle_favourite_state method
     *
     * @return external_function_parameters
     */
    public static function toggle_favourite_state_parameters() {
        return new external_function_parameters(
                [
                        'discussionid' => new external_value(PARAM_INT, 'The discussion to subscribe or unsubscribe'),
                        'targetstate' => new external_value(PARAM_BOOL, 'The target state')
                ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function add_discussion_parameters() {
        return new external_function_parameters(
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'PeerForum instance ID'),
                        'subject' => new external_value(PARAM_TEXT, 'New Discussion subject'),
                        'message' => new external_value(PARAM_RAW, 'New Discussion message (only html format allowed)'),
                        'groupid' => new external_value(PARAM_INT, 'The group, default to 0', VALUE_DEFAULT, 0),
                        'options' => new external_multiple_structure (
                                new external_single_structure(
                                        array(
                                                'name' => new external_value(PARAM_ALPHANUM,
                                                        'The allowed keys (value format) are:
                                        discussionsubscribe (bool); subscribe to the discussion?, default to true
                                        discussionpinned    (bool); is the discussion pinned, default to false
                                        inlineattachmentsid              (int); the draft file area id for inline attachments
                                        attachmentsid       (int); the draft file area id for attachments
                            '),
                                                'value' => new external_value(PARAM_RAW, 'The value of the option,
                                                            This param is validated in the external function.'
                                                )
                                        )
                                ), 'Options', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Add a new discussion into an existing peerforum.
     *
     * @param int $peerforumid the peerforum instance id
     * @param string $subject new discussion subject
     * @param string $message new discussion message (only html format allowed)
     * @param int $groupid the user course group
     * @param array $options optional settings
     * @return array of warnings and the new discussion id
     * @throws moodle_exception
     * @since Moodle 3.0
     */
    public static function add_discussion($peerforumid, $subject, $message, $groupid = 0, $options = array()) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::add_discussion_parameters(),
                array(
                        'peerforumid' => $peerforumid,
                        'subject' => $subject,
                        'message' => $message,
                        'groupid' => $groupid,
                        'options' => $options
                ));

        $warnings = array();

        // Request and permission validation.
        $peerforum = $DB->get_record('peerforum', array('id' => $params['peerforumid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($peerforum, 'peerforum');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Validate options.
        $options = array(
                'discussionsubscribe' => true,
                'discussionpinned' => false,
                'inlineattachmentsid' => 0,
                'attachmentsid' => null
        );
        foreach ($params['options'] as $option) {
            $name = trim($option['name']);
            switch ($name) {
                case 'discussionsubscribe':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'discussionpinned':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'inlineattachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    break;
                case 'attachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    // Ensure that the user has permissions to create attachments.
                    if (!has_capability('mod/peerforum:createattachment', $context)) {
                        $value = 0;
                    }
                    break;
                default:
                    throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
            }
            $options[$name] = $value;
        }

        // Normalize group.
        if (!groups_get_activity_groupmode($cm)) {
            // Groups not supported, force to -1.
            $groupid = -1;
        } else {
            // Check if we receive the default or and empty value for groupid,
            // in this case, get the group for the user in the activity.
            if (empty($params['groupid'])) {
                $groupid = groups_get_activity_group($cm);
            } else {
                // Here we rely in the group passed, peerforum_user_can_post_discussion will validate the group.
                $groupid = $params['groupid'];
            }
        }

        if (!peerforum_user_can_post_discussion($peerforum, $groupid, -1, $cm, $context)) {
            throw new moodle_exception('cannotcreatediscussion', 'peerforum');
        }

        $thresholdwarning = peerforum_check_throttling($peerforum, $cm);
        peerforum_check_blocking_threshold($thresholdwarning);

        // Create the discussion.
        $discussion = new stdClass();
        $discussion->course = $course->id;
        $discussion->peerforum = $peerforum->id;
        $discussion->message = $params['message'];
        $discussion->messageformat = FORMAT_HTML;   // Force formatting for now.
        $discussion->messagetrust = trusttext_trusted($context);
        $discussion->itemid = $options['inlineattachmentsid'];
        $discussion->groupid = $groupid;
        $discussion->mailnow = 0;
        $discussion->subject = $params['subject'];
        $discussion->name = $discussion->subject;
        $discussion->timestart = 0;
        $discussion->timeend = 0;
        $discussion->timelocked = 0;
        $discussion->attachments = $options['attachmentsid'];

        if (has_capability('mod/peerforum:pindiscussions', $context) && $options['discussionpinned']) {
            $discussion->pinned = PEERFORUM_DISCUSSION_PINNED;
        } else {
            $discussion->pinned = PEERFORUM_DISCUSSION_UNPINNED;
        }
        $fakemform = $options['attachmentsid'];
        if ($discussionid = peerforum_add_discussion($discussion, $fakemform)) {

            $discussion->id = $discussionid;

            // Trigger events and completion.

            $params = array(
                    'context' => $context,
                    'objectid' => $discussion->id,
                    'other' => array(
                            'peerforumid' => $peerforum->id,
                    )
            );
            $event = \mod_peerforum\event\discussion_created::create($params);
            $event->add_record_snapshot('peerforum_discussions', $discussion);
            $event->trigger();

            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) &&
                    ($peerforum->completiondiscussions || $peerforum->completionposts)) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }

            $settings = new stdClass();
            $settings->discussionsubscribe = $options['discussionsubscribe'];
            peerforum_post_subscription($settings, $peerforum, $discussion);
        } else {
            throw new moodle_exception('couldnotadd', 'peerforum');
        }

        $result = array();
        $result['discussionid'] = $discussionid;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function add_discussion_returns() {
        return new external_single_structure(
                array(
                        'discussionid' => new external_value(PARAM_INT, 'New Discussion ID'),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function can_add_discussion_parameters() {
        return new external_function_parameters(
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'PeerForum instance ID'),
                        'groupid' => new external_value(PARAM_INT, 'The group to check, default to active group.
                                                Use -1 to check if the user can post in all the groups.', VALUE_DEFAULT, null)
                )
        );
    }

    /**
     * Check if the current user can add discussions in the given peerforum (and optionally for the given group).
     *
     * @param int $peerforumid the peerforum instance id
     * @param int $groupid the group to check, default to active group. Use -1 to check if the user can post in all the groups.
     * @return array of warnings and the status (true if the user can add discussions)
     * @throws moodle_exception
     * @since Moodle 3.1
     */
    public static function can_add_discussion($peerforumid, $groupid = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::can_add_discussion_parameters(),
                array(
                        'peerforumid' => $peerforumid,
                        'groupid' => $groupid,
                ));
        $warnings = array();

        // Request and permission validation.
        $peerforum = $DB->get_record('peerforum', array('id' => $params['peerforumid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($peerforum, 'peerforum');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $status = peerforum_user_can_post_discussion($peerforum, $params['groupid'], -1, $cm, $context);

        $result = array();
        $result['status'] = $status;
        $result['canpindiscussions'] = has_capability('mod/peerforum:pindiscussions', $context);
        $result['cancreateattachment'] = peerforum_can_create_attachment($peerforum, $context);
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function can_add_discussion_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_BOOL, 'True if the user can add discussions, false otherwise.'),
                        'canpindiscussions' => new external_value(PARAM_BOOL,
                                'True if the user can pin discussions, false otherwise.',
                                VALUE_OPTIONAL),
                        'cancreateattachment' => new external_value(PARAM_BOOL,
                                'True if the user can add attachments, false otherwise.',
                                VALUE_OPTIONAL),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Describes the parameters for get_peerforum_access_information.
     *
     * @return external_external_function_parameters
     * @since Moodle 3.7
     */
    public static function get_peerforum_access_information_parameters() {
        return new external_function_parameters (
                array(
                        'peerforumid' => new external_value(PARAM_INT, 'PeerForum instance id.')
                )
        );
    }

    /**
     * Return access information for a given peerforum.
     *
     * @param int $peerforumid peerforum instance id
     * @return array of warnings and the access information
     * @throws  moodle_exception
     * @since Moodle 3.7
     */
    public static function get_peerforum_access_information($peerforumid) {
        global $DB;

        $params = self::validate_parameters(self::get_peerforum_access_information_parameters(),
                array('peerforumid' => $peerforumid));

        // Request and permission validation.
        $peerforum = $DB->get_record('peerforum', array('id' => $params['peerforumid']), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $result = array();
        // Return all the available capabilities.
        $capabilities = load_capability_def('mod_peerforum');
        foreach ($capabilities as $capname => $capdata) {
            // Get fields like cansubmit so it is consistent with the access_information function implemented in other modules.
            $field = 'can' . str_replace('mod/peerforum:', '', $capname);
            $result[$field] = has_capability($capname, $context);
        }

        $result['warnings'] = array();
        return $result;
    }

    /**
     * Describes the get_peerforum_access_information return value.
     *
     * @return external_single_structure
     * @since Moodle 3.7
     */
    public static function get_peerforum_access_information_returns() {

        $structure = array(
                'warnings' => new external_warnings()
        );

        $capabilities = load_capability_def('mod_peerforum');
        foreach ($capabilities as $capname => $capdata) {
            // Get fields like cansubmit so it is consistent with the access_information function implemented in other modules.
            $field = 'can' . str_replace('mod/peerforum:', '', $capname);
            $structure[$field] = new external_value(PARAM_BOOL, 'Whether the user has the capability ' . $capname . ' allowed.',
                    VALUE_OPTIONAL);
        }

        return new external_single_structure($structure);
    }

    /**
     * Set the subscription state.
     *
     * @param int $peerforumid
     * @param int $discussionid
     * @param bool $targetstate
     * @return  \stdClass
     */
    public static function set_subscription_state($peerforumid, $discussionid, $targetstate) {
        global $PAGE, $USER;

        $params = self::validate_parameters(self::set_subscription_state_parameters(), [
                'peerforumid' => $peerforumid,
                'discussionid' => $discussionid,
                'targetstate' => $targetstate
        ]);

        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_id($params['peerforumid']);
        $coursemodule = $peerforum->get_course_module_record();
        $context = $peerforum->get_context();

        self::validate_context($context);

        $discussionvault = $vaultfactory->get_discussion_vault();
        $discussion = $discussionvault->get_from_id($params['discussionid']);
        $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();

        $peerforumrecord = $legacydatamapperfactory->get_peerforum_data_mapper()->to_legacy_object($peerforum);
        $discussionrecord = $legacydatamapperfactory->get_discussion_data_mapper()->to_legacy_object($discussion);

        if (!\mod_peerforum\subscriptions::is_subscribable($peerforumrecord)) {
            // Nothing to do. We won't actually output any content here though.
            throw new \moodle_exception('cannotsubscribe', 'mod_peerforum');
        }

        $issubscribed = \mod_peerforum\subscriptions::is_subscribed(
                $USER->id,
                $peerforumrecord,
                $discussion->get_id(),
                $coursemodule
        );

        // If the current state doesn't equal the desired state then update the current
        // state to the desired state.
        if ($issubscribed != (bool) $params['targetstate']) {
            if ($params['targetstate']) {
                \mod_peerforum\subscriptions::subscribe_user_to_discussion($USER->id, $discussionrecord, $context);
            } else {
                \mod_peerforum\subscriptions::unsubscribe_user_from_discussion($USER->id, $discussionrecord, $context);
            }
        }

        $exporterfactory = mod_peerforum\local\container::get_exporter_factory();
        $exporter = $exporterfactory->get_discussion_exporter($USER, $peerforum, $discussion);
        return $exporter->export($PAGE->get_renderer('mod_peerforum'));
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function set_subscription_state_parameters() {
        return new external_function_parameters(
                [
                        'peerforumid' => new external_value(PARAM_INT, 'PeerForum that the discussion is in'),
                        'discussionid' => new external_value(PARAM_INT, 'The discussion to subscribe or unsubscribe'),
                        'targetstate' => new external_value(PARAM_BOOL, 'The target state')
                ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function set_subscription_state_returns() {
        return discussion_exporter::get_read_structure();
    }

    /**
     * Set the lock state.
     *
     * @param int $peerforumid
     * @param int $discussionid
     * @param string $targetstate
     * @return  \stdClass
     */
    public static function set_lock_state($peerforumid, $discussionid, $targetstate) {
        global $DB, $PAGE, $USER;

        $params = self::validate_parameters(self::set_lock_state_parameters(), [
                'peerforumid' => $peerforumid,
                'discussionid' => $discussionid,
                'targetstate' => $targetstate
        ]);

        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_id($params['peerforumid']);

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);
        if (!$capabilitymanager->can_manage_peerforum($USER)) {
            throw new moodle_exception('errorcannotlock', 'peerforum');
        }

        // If the targetstate(currentstate) is not 0 then it should be set to the current time.
        $lockedvalue = $targetstate ? 0 : time();
        self::validate_context($peerforum->get_context());

        $discussionvault = $vaultfactory->get_discussion_vault();
        $discussion = $discussionvault->get_from_id($params['discussionid']);

        // If the current state doesn't equal the desired state then update the current.
        // state to the desired state.
        $discussion->toggle_locked_state($lockedvalue);
        $response = $discussionvault->update_discussion($discussion);
        $discussion = !$response ? $response : $discussion;
        $exporterfactory = mod_peerforum\local\container::get_exporter_factory();
        $exporter = $exporterfactory->get_discussion_exporter($USER, $peerforum, $discussion);
        return $exporter->export($PAGE->get_renderer('mod_peerforum'));
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function set_lock_state_parameters() {
        return new external_function_parameters(
                [
                        'peerforumid' => new external_value(PARAM_INT, 'PeerForum that the discussion is in'),
                        'discussionid' => new external_value(PARAM_INT, 'The discussion to lock / unlock'),
                        'targetstate' => new external_value(PARAM_INT, 'The timestamp for the lock state')
                ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function set_lock_state_returns() {
        return new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The discussion we are locking.'),
                'locked' => new external_value(PARAM_BOOL, 'The locked state of the discussion.'),
                'times' => new external_single_structure([
                        'locked' => new external_value(PARAM_INT, 'The locked time of the discussion.'),
                ])
        ]);
    }

    /**
     * Set the pin state.
     *
     * @param int $discussionid
     * @param bool $targetstate
     * @return  \stdClass
     */
    public static function set_pin_state($discussionid, $targetstate) {
        global $PAGE, $USER;
        $params = self::validate_parameters(self::set_pin_state_parameters(), [
                'discussionid' => $discussionid,
                'targetstate' => $targetstate,
        ]);
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $discussion = $discussionvault->get_from_id($params['discussionid']);
        $peerforum = $peerforumvault->get_from_id($discussion->get_peerforum_id());
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

        self::validate_context($peerforum->get_context());

        $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
        if (!$capabilitymanager->can_pin_discussions($USER)) {
            // Nothing to do. We won't actually output any content here though.
            throw new \moodle_exception('cannotpindiscussions', 'mod_peerforum');
        }

        $discussion->set_pinned($targetstate);
        $discussionvault->update_discussion($discussion);

        $exporterfactory = mod_peerforum\local\container::get_exporter_factory();
        $exporter = $exporterfactory->get_discussion_exporter($USER, $peerforum, $discussion);
        return $exporter->export($PAGE->get_renderer('mod_peerforum'));
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function set_pin_state_parameters() {
        return new external_function_parameters(
                [
                        'discussionid' => new external_value(PARAM_INT, 'The discussion to pin or unpin', VALUE_REQUIRED,
                                null, NULL_NOT_ALLOWED),
                        'targetstate' => new external_value(PARAM_INT, 'The target state', VALUE_REQUIRED,
                                null, NULL_NOT_ALLOWED),
                ]
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function set_pin_state_returns() {
        return discussion_exporter::get_read_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function delete_post_parameters() {
        return new external_function_parameters(
                array(
                        'postid' => new external_value(PARAM_INT, 'Post to be deleted. It can be a discussion topic post.'),
                )
        );
    }

    /**
     * Deletes a post or a discussion completely when the post is the discussion topic.
     *
     * @param int $postid post to be deleted, it can be a discussion topic post.
     * @return array of warnings and the status (true if the post/discussion was deleted)
     * @throws moodle_exception
     * @since Moodle 3.8
     */
    public static function delete_post($postid) {
        global $USER, $CFG;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::delete_post_parameters(),
                array(
                        'postid' => $postid,
                )
        );
        $warnings = array();
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $postvault = $vaultfactory->get_post_vault();
        $postentity = $postvault->get_from_id($params['postid']);

        if (empty($postentity)) {
            throw new moodle_exception('invalidpostid', 'peerforum');
        }

        $discussionentity = $discussionvault->get_from_id($postentity->get_discussion_id());

        if (empty($discussionentity)) {
            throw new moodle_exception('notpartofdiscussion', 'peerforum');
        }

        $peerforumentity = $peerforumvault->get_from_id($discussionentity->get_peerforum_id());
        if (empty($peerforumentity)) {
            throw new moodle_exception('invalidpeerforumid', 'peerforum');
        }

        $context = $peerforumentity->get_context();

        self::validate_context($context);

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);
        $peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();
        $discussiondatamapper = $legacydatamapperfactory->get_discussion_data_mapper();
        $postdatamapper = $legacydatamapperfactory->get_post_data_mapper();

        $replycount = $postvault->get_reply_count_for_post_id_in_discussion_id($USER, $postentity->get_id(),
                $discussionentity->get_id(), true);
        $hasreplies = $replycount > 0;

        $capabilitymanager->validate_delete_post($USER, $discussionentity, $postentity, $hasreplies);

        if (!$postentity->has_parent()) {
            $status = peerforum_delete_discussion(
                    $discussiondatamapper->to_legacy_object($discussionentity),
                    false,
                    $peerforumentity->get_course_record(),
                    $peerforumentity->get_course_module_record(),
                    $peerforumdatamapper->to_legacy_object($peerforumentity)
            );
        } else {
            $status = peerforum_delete_post(
                    $postdatamapper->to_legacy_object($postentity),
                    has_capability('mod/peerforum:deleteanypost', $context),
                    $peerforumentity->get_course_record(),
                    $peerforumentity->get_course_module_record(),
                    $peerforumdatamapper->to_legacy_object($peerforumentity)
            );
        }

        $result = array();
        $result['status'] = $status;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.8
     */
    public static function delete_post_returns() {
        return new external_single_structure(
                array(
                        'status' => new external_value(PARAM_BOOL, 'True if the post/discussion was deleted, false otherwise.'),
                        'warnings' => new external_warnings()
                )
        );
    }

    /**
     * Get the peerforum posts in the specified peerforum instance.
     *
     * @param int $userid
     * @param int $cmid
     * @param string $sortby
     * @param string $sortdirection
     * @return  array
     */
    public static function get_discussion_posts_by_userid(int $userid = 0, int $cmid, ?string $sortby, ?string $sortdirection) {
        global $USER, $DB;
        // Validate the parameter.
        $params = self::validate_parameters(self::get_discussion_posts_by_userid_parameters(), [
                'userid' => $userid,
                'cmid' => $cmid,
                'sortby' => $sortby,
                'sortdirection' => $sortdirection,
        ]);
        $warnings = [];

        $user = core_user::get_user($params['userid']);

        $vaultfactory = mod_peerforum\local\container::get_vault_factory();

        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $peerforum = $peerforumvault->get_from_course_module_id($params['cmid']);

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        self::validate_context($peerforum->get_context());

        $sortby = $params['sortby'];
        $sortdirection = $params['sortdirection'];
        $sortallowedvalues = ['id', 'created', 'modified'];
        $directionallowedvalues = ['ASC', 'DESC'];

        if (!in_array(strtolower($sortby), $sortallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortby parameter (value: ' . $sortby . '),' .
                    'allowed values are: ' . implode(', ', $sortallowedvalues));
        }

        $sortdirection = strtoupper($sortdirection);
        if (!in_array($sortdirection, $directionallowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for sortdirection parameter (value: ' . $sortdirection . '),' .
                    'allowed values are: ' . implode(',', $directionallowedvalues));
        }

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforum);

        $discussionsummariesvault = $vaultfactory->get_discussions_in_peerforum_vault();
        $discussionsummaries = $discussionsummariesvault->get_from_peerforum_id(
                $peerforum->get_id(),
                true,
                null,
                $discussionsummariesvault::SORTORDER_CREATED_ASC,
                0,
                0
        );

        $postvault = $vaultfactory->get_post_vault();

        $builderfactory = mod_peerforum\local\container::get_builder_factory();
        $postbuilder = $builderfactory->get_exported_posts_builder();

        $builtdiscussions = [];
        foreach ($discussionsummaries as $discussionsummary) {
            $discussion = $discussionsummary->get_discussion();
            $posts = $postvault->get_posts_in_discussion_for_user_id(
                    $discussion->get_id(),
                    $user->id,
                    $capabilitymanager->can_view_any_private_reply($USER),
                    "{$sortby} {$sortdirection}"
            );
            if (empty($posts)) {
                continue;
            }

            $parentids = array_filter(array_map(function($post) {
                return $post->has_parent() ? $post->get_parent_id() : null;
            }, $posts));

            $parentposts = [];
            if ($parentids) {
                $parentposts = $postbuilder->build(
                        $USER,
                        [$peerforum],
                        [$discussion],
                        $postvault->get_from_ids(array_values($parentids))
                );
            }

            $discussionauthor = $discussionsummary->get_first_post_author();
            $firstpost = $discussionsummary->get_first_post();

            $builtdiscussions[] = [
                    'name' => $discussion->get_name(),
                    'id' => $discussion->get_id(),
                    'timecreated' => $firstpost->get_time_created(),
                    'authorfullname' => $discussionauthor->get_full_name(),
                    'posts' => [
                            'userposts' => $postbuilder->build($USER, [$peerforum], [$discussion], $posts),
                            'parentposts' => $parentposts,
                    ],
            ];
        }

        return [
                'discussions' => $builtdiscussions,
                'warnings' => $warnings,
        ];
    }

    /**
     * Describe the post parameters.
     *
     * @return external_function_parameters
     */
    public static function get_discussion_posts_by_userid_parameters() {
        return new external_function_parameters ([
                'userid' => new external_value(
                        PARAM_INT, 'The ID of the user of whom to fetch posts.', VALUE_REQUIRED),
                'cmid' => new external_value(
                        PARAM_INT, 'The ID of the module of which to fetch items.', VALUE_REQUIRED),
                'sortby' => new external_value(
                        PARAM_ALPHA, 'Sort by this element: id, created or modified', VALUE_DEFAULT, 'created'),
                'sortdirection' => new external_value(
                        PARAM_ALPHA, 'Sort direction: ASC or DESC', VALUE_DEFAULT, 'DESC')
        ]);
    }

    /**
     * Describe the post return format.
     *
     * @return external_single_structure
     */
    public static function get_discussion_posts_by_userid_returns() {
        return new external_single_structure([
                'discussions' => new external_multiple_structure(
                        new external_single_structure([
                                'name' => new external_value(PARAM_RAW, 'Name of the discussion'),
                                'id' => new external_value(PARAM_INT, 'ID of the discussion'),
                                'timecreated' => new external_value(PARAM_INT, 'Timestamp of the discussion start'),
                                'authorfullname' => new external_value(PARAM_RAW,
                                        'Full name of the user that started the discussion'),
                                'posts' => new external_single_structure([
                                        'userposts' => new external_multiple_structure(\mod_peerforum\local\exporters\post::get_read_structure()),
                                        'parentposts' => new external_multiple_structure(\mod_peerforum\local\exporters\post::get_read_structure()),
                                ]),
                        ])),
                'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function get_discussion_post_parameters() {
        return new external_function_parameters(
                array(
                        'postid' => new external_value(PARAM_INT, 'Post to fetch.'),
                )
        );
    }

    /**
     * Get a particular discussion post.
     *
     * @param int $postid post to fetch
     * @return array of post and warnings (if any)
     * @throws moodle_exception
     * @since Moodle 3.8
     */
    public static function get_discussion_post($postid) {
        global $USER, $CFG;

        $params = self::validate_parameters(self::get_discussion_post_parameters(),
                array(
                        'postid' => $postid,
                ));
        $warnings = array();
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $postvault = $vaultfactory->get_post_vault();

        $postentity = $postvault->get_from_id($params['postid']);
        if (empty($postentity)) {
            throw new moodle_exception('invalidpostid', 'peerforum');
        }
        $discussionentity = $discussionvault->get_from_id($postentity->get_discussion_id());
        if (empty($discussionentity)) {
            throw new moodle_exception('notpartofdiscussion', 'peerforum');
        }
        $peerforumentity = $peerforumvault->get_from_id($discussionentity->get_peerforum_id());
        if (empty($peerforumentity)) {
            throw new moodle_exception('invalidpeerforumid', 'peerforum');
        }
        self::validate_context($peerforumentity->get_context());

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);

        if (!$capabilitymanager->can_view_post($USER, $discussionentity, $postentity)) {
            throw new moodle_exception('noviewdiscussionspermission', 'peerforum');
        }

        $builderfactory = mod_peerforum\local\container::get_builder_factory();
        $postbuilder = $builderfactory->get_exported_posts_builder();
        $posts = $postbuilder->build($USER, [$peerforumentity], [$discussionentity], [$postentity]);
        $post = empty($posts) ? array() : reset($posts);

        $result = array();
        $result['post'] = $post;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.8
     */
    public static function get_discussion_post_returns() {
        return new external_single_structure(
                array(
                        'post' => \mod_peerforum\local\exporters\post::get_read_structure(),
                        'warnings' => new external_warnings(),
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function prepare_draft_area_for_post_parameters() {
        return new external_function_parameters(
                array(
                        'postid' => new external_value(PARAM_INT, 'Post to prepare the draft area for.'),
                        'area' => new external_value(PARAM_ALPHA, 'Area to prepare: attachment or post.'),
                        'draftitemid' => new external_value(PARAM_INT, 'The draft item id to use. 0 to generate one.',
                                VALUE_DEFAULT, 0),
                        'filestokeep' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'filename' => new external_value(PARAM_FILE, 'File name.'),
                                                'filepath' => new external_value(PARAM_PATH, 'File path.'),
                                        )
                                ), 'Only keep these files in the draft file area. Empty for keeping all.', VALUE_DEFAULT, []
                        ),
                )
        );
    }

    /**
     * Prepares a draft area for editing a post.
     *
     * @param int $postid post to prepare the draft area for
     * @param string $area area to prepare attachment or post
     * @param int $draftitemid the draft item id to use. 0 to generate a new one.
     * @param array $filestokeep only keep these files in the draft file area. Empty for keeping all.
     * @return array of files in the area, the area options and the draft item id
     * @throws moodle_exception
     * @since Moodle 3.8
     */
    public static function prepare_draft_area_for_post($postid, $area, $draftitemid = 0, $filestokeep = []) {
        global $USER;

        $params = self::validate_parameters(
                self::prepare_draft_area_for_post_parameters(),
                array(
                        'postid' => $postid,
                        'area' => $area,
                        'draftitemid' => $draftitemid,
                        'filestokeep' => $filestokeep,
                )
        );
        $directionallowedvalues = ['ASC', 'DESC'];

        $allowedareas = ['attachment', 'post'];
        if (!in_array($params['area'], $allowedareas)) {
            throw new invalid_parameter_exception('Invalid value for area parameter
                (value: ' . $params['area'] . '),' . 'allowed values are: ' . implode(', ', $allowedareas));
        }

        $warnings = array();
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $postvault = $vaultfactory->get_post_vault();

        $postentity = $postvault->get_from_id($params['postid']);
        if (empty($postentity)) {
            throw new moodle_exception('invalidpostid', 'peerforum');
        }
        $discussionentity = $discussionvault->get_from_id($postentity->get_discussion_id());
        if (empty($discussionentity)) {
            throw new moodle_exception('notpartofdiscussion', 'peerforum');
        }
        $peerforumentity = $peerforumvault->get_from_id($discussionentity->get_peerforum_id());
        if (empty($peerforumentity)) {
            throw new moodle_exception('invalidpeerforumid', 'peerforum');
        }

        $context = $peerforumentity->get_context();
        self::validate_context($context);

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);

        if (!$capabilitymanager->can_edit_post($USER, $discussionentity, $postentity)) {
            throw new moodle_exception('noviewdiscussionspermission', 'peerforum');
        }

        if ($params['area'] == 'attachment') {
            $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
            $peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();
            $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);

            $areaoptions = mod_peerforum_post_form::attachment_options($peerforum);
            $messagetext = null;
        } else {
            $areaoptions = mod_peerforum_post_form::editor_options($context, $postentity->get_id());
            $messagetext = $postentity->get_message();
        }

        $draftitemid = empty($params['draftitemid']) ? 0 : $params['draftitemid'];
        $messagetext = file_prepare_draft_area($draftitemid, $context->id, 'mod_peerforum', $params['area'],
                $postentity->get_id(), $areaoptions, $messagetext);

        // Just get a structure compatible with external API.
        array_walk($areaoptions, function(&$item, $key) {
            $item = ['name' => $key, 'value' => $item];
        });

        // Do we need to keep only the given files?
        $usercontext = context_user::instance($USER->id);
        if (!empty($params['filestokeep'])) {
            $fs = get_file_storage();

            if ($areafiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid)) {
                $filestokeep = [];
                foreach ($params['filestokeep'] as $ftokeep) {
                    $filestokeep[$ftokeep['filepath']][$ftokeep['filename']] = $ftokeep;
                }

                foreach ($areafiles as $file) {
                    if ($file->is_directory()) {
                        continue;
                    }
                    if (!isset($filestokeep[$file->get_filepath()][$file->get_filename()])) {
                        $file->delete();    // Not in the list to be kept.
                    }
                }
            }
        }

        $result = array(
                'draftitemid' => $draftitemid,
                'files' => external_util::get_area_files($usercontext->id, 'user', 'draft',
                        $draftitemid),
                'areaoptions' => $areaoptions,
                'messagetext' => $messagetext,
                'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.8
     */
    public static function prepare_draft_area_for_post_returns() {
        return new external_single_structure(
                array(
                        'draftitemid' => new external_value(PARAM_INT, 'Draft item id for the file area.'),
                        'files' => new external_files('Draft area files.', VALUE_OPTIONAL),
                        'areaoptions' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'name' => new external_value(PARAM_RAW, 'Name of option.'),
                                                'value' => new external_value(PARAM_RAW, 'Value of option.'),
                                        )
                                ), 'Draft file area options.'
                        ),
                        'messagetext' => new external_value(PARAM_RAW, 'Message text with URLs rewritten.'),
                        'warnings' => new external_warnings(),
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function update_discussion_post_parameters() {
        return new external_function_parameters(
                [
                        'postid' => new external_value(PARAM_INT, 'Post to be updated. It can be a discussion topic post.'),
                        'subject' => new external_value(PARAM_TEXT, 'Updated post subject', VALUE_DEFAULT, ''),
                        'message' => new external_value(PARAM_RAW,
                                'Updated post message (HTML assumed if messageformat is not provided)',
                                VALUE_DEFAULT, ''),
                        'messageformat' => new external_format_value('message', VALUE_DEFAULT),
                        'options' => new external_multiple_structure (
                                new external_single_structure(
                                        [
                                                'name' => new external_value(
                                                        PARAM_ALPHANUM,
                                                        'The allowed keys (value format) are:
                                pinned (bool); (only for discussions) whether to pin this discussion or not
                                discussionsubscribe (bool); whether to subscribe to the post or not
                                inlineattachmentsid (int); the draft file area id for inline attachments in the text
                                attachmentsid (int); the draft file area id for attachments'
                                                ),
                                                'value' => new external_value(PARAM_RAW, 'The value of the option.')
                                        ]
                                ),
                                'Configuration options for the post.',
                                VALUE_DEFAULT,
                                []
                        ),
                ]
        );
    }

    /**
     * Updates a post or a discussion post topic.
     *
     * @param int $postid post to be updated, it can be a discussion topic post.
     * @param string $subject updated post subject
     * @param string $message updated post message (HTML assumed if messageformat is not provided)
     * @param int $messageformat The format of the message, defaults to FORMAT_HTML
     * @param array $options different configuration options for the post to be updated.
     * @return array of warnings and the status (true if the post/discussion was deleted)
     * @throws moodle_exception
     * @since Moodle 3.8
     * @todo support more options: timed posts, groups change and tags.
     */
    public static function update_discussion_post($postid, $subject = '', $message = '', $messageformat = FORMAT_HTML,
            $options = []) {
        global $CFG, $USER;
        require_once($CFG->dirroot . "/mod/peerforum/lib.php");

        $params = self::validate_parameters(self::add_discussion_post_parameters(),
                [
                        'postid' => $postid,
                        'subject' => $subject,
                        'message' => $message,
                        'options' => $options,
                        'messageformat' => $messageformat,
                ]
        );
        $warnings = [];

        // Validate options.
        $options = [];
        foreach ($params['options'] as $option) {
            $name = trim($option['name']);
            switch ($name) {
                case 'pinned':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'discussionsubscribe':
                    $value = clean_param($option['value'], PARAM_BOOL);
                    break;
                case 'inlineattachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    break;
                case 'attachmentsid':
                    $value = clean_param($option['value'], PARAM_INT);
                    break;
                default:
                    throw new moodle_exception('errorinvalidparam', 'webservice', '', $name);
            }
            $options[$name] = $value;
        }

        $managerfactory = mod_peerforum\local\container::get_manager_factory();
        $vaultfactory = mod_peerforum\local\container::get_vault_factory();
        $peerforumvault = $vaultfactory->get_peerforum_vault();
        $discussionvault = $vaultfactory->get_discussion_vault();
        $postvault = $vaultfactory->get_post_vault();
        $legacydatamapperfactory = mod_peerforum\local\container::get_legacy_data_mapper_factory();
        $peerforumdatamapper = $legacydatamapperfactory->get_peerforum_data_mapper();
        $discussiondatamapper = $legacydatamapperfactory->get_discussion_data_mapper();
        $postdatamapper = $legacydatamapperfactory->get_post_data_mapper();

        $postentity = $postvault->get_from_id($params['postid']);
        if (empty($postentity)) {
            throw new moodle_exception('invalidpostid', 'peerforum');
        }
        $discussionentity = $discussionvault->get_from_id($postentity->get_discussion_id());
        if (empty($discussionentity)) {
            throw new moodle_exception('notpartofdiscussion', 'peerforum');
        }
        $peerforumentity = $peerforumvault->get_from_id($discussionentity->get_peerforum_id());
        if (empty($peerforumentity)) {
            throw new moodle_exception('invalidpeerforumid', 'peerforum');
        }
        $peerforum = $peerforumdatamapper->to_legacy_object($peerforumentity);
        $capabilitymanager = $managerfactory->get_capability_manager($peerforumentity);

        $modcontext = $peerforumentity->get_context();
        self::validate_context($modcontext);

        if (!$capabilitymanager->can_edit_post($USER, $discussionentity, $postentity)) {
            throw new moodle_exception('cannotupdatepost', 'peerforum');
        }

        // Get the original post.
        $updatepost = $postdatamapper->to_legacy_object($postentity);
        $updatepost->itemid = IGNORE_FILE_MERGE;
        $updatepost->attachments = IGNORE_FILE_MERGE;

        // Prepare the post to be updated.
        if (!empty($params['subject'])) {
            $updatepost->subject = $params['subject'];
        }

        if (!empty($params['message']) && !empty($params['messageformat'])) {
            $updatepost->message = $params['message'];
            $updatepost->messageformat = $params['messageformat'];
            $updatepost->messagetrust = trusttext_trusted($modcontext);
            // Clean message text.
            $updatepost = trusttext_pre_edit($updatepost, 'message', $modcontext);
        }

        if (isset($options['discussionsubscribe'])) {
            // No need to validate anything here, peerforum_post_subscription will do.
            $updatepost->discussionsubscribe = $options['discussionsubscribe'];
        }

        // When editing first post/discussion.
        if (!$postentity->has_parent()) {
            // Defaults for discussion topic posts.
            $updatepost->name = $discussionentity->get_name();
            $updatepost->timestart = $discussionentity->get_time_start();
            $updatepost->timeend = $discussionentity->get_time_end();

            if (isset($options['pinned'])) {
                if ($capabilitymanager->can_pin_discussions($USER)) {
                    // Can change pinned if we have capability.
                    $updatepost->pinned = !empty($options['pinned']) ? PEERFORUM_DISCUSSION_PINNED : PEERFORUM_DISCUSSION_UNPINNED;
                }
            }
        }

        if (isset($options['inlineattachmentsid'])) {
            $updatepost->itemid = $options['inlineattachmentsid'];
        }

        if (isset($options['attachmentsid']) && peerforum_can_create_attachment($peerforum, $modcontext)) {
            $updatepost->attachments = $options['attachmentsid'];
        }

        // Update the post.
        $fakemform = $updatepost->id;
        if (peerforum_update_post($updatepost, $fakemform)) {
            $discussion = $discussiondatamapper->to_legacy_object($discussionentity);

            peerforum_trigger_post_updated_event($updatepost, $discussion, $modcontext, $peerforum);

            peerforum_post_subscription(
                    $updatepost,
                    $peerforum,
                    $discussion
            );
            $status = true;
        } else {
            $status = false;
        }

        return [
                'status' => $status,
                'warnings' => $warnings,
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.8
     */
    public static function update_discussion_post_returns() {
        return new external_single_structure(
                [
                        'status' => new external_value(PARAM_BOOL, 'True if the post/discussion was updated, false otherwise.'),
                        'warnings' => new external_warnings()
                ]
        );
    }
}
