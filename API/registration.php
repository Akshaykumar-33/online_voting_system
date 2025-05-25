<?php

require ("connect.php");

require("../admin/check_election.php");

$name = $_POST['name'];
$number = $_POST['number'];
$password = $_POST['password'];
$gender = $_POST['gender'];
$confirmpassword = $_POST['confirmpassword'];
$address = $_POST['address'];
$image = $_FILES['photo']['name'];
$tmp_name = $_FILES['photo']['tmp_name'];
$role = $_POST['role'];

if ($password == $confirmpassword) {
    move_uploaded_file($tmp_name, "../uploads/UserUploads/$image");
    $mobiles = $db->querySingle("SELECT mobile FROM userdata WHERE mobile='$number'");
    if ($mobiles) {
        echo '
        <script> 
            alert("Mobile number already exists"); 
            window.location = "../Routes/registeration.html"; 
        </script>';
        exit();
    }

    



    $hash_pass = password_hash($password, PASSWORD_DEFAULT);
    $insert = $db->exec("INSERT INTO userdata (name,mobile,password,gender,address,photo,role,status,votes) VALUES ('$name','$number','$hash_pass','$gender','$address','$image','$role',0,0)");
    if ($insert) {
        echo '
            <script>
            // window.location = "../Routes/otp_req.php";
             window.location = "../Routes/otp_req.php?role='.$role.'&number='.$number.'";
                // alert("Registration Successful");
                // window.location = ".././Routes/login.php";
            </script>                
        ';
    } else {
        $error_message = $db->lastErrorMsg();
        echo "
            <script>
                alert('Error: $error_message');
                window.location = '../Routes/registeration.html';
            </script>
        ";
    }
} else {
    echo '
        <script>
            alert("Password and confirm password do not match");
            window.location = "../Routes/registeration.html";
        </script>
    ';
}