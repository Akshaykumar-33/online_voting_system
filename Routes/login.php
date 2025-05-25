<?php

session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online voting system</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>

<body>
    <div id="container">
        <div id="headersection">
            <h1>ONLINE VOTING SYSTEM</h1>
        </div>
        <h4 id='banmsg' style='color:red;display:none;'>You account is locked due to multiple failed login attempts.
            <br> [ Contact Admin for support ]
        </h4>

        <div id="bodysection">
            <h2>LOGIN DETAILS</h2>
            <form action="../API/login.php" method="post" enctype="multipart/form-data">
                <fieldset id="mform">
                    <input type="tel" name="mobile" id="mobile-detail" placeholder="Enter Mobile Number" maxlength="10"
                        required><br><br>
                    <input type="password" name="password" id="password-detail" placeholder="Enter password"
                        required><br><br>
                    <select name="role" id="" required>
                        <option value="0">Select option</option>
                        <option value="1">Voter</option>
                        <option value="2">Group</option>
                    </select><br><br>
                    <button type="submit" onclick="incAttempts()">LOGIN</button><br><br>
                    <p>New User?<a href="./reg.html">Register Here</a></p>
                </fieldset>
            </form>
        </div>
    </div>
    <?php if (isset($_SESSION['login_banned']) && $_SESSION['login_banned'] == 'true')
        echo "<script>
        const ibanmsg = document.querySelector('#banmsg');
        ibanmsg.style.display = 'block';
        const imform = document.querySelector('#mform');
        imform.setAttribute('disabled', 'disabled');
    </script>";
    ?>
</body>

</html>