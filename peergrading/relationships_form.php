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

class relationships_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG, $COURSE, $DB, $USER;

        $mform = $this->_form;

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'display');
        $mform->setType('display', PARAM_INT);

        //---------------------------------------------------------
        // FAV STUDENTS -------------------------------------------

        $mform->addElement('header', 'favpeers', get_string('favstudents', 'peerforum'));
        $mform->setExpanded('favpeers');

        $rankoptions = array(
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5'
        );
        $student_data = get_students_enroled($COURSE->id);
        $names = get_students_ordered_by_name($student_data);
        $names_updated = remove_own_student($names, $USER->id);

        $peerforum = $DB->get_record("peerforum", array('course' => $COURSE->id));
        $fields = $peerforum->peernominationsfields;
        $addfields = $peerforum->peernominationsaddfields;

        //---------------------------------------------------------
        // FAV STUDENTS -------------------------------------------

        $groupitems = array();
        $groupitems[] =&
                $mform->createElement('select', 'listoffavstudents', get_string('choosefavstudents', 'peerforum'), $names_updated);

        $repeateloptions = array();
        //$repeateloptions['listoffavstudents']['rule'] = 'required';

        $this->repeat_elements($groupitems, $fields,
                $repeateloptions, 'option_repeats', 'option_add_fields', $addfields, null, true);

        //---------------------------------------------------------
        // LEAST FAV STUDENTS -------------------------------------

        $mform->addElement('header', 'leastfavpeers', get_string('leastfavstudents', 'peerforum'));

        $lfgroupitems = array();
        $lfgroupitems[] =& $mform->createElement('select', 'listofleastfavstudents', get_string('choosefavstudents', 'peerforum'),
                $names_updated);

        $lfrepeateloptions = array();
        //$lfrepeateloptions['listofleastfavstudentsofstudents']['rule'] = 'required';

        $this->repeat_elements($lfgroupitems, $fields,
                $lfrepeateloptions, 'option_repeats', 'option_add_fields', $addfields, null, true);

        //---------------------------------------------------------
        // SUBMISSION  --------------------------------------------

        $this->add_action_buttons();

    }
}
