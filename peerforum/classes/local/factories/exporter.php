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
 * PeerForum Exporter factory.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\discussion as discussion_entity;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\entities\post as post_entity;
use mod_peerforum\local\entities\post_read_receipt_collection as post_read_receipt_collection_entity;
use mod_peerforum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_peerforum\local\factories\manager as manager_factory;
use mod_peerforum\local\factories\url as url_factory;
use mod_peerforum\local\factories\vault as vault_factory;
use mod_peerforum\local\exporters\peerforum as peerforum_exporter;
use mod_peerforum\local\exporters\discussion as discussion_exporter;
use mod_peerforum\local\exporters\discussion_summaries as discussion_summaries_exporter;
use mod_peerforum\local\exporters\post as post_exporter;
use mod_peerforum\local\exporters\posts as posts_exporter;
use context;
use rating;
use peergrade;
use stdClass;

/**
 * The exporter factory class used to fetch an instance of the different exporter types.
 *
 * See:
 * https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter {
    /** @var legacy_data_mapper_factory The factory to fetch a legacy data mapper */
    private $legacydatamapperfactory;

    /** @var manager_factory The factory to fetch a new manager */
    private $managerfactory;

    /** @var url_factory The factory to create urls */
    private $urlfactory;

    /** @var vault_factory The vault factory */
    private $vaultfactory;

    /**
     * Constructor for the exporter factory.
     *
     * @param legacy_data_mapper_factory $legacydatamapperfactory The factory to fetch a legacy data mapper instance
     * @param manager_factory $managerfactory The factory fo fetch a manager instance
     * @param url_factory $urlfactory The factory to create urls
     * @param vault_factory $vaultfactory The vault factory
     */
    public function __construct(legacy_data_mapper_factory $legacydatamapperfactory, manager_factory $managerfactory,
            url_factory $urlfactory, vault_factory $vaultfactory) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->managerfactory = $managerfactory;
        $this->urlfactory = $urlfactory;
        $this->vaultfactory = $vaultfactory;
    }

    /**
     * Construct a new peerforum exporter for the specified user and peerforum.
     *
     * @param stdClass $user The user viewing the peerforum
     * @param peerforum_entity $peerforum The peerforum being viewed
     * @param int $currentgroup The group currently being viewed
     * @return  peerforum_exporter
     */
    public function get_peerforum_exporter(
            stdClass $user,
            peerforum_entity $peerforum,
            ?int $currentgroup
    ): peerforum_exporter {
        return new peerforum_exporter($peerforum, [
                'legacydatamapperfactory' => $this->legacydatamapperfactory,
                'capabilitymanager' => $this->managerfactory->get_capability_manager($peerforum),
                'urlfactory' => $this->urlfactory,
                'user' => $user,
                'currentgroup' => $currentgroup,
                'vaultfactory' => $this->vaultfactory,
        ]);
    }

    /**
     * Fetch the structure of the peerforum exporter.
     *
     * @return  array
     */
    public static function get_peerforum_export_structure(): array {
        return peerforum_exporter::read_properties_definition();
    }

    /**
     * Construct a new discussion exporter for the specified user and peerforum discussion.
     *
     * @param stdClass $user The user viewing the peerforum
     * @param peerforum_entity $peerforum The peerforum being viewed
     * @param discussion_entity $discussion The discussion being viewed
     * @param stdClass[] $groupsbyid The list of groups in the peerforum
     * @return  discussion_exporter
     */
    public function get_discussion_exporter(
            stdClass $user,
            peerforum_entity $peerforum,
            discussion_entity $discussion,
            array $groupsbyid = [],
            array $favouriteids = []
    ): discussion_exporter {
        return new discussion_exporter($discussion, [
                'context' => $peerforum->get_context(),
                'peerforum' => $peerforum,
                'capabilitymanager' => $this->managerfactory->get_capability_manager($peerforum),
                'urlfactory' => $this->urlfactory,
                'user' => $user,
                'legacydatamapperfactory' => $this->legacydatamapperfactory,
                'latestpostid' => null,
                'groupsbyid' => $groupsbyid,
                'favouriteids' => $favouriteids
        ]);
    }

    /**
     * Fetch the structure of the discussion exporter.
     *
     * @return  array
     */
    public static function get_discussion_export_structure() {
        return discussion_exporter::read_properties_definition();
    }

    /**
     * Construct a new discussion summaries exporter for the specified user and set of discussions.
     *
     * @param stdClass $user The user viewing the peerforum
     * @param peerforum_entity $peerforum The peerforum being viewed
     * @param discussion_summary_entity[] $discussions The set of discussion summaries to export
     * @param stdClass[] $groupsbyauthorid The set of groups in an associative array for each author
     * @param stdClass[] $groupsbyid The set of groups in the peerforum in an associative array for each group
     * @param int[] $discussionreplycount The number of replies for each discussion
     * @param int[] $discussionunreadcount The number of unread posts for each discussion
     * @param int[] $latestpostids The latest post id for each discussion
     * @param int[] $postauthorcontextids The context ids for the first and last post authors (indexed by author id)
     * @param int[] $favourites The list of discussion ids that have been favourited
     * @return  discussion_summaries_exporter
     */
    public function get_discussion_summaries_exporter(
            stdClass $user,
            peerforum_entity $peerforum,
            array $discussions,
            array $groupsbyid = [],
            array $groupsbyauthorid = [],
            array $discussionreplycount = [],
            array $discussionunreadcount = [],
            array $latestpostids = [],
            array $postauthorcontextids = [],
            array $favourites = [],
            array $latestauthors = [],
            array $trainingpagesbydiscussionid = []
    ): discussion_summaries_exporter {
        return new discussion_summaries_exporter(
                $discussions,
                $groupsbyid,
                $groupsbyauthorid,
                $discussionreplycount,
                $discussionunreadcount,
                $latestpostids,
                $postauthorcontextids,
                [
                        'legacydatamapperfactory' => $this->legacydatamapperfactory,
                        'context' => $peerforum->get_context(),
                        'peerforum' => $peerforum,
                        'capabilitymanager' => $this->managerfactory->get_capability_manager($peerforum),
                        'urlfactory' => $this->urlfactory,
                        'user' => $user,
                        'favouriteids' => $favourites,
                        'latestauthors' => $latestauthors,
                        'trainingpagesbydiscussionid' => $trainingpagesbydiscussionid
                ]
        );
    }

    /**
     * Fetch the structure of the discussion summaries exporter.
     *
     * @return  array
     */
    public static function get_discussion_summaries_export_structure() {
        return discussion_summaries_exporter::read_properties_definition();
    }

    /**
     * Construct a new post exporter for the specified user and set of post.
     *
     * @param stdClass $user The user viewing the peerforum
     * @param peerforum_entity $peerforum The peerforum being viewed
     * @param discussion_entity $discussion The discussion that the post is in
     * @param post_entity[] $posts The set of posts to be exported
     * @param author_entity[] $authorsbyid List of authors indexed by author id
     * @param int[] $authorcontextids List of authors context ids indexed by author id
     * @param array $attachmentsbypostid List of attachments for each post indexed by post id
     * @param array $groupsbyauthorid List of groups for the post authors indexed by author id
     * @param post_read_receipt_collection_entity|null $readreceiptcollection Details of read receipts for each post
     * @param array $tagsbypostid List of tags for each post indexed by post id
     * @param rating[] $ratingbypostid List of ratings for each post indexed by post id
     * @param peergrade[] $peergradebypostid List of peergrades for each post indexed by post id
     * @param bool $includehtml Include some pre-constructed HTML in the export
     * @return  post_exporter
     */
    public function get_posts_exporter(
            stdClass $user,
            peerforum_entity $peerforum,
            discussion_entity $discussion,
            array $posts,
            array $authorsbyid = [],
            array $authorcontextids = [],
            array $attachmentsbypostid = [],
            array $groupsbyauthorid = [],
            post_read_receipt_collection_entity $readreceiptcollection = null,
            array $tagsbypostid = [],
            array $ratingbypostid = [],
            array $peergradebypostid = [],
            bool $includehtml = false
    ): posts_exporter {
        return new posts_exporter(
                $posts,
                $authorsbyid,
                $authorcontextids,
                $attachmentsbypostid,
                $groupsbyauthorid,
                $tagsbypostid,
                $ratingbypostid,
                $peergradebypostid,
                [
                        'capabilitymanager' => $this->managerfactory->get_capability_manager($peerforum),
                        'urlfactory' => $this->urlfactory,
                        'peerforum' => $peerforum,
                        'discussion' => $discussion,
                        'user' => $user,
                        'context' => $peerforum->get_context(),
                        'readreceiptcollection' => $readreceiptcollection,
                        'includehtml' => $includehtml
                ]
        );
    }

    /**
     * Fetch the structure of the posts exporter.
     *
     * @return  array
     */
    public static function get_posts_export_structure() {
        return posts_exporter::read_properties_definition();
    }
}
