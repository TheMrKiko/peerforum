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
 * File containing the form definition to rank in the peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_peerblock;

require_once($CFG->libdir . '/formslib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Class to rank in a peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rankings_form extends \moodleform {

    // Add elements to form.
    public function definition() {
        $mform = $this->_form;

        $students = $this->_customdata['students'];
        $scale = $this->_customdata['scale'];

        foreach ($students as $sid => $studentname) {
            $mform->addElement('select', 'rankings[' . $sid . ']',
                    $students[$sid],
                    $scale);
            $mform->addElement('hidden', 'ids[' . $sid . ']');
        }

        $mform->setType('ids', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

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
        $ranks = $data['rankings'] ?? array();
        foreach ($ranks as $rid => $r) {
            if ($r == RATING_UNSET_RATING) {
                $errors['rankings[' . $rid . ']'] = 'You can be a Mariah and don\'t know her,
                BUT YOU GOTTA SAY YOU DONT KNOW HER. oR DO.';
            }
        }
        return $errors;
    }
}
