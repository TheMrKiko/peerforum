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
 * PeerGrade external functions utility class.
 *
 * @package    core_peergrade
 * @copyright  2017 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_peergrade\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/peergrade/lib.php');
require_once($CFG->libdir . '/externallib.php');

use external_multiple_structure;
use external_single_structure;
use external_value;
use peergrade_manager;
use stdClass;

/**
 * PeerGrade external functions utility class.
 *
 * @package   core_peergrade
 * @copyright 2017 Juan Leyva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 3.4
 */
class util {

    /**
     * Returns the peergrades definition for external functions.
     */
    public static function external_peergrades_structure() {

        return new external_single_structure (
                [
                        'contextid' => new external_value(PARAM_INT, 'Context id.'),
                        'component' => new external_value(PARAM_COMPONENT, 'Context name.'),
                        'peergradearea' => new external_value(PARAM_AREA, 'PeerGrade area name.'),
                        'canviewall' => new external_value(PARAM_BOOL, 'Whether the user can view all the individual peergrades.',
                                VALUE_OPTIONAL),
                        'canviewany' => new external_value(PARAM_BOOL,
                                'Whether the user can view aggregate of peergrades of others.',
                                VALUE_OPTIONAL),
                        'scales' => new external_multiple_structure(
                                new external_single_structure (
                                        [
                                                'id' => new external_value(PARAM_INT, 'Scale id.'),
                                                'courseid' => new external_value(PARAM_INT, 'Course id.', VALUE_OPTIONAL),
                                                'name' => new external_value(PARAM_TEXT, 'Scale name (when a real scale is used).',
                                                        VALUE_OPTIONAL),
                                                'max' => new external_value(PARAM_INT, 'Max value for the scale.'),
                                                'isnumeric' => new external_value(PARAM_BOOL, 'Whether is a numeric scale.'),
                                                'items' => new external_multiple_structure(
                                                        new external_single_structure (
                                                                [
                                                                        'value' => new external_value(PARAM_INT,
                                                                                'Scale value/option id.'),
                                                                        'name' => new external_value(PARAM_NOTAGS, 'Scale name.'),
                                                                ]
                                                        ), 'Scale items. Only returned for not numerical scales.', VALUE_OPTIONAL
                                                )
                                        ], 'Scale information'
                                ), 'Different scales used information', VALUE_OPTIONAL
                        ),
                        'peergrades' => new external_multiple_structure(
                                new external_single_structure (
                                        [
                                                'itemid' => new external_value(PARAM_INT, 'Item id.'),
                                                'scaleid' => new external_value(PARAM_INT, 'Scale id.', VALUE_OPTIONAL),
                                                'userid' => new external_value(PARAM_INT, 'User who peergraded id.',
                                                        VALUE_OPTIONAL),
                                                'aggregate' => new external_value(PARAM_FLOAT, 'Aggregated peergrades grade.',
                                                        VALUE_OPTIONAL),
                                                'aggregatestr' => new external_value(PARAM_NOTAGS,
                                                        'Aggregated peergrades as string.',
                                                        VALUE_OPTIONAL),
                                                'aggregatelabel' => new external_value(PARAM_NOTAGS, 'The aggregation label.',
                                                        VALUE_OPTIONAL),
                                                'count' => new external_value(PARAM_INT,
                                                        'PeerGrades count (used when aggregating).',
                                                        VALUE_OPTIONAL),
                                                'peergrade' => new external_value(PARAM_INT, 'The peergrade the user gave.',
                                                        VALUE_OPTIONAL),
                                                'canpeergrade' => new external_value(PARAM_BOOL,
                                                        'Whether the user can peergrade the item.',
                                                        VALUE_OPTIONAL),
                                                'canviewaggregate' => new external_value(PARAM_BOOL,
                                                        'Whether the user can view the aggregated grade.',
                                                        VALUE_OPTIONAL),
                                        ]
                                ), 'The peergrades', VALUE_OPTIONAL
                        ),
                ], 'PeerGrade information', VALUE_OPTIONAL
        );
    }

    /**
     * Returns peergrade information inside a data structure like the one defined by external_peergrades_structure.
     *
     * @param stdClass $mod course module object
     * @param stdClass $context context object
     * @param str $component component name
     * @param str $peergradearea peergrade area
     * @param array $items items to add peergrades
     * @return array peergrades ready to be returned by external functions.
     */
    public static function get_peergrade_info($mod, $context, $component, $peergradearea, $items) {
        global $USER;

        $peergradeinfo = [
                'contextid' => $context->id,
                'component' => $component,
                'peergradearea' => $peergradearea,
                'canviewall' => null,
                'canviewany' => null,
                'scales' => [],
                'peergrades' => [],
        ];
        if ($mod->assessed != PEERGRADE_AGGREGATE_NONE) {
            $peergradeoptions = new stdClass;
            $peergradeoptions->context = $context;
            $peergradeoptions->component = $component;
            $peergradeoptions->peergradearea = $peergradearea;
            $peergradeoptions->items = $items;
            $peergradeoptions->aggregate = $mod->assessed;
            $peergradeoptions->scaleid = $mod->scale;
            $peergradeoptions->userid = $USER->id;
            $peergradeoptions->assesstimestart = $mod->assesstimestart;
            $peergradeoptions->assesstimefinish = $mod->assesstimefinish;

            $rm = new peergrade_manager();
            $allitems = $rm->get_peergrades($peergradeoptions);

            foreach ($allitems as $item) {
                if (empty($item->peergrade)) {
                    continue;
                }
                $peergrade = [
                        'itemid' => $item->peergrade->itemid,
                        'scaleid' => $item->peergrade->scaleid,
                        'userid' => $item->peergrade->userid,
                        'peergrade' => $item->peergrade->peergrade,
                        'canpeergrade' => $item->peergrade->user_can_peergrade(),
                        'canviewaggregate' => $item->peergrade->user_can_view_aggregate(),
                ];
                // Fill the capabilities fields the first time (the rest are the same values because they are not item dependent).
                if ($peergradeinfo['canviewall'] === null) {
                    $peergradeinfo['canviewall'] = $item->peergrade->settings->permissions->viewall &&
                            $item->peergrade->settings->pluginpermissions->viewall;
                    $peergradeinfo['canviewany'] = $item->peergrade->settings->permissions->viewany &&
                            $item->peergrade->settings->pluginpermissions->viewany;
                }

                // Return only the information the user can see.
                if ($peergrade['canviewaggregate']) {
                    $peergrade['aggregate'] = $item->peergrade->aggregate;
                    $peergrade['aggregatestr'] = $item->peergrade->get_aggregate_string();
                    $peergrade['aggregatelabel'] = $rm->get_aggregate_label($item->peergrade->settings->aggregationmethod);
                    $peergrade['count'] = $item->peergrade->count;
                }
                // If the user can peergrade, return the scale information only one time.
                if ($peergrade['canpeergrade'] &&
                        !empty($item->peergrade->settings->scale->id) &&
                        !isset($peergradeinfo['scales'][$item->peergrade->settings->scale->id])) {
                    $scale = $item->peergrade->settings->scale;
                    // Return only non numeric scales (to avoid return lots of data just including items from 0 to $scale->max).
                    if (!$scale->isnumeric) {
                        $scaleitems = [];
                        foreach ($scale->scaleitems as $value => $name) {
                            $scaleitems[] = [
                                    'name' => $name,
                                    'value' => $value,
                            ];
                        }
                        $scale->items = $scaleitems;
                    }
                    $peergradeinfo['scales'][$item->peergrade->settings->scale->id] = (array) $scale;
                }
                $peergradeinfo['peergrades'][] = $peergrade;
            }
        }
        return $peergradeinfo;
    }
}
