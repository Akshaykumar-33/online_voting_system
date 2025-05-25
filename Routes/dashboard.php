<?php
session_start();

require ("../API/connect.php");
require("../admin/check_election.php");

if (empty($_SESSION) || !isset($_SESSION['usersdata'])) {
    header("Location: .././Routes/login.php");
    exit();
}

$usersdata = $_SESSION['usersdata'];
$groupdata = $_SESSION['groupdata'];

// print_r($groupdata);
if ($_SESSION['usersdata']['status'] == 0) {
    $status = 'Not voted';
} else {
    $status = 'Voted successfully';
}


$groups = $db->query("SELECT * FROM candidate WHERE role = 2 and verified=1");
$groupdata = [];
while ($row = $groups->fetchArray(SQLITE3_ASSOC)) {
    $groupdata[] = $row;
}
$_SESSION['groupdata'] = $groupdata;

?>

<html>

<head>
    <title>Online voting system - Dashboard</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


</head>

<body>
    <div id="mainsection">
        <div id="headersection">
            <div id="bck-logout">
                <button id="back" onclick="backbutton()"> <span> Back </span></button>
                <div class="rt-nav">
                    <button id="prof" onclick="profbutton()"> <span> Profile </span></button>
                    <button id="res" onclick="resbutton()"> <span> Result </span></button>
                    <button id="logout" onclick="logout()"> <span>Logout</span> </button>
                    <button id="home" onclick="homebutton()"> <span>Home</span> </button>

                </div>

            </div>

           
            <h1>
                <marquee id="electionStatusMarquee">
                   
                </marquee>
            </h1>
            <script>
                function homebutton() {
                    window.location = "./home.php";
                }

                function backbutton() {
                    window.history.back();
                    document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    window.location.replace("./login.php");
                }


                function logout() {
                    document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    window.location.replace("./login.php");
                }


                function profbutton() {
                    window.location = "./Profile.php";
                }

                function resbutton() {
                    if (is_election_live) {
                        alert('Elections are Live. Results not announced yet.');
                    } else {
                        window.location = "./result.php";
                    }
                }

                function logout() {
                    document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    window.history.back();
                }
            </script>

        </div>

        <div id="user-profile">

        </div>

        <div id="group">
            <?php
            if ($_SESSION['groupdata']) {
                foreach ($groupdata as $party) {
                    $party_id = $party['id'];
                    $party_info = $db->querySingle("SELECT * FROM candidate WHERE id='$party_id'", true);
                    ?>
                    <div class="card">
                        <img src="../uploads/CandidateUploads/<?php echo $party_info['cand_img'] ?>" class="card-image">
                        <div class="card-content">
                            <p class="party-name">Party Name: <?php echo $party_info['name'] ?> </p>
                            <form action="../API/vote.php" method="post">
                                <p class="vote-status">Status: <?php echo $status ?> </p>
                                <input type="hidden" name="pvote" value="<?php echo $party_info['votes'] ?>">
                                <input type="hidden" name="pid" value="<?php echo $party_info['id'] ?>">
                                <input type="submit" name="votebutton" value="VOTE" class="vote-button" <?php if ($_SESSION['usersdata']['status'] == 1 || $election_is_live == false) {
                                    echo "disabled";
                                } ?>>
                                <br><br><br>
                            </form>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>

    </div>

    <script>
        window.localStorage.setItem("attempts", 0);
    </script>
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