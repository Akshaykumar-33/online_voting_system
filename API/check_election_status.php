<?php
$db = new SQLite3('../db/online_voting_system.db');

date_default_timezone_set('Asia/Kolkata');

$currentDatetime = date('Y-m-d\TH:i');

$queryStart = "SELECT timestart FROM meta";
$resultStart = $db->querySingle($queryStart);

$queryEnd = "SELECT timeend FROM meta";
$resultEnd = $db->querySingle($queryEnd);

$election_is_live = false;

if ($resultStart && $resultEnd) {
    if ($resultStart <= $currentDatetime && $currentDatetime < $resultEnd) {
        $election_is_live = true;
    }
} else {
    echo json_encode(array('error' => 'Error retrieving datetime from database.'));
    exit();
}

echo json_encode(array('election_status' => $election_is_live));
?>
