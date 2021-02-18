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
 * Exported post builder class.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\builders;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\discussion as discussion_entity;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\entities\post as post_entity;
use mod_peerforum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_peerforum\local\factories\exporter as exporter_factory;
use mod_peerforum\local\factories\vault as vault_factory;
use context;
use core_tag_tag;
use moodle_exception;
use rating_manager;
use peergrade_manager;
use renderer_base;
use stdClass;

/**
 * Exported post builder class.
 *
 * This class is an implementation of the builder pattern (loosely). It is responsible
 * for taking a set of related peerforums, discussions, and posts and generate the exported
 * version of the posts.
 *
 * It encapsulates the complexity involved with exporting posts. All of the relevant
 * additional resources will be loaded by this class in order to ensure the exporting
 * process can happen.
 *
 * See this doc for more information on the builder pattern:
 * https://designpatternsphp.readthedocs.io/en/latest/Creational/Builder/README.html
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exported_posts {
    /** @var renderer_base $renderer Core renderer */
    private $renderer;

    /** @var legacy_data_mapper_factory $legacydatamapperfactory Data mapper factory */
    private $legacydatamapperfactory;

    /** @var exporter_factory $exporterfactory Exporter factory */
    private $exporterfactory;

    /** @var vault_factory $vaultfactory Vault factory */
    private $vaultfactory;

    /** @var rating_manager $ratingmanager Rating manager */
    private $ratingmanager;

    /** @var peergrade_manager $peergrademanager peergrade manager */
    private $peergrademanager;

    /**
     * Constructor.
     *
     * @param renderer_base $renderer Core renderer
     * @param legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory
     * @param exporter_factory $exporterfactory Exporter factory
     * @param vault_factory $vaultfactory Vault factory
     * @param rating_manager $ratingmanager Rating manager
     * @param peergrade_manager $peergrademanager Peergrade manager
     */
    public function __construct(
            renderer_base $renderer,
            legacy_data_mapper_factory $legacydatamapperfactory,
            exporter_factory $exporterfactory,
            vault_factory $vaultfactory,
            rating_manager $ratingmanager,
            peergrade_manager $peergrademanager
    ) {
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->ratingmanager = $ratingmanager;
        $this->peergrademanager = $peergrademanager;
    }

    /**
     * Build the exported posts for a given set of peerforums, discussions, and posts.
     *
     * This will typically be used for a list of posts in the same discussion/peerforum however
     * it does support exporting any arbitrary list of posts as long as the caller also provides
     * a unique list of all discussions for the list of posts and all peerforums for the list of discussions.
     *
     * Increasing the number of different peerforums being processed will increase the processing time
     * due to processing multiple contexts (for things like capabilities, files, etc). The code attempts
     * to load the additional resources as efficiently as possible but there is no way around some of
     * the additional overhead.
     *
     * Note: Some posts will be removed as part of the build process according to capabilities.
     * A one-to-one mapping should not be expected.
     *
     * @param stdClass $user The user to export the posts for.
     * @param peerforum_entity[] $peerforums A list of all peerforums that each of the $discussions belong to
     * @param discussion_entity[] $discussions A list of all discussions that each of the $posts belong to
     * @param post_entity[] $posts The list of posts to export.
     * @return stdClass[] List of exported posts in the same order as the $posts array.
     */
    public function build(
            stdClass $user,
            array $peerforums,
            array $discussions,
            array $posts
    ): array {
        // Format the peerforums and discussion to make them more easily accessed later.
        $peerforums = array_reduce($peerforums, function($carry, $peerforum) {
            $carry[$peerforum->get_id()] = $peerforum;
            return $carry;
        }, []);
        $discussions = array_reduce($discussions, function($carry, $discussion) {
            $carry[$discussion->get_id()] = $discussion;
            return $carry;
        }, []);

        // Group the posts by discussion and peerforum so that we can load the resources in
        // batches to improve performance.
        $groupedposts = $this->group_posts_by_discussion($peerforums, $discussions, $posts);
        // Load all of the resources we need in order to export the posts.
        $authorsbyid = $this->get_authors_for_posts($posts);
        $authorcontextids = $this->get_author_context_ids(array_keys($authorsbyid));
        $attachmentsbypostid = $this->get_attachments_for_posts($groupedposts);
        $groupsbycourseandauthorid = $this->get_author_groups_from_posts($groupedposts);
        $tagsbypostid = $this->get_tags_from_posts($posts);
        $ratingbypostid = $this->get_ratings_from_posts($user, $groupedposts);
        $peergradebypostid = $this->get_peergrades_from_posts($user, $groupedposts);
        $readreceiptcollectionbypeerforumid = $this->get_read_receipts_from_posts($user, $groupedposts);
        $exportedposts = [];

        // Export each set of posts per discussion because it's the largest chunks we can
        // break them into due to constraints on capability checks.
        foreach ($groupedposts as $grouping) {
            [
                    'peerforum' => $peerforum,
                    'discussion' => $discussion,
                    'posts' => $groupedposts
            ] = $grouping;

            $peerforumid = $peerforum->get_id();
            $courseid = $peerforum->get_course_record()->id;
            $postsexporter = $this->exporterfactory->get_posts_exporter(
                    $user,
                    $peerforum,
                    $discussion,
                    $groupedposts,
                    $authorsbyid,
                    $authorcontextids,
                    $attachmentsbypostid,
                    $groupsbycourseandauthorid[$courseid],
                    $readreceiptcollectionbypeerforumid[$peerforumid] ?? null,
                    $tagsbypostid,
                    $ratingbypostid,
                    $peergradebypostid,
                    true
            );
            ['posts' => $exportedgroupedposts] = (array) $postsexporter->export($this->renderer);
            $exportedposts = array_merge($exportedposts, $exportedgroupedposts);
        }

        if (count($peerforums) == 1 && count($discussions) == 1) {
            // All of the posts belong to a single discussion in a single peerforum so
            // the exported order will match the given $posts array.
            return $exportedposts;
        } else {
            // Since we grouped the posts by discussion and peerforum the ordering of the
            // exported posts may be different to the given $posts array so we should
            // sort it back into the correct order for the caller.
            return $this->sort_exported_posts($posts, $exportedposts);
        }
    }

    /**
     * Group the posts by which discussion they belong to in order for them to be processed
     * in chunks by the exporting.
     *
     * Returns a list of groups where each group has a peerforum, discussion, and list of posts.
     * E.g.
     * [
     *      [
     *          'peerforum' => <peerforum_entity>,
     *          'discussion' => <discussion_entity>,
     *          'posts' => [
     *              <post_entity in discussion>,
     *              <post_entity in discussion>,
     *              <post_entity in discussion>
     *          ]
     *      ]
     * ]
     *
     * @param peerforum_entity[] $peerforums A list of all peerforums that each of the $discussions belong to, indexed by id.
     * @param discussion_entity[] $discussions A list of all discussions that each of the $posts belong to, indexed by id.
     * @param post_entity[] $posts The list of posts to process.
     * @return array List of grouped posts. Each group has a discussion, peerforum, and posts.
     */
    private function group_posts_by_discussion(array $peerforums, array $discussions, array $posts): array {
        return array_reduce($posts, function($carry, $post) use ($peerforums, $discussions) {
            $discussionid = $post->get_discussion_id();
            if (!isset($discussions[$discussionid])) {
                throw new moodle_exception('Unable to find discussion with id ' . $discussionid);
            }

            if (isset($carry[$discussionid])) {
                $carry[$discussionid]['posts'][] = $post;
            } else {
                $discussion = $discussions[$discussionid];
                $peerforumid = $discussion->get_peerforum_id();

                if (!isset($peerforums[$peerforumid])) {
                    throw new moodle_exception('Unable to find peerforum with id ' . $peerforumid);
                }

                $carry[$discussionid] = [
                        'peerforum' => $peerforums[$peerforumid],
                        'discussion' => $discussions[$discussionid],
                        'posts' => [$post]
                ];
            }

            return $carry;
        }, []);
    }

    /**
     * Load the list of authors for the given posts.
     *
     * The list of authors will be indexed by the author id.
     *
     * @param post_entity[] $posts The list of posts to process.
     * @return author_entity[]
     */
    private function get_authors_for_posts(array $posts): array {
        $authorvault = $this->vaultfactory->get_author_vault();
        return $authorvault->get_authors_for_posts($posts);
    }

    /**
     * Get the user context ids for each of the authors.
     *
     * @param int[] $authorids The list of author ids to fetch context ids for.
     * @return int[] Context ids indexed by author id
     */
    private function get_author_context_ids(array $authorids): array {
        $authorvault = $this->vaultfactory->get_author_vault();
        return $authorvault->get_context_ids_for_author_ids($authorids);
    }

    /**
     * Load the list of all attachments for the posts. The list of attachments will be
     * indexed by the post id.
     *
     * @param array $groupedposts List of posts grouped by discussions.
     * @return stored_file[]
     */
    private function get_attachments_for_posts(array $groupedposts): array {
        $attachmentsbypostid = [];
        $postattachmentvault = $this->vaultfactory->get_post_attachment_vault();
        $postsbypeerforum = array_reduce($groupedposts, function($carry, $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;

            $peerforumid = $peerforum->get_id();
            if (!isset($carry[$peerforumid])) {
                $carry[$peerforumid] = [
                        'peerforum' => $peerforum,
                        'posts' => []
                ];
            }

            $carry[$peerforumid]['posts'] = array_merge($carry[$peerforumid]['posts'], $posts);
            return $carry;
        }, []);

        foreach ($postsbypeerforum as $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;
            $attachments = $postattachmentvault->get_attachments_for_posts($peerforum->get_context(), $posts);

            // Have to loop in order to maintain the correct indexes since they are numeric.
            foreach ($attachments as $postid => $attachment) {
                $attachmentsbypostid[$postid] = $attachment;
            }
        }

        return $attachmentsbypostid;
    }

    /**
     * Get the groups for each author of the given posts.
     *
     * The results are grouped by course and then author id because the groups are
     * contextually related to the course, e.g. a single author can be part of two different
     * sets of groups in two different courses.
     *
     * @param array $groupedposts List of posts grouped by discussions.
     * @return array List of groups indexed by peerforum id and then author id.
     */
    private function get_author_groups_from_posts(array $groupedposts): array {
        $groupsbyauthorid = [];
        $authoridsbycourseid = [];

        // Get the unique list of author ids for each course in the grouped
        // posts. Grouping by course is the largest grouping we can achieve.
        foreach ($groupedposts as $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;
            $course = $peerforum->get_course_record();
            $courseid = $course->id;

            if (!isset($authoridsbycourseid[$courseid])) {
                $coursemodule = $peerforum->get_course_module_record();
                $authoridsbycourseid[$courseid] = [
                        'groupingid' => $coursemodule->groupingid,
                        'authorids' => []
                ];
            }

            $authorids = array_map(function($post) {
                return $post->get_author_id();
            }, $posts);

            foreach ($authorids as $authorid) {
                $authoridsbycourseid[$courseid]['authorids'][$authorid] = $authorid;
            }
        }

        // Load each set of groups per course.
        foreach ($authoridsbycourseid as $courseid => $values) {
            ['groupingid' => $groupingid, 'authorids' => $authorids] = $values;
            $authorgroups = groups_get_all_groups(
                    $courseid,
                    array_keys($authorids),
                    $groupingid,
                    'g.*, gm.id, gm.groupid, gm.userid'
            );

            if (!isset($groupsbyauthorid[$courseid])) {
                $groupsbyauthorid[$courseid] = [];
            }

            foreach ($authorgroups as $group) {
                // Clean up data returned from groups_get_all_groups.
                $userid = $group->userid;
                $groupid = $group->groupid;

                unset($group->userid);
                unset($group->groupid);
                $group->id = $groupid;

                if (!isset($groupsbyauthorid[$courseid][$userid])) {
                    $groupsbyauthorid[$courseid][$userid] = [];
                }

                $groupsbyauthorid[$courseid][$userid][] = $group;
            }
        }

        return $groupsbyauthorid;
    }

    /**
     * Get the list of tags for each of the posts. The tags will be returned in an
     * array indexed by the post id.
     *
     * @param post_entity[] $posts The list of posts to load tags for.
     * @return array Sets of tags indexed by post id.
     */
    private function get_tags_from_posts(array $posts): array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);
        return core_tag_tag::get_items_tags('mod_peerforum', 'peerforum_posts', $postids);
    }

    /**
     * Get the list of ratings for each post. The ratings are returned in an array
     * indexed by the post id.
     *
     * @param stdClass $user The user viewing the ratings.
     * @param array $groupedposts List of posts grouped by discussions.
     * @return array Sets of ratings indexed by post id.
     */
    private function get_ratings_from_posts(stdClass $user, array $groupedposts) {
        $ratingsbypostid = [];
        $postsdatamapper = $this->legacydatamapperfactory->get_post_data_mapper();
        $postsbypeerforum = array_reduce($groupedposts, function($carry, $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;

            $peerforumid = $peerforum->get_id();
            if (!isset($carry[$peerforumid])) {
                $carry[$peerforumid] = [
                        'peerforum' => $peerforum,
                        'posts' => []
                ];
            }

            $carry[$peerforumid]['posts'] = array_merge($carry[$peerforumid]['posts'], $posts);
            return $carry;
        }, []);

        foreach ($postsbypeerforum as $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;

            if (!$peerforum->has_rating_aggregate()) {
                continue;
            }

            $items = $postsdatamapper->to_legacy_objects($posts);
            $ratingoptions = (object) [
                    'context' => $peerforum->get_context(),
                    'component' => 'mod_peerforum',
                    'ratingarea' => 'post',
                    'items' => $items,
                    'aggregate' => $peerforum->get_rating_aggregate(),
                    'scaleid' => $peerforum->get_scale(),
                    'userid' => $user->id,
                    'assesstimestart' => $peerforum->get_assess_time_start(),
                    'assesstimefinish' => $peerforum->get_assess_time_finish()
            ];

            $rm = $this->ratingmanager;
            $items = $rm->get_ratings($ratingoptions);

            foreach ($items as $item) {
                $ratingsbypostid[$item->id] = empty($item->rating) ? null : $item->rating;
            }
        }

        return $ratingsbypostid;
    }

    /**
     * Get the list of peergrades for each post. The peergrades are returned in an array
     * indexed by the post id.
     *
     * @param stdClass $user The user viewing the peergrades.
     * @param array $groupedposts List of posts grouped by discussions.
     * @return array Sets of peergrades indexed by post id.
     */
    private function get_peergrades_from_posts(stdClass $user, array $groupedposts) {
        $peergradesbypostid = [];
        $postsdatamapper = $this->legacydatamapperfactory->get_post_data_mapper();
        $postsbypeerforum = array_reduce($groupedposts, function($carry, $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;

            $peerforumid = $peerforum->get_id();
            if (!isset($carry[$peerforumid])) {
                $carry[$peerforumid] = [
                        'peerforum' => $peerforum,
                        'posts' => []
                ];
            }

            $carry[$peerforumid]['posts'] = array_merge($carry[$peerforumid]['posts'], $posts);
            return $carry;
        }, []);

        foreach ($postsbypeerforum as $grouping) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $grouping;

            if (!$peerforum->has_peergrade_aggregate()) {
                continue;
            }

            $items = $postsdatamapper->to_legacy_objects($posts);
            $peergradeoptions = (object) ([
                    'items' => $items,
                    'userid' => $user->id,
            ] + $peerforum->get_peergrade_options());

            $rm = $this->peergrademanager;
            $items = $rm->get_peergrades($peergradeoptions);

            foreach ($items as $item) {
                $peergradesbypostid[$item->id] = empty($item->peergrade) ? null : $item->peergrade;
            }
        }

        return $peergradesbypostid;
    }

    /**
     * Get the read receipt collections for the given viewing user and each peerforum. The
     * receipt collections will only be loaded for posts in peerforums that the user is tracking.
     *
     * The receipt collections are returned in an array indexed by the peerforum ids.
     *
     * @param stdClass $user The user viewing the posts.
     * @param array $groupedposts List of posts grouped by discussions.
     */
    private function get_read_receipts_from_posts(stdClass $user, array $groupedposts) {
        $peerforumdatamapper = $this->legacydatamapperfactory->get_peerforum_data_mapper();
        $trackedpeerforums = [];
        $trackedpostids = [];

        foreach ($groupedposts as $group) {
            ['peerforum' => $peerforum, 'posts' => $posts] = $group;
            $peerforumid = $peerforum->get_id();

            if (!isset($trackedpeerforums[$peerforumid])) {
                $peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);
                $trackedpeerforums[$peerforumid] = peerforum_tp_is_tracked($peerforumrecord, $user);
            }

            if ($trackedpeerforums[$peerforumid]) {
                foreach ($posts as $post) {
                    $trackedpostids[] = $post->get_id();
                }
            }
        }

        if (empty($trackedpostids)) {
            return [];
        }

        // We can just load a single receipt collection for all tracked posts.
        $receiptvault = $this->vaultfactory->get_post_read_receipt_collection_vault();
        $readreceiptcollection = $receiptvault->get_from_user_id_and_post_ids($user->id, $trackedpostids);
        $receiptsbypeerforumid = [];

        // Assign the collection to all peerforums that are tracked.
        foreach ($trackedpeerforums as $peerforumid => $tracked) {
            if ($tracked) {
                $receiptsbypeerforumid[$peerforumid] = $readreceiptcollection;
            }
        }

        return $receiptsbypeerforumid;
    }

    /**
     * Sort the list of exported posts back into the same order as the given posts.
     * The ordering of the exported posts can often deviate from the given posts due
     * to the process of exporting them so we need to sort them back into the order
     * that the calling code expected.
     *
     * @param post_entity[] $posts The posts in the expected order.
     * @param stdClass[] $exportedposts The list of exported posts in any order.
     * @return stdClass[] Sorted exported posts.
     */
    private function sort_exported_posts(array $posts, array $exportedposts) {
        $postindexes = [];
        foreach (array_values($posts) as $index => $post) {
            $postindexes[$post->get_id()] = $index;
        }

        $sortedexportedposts = [];

        foreach ($exportedposts as $exportedpost) {
            $index = $postindexes[$exportedpost->id];
            $sortedexportedposts[$index] = $exportedpost;
        }

        return $sortedexportedposts;
    }
}
