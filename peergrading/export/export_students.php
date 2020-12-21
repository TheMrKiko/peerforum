<?php

require_once(dirname(__FILE__) . '/../../config.php');
if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/peergrading/export/export_nominations.php', array('courseid' => $courseid));

global $DB, $CFG;

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_stats.csv"');
//do not cache the file
header('Pragma: no-cache');
header('Expires: 0');

$file = fopen('php://output', 'w');
fputcsv($file, array('StudentID', 'FenixID', 'Name', 'Posts Assigned', 'Posts Graded', 'Posts Blocked', 'Posts Expired'));

// Open the connection
$link = mysqli_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);

//query the database
$query =
        "SELECT p.iduser, user.idnumber, user.firstname, user.lastname, p.numpostsassigned, p.postspeergradedone, p.postsblocked, p.postsexpired
   FROM mdl_peerforum_peergrade_users p
   INNER JOIN mdl_user user
     ON user.id=p.iduser
     AND p.courseid = $courseid
   ORDER BY user.firstname;";

$rows = mysqli_query($link, $query);

// loop over the rows, outputting them
while ($row = mysqli_fetch_assoc($rows)) {
    $result = array();
    $row = array_values($row);

    array_push($result, $row[0], $row[1]);

    $name = $row[2] . ' ' . $row[3];
    array_push($result, $name);

    $assigned = $row[4];
    $done = count(array_filter(explode(";", $row[5])));
    $blocked = count(array_filter(explode(";", $row[6])));
    $expired = count(array_filter(explode(";", $row[7])));

    array_push($result, $assigned, $done, $blocked, $expired);
    fputcsv($file, $result, ",");
}
// free result set
mysqli_free_result($rows);

// close the connection
mysqli_close($link);

fclose($file);
