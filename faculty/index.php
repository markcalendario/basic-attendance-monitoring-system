
<?php
session_start();
include '../php/connection.php';

if (!isset($_SESSION['alreadyLogin'])) {
    header('location: ..');
}

if ($_SESSION['userClass'] != 2) {
    header('location: ..');
}

if (isset($_POST['out'])) {
    session_destroy();
    header('location: '. $_SERVER['PHP_SELF']);
}

########

if (isset($_POST['cancel'])) {
    header('location: ?'. $_SERVER['QUERY_STRING']);
}

#########


function getOverAllAttendance() {
    $con = connect(); 

    if (!isset($_GET['sbd'])) {
        $stmt = $con->prepare('SELECT fullname, sectionname, attendance_date, attendance.id FROM attendance
            LEFT JOIN userinfo
            ON attendance.student_id = userinfo.id
            LEFT JOIN account
            ON userinfo.id = account.id
            LEFT JOIN sections
            ON section_id = sectionid
            WHERE class = 0 AND account_status = 1
            ORDER BY attendance_date DESC, sectionname DESC');
    } else {
        $stmt = $con->prepare('SELECT fullname, sectionname, attendance_date, attendance.id FROM attendance
            LEFT JOIN userinfo
            ON attendance.student_id = userinfo.id
            LEFT JOIN account
            ON userinfo.id = account.id
            LEFT JOIN sections
            ON section_id = sectionid
            WHERE class = 0 AND attendance_date = ? AND account_status = 1
            ORDER BY attendance_date DESC, sectionname DESC');
        $stmt->bind_param('s', $_GET['sbd']);
    }
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($fullName, $sectionName, $attendanceDate, $attendanceID);

    while ($stmt->fetch()) {
        ?>
        <form method="post">
            <input type="hidden" name="attendance-id" value="<?php echo $attendanceID; ?>">
            <tr>
                <td><?php echo $fullName; ?></td>
                <td><?php echo $sectionName; ?></td>
                <td><?php echo sentenceTypeNaAttendance($attendanceDate); ?></td>
            </tr>
        </form>
        <?php
    }
}

function sentenceTypeNaAttendance($date) {
    return date('F d, Y', strtotime($date));
}

######
if (isset($_POST['sbd'])) {
    header('location: ?sbd='. $_POST['attendance-date-sbd']);
}

if (isset($_POST['clearsearches'])) {
    header('location: '.$_SERVER['PHP_SELF']);
}

########

if (isset($_POST['save-teacher'])) {

    if (!empty($_POST['teacher-name'])) {
        $con = connect();
       
        $username = strtolower(str_replace(' ', '', $_POST['teacher-name'])).'.teacher';
        
        $year = date('Y');
        $password = strtolower(str_replace(' ', '', $_POST['teacher-name'])).'.teacher.'.$year;
        
        $class = 1;
        
        $stmt = $con->prepare('INSERT INTO account (username, password, class) VALUES (?,?,?)');
        $stmt->bind_param('ssi', $username, $password, $class);
        $stmt->execute();

        $stmt2 = $con->prepare('INSERT INTO userinfo (id, fullname) VALUES (?,?)');
        $lastInsertID = $con->insert_id;
        $stmt2->bind_param('is', $lastInsertID, $_POST['teacher-name']);
        $stmt2->execute();

        if ($stmt2->execute()) {
            header('location: '. $_SERVER['PHP_SELF']. '?control=teachers&success');
        }
    } else {
        ?>
        <script>alert('You cannot submit this because of an empty field.');</script>
        <?php
    }

}

########
# Manage Teachers

if (isset($_POST['deac-teacher'])) {
    $con = connect();

    $stmt = $con->prepare('UPDATE account SET account_status = 0 WHERE id = ?');
    $stmt->bind_param('i', $_POST['teacher-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=teachers&class=active');
    }
}

if (isset($_POST['restore-teacher'])) {
    $con = connect();

    $stmt = $con->prepare('UPDATE account SET account_status = 1 WHERE id = ?');
    $stmt->bind_param('i', $_POST['teacher-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=teachers&class=inactive');
    }
}

if (isset($_POST['delete-teacher'])) {
    $con = connect();

    # Delete Account Credentials
    $stmt = $con->prepare('DELETE FROM account WHERE id = ?');
    $stmt->bind_param('i', $_POST['teacher-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=teachers&class=inactive');
    }

}

function listAllActiveTeachers() {
    $con = connect();

    $stmt = $con->prepare('SELECT account.id, fullname FROM account
    LEFT JOIN userinfo
    ON account.id = userinfo.id
    WHERE class = 1 and account_status = 1');
    $stmt->execute();
    $stmt->bind_result($tid, $tFullName);
    
    while ($stmt->fetch()) {
        ?>
        <form method="post">
            <input type="hidden" name="teacher-id" value="<?php echo $tid; ?>">
            <tr>
                <?php

                if (isset($_POST['edit-teacher']) && $_POST['teacher-id'] == $tid) {
                    ?>
                    <td> <input type="text" name="new-teacher-name" value="<?php echo $tFullName; ?>"> </td>
                    <?php
                } else {
                    ?>
                    <td> <?php echo $tFullName; ?> </td>
                    <?php
                }

                ?>
                <td> 
                    <?php

                    if (!isset($_POST['edit-teacher'])) {
                        ?>
                        <button class="deactivate" name="deac-teacher"> Deactivate Teacher </button> 
                        <button class="edit" name="edit-teacher"> Edit Teacher Name </button> 
                        <?php
                    } else {
                        ?>
                        <button class="deactivate" name="cancel"> Cancel Edit </button> 
                        <button class="save" name="save-edit-teacher"> Save Teacher Name </button> 
                        <?php
                    }

                    ?>
                </td>
            </tr>
        </form>
        <?php
    }
}

if (isset($_POST['save-edit-teacher'])) {
    if (!empty($_POST['new-teacher-name'])) {
        saveEdit();
    } else {
        ?> <script>alert('Please fill out new teacher name.')</script> <?php
    }
}

function saveEdit() {
    $con = connect();

    $stmt = $con->prepare('UPDATE userinfo SET fullname = ? WHERE id = ?');
    $stmt->bind_param('si', $_POST['new-teacher-name'], $_POST['teacher-id']);
   
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=teachers');
    }
}

function listAllInactiveTeachers() {
    $con = connect();

    $stmt = $con->prepare('SELECT account.id, fullname FROM account
    LEFT JOIN userinfo
    ON account.id = userinfo.id
    WHERE class = 1 and account_status = 0');
    $stmt->execute();
    $stmt->bind_result($tid, $tFullName);
    
    while ($stmt->fetch()) {
        ?>
        <form method="post">
            <input type="hidden" name="teacher-id" value="<?php echo $tid; ?>">
            <tr>
                <td> <?php echo $tFullName; ?> </td>
                <td> <button class="restore" name="restore-teacher"> Activate </button> <button class="deactivate" name="delete-teacher"> Delete Permanently </button> </td>
            </tr>
        </form>
        <?php
    }
}

########
# Manage Sections

if (isset($_POST['save-section'])) {
    if (!empty($_POST['new-section-name']) 
    && !empty($_POST['new-section-adviser'])) {
        insertSectionToDatabase();
    } else {
        ?> <script>alert('Form field are empty.')</script> <?php
    }

}

function insertSectionToDatabase() {
    $con = connect();

    $stmt = $con->prepare('INSERT INTO sections (sectionname, sectionadviserid) VALUES (?,?)');
    $stmt->bind_param('si', $_POST['new-section-name'], $_POST['new-section-adviser']);
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=section');
    }
}

function optionOfAvailableTeachers() {
    $con = connect();

    $stmt = $con->prepare('SELECT userinfo.id, fullname FROM userinfo
    RIGHT JOIN account
    ON userinfo.id = account.id
    WHERE class = 1');
    $stmt->execute();
    $stmt->bind_result($tid, $fullName);

    while ($stmt->fetch()) {
        if (isTeacherHasNoAdvisory($tid)) {
            ?>
            <option value="<?php echo $tid; ?>"> <?php echo $fullName; ?> </option>
            <?php
        }
    }
}

function isTeacherHasNoAdvisory($tid) {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM sections WHERE sectionadviserid = ?');
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return 0;
    } else {
        return 1;
    }
}

function listAllSection() {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM sections');
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        ?>
        <form method="post">
        <input type="hidden" name="section-id" value="<?php echo $row['section_id']; ?>">
        <tr>
            <?php

            if (isset($_POST['edit-section']) && $_POST['section-id'] == $row['section_id']) {
                ?> 
                <td> <input type="text" name="new-section-name" value="<?php echo $row['sectionname']; ?>"> </td> 
                <td> <?php echo getAdviserName($row['sectionadviserid']); ?></td>
                <td> <button class="save" name="save-section"> Save </button> <button class="cancel" name="cancel"> Cancel </button> </td>
                <?php
            } else {
                ?> 
                <td> <?php echo $row['sectionname']; ?> </td> 
                <td> <?php echo getAdviserName($row['sectionadviserid']); ?></td>
                <td> <button class="edit" name="edit-section"> Edit Section </button> </td>
                <?php
            }

            ?>

        </tr>
        </form>
        <?php
    }
}

function getAdviserName($adviserid) {
    $con = connect();

    $stmt = $con->prepare('SELECT fullname FROM userinfo WHERE id = ?');
    $stmt->bind_param('i', $adviserid);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($fullname);
    $stmt->fetch();

    return $fullname;
}

if (isset($_POST['save-section'])) {
    if (empty($_POST['new-section-name'])) {
        ?> <script>alert('Form field is empty.')</script> <?php
    } else {

        $con = connect();

        $stmt = $con->prepare('UPDATE sections SET sectionname = ? WHERE section_id = ?');
        $stmt->bind_param('si', $_POST['new-section-name'], $_POST['section-id']);
        
        if ($stmt->execute()) {
            header('location:'.$_SERVER['PHP_SELF']. '?control=section');
        }

    }
}

######
# Student Management
function listStudents() {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM account
    LEFT JOIN userinfo
    ON userinfo.id = account.id
    WHERE class = 0 AND account_status = 1');
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        ?>
        <form method="post">
            <input type="hidden" name="student-id" value="<?php echo $row['id']; ?>">
            <?php
                if (isset($_POST['edit-student']) && $_POST['student-id'] == $row['id']) {
                    ?>
                    <tr>        
                        <td>S-00<?php echo $row['id'] . '-' . date('Y'); ?></td>
                        <td> <input type="text" name="new-student-name" value="<?php echo $row['fullname']; ?>"> </td>
                        <td> <input type="text" name="new-student-gender" value="<?php echo $row['gender']; ?>"> </td>
                        <td> <input type="text" name="new-student-address" value="<?php echo $row['address']; ?>"> </td>
                        <td> <button class="save" type="submit" name="save-student">Save</button> <button class="deactivate" type="submit" name="cancel">Cancel</button> </td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <tr>        
                        <td>S-00<?php echo $row['id'] . '-' . date('Y'); ?></td>
                        <td><?php echo $row['fullname']; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td><?php echo $row['address']; ?></td>
                        <td> <button class="edit" type="submit" name="edit-student">Edit Student</button> <button class="deactivate" type="submit" name="deac-student">Deactivate</button> </td>
                    </tr>
                    <?php
                }
            ?>
        </form>
        <?php
    }
}

function listDeactivatedStudents() {
    $con = connect();

    $stmt = $con->prepare('SELECT * FROM account
    LEFT JOIN userinfo
    ON userinfo.id = account.id
    WHERE class = 0 AND account_status = 0');
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        ?>
        <form method="post">
            <input type="hidden" name="student-id" value="<?php echo $row['id']; ?>">
            <tr>        
                <td>S-00<?php echo $row['id'] . '-' . date('Y'); ?></td>
                <td><?php echo $row['fullname']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo $row['address']; ?></td>
                <td> <button class="restore" type="submit" name="reac-student">Reactivate</button> <button class="deactivate" type="submit" name="delete-student">Delete Permanently</button> </td>
            </tr>
        </form>
        <?php
    }
}

if (isset($_POST['save-student'])) {
    if (empty($_POST['new-student-name']) || empty($_POST['new-student-gender']) || empty($_POST['new-student-address'])) {
        ?> <script>alert('Form fields are empty.')</script> <?php
    } else {
        saveStudent();
    }
}

function saveStudent() {
    $con = connect();

    $stmt = $con->prepare('UPDATE userinfo SET fullname = ?, address = ?, gender = ? WHERE id = ?');
    $stmt->bind_param('sssi', $_POST['new-student-name'], $_POST['new-student-address'], $_POST['new-student-gender'], $_POST['student-id']);
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=students');
    }

}

if (isset($_POST['deac-student'])) {
    $con = connect();

    $stmt = $con->prepare('UPDATE account SET account_status = 0 WHERE id = ?');
    $stmt->bind_param('i', $_POST['student-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=students&sclass=active');
    }
}

if (isset($_POST['reac-student'])) {
    $con = connect();

    $stmt = $con->prepare('UPDATE account SET account_status = 1 WHERE id = ?');
    $stmt->bind_param('i', $_POST['student-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=students&sclass=inactive');
    }
}

if (isset($_POST['delete-student'])) {
    $con = connect();

    # Delete Account Credentials
    $stmt = $con->prepare('DELETE FROM account WHERE id = ?');
    $stmt->bind_param('i', $_POST['student-id']);
    
    if ($stmt->execute()) {
        header('location:'.$_SERVER['PHP_SELF']. '?control=students&class=inactive');
    }

}

function optionOfSections() {
    $con = connect();

    $stmt = $con->prepare('SELECT section_id, sectionname FROM sections');
    $stmt->execute();
    $stmt->bind_result($sid, $sectionName);

    while ($stmt->fetch()) {
        ?>
        <option value="<?php echo $sid; ?>"> <?php echo $sectionName; ?> </option>
        <?php
    }
}

if (isset($_POST['save-add-student'])) {
    if (empty($_POST['add-student-name']) || empty($_POST['add-student-gender']) || empty($_POST['add-student-address']) || empty($_POST['add-student-section'])) {
        ?> <script>alert('Form fields are empty.')</script> <?php
    } else {
        addNewStudent();
    }
}

function addNewStudent() {
    $con = connect();

    $fullName = $_POST['add-student-name'];
    $gender = $_POST['add-student-gender'];
    $address = $_POST['add-student-address'];
    $section = $_POST['add-student-section'];

    $username = strtolower(str_replace(' ', '', $fullName));
    $password = 'stdnt.'.strtolower(str_replace(' ', '', $fullName)).'.'.date('Y');
    $class = 0;

    $stmt = $con->prepare('INSERT INTO account (username, password, class) VALUES (?,?,?)');
    $stmt->bind_param('ssi', $username, $password, $class);
    $stmt->execute();

    $lastID = $con->insert_id;

    $stmt = $con->prepare('INSERT INTO userinfo (id, fullname, gender, address, sectionid) VALUES (?,?,?,?,?)');
    $stmt->bind_param('isssi', $lastID, $fullName, $gender, $address, $section);
    $stmt->execute();

    header('location:'.$_SERVER['PHP_SELF']. '?control=students');
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
    <title>Faculty</title>
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
                <p>You are logged in as Faculty Administrator.</p>
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
                    <a type="submit" href="?control=section" class="<?php if (isset($_GET['control']) && $_GET['control'] == 'section') { echo "active"; } ?>"> Manage Sections </a>
                    <a type="submit" href="?control=teachers" class="<?php if (isset($_GET['control']) && $_GET['control'] == 'teachers') { echo "active"; } ?>"> Manage Teachers </a>
                    <a type="submit" href="?control=students" class="<?php if (isset($_GET['control']) && $_GET['control'] == 'students') { echo "active"; } ?>"> Manage Students </a>
                    <a type="submit" href="?control=overallattendance" class="<?php if (!isset($_GET['control']) || isset($_GET['control']) && $_GET['control'] == 'overallattendance') { echo "active"; } ?>"> View Overall Attendance </a>
                </div>

            </div>
        </div>
    </section>

    <?php

    if (isset($_GET['control']) && $_GET['control'] == 'section') {
        ?>
        <section id="panel">
            <div class="container">
                <div class="wrapper">

                    <div class="top">
                        <h1>Manage Sections</h1>
                    </div>
                    
                    <div class="sec-creator">
                        <form method="post">
                            <?php
                            if (!isset($_POST['create-section'])) {
                                ?>
                                <button type="submit" name="create-section">Create Section</button>
                                <?php
                            } else {
                                ?>
                                <button type="submit" name="save-section">Save Section</button>
                                <button name="cancel"> Cancel </button>
                                <?php
                            }
                            ?>
                            
                            <?php

                            if (isset($_POST['create-section'])) {
                                ?>
                                <div class="input-group">
                                    <fieldset>
                                        <legend>Section Name</legend>
                                        <input type="text" name="new-section-name">
                                    </fieldset>
                                    <fieldset>
                                        <legend>Section Adviser</legend>
                                        <select name="new-section-adviser" >
                                            <option value="">Select Available Teachers - Teachers that doesn't have any advisory class yet.</option>
                                            <?php optionOfAvailableTeachers(); ?>
                                        </select>
                                    </fieldset>
                                </div>
                                <?php
                            }

                            ?>
                        </form>
                    </div>

                    <div class="sec-list">
                        <div class="tube">

                            <table>
                                <tbody>
                                    <tr>
                                        <th>Section Name</th>
                                        <th>Section Adviser</th>
                                        <th>Action</th>
                                    </tr>
                                    <?php listAllSection(); ?>
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

    <?php

    if (isset($_GET['control']) && $_GET['control'] == 'teachers') {
        ?>
        <section id="panel">
            <div class="container">
                <div class="wrapper">

                    <div class="top">
                        <h1>Manage Teachers</h1>
                        <?php 
                        
                        if (isset($_GET['class']) && $_GET['class'] == 'active') {
                            ?>
                            <br>
                            <a class="deactivatedteacherbtn" href="?control=teachers&class=inactive" type="submit">Show All Deactivated Teachers</a>
                            <?php
                        } else if (isset($_GET['class']) && $_GET['class'] == 'inactive') {
                            ?>
                            <br>
                            <a class="activeteacherbtn" href="?control=teachers&class=active" type="submit">Show All Active Teachers</a>
                            <?php
                        } else {
                            ?>
                            <br>
                            <a class="deactivatedteacherbtn" href="?control=teachers&class=inactive" type="submit">Show All Deactivated Teachers</a>
                            <?php
                        }
                        
                        ?>
                    </div>
                    
                    <div class="sec-creator">
                        <form method="post">
                            <?php
                            if (!isset($_POST['create-teacher'])) {
                                ?>
                                <button type="submit" name="create-teacher">Create Teacher</button>
                                <?php
                            } else {
                                ?>
                                <button type="submit" name="save-teacher">Save Teacher</button>
                                <?php
                            }
                            ?>
                            
                            <?php

                            if (isset($_POST['create-teacher'])) {
                                ?>
                                <div class="input-group">
                                    <fieldset>
                                        <legend>Teacher Name</legend>
                                        <input type="text" name="teacher-name">
                                    </fieldset>
                                </div>
                                <?php
                            }

                            ?>
                        </form>
                    </div>

                    <div class="sec-list">
                        <div class="tube">
                            <table>
                                <tbody>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Action</th>
                                    </tr>
                                    <!-- TODO -->
                                    <?php 
                                    
                                    if (isset($_GET['class']) && $_GET['class'] == 'active') {
                                        ?> <h3>List of Active Teachers</h3> <?php
                                        listAllActiveTeachers();
                                    } else if (isset($_GET['class']) && $_GET['class'] == 'inactive') {
                                        ?> <h3>List of Inactive Teachers</h3> <?php
                                        listAllInactiveTeachers();
                                    }
                                    else {
                                        ?> <h3>List of Active Teachers</h3> <?php
                                        listAllActiveTeachers();
                                    }
                                    
                                    ?>
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </section>
        <?php
    }

    if (isset($_GET['control']) && $_GET['control'] == 'students') {
        ?>
        <section id="panel">
            <div class="container">
                <div class="wrapper">

                    <div class="top">
                        <h1>Manage Students</h1>
                    </div>

                    <?php 
                        
                        if (isset($_GET['sclass']) && $_GET['sclass'] == 'active') {
                            ?>
                            <br>
                            <a class="deactivatedstudentbtn" href="?control=students&sclass=inactive" type="submit">Show All Deactivated Students</a>
                            <?php
                        } else if (isset($_GET['sclass']) && $_GET['sclass'] == 'inactive') {
                            ?>
                            <br>
                            <a class="activestudentbtn" href="?control=students&sclass=active" type="submit">Show All Active Students</a>
                            <?php
                        } else {
                            ?>
                            <br>
                            <a class="deactivatedstudentbtn" href="?control=students&sclass=inactive" type="submit">Show All Deactivated Students</a>
                            <?php
                        }
                        
                        ?>
                    
                    <div class="sec-creator">
                        <form method="post">
                            <?php
                            if (!isset($_POST['add-student'])) {
                                ?>
                                <button type="submit" name="add-student">Add Student</button>
                                <?php
                            } else {
                                ?>
                                <button type="submit" name="save-add-student">Save Student</button>
                                <?php
                            }
                            ?>
                            
                            <?php

                            if (isset($_POST['add-student'])) {
                                ?>
                                <div class="input-group">
                                    <fieldset>
                                        <legend>Student Full Name</legend>
                                        <input type="text" name="add-student-name">
                                    </fieldset>
                                    <fieldset>
                                        <legend>Gender</legend>
                                        <input type="text" name="add-student-gender">
                                    </fieldset>
                                    <fieldset>
                                        <legend>Address</legend>
                                        <input type="text" name="add-student-address">
                                    </fieldset>
                                    <fieldset>
                                        <legend>Student's Section</legend>
                                        <select name="add-student-section">
                                            <option value="">Select Section</option>
                                            <?php optionOfSections(); ?>
                                        </select>
                                    </fieldset>
                                </div>
                                <?php
                            }

                            ?>
                        </form>
                    </div>

                    <div class="fac-stud-list">
                        <div class="tube">

                            <table>
                                <tbody>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Address</th>
                                        <th>Action</th>
                                    </tr>
                                    <?php 
                                        if (!isset($_GET['sclass']) || isset($_GET['sclass']) && $_GET['sclass'] == 'active')  {
                                            listStudents();
                                        } else {
                                            listDeactivatedStudents();
                                        }
                                    ?>
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
                                    
                                    <?php getOverAllAttendance(); ?>
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