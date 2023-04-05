
<?php
session_start();
include '../php/connection.php';

# Getter ng Manila Time
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['alreadyLogin'])) {
    header('location: ..');
}

if ($_SESSION['userClass'] != 0) {
    header('location: ..');
}

if (isset($_POST['out'])) {
    session_destroy();
    header('location: '. $_SERVER['PHP_SELF']);
}

######

function getSectionName() {
    $con = connect();

    $stmt = $con->prepare('SELECT sectionname FROM sections WHERE section_id = ?');
    $stmt->bind_param('i', $_SESSION['sectionid']);
    $stmt->execute();
    $stmt->bind_result($sectionName);
    $stmt->fetch();

    return $sectionName;
}

##########

if (isset($_POST['present'])) {
    recordAttendance();
}

function recordAttendance() {
    $con = connect();

    date_default_timezone_set('Asia/Manila'); # GMT +8 to Aryan kung saan nakapwesto ang Pilipins.
    $currentDate = date('Y-m-d');
    $stmt = $con->prepare('INSERT INTO attendance (attendance_date, student_id) VALUES (?, ?)');
    $stmt->bind_param('si', $currentDate, $_SESSION['id']);
    
    if ($stmt->execute()) {
        header('location: '.$_SERVER['PHP_SELF']);
    }

}

function isAlreadyPresent() {
    $con = connect();
    $currentDate = date('Y-m-d');

    $stmt = $con->prepare('SELECT * FROM attendance WHERE attendance_date = ? AND student_id = ?');
    $stmt->bind_param('si', $currentDate, $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return 1;
    } else {
        return 0;
    }
}

######

function listMyAttendance() {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC');
    $stmt->bind_param('i', $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td>You</td>
            <td><?php echo ayusinAngDate($row['attendance_date']); ?></td>
        </tr>
        <?php
    }
}

function ayusinAngDate($date) {
    return date('F d, Y', strtotime($date));
}

######

function attendanceIsAvailable() {
    
    $hour = date('H');
    $day = date('N');

    $isAvailableOnHours = $hour >= 6 && $hour <= 18; # Condition para tignan kung Alas Sais hanggang Alas 12
    $isAvailableOnDay = $day != 7; // Determiner kung not Equal sa Sunday today 

    if ($isAvailableOnHours && $isAvailableOnDay) {
        return 1;
    } else {
        return 0;
    }
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/query.css">
    <link rel="stylesheet" href="../css/system.css">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student</title>
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
                <p>You are logged in as a student of <?php echo getSectionName(); ?></p>
                <form method="post">
                    <button name="out" type="submit"> Signout </button>
                </form>
            </div>
        </div>
    </section>

    <section id="attendance">
        <div class="container">
            <div class="wrapper">
            
                <h3>Today is 
                <?php
                date_default_timezone_set("Asia/Manila");
                $date = date('l F d, Y');
                echo $date; 
                ?></h3>

                <form method="post">

                    <?php 
                    
                    if (isAlreadyPresent()) {
                        ?>
                        <p class="attended">Your attendance for today is already recorded.</p>
                        <?php
                    } else if (!attendanceIsAvailable()){
                        ?>
                        <p class="not-available">Attendance isn't open at this moment. <br> Current time: <?php echo date('l h:i A'); ?></p>
                        <?php
                    } else if (!isAlreadyPresent() && attendanceIsAvailable()) {
                        ?>
                        <button name="present">I'm Present</button>
                        <?php
                    } 

                    ?>
                    
                </form>

            </div>
        </div>
    </section>

    <section id="panel">
        <div class="container">
            <div class="wrapper">

                <div class="top">
                    <h1>My Attendance</h1>
                </div>

                <div class="attendance-list">
                    <div class="tube">

                        <table id="attendance-table">
                            <tbody>
                                <tr>
                                    <th>Student</th>
                                    <th>Attendance Date</th>
                                </tr>
                                <?php listMyAttendance(); ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>
        </div>
    </section>

</body>
</html>