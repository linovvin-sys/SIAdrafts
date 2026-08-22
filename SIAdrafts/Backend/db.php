<?php

require_once __DIR__ . '/config.php';

// Thrown for raw mysqli/driver failures whose message may contain schema
// details. Kept distinct from RuntimeException/Exception so endpoints can
// catch it separately and return a generic message instead of echoing
// $conn->error to the client.
class DbError extends RuntimeException
{
}

class Database
{
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private string $port;

    public $conn;

    public function __construct()
    {
        $this->host     = config('DB_HOST') ?? 'localhost';
        $this->username = config('DB_USERNAME') ?? 'root';
        $this->password = config('DB_PASSWORD') ?? 'root';
        $this->database = config('DB_DATABASE') ?? 'sia_project';
        $this->port     = config('DB_PORT') ?? '8889';
    }

    public function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if ($this->conn->connect_error) {
            die("Database Connection Failed: " . $this->conn->connect_error);
        }

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
