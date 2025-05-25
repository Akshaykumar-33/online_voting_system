<?php
session_start();
require ("connect.php");
require("check_election.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$groups = $db->query("SELECT * FROM candidate WHERE role=2");

$winner = null;
$maxVotes = -1;

$candidates = [];
while ($row = $groups->fetchArray(SQLITE3_ASSOC)) {
    $candidates[] = $row;
    if ($row['votes'] > $maxVotes) {
        $maxVotes = $row['votes'];
        $winner = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Results</title>
    <link rel="stylesheet" href="./css/view_results.css">
</head>
<body>
    <div id="container">
        <h2>Election Results</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Candidate Name</th>
                <th>Votes</th>
            </tr>
            <?php foreach ($candidates as $candidate) { ?>
                <tr>
                    <td><?php echo $candidate['id']; ?></td>
                    <td><?php echo $candidate['name']; ?></td>
                    <td><?php echo $candidate['votes']; ?></td>
                </tr>
            <?php } ?>
        </table>
        
        <?php if ($winner) { ?>
            <h2>Winner</h2>
            <p>ID: <?php echo $winner['id']; ?></p>
            <p>Name: <?php echo $winner['name']; ?></p>
            <p>Votes: <?php echo $winner['votes']; ?></p>
        <?php } else { ?>
            <p>Winner yet to be determined.</p>
        <?php } ?>
        
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
