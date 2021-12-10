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
 * This file keeps track of upgrades to
 * the peerforum module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   mod_peerforum
 * @copyright 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_peerforum_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2019031200) {
        // Define field privatereplyto to be added to peerforum_posts.
        $table = new xmldb_table('peerforum_posts');
        $field = new xmldb_field('privatereplyto', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'mailnow');

        // Conditionally launch add field privatereplyto.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019031200, 'peerforum');
    }

    if ($oldversion < 2019040400) {

        $table = new xmldb_table('peerforum');

        // Define field duedate to be added to peerforum.
        $field = new xmldb_field('duedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'introformat');

        // Conditionally launch add field duedate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field cutoffdate to be added to peerforum.
        $field = new xmldb_field('cutoffdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'duedate');

        // Conditionally launch add field cutoffdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019040400, 'peerforum');
    }

    if ($oldversion < 2019040402) {
        // Define field deleted to be added to peerforum_posts.
        $table = new xmldb_table('peerforum_discussions');
        $field = new xmldb_field('timelocked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'pinned');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019040402, 'peerforum');
    }

    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2019071901) {

        // Define field wordcount to be added to peerforum_posts.
        $table = new xmldb_table('peerforum_posts');
        $field = new xmldb_field('wordcount', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'privatereplyto');

        // Conditionally launch add field wordcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field charcount to be added to peerforum_posts.
        $table = new xmldb_table('peerforum_posts');
        $field = new xmldb_field('charcount', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'wordcount');

        // Conditionally launch add field charcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019071901, 'peerforum');
    }

    if ($oldversion < 2019071902) {
        // Create adhoc task for upgrading of existing peerforum_posts.
        $record = new \stdClass();
        $record->classname = '\mod_peerforum\task\refresh_peerforum_post_counts';
        $record->component = 'mod_peerforum';

        // Next run time based from nextruntime computation in \core\task\manager::queue_adhoc_task().
        $nextruntime = time() - 1;
        $record->nextruntime = $nextruntime;
        $DB->insert_record('task_adhoc', $record);

        // Main savepoint reached.
        upgrade_mod_savepoint(true, 2019071902, 'peerforum');
    }

    if ($oldversion < 2019081100) {

        // Define field grade_peerforum to be added to peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('grade_peerforum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'scale');

        // Conditionally launch add field grade_peerforum.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019081100, 'peerforum');

    }

    if ($oldversion < 2019100100) {
        // Define table peerforum_grades to be created.
        $table = new xmldb_table('peerforum_grades');

        // Adding fields to table peerforum_grades.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('peerforum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table peerforum_grades.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('peerforum', XMLDB_KEY_FOREIGN, ['peerforum'], 'peerforum', ['id']);

        // Adding indexes to table peerforum_grades.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('peerforumusergrade', XMLDB_INDEX_UNIQUE, ['peerforum', 'itemnumber', 'userid']);

        // Conditionally launch create table for peerforum_grades.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019100100, 'peerforum');
    }

    if ($oldversion < 2019100108) {

        // Define field sendstudentnotifications_peerforum to be added to peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('sendstudentnotifications_peerforum', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0',
                'grade_peerforum');

        // Conditionally launch add field sendstudentnotifications_peerforum.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019100108, 'peerforum');
    }

    if ($oldversion < 2019100109) {

        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('sendstudentnotifications_peerforum');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'grade_peerforum');
            $dbman->rename_field($table, $field, 'grade_peerforum_notify');
        }

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019100109, 'peerforum');

    }

    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2019111801) {
        $sql = "SELECT d.id AS discussionid, p.userid AS correctuser
                FROM {peerforum_discussions} d
                INNER JOIN {peerforum_posts} p ON p.id = d.firstpost
                WHERE d.userid <> p.userid";
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $object = new stdClass();
            $object->id = $record->discussionid;
            $object->userid = $record->correctuser;
            $DB->update_record('peerforum_discussions', $object);
        }

        $recordset->close();

        // PeerForum savepoint reached.
        upgrade_mod_savepoint(true, 2019111801, 'peerforum');
    }

    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2020072100) {
        // Add index privatereplyto (not unique) to the peerforum_posts table.
        $table = new xmldb_table('peerforum_posts');
        $index = new xmldb_index('privatereplyto', XMLDB_INDEX_NOTUNIQUE, ['privatereplyto']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2020072100, 'peerforum');
    }

    // Automatically generated Moodle v3.10.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2021021803) {

        // Changing precision of field type on table peerforum_discussions to (4).
        $table = new xmldb_table('peerforum_discussions');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '2', 'idlink');

        // Launch change of precision for field type.
        $dbman->change_field_precision($table, $field);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021021803, 'peerforum');
    }

    if ($oldversion < 2021021901) {

        // Define field ended to be added to peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('ended', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'blocked');

        // Conditionally launch add field ended.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021021901, 'peerforum');
    }

    if ($oldversion < 2021022101) {

        $peerforums = $DB->get_records('peerforum');
        foreach ($peerforums as $peerforum) {
            $old = $peerforum->whenpeergrades;
            if ($peerforum->whenpeergrades == 'always') {
                $new = PEERFORUM_GRADEVISIBLE_ALWAYS;
            } else if ($peerforum->whenpeergrades == 'after peergrade ends') {
                $new = PEERFORUM_GRADEVISIBLE_AFTERPGENDS;
            } else {
                $new = 0;
            }
            $DB->set_field('peerforum', 'whenpeergrades', $new, array('id' => $peerforum->id));
        }

        // Changing type of field whenpeergrades on table peerforum to int.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('whenpeergrades', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'peergradesvisibility');

        // Launch change of type for field whenpeergrades.
        $dbman->change_field_type($table, $field);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021022101, 'peerforum');
    }

    if ($oldversion < 2021022102) {

        // Define field itemid to be added to peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'postid');

        // Conditionally launch add field itemid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021022102, 'peerforum');
    }

    if ($oldversion < 2021022202) {

        // Define table peerforum_relationship_nomin to be created.
        $table = new xmldb_table('peerforum_relationship_nomin');

        // Adding fields to table peerforum_relationship_nomin.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('otheruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nomination', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table peerforum_relationship_nomin.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('otheruserid', XMLDB_KEY_FOREIGN, ['otheruserid'], 'user', ['id']);

        // Conditionally launch create table for peerforum_relationship_nomin.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table peerforum_relationship_rank to be created.
        $table = new xmldb_table('peerforum_relationship_rank');

        // Adding fields to table peerforum_relationship_rank.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('otheruserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ranking', XMLDB_TYPE_INTEGER, '4', null, null, null, null);

        // Adding keys to table peerforum_relationship_rank.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('otheruserid', XMLDB_KEY_FOREIGN, ['otheruserid'], 'user', ['id']);

        // Conditionally launch create table for peerforum_relationship_rank.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021022202, 'peerforum');
    }

    if ($oldversion < 2021022203) {

        // Define field n to be added to peerforum_relationship_nomin.
        $table = new xmldb_table('peerforum_relationship_nomin');
        $field = new xmldb_field('n', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'otheruserid');

        // Conditionally launch add field n.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field n to be added to peerforum_relationship_rank.
        $table = new xmldb_table('peerforum_relationship_rank');
        $field = new xmldb_field('n', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'otheruserid');

        // Conditionally launch add field n.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021022203, 'peerforum');
    }

    if ($oldversion < 2021022302) {

        // Define field nomination to be added to peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('nomination', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'peergraded');

        // Conditionally launch add field nomination.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key nomination (foreign) to be added to peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $key = new xmldb_key('nomination', XMLDB_KEY_FOREIGN, ['nomination'], 'peerforum_relationship_nomin', ['id']);

        // Launch add key nomination.
        $dbman->add_key($table, $key);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021022302, 'peerforum');
    }

    if ($oldversion < 2021030401) {

        // Define field confidence to be added to peerforum_relationship_nomin.
        $table = new xmldb_table('peerforum_relationship_nomin');
        $field = new xmldb_field('confidence', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '2', 'nomination');

        // Conditionally launch add field confidence.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021030401, 'peerforum');
    }

    if ($oldversion < 2021032501) {

        // Define table peerforum_delayed_post to be created.
        $table = new xmldb_table('peerforum_delayed_post');

        // Adding fields to table peerforum_delayed_post.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table peerforum_delayed_post.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('postid', XMLDB_KEY_FOREIGN, ['postid'], 'peerforum_posts', ['id']);

        // Conditionally launch create table for peerforum_delayed_post.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021032501, 'peerforum');
    }

    if ($oldversion < 2021032502) {

        // Define table peerforum_user_block to be created.
        $table = new xmldb_table('peerforum_user_block');

        // Adding fields to table peerforum_user_block.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table peerforum_user_block.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for peerforum_user_block.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021032502, 'peerforum');
    }

    if ($oldversion < 2021040101) {

        // Define index pageid-n (unique) to be added to peerforum_training_criteria.
        $table = new xmldb_table('peerforum_training_criteria');
        $index = new xmldb_index('pageid-n', XMLDB_INDEX_UNIQUE, ['pageid', 'n']);

        // Conditionally launch add index pageid-n.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index pageid-n (unique) to be dropped form peerforum_training_exercise.
        $table = new xmldb_table('peerforum_training_exercise');
        $index = new xmldb_index('n-pageid', XMLDB_INDEX_NOTUNIQUE, ['pageid', 'n']);

        // Conditionally launch drop index pageid-n.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index pageid-n (unique) to be added to peerforum_training_exercise.
        $table = new xmldb_table('peerforum_training_exercise');
        $index = new xmldb_index('pageid-n', XMLDB_INDEX_UNIQUE, ['pageid', 'n']);

        // Conditionally launch add index pageid-n.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index pageid-exid-criteriaid (unique) to be added to peerforum_training_feedback.
        $table = new xmldb_table('peerforum_training_feedback');
        $index = new xmldb_index('pageid-exid-criteriaid-grade', XMLDB_INDEX_UNIQUE, ['pageid', 'exid', 'criteriaid', 'grade']);

        // Conditionally launch add index pageid-exid-criteriaid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index pageid-exid-criteriaid (unique) to be added to peerforum_training_rgh_grade.
        $table = new xmldb_table('peerforum_training_rgh_grade');
        $index = new xmldb_index('pageid-exid-criteriaid', XMLDB_INDEX_UNIQUE, ['pageid', 'exid', 'criteriaid']);

        // Conditionally launch add index pageid-exid-criteriaid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index submissionid-exid-criteriaid (unique) to be added to peerforum_training_rating.
        $table = new xmldb_table('peerforum_training_rating');
        $index = new xmldb_index('submissionid-exid-criteriaid', XMLDB_INDEX_UNIQUE, ['submissionid', 'exid', 'criteriaid']);

        // Conditionally launch add index submissionid-exid-criteriaid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021040101, 'peerforum');
    }

    if ($oldversion < 2021050901) {

        // Define field timeexpired to be added to peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('timeexpired', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field timeexpired.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $pfs = $DB->get_records('peerforum');
        $pf = end($pfs);

        $timetoexpire = $pf->timetopeergrade * DAYSECS;
        $assigns = $DB->get_records('peerforum_time_assigned');

        foreach ($assigns as $assign) {
            $timeexpired = $assign->timeassigned + $timetoexpire;
            $DB->set_field('peerforum_time_assigned', 'timeexpired', $timeexpired,
                    array('id' => $assign->id)
            );
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021050901, 'peerforum');
    }

    if ($oldversion < 2021050902) {

        $pfs = $DB->get_records('peerforum');
        foreach ($pfs as $pfe) {
            $DB->set_field('peerforum', 'outlierdetection', 0,
                    array('id' => $pfe->id)
            );
        }

        // Changing type of field outlierdetection on table peerforum to int.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('outlierdetection', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'seeoutliers');

        // Launch change of type for field outlierdetection.
        $dbman->change_field_type($table, $field);

        // Changing precision of field outlierdetection on table peerforum to (4).
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('outlierdetection', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'seeoutliers');

        // Launch change of precision for field outlierdetection.
        $dbman->change_field_precision($table, $field);

        // Changing the default of field outlierdetection on table peerforum to 0.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('outlierdetection', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'seeoutliers');

        // Launch change of default for field outlierdetection.
        $dbman->change_field_default($table, $field);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021050902, 'peerforum');
    }

    if ($oldversion < 2021051001) {

        // Rename field peergraderid on table peerforum_peergrade to blocked.
        $table = new xmldb_table('peerforum_peergrade');
        $field = new xmldb_field('peergraderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'peergradescaleid');

        // Launch rename field peergraderid.
        $dbman->rename_field($table, $field, 'blocked');

        // Changing precision of field blocked on table peerforum_peergrade to (1).
        $table = new xmldb_table('peerforum_peergrade');
        $field = new xmldb_field('blocked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'peergradescaleid');

        // Launch change of precision for field blocked.
        $dbman->change_field_precision($table, $field);

        // Changing the default of field blocked on table peerforum_peergrade to 0.
        $table = new xmldb_table('peerforum_peergrade');
        $field = new xmldb_field('blocked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'peergradescaleid');

        // Launch change of default for field blocked.
        $dbman->change_field_default($table, $field);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021051001, 'peerforum');
    }

    if ($oldversion < 2021051801) {

        // Changing type of field outdetectvalue on table peerforum to number.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('outdetectvalue', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '1', 'showpeergrades');

        // Launch change of type for field outdetectvalue.
        $dbman->change_field_type($table, $field);

        // Launch change of precision for field outdetectvalue.
        $dbman->change_field_precision($table, $field);

        // Changing precision of field warningoutliers on table peerforum to (10, 2).
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('warningoutliers', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0', 'outlierdetection');

        $dbman->change_field_type($table, $field);

        // Launch change of precision for field warningoutliers.
        $dbman->change_field_precision($table, $field);

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021051801, 'peerforum');
    }

    if ($oldversion < 2021051802) {

        // Define field minpeerrankings to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('minpeerrankings');

        // Conditionally launch drop field minpeerrankings.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021051802, 'peerforum');
    }

    if ($oldversion < 2021111601) {

        // Define table peerforum_peergrade_users to be dropped.
        $table = new xmldb_table('peerforum_peergrade_users');

        // Conditionally launch drop table for peerforum_peergrade_users.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_groups to be dropped.
        $table = new xmldb_table('peerforum_groups');

        // Conditionally launch drop table for peerforum_groups.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_peergrade_conflict to be dropped.
        $table = new xmldb_table('peerforum_peergrade_conflict');

        // Conditionally launch drop table for peerforum_peergrade_conflict.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_blockedgrades to be dropped.
        $table = new xmldb_table('peerforum_blockedgrades');

        // Conditionally launch drop table for peerforum_blockedgrades.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_users_assigned to be dropped.
        $table = new xmldb_table('peerforum_users_assigned');

        // Conditionally launch drop table for peerforum_users_assigned.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_peergrade_subject to be dropped.
        $table = new xmldb_table('peerforum_peergrade_subject');

        // Conditionally launch drop table for peerforum_peergrade_subject.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table peerforum_relationships to be dropped.
        $table = new xmldb_table('peerforum_relationships');

        // Conditionally launch drop table for peerforum_relationships.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define field peergradesvisibility to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('peergradesvisibility');

        // Conditionally launch drop field peergradesvisibility.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field feedbackvisibility to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('feedbackvisibility');

        // Conditionally launch drop field feedbackvisibility.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field whenfeedback to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('whenfeedback');

        // Conditionally launch drop field whenfeedback.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field allowpeergrade to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('allowpeergrade');

        // Conditionally launch drop field allowpeergrade.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field expirepeergrade to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('expirepeergrade');

        // Conditionally launch drop field expirepeergrade.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field showpeergrades to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('showpeergrades');

        // Conditionally launch drop field showpeergrades.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field showafterrating to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('showafterrating');

        // Conditionally launch drop field showafterrating.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field showratings to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('showratings');

        // Conditionally launch drop field showratings.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field showpostid to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('showpostid');

        // Conditionally launch drop field showpostid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field random_distribution to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('random_distribution');

        // Conditionally launch drop field random_distribution.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field threaded_grading to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('threaded_grading');

        // Conditionally launch drop field threaded_grading.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field adv_peergrading to be dropped from peerforum.
        $table = new xmldb_table('peerforum');
        $field = new xmldb_field('adv_peergrading');

        // Conditionally launch drop field adv_peergrading.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field idlink to be dropped from peerforum_discussions.
        $table = new xmldb_table('peerforum_discussions');
        $field = new xmldb_field('idlink');

        // Conditionally launch drop field idlink.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field type to be dropped from peerforum_discussions.
        $table = new xmldb_table('peerforum_discussions');
        $field = new xmldb_field('type');

        // Conditionally launch drop field type.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field peergraders to be dropped from peerforum_posts.
        $table = new xmldb_table('peerforum_posts');
        $field = new xmldb_field('peergraders');

        // Conditionally launch drop field peergraders.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field postid to be dropped from peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('postid');

        // Conditionally launch drop field postid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field courseid to be dropped from peerforum_time_assigned.
        $table = new xmldb_table('peerforum_time_assigned');
        $field = new xmldb_field('courseid');

        // Conditionally launch drop field courseid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021111601, 'peerforum');
    }

    if ($oldversion < 2021120801) {
        global $DB;

        $fs = get_file_storage();
        $peerforums = $DB->get_records('peerforum', array());

        foreach ($peerforums as $peerforum) {
            $cm = get_coursemodule_from_instance('peerforum', $peerforum->id);
            $context = context_module::instance($cm->id);

            $trainingpages = $DB->get_records('peerforum_training_page', array('peerforum' => $peerforum->id));
            foreach ($trainingpages as $trainingpage) {

                $oldfiles = $fs->get_area_files($context->id, 'mod_peerforum', 'training', $trainingpage->id, 'id', false);
                foreach ($oldfiles as $oldfile) {
                    $filerecord = new stdClass();
                    $filerecord->filearea = 'trainingpage';
                    $fs->create_file_from_storedfile($filerecord, $oldfile);
                }

                $trainingexs = $DB->get_records('peerforum_training_exercise', array('pageid' => $trainingpage->id));
                foreach ($trainingexs as $trainingex) {

                    $oldfiles = $fs->get_area_files($context->id, 'mod_peerforum', 'training',
                            $trainingpage->id.$trainingex->n, 'id', false);
                    foreach ($oldfiles as $oldfile) {
                        $filerecord = new stdClass();
                        $filerecord->filearea = 'trainingexercise';
                        $filerecord->itemid = $trainingex->id;
                        $fs->create_file_from_storedfile($filerecord, $oldfile);
                    }
                }
            }
        }
        // Peerforum savepoint reached.
        upgrade_mod_savepoint(true, 2021120801, 'peerforum');
    }

    return true;
}
