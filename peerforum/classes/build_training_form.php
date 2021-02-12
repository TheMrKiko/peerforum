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

        /*--------------------------------------- HEADER ---------------------------------------*/
        /* Name */
        $mform->addElement('text', 'name', 'Name of Skill'); // Add elements to your form.
        $mform->setType('name', PARAM_NOTAGS);  // Set type of element.
        $mform->addRule('name', 'lol', 'required');

        /* Description */
        $mform->addElement('editor', 'description', 'Skill description with some examples',
                null, self::editor_options());
        $mform->setType('description', PARAM_RAW);

        /* Discussion */
        $mform->addElement('text', 'discussion', 'Discussion ID');
        $mform->setType('discussion', PARAM_INT);
        $mform->addRule('discussion', 'Must be a number.', 'numeric', null, 'client');

        /*-------- CRITERIA --------*/
        $repeatarray = array();

        /* Name */
        $repeatarray[] = $mform->createElement('text', 'criteria[name]', 'Criteria {no}');
        $mform->setType('criteria[name]', PARAM_NOTAGS);

        /* Id */
        $repeatarray[] = $mform->createElement('hidden', 'criteria[id]', -1);
        $mform->setType('criteria[id]', PARAM_INT);

        /* Repeat */
        $repeatnocrit = !empty($edit) ? $trainingpage->ncriterias : 0;
        $this->repeat_elements($repeatarray, $repeatnocrit, array(), 'ncriterias',
                'criteria_add_fields', 1, 'Add {no} more criteria');

        /*--------------------------------------- EXERCISES ---------------------------------------*/
        $repeatarray = array();
        /* Header */
        $repeatarray[] = $mform->createElement('header', 'nameforyourheaderelement', 'Exercise {no}');

        /* Name */
        $repeatarray[] = $mform->createElement('text', 'exercise[name]', 'Title of exercise {no}');
        $mform->setType('exercise[name]', PARAM_NOTAGS);

        /* Description */
        $repeatarray[] = $mform->createElement('editor', 'exercise[description]', 'Exercise description', null,
                self::editor_options());
        $mform->setType('exercise[description]', PARAM_RAW);

        /* Id */
        $repeatarray[] = $mform->createElement('hidden', 'exercise[id]', -1);
        $mform->setType('exercise[id]', PARAM_INT);


        /*-------- CRITERIAS --------*/
        $criterias = (int) $trainingpage->ncriterias;
        $ratingscale = array(1 => 'Rating 1', 2 => 'Rating 2');
        $scalearray = array(RATING_UNSET_RATING => 'Rating...') + $ratingscale;

        foreach (range(0, $criterias) as $k) {
            if ($k == $criterias) {
                break; // So the last element isn't run. $criteria-1 does not work.
            }

            /*-- Correct grade --*/
            $critid = $trainingpage->criteria['id'][$k];

            /* Select */
            $repeatarray[] = $mform->createElement('select', 'correctgrades[grade]['.$critid.']',
                    'What is the correct grade for '. $trainingpage->criteria['name'][$k] .'?', $scalearray);

            /* Id */
            $repeatarray[] = $mform->createElement('hidden', 'correctgrades[id]['.$critid.']', -1);
            $mform->setType('correctgrades[id]['.$critid.']', PARAM_INT);

            /*-- Feedback strings --*/
            foreach ($ratingscale as $rid => $str) {
                /* Text */
                $repeatarray[] = $mform->createElement('text', 'feedback[feedback]['.$rid.']['.$critid.']',
                        'What to show if student grades this as a ' . $str . '? ({'.$rid.'})');
                $mform->setType('feedback[feedback]['.$rid.']['.$critid.']', PARAM_NOTAGS);

                /* Id */
                $repeatarray[] = $mform->createElement('hidden', 'feedback[id]['.$rid.']['.$critid.']', -1);
                $mform->setType('feedback[id]['.$rid.']['.$critid.']', PARAM_INT);
            }
        }

        /*-------- OVERALL EXERCISE --------*/
        /*-- Correct grade --*/
        /* Select */
        $repeatarray[] = $mform->createElement('select', 'correctgrades[grade][-1]',
                'What is the correct grade for this exercise?', $scalearray);

        /* Id */
        $repeatarray[] = $mform->createElement('hidden', 'correctgrades[id][-1]', -1);
        $mform->setType('correctgrades[id][-1]', PARAM_INT);

        /*-- Feedback strings --*/
        foreach ($ratingscale as $rid => $str) {
            /* Text */
            $repeatarray[] = $mform->createElement('text', 'feedback[feedback]['.$rid.'][-1]',
                    'What to show if student grades this exercise as a ' . $str . '? ({'.$rid.'})');
            $mform->setType('feedback[feedback]['.$rid.'][-1]', PARAM_NOTAGS);

            /* Id */
            $repeatarray[] = $mform->createElement('hidden', 'feedback[id]['.$rid.'][-1]', -1);
            $mform->setType('feedback[id]['.$rid.'][-1]', PARAM_INT);
        }

        /*-------- REPEAT --------*/
        $repeateloptions = array();
        $repeatno = !empty($edit) ? $trainingpage->exercises : 0;
        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'exercises',
                'option_add_fields', 1, 'Add {no} more exercise');

        /*--------------------------------------- HIDDEN VARS ---------------------------------------*/
        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'peerforum');
        $mform->setType('peerforum', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'exercises');
        $mform->setType('exercises', PARAM_INT);

        $this->add_action_buttons();
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
