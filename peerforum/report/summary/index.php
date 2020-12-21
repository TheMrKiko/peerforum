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
 * This script displays the peerforum summary report for the given parameters, within a user's capabilities.
 *
 * @package   peerforumreport_summary
 * @copyright 2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");

if (isguestuser()) {
    print_error('noguest');
}

$courseid = required_param('courseid', PARAM_INT);
$peerforumid = optional_param('peerforumid', 0, PARAM_INT);
$perpage = optional_param('perpage', \peerforumreport_summary\summary_table::DEFAULT_PER_PAGE, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$filters = [];
$pageurlparams = [
        'courseid' => $courseid,
        'perpage' => $perpage,
];

// Establish filter values.
$filters['groups'] = optional_param_array('filtergroups', [], PARAM_INT);
$filters['datefrom'] = optional_param_array('datefrom', ['enabled' => 0], PARAM_INT);
$filters['dateto'] = optional_param_array('dateto', ['enabled' => 0], PARAM_INT);

$modinfo = get_fast_modinfo($courseid);
$course = $modinfo->get_course();
$coursepeerforums = $modinfo->instances['peerforum'];
$cms = [];

// Determine which peerforums the user has access to in the course.
$accessallpeerforums = false;
$allpeerforumidsincourse = array_keys($coursepeerforums);
$peerforumsvisibletouser = [];
$peerforumselectoptions = [0 => get_string('peerforumselectcourseoption', 'peerforumreport_summary')];

foreach ($coursepeerforums as $coursepeerforumid => $coursepeerforum) {
    if ($coursepeerforum->uservisible) {
        $peerforumsvisibletouser[$coursepeerforumid] = $coursepeerforum;
        $peerforumselectoptions[$coursepeerforumid] = $coursepeerforum->name;
    }
}

if ($peerforumid) {
    if (!isset($peerforumsvisibletouser[$peerforumid])) {
        throw new \moodle_exception('A valid peerforum ID is required to generate a summary report.');
    }

    $filters['peerforums'] = [$peerforumid];
    $title = $peerforumsvisibletouser[$peerforumid]->name;
    $peerforumcm = $peerforumsvisibletouser[$peerforumid];
    $cms[] = $peerforumcm;

    require_login($courseid, false, $peerforumcm);
    $context = $peerforumcm->context;
    $canexport = !$download && has_capability('mod/peerforum:exportpeerforum', $context);
    $redirecturl = new moodle_url('/mod/peerforum/view.php', ['id' => $peerforumid]);
    $numpeerforums = 1;
    $pageurlparams['peerforumid'] = $peerforumid;
    $iscoursereport = false;
} else {
    // Course level report.
    require_login($courseid, false);

    $filters['peerforums'] = array_keys($peerforumsvisibletouser);

    // Fetch the peerforum CMs for the course.
    foreach ($peerforumsvisibletouser as $visiblepeerforum) {
        $cms[] = $visiblepeerforum;
    }

    $context = \context_course::instance($courseid);
    $title = $course->fullname;
    // Export currently only supports single peerforum exports.
    $canexport = false;
    $redirecturl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $numpeerforums = count($peerforumsvisibletouser);
    $iscoursereport = true;

    // Specify whether user has access to all peerforums in the course.
    $accessallpeerforums = empty(array_diff($allpeerforumidsincourse, $filters['peerforums']));
}

$pageurl = new moodle_url('/mod/peerforum/report/summary/index.php', $pageurlparams);

$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('nodetitle', 'peerforumreport_summary'));

$allowbulkoperations = !$download && !empty($CFG->messaging) && has_capability('moodle/course:bulkmessaging', $context);
$canseeprivatereplies = false;
$hasviewall = false;
$privatereplycapcount = 0;
$viewallcount = 0;
$canview = false;

foreach ($cms as $cm) {
    $peerforumcontext = $cm->context;

    // This capability is required in at least one of the given contexts to view any version of the report.
    if (has_capability('peerforumreport/summary:view', $peerforumcontext)) {
        $canview = true;
    }

    if (has_capability('mod/peerforum:readprivatereplies', $peerforumcontext)) {
        $privatereplycapcount++;
    }

    if (has_capability('peerforumreport/summary:viewall', $peerforumcontext)) {
        $viewallcount++;
    }
}

if (!$canview) {
    redirect($redirecturl);
}

// Only use private replies if user has that cap in all peerforums in the report.
if ($numpeerforums === $privatereplycapcount) {
    $canseeprivatereplies = true;
}

// Will only show all users if user has the cap for all peerforums in the report.
if ($numpeerforums === $viewallcount) {
    $hasviewall = true;
}

// Prepare and display the report.
$table = new \peerforumreport_summary\summary_table($courseid, $filters, $allowbulkoperations,
        $canseeprivatereplies, $perpage, $canexport, $iscoursereport, $accessallpeerforums);
$table->baseurl = $pageurl;

$eventparams = [
        'context' => $context,
        'other' => [
                'peerforumid' => $peerforumid,
                'hasviewall' => $hasviewall,
        ],
];

if ($download) {
    \peerforumreport_summary\event\report_downloaded::create($eventparams)->trigger();
    $table->download($download);
} else {
    \peerforumreport_summary\event\report_viewed::create($eventparams)->trigger();

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('summarytitle', 'peerforumreport_summary', $title), 2, 'pb-5');

    if (!empty($filters['groups'])) {
        \core\notification::info(get_string('viewsdisclaimer', 'peerforumreport_summary'));
    }

    // Allow switching to course report (or other peerforum user has access to).
    $reporturl = new moodle_url('/mod/peerforum/report/summary/index.php', ['courseid' => $courseid]);
    $peerforumselect = new single_select($reporturl, 'peerforumid', $peerforumselectoptions, $peerforumid, '');
    $peerforumselect->set_label(get_string('peerforumselectlabel', 'peerforumreport_summary'));
    echo $OUTPUT->render($peerforumselect);

    // Render the report filters form.
    $renderer = $PAGE->get_renderer('peerforumreport_summary');

    unset($filters['peerforums']);
    echo $renderer->render_filters_form($course, $cms, $pageurl, $filters);
    $table->show_download_buttons_at(array(TABLE_P_BOTTOM));
    echo $renderer->render_summary_table($table);
    echo $OUTPUT->footer();
}
