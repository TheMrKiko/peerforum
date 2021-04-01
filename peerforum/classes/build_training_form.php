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
        $mform = $this->_form; // Don't forget the underscore!

        $peerforum = $this->_customdata['peerforum'];
        $peergradescaleitems = $this->_customdata['peergradescaleitems'];
        $discussionsselect = $this->_customdata['discussionsselect'];
        $trainingpage = $this->_customdata['trainingpage'];
        $edit = $this->_customdata['edit'];

        $scalearray = array(PEERGRADE_UNSET_PEERGRADE => 'Rating...') + $peergradescaleitems;

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
        $mform->addElement('select', 'discussion', 'Discussion', array(0 => 'Discussion...') + $discussionsselect);
        $mform->setType('discussion', PARAM_INT);

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

        foreach (range(0, $criterias) as $k) {
            if ($k == $criterias) {
                break; // So the last element isn't run. $criteria-1 does not work.
            }

            /*-- Correct grade --*/
            $critid = $trainingpage->criteria['id'][$k];

            $repeatarray[] = $mform->createElement('html', '<h3><b>Criteria:</b> '. $trainingpage->criteria['name'][$k] .'</h3>');

            /* Select */
            $repeatarray[] = $mform->createElement('select', 'correctgrades[grade]['.$critid.']',
                    'What is the correct grade for '. $trainingpage->criteria['name'][$k] .'?', $scalearray);

            /* Id */
            $repeatarray[] = $mform->createElement('hidden', 'correctgrades[id]['.$critid.']', -1);
            $mform->setType('correctgrades[id]['.$critid.']', PARAM_INT);

            $repeatarray[] = $mform->createElement('html', '<h4>What to show if student grades as:</h4>');
            $repeatarray[] = $mform->createElement('html',
                    '<p><b>Tip</b>: If you want to reuse the same message for a different value, just paste its <i>id</i>
                    into that other text value field! <i>(only valid inside the same exercise)</i></p>');

            /*-- Feedback strings --*/
            foreach ($peergradescaleitems as $rid => $str) {
                /* Text */
                $repeatarray[] = $mform->createElement('textarea', 'feedback[feedback]['.$rid.']['.$critid.']',
                        '... a ' . $str . '? id: {'.$rid.'}{'.$critid.'}',
                        array('wrap' => 'off', 'cols' => '70', 'rows' => '1'));
                $mform->setType('feedback[feedback]['.$rid.']['.$critid.']', PARAM_NOTAGS);

                /* Id */
                $repeatarray[] = $mform->createElement('hidden', 'feedback[id]['.$rid.']['.$critid.']', -1);
                $mform->setType('feedback[id]['.$rid.']['.$critid.']', PARAM_INT);
            }
        }

        /*-------- OVERALL EXERCISE --------*/
        /*-- Correct grade --*/
        $repeatarray[] = $mform->createElement('html', '<h3><b>Whole exercise</b></h3>');

        /* Select */
        $repeatarray[] = $mform->createElement('select', 'correctgrades[grade][-1]',
                'What is the correct grade for this exercise?', $scalearray);

        /* Id */
        $repeatarray[] = $mform->createElement('hidden', 'correctgrades[id][-1]', -1);
        $mform->setType('correctgrades[id][-1]', PARAM_INT);

        $repeatarray[] = $mform->createElement('html', '<h4>What to show if student grades as:</h4>');
        $repeatarray[] = $mform->createElement('html',
                '<p><b>Tip</b>: If you want to reuse the same message for a different value, just paste its <i>id</i>
                    into that other text value field! <i>(only valid inside the same exercise)</i></p>');

        /*-- Feedback strings --*/
        foreach ($peergradescaleitems as $rid => $str) {
            /* Text */
            $repeatarray[] = $mform->createElement('textarea', 'feedback[feedback]['.$rid.'][-1]',
                    '...a ' . $str . '? id: {'.$rid.'}{-1}',
                    array('wrap' => 'off', 'cols' => '70', 'rows' => '1'));
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

        // Elements in a row need a group.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton3', 'Save and manage');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', 'Save and continue edit');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Save and view');
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
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
