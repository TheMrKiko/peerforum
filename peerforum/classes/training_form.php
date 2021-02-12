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

use mod_peerforum\local\container;

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
class mod_peerforum_training_form extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    function definition() {
        $mform = $this->_form; // Don't forget the underscore!

        $modcontext = $this->_customdata['modcontext'];
        $peerforum = $this->_customdata['peerforum'];
        $trainingpage = $this->_customdata['trainingpage'];
        $trainingsubmission = $this->_customdata['submission'];
        $submitted = isset($trainingsubmission) && !empty($trainingsubmission);

        $ratingoptions = (object) [
                'context' => $modcontext,
                'component' => 'mod_peerforum',
                'ratingarea' => 'post',
                'items' => array((object) [
                        'id' => 0,
                        'userid' => 0
                ]),
                'aggregate' => $peerforum->peergradeassessed,
                'scaleid' => $peerforum->peergradescale,
                'userid' => 0,
                'peerforum' => $peerforum,
        ];

        $rm = container::get_manager_factory()->get_rating_manager();
        $rating = $rm->get_ratings($ratingoptions)[0]->rating;

        $exercises = (int) $trainingpage->exercises;

        $scalearray = array(RATING_UNSET_RATING => 'Rating...') + $rating->settings->scale->scaleitems;

        /*--------------------------------------- EXERCISES ---------------------------------------*/
        foreach (range(0, $exercises) as $k) {
            if ($k == $exercises) {
                break; // So the last element isn't run. $criteria-1 does not work.
            }

            $exid = $trainingpage->exercise['id'][$k];
            $n = $trainingpage->exercise['n'][$k];

            /* Header */
            $mform->addElement('header', 'header' . $k, $trainingpage->exercise['name'][$k]);

            /* Description */
            $mform->addElement('html', $trainingpage->exercise['description'][$k]);

            /*-------- CRITERIAS --------*/
            $criterias = (int) $trainingpage->ncriterias;
            foreach (range(0, $criterias) as $c) {
                if ($c == $criterias) {
                    break; // So the last element isn't run. $criteria-1 does not work.
                }

                $critid = $trainingpage->criteria['id'][$c];

                /* Grade */
                $mform->addElement('select', 'grades[grade]['.$critid.']['.$exid.']', 'How would you grade this for ' .
                        $trainingpage->criteria['name'][$c] . '?', $scalearray);
                $mform->addRule('grades[grade]['.$critid.']['.$exid.']', get_string('error'), 'required');

                /* Feedback */
                if ($submitted) {
                    $grade = $trainingsubmission->grades['grade'][$critid][$exid];
                    $correctgrades = $trainingpage->correctgrades['grade'][$critid][$n];
                    if ($grade == $correctgrades) {
                        $mform->addElement('html', '<p style="color: #00ff00;"><b>Right</b>: Look at the lights, lol. (' .$grade.') </p>');
                    } else {
                        $mform->addElement('html', '<p style="color: #ff0000;"><b>Wrong</b>: Look at the lights, lol. (' .$grade.') </p>');
                    }
                }
            }


            /*-------- OVERALL EXERCISE --------*/
            /* Grade */
            $mform->addElement('select', 'grades[grade][-1]['.$exid.']', 'How would you grade this exercise?', $scalearray);
            $mform->addRule('grades[grade][-1]['.$exid.']', get_string('error'), 'required');

            /* Feedback */
            if ($submitted) {
                $grade = $trainingsubmission->grades['grade'][-1][$exid];
                $correctgrades = $trainingpage->correctgrades['grade'][-1][$n];
                if ($grade == $correctgrades) {
                    $mform->addElement('html', '<p style="color: #00ff00;"><b>Right</b>: Look at the lights, lol. (' .$grade.') </p>');
                } else {
                    $mform->addElement('html', '<p style="color: #ff0000;"><b>Wrong</b>: Look at the lights, lol. (' .$grade.') </p>');
                }
            }
        }

        /*--------------------------------------- HIDDEN VARS ---------------------------------------*/
        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'peerforum');
        $mform->setType('peerforum', PARAM_INT);

        $mform->addElement('hidden', 'page');
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'exercises');
        $mform->setType('exercises', PARAM_INT);

        $mform->addElement('hidden', 'open');
        $mform->setType('open', PARAM_INT);

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
        foreach ($data['grades'] as $exid => $g) {
            if ($g == RATING_UNSET_RATING) {
                $errors['grades[grade]['.$exid.']'] = get_string('erroremptysubject', 'peerforum');
            }
        }
        return $errors;
    }
}
