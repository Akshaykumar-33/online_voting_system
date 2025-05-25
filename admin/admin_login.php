<?php
session_start();
require ("connect.php");
require("check_election.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $admin = $db->querySingle("SELECT * FROM admins WHERE username='$username'", true);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
    } else {
        echo '
            <script>
                alert("Invalid username or password!");
                window.location = "admin_login.php";
            </script>
        ';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="./css/admin_login.css">
</head>
<body>
    <div id="bodysection">
        <h2>Admin Login</h2>
        <form action="admin_login.php" method="post">
            <fieldset id="mform">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>

            </fieldset>
            
        </form>
    </div>
</body>
</html>
