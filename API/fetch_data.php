<?php
require ("../API/connect.php");

$query = "SELECT * FROM candidate where verified=1";
$result = $db->query($query);

$candidates = array();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $candidates[] = $row;
}

header('Content-Type: application/json');
echo json_encode($candidates);
?>
