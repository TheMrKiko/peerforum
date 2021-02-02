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
 * File containing the form definition to post in the peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Class to post in a peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_peerforum_build_training_form extends moodleform {

    /**
     * Returns the options array to use in peerforum text editor
     *
     * @return array
     */
    public static function editor_options() {
        return array(
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'trusttext' => true,
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );
    }

    /**
     * Form definition
     *
     * @return void
     */
    function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $coursecontext = $this->_customdata['coursecontext'];
        $modcontext = $this->_customdata['modcontext'];
        $peerforum = $this->_customdata['peerforum'];
        $trainingpage = $this->_customdata['trainingpage'];
        $edit = $this->_customdata['edit'];

        $mform->addElement('text', 'name', 'Name of Skill'); // Add elements to your form.
        $mform->setType('name', PARAM_NOTAGS);  // Set type of element.
        $mform->addRule('name', 'lol', 'required');

        $mform->addElement('editor', 'description', 'Skill description with some examples',
                null, self::editor_options());
        $mform->setType('description', PARAM_RAW);

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', 'nameforyourheaderelement', 'Exercise {no}');
        $repeatarray[] = $mform->createElement('text', 'name_eg', 'Title of exercise {no}');
        $repeatarray[] = $mform->createElement('editor', 'description_eg', 'Exercise description', null,
                self::editor_options());

        $repeatarray[] = $mform->createElement('hidden', 'id_eg', -1);

        $repeatno = !empty($edit) ? $trainingpage->examples : 1;

        $repeateloptions = array();
        $repeateloptions['description_eg']['helpbutton'] = array('choiceoptions', 'choice');

        $mform->setType('id_eg', PARAM_INT);
        $mform->setType('name_eg', PARAM_NOTAGS);
        $mform->setType('description_eg', PARAM_RAW);

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'examples',
                'option_add_fields', 1, 'Add {no} more exercise');

        $this->add_action_buttons();

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'peerforum');
        $mform->setType('peerforum', PARAM_INT);

        $mform->addElement('hidden', 'discussion');
        $mform->setType('discussion', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'examples');
        $mform->setType('examples', PARAM_INT);
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['description']['text'])) {
            $errors['description'] = get_string('erroremptymessage', 'peerforum');
        }
        if (empty($data['name'])) {
            $errors['name'] = get_string('erroremptysubject', 'peerforum');
        }
        return $errors;
    }
}
