<?php
session_start();
include ("connect.php");
require("../admin/check_election.php");
require("check_election_status.php");


$votes = $_POST['pvote'];
$t_votes = $votes + 1;
$partyid = $_POST['pid'];
$user_id = $_SESSION['usersdata']['id'];
$role = $_SESSION['usersdata']['role'];

if($role==1){
$update_status = $db->exec("UPDATE userdata SET status=1 WHERE id='$user_id'");

}
else {
    $update_status = $db->exec("UPDATE candidate SET status=1 WHERE id='$user_id'");

}
$update_vote = $db->exec("UPDATE candidate SET votes='$t_votes' WHERE id ='$partyid'");

if ($update_vote !== false && $update_status !== false) {
    $groups = $db->query("SELECT * FROM candidate WHERE role=2 ");
    $groupdata = [];
    while ($row = $groups->fetchArray(SQLITE3_ASSOC)) {
        $groupdata[] = $row;
    }

    $_SESSION['usersdata']['status'] = 1;
    $_SESSION['groupdata'] = $groupdata;

    echo '
        <script>
            alert("Voting done successfully!");
            window.location = "../Routes/dashboard.php";
        </script>
    ';
} else {
    echo '
        <script>
            alert("Error occurred! Vote");
            window.location = "../Routes/dashboard.php";
        </script>
    ';
}
?>