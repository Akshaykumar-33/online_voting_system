<?php
session_start();
require("connect.php");
// require("check_election.php");

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = $_POST['delete_user_id'];

    // Check if the user is a voter or candidate and delete from the respective table
    $db->exec("DELETE FROM userdata WHERE id='$delete_user_id'");
    $db->exec("DELETE FROM candidate WHERE id='$delete_user_id'");

    echo json_encode(['status' => 'success']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="./css/manage_users.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div id="container">
        <div id="voter">
            <h2>Manage Voters</h2>
            <table id="voter-table">
                <caption>Voters Details</caption>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Gender</th>
                    <th colspan="2">Address</th>
                    <th>Actions</th>
                </tr>
                <!-- Voters will be loaded here via AJAX -->
            </table>
        </div>
        <div id="candidate">
            <h2>Manage Candidates</h2>
            <table id="candidate-table">
                <caption>Candidates Details</caption>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Gender</th>
                    <th colspan="2">Address</th>
                    <th>Actions</th>
                </tr>
                <!-- Candidates will be loaded here via AJAX -->
            </table>
        </div>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>

    <script>
        function loadUsers() {
            $.ajax({
                url: 'fetch_users.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#voter-table tr:gt(0)').remove();
                    $('#candidate-table tr:gt(0)').remove();

                    data.voters.forEach(function(voter) {
                        $('#voter-table').append(`
                            <tr>
                                <td>${voter.id}</td>
                                <td>${voter.name}</td>
                                <td>${voter.mobile}</td>
                                <td>${voter.gender}</td>
                                <td colspan="2">${voter.address}</td>
                                <td>
                                    <form class="delete-form" data-id="${voter.id}">
                                        <button type="submit">DELETE</button>
                                    </form>
                                </td>
                            </tr>
                        `);
                    });

                    data.candidates.forEach(function(candidate) {
                        $('#candidate-table').append(`
                            <tr>
                                <td>${candidate.id}</td>
                                <td>${candidate.name}</td>
                                <td>${candidate.mobile}</td>
                                <td>${candidate.gender}</td>
                                <td colspan="2">${candidate.address}</td>
                                <td>
                                    <form class="delete-form" data-id="${candidate.id}">
                                        <button type="submit">DELETE</button>
                                    </form>
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        }

        function deleteUser(userId) {
            $.ajax({
                url: 'manage_users.php',
                type: 'POST',
                data: { delete_user_id: userId },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        loadUsers();
                    } else {
                        alert('Error deleting user');
                    }
                }
            });
        }

        $(document).on('submit', '.delete-form', function(e) {
            e.preventDefault();
            var userId = $(this).data('id');
            deleteUser(userId);
        });

        $(document).ready(function() {
            loadUsers();
            setInterval(loadUsers, 10000); // Fetch users every 10 seconds
        });
    </script>
</body>
</html>
