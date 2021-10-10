<?php

function connect() {
    $con = new mysqli('localhost', 
    'root', 
    '', 
    'attendance_monitoring_system');

    return $con;
}

?>