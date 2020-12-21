<?php

require_once(dirname(__FILE__) . '/../../config.php');
if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/peergrading/export/export_peergrades.php', array('courseid' => $courseid));

global $DB, $CFG;

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_peergrades.csv"');
//do not cache the file
header('Pragma: no-cache');
header('Expires: 0');

$file = fopen('php://output', 'w');
fputcsv($file,
        array('PostID', 'Subject', 'ParaentID', 'PostAuthorID', 'PostAuthor', 'Rate', 'PeergraderID', 'Peergrader', 'Peergrade',
                'Feedback'));

// Open the connection - dbhost, dbuser, dbpass, dbname
$link = mysqli_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);

//query the database
$query =
        "SELECT p.itemid, post.subject, post.parent, post.userid, u1.firstname as author_firstname, u1.lastname as author_lastname, r.ratingpeer, p.userid as peergrader, u.firstname, u.lastname, p.peergrade, p.feedback
   FROM mdl_peerforum_peergrade p
   INNER JOIN mdl_peerforum_ratingpeer r
      ON p.itemid=r.itemid
    INNER JOIN mdl_user u
      ON p.userid = u.id
    INNER JOIN mdl_peerforum_posts post
      ON post.id = p.itemid
    INNER JOIN mdl_user u1
      ON post.userid = u1.id
      ;";

$rows = mysqli_query($link, $query);

// loop over the rows, outputting them
while ($row = mysqli_fetch_assoc($rows)) {

    $result = array();
    $row = array_values($row);

    array_push($result, $row[0], $row[1], $row[2], $row[3]);

    $author_name = $row[4] . ' ' . $row[5];
    array_push($result, $author_name, $row[6], $row[7]);

    $grader_name = $row[8] . ' ' . $row[9];
    array_push($result, $grader_name, $row[10], $row[11]);

    fputcsv($file, $result, ",");
}
// free result set
mysqli_free_result($rows);

// close the connection
mysqli_close($link);

fclose($file);
