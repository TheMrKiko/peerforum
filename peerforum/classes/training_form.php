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
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        $course = $this->_customdata['course'];
        $cm = $this->_customdata['cm'];
        $coursecontext = $this->_customdata['coursecontext'];
        $modcontext = $this->_customdata['modcontext'];
        $peerforum = $this->_customdata['peerforum'];
        $trainingpage = $this->_customdata['trainingpage'];

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
            $mform->addElement('header', 'header' . $k, $trainingpage->name_eg[$k]);
            $availablefromgroup = array();
            $availablefromgroup[] =& $mform->createElement('html', $trainingpage->description_eg[$k]);
            $mform->addGroup($availablefromgroup, 'availablefromgroup'.$k);
            // $mform->addElement('html', $trainingpage->description_eg[$k]);
            $mform->addElement('select', 'rating'.$k, 'How would you grade this?', $scalearray);
            // $mform->addRule('rating'.$k, get_string('error'), 'required');
            $mform->addElement('html', '<p style="color: red;"><b>Wrong</b>: Look at the lights, lol.</p>');
            //$mform->hideIf('rating'.$k, 'submitted', 'eq', 1);
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

        $mform->addElement('hidden', 'submitted');
        $mform->setType('submitted', PARAM_INT);
    }

    function definition_after_data() {
        $mformpage =& $this->_form;
        if ($this->is_submitted()) {
            $rating = $mformpage->getElement('availablefromgroup1');
            $rating->_elements[0]->_attributes['hidden'] = true;
            //$value = $rating->_values[0];
        }
        //$config_text =& $mform->getElement(‘config_text’);
        //$config_checkbox =& $mform->getElement(‘config_checkbox’);

        //if (isset($config_checkbox->_attributes[‘checked’])) {
        //    $config_text->attributes[‘value’] = "The checkbox is checked";
        //} // if
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
        /*if (($data['timeend'] != 0) && ($data['timestart'] != 0) && $data['timeend'] <= $data['timestart']) {
            $errors['timeend'] = get_string('timestartenderror', 'peerforum');
        }
        if (empty($data['description']['text'])) {
            $errors['description'] = get_string('erroremptymessage', 'peerforum');
        }
        if (empty($data['name'])) {
            $errors['name'] = get_string('erroremptysubject', 'peerforum');
        }*/
        return $errors;
    }
}
