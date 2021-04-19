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
 * Displays a compact list of posts with peergrades.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$urlfactory = mod_peerforum\local\container::get_url_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$postsdatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory()->get_post_data_mapper();
$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
$rmanager = mod_peerforum\local\container::get_manager_factory()->get_rating_manager();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$display = optional_param('display', 1, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/short.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
        'display' => $display,
));

$PAGE->requires->css('/blocks/peerblock/styles.css');

set_peergradepanel_page($courseid, $userid, $url, 'peergrades', true, false);

// Build filter options.
if ($display == MANAGEPOSTS_MODE_SEEALL) {
    // All posts.
    $filters = array();

} else if ($display == MANAGEPOSTS_MODE_SEENOTGRADED) {
    // Posts to peer grade.
    $filters = array('ended' => 0);

} else if ($display == MANAGEPOSTS_MODE_SEEGRADED) {
    // Posts peergraded.
    $filters = array('peergradednot' => 0);

} else if ($display == MANAGEPOSTS_MODE_SEEEXPIRED) {
    // Posts expired.
    $filters = array('expired' => 1);

}

// Manage users.
$filters += $userid ? array('userid' => $userid) : array();

$PAGE->requires->js_call_amd('block_peerblock/truncate_text', 'init');

$options = get_peerblock_select_options();
echo $OUTPUT->box_start('posts-list');
echo $OUTPUT->render(new single_select($url, 'display', $options, $display, false));

$filters += array(
        'itemtable' => 'peerforum_posts',
        'itemtableusercolumn' => 'userid',
);

// Gets posts from filters.
$items = $pgmanager->get_items_from_filters($filters);
$ritems = array();

if (!empty($items)) {
    $postids = array_map(static function($item) {
        return $item->itemid;
    }, $items);
    $postvault = $vaultfactory->get_post_vault();
    $posts = $postvault->get_from_ids(array_unique($postids));

    $discussionids = array_reduce($posts, function($carry, $post) {
        $did = $post->get_discussion_id();
        if (!in_array($did, $carry)) {
            $carry[] = $did;
        }
        return $carry;
    }, array());
    $discussions = $vaultfactory->get_discussion_vault()->get_from_ids($discussionids);

    $peerforumids = array_reduce($discussions, function($carry, $discussion) {
        $fid = $discussion->get_peerforum_id();
        if (!in_array($fid, $carry)) {
            $carry[] = $fid;
        }
        return $carry;
    }, array());
    $peerforums = $vaultfactory->get_peerforum_vault()->get_from_ids($peerforumids);

    $authorvault = $vaultfactory->get_author_vault();
    $authors = $authorvault->get_authors_for_posts($posts);

    $items = array();
    foreach ($peerforums as $peerforum) {
        $pfposts = array_filter($posts, function($post) use ($USER, $userid, $peerforum, $discussions) {
            $did = $post->get_discussion_id();
            if ($discussions[$did]->get_peerforum_id() != $peerforum->get_id()) {
                return false;
            }

            $pfcontext = $peerforum->get_context();
            return !($userid != $USER->id && $peerforum->is_remainanonymous() &&
                    !has_capability('mod/peerforum:professorpeergrade', $pfcontext, null, false));
        });

        $pfitems = $postsdatamapper->to_legacy_objects($pfposts);
        $peergradeoptions = (object) ([
                        'items' => $pfitems,
                        'userid' => $USER->id,
                ] + $peerforum->get_peergrade_options());
        $items += $pgmanager->get_peergrades($peergradeoptions);

        $ratingoptions = (object) [
                'context' => $peerforum->get_context(),
                'component' => 'mod_peerforum',
                'ratingarea' => 'post',
                'items' => $pfitems,
                'aggregate' => $peerforum->get_rating_aggregate(),
                'scaleid' => $peerforum->get_scale(),
                'userid' => $USER->id,
                'assesstimestart' => $peerforum->get_assess_time_start(),
                'assesstimefinish' => $peerforum->get_assess_time_finish()
        ];
        $ritems += $rmanager->get_ratings($ratingoptions);
    }
}

// Sort posts.
krsort($items);

$table = new html_table;
$table->attributes['class'] = 'generalboxtable table table-striped table-sm';
$table->head = array(
        'Subject',
        'Author',
        'Peer grades',
        'Rating',
        'Assignee',
        'Relation',
        'Date assigned',
        'Unassign & remove',
        'State',
        'Peer grade',
        'Feedback',
        'Date modified',
        'Time left',
);
$table->align = array(
        'left',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
        'center',
);

$table->data = array();
$a = static function ($p) : \mod_peerforum\local\entities\post {
    return $p;
};
$b = static function ($p) : peergrade {
    return $p;
};
$c = static function ($p) : \mod_peerforum\local\entities\author {
    return $p;
};
$d = static function ($p) : rating {
    return $p;
};

$dateformat = get_string('strftimedatetimeshort', 'langconfig');

foreach ($items as $item) {
    $peergradeobj = $b($item->peergrade);
    $ratingobj = $d($ritems[$item->id]->rating);
    $post = $a($posts[$item->id]);
    $author = $c($authors[$post->get_author_id()]);

    $peergradescalemenu = $peergradeobj->settings->peergradescale->peergradescaleitems;
    $nassigns = count($peergradeobj->usersassigned) ?? 0;

    $peergradeoptions = new stdClass;
    $peergradeoptions->itemid = $peergradeobj->itemid;
    $peergradeoptions->context = $peergradeobj->context;
    $peergradeoptions->component = $peergradeobj->component;
    $peergradeoptions->peergradearea = $peergradeobj->peergradearea;
    $allpeergrades = $pgmanager->get_all_peergrades_for_item($peergradeoptions);

    $row = new html_table_row();

    $subjcell = new html_table_cell(html_writer::link(
            $urlfactory->get_view_post_url_from_post($post),
            $post->get_subject() . ($ratingobj->count ? '' : '*'),
    ));
    $subjcell->header = true;
    $subjcell->attributes = array('class' => 'text-left align-middle');
    $authorcell = new html_table_cell(html_writer::link(
            $urlfactory->get_author_profile_url($author, $courseid),
            $author->get_full_name()
    ));
    $authorcell->attributes = array('class' => 'text-center align-middle');

    $peergradecell = new html_table_cell($peergradeobj->count ? $peergradeobj->get_aggregate_string() : '-');
    $peergradecell->attributes = array('class' => 'text-center align-middle');

    $ratingcell = new html_table_cell($ratingobj->count ? $ratingobj->get_aggregate_string() : '-');
    $ratingcell->attributes = array('class' => 'text-center align-middle');

    $subjcell->rowspan = $authorcell->rowspan = $peergradecell->rowspan = $ratingcell->rowspan = $nassigns;
    $row->cells = array($subjcell, $authorcell, $peergradecell, $ratingcell);
    $row->style = 'border-top: var(--secondary) 2px solid;';

    foreach ($peergradeobj->usersassigned as $assign) {

        $unassignurl = $peergradeobj->get_assign_url('remove', $assign->userinfo->id);
        $row->cells[] = new html_table_cell(html_writer::link(
                new moodle_url($url, array('userid' => $assign->userinfo->id)),
                fullname($assign->userinfo),
        ));
        end($row->cells)->attributes = array('class' => 'text-center align-middle');

        $row->cells[] = new html_table_cell(
                $assign->nomination ? 'Like '
                . ($assign->nominationvalue > 0 ? 'most' : 'least')
                : 'None'
        );
        end($row->cells)->attributes = array('class' => 'text-center align-middle');

        $row->cells[] = new html_table_cell(userdate($assign->timeassigned, $dateformat));
        end($row->cells)->attributes = array('class' => 'text-center align-middle');

        $singlebutton = new single_button($unassignurl, 'Delete');
        $singlebutton->add_confirm_action('Are you sure you wanna de assign and delete the peer grade?
            This is irreversible. Also, if this is the LAST assign left, you won\'t be able to assign anyone else. ');

        $row->cells[] = new html_table_cell($OUTPUT->render($singlebutton));
        end($row->cells)->attributes = array('class' => 'text-center align-middle');

        if (!empty($assign->peergraded)) {
            // When already peer graded.
            $row->cells[] = new html_table_cell('GRAD');
            end($row->cells)->style = 'color: #339966';
            end($row->cells)->attributes = array('class' => 'bold text-center align-middle');

            $pg = $allpeergrades[$assign->peergraded];
            $row->cells[] = new html_table_cell($peergradescalemenu[$pg->peergrade]);
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
            $row->cells[] = new html_table_cell(
                html_writer::empty_tag('input',
                    array('type' => 'checkbox', 'id' => 'expanded' . $pg->id, 'class' => 'input-collapsable'))
                . html_writer::span($pg->feedback, '', array('data-region-content' => 'peerblock-collapsable-text'))
                . html_writer::label('Read more', 'expanded' . $pg->id, true, array('role' => 'button')));
            end($row->cells)->attributes = array('class' => 'text-left w-25 align-middle');

            $row->cells[] = new html_table_cell(userdate($pg->timemodified, $dateformat));
            end($row->cells)->attributes = array('class' => 'text-center align-middle');

            $row->cells[] = new html_table_cell('-');
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
        } else if (!empty($assign->expired)) {
            // When expired.
            $row->cells[] = new html_table_cell('EXP');
            end($row->cells)->style = 'color: #cc3300';
            end($row->cells)->attributes = array('class' => 'bold text-center align-middle');

            $row->cells[] = new html_table_cell('-');
            end($row->cells)->colspan = 4;
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
        } else if (!empty($assign->ended)) {
            // When ended but not peer graded.
            $row->cells[] = new html_table_cell('END');
            end($row->cells)->style = 'color: grey';
            end($row->cells)->attributes = array('class' => 'bold text-center align-middle');

            $row->cells[] = new html_table_cell('-');
            end($row->cells)->colspan = 4;
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
        } else {
            // When waiting for grade.
            $row->cells[] = new html_table_cell('TODO');
            end($row->cells)->attributes = array('class' => 'bold text-center align-middle');

            $row->cells[] = new html_table_cell('-');
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
            $row->cells[] = end($row->cells);
            $row->cells[] = end($row->cells);

            $row->cells[] = new html_table_cell($assign->get_time_to_expire());
            end($row->cells)->attributes = array('class' => 'text-center align-middle');
        }

        $table->data[] = $row;
        $row = new html_table_row();
    }
}
echo !empty($items) ? html_writer::table($table) : 'No posts to show.';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
