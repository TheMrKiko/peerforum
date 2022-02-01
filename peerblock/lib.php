<?php

/**
 * @package    block
 * @subpackage peerblock
 * @copyright  2016 Jessica Ribeiro
 * @author     Jessica Ribeiro <jessica.ribeiro@tecnico.ulisboa.pt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

/**
 * @param moodle_url $url
 * @param bool $isprofessor
 * @param bool $isself
 * @return array
 */
function get_peerblock_tabs($url, $isprofessor = false, $isself = true) {
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
    $viewgradesgraph = get_string('viewgradesgraph', 'block_peerblock');
    $managerelations = get_string('managerelations', 'block_peerblock');
    $threadingstats = get_string('threadingstats', 'block_peerblock');
    $peerranking = get_string('peer_ranking', 'block_peerblock');
    $managetraining = get_string('managetraining', 'block_peerblock');

    $display = $url->get_param('display') ?: MANAGEPOSTS_MODE_SEEALL;
    $params = array(
            'userid' => $url->get_param('userid') ?: 0,
            'courseid' => $url->get_param('courseid') ?: 0,
    );

    $row[] = new tabobject('manageposts', new moodle_url('/blocks/peerblock/summary.php',
                    $params + array('display' => $display, 'expanded' => true, )), $postsassigned);
    if ($isprofessor) {
        $row[] = new tabobject('peergrades',
                new moodle_url('/blocks/peerblock/short.php',
                        $params + array('display' => $display)), $viewpeergrades);
    }
    if (!$isprofessor && $isself) {
        $row[] = new tabobject('peerranking',
                new moodle_url('/blocks/peerblock/rankings.php',
                        $params), $peerranking);
    }
    if ($isprofessor) {
        $row[] = new tabobject('viewgradersstats',
                new moodle_url('/blocks/peerblock/user.php',
                        $params), $viewgradersstats);
    }
    if ($isprofessor) {
        $row[] = new tabobject('viewgradesgraph',
                new moodle_url('/blocks/peerblock/graph.php',
                        $params), $viewgradesgraph);
    }
    if ($isprofessor) {
        $row[] = new tabobject('managerelations',
                new moodle_url('/blocks/peerblock/relationships.php',
                        $params), $managerelations);
    }
    /*if ($isprofessor) {
        $row[] = new tabobject('manageconflicts',
                new moodle_url('/blocks/peerblock/conflicts.php',
                        $params), $manageconflicts);
    }*/
    return $row;
}

/**
 * @return array
 */
function get_peerblock_select_options() {
    return array(
            MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
    );
}

/**
 * Returns array of peerforum posts filters
 *
 * @return array
 */
function peerforum_get_manage_posts_filters() {
    return array(MANAGEPOSTS_MODE_SEEALL => get_string('managepostsmodeseeall', 'peerforum'),
            MANAGEPOSTS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTGRADED => get_string('managepostsmodeseenotgraded', 'peerforum'),
            MANAGEPOSTS_MODE_SEEEXPIRED => get_string('managepostsmodeseeexpired', 'peerforum'),
            MANAGEPOSTS_MODE_SEENOTEXPIRED => get_string('managepostsmodeseenotexpired', 'peerforum'));
}

/**
 * Returns array of peerforum relationships filters
 *
 * @return array
 */
function peerforum_get_manage_relations_filters($rankings, $nominations) {
    if ($rankings && $nominations) {
        return array(RELATIONSHIPS_MODE_NOMINATIONS => get_string('managerealtionshipsmodenominations', 'peerforum'),
                RELATIONSHIPS_MODE_RANKINGS => get_string('managerealtionshipsmoderankings', 'peerforum'));
    }
}

/**
 * Returns array of peerforum graders filters
 *
 * @return array
 */
function peerforum_get_graders_posts_filters() {
    return array(MANAGEGRADERS_MODE_SEEALL => get_string('managegradersmodeseeall', 'peerforum'),
            MANAGEGRADERS_MODE_SEEGRADED => get_string('managepostsmodeseegraded', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTGRADED => get_string('managegradersmodeseenotgraded', 'peerforum'),
            MANAGEGRADERS_MODE_SEEEXPIRED => get_string('managegradersmodeseeexpired', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTEXPIRED => get_string('managegradersmodeseenotexpired', 'peerforum')
    );
}

/**
 * Returns array of peerforum graders filters
 *
 * @return array
 */
function peerforum_get_manage_graders_filters() {
    return array(MANAGEGRADERS_MODE_SEEEXPIRED => get_string('managegradersmodeseeexpired', 'peerforum'),
            MANAGEGRADERS_MODE_SEENOTGRADED => get_string('managegradersmodeseenotgraded', 'peerforum'));
}

/**
 * Returns array of peerforum view peergrades filters
 *
 * @return array
 */
function peerforum_get_view_peergrades_filters() {
    return array(VIEWPEERGRADES_MODE_SEEALL => get_string('viewpeergradesmodeseeall', 'peerforum'),
            VIEWPEERGRADES_MODE_SEEWARNINGS => get_string('viewpeergradesmodeseewarnings', 'peerforum'),
            VIEWPEERGRADES_MODE_SEEOUTLIERS => get_string('viewpeergradesmodeseeoutliers', 'peerforum'));
}

/**
 * @param $fromform
 * @param $mform
 * @param $nominationsfull
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function peerblock_edit_rankings($data, $mform) {
    global $DB;

    $rankings = \mod_peerforum\local\vaults\training_page::turn_outside_in($data->rankings, array('userid'));

    foreach ($rankings as $ranking) {
        if (!$ranking->id) {
            continue;
        }
        $DB->set_field('peerforum_relationship_rank', 'ranking', $ranking->ranking, array('id' => $ranking->id));
    }

    return true;
}

function set_peergradepanel_page($courseid, $userid, $url, $tab, $onlyforprofs, $onlyforself, $stdscanviewothers = false) {
    global $DB, $CFG, $PAGE, $USER, $OUTPUT;

    $PAGE->set_url($url);

    if ($courseid == SITEID) {
        print_error('invalidcourseid');
    }

    require_login($courseid, false);

    $coursecontext = context_course::instance($courseid, MUST_EXIST);
    $isprofessor = has_capability('mod/peerforum:professorpeergrade', $coursecontext, $USER);
    $iscurrentuser = ($USER->id == $userid);

    // Check if the person can be here.
    if (($onlyforprofs && !$isprofessor) || ($onlyforself && !$iscurrentuser) ||
            (!$stdscanviewothers && !$isprofessor && !$iscurrentuser) || isguestuser()) {
        print_error('error');
    }

    $course = get_course($courseid, false);

    if ($userid) {
        require_once($CFG->dirroot . '/user/lib.php');

        $user = !$iscurrentuser ? $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST) : $USER;
        $usercontext = context_user::instance($userid, MUST_EXIST);

        // Check if the requested user is the guest user.
        if (isguestuser($user)) {
            // May as well just bail aggressively here.
            print_error('invaliduserid');
        }

        if (!user_can_view_profile($user, $course, $usercontext)) {
            print_error('cannotviewusersposts', 'peerforum');
        }

        // Make sure the user has not been deleted.
        if ($user->deleted) {
            $PAGE->set_title(get_string('userdeleted'));
            $PAGE->set_context(context_system::instance());
            echo $OUTPUT->header();
            echo $OUTPUT->heading($PAGE->title);
            echo $OUTPUT->footer();
            die;
        }
    }

    $coursefullname = format_string($course->fullname, true, array('context' => $coursecontext));

    $a = new stdClass;
    $a->coursename = $coursefullname;
    if ($userid) {
        $userfullname = fullname($user);
        $a->fullname = $userfullname;
        $pagetitle = get_string('pgbyuserincourse', 'block_peerblock', $a);
    } else {
        $pagetitle = get_string('pgincourse', 'block_peerblock', $a);
    }

    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($coursefullname);

    if ($userid) {
        $PAGE->navigation->extend_for_user($user);
        $usernode = $PAGE->navigation->find('user' . $userid, null);
        $usernode->make_active();

        if ($isprofessor || $stdscanviewothers) {
            $nuurl = new moodle_url($url, array('userid' => 0));
            $PAGE->set_button(html_writer::link($nuurl, 'Clear selected user'));
        }
    }

    $PAGE->navbar->add('Peer grading');

    echo $OUTPUT->header();

    if ($userid) {
        $userheading = array(
                'heading' => fullname($user),
                'user' => $user,
                'usercontext' => $usercontext
        );
        echo $OUTPUT->context_header($userheading, 2);
    }

    $row = get_peerblock_tabs($url, $isprofessor, $iscurrentuser);
    echo $OUTPUT->tabtree($row, $tab);
}

function get_all_peerforum_context_for_course(int $courseid): array {
    $modinfo = get_fast_modinfo($courseid);
    $peerforums = $modinfo->get_instances_of('peerforum');
    return array_map(static function ($pf) {
        return context_module::instance($pf->id);
    }, $peerforums);
}

function peerblock_get_items_basefilters(int $courseid): array {
    return array(
            'component' => 'mod_peerforum',
            'peergradearea' => 'post',
            'contextids' => array_map(static function ($c) {
                return $c->id;
            }, get_all_peerforum_context_for_course($courseid)),
    );
}