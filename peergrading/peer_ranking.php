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

class peer_ranking_form extends moodleform {

    //Add elements to form
    public function definition() {
        global $CFG, $COURSE, $DB, $USER;

        $mform = $this->_form;
        $torank = $this->_customdata;

        //---------< PEERGRADE PEER RANKING local configurations >----------//

        $permission = CAP_ALLOW;
        $rolenamestring = null;

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'display');
        $mform->setType('display', PARAM_INT);

        $mform->addElement('hidden', 'peeruserid');
        $mform->setType('peeruserid', PARAM_INT);

        $optionsstudents = array(
            //null  => 'Select an option',
                0 => '0',
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
        );

        $groupitems = array();
        $groupitems[] =& $mform->createElement('select', 'rankingstudents', get_string('classifyranking', 'block_peerblock'),
                $optionsstudents);

        $repeateloptions = array();

        $this->repeat_elements($groupitems, count($torank),
                $repeateloptions, 'option_repeats', 'option_add_fields', 0, null, false);

        //---------------------------------------------------------
        // SUBMISSION  --------------------------------------------

        $this->add_action_buttons($cancel = false);

    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);
        if ($data['rankingstudents'] == 'null') {
            $errors['rankingstudents'] = get_string('required');
        }

    }

}
