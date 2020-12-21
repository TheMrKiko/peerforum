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
 * Discussion list renderer.
 *
 * @package    mod_peerforum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\grades\peerforum_gradeitem;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_peerforum\local\factories\exporter as exporter_factory;
use mod_peerforum\local\factories\vault as vault_factory;
use mod_peerforum\local\factories\url as url_factory;
use mod_peerforum\local\managers\capability as capability_manager;
use mod_peerforum\local\vaults\discussion_list as discussion_list_vault;
use renderer_base;
use stdClass;
use core\output\notification;
use mod_peerforum\local\factories\builder as builder_factory;

require_once($CFG->dirroot . '/mod/peerforum/lib.php');

/**
 * The discussion list renderer.
 *
 * @package    mod_peerforum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion_list {
    /** @var peerforum_entity The peerforum being rendered */
    private $peerforum;

    /** @var stdClass The DB record for the peerforum being rendered */
    private $peerforumrecord;

    /** @var renderer_base The renderer used to render the view */
    private $renderer;

    /** @var legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;

    /** @var exporter_factory $exporterfactory Exporter factory */
    private $exporterfactory;

    /** @var vault_factory $vaultfactory Vault factory */
    private $vaultfactory;

    /** @var capability_manager $capabilitymanager Capability manager */
    private $capabilitymanager;

    /** @var url_factory $urlfactory URL factory */
    private $urlfactory;

    /** @var array $notifications List of notification HTML */
    private $notifications;

    /** @var builder_factory $builderfactory Builder factory */
    private $builderfactory;

    /** @var callable $postprocessfortemplate Function to process exported posts before template rendering */
    private $postprocessfortemplate;

    /** @var string $template The template to use when displaying */
    private $template;

    /** @var gradeitem The gradeitem instance associated with this peerforum */
    private $peerforumgradeitem;

    /**
     * Constructor for a new discussion list renderer.
     *
     * @param peerforum_entity $peerforum The peerforum entity to be rendered
     * @param renderer_base $renderer The renderer used to render the view
     * @param legacy_data_mapper_factory $legacydatamapperfactory The factory used to fetch a legacy record
     * @param exporter_factory $exporterfactory The factory used to fetch exporter instances
     * @param vault_factory $vaultfactory The factory used to fetch the vault instances
     * @param builder_factory $builderfactory The factory used to fetch the builder instances
     * @param capability_manager $capabilitymanager The managed used to check capabilities on the peerforum
     * @param url_factory $urlfactory The factory used to create URLs in the peerforum
     * @param string $template
     * @param notification[] $notifications A list of any notifications to be displayed within the page
     * @param callable|null $postprocessfortemplate Callback function to process discussion lists for templates
     */
    public function __construct(
            peerforum_entity $peerforum,
            renderer_base $renderer,
            legacy_data_mapper_factory $legacydatamapperfactory,
            exporter_factory $exporterfactory,
            vault_factory $vaultfactory,
            builder_factory $builderfactory,
            capability_manager $capabilitymanager,
            url_factory $urlfactory,
            peerforum_gradeitem $peerforumgradeitem,
            string $template,
            array $notifications = [],
            callable $postprocessfortemplate = null
    ) {
        $this->peerforum = $peerforum;
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->builderfactory = $builderfactory;
        $this->capabilitymanager = $capabilitymanager;

        $this->urlfactory = $urlfactory;
        $this->notifications = $notifications;
        $this->postprocessfortemplate = $postprocessfortemplate;
        $this->template = $template;
        $this->peerforumgradeitem = $peerforumgradeitem;

        $peerforumdatamapper = $this->legacydatamapperfactory->get_peerforum_data_mapper();
        $this->peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);
    }

    /**
     * Render for the specified user.
     *
     * @param stdClass $user The user to render for
     * @param cm_info $cm The course module info for this discussion list
     * @param int $groupid The group to render
     * @param int $sortorder The sort order to use when selecting the discussions in the list
     * @param int $pageno The zero-indexed page number to use
     * @param int $pagesize The number of discussions to show on the page
     * @param int $displaymode The discussion display mode
     * @return  string      The rendered content for display
     */
    public function render(
            stdClass $user,
            \cm_info $cm,
            ?int $groupid,
            ?int $sortorder,
            ?int $pageno,
            ?int $pagesize,
            int $displaymode = null
    ): string {
        global $PAGE;

        $peerforum = $this->peerforum;
        $course = $peerforum->get_course_record();

        $peerforumexporter = $this->exporterfactory->get_peerforum_exporter(
                $user,
                $this->peerforum,
                $groupid
        );

        $pagesize = $this->get_page_size($pagesize);
        $pageno = $this->get_page_number($pageno);

        // Count all peerforum discussion posts.
        $alldiscussionscount = mod_peerforum_count_all_discussions($peerforum, $user, $groupid);

        // Get all peerforum discussion summaries.
        $discussions = mod_peerforum_get_discussion_summaries($peerforum, $user, $groupid, $sortorder, $pageno, $pagesize);

        $capabilitymanager = $this->capabilitymanager;
        $hasanyactions = false;
        $hasanyactions = $hasanyactions || $capabilitymanager->can_favourite_discussion($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_pin_discussions($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_manage_peerforum($user);

        $peerforumview = [
                'peerforum' => (array) $peerforumexporter->export($this->renderer),
                'contextid' => $peerforum->get_context()->id,
                'cmid' => $cm->id,
                'name' => $peerforum->get_name(),
                'courseid' => $course->id,
                'coursename' => $course->shortname,
                'experimentaldisplaymode' => $displaymode == PEERFORUM_MODE_NESTED_V2,
                'gradingcomponent' => $this->peerforumgradeitem->get_grading_component_name(),
                'gradingcomponentsubtype' => $this->peerforumgradeitem->get_grading_component_subtype(),
                'sendstudentnotifications' => $peerforum->should_notify_students_default_when_grade_for_peerforum(),
                'hasanyactions' => $hasanyactions,
                'groupchangemenu' => groups_print_activity_menu(
                        $cm,
                        $this->urlfactory->get_peerforum_view_url_from_peerforum($peerforum),
                        true
                ),
                'hasmore' => ($alldiscussionscount > $pagesize),
                'notifications' => $this->get_notifications($user, $groupid),
                'settings' => [
                        'excludetext' => true,
                        'togglemoreicon' => true,
                        'excludesubscription' => true
                ],
                'totaldiscussioncount' => $alldiscussionscount,
                'userid' => $user->id,
                'visiblediscussioncount' => count($discussions)
        ];

        if ($peerforumview['peerforum']['capabilities']['create']) {
            $peerforumview['newdiscussionhtml'] = $this->get_discussion_form($user, $cm, $groupid);
        }

        if (!$discussions) {
            return $this->renderer->render_from_template($this->template, $peerforumview);
        }

        if ($this->postprocessfortemplate !== null) {
            // We've got some post processing to do!
            $exportedposts = ($this->postprocessfortemplate) ($discussions, $user, $peerforum);
        }

        $baseurl = new \moodle_url($PAGE->url, array('o' => $sortorder));

        $peerforumview = array_merge(
                $peerforumview,
                [
                        'pagination' => $this->renderer->render(new \paging_bar($alldiscussionscount, $pageno, $pagesize, $baseurl,
                                'p')),
                ],
                $exportedposts
        );

        $firstdiscussion = reset($discussions);
        $peerforumview['firstgradeduserid'] = $firstdiscussion->get_latest_post_author()->get_id();

        return $this->renderer->render_from_template($this->template, $peerforumview);
    }

    /**
     * Get the mod_peerforum_post_form. This is the default boiler plate from mod_peerforum/post_form.php with the inpage flag
     * caveat
     *
     * @param stdClass $user The user the form is being generated for
     * @param \cm_info $cm
     * @param int $groupid The groupid if any
     *
     * @return string The rendered html
     */
    private function get_discussion_form(stdClass $user, \cm_info $cm, ?int $groupid) {
        $peerforum = $this->peerforum;
        $peerforumrecord = $this->legacydatamapperfactory->get_peerforum_data_mapper()->to_legacy_object($peerforum);
        $modcontext = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($peerforum->get_course_id());
        $post = (object) [
                'course' => $peerforum->get_course_id(),
                'peerforum' => $peerforum->get_id(),
                'discussion' => 0,           // Ie discussion # not defined yet.
                'parent' => 0,
                'subject' => '',
                'userid' => $user->id,
                'message' => '',
                'messageformat' => editors_get_preferred_format(),
                'messagetrust' => 0,
                'groupid' => $groupid,
        ];
        $thresholdwarning = peerforum_check_throttling($peerforumrecord, $cm);

        $formparams = array(
                'course' => $peerforum->get_course_record(),
                'cm' => $cm,
                'coursecontext' => $coursecontext,
                'modcontext' => $modcontext,
                'peerforum' => $peerforumrecord,
                'post' => $post,
                'subscribe' => \mod_peerforum\subscriptions::is_subscribed($user->id, $peerforumrecord,
                        null, $cm),
                'thresholdwarning' => $thresholdwarning,
                'inpagereply' => true,
                'edit' => 0
        );
        $posturl = new \moodle_url('/mod/peerforum/post.php');
        $mformpost = new \mod_peerforum_post_form($posturl, $formparams, 'post', '', array('id' => 'mformpeerforum'));
        $discussionsubscribe =
                \mod_peerforum\subscriptions::get_user_default_subscription($peerforumrecord, $coursecontext, $cm, null);

        $params = array('reply' => 0, 'peerforum' => $peerforumrecord->id, 'edit' => 0) +
                (isset($post->groupid) ? array('groupid' => $post->groupid) : array()) +
                array(
                        'userid' => $post->userid,
                        'parent' => $post->parent,
                        'discussion' => $post->discussion,
                        'course' => $peerforum->get_course_id(),
                        'discussionsubscribe' => $discussionsubscribe
                );
        $mformpost->set_data($params);

        return $mformpost->render();
    }

    /**
     * Fetch the page size to use when displaying the page.
     *
     * @param int $pagesize The number of discussions to show on the page
     * @return  int         The normalised page size
     */
    private function get_page_size(?int $pagesize): int {
        if (null === $pagesize || $pagesize <= 0) {
            $pagesize = discussion_list_vault::PAGESIZE_DEFAULT;
        }

        return $pagesize;
    }

    /**
     * Fetch the current page number (zero-indexed).
     *
     * @param int $pageno The zero-indexed page number to use
     * @return  int         The normalised page number
     */
    private function get_page_number(?int $pageno): int {
        if (null === $pageno || $pageno < 0) {
            $pageno = 0;
        }

        return $pageno;
    }

    /**
     * Get the list of notification for display.
     *
     * @param stdClass $user The viewing user
     * @param int|null $groupid The peerforum's group id
     * @return      array
     */
    private function get_notifications(stdClass $user, ?int $groupid): array {
        $notifications = $this->notifications;
        $peerforum = $this->peerforum;
        $renderer = $this->renderer;
        $capabilitymanager = $this->capabilitymanager;

        if ($peerforum->is_cutoff_date_reached()) {
            $notifications[] = (new notification(
                    get_string('cutoffdatereached', 'peerforum'),
                    notification::NOTIFY_INFO
            ))->set_show_closebutton();
        } else if ($peerforum->is_due_date_reached()) {
            $notifications[] = (new notification(
                    get_string('thispeerforumisdue', 'peerforum', userdate($peerforum->get_due_date())),
                    notification::NOTIFY_INFO
            ))->set_show_closebutton();
        } else if ($peerforum->has_due_date()) {
            $notifications[] = (new notification(
                    get_string('thispeerforumhasduedate', 'peerforum', userdate($peerforum->get_due_date())),
                    notification::NOTIFY_INFO
            ))->set_show_closebutton();
        }

        if ($peerforum->has_blocking_enabled()) {
            $notifications[] = (new notification(
                    get_string('thispeerforumisthrottled', 'peerforum', [
                            'blockafter' => $peerforum->get_block_after(),
                            'blockperiod' => get_string('secondstotime' . $peerforum->get_block_period())
                    ])
            ))->set_show_closebutton();
        }

        if ($peerforum->is_in_group_mode() && !$capabilitymanager->can_access_all_groups($user)) {
            if ($groupid === null) {
                if (!$capabilitymanager->can_post_to_my_groups($user)) {
                    $notifications[] = (new notification(
                            get_string('cannotadddiscussiongroup', 'mod_peerforum'),
                            \core\output\notification::NOTIFY_WARNING
                    ))->set_show_closebutton();
                } else {
                    $notifications[] = (new notification(
                            get_string('cannotadddiscussionall', 'mod_peerforum'),
                            \core\output\notification::NOTIFY_WARNING
                    ))->set_show_closebutton();
                }
            } else if (!$capabilitymanager->can_access_group($user, $groupid)) {
                $notifications[] = (new notification(
                        get_string('cannotadddiscussion', 'mod_peerforum'),
                        \core\output\notification::NOTIFY_WARNING
                ))->set_show_closebutton();
            }
        }

        if ('qanda' === $peerforum->get_type() && !$capabilitymanager->can_manage_peerforum($user)) {
            $notifications[] = (new notification(
                    get_string('qandanotify', 'peerforum'),
                    notification::NOTIFY_INFO
            ))->set_show_closebutton();
        }

        if ('eachuser' === $peerforum->get_type()) {
            $notifications[] = (new notification(
                    get_string('allowsdiscussions', 'peerforum'),
                    notification::NOTIFY_INFO)
            )->set_show_closebutton();
        }

        return array_map(function($notification) {
            return $notification->export_for_template($this->renderer);
        }, $notifications);
    }
}
