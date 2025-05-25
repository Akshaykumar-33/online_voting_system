
<?php
session_start();

require("connect.php"); // Assuming this file connects to your SQLite database
require("../admin/check_election.php");

$mobile = $_POST['mobile'];
$password = $_POST['password'];
$role = $_POST['role'];

// Check if database connection is successful
if (!$db) {
    echo '<script>alert("Could not connect to database.");</script>';
    exit;
}

// Prepare query to check failed login attempts
$attempt_statement = $db->prepare("SELECT failed_attempts FROM " . ($role == 1 ? "userdata" : "candidate") . " WHERE mobile = :mobile AND role = :role");
$attempt_statement->bindValue(':mobile', $mobile);
$attempt_statement->bindValue(':role', $role);
$attempt_result = $attempt_statement->execute();

$attempt_row = $attempt_result->fetchArray(SQLITE3_ASSOC);

if ($attempt_row && $attempt_row['failed_attempts'] >= 3) {
    echo '<script>alert("Your account is locked due to multiple failed login attempts. Please contact support."); window.location = "../Routes/login.php";</script>';
    exit; // Stop further execution
}

// Prepare query to fetch user data
$statement = $db->prepare("SELECT * FROM " . ($role == 1 ? "userdata" : "candidate") . " WHERE mobile = :mobile AND role = :role");
$statement->bindValue(':mobile', $mobile);
$statement->bindValue(':role', $role);
$result = $statement->execute();

$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row) {
    // Verify password
    if (password_verify($password, $row['password'])) {
        $verified = $row['verified'];
        if(!$verified) {echo '<script>alert("You are not verified!");
             window.location = "../Routes/reg.html";</script>';
        }

        // Reset failed attempts
        $reset_statement = $db->prepare("UPDATE " . ($role == 1 ? "userdata" : "candidate") . " SET failed_attempts = 0 WHERE mobile = :mobile AND role = :role");
        $reset_statement->bindValue(':mobile', $mobile);
        $reset_statement->bindValue(':role', $role);
        $reset_statement->execute();

        // Set session variables
        $_SESSION['usersdata'] = $row;
        // echo '<pre>Session usersdata: ' . print_r($_SESSION['usersdata'], true) . '</pre>';
        
        $groups = $db->query("SELECT * FROM candidate WHERE role = 2");
        $groupdata = [];
        while ($row = $groups->fetchArray(SQLITE3_ASSOC)) {
            $groupdata[] = $row;
        }
        $_SESSION['groupdata'] = $groupdata;
        


        // Redirect based on role
        if ($role == 1 || $role == 2) {
            echo '<script>window.location.replace("../Routes/dashboard.php");</script>';
            exit; // Stop further execution
        }
    } else {
        // Increment failed attempts
        $increment_statement = $db->prepare("UPDATE " . ($role == 1 ? "userdata" : "candidate") . " SET failed_attempts = failed_attempts + 1 WHERE mobile = :mobile AND role = :role");
        $increment_statement->bindValue(':mobile', $mobile);
        $increment_statement->bindValue(':role', $role);
        $increment_statement->execute();
        
        echo '<script>alert("Wrong password!"); window.location = "../Routes/login.php";</script>';
        exit; // Stop further execution
    }
} else {
    echo '<script>alert("User not found or Wrong details!"); window.location = "../Routes/login.php";</script>';
    exit; // Stop further execution
}
?>
