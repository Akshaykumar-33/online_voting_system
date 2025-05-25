<?php
require("connect.php");
require("../admin/check_election.php");

$name = $_POST['name'];
$number = $_POST['number'];
$password = $_POST['password'];
$confirmpassword = $_POST['confirmpassword'];
$gender = $_POST['gender'];
$address = $_POST['address'];
$image = $_FILES['image']['name'];
$partyImage = $_FILES['symbol']['name'];
$tmp_name_img = $_FILES['image']['tmp_name'];
$tmp_name_sym = $_FILES['symbol']['tmp_name'];
$role = 2;

if ($password == $confirmpassword) {
    if ($_FILES['pimg']['error'] != UPLOAD_ERR_OK || $_FILES['symbol']['error'] != UPLOAD_ERR_OK) {
        echo '
            <script>
                alert("Error uploading files.");
                window.location = "../Routes/registeration.html";
            </script>
        ';
        exit();
    }

    move_uploaded_file($tmp_name_img, "../uploads/CandidateUploads/$image");
    move_uploaded_file($tmp_name_sym, "../uploads/CandidateUploads/$partyImage");

    $mobiles = $db->querySingle("SELECT mobile FROM candidate WHERE mobile='$number'");
    if ($mobiles) {
        echo '
        <script>
            alert("Mobile number already exists");
            window.location = "../Routes/registeration.html";
        </script>';
        exit();
    }

    $hash_pass = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO candidate (name, mobile, gender, password, address, cand_img, party_img, role, status, votes) VALUES (:name, :number, :gender, :password, :address, :image, :partyImage, :role, 0, 0)");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':number', $number, SQLITE3_TEXT);
    $stmt->bindValue(':gender', $gender, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hash_pass, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->bindValue(':image', $image, SQLITE3_TEXT);
    $stmt->bindValue(':partyImage', $partyImage, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_INTEGER);

    $result = $stmt->execute();
    if ($result) {
        echo '
            <script>

             window.location = "../Routes/otp_req.php?role='.$role.'&number='.$number.'";

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
?>
