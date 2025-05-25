<?php
session_start();

require ("../API/connect.php");
require("../admin/check_election.php");

if (empty($_SESSION) || !isset($_SESSION['usersdata'])) {
    header("Location: .././Routes/login.php", TRUE, 301);
    exit();
}

$groups = $db->query("SELECT * FROM candidate WHERE role=2 and verified=1 ");
$groupdata = [];
while ($row = $groups->fetchArray(SQLITE3_ASSOC)) {
    $groupdata[] = $row;
}

$_SESSION['groupdata'] = $groupdata;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <title>Voting Results</title>
    <style>
        body {
            background-color: #000;
            color: black;
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 50px;
        }

        .result-box {
            width: 80%;
            margin: 0 auto;
            background-color: #222;
            border: 2px solid #fff;
            padding: 20px;
            border-radius: 10px;
        }

        .candidate {
            margin-bottom: 20px;
        }

        .winner {
            margin-top: 40px;
            font-size: 24px;
        }

        .special-election {
            margin-top: 20px;
            font-size: 18px;
            color: #ff0000;
        }
    </style>
</head>

<body>
    <div id="headersection">
        <div id="bck-logout">
            <button id="back" onclick="backbutton()" span> Back </span></button>
            <div class="rt-nav">
                <button id="prof" onclick="profbutton()"> <span> Profile </span></button>
                <button id="home" onclick="homebutton()"> <span>Home</span> </button>
                <button id="home" onclick="dashbutton()"> <span>Dashboard</span> </button>
                <button id="logout" onclick="logout()"> <span>Logout</span> </button>

            </div>

        </div>

        <h1>
            <marquee behavior="" direction=" ">

                <?php
                if ($election_is_live == true) {
                    echo "Election is Live Now";
                } else {
                    echo "Election have ended. Results Declared";
                } ?>
            </marquee>
        </h1>
        <script>
            function backbutton() {
                window.history.back();
            }
            function dashbutton() {
                window.location = "./dashboard.php"
            }
            function homebutton() {
                window.location = "../Routes/home.php";
            }

            function profbutton() {
                window.location = "./Profile.php";
            }

           

            function logout() {
                document.cookie = "<?php echo session_name(); ?>=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                window.history.back();
            }
        </script>

    </div>
    <div style="color: black; background-image: url(../images/img2.jpg);;border: solid whitesmoke; " class="result-box">
        <h1>Voting Results</h1>

        <?php foreach ($groupdata as $result) {
            echo "<div class='candidate'>";
            echo "<h2>";
            echo $result['name'];
            echo "</h2>";
            echo "<p>Votes: <span id='mvotes'>";
            echo $result['votes'];
            echo "</span></p>";
            echo "</div>";
            echo "<br>";
        } ?>

        <div style="border: solid rgb(255, 246, 246); background-color: rgb(255, 243, 5); color: #000; font-weight: 900;"
            class="winner">
            <p>Winner: <span id="winner_name"></span></p>
        </div>

        <div class="special-election" id="special_election"></div>
    </div>

    <script>
        const iwinnerName = document.querySelector('#winner_name');
        let cdivs = document.querySelectorAll('.candidate');
        let maxVotes = -1;
        let winner = "";
        let tie = false;
        for (let cdiv of cdivs) {
            let cvotes = parseInt(cdiv.querySelector('#mvotes').innerHTML);
            if (maxVotes == cvotes) {
                tie = true;
                break;
            }
            if (maxVotes <= cvotes) {
                winner = cdiv.querySelector('h2').innerHTML;
                maxVotes = cvotes;
            }
        }
        if (tie) iwinnerName.innerHTML = "Election Tie";
        else iwinnerName.innerHTML = winner;
    </script>
</body>

</html>