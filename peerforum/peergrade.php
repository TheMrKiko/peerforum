<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/peerforum/lib.php');

$PAGE->set_url('/mod/peerforum/peergrade.php', array());

require_login(null, false, null, false, true);

$itemid = required_param('itemid', PARAM_INT);
$peerid = required_param('peerid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$postauthor = required_param('postauthor', PARAM_INT);
