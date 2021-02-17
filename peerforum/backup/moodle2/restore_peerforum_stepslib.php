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
 * @package    mod_peerforum
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_peerforum_activity_task
 */

/**
 * Structure step to restore one peerforum activity
 */
class restore_peerforum_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('peerforum', '/activity/peerforum');
        if ($userinfo) {
            $paths[] = new restore_path_element('peerforum_discussion', '/activity/peerforum/discussions/discussion');
            $paths[] = new restore_path_element('peerforum_post', '/activity/peerforum/discussions/discussion/posts/post');
            $paths[] = new restore_path_element('peerforum_tag', '/activity/peerforum/poststags/tag');
            $paths[] = new restore_path_element('peerforum_discussion_sub',
                    '/activity/peerforum/discussions/discussion/discussion_subs/discussion_sub');
            $paths[] = new restore_path_element('peerforum_rating',
                    '/activity/peerforum/discussions/discussion/posts/post/ratings/rating');
            $paths[] = new restore_path_element('peerforum_subscription', '/activity/peerforum/subscriptions/subscription');
            $paths[] = new restore_path_element('peerforum_digest', '/activity/peerforum/digests/digest');
            $paths[] = new restore_path_element('peerforum_read', '/activity/peerforum/readposts/read');
            $paths[] = new restore_path_element('peerforum_track', '/activity/peerforum/trackedprefs/track');
            $paths[] = new restore_path_element('peerforum_peergrade', '/activity/peerforum/discussions/discussion/posts/post/peergrades/peergrade');
            $paths[] = new restore_path_element('peerforum_blockedgrades', '/activity/peerforum/blockedgrades/blockedgrades');
            $paths[] = new restore_path_element('peerforum_groups', '/activity/peerforum/groups/groups');
            $paths[] = new restore_path_element('peerforum_peergradecriteria', '/activity/peergradecriteria/peergradecriteria');
            $paths[] = new restore_path_element('peerforum_peergrade_conflict', '/activity/peergrade_conflict/peergrade_conflict');
            $paths[] = new restore_path_element('peerforum_peergrade_users', '/activity/peergrade_users/peergrade_users');
            $paths[] = new restore_path_element('peerforum_time_assigned', '/activity/time_assigned/time_assigned');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_peerforum($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        if (!isset($data->duedate)) {
            $data->duedate = 0;
        }
        $data->duedate = $this->apply_date_offset($data->duedate);
        if (!isset($data->cutoffdate)) {
            $data->cutoffdate = 0;
        }
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate);
        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);
        if ($data->scale < 0) { // scale found, get mapping
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        $newitemid = $DB->insert_record('peerforum', $data);
        $this->apply_activity_instance($newitemid);

        // Add current enrolled user subscriptions if necessary.
        $data->id = $newitemid;
        $ctx = context_module::instance($this->task->get_moduleid());
        peerforum_instance_created($ctx, $data);
    }

    protected function process_peerforum_discussion($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->peerforum = $this->get_new_parentid('peerforum');
        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timeend = $this->apply_date_offset($data->timeend);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        $newitemid = $DB->insert_record('peerforum_discussions', $data);
        $this->set_mapping('peerforum_discussion', $oldid, $newitemid);
    }

    protected function process_peerforum_post($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->discussion = $this->get_new_parentid('peerforum_discussion');
        $data->userid = $this->get_mappingid('user', $data->userid);
        // If post has parent, map it (it has been already restored)
        if (!empty($data->parent)) {
            $data->parent = $this->get_mappingid('peerforum_post', $data->parent);
        }

        \mod_peerforum\local\entities\post::add_message_counts($data);
        $newitemid = $DB->insert_record('peerforum_posts', $data);
        $this->set_mapping('peerforum_post', $oldid, $newitemid, true);

        // If !post->parent, it's the 1st post. Set it in discussion
        if (empty($data->parent)) {
            $DB->set_field('peerforum_discussions', 'firstpost', $newitemid, array('id' => $data->discussion));
        }
    }

    protected function process_peerforum_tag($data) {
        $data = (object) $data;

        if (!core_tag_tag::is_enabled('mod_peerforum', 'peerforum_posts')) { // Tags disabled in server, nothing to process.
            return;
        }

        $tag = $data->rawname;
        if (!$itemid = $this->get_mappingid('peerforum_post', $data->itemid)) {
            // Some orphaned tag, we could not find the restored post for it - ignore.
            return;
        }

        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_peerforum', 'peerforum_posts', $itemid, $context, $tag);
    }

    protected function process_peerforum_rating($data) {
        global $DB;

        $data = (object) $data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('peerforum_post');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);

        // We need to check that component and ratingarea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_peerforum';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'post';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }

    protected function process_peerforum_peergrade($data) {
        global $DB;

        $data = (object) $data;

        // Cannot use peergrade API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('peerforum_post');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->peergrade = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);

        // We need to check that component and peergradearea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_peerforum';
        }
        if (empty($data->peergradearea)) {
            $data->peergradearea = 'post';
        }

        $newitemid = $DB->insert_record('peerforum_peergrade', $data);
    }

    protected function process_peerforum_subscription($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerforum = $this->get_new_parentid('peerforum');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Create only a new subscription if it does not already exist (see MDL-59854).
        if ($subscription = $DB->get_record('peerforum_subscriptions',
                array('peerforum' => $data->peerforum, 'userid' => $data->userid))) {
            $this->set_mapping('peerforum_subscription', $oldid, $subscription->id, true);
        } else {
            $newitemid = $DB->insert_record('peerforum_subscriptions', $data);
            $this->set_mapping('peerforum_subscription', $oldid, $newitemid, true);
        }

    }

    protected function process_peerforum_discussion_sub($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->discussion = $this->get_new_parentid('peerforum_discussion');
        $data->peerforum = $this->get_new_parentid('peerforum');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerforum_discussion_subs', $data);
        $this->set_mapping('peerforum_discussion_sub', $oldid, $newitemid, true);
    }

    protected function process_peerforum_digest($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerforum = $this->get_new_parentid('peerforum');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerforum_digests', $data);
    }

    protected function process_peerforum_grade($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerforum = $this->get_new_parentid('peerforum');

        $data->userid = $this->get_mappingid('user', $data->userid);

        // We want to ensure the current user has an ID that we can associate to a grade.
        if ($data->userid != 0) {
            $newitemid = $DB->insert_record('peerforum_grades', $data);

            // Note - the old contextid is required in order to be able to restore files stored in
            // sub plugin file areas attached to the gradeid.
            $this->set_mapping('grade', $oldid, $newitemid, false, null, $this->task->get_old_contextid());
            $this->set_mapping(restore_gradingform_plugin::itemid_mapping('peerforum'), $oldid, $newitemid);
        }
    }

    protected function process_peerforum_read($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerforumid = $this->get_new_parentid('peerforum');
        $data->discussionid = $this->get_mappingid('peerforum_discussion', $data->discussionid);
        $data->postid = $this->get_mappingid('peerforum_post', $data->postid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerforum_read', $data);
    }

    protected function process_peerforum_track($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->peerforumid = $this->get_new_parentid('peerforum');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerforum_track_prefs', $data);
    }

    protected function after_execute() {
        global $DB;

        // Add peerforum related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_peerforum', 'intro', null);

        // If the peerforum is of type 'single' and no discussion has been ignited
        // (non-userinfo backup/restore) create the discussion here, using peerforum
        // information as base for the initial post.
        $peerforumid = $this->task->get_activityid();
        $peerforumrec = $DB->get_record('peerforum', array('id' => $peerforumid));
        if ($peerforumrec->type == 'single' && !$DB->record_exists('peerforum_discussions', array('peerforum' => $peerforumid))) {
            // Create single discussion/lead post from peerforum data
            $sd = new stdClass();
            $sd->course = $peerforumrec->course;
            $sd->peerforum = $peerforumrec->id;
            $sd->name = $peerforumrec->name;
            $sd->assessed = $peerforumrec->assessed;
            $sd->message = $peerforumrec->intro;
            $sd->messageformat = $peerforumrec->introformat;
            $sd->messagetrust = true;
            $sd->mailnow = false;
            $sdid = peerforum_add_discussion($sd, null, null, $this->task->get_userid());
            // Mark the post as mailed
            $DB->set_field('peerforum_posts', 'mailed', '1', array('discussion' => $sdid));
            // Copy all the files from mod_foum/intro to mod_peerforum/post
            $fs = get_file_storage();
            $files = $fs->get_area_files($this->task->get_contextid(), 'mod_peerforum', 'intro');
            foreach ($files as $file) {
                $newfilerecord = new stdClass();
                $newfilerecord->filearea = 'post';
                $newfilerecord->itemid = $DB->get_field('peerforum_discussions', 'firstpost', array('id' => $sdid));
                $fs->create_file_from_storedfile($newfilerecord, $file);
            }
        }

        // Add post related files, matching by itemname = 'peerforum_post'
        $this->add_related_files('mod_peerforum', 'post', 'peerforum_post');
        $this->add_related_files('mod_peerforum', 'attachment', 'peerforum_post');
    }
}
