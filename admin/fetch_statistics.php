
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require("connect.php");


try {
    $total_voters = $db->querySingle("SELECT COUNT(*) FROM userdata");
    if ($total_voters === false) {
        throw new Exception("Failed to fetch total voters: " . $db->lastErrorMsg());
    }

    $voted_voters = $db->querySingle("SELECT COUNT(*) FROM userdata WHERE status=1");
    if ($voted_voters === false) {
        throw new Exception("Failed to fetch voted voters: " . $db->lastErrorMsg());
    }

    $male_voters = $db->querySingle("SELECT COUNT(*) FROM userdata WHERE gender='Male'");
    if ($male_voters === false) {
        throw new Exception("Failed to fetch male voters: " . $db->lastErrorMsg());
    }

    $male_voters_cast = $db->querySingle("SELECT COUNT(*) FROM userdata WHERE gender='Male' and status=1");
    if ($male_voters_cast === false) {
        throw new Exception("Failed to fetch male voters who cast vote: " . $db->lastErrorMsg());
    }

    $female_voters = $db->querySingle("SELECT COUNT(*) FROM userdata WHERE gender='Female'");
    if ($female_voters === false) {
        throw new Exception("Failed to fetch female voters: " . $db->lastErrorMsg());
    }

    $female_voters_cast = $db->querySingle("SELECT COUNT(*) FROM userdata WHERE gender='Female' and status=1");
    if ($female_voters_cast === false) {
        throw new Exception("Failed to fetch female voters who cast vote: " . $db->lastErrorMsg());
    }
    header('Content-Type: application/json');
    echo json_encode([
        "total_voters" => $total_voters,
        "voted_voters" => $voted_voters,
        "male_voters" => $male_voters,
        "male_voters_cast" => $male_voters_cast,
        "female_voters" => $female_voters,
        "female_voters_cast" => $female_voters_cast
    ]);

} catch (Exception $e) {
    // Handle the exception, e.g., log the error message and send an error response
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

