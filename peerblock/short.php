<?php
/**
 * @package    core_peergrade
 * @copyright  2016 Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$display = optional_param('display', 1, PARAM_INT);

if (isset($userid) && empty($courseid)) {
    $context = context_user::instance($userid);
} else if (!empty($courseid) && $courseid != SITEID) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}
$PAGE->set_context($context);

$logcourseid = (empty($courseid)) ? SITEID : $courseid;
require_login($logcourseid);

$canviewalltabs = false;
if (has_capability('mod/peerforum:professorpeergrade', $context, null, false)) {
    $canviewalltabs = true;
}

$urlparams = array(
        'userid' => $userid,
        'courseid' => $courseid,
);
$url = new moodle_url('/blocks/peerblock/short.php', $urlparams);
$PAGE->set_url($url, array('display' => $display, ));

// Output the page.
$pagetitle = get_string('pluginname', 'block_peerblock');

// Check if the person can be here.
if (!$canviewalltabs) {
    print_error('error');
}

$PAGE->navbar->add($pagetitle);
$PAGE->set_title(format_string($pagetitle));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();

// Strings.
$poststopeergrade = get_string('poststopeergrade', 'block_peerblock');
$postspeergraded = get_string('postspeergraded', 'block_peerblock');
$postsexpired = get_string('postsexpired', 'block_peerblock');
$viewpeergrades = get_string('viewpeergrades', 'block_peerblock');
$manageposts = get_string('manageposts', 'block_peerblock');
$postsassigned = get_string('postsassigned', 'block_peerblock');
$manageconflicts = get_string('manageconflicts', 'block_peerblock');
$managegradersposts = get_string('managegraders_posts', 'block_peerblock');
$viewgradersstats = get_string('viewgradersstats', 'block_peerblock');
$managerelations = get_string('managerelations', 'block_peerblock');
$threadingstats = get_string('threadingstats', 'block_peerblock');
$peerranking = get_string('peer_ranking', 'block_peerblock');
$managetraining = get_string('managetraining', 'block_peerblock');
echo $OUTPUT->box_start('posts-list');

$options = array(
        MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
        MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
        MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
        MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
);

$userfilter = $userid ? array('userid' => $userid) : array();

if ($display == MANAGEPOSTS_MODE_SEEALL) {
    // All posts.
    $filters = $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEENOTGRADED) {
    // Posts to peer grade.
    $filters = array('ended' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEGRADED) {
    // Posts peergraded.
    $filters = array('peergradednot' => 0) + $userfilter;

} else if ($display == MANAGEPOSTS_MODE_SEEEXPIRED) {
    // Posts expired.
    $filters = array('expired' => 1) + $userfilter;

}
$row = get_peerblock_tabs($urlparams, $canviewalltabs, $userid == $USER->id);
echo $OUTPUT->tabtree($row, 'peergrades');
echo $OUTPUT->render(new single_select($url, 'display', $options, $display, false));

$filters += array(
        'itemtable' => 'peerforum_posts',
        'itemtableusercolumn' => 'userid',
);

$urlfactory = mod_peerforum\local\container::get_url_factory();
$vaultfactory = mod_peerforum\local\container::get_vault_factory();
$entityfactory = mod_peerforum\local\container::get_entity_factory();
$rendererfactory = mod_peerforum\local\container::get_renderer_factory();
$postsdatamapper = mod_peerforum\local\container::get_legacy_data_mapper_factory()->get_post_data_mapper();

$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();
$items = $pgmanager->get_items_from_filters($filters);

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
    }
}
krsort($items);

$table = new html_table;
$table->attributes['class'] = 'generalboxtable table table-striped table-sm';
$table->head = array(
        'Subject',
        'Author',
        'Assignee',
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

$dateformat = get_string('strftimedatetimeshort', 'langconfig');

foreach ($items as $item) {
    $peergradeobj = $b($item->peergrade);
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
            $post->get_subject(),
    ));
    $subjcell->header = true;
    $subjcell->attributes = array('class' => 'text-left align-middle');
    $authorcell = new html_table_cell(html_writer::link(
            $urlfactory->get_author_profile_url($author, $courseid),
            $author->get_full_name()
    ));
    $authorcell->attributes = array('class' => 'text-center align-middle');

    $subjcell->rowspan = $authorcell->rowspan = $nassigns;
    $row->cells = array($subjcell, $authorcell);
    $row->style = 'border-top: var(--secondary) 2px solid;';

    foreach ($peergradeobj->usersassigned as $assign) {

        $unassignurl = $peergradeobj->get_assign_url('remove', $assign->userinfo->id);
        $row->cells[] = new html_table_cell(html_writer::link(
                $urlfactory->get_user_summary_url($assign->userinfo),
                fullname($assign->userinfo),
        ));
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

            $row->cells[] = new html_table_cell($pg->feedback);
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
