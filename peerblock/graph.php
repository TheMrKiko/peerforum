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
$items = $pgmanager->get_items_from_filters(array(), '', 'timemodified ASC');

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

$assigns = array(); // List of assign state by week.
$ngrades = array(); // List of timedelay by number of grade by postweek.
$pweeks = array(); // List of postweeks by postid.
$posts = array(); // List of current number of grade by postid.
foreach ($items as $item) {
    $itemid = $item->itemid;

    $week = intdiv($item->timeassigned - $datemin, WEEKSECS);
    $pweek = $pweeks[$itemid] = $pweeks[$itemid] ?? $week;

    if ($userid && $item->userid != $userid) {
        continue;
    }

    if (!isset($posts[$itemid])) {
        $posts[$itemid] = 0;
        $ngrades[$pweek][0][] = 0;
    }

    $assigns[$pweek] = $assigns[$pweek] ?? array();

    $ngrades[$pweek] = $ngrades[$pweek] ?? array();

    $timedelay = 0;
    $pn = 0;
    if (!empty($item->peergraded)) {
        // When already peer graded.
        $assigns[$pweek]['peergraded'] = ($assigns[$pweek]['peergraded'] ?? 0) + 1;
        $timedelay = ($item->timemodified - $item->timeassigned) / ($item->timeexpired - $item->timeassigned);
        $assigns[$pweek]['timedelay'][] = $timedelay;

        $pn = $posts[$itemid] = $posts[$itemid] + 1;
        $ngrades[$pweek][$pn][] = $timedelay;
    } else if (!empty($item->expired)) {
        // When expired.
        $assigns[$pweek]['expired'] = ($assigns[$pweek]['expired'] ?? 0) + 1;
    } else if (!empty($item->ended)) {
        // When ended but not peer graded.
        $assigns[$pweek]['ended'] = ($assigns[$pweek]['ended'] ?? 0) + 1;
    } else {
        // When waiting for grade.
        $assigns[$pweek]['todo'] = ($assigns[$pweek]['todo'] ?? 0) + 1;
    }
}

$nmaxgrades = max($posts);

$seriepgvals = [];
$serieexvals = [];
$serieenvals = [];
$serietdvals = [];
$seriedelvals = [];
$seriesnpvals = [];
$seriesnptvals = [];
$labelsvals = [];

foreach (range(0, $numweeks - 1) as $n) {
    $seriepgvals[] = $assigns[$n]['peergraded'] ?? 0;
    $serieexvals[] = $assigns[$n]['expired'] ?? 0;
    $serieenvals[] = $assigns[$n]['ended'] ?? 0;
    $serietdvals[] = $assigns[$n]['todo'] ?? 0;
    $seriedelvals[] = !empty($assigns[$n]['timedelay']) ?
            round(array_sum($assigns[$n]['timedelay']) * 100 / count($assigns[$n]['timedelay'])) : 0;

    // Special case for no grades.
    $ngr = $ngrades[$n][0] ?? array();
    $seriesnpvals[0][] = count($ngr);

    foreach (range(1, $nmaxgrades) as $k) {
        $ngr = $ngrades[$n][$k] ?? array();
        $seriesnpvals[$k][] = count($ngr);
        $seriesnptvals[$k][] = !empty($ngr) ? round(array_sum($ngr) * 100 / count($ngr), 2) : 0;
    }

    $labelsvals[] = date('d/m/Y', $datemin + ($n * WEEKSECS));
}

/*------------------------ Assigns distribution graph ------------------------*/
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

$colorset = $CFG->chart_colorset ?? null;
$CFG->chart_colorset = ['#01ff70', 'black', '#339966', '#cc3300', 'grey'];

echo isset($pn) ? $OUTPUT->render($chart) : 'No posts to show.';
$CFG->chart_colorset = $colorset;

/*------------------------ Grade distribution graph ------------------------*/
ksort($seriesnpvals);
ksort($seriesnptvals);

$chart = new core\chart_bar();
$chart->set_title('Peer grade sequential distribution');
foreach ($seriesnpvals as $n => $serienpvals) {
    $nthgradelabel = $n . 'nth grade';
    $serienp = new core\chart_series($nthgradelabel, $serienpvals);
    $chart->add_series($serienp);
}

foreach ($seriesnptvals as $n => $serienptvals) {
    $serienpt = new core\chart_series($n . 'nth time', $serienptvals);
    $serienpt->set_type(\core\chart_series::TYPE_LINE);
    $serienpt->set_yaxis(1);
    $chart->add_series($serienpt);
}

$chart->set_labels($labelsvals);
$xaxis = $chart->get_xaxis(0, true);
$xaxis->set_label('Week');
$yaxis0 = $chart->get_yaxis(0, true);
$yaxis0->set_label('Number of posts that at least reach nth grade');
$yaxis1 = $chart->get_yaxis(1, true);
$yaxis1->set_label('Percentage of time used from the avaliable (%)');
$yaxis1->set_stepsize(50);
$yaxis1->set_min(0);
$yaxis1->set_max(100);
$yaxis1->set_position(\core\chart_axis::POS_RIGHT);

echo isset($pn) ? $OUTPUT->render($chart) : 'No posts to show.';

/*------------------------ Grade distribution graph 2 ------------------------*/
// Prep.
$seriesnprvals = [];
foreach (range(0, $numweeks - 1) as $n) {
    $ngr = $ngrades[$n][$nmaxgrades] ?? array();
    $seriesnprvals[$nmaxgrades] = $seriesnprvals[$nmaxgrades] ?? array();
    $seriesnprvals[$nmaxgrades][$n] = count($ngr);

    foreach (range($nmaxgrades - 1, 0) as $k) {
        $nextk = $k + 1;
        $ngr = $ngrades[$n][$k] ?? array();
        $seriesnprvals[$k][$n] = count($ngr) - $seriesnpvals[$nextk][$n];
    }
}
ksort($seriesnprvals);
// End prep.

$chart = new core\chart_bar();
$chart->set_title('Peer grade sequential distribution 2');
$chart->set_stacked(true);
foreach ($seriesnprvals as $n => $serienpvals) {
    $nthgradelabel = $n . ' grades';
    $serienp = new core\chart_series($nthgradelabel, $serienpvals);
    $chart->add_series($serienp);
}

foreach ($seriesnptvals as $n => $serienptvals) {
    $serienpt = new core\chart_series($n . 'nth time', $serienptvals);
    $serienpt->set_type(\core\chart_series::TYPE_LINE);
    $serienpt->set_yaxis(1);
    $chart->add_series($serienpt);
}

$chart->set_labels($labelsvals);
$xaxis = $chart->get_xaxis(0, true);
$xaxis->set_label('Week');
$yaxis0 = $chart->get_yaxis(0, true);
$yaxis0->set_label('Number of posts that end with nth grades');
$yaxis1 = $chart->get_yaxis(1, true);
$yaxis1->set_label('Percentage of time used from the avaliable (%)');
$yaxis1->set_stepsize(50);
$yaxis1->set_min(0);
$yaxis1->set_max(100);
$yaxis1->set_position(\core\chart_axis::POS_RIGHT);

echo isset($pn) ? $OUTPUT->render($chart) : 'No posts to show.';

/*------------------------ Grade distribution graph 3 ------------------------*/
// Prep.
$seriesnppvals = [];
foreach (range(0, $numweeks - 1) as $n) {
    $ngr = $ngrades[$n][$nmaxgrades] ?? array();
    $perctotal = $seriesnpvals[0][$n] ?? 0;
    $seriesnppvals[$nmaxgrades] = $seriesnppvals[$nmaxgrades] ?? array();
    $seriesnppvals[$nmaxgrades][$n] = $perctotal ? count($ngr) * 100 / $seriesnpvals[0][$n] : 0;

    foreach (range($nmaxgrades - 1, 0) as $k) {
        $nextk = $k + 1;
        $ngr = $ngrades[$n][$k] ?? array();
        $seriesnppvals[$k][$n] = $perctotal ? (count($ngr) - $seriesnpvals[$nextk][$n]) * 100 / $seriesnpvals[0][$n] : 0;
    }
}
ksort($seriesnppvals);
// End prep.

$chart = new core\chart_bar();
$chart->set_title('Peer grade sequential distribution 3');
$chart->set_stacked(true);

foreach ($seriesnptvals as $n => $serienptvals) {
    $serienpt = new core\chart_series($n . 'nth time', $serienptvals);
    $serienpt->set_type(\core\chart_series::TYPE_LINE);
    $serienpt->set_yaxis(1);
    $chart->add_series($serienpt);
}

foreach ($seriesnppvals as $n => $serienpvals) {
    $nthgradelabel = $n . ' grades';
    $serienp = new core\chart_series($nthgradelabel, $serienpvals);
    $chart->add_series($serienp);
}

$chart->set_labels($labelsvals);
$xaxis = $chart->get_xaxis(0, true);
$xaxis->set_label('Week');
$yaxis0 = $chart->get_yaxis(0, true);
$yaxis0->set_label('Percentage of posts that end with nth grades (%)');
$yaxis1 = $chart->get_yaxis(1, true);
$yaxis1->set_label('Percentage of time used from the avaliable (%)');
$yaxis1->set_stepsize(50);
$yaxis1->set_min(0);
$yaxis1->set_max(100);
$yaxis1->set_position(\core\chart_axis::POS_RIGHT);

echo isset($pn) ? $OUTPUT->render($chart) : 'No posts to show.';


echo $OUTPUT->box_end();
echo $OUTPUT->footer();
