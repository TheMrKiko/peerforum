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
 * File containing the form definition to nominate in the peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Class to nominate in a peerforum.
 *
 * @package   mod_peerforum
 * @copyright Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_nominations_form extends moodleform {

    // Add elements to form.
    public function definition() {
        $mform = $this->_form;

        $peerforum = $this->_customdata['peerforum'];
        $allstudents = $this->_customdata['allstudents'];
        $inifields = $this->_customdata['minfields'];
        $iniilmfields = (int) $this->_customdata['inilmfields'];
        $iniillfields = (int) $this->_customdata['inillfields'];
        $addfields = $this->_customdata['fieldsatatime'];

        // Like most students.
        $mform->addElement('header', 'hlikemost', get_string('favstudents', 'peerforum'));
        $mform->setExpanded('hlikemost');

        $confscale = array(
                2 => 'Totally my feelings',
                1 => 'A solid decision',
                0 => 'Totally random',
        );

        $lmgroupitem = array();
        $lmgroupitem[] =& $mform->createElement('select', 'nominations[1]',
                '',
                $allstudents);
        $lmgroupitem[] =& $mform->createElement('select', 'confidence[1]',
                '',
                $confscale);

        $lmgroupitems = array();
        $lmgroupitems[] =& $mform->createElement('group', 'group[1]',
                get_string('choosefavstudents', 'peerforum'), $lmgroupitem, ', which is', false);
        $lmgroupitems[] =& $mform->createElement('hidden', 'ids[1]');
        $inilmfields = $iniilmfields ?: $inifields;
        $repeateloptions = array();
        $this->repeat_elements($lmgroupitems, $inilmfields, $repeateloptions,
                'repeatlm', 'addfieldslm', $addfields, 'Add {no} more field', true);


        // Like least students.
        $mform->addElement('header', 'hlikeleast', get_string('leastfavstudents', 'peerforum'));
        $mform->setExpanded('hlikeleast');

        $llgroupitem = array();
        $llgroupitem[] =& $mform->createElement('select', 'nominations[-1]',
                '',
                $allstudents);
        $llgroupitem[] =& $mform->createElement('select', 'confidence[-1]',
                '',
                $confscale);

        $llgroupitems = array();
        $llgroupitems[] =& $mform->createElement('group', 'group[-1]',
                get_string('choosefavstudents', 'peerforum'), $llgroupitem, ', which is', false);
        $llgroupitems[] =& $mform->createElement('hidden', 'ids[-1]');

        $inillfields = $iniillfields ?: $inifields;
        $this->repeat_elements($llgroupitems, $inillfields, $repeateloptions,
                'repeatll', 'addfieldsll', $addfields, 'Add {no} more field', true);

        $mform->setType('ids[1]', PARAM_INT);
        $mform->setDefault('ids[1]', 0);
        $mform->setType('ids[-1]', PARAM_INT);
        $mform->setDefault('ids[-1]', 0);

        foreach (range(0, $iniilmfields) as $i) {
            if ($i == $iniilmfields) {
                break;
            }
            $mform->disabledIf('nominations[1]['.$i.']', 'ids[1]['.$i.']', 'neq', '0');
        }

        foreach (range(0, $iniillfields) as $i) {
            if ($i == $iniillfields) {
                break;
            }
            $mform->disabledIf('nominations[-1]['.$i.']', 'ids[-1]['.$i.']', 'neq', '0');
        }


        $mform->addElement('hidden', 'peerforum');
        $mform->setType('peerforum', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, 'Submit peers');
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
        $emptymessage = 'You kinda have to select someone.';
        $repeatmessage = 'You cannot repeat someone.';
        $lmnominations = $data['nominations']['1'];
        $llnominations = $data['nominations']['-1'];
        $noms = array();
        foreach ($lmnominations as $k => $lm) {
            if (!$lm) {
                $errors['group[1]['.$k.']'] = $emptymessage;
            } else if (isset($noms[$lm])) {
                $errors['group[1]['.$k.']'] = $repeatmessage;
            }
            $noms[$lm] = true;
        }
        foreach ($llnominations as $k => $ll) {
            if (!$ll) {
                $errors['group[-1]['.$k.']'] = $emptymessage;
            } else if (isset($noms[$ll])) {
                $errors['group[-1]['.$k.']'] = $repeatmessage;
            }
            $noms[$ll] = true;
        }
        return $errors;
    }
}
