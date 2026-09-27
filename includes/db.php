<?php
/**
 * db.php — Database connection
 * Update these values for your environment.
 */
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "exam_system";

date_default_timezone_set('Asia/Kolkata');

mysqli_report(MYSQLI_REPORT_OFF); // we handle errors manually below

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
