<?php
require ("connect.php");

$voters = $db->query("SELECT * FROM userdata");
$candidates = $db->query("SELECT * FROM candidate");

$data = [
    'voters' => [],
    'candidates' => [],
];

while ($row = $voters->fetchArray(SQLITE3_ASSOC)) {
    $data['voters'][] = $row;
}

while ($row = $candidates->fetchArray(SQLITE3_ASSOC)) {
    $data['candidates'][] = $row;
}

echo json_encode($data);
?>
