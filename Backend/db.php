<?php

class Database
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "enrollment_db_finish";

    public $conn;

    public function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }

        // Set character encoding
        $this->conn->set_charset("utf8mb4");

        return $this->conn;
    }

    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

?>