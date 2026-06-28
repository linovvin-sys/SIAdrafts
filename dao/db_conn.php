<?php 

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "enrollment_db";
$port       = 4306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);


if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>