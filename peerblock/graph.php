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
 * Displays a graph with the week summary of peergrades.
 *
 * @package   block_peerblock
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./lib.php');

$pgmanager = mod_peerforum\local\container::get_manager_factory()->get_peergrade_manager();

$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$url = new moodle_url('/blocks/peerblock/graph.php', array(
        'courseid' => $courseid,
        'userid' => $userid,
));

set_peergradepanel_page($courseid, $userid, $url, 'viewgradesgraph', true, false);

echo $OUTPUT->box_start('posts-list');

// Gets posts from filters.
$items = $pgmanager->get_items_from_filters();

$datemin = 0;
$datemax = 0;
foreach ($items as $item) {
    if ($item->timeassigned < $datemin || empty($datemin)) {
        $datemin = $item->timeassigned;
    }
    if ($item->timeassigned > $datemax || empty($datemax)) {
        $datemax = $item->timeassigned;
    }
}
$datediff = $datemax - $datemin;
$numweeks = intdiv($datediff, WEEKSECS) + 1;

$values = array();
foreach ($items as $item) {
    if ($userid && $item->userid != $userid) {
        continue;
    }

    $week = intdiv($item->timeassigned - $datemin, WEEKSECS);
    $values[$week] = $values[$week] ?? array();

    if (!empty($item->peergraded)) {
        // When already peer graded.
        $values[$week]['peergraded'] = ($values[$week]['peergraded'] ?? 0) + 1;
        $values[$week]['timedelay'][] = ($item->timemodified - $item->timeassigned) / (2 * DAYSECS); // TODO Change!!
    } else if (!empty($item->expired)) {
        // When expired.
        $values[$week]['expired'] = ($values[$week]['expired'] ?? 0) + 1;
    } else if (!empty($item->ended)) {
        // When ended but not peer graded.
        $values[$week]['ended'] = ($values[$week]['ended'] ?? 0) + 1;
    } else {
        // When waiting for grade.
        $values[$week]['todo'] = ($values[$week]['todo'] ?? 0) + 1;
    }
}

$seriepgvals = [];
$serieexvals = [];
$serieenvals = [];
$serietdvals = [];
$seriedelvals = [];
$labelsvals = [];

foreach (range(0, $numweeks - 1) as $n) {
    $seriepgvals[] = $values[$n]['peergraded'] ?? 0;
    $serieexvals[] = $values[$n]['expired'] ?? 0;
    $serieenvals[] = $values[$n]['ended'] ?? 0;
    $serietdvals[] = $values[$n]['todo'] ?? 0;
    $seriedelvals[] = !empty($values[$n]['timedelay']) ?
            round(array_sum($values[$n]['timedelay']) * 100 / count($values[$n]['timedelay'])) : 0;
    $labelsvals[] = date('d/m/Y', $datemin + ($n * WEEKSECS));
}

$serietd = new core\chart_series('TODO', $serietdvals);
$seriepg = new core\chart_series('Peer graded', $seriepgvals);
$serieex = new core\chart_series('Expired', $serieexvals);
$serieen = new core\chart_series('Ended', $serieenvals);
$seriedel = new core\chart_series('Grading time performance', $seriedelvals);
$seriedel->set_type(\core\chart_series::TYPE_LINE); // Set the series type to line chart.
$seriedel->set_yaxis(1);

$chart = new core\chart_bar();
$chart->set_title('Peer grade state distribution');
$chart->add_series($serietd);
$chart->add_series($seriepg);
$chart->add_series($serieex);
$chart->add_series($serieen);
$chart->add_series($seriedel);
$chart->set_labels($labelsvals);
$xaxis = $chart->get_xaxis(0, true);
$xaxis->set_label('Week');
$yaxis0 = $chart->get_yaxis(0, true);
$yaxis0->set_label('Number of assignes');
$yaxis1 = $chart->get_yaxis(1, true);
$yaxis1->set_label('Average percentage of time used from the avaliable (%)');
$yaxis1->set_stepsize(50);
$yaxis1->set_min(0);
$yaxis1->set_max(100);
$yaxis1->set_position(\core\chart_axis::POS_RIGHT);

$CFG->chart_colorset = ['#01ff70', 'black', '#339966', '#cc3300', 'grey'];

echo isset($week) ? $OUTPUT->render($chart) : 'No posts to show.';
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
