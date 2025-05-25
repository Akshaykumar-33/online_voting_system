<?php
session_start();
require ("connect.php");
require("check_election.php");


if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_end_time = $_POST['end_time'];
    $new_start_time = $_POST['start_time'];
    $db->exec("UPDATE meta SET timeend='$new_end_time'");
    $db->exec("UPDATE meta SET timestart='$new_start_time'");
}

$meta = $db->querySingle("SELECT * FROM meta", true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Election</title>
    <link rel="stylesheet" href="./css/manage_election.css">
</head>
<body>
    <div id="container">
        <h2>Manage Election</h2>
        <form action="manage_election.php" method="post">
            <label for="end_time">Start Time:</label>
            <input type="datetime-local" name="start_time" value="<?php echo $meta['timestart']; ?>" required><br>
            <label for="end_time">End Time:</label>
            <input type="datetime-local" name="end_time" value="<?php echo $meta['timeend']; ?>" required><br>
            <button type="submit">Update</button>
        </form>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
