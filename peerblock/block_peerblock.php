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

        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        global $USER, $PAGE, $CFG, $COURSE, $DB, $OUTPUT;

        // Create empty content.
        $this->content = new stdClass();
        $this->content->icons = array();
        $this->content->items = array();
        $this->content->footer = '';

        if (!empty($CFG->enablepeergrade)) {
            $this->content->text .= get_string('enablepeergrade', 'block_peerblock');
            return $this->content;
        }

        $peerforumvault = \mod_peerforum\local\container::get_vault_factory()->get_peerforum_vault();
        $courseid = $this->page->course->id;
        if ($courseid == SITEID) {
            $courseid = null;
        }

        // TODO ver /user.php for inspiraçao!
        $contextid = context_course::instance($COURSE->id);
        $peerforumid = $contextid->instanceid; // is courseid
        $userid = $USER->id;

        $sql = "SELECT a.*
                  FROM {peerforum_time_assigned} a
                 WHERE a.ended = 0 AND
                       a.userid = {$userid}";
        $notended = $DB->get_records_sql($sql);

        // Array of PeerForum Ids.
        $items = array();
        $contextids = array();
        $peergrades = array();
        $poststopeergrade = 0;
        $timetoexpire = 0;
        $pgexpiring = null;
        foreach ($notended as $n) {
            $contextids[$n->contextid][] = $n;
        }
        foreach ($contextids as $id => $cont) {
            $instanceid = context::instance_by_id($id)->instanceid;
            $peerforumentity = $peerforumvault->get_from_course_module_id($instanceid);

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

            $rm = \mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
            $items = $rm->get_peergrades($peergradeoptions);
            foreach ($items as $item) {
                $peergrades[$item->id] = $item->peergrade;
            }

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
        }

        /*$num_posts = get_num_posts_to_grade($USER->id, $COURSE->id);
        $time_old_post = get_time_old_post($USER->id, $COURSE->id);
        $peers_to_rank = get_num_peers_to_rank($USER->id, $COURSE->id);

        $posts_not_expired = get_active_peergrading_posts($COURSE->id);
        $posts_expiring = get_posts_about_to_expire($COURSE->id, $peerforumid);

        if (!($DB->get_record('peerforum', array('course' => $peerforumid)))) {
            $rankings = false;
        } else {
            $rankings = ($DB->get_record('peerforum', array('course' => $peerforumid)))->peerrankings;
        }*/


        $this->content->icons[] = $OUTPUT->render(new pix_icon('icon', 'logo', 'block_peerblock'));
        $this->content->items[] = html_writer::link(
                new moodle_url($CFG->wwwroot . '/peergrading/index.php',
                array('courseid' => $this->page->course->id,
                        'userid' => $USER->id, 'display' => 1, 'peerforum' => $peerforumid)),
                get_string('viewpanel', 'block_peerblock'),
                array('title' => get_string('viewpanel', 'block_peerblock')));

        //student view

        if (!has_capability('mod/peerforum:viewpanelpeergrades', $PAGE->context)) {
            $this->content->items[] = html_writer::tag('span',
                    'Number of posts to grade: ' . $poststopeergrade, array('style' => 'color:black'));
        }

        if ($poststopeergrade > 0 && !has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
            $this->content->items[] = html_writer::tag('span',
                    'Time to expire: ' . $pgexpiring->get_time_to_expire(), array('style' => 'color:black'));
        }
        /*if (!has_capability('mod/peerforum:viewallpeergrades', $PAGE->context) && $rankings) {
            $this->content->items[] = html_writer::tag('span', 'Number of peers available to rank: ' . $peers_to_rank,
                    array('style' => 'color:black'));
        }*/

        //teacher view

        /*if ($posts_not_expired > 0 && has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
            $this->content->items[] =
                    html_writer::tag('span', $posts_not_expired . ' active peergrade posts', array('style' => 'color:black'));
            if ($posts_expiring > 0) {
                $this->content->items[] =
                        html_writer::tag('span', $posts_expiring . ' posts about to expire', array('style' => 'color:black'));
            }
        } else if (has_capability('mod/peerforum:viewallpeergrades', $PAGE->context)) {
            $this->content->items[] = html_writer::tag('span', 'No posts to peergrade.', array('style' => 'color:black'));
        }*/

        return $this->content;
    }

    public function instance_config_save($data, $nolongerused = false) {
        if (get_config('peerblock', 'Allow_HTML') == '1') {
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

    public function cron() {
        global $DB; // Global database object
        // Get the instances of the block
        $instances = $DB->get_records('block_instances', array('blockname' => 'peerblock'));
        // Iterate over the instances
        foreach ($instances as $instance) {
            // Recreate block object
            $block = block_instance('peerblock', $instance);
            // $block is now the equivalent of $this in 'normal' block
            // usage, e.g.
            $someconfigitem = $block->config->item2;
        }
    }
}
