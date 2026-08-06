<?php

function get_db_connection() {
    $host = 'localhost';
    $port = '8889';
    $username = 'root';
    $password = 'root';
    $database = 'vue_todo_db';

    $conn = new mysqli($host, $username, $password, $database, $port);

    if ($conn->connect_error) {
        die('Database Connection Failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
