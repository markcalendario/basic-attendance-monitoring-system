<?php
session_start();
include 'php/connection.php';

function transferToRespectivePage() {
    if ($_SESSION['userClass'] == 0) {
        header('location: student');
    }

    if ($_SESSION['userClass'] == 1) {
        header('location: teacher');
    }

    if ($_SESSION['userClass'] == 2) {
        header('location: faculty');
    }
}

if (isset($_SESSION['alreadyLogin'])) {
    transferToRespectivePage();
}

if (isset($_POST['login'])) {
    validateLogin();
}

function validateLogin() {
    if (empty($_POST['username']) ||
    empty($_POST['password'])) {
        ?>
        <script>alert('Please complete the login input field.');</script>
        <?php
    } else {
        checkLogin();
    }
}

function checkLogin() {
    $con = connect();

    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $stmt = $con->prepare('SELECT * FROM account WHERE username = ? AND password = ?');
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        logInUser($username, $password);
    } else {
        ?>
        <script>alert('Incorrent credentials. Try again.');</script>
        <?php
    }
    
}

function loginUser($username, $password) {
    $con = connect();

    $stmt = $con->prepare('SELECT 
    account.id, 
    account.class,
    userinfo.fullname, 
    userinfo.sectionid
    FROM account
    LEFT JOIN userinfo 
    ON account.id = userinfo.id
    WHERE username = ? AND password = ?');
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $stmt->bind_result($id, $class, $fullname, $sectionid);
    $stmt->fetch();

    $_SESSION['id'] = $id;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['userClass'] = $class;
    $_SESSION['sectionid'] = $sectionid;
    $_SESSION['alreadyLogin'] = true;

    header('location: '. $_SERVER['PHP_SELF']);
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/query.css">
    <link rel="stylesheet" href="css/system.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
</head>
<body>

    <nav>
        <div class="container">
            <div class="wrapper">

                <div class="ams-brand">
                    <div class="img-cont">
                        <img src="files/AU Logo.png">
                    </div>
                    <h1>AMS</h1>
                </div>

                <div class="ams-links">
                    <a href="#about">About</a>
                    <a href="#login">Login</a>
                </div>

            </div>
        </div>
    </nav>

    <section id="front">
        <div class="container">
            <div class="wrapper">

                <div class="texts">
                    <img src="files/AU Logo.png">
                    <h1>Attendance Monitoring System</h1>
                    <p>The First Online Attendance Monitoring System of Arellano University</p>
                </div>

            </div>
        </div>
    </section>

    <section id="login">
        <div class="container">
            <div class="wrapper">

                <div class="left">
                    <h1>Experience AU-AMS</h1>
                    <p>By signing in! It's secure.</p>
                </div>
                
                <div class="right">
                    <div class="login-panel">
                        <h1>Login</h1>
                        <form method="post">
                            <fieldset>
                                <legend>Username</legend>
                                <input type="text" name="username">
                            </fieldset>
                            <fieldset>
                                <legend>Password</legend>
                                <input type="password" name="password">
                            </fieldset>
                            <input type="submit" value="Login" name="login">
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="about">
        <div class="container">

            <h1>History of Arellano University</h1>

            <div class="wrapper">

                <div class="left">
                    <img src="files/cayetano.jpg">
                </div>

                <div class="right">
                    <p>Arellano University (AU) is a private, coeducational 
                        and nonsectarian university located in Manila, Phil
                        ippines. It was founded in 1938 as a law school by Flor
                        entino Cayco, Sr., the first Filipino Undersecretary of
                            Public Instruction. The university was named after 
                            Cayetano Arellano, the first Chief Justice of the Supreme 
                            Court of the Philippines.
                    </p>
                </div>

            </div>
        </div>
    </section>
    
</body>
</html>