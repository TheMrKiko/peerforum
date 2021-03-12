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

    public static function replace_placeholders($original, $avaliable, $n, $visited) {
        $original = trim($original);
        $bpos = strpos($original, '{');
        if ($bpos === false) {
            return $original;
        }
        $mpos = strpos($original, '}{', $bpos + 1);
        if ($mpos === false) {
            return $original;
        }
        $epos = strpos($original, '}', $mpos + 1);
        if ($epos === false) {
            return $original;
        }
        $substr = substr($original, $bpos + 1, $epos - $bpos - 1);
        $substr = explode('}{', $substr);
        $string = '!LOOP!';
        if (empty($visited["{{$substr[0]}}{{$substr[1]}}"])) {
            $string = $avaliable[$substr[0]][$substr[1]][$n] ?? '!WRONG ID!';
            $visited["{{$substr[0]}}{{$substr[1]}}"] = true;
            $string = self::replace_placeholders($string, $avaliable, $n, $visited);
        }
        return substr_replace($original, $string, $bpos, $epos - $bpos + 1);
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
        $trainingpage = $this->_customdata['trainingpage'];
        $trainingsubmission = $this->_customdata['submission'];
        $submitted = isset($trainingsubmission) && !empty($trainingsubmission);

        $exercises = (int) $trainingpage->exercises;

        $scalearray = array(PEERGRADE_UNSET_PEERGRADE => 'Rating...') + $peergradescaleitems;

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

            if ($criterias) {
                $mform->addElement('html', '<h5>What grade would you give to these <b>criteria</b>?</h5>');
            }

            foreach (range(0, $criterias) as $c) {
                if ($c == $criterias) {
                    break; // So the last element isn't run. $criteria-1 does not work.
                }

                $critid = $trainingpage->criteria['id'][$c];

                /* Grade */
                $gradegroup = array();
                $gradegroup[] =& $mform->createElement('select', 'grades[grade]['.$critid.']['.$exid.']',
                        $trainingpage->criteria['name'][$c], $scalearray);

                /* Feedback */
                if ($submitted) {
                    $grade = $trainingsubmission->grades['grade'][$critid][$exid];
                    $correctgrades = $trainingpage->correctgrades['grade'][$critid][$n];
                    $feedback = $trainingpage->feedback['feedback'][$grade][$critid][$n] ?? '';
                    $feedback = self::replace_placeholders($feedback, $trainingpage->feedback['feedback'], $n,
                            array("{{$grade}}{{$critid}}" => true));
                    if ($grade == $correctgrades) {
                        $html = '<span class="text-success"><b>Correct</b>: '.$feedback.'</span>';
                    } else {
                        $html = '<span class="text-danger"><b>Wrong</b>: '.$feedback.'</span>';
                    }
                    $gradegroup[] =& $mform->createElement('html', $html);
                }
                $mform->addGroup($gradegroup, 'group[grade]['.$critid.']['.$exid.']',
                        $trainingpage->criteria['name'][$c], '', false);
                $mform->addGroupRule('group[grade]['.$critid.']['.$exid.']', get_string('error'), 'required');
            }

            /*-------- OVERALL EXERCISE --------*/
            $mform->addElement('html', '<h4>How would you grade the exercise?</h4>');

            /* Grade */
            $gradegroup = array();
            $gradegroup[] =& $mform->createElement('select', 'grades[grade][-1]['.$exid.']', 'Overall grade', $scalearray);

            /* Feedback */
            if ($submitted) {
                $grade = $trainingsubmission->grades['grade'][-1][$exid];
                $correctgrades = $trainingpage->correctgrades['grade'][-1][$n];
                $feedback = $trainingpage->feedback['feedback'][$grade][-1][$n] ?? '';
                $feedback = self::replace_placeholders($feedback, $trainingpage->feedback['feedback'], $n,
                        array("{{$grade}}{-1}" => true));
                if ($grade == $correctgrades) {
                    $html = '<span class="text-success"><b>Correct</b>: '.$feedback.'</span>';
                } else {
                    $html = '<span class="text-danger"><b>Wrong</b>: '.$feedback.'</span>';
                }
                $gradegroup[] =& $mform->createElement('html', $html);
            }
            $mform->addGroup($gradegroup, 'group[grade][-1]['.$exid.']', 'Overall grade', '', false);
            $mform->addGroupRule('group[grade][-1]['.$exid.']', get_string('error'), 'required');
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

        $mform->addElement('hidden', 'openid');
        $mform->setType('openid', PARAM_INT);

        $mform->addElement('hidden', 'previous');
        $mform->setType('previous', PARAM_INT);

        // Elements in a row need a group.
        $buttonarray = array();
        if ($submitted) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Try again');
        } else {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Test answers');
        }
        $buttonarray[] = &$mform->createElement('cancel', null, 'Go back');

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
        $grades = $data['grades']['grade'] ?? array();
        foreach ($grades as $critid => $g) {
            foreach ($g as $exid => $v) {
                if ($v == PEERGRADE_UNSET_PEERGRADE) {
                    $errors['group[grade][' . $critid . '][' . $exid . ']'] = 'You gotta give a grade to this!';
                    $errors['grades[grade][' . $critid . '][' . $exid . ']'] = 'You gotta give a grade to this!';
                }
            }
        }
        return $errors;
    }
}
