<?php
 
$host     = "localhost";
$username = "root";
$password = "root";
$database = "enrollment_db";
$port     = 8889;
 
//pre palitan mo na lang ung host to port naka mamp kasi ako, xampp sainyo eh diba
$conn = new mysqli($host, $username, $password, $database, $port);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . " (errno: " . $conn->connect_errno . ")");
}
 