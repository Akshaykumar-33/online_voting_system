<?php
session_start();
require("../admin/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the JSON payload
    $data = json_decode(file_get_contents("php://input"), true);
    $phone = $data['phone'];
    $role = $data['role'];
    $otp = $data['otp'];

    $table = ($role == 1) ? 'userdata' : 'candidate';
        $sql = $db->querySingle("SELECT otp FROM $table WHERE mobile = '$phone' AND otp = '$otp'");

        if ($sql) {
        $verify= $db->exec("UPDATE $table SET verified=1 WHERE mobile='$phone'");
        if($verify){

            echo json_encode(['success' => true, 'message' => 'OTP validated successfully']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
    }
}
?>
