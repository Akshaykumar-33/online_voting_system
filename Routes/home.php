<?php

require ("../API/connect.php");
require("../admin/check_election.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jersey+25+Charted&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../CSS/home.css">
    <link rel="stylesheet" href="../CSS/bootstrap.min.css">
    <script src="../CSS/bootstrap.min.js">
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <title>Online Voting System</title>

</head>

<body class="d-flex flex-column">

    <div id="headersection">
        <button id="homebtn" onclick="homepage()">Home</button>
        <div id="signin-up">
            <button id="signin" onclick="signin()"> <span>SignIn</span> </button>
            <button id="signup" onclick="signUp()"> <span>Sign-Up</span> </button>
        </div>
    </div>
    <br>
    <div id="livearea">
        <h2 id="live">
            <?php
            if ($election_is_live == true) {
                echo "ELECTION IS LIVE";
            } else {
                echo "ELECTION ENDED";
            }
            ?>
        </h2>
    </div>

    <b><span style="font-size:30px;cursor:pointer;color: black;" onclick="openNav()">&#9776; Candidate List</span></b>

    
    <div id="myNav" class="overlay">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <div class="overlay-content" id="candidate-container">
            <!-- Candidate cards will be inserted here dynamically -->
        </div>
    </div>
    <div id="page-content">
    </div>
    <footer id="sticky-footer" class="flex-shrink-0  text-dark-50">
        <div class="container text-center">
            <small>Copyright &copy; All Rights Reserved</small>
        </div>
    </footer>

    <script>
        $(document).ready(function() {
            function fetchCandidates() {
                $.ajax({
                    type: "GET",
                    url: "../API/fetch_data.php",
                    dataType: "json",
                    success: function(candidates) {
                        var candidateContainer = $("#candidate-container");
                        candidateContainer.empty(); // Clear existing content

                        candidates.forEach(function(candidate) {
                            var card = `
                                <div class="flip-card" id="coloumn">
                                    <div class="flip-card-inner">
                                        <div class="flip-card-front">
                                            <img src="../uploads/CandidateUploads/${candidate.party_img}" alt="Avatar" id="partyimg">
                                        </div>
                                        <div class="flip-card-back">
                                            <h1>Candidate Name</h1>
                                            <p style="font-size: large;">${candidate.name}</p>
                                            <img src="../uploads/CandidateUploads/${candidate.cand_img}" alt="" id="candidateimg">
                                        </div>
                                    </div>
                                </div>
                            `;
                            candidateContainer.append(card);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: " + status + " - " + error);
                    }
                });
            }

            fetchCandidates();
            setInterval(fetchCandidates, 10000); // Refresh every 10 seconds
            // Function to fetch election status via AJAX
            function checkElectionStatus() {
                $.ajax({
                    type: "GET",
                    url: "../API/check_election_status.php",
                    dataType: "json",
                    success: function(response) {
                        // Update UI based on election status
                        if (response.election_status) {
                            $("#live").text("ELECTION IS LIVE");
                        } else {

                            $("#live").text("ELECTION ENDED");
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
    <script>

        function homepage() {
            window.location = "./home.php"
        }

        function signin() {
            document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            window.location.replace(".././Routes/login.php");
        }

        function signUp() {
            window.location = "./reg.html";
        }

        function openNav() {
            document.getElementById("myNav").style.height = "100%";
        }

        function closeNav() {
            document.getElementById("myNav").style.height = "0%";
        }
    </script>
</body>

</html>