<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/course/moodleform_mod.php');

if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

class settings_form extends moodleform {

    //Add elements to form
    public function definition() {
        global $CFG, $COURSE, $DB, $USER;

        $mform = $this->_form;
        $courseid = $this->_customdata['my_array']['cid'];

        //---------< PEERGRADE TOPIC ATTRIBUTION local configurations >----------//

        $permission = CAP_ALLOW;
        $rolenamestring = null;

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'display');
        $mform->setType('display', PARAM_INT);

        //Enable advanced topic distribution on PeerForum
        $mform->addElement('selectyesno', 'attribution_advanced', get_string('enable_attribution_advanced', 'peerforum'));
        $mform->setDefault('attribution_advanced', 0);
        $mform->addHelpButton('attribution_advanced', 'enable_attribution_advanced_help', 'peerforum');

        $yesno = array(0 => get_string('no'),
                1 => get_string('yes'));

        //Present topics
        $discussiontopics = get_discussions_name($COURSE->id);
        $selecttopics =
                $mform->addElement('select', 'topicstoattribute', get_string('topicstoattribute', 'peerforum'), $discussiontopics);
        $selecttopics->setMultiple(true);

        //Choose the type of grading attribution
        $attrtypes = array(
                get_string('specifictopic', 'peerforum'),
                get_string('randomtopic', 'peerforum')
        );

        $mform->addElement('select', 'typestoattribute', get_string('typestoattribute', 'peerforum'), $attrtypes);
        $mform->disabledIf('typestoattribute', 'attribution_advanced', 'eq', 0);
        $mform->disabledIf('topicstoattribute', 'attribution_advanced', 'eq', 0);
        $mform->addHelpButton('topicstoattribute', 'topicsattrinstructions', 'peerforum');

        $optionsstudents = array(
                1 => '6',
                2 => '10',
                3 => '15',
                4 => '20',
                5 => '25',
                6 => '30',
        );

        $selecttopics = $mform->addElement('select', 'numberofstudents', get_string('studentsperpost', 'block_peerblock'),
                $optionsstudents);
        $mform->disabledIf('numberofstudents', 'attribution_advanced', 'eq', 0);
        $mform->disabledIf('numberofstudents', 'typestoattribute', 'eq', 1);

        //---------------------------------------------------------
        // SUBMISSION  --------------------------------------------

        $this->add_action_buttons($cancel = false);

    }
}
