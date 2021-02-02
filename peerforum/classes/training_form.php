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

        $examples = (int) $trainingpage->examples;

        $scalearray = array(RATING_UNSET_RATING => 'Rating...') + $rating->settings->scale->scaleitems;

        foreach (range(0, $examples - 1) as $k) {
            $exid = $trainingpage->id_eg[$k];
            $mform->addElement('header', 'header' . $k, $trainingpage->name_eg[$k]);
            $mform->addElement('html', $trainingpage->description_eg[$k]);
            $mform->addElement('select', 'grades['.$exid.']', 'How would you grade this?', $scalearray);

            if ($submitted) {
                $grade = $trainingsubmission->grades[$exid]->grade;
                $mform->addElement('html', '<p style="color: red;"><b>Wrong</b>: Look at the lights, lol. ('.$grade.') </p>');
            }

            $mform->addRule('grades['.$exid.']', get_string('error'), 'required');
        }

        $this->add_action_buttons();

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'peerforum');
        $mform->setType('peerforum', PARAM_INT);

        $mform->addElement('hidden', 'page');
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'examples');
        $mform->setType('examples', PARAM_INT);

        $mform->addElement('hidden', 'open');
        $mform->setType('open', PARAM_INT);
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
                $errors['grades['.$exid.']'] = get_string('erroremptysubject', 'peerforum');
            }
        }
        return $errors;
    }
}
