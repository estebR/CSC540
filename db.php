<?php
$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP MySQL password
$dbname = "tutoring_system";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
