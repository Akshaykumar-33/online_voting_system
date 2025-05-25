<?php

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}
require ("connect.php");
require("check_election.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <script>
        $(document).ready(function(){
            // Function to update statistics
            function updateStatistics() {
                $.ajax({
                    url: 'fetch_statistics.php', // PHP script to fetch the updated data
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        console.log('AJAX Success:', data);
                        $('#total_voters').html(data.total_voters);
                        $('#male_voters').text(data.male_voters);
                        $('#female_voters').text(data.female_voters);
                        $('#voted_voters').text(data.voted_voters);
                        $('#male_voters_cast').text(data.male_voters_cast);
                        $('#female_voters_cast').text(data.female_voters_cast);

                        // Update the chart
                        genderChart.data.datasets[0].data = [data.male_voters, data.female_voters];
                        genderChart.update();
                        votedChart.data.datasets[0].data = [data.total_voters, data.voted_voters];
                        votedChart.update();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX Error:', textStatus, errorThrown); // Log any errors
                }
                });
            }
            // Initial update
            updateStatistics();

            // Update every 10 seconds
            setInterval(updateStatistics, 10000);
        });
    </script>
    <div id="topbar">
        <div class="topbar-content">
            <h1>Admin Dashboard</h1>
        </div>
    </div>

    
    <div id="sidebar">
        <div class="sidebar-content">
            <a href="manage_users.php">Manage Users</a><br>
            <a href="manage_election.php">Manage Election</a><br>
            <a href="#" onclick="res()">View Results</a><br>
            <a href="#" onclick="printPart()">Print Data</a><br>
            <a href="admin_logout.php">Logout</a>
        </div>
    </div>
<div id="blocks">
    <div id="main-content" class="printable">
        <div id="voter" class="block" >
            <h3>Voter Statistics</h3>
            <p>Total Registered Voters: <span id="total_voters"></span></p>
            <p>Total Male Voters: <span id="male_voters"></span></p>
            <p>Total Female Voters: <span id="female_voters"></span></p>
        </div>
        <div id="casted" class ="block">
            <h3>Voter Statistics</h3>
            <p>Voters Who Cast Their Vote: <span id="voted_voters"></span></p>
            <p>Male Voters Who Cast Their Vote: <span id="male_voters_cast"></span></p>
            <p>Female Voters Who Cast Their Vote: <span id="female_voters_cast"></span></p>
        </div>

        <div class="block">
            <h3>Gender Distribution</h3>
            <canvas id="genderChart"></canvas>
        </div>
        <div class="block">
            <h3>Voter Distribution</h3>
            <canvas id="votedChart"></canvas>
        </div>

    </div>
</div>
    <script>
        function res(){
            if(!is_election_live){
                window.location = "./view_results.php";
            }
            else{
                alert("Elections are live now");
            }
        }
        function printPart(){
            window.print()
        }
        var ctx = document.getElementById('genderChart').getContext('2d');
        var genderChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [],
                    backgroundColor: ['#36A2EB', '#FF6384']
                }]
            },
            options: {
                responsive: true
            }
        });

        var ctxx = document.getElementById('votedChart').getContext('2d');
        var votedChart = new Chart(ctxx, {
            type: 'pie',
            data: {
                labels: ['Total', 'Voted'],
                datasets: [{
                    data: [],
                    backgroundColor: ['#36A2EB', '#FF6384']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>
