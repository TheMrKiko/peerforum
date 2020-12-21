<?php

require_once(dirname(__FILE__) . '/../../config.php');
if (is_file($CFG->dirroot . '/mod/peerforum/lib.php')) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');
} else {
    return;
}

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/peergrading/export/export_nominations_2.php', array('courseid' => $courseid));

global $DB, $CFG;

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="peer_nominations2.csv"');
//do not cache the file
header('Pragma: no-cache');
header('Expires: 0');

$file = fopen('php://output', 'w');
fputcsv($file, array('StudentID', 'Student', 'Least Favorite Peer 1', 'Least Favorite Peer 2', 'Least Favorite Peer 3',
        'Least Favorite Peer 4'));

// Open the connection
$link = mysqli_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);

//query the database
$query = "SELECT r.iduser, u.firstname, u.lastname, r.peersunfav
            FROM mdl_peerforum_relationships r
            INNER JOIN mdl_user u
              ON r.iduser = u.id
              AND r.courseid = $courseid;";

$rows = mysqli_query($link, $query);

// loop over the rows, outputting them
while ($row = mysqli_fetch_assoc($rows)) {

    $row = array_values($row);

    $result = array();

    $name = $row[1] . ' ' . $row[2];
    array_push($result, $row[0], $name);

    $favs = explode(";", $row[3]);

    $all_students = get_students_enroled($courseid);
    $all_students = array_values($all_students);

    foreach ($favs as $key => $value) {
        $uid = $all_students[$favs[$key]]->userid;

        $std_firstname = $DB->get_record('user', array('id' => $uid))->firstname;
        $std_lastname = $DB->get_record('user', array('id' => $uid))->lastname;
        $std_name = $std_firstname . ' ' . $std_lastname;

        array_push($result, $std_name);
    }

    fputcsv($file, $result, ",");
}
// free result set
mysqli_free_result($rows);

// close the connection
mysqli_close($link);

fclose($file);
