<?php
/**
 * Block displaying a Peer Grade panel.
 *
 *
 * @package    block
 * @subpackage peerblock
 * @copyright  2016 Jessica Ribeiro
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/blocks/peerblock/lib.php');

class block_peerblock extends block_list {

    public function init() {
        $this->title = get_string('peerblock', 'block_peerblock');
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'admin' => true,
                'site-index' => true,
                'course-view' => true,
                'course-view-social' => true,
                'mod' => true,
                'my' => true
        );
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('peerblock', 'block_peerblock');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        global $USER, $CFG, $DB, $OUTPUT;

        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        // Create empty content.
        $this->content = new stdClass();
        $this->content->icons = array();
        $this->content->items = array();
        $this->content->footer = '';

        if (!empty($CFG->enablepeergrade)) { // TODO check pf settings.
            $this->content->text .= get_string('enablepeergrade', 'block_peerblock');
            return $this->content;
        }

        $urlfactory = \mod_peerforum\local\container::get_url_factory();
        $peerforumvault = \mod_peerforum\local\container::get_vault_factory()->get_peerforum_vault();
        $rankingvault = \mod_peerforum\local\container::get_vault_factory()->get_relationship_ranking_vault();
        $pgmanager = \mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();

        $userid = $USER->id;
        $courseid = $this->page->course->id;
        if ($courseid == SITEID) { // SITEID is frontpage.
            return '';
        }
        $sumurl = new moodle_url('/blocks/peerblock/summary.php', array('courseid' => $courseid));
        $rankurl = new moodle_url('/blocks/peerblock/rankings.php', array('courseid' => $courseid));

        $viewgeneral = has_capability('mod/peerforum:viewpanelpeergrades', $this->page->context);

        /*------- Get the peergrading data -------*/
        if (!$viewgeneral) {
            $filters = array('ended' => 0, 'userid' => $userid);
        } else {
            $filters = array('ended' => 0);
        }

        $itemsdb = $pgmanager->get_items_from_filters($filters);

        /*------- Instance the peergrade objects -------*/
        $items = array(); // Obj items.
        $contextids = array(); // Items grouped by context.
        $peergrades = array(); // All the pergrades objs.
        $shouldnominate = null;
        foreach ($itemsdb as $it) {
            $contextids[$it->contextid][] = $it;
        }
        foreach ($contextids as $id => $cont) {
            $instanceid = context::instance_by_id($id)->instanceid;
            $peerforumentity = $peerforumvault->get_from_course_module_id($instanceid);
            $capabilitymanager = \mod_peerforum\local\container::get_manager_factory()->get_capability_manager($peerforumentity);

            foreach ($cont as $item) {
                $itemid = $item->itemid;
                if (isset($items[$itemid])) {
                    continue;
                }
                $obj = new stdClass();
                $obj->id = $itemid;
                $items[$itemid] = $obj;
            }

            $peergradeoptions = (object) ([
                            'items' => $items,
                            'userid' => $userid,
                    ] + $peerforumentity->get_peergrade_options());

            if ($capabilitymanager->must_nominate($USER, $courseid)) {
                $shouldnominate = $peerforumentity;
            }

            $items = $pgmanager->get_peergrades($peergradeoptions);
            foreach ($items as $item) {
                $peergrades[$item->id] = $item->peergrade;
            }
        }

        /*------- Compute data -------*/
        if (!$viewgeneral) {
            $poststopeergrade = 0;
            $timetoexpire = 0;
            $pgexpiring = null;
            foreach ($peergrades as $peergrade) {
                if ($peergrade->user_can_peergrade()) {
                    $poststopeergrade++;
                    $tte = $peergrade->get_time_to_expire(false);
                    if ($tte < $timetoexpire || $timetoexpire == 0) {
                        $pgexpiring = $peergrade;
                        $timetoexpire = $tte;
                    }
                }
            }
            $rankings = $rankingvault->count_pending_from_user_id($userid, $courseid);
            $nrankings = $rankings ?: 0;
        } else {
            $postspeergrading = 0;
            $indpgexpiringsoon = 0;
            $pgexpiringsoon = 0;
            foreach ($peergrades as $peergrade) {
                if (!$peergrade->is_ended()) {
                    $postspeergrading++;
                    $expsoon = $peergrade->get_expiring_soon();
                    if ($expsoon) {
                        $indpgexpiringsoon += $expsoon;
                        $pgexpiringsoon++;
                    }
                }
            }
        }

        /*------- Render data -------*/
        $this->content->items[] = html_writer::span(
                $OUTPUT->render(new pix_icon('icon', 'logo', 'block_peerblock')) .
                'Summary',
                'd-flex align-items-center bold');

        if ($shouldnominate) {
            $sumurl = $rankurl = $urlfactory->get_nominations_url($shouldnominate);
        }

        if (!$viewgeneral) {
            // Student view.
            $this->content->items[] = html_writer::span('Posts to grade: ' .
                    html_writer::link(new moodle_url($sumurl, array(
                            'display' => MANAGEPOSTS_MODE_SEENOTGRADED,
                    )), $poststopeergrade . ' posts'));

            if ($poststopeergrade > 0) {
                $this->content->items[] = html_writer::span(
                        'Time to expire: ' . $pgexpiring->get_time_to_expire());
            }

            if ($nrankings >= 5) {
                $this->content->items[] = html_writer::span('Peers to rank: ' .
                        html_writer::link(new moodle_url($rankurl, array(
                                'userid' => $userid,
                        )), $nrankings . ' peers'));
            }

        } else {
            // Professor view.
            $this->content->items[] = html_writer::span('In progress: ' .
                    html_writer::link(new moodle_url($sumurl, array(
                            'display' => MANAGEPOSTS_MODE_SEENOTGRADED,
                    )), $postspeergrading .  ' post peer grading'));

            $this->content->items[] = html_writer::span(
                'Expiring soon: ' .
                $pgexpiringsoon . ' posts (' . $indpgexpiringsoon . ' individual graders)');

            $this->content->items[] = html_writer::link(
                    new moodle_url($CFG->wwwroot . '/peergrading/index.php',
                            array('courseid' => $courseid,
                                    'userid' => $userid, 'display' => 1, 'peerforum' => $courseid)),
                    'View old peer grade panel only for professors...',
                    array('title' => get_string('viewpanel', 'block_peerblock')));
            $this->content->items[] = 'Pls dont press action buttons while there. only navigate.';
        }

        $this->content->items[] = html_writer::tag('p', html_writer::link(new moodle_url($sumurl, array(
                        'display' => MANAGEPOSTS_MODE_SEEALL)),
                html_writer::span('Full summary...', 'mark')),
                array('class' => 'm-0 mt-3'));

        return $this->content;
    }

    public function instance_config_save($data, $nolongerused = false) {
        if (get_config('peerblock', 'Allow_HTML') == '1') { // Check this.
            $data->text = strip_tags($data->text);
        }

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data, $nolongerused);
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_' . $this->name(); // Append our class to class attribute
        return $attributes;
    }
}
