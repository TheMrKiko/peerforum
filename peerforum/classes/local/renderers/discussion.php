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
 * Discussion renderer.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\entities\discussion as discussion_entity;
use mod_peerforum\local\entities\peerforum as peerforum_entity;
use mod_peerforum\local\entities\post as post_entity;
use mod_peerforum\local\entities\sorter as sorter_entity;
use mod_peerforum\local\factories\entity as entity_factory;
use mod_peerforum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_peerforum\local\factories\exporter as exporter_factory;
use mod_peerforum\local\factories\url as url_factory;
use mod_peerforum\local\factories\vault as vault_factory;
use mod_peerforum\local\managers\capability as capability_manager;
use mod_peerforum\local\renderers\posts as posts_renderer;
use peerforum_portfolio_caller;
use core\output\notification;
use context;
use context_module;
use html_writer;
use moodle_exception;
use moodle_page;
use moodle_url;
use rating_manager;
use renderer_base;
use single_button;
use single_select;
use stdClass;
use url_select;

require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/mod/peerforum/locallib.php');

/**
 * Discussion renderer class.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion {
    /** @var peerforum_entity $peerforum The peerforum that the discussion belongs to */
    private $peerforum;
    /** @var discussion_entity $discussion The discussion entity */
    private $discussion;
    /** @var stdClass $discussionrecord Legacy discussion record */
    private $discussionrecord;
    /** @var stdClass $peerforumrecord Legacy peerforum record */
    private $peerforumrecord;
    /** @var int $displaymode The display mode to render the discussion in */
    private $displaymode;
    /** @var renderer_base $renderer Renderer base */
    private $renderer;
    /** @var posts_renderer $postsrenderer A posts renderer */
    private $postsrenderer;
    /** @var moodle_page $page The page this discussion is being rendered for */
    private $page;
    /** @var legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;
    /** @var exporter_factory $exporterfactory Exporter factory */
    private $exporterfactory;
    /** @var vault_factory $vaultfactory Vault factory */
    private $vaultfactory;
    /** @var url_factory $urlfactory URL factory */
    private $urlfactory;
    /** @var entity_factory $entityfactory Entity factory */
    private $entityfactory;
    /** @var capability_manager $capabilitymanager Capability manager */
    private $capabilitymanager;
    /** @var rating_manager $ratingmanager Rating manager */
    private $ratingmanager;
    /** @var moodle_url $baseurl The base URL for the discussion */
    private $baseurl;
    /** @var array $notifications List of HTML notifications to display */
    private $notifications;
    /** @var sorter_entity $exportedpostsorter Sorter for the exported posts */
    private $exportedpostsorter;
    /** @var callable $postprocessfortemplate Function to process exported posts before template rendering */
    private $postprocessfortemplate;

    /**
     * Constructor.
     *
     * @param peerforum_entity $peerforum The peerforum that the discussion belongs to
     * @param discussion_entity $discussion The discussion entity
     * @param int $displaymode The display mode to render the discussion in
     * @param renderer_base $renderer Renderer base
     * @param posts_renderer $postsrenderer A posts renderer
     * @param moodle_page $page The page this discussion is being rendered for
     * @param legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory
     * @param exporter_factory $exporterfactory Exporter factory
     * @param vault_factory $vaultfactory Vault factory
     * @param url_factory $urlfactory URL factory
     * @param entity_factory $entityfactory Entity factory
     * @param capability_manager $capabilitymanager Capability manager
     * @param rating_manager $ratingmanager Rating manager
     * @param sorter_entity $exportedpostsorter Sorter for the exported posts
     * @param moodle_url $baseurl The base URL for the discussion
     * @param array $notifications List of HTML notifications to display
     * @param callable|null $postprocessfortemplate Post processing for template callback
     */
    public function __construct(
            peerforum_entity $peerforum,
            discussion_entity $discussion,
            int $displaymode,
            renderer_base $renderer,
            posts_renderer $postsrenderer,
            moodle_page $page,
            legacy_data_mapper_factory $legacydatamapperfactory,
            exporter_factory $exporterfactory,
            vault_factory $vaultfactory,
            url_factory $urlfactory,
            entity_factory $entityfactory,
            capability_manager $capabilitymanager,
            rating_manager $ratingmanager,
            sorter_entity $exportedpostsorter,
            moodle_url $baseurl,
            array $notifications = [],
            callable $postprocessfortemplate = null
    ) {
        $this->peerforum = $peerforum;
        $this->discussion = $discussion;
        $this->displaymode = $displaymode;
        $this->renderer = $renderer;
        $this->postsrenderer = $postsrenderer;
        $this->page = $page;
        $this->baseurl = $baseurl;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->urlfactory = $urlfactory;
        $this->entityfactory = $entityfactory;
        $this->capabilitymanager = $capabilitymanager;
        $this->ratingmanager = $ratingmanager;
        $this->notifications = $notifications;

        $this->exportedpostsorter = $exportedpostsorter;
        $this->postprocessfortemplate = $postprocessfortemplate;

        $peerforumdatamapper = $this->legacydatamapperfactory->get_peerforum_data_mapper();
        $this->peerforumrecord = $peerforumdatamapper->to_legacy_object($peerforum);

        $discussiondatamapper = $this->legacydatamapperfactory->get_discussion_data_mapper();
        $this->discussionrecord = $discussiondatamapper->to_legacy_object($discussion);
    }

    /**
     * Render the discussion for the given user in the specified display mode.
     *
     * @param stdClass $user The user viewing the discussion
     * @param post_entity $firstpost The first post in the discussion
     * @param array $replies List of replies to the first post
     * @return string HTML for the discussion
     */
    public function render(
            stdClass $user,
            post_entity $firstpost,
            array $replies
    ): string {
        global $CFG;

        $displaymode = $this->displaymode;
        $capabilitymanager = $this->capabilitymanager;
        $urlfactory = $this->urlfactory;
        $entityfactory = $this->entityfactory;

        // Make sure we can render.
        if (!$capabilitymanager->can_view_discussions($user)) {
            throw new moodle_exception('noviewdiscussionspermission', 'mod_peerforum');
        }

        $posts = array_merge([$firstpost], array_values($replies));

        if ($this->postprocessfortemplate !== null) {
            $exporteddiscussion = ($this->postprocessfortemplate) ($this->discussion, $user, $this->peerforum);
        } else {
            $exporteddiscussion = $this->get_exported_discussion($user);
        }

        $hasanyactions = false;
        $hasanyactions = $hasanyactions || $capabilitymanager->can_favourite_discussion($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_pin_discussions($user);
        $hasanyactions = $hasanyactions || $capabilitymanager->can_manage_peerforum($user);

        $exporteddiscussion = array_merge($exporteddiscussion, [
                'notifications' => $this->get_notifications($user),
                'html' => [
                        'hasanyactions' => $hasanyactions,
                        'posts' => $this->postsrenderer->render($user, [$this->peerforum], [$this->discussion], $posts),
                        'modeselectorform' => $this->get_display_mode_selector_html($displaymode, $user),
                        'subscribe' => null,
                        'movediscussion' => null,
                        'pindiscussion' => null,
                        'neighbourlinks' => $this->get_neighbour_links_html(),
                        'exportdiscussion' => !empty($CFG->enableportfolios) ? $this->get_export_discussion_html($user) : null
                ]
        ]);

        $capabilities = (array) $exporteddiscussion['capabilities'];

        if ($capabilities['move']) {
            $exporteddiscussion['html']['movediscussion'] = $this->get_move_discussion_html();
        }

        if (!empty($user->id)) {
            $loggedinuser = $entityfactory->get_author_from_stdClass($user);
            $exporteddiscussion['loggedinuser'] = [
                    'firstname' => $loggedinuser->get_first_name(),
                    'fullname' => $loggedinuser->get_full_name(),
                    'profileimageurl' => ($urlfactory->get_author_profile_image_url($loggedinuser, null))->out(false)
            ];
        }

        if ($this->displaymode === PEERFORUM_MODE_NESTED_V2) {
            $template = 'mod_peerforum/peerforum_discussion_nested_v2';
        } else {
            $template = 'mod_peerforum/peerforum_discussion';
        }

        // TODO Pagination of peerforum
        /* $canratepeer = has_capability('mod/peerforum:ratepeer', $modcontext);
        $cangrade = has_capability('mod/peerforum:grade', $modcontext);

        $enable_pagination = $peerforum->pagination;

        if ($enable_pagination) {
            $total_posts = count($DB->get_records('peerforum_posts', array('discussion' => $discussion->id)));

            $perpage = $peerforum->postsperpage;
            $start = $currentpage * $perpage;

            if ($start > $total_posts) {
                $currentpage = 0;
                $start = 0;
            }
            peerforum_print_discussion(
                $course,
                $cm,
                $peerforum,
                $discussion,
                $post,
                $displaymode,
                $canreply,
                $canratepeer,
                $cangrade,
                false,
                true,
                null,
                null,
                $start,
                $perpage,
                $enable_pagination
            );

            // pagination of peerforum
            echo '</br>';
            $pageurl = new moodle_url('/mod/peerforum/discuss.php', array('d' => $discussion->id, 'page' => $currentpage));
            echo $OUTPUT->paging_bar($total_posts, $currentpage, $perpage, $pageurl);
        } else {
            peerforum_print_discussion(
                $course,
                $cm,
                $peerforum,
                $discussion,
                $post,
                $displaymode,
                $canreply,
                $canratepeer,
                $cangrade,
                false,
                true,
                null,
                null
            );
        }*/

        return $this->renderer->render_from_template($template, $exporteddiscussion);
    }

    /**
     * Get the groups details for all groups available to the peerforum.
     *
     * @return  stdClass[]
     */
    private function get_groups_available_in_peerforum(): array {
        $course = $this->peerforum->get_course_record();
        $coursemodule = $this->peerforum->get_course_module_record();

        return groups_get_all_groups($course->id, 0, $coursemodule->groupingid);
    }

    /**
     * Get the exported discussion.
     *
     * @param stdClass $user The user viewing the discussion
     * @return array
     */
    private function get_exported_discussion(stdClass $user): array {
        $discussionexporter = $this->exporterfactory->get_discussion_exporter(
                $user,
                $this->peerforum,
                $this->discussion,
                $this->get_groups_available_in_peerforum()
        );

        return (array) $discussionexporter->export($this->renderer);
    }

    /**
     * Get the HTML for the display mode selector.
     *
     * @param int $displaymode The current display mode
     * @param stdClass $user The current user
     * @return string
     */
    private function get_display_mode_selector_html(int $displaymode, stdClass $user): string {
        $baseurl = $this->baseurl;
        $select = new single_select(
                $baseurl,
                'mode',
                peerforum_get_layout_modes(get_user_preferences('peerforum_useexperimentalui', false, $user)),
                $displaymode,
                null,
                'mode'
        );
        $select->set_label(get_string('displaymode', 'peerforum'), ['class' => 'accesshide']);

        return $this->renderer->render($select);
    }

    /**
     * Get the HTML to render the move discussion selector and button.
     *
     * @return string
     */
    private function get_move_discussion_html(): ?string {
        global $DB;

        $peerforum = $this->peerforum;
        $discussion = $this->discussion;
        $courseid = $peerforum->get_course_id();

        // Popup menu to move discussions to other peerforums. The discussion in a
        // single discussion peerforum can't be moved.
        $modinfo = get_fast_modinfo($courseid);
        if (isset($modinfo->instances['peerforum'])) {
            $peerforummenu = [];
            // Check peerforum types and eliminate simple discussions.
            $peerforumcheck = $DB->get_records('peerforum', ['course' => $courseid], '', 'id, type');
            foreach ($modinfo->instances['peerforum'] as $peerforumcm) {
                if (!$peerforumcm->uservisible || !has_capability('mod/peerforum:startdiscussion',
                                context_module::instance($peerforumcm->id))) {
                    continue;
                }
                $section = $peerforumcm->sectionnum;
                $sectionname = get_section_name($courseid, $section);
                if (empty($peerforummenu[$section])) {
                    $peerforummenu[$section] = [$sectionname => []];
                }
                $peerforumidcompare = $peerforumcm->instance != $peerforum->get_id();
                $peerforumtypecheck = $peerforumcheck[$peerforumcm->instance]->type !== 'single';

                if ($peerforumidcompare and $peerforumtypecheck) {
                    $url = "/mod/peerforum/discuss.php?d={$discussion->get_id()}&move=$peerforumcm->instance&sesskey=" . sesskey();
                    $peerforummenu[$section][$sectionname][$url] = format_string($peerforumcm->name);
                }
            }
            if (!empty($peerforummenu)) {
                $html = '<div class="movediscussionoption">';

                $movebutton = get_string('move');
                if ($this->displaymode === PEERFORUM_MODE_NESTED_V2) {
                    // Move discussion selector will be rendered on the settings drawer. We won't output the button in this mode.
                    $movebutton = null;
                }
                $select = new url_select($peerforummenu, '',
                        ['/mod/peerforum/discuss.php?d=' . $discussion->get_id() => get_string("movethisdiscussionto",
                                "peerforum")],
                        'peerforummenu', $movebutton);
                $select->set_label(get_string('movethisdiscussionlabel', 'mod_peerforum'), [
                        'class' => 'sr-only',
                ]);
                $html .= $this->renderer->render($select);
                $html .= "</div>";
                return $html;
            }
        }

        return null;
    }

    /**
     * Get the HTML to render the export discussion button.
     *
     * @param stdClass $user The user viewing the discussion
     * @return  string|null
     */
    private function get_export_discussion_html(stdClass $user): ?string {
        global $CFG;

        if (!$this->capabilitymanager->can_export_discussions($user)) {
            return null;
        }

        $button = new \portfolio_add_button();
        $button->set_callback_options('peerforum_portfolio_caller', ['discussionid' => $this->discussion->get_id()],
                'mod_peerforum');
        $button = $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportdiscussion', 'mod_peerforum'));
        return $button ?: null;
    }

    /**
     * Get a list of notification HTML to render in the page.
     *
     * @param stdClass $user The user viewing the discussion
     * @return string[]
     */
    private function get_notifications($user): array {
        $notifications = $this->notifications;
        $discussion = $this->discussion;
        $peerforum = $this->peerforum;
        $renderer = $this->renderer;

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

        if ($peerforum->is_discussion_locked($discussion)) {
            $notifications[] = (new notification(
                    get_string('discussionlocked', 'peerforum'),
                    notification::NOTIFY_INFO
            ))
                    ->set_extra_classes(['discussionlocked'])
                    ->set_show_closebutton();
        }

        if ($peerforum->get_type() == 'qanda') {
            if ($this->capabilitymanager->must_post_before_viewing_discussion($user, $discussion)) {
                $notifications[] = (new notification(
                        get_string('qandanotify', 'peerforum')
                ))->set_show_closebutton(true);
            }
        }

        if ($peerforum->has_blocking_enabled()) {
            $notifications[] = (new notification(
                    get_string('thispeerforumisthrottled', 'peerforum', [
                            'blockafter' => $peerforum->get_block_after(),
                            'blockperiod' => get_string('secondstotime' . $peerforum->get_block_period())
                    ])
            ))->set_show_closebutton();
        }

        return array_map(function($notification) {
            return $notification->export_for_template($this->renderer);
        }, $notifications);
    }

    /**
     * Get HTML to display the neighbour links.
     *
     * @return string
     */
    private function get_neighbour_links_html(): string {
        $peerforum = $this->peerforum;
        $coursemodule = $peerforum->get_course_module_record();
        $neighbours = peerforum_get_discussion_neighbours($coursemodule, $this->discussionrecord, $this->peerforumrecord);
        return $this->renderer->neighbouring_discussion_navigation($neighbours['prev'], $neighbours['next']);
    }
}
