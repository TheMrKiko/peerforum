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
 * A URL factory for the peerforum.
 *
 * @package    mod_peerforum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\author as author_entity;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\entities\discussion as discussion_entity;
use mod_peerforum\local\entities\post as post_entity;
use mod_peerforum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use moodle_url;
use stored_file;
use user_picture;

require_once($CFG->dirroot . '/mod/peerforum/lib.php');

/**
 * A URL factory for the peerforum.
 *
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url {
    /** @var legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;

    /**
     * Constructor.
     *
     * @param legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory
     */
    public function __construct(legacy_data_mapper_factory $legacydatamapperfactory) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
    }

    /**
     * Get the course url from the given course id.
     *
     * @param int $courseid The course id
     * @return moodle_url
     */
    public function get_course_url_from_courseid(int $courseid): moodle_url {
        return new moodle_url('/course/view.php', [
                'id' => $courseid,
        ]);
    }

    /**
     * Get the course url from the given peerforum entity.
     *
     * @param peerforum_entity $peerforum The peerforum entity
     * @return moodle_url
     */
    public function get_course_url_from_peerforum(peerforum_entity $peerforum): moodle_url {
        return $this->get_course_url_from_courseid($peerforum->get_course_id());
    }

    /**
     * Get the create discussion url for the given peerforum.
     *
     * @param peerforum_entity $peerforum The peerforum entity
     * @return moodle_url
     */
    public function get_discussion_create_url(peerforum_entity $peerforum): moodle_url {
        return new moodle_url('/mod/peerforum/post.php', [
                'peerforum' => $peerforum->get_id(),
        ]);
    }

    /**
     * Get the view peerforum url for the given peerforum and optionally a page number.
     *
     * @param peerforum_entity $peerforum The peerforum entity
     * @param int|null $pageno The page number
     * @param int|null $sortorder The sorting order
     * @return moodle_url
     */
    public function get_peerforum_view_url_from_peerforum(peerforum_entity $peerforum, ?int $pageno = null,
            ?int $sortorder = null): moodle_url {

        return $this->get_peerforum_view_url_from_course_module_id($peerforum->get_course_module_record()->id, $pageno, $sortorder);
    }

    /**
     * Get the view peerforum url for the given course module id and optionally a page number.
     *
     * @param int $coursemoduleid The course module id
     * @param int|null $pageno The page number
     * @param int|null $sortorder The sorting order
     * @return moodle_url
     */
    public function get_peerforum_view_url_from_course_module_id(int $coursemoduleid, ?int $pageno = null,
            ?int $sortorder = null): moodle_url {

        $url = new moodle_url('/mod/peerforum/view.php', [
                'id' => $coursemoduleid,
        ]);

        if (null !== $pageno) {
            $url->param('p', $pageno);
        }

        if (null !== $sortorder) {
            $url->param('o', $sortorder);
        }

        return $url;
    }

    /**
     * Get the view discussion url from the given discussion id.
     *
     * @param int $discussionid The discussion id
     * @return moodle_url
     */
    public function get_discussion_view_url_from_discussion_id(int $discussionid): moodle_url {
        return new moodle_url('/mod/peerforum/discuss.php', [
                'd' => $discussionid
        ]);
    }

    /**
     * Get the view discussion url from the given discussion.
     *
     * @param discussion_entity $discussion The discussion
     * @return moodle_url
     */
    public function get_discussion_view_url_from_discussion(discussion_entity $discussion): moodle_url {
        return $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
    }

    /**
     * Get the url to view the first unread post in a discussion.
     *
     * @param discussion_entity $discussion The discussion
     * @return moodle_url
     */
    public function get_discussion_view_first_unread_post_url_from_discussion(discussion_entity $discussion) {
        $viewurl = $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
        $viewurl->set_anchor('unread');

        return $viewurl;
    }

    /**
     * Get the url to view the latest post in a discussion.
     *
     * @param discussion_entity $discussion The discussion
     * @param int|null $latestpost The id of the latest post
     * @return moodle_url
     */
    public function get_discussion_view_latest_post_url_from_discussion(discussion_entity $discussion, ?int $latestpost) {
        $viewurl = $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
        if (null === $latestpost) {
            return $viewurl;
        } else {
            return new moodle_url($viewurl, ['parent' => $latestpost]);
        }
    }

    /**
     * Get the url to view a discussion from a post.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_discussion_view_url_from_post(post_entity $post): moodle_url {
        return $this->get_discussion_view_url_from_discussion_id($post->get_discussion_id());
    }

    /**
     * Get the url to view a discussion from a discussion id and post id.
     *
     * @param int $discussionid The discussion id
     * @param int $postid The post id
     * @return moodle_url
     */
    public function get_view_post_url_from_post_id(int $discussionid, int $postid): moodle_url {
        $url = $this->get_discussion_view_url_from_discussion_id($discussionid);
        $url->set_anchor('p' . $postid);
        return $url;
    }

    /**
     * Get the url to view a post in the context of the rest of the discussion.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_view_post_url_from_post(post_entity $post): moodle_url {
        return $this->get_view_post_url_from_post_id($post->get_discussion_id(), $post->get_id());
    }

    /**
     * Get the url to view a post and it's replies in isolation without the rest of the
     * discussion.
     *
     * @param int $discussionid The discussion id
     * @param int $postid The post id
     * @return moodle_url
     */
    public function get_view_isolated_post_url_from_post_id(int $discussionid, int $postid): moodle_url {
        $url = $this->get_discussion_view_url_from_discussion_id($discussionid);
        $url->params(['parent' => $postid]);
        return $url;
    }

    /**
     * Get the url to view a post and it's replies in isolation without the rest of the
     * discussion.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_view_isolated_post_url_from_post(post_entity $post): moodle_url {
        return $this->get_view_isolated_post_url_from_post_id($post->get_discussion_id(), $post->get_id());
    }

    /**
     * Get the url to edit a post.
     *
     * @param peerforum_entity $peerforum The peerforum the post belongs to
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_edit_post_url_from_post(peerforum_entity $peerforum, post_entity $post): moodle_url {
        if ($peerforum->get_type() == 'single' && !$post->has_parent()) {
            return new moodle_url('/course/modedit.php', [
                    'update' => $peerforum->get_course_module_record()->id,
                    'sesskey' => sesskey(),
                    'return' => 1
            ]);
        } else {
            return new moodle_url('/mod/peerforum/post.php', [
                    'edit' => $post->get_id()
            ]);
        }
    }

    /**
     * Get the url to split a discussion at a post.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_split_discussion_at_post_url_from_post(post_entity $post): moodle_url {
        return new moodle_url('/mod/peerforum/post.php', [
                'prune' => $post->get_id()
        ]);
    }

    /**
     * Get the url to delete a post.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_delete_post_url_from_post(post_entity $post): moodle_url {
        return new moodle_url('/mod/peerforum/post.php', [
                'delete' => $post->get_id()
        ]);
    }

    /**
     * Get the url to reply to a post.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_reply_to_post_url_from_post(post_entity $post): moodle_url {
        return new moodle_url('/mod/peerforum/post.php#mformpeerforum', [
                'reply' => $post->get_id()
        ]);
    }

    /**
     * Get the url to export (see portfolios) a post.
     *
     * @param post_entity $post The post
     * @return moodle_url
     */
    public function get_export_post_url_from_post(post_entity $post): ?moodle_url {
        global $CFG;

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new \portfolio_add_button();
        $button->set_callback_options('peerforum_portfolio_caller', ['postid' => $post->get_id()], 'mod_peerforum');
        if ($post->has_attachments()) {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        }

        $url = $button->to_html(PORTFOLIO_ADD_MOODLE_URL);
        return $url ?: null;
    }

    /**
     * Get the url to mark a post as read.
     *
     * @param post_entity $post The post
     * @param int $displaymode The display mode to show the peerforum in after marking as read
     * @return moodle_url
     */
    public function get_mark_post_as_read_url_from_post(post_entity $post, int $displaymode = PEERFORUM_MODE_THREADED): moodle_url {
        $params = [
                'd' => $post->get_discussion_id(),
                'postid' => $post->get_id(),
                'mark' => 'read'
        ];

        $url = new moodle_url('/mod/peerforum/discuss.php', $params);

        if ($displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->get_parent_id());
        } else {
            $url->set_anchor('p' . $post->get_id());
        }

        return $url;
    }

    /**
     * Get the url to mark a post as unread.
     *
     * @param post_entity $post The post
     * @param int $displaymode The display mode to show the peerforum in after marking as unread
     * @return moodle_url
     */
    public function get_mark_post_as_unread_url_from_post(post_entity $post,
            int $displaymode = PEERFORUM_MODE_THREADED): moodle_url {
        $params = [
                'd' => $post->get_discussion_id(),
                'postid' => $post->get_id(),
                'mark' => 'unread'
        ];

        $url = new moodle_url('/mod/peerforum/discuss.php', $params);

        if ($displaymode == PEERFORUM_MODE_THREADED) {
            $url->param('parent', $post->get_parent_id());
        } else {
            $url->set_anchor('p' . $post->get_id());
        }

        return $url;
    }

    /**
     * Get the url to export attachments for a post.
     *
     * @param post_entity $post The post
     * @param stored_file $attachment
     * @return moodle_url|null
     */
    public function get_export_attachment_url_from_post_and_attachment(post_entity $post, stored_file $attachment): ?moodle_url {
        global $CFG;

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new \portfolio_add_button();
        $button->set_callback_options(
                'peerforum_portfolio_caller',
                ['postid' => $post->get_id(), 'attachment' => $attachment->get_id()],
                'mod_peerforum'
        );
        $button->set_format_by_file($attachment);
        $url = $button->to_html(PORTFOLIO_ADD_MOODLE_URL);
        return $url ?: null;
    }

    /**
     * Get the url to view an author's profile.
     *
     * @param author_entity $author The author
     * @param int $courseid The course id
     * @return moodle_url
     */
    public function get_author_profile_url(author_entity $author, int $courseid): moodle_url {
        return new moodle_url('/user/view.php', [
                'id' => $author->get_id(),
                'course' => $courseid
        ]);
    }

    /**
     * Get the url to view the author's profile image. The author's context id should be
     * provided to prevent the code from needing to load it.
     *
     * @param author_entity $author The author
     * @param int|null $authorcontextid The author context id
     * @param int $size The size of the image to return
     * @return moodle_url
     */
    public function get_author_profile_image_url(
            author_entity $author,
            int $authorcontextid = null,
            int $size = 100
    ): moodle_url {
        global $PAGE;

        $datamapper = $this->legacydatamapperfactory->get_author_data_mapper();
        $record = $datamapper->to_legacy_object($author);
        $record->contextid = $authorcontextid;
        $userpicture = new user_picture($record);
        $userpicture->size = $size;

        return $userpicture->get_url($PAGE);
    }

    /**
     * Get the url to view an author's group.
     *
     * @param \stdClass $group The group
     * @return moodle_url
     */
    public function get_author_group_url(\stdClass $group): moodle_url {
        return new moodle_url('/user/index.php', [
                'id' => $group->courseid,
                'group' => $group->id
        ]);
    }

    /**
     * Get the url to mark a discussion as read.
     *
     * @param peerforum_entity $peerforum The peerforum that the discussion belongs to
     * @param discussion_entity $discussion The discussion
     * @return moodle_url
     */
    public function get_mark_discussion_as_read_url_from_discussion(
            peerforum_entity $peerforum,
            discussion_entity $discussion
    ): moodle_url {
        return new moodle_url('/mod/peerforum/markposts.php', [
                'f' => $discussion->get_peerforum_id(),
                'd' => $discussion->get_id(),
                'mark' => 'read',
                'sesskey' => sesskey(),
                'return' => $this->get_peerforum_view_url_from_peerforum($peerforum)->out(),
        ]);
    }

    /**
     * Get the url to mark all discussions as read.
     *
     * @param peerforum_entity $peerforum The peerforum that the discussions belong to
     * @return moodle_url
     */
    public function get_mark_all_discussions_as_read_url(peerforum_entity $peerforum): moodle_url {
        return new moodle_url('/mod/peerforum/markposts.php', [
                'f' => $peerforum->get_id(),
                'mark' => 'read',
                'sesskey' => sesskey(),
                'return' => $this->get_peerforum_view_url_from_peerforum($peerforum)->out(),
        ]);
    }

    /**
     * Get the url to subscribe to a discussion.
     *
     * @param discussion_entity $discussion The discussion
     * @return moodle_url
     */
    public function get_discussion_subscribe_url(discussion_entity $discussion): moodle_url {
        return new moodle_url('/mod/peerforum/subscribe.php', [
                'sesskey' => sesskey(),
                'id' => $discussion->get_peerforum_id(),
                'd' => $discussion->get_id()
        ]);
    }

    /**
     * Generate the pinned discussion link
     *
     * @param discussion_entity $discussion
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_pin_discussion_url_from_discussion(discussion_entity $discussion): moodle_url {
        return new moodle_url('discuss.php', [
                'sesskey' => sesskey(),
                'd' => $discussion->get_id(),
                'pin' => $discussion->is_pinned() ? PEERFORUM_DISCUSSION_UNPINNED : PEERFORUM_DISCUSSION_PINNED
        ]);
    }

    /**
     * Generate the training link.
     *
     * @param \stdClass $trainingpage
     * @param null $submitid
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_training_url(\stdClass $trainingpage, $submitid = null): moodle_url {
        $submit = ($submitid) ? array('submitid' => $submitid) : array();
        return new moodle_url('/mod/peerforum/training.php', array_merge([
                'page' => $trainingpage->id
        ], $submit));
    }

    /**
     * Generate the edit training page link.
     *
     * @param \stdClass $trainingpage
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_training_edit_url(\stdClass $trainingpage): moodle_url {
        return new moodle_url('/mod/peerforum/buildtraining.php', [
                'edit' => $trainingpage->id
        ]);
    }

    /**
     * Generate the training new page link.
     *
     * @param peerforum_entity $peerforum
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_training_new_url(peerforum_entity $peerforum): moodle_url {
        return new moodle_url('/mod/peerforum/buildtraining.php', [
                'peerforum' => $peerforum->get_id()
        ]);
    }

    /**
     * Generate the delete training page link.
     *
     * @param \stdClass $trainingpage
     * @param int $deltype
     * @param int $delid
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_training_delete_url(\stdClass $trainingpage, int $deltype = 0, int $delid = 0): moodle_url {
        return new moodle_url('/mod/peerforum/buildtraining.php', [
                'delete' => $trainingpage->id,
                'deltype' => $deltype,
                'delid' => $delid,
        ]);
    }

    /**
     * Generate the training manager link.
     *
     * @param peerforum_entity $peerforum
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_training_manager_url(peerforum_entity $peerforum): moodle_url {
        return new moodle_url('/mod/peerforum/trainingpages.php', [
                'pf' => $peerforum->get_id()
        ]);
    }

    /**
     * Generate the nominations form link.
     *
     * @param peerforum_entity $peerforum
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_nominations_url(peerforum_entity $peerforum): moodle_url {
        return new moodle_url('/mod/peerforum/nominations.php', [
                'peerforum' => $peerforum->get_id()
        ]);
    }

    /**
     * Generate the user summary link.
     *
     * @param \stdClass $userinfo
     * @param int $courseid
     * @param int $display
     * @return moodle_url
     */
    public function get_user_summary_url(\stdClass $userinfo, $courseid = 0, $display = MANAGEPOSTS_MODE_SEEALL): moodle_url {
        return new moodle_url('/blocks/peerblock/summary.php',
                array(
                        'display' => $display,
                        'courseid' => $courseid,
                        'userid' => $userinfo->id)
        );
    }
}
