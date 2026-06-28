<?php

$host = "localhost";
$username = "root";
$password = "root";
$database = "enrollment_db";
$port = 8889;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


?>