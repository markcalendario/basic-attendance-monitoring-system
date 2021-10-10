
<?php
session_start();
include '../php/connection.php';

if (!isset($_SESSION['alreadyLogin'])) {
    header('location: ..');
}

if ($_SESSION['userClass'] != 1) {
    header('location: ..');
}

if (isset($_POST['out'])) {
    session_destroy();
    header('location: '. $_SERVER['PHP_SELF']);
}

# Fetch muna yung advisory class ID
# Makukuha dapat sa session

fetchAdvisoryInfo();
function fetchAdvisoryInfo() {
    $con = connect();
    
    $stmt = $con->prepare('SELECT * FROM sections WHERE sectionadviserid = ?');
    $stmt->bind_param('i', $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $_SESSION['advisoryName'] = $row['sectionname'];
    $_SESSION['advisoryID'] = $row['section_id'];
}

function listMyStudents() {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM account
    LEFT JOIN userinfo
    ON userinfo.id = account.id
    WHERE sectionid = ? AND class = 0 AND account_status = 1');
    $stmt->bind_param('i', $_SESSION['advisoryID']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td>S-00<?php echo $row['id'] . '-' . date('Y'); ?></td>
            <td><?php echo $row['fullname']; ?></td>
            <td><?php echo $row['gender']; ?></td>
            <td><?php echo $row['address']; ?></td>
        </tr>
        <?php
    }
}

function listAllAttendance() {
    $con = connect();

    if (!isset($_GET['sbd'])) {
        $stmt = $con->prepare('SELECT * FROM attendance
            LEFT JOIN userinfo
            ON attendance.student_id = userinfo.id
            LEFT JOIN account
            ON userinfo.id = account.id
            LEFT JOIN sections
            ON section_id = sectionid
            WHERE class = 0 AND sectionid = ?
            ORDER BY attendance_date DESC');
        $stmt->bind_param('i', $_SESSION['advisoryID']);
    } else {
        $stmt = $con->prepare('SELECT * FROM attendance
            LEFT JOIN userinfo
            ON attendance.student_id = userinfo.id
            LEFT JOIN account
            ON userinfo.id = account.id
            LEFT JOIN sections
            ON section_id = sectionid
            WHERE class = 0 AND sectionid = ? AND attendance_date = ?
            ORDER BY attendance_date DESC');
        $stmt->bind_param('is', $_SESSION['advisoryID'], $_GET['sbd']);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row['fullname']; ?></td>
            <td><?php echo $row['sectionname']; ?></td>
            <td><?php echo sentenceTypeNaAttendance($row['attendance_date']); ?></td>
        </tr>
        <?php
    }
}

function sentenceTypeNaAttendance($date) {
    return date('F d, Y', strtotime($date));
}

if (isset($_POST['sbd'])) {
    header('location: ?sbd='. $_POST['attendance-date-sbd']);
}

if (isset($_POST['clearsearches'])) {
    header('location: '.$_SERVER['PHP_SELF']);
}

#####

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/query.css">
    <link rel="stylesheet" href="../css/system.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher</title>
</head>
<body>

    <nav class="dark-nav">
        <div class="container">
            <div class="wrapper center-nav">

                <div class="ams-brand">
                    <div class="img-cont">
                        <img src="../files/AU Logo.png">
                    </div>
                    <h1>AMS</h1>
                </div>

            </div>
        </div>
    </nav>

    <section id="welcomer">
        <div class="container">
            <div class="wrapper">

                <h1>Welcome, <?php echo $_SESSION['fullname']; ?></h1>
                <p>You are logged in as a Teacher of <?php echo $_SESSION['advisoryName']; ?>.</p>
                <form method="post">
                    <button name="out" type="submit"> Signout </button>
                </form>

            </div>
        </div>
    </section>

    <section id="controls">
        <div class="container">
            <div class="wrapper">

                <h1>Control Panel</h1>

                <div class="controls">
                    <a type="submit" href="?control=students" class="<?php if (isset($_GET['control']) && $_GET['control'] == 'students') { echo "active"; } ?>"> Manage Students </a>
                    <a type="submit" href="?control=overallattendance" class="<?php if (!isset($_GET['control']) || isset($_GET['control']) && $_GET['control'] == 'overallattendance') { echo "active"; } ?>"> View Overall Attendance </a>
                </div>

            </div>
        </div>
    </section>



    <?php

    if (isset($_GET['control']) && $_GET['control'] == 'students') {
        ?>
        <section id="panel">
            <div class="container">
                <div class="wrapper">

                    <div class="top">
                        <h1>Manage Students</h1>
                        <p>The list of your students are from the Faculty.</p>
                    </div>
                

                    <div class="stud-list">
                        <div class="tube">

                            <table>
                                <tbody>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Address</th>
                                    </tr>
                                    <?php listMyStudents(); ?>
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </section>
        <?php
    }

    if (!isset($_GET['control']) || isset($_GET['control']) && $_GET['control'] == 'overallattendance') {
        ?>
        <section id="panel">
            <div class="container">
                <div class="wrapper">

                    <div class="top">
                        <h1>Overall Attendance</h1>
                    </div>

                    <div class="date-picker">
                        <form method="post">
                            <input type="date" name="attendance-date-sbd" placeholder="a">
                            <input type="submit" name="sbd" value="Search by date">
                            <?php
                            
                            if (isset($_GET['sbd'])) {
                                ?>
                                <input type="submit" style="background-color: red;" name="clearsearches" value="Clear Search">
                                <?php
                            }

                            ?>
                        </form>
                    </div>

                    <div class="attendance-list">
                        <div class="tube">

                            <table id="attendance-table">
                                <tbody>
                                    <tr>
                                        <th>Student</th>
                                        <th>Student's Section</th>
                                        <th>Attendance Date</th>
                                    </tr>
                                    <?php listAllAttendance(); ?>
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </section>
        <?php
    }
    

    ?>

</body>
</html>