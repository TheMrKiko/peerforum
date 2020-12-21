<?php

require_once('../config.php');
require_once($CFG->dirroot . '/peergrading/relationships_form.php');
require_once("$CFG->libdir/formslib.php");

global $PAGE, $USER;
$title = get_string('relquest', 'peerforum');
$PAGE->set_heading($title);

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$display = required_param('display', PARAM_INT);
$peerforumid = optional_param('peerforum', 2, PARAM_INT);

$url = new moodle_url('/peergrading/peer_nominations.php');

$urlparams = compact('userid', 'courseid', 'display');

$PAGE->set_url('/peergrading/peer_nominations.php',
        array('userid' => $userid, 'courseid' => $courseid, 'display' => $display, 'peerforumid' => $peerforumid));

if (isset($userid) && empty($courseid)) {
    $context = context_user::instance($userid);
} else if (!empty($courseid) && $courseid != SITEID) {
    $context = context_course::instance($courseid);
} else {
    $context = context_system::instance();
}

// Add courseid if modid or groupid is specified: This is used for navigation and title.
if (!empty($modid) && empty($courseid)) {
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $modid));
}

if (empty($userid)) {
    $userid = $USER->id;
}

if (!empty($modid)) {
    if (!$mod = $DB->get_record('course_modules', array('id' => $modid))) {
        print_error(get_string('invalidmodid', 'blog'));
    }
    $courseid = $mod->course;
}

if ((empty($courseid) ? true : $courseid == SITEID) && empty($userid)) {
    $COURSE = $DB->get_record('course', array('format' => 'site'));
    $courseid = $COURSE->id;
}

if (!empty($courseid)) {
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('invalidcourseid');
    }
    $courseid = $course->id;

} else {
    $coursecontext = context_course::instance(SITEID);
}

$contextid = context_course::instance($courseid);

$courseid = (empty($courseid)) ? SITEID : $courseid;

$usernode = $PAGE->navigation->find('user' . $userid, null);
if ($usernode && $courseid != SITEID) {
    $url = new moodle_url($PAGE->url);
}

require_login($courseid);
$PAGE->set_context($context);

//Instantiate relationships_form
$mform = new relationships_form();
$formdata = array('userid' => $userid, 'courseid' => $courseid, 'display' => $display);
$mform->set_data($formdata);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    $returnurl = new moodle_url('/mod/peerforum/view.php', array('id' => $courseid));
    redirect($returnurl);

} else if ($fromform = $mform->get_data()) {
    $resultsfav = $fromform->listoffavstudents;
    $resultsunfav = $fromform->listofleastfavstudents;

    if (!empty($resultsfav) && !empty($resultsunfav)) {

        //Verify if student isnt on table already
        $studenthasdonequest = student_answered_questionnaire($userid);
        //Verify if student has N different answers
        $unique_arrays = arrays_have_unique_values($resultsfav, $resultsunfav);
        //Verify if student has different fav peers
        $unique_fav = array_has_unique_values($resultsfav);
        //Verify if student has different unfav peers
        $unique_unfav = array_has_unique_values($resultsunfav);

        if (count($unique_arrays) >= 1 || $unique_fav == 0 || $unique_unfav == 0) {
            print_error("You need to submit 9 different names.");
        }

        if ($studenthasdonequest != 0) {
            print_error("You already have submitted your answers.");
        } else {
            $peersfav = implode(";", $resultsfav);
            $peersunfav = implode(";", $resultsunfav);

            $data = new stdClass();
            $data->iduser = $USER->id;
            $data->courseid = $fromform->courseid;
            $data->peersfav = $peersfav;
            $data->peersunfav = $peersunfav;
            $DB->insert_record('peerforum_relationships', $data);

        }
    } else {
        print_error('invaliddata');
    }

    if (isset($fromform->submitbutton)) {
        $returnurl = new moodle_url('/peergrading/index.php',
                array('userid' => $fromform->userid, 'courseid' => $fromform->courseid, 'display' => $fromform->display));
        redirect($returnurl);
    }
    exit;
} else {

    if (!empty($cm->id)) {
        $context = context_module::instance($cm->id);
    } else {
        $context = context_course::instance($courseid);
    }
    $PAGE->set_cacheable(false);

    if (isset($navbaraddition)) {
        $PAGE->navbar->add($navbaraddition);
    }

    echo $OUTPUT->header();
    echo get_string('questionnaire_instructions', 'peerforum');
    $mform->display();

    echo $OUTPUT->footer();
}
?>
