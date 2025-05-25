<?php
session_start();
require ("../API/connect.php");
// require("../admin/check_election.php");

if (empty($_SESSION) || !isset($_SESSION['usersdata'])) {
    header("Location: .././Routes/login.php", TRUE, 301);
    exit();
}

$usersdata = $_SESSION['usersdata'];
$role=$usersdata['role'];
$userid = $usersdata['id'];

// Check if user data exists in the database
if($role==1){
    $query = "SELECT * FROM userdata WHERE id = :id";
}
else{
    $query = "SELECT * FROM candidate WHERE id = :id";

}
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $userid, SQLITE3_INTEGER);
$result = $stmt->execute();
$user_data_from_db = $result->fetchArray(SQLITE3_ASSOC);

if (!$user_data_from_db) {
    // If user data is not found, redirect to login
    session_destroy();
    header("Location: ../Routes/login.php", TRUE, 301);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/userprofile.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <title>User Profile</title>
</head>

<body>
    <div id="headersection">
        <div id="bck-logout">
            <button id="back" onclick="backbutton()"> <span> Back </span></button>
            <div class="rt-nav">
                <!-- <button id="prof" onclick="profbutton()"> <span> Profile </span></button> -->
                <button id="res" onclick="resbutton()"> <span> Result </span></button>
                <button id="home" onclick="homebutton()"> <span>Home</span> </button>
                <button id="home" onclick="dashbutton()"> <span>Dashboard</span> </button>
                <button id="logout" onclick="logout()"> <span>Logout</span> </button>

            </div>

        </div>

        <!-- <h1>
            <marquee behavior="" direction=" ">Online Voting System</marquee>
        </h1> -->
        <h1>
                <marquee id="electionStatusMarquee">
                   
                </marquee>
            </h1>
        <script>
            var is_election_live=false;
            function backbutton() {
                window.history.back();
                document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                window.location.replace(".././Routes/login.php");
            }


            function logout() {
                document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                window.location.replace(".././Routes/login.php");
            }

            function homebutton() {
                window.location = "../Routes/home.php"
                // alert("button clicked");
            }
            function dashbutton() {
                window.location = "../Routes/dashboard.php"
                // alert("button clicked");
            }

            function resbutton() {
                if (is_election_live) {
                    alert('Elections are Live. Results not announced yet.');
                } else {
                    window.location = "./result.php";
                }
            }

            function profbutton() {
                window.location = "./Profile.php"
                // alert("button clicked");
            }
        </script>
    </div>
    <div class="profile">
    <div class="prof-img">
    <?php if ($usersdata['role'] == 1) { ?>
        <img src="../uploads/UserUploads/<?php echo $usersdata['photo']; ?>" id="user-img">
    <?php } else { ?>
        <img src="../uploads/CandidateUploads/<?php echo $usersdata['cand_img']; ?>" id="user-img">
    <?php } ?>
</div>
        <div class="prof-details">

            <table>
                <tr>
                    <th>Name</th>
                    <td><?php echo $usersdata['name'] ?></td>
                </tr>
                <tr>
                    <th>Mobile Number</th>
                    <td><?php echo $usersdata['mobile'] ?></td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td><?php echo $usersdata['gender'] ?></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td rowspan="2"><?php
                    echo $usersdata['address']
                        ?></td>
                </tr>
                <tr>
                </tr>
                <tr>

                    <th>Role</th>
                    <td><?php
                    if ($usersdata['role'] == 1) {
                        echo "Voter";
                    } else {
                        echo "Candidate";
                    }
                    ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Function to fetch election status via AJAX
            function checkElectionStatus() {
                $.ajax({
                    type: "GET",
                    url: "../API/check_election_status.php",
                    dataType: "json",
                    success: function(response) {
                        if (response.election_status) {
                            $("#electionStatusMarquee").text("Election is Live Now");
                            is_election_live=true;
                        } else {
                            $("#electionStatusMarquee").text("Election have ended. Results Declared");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: " + status + " - " + error);
                    }
                });
            }

            checkElectionStatus(); // Initial call
            setInterval(checkElectionStatus, 1000); // Call every  seconds
        });
    </script>
</body>

</html>