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
        // Cloud MySQL providers (TiDB Cloud, Aiven, ...) reject unencrypted
        // connections. Opt in with DB_SSL=1; local MAMP leaves it unset and
        // keeps the plain connection it has always used.
        if (config('DB_SSL') === '1') {
            $this->conn = mysqli_init();
            $this->conn->ssl_set(null, null, '/etc/ssl/certs/ca-certificates.crt', null, null);
            $this->conn->real_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                (int)$this->port,
                null,
                MYSQLI_CLIENT_SSL
            );
        } else {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );
        }

        if ($this->conn->connect_error) {
            error_log('Database connection failed: ' . $this->conn->connect_error);
            die('Database connection failed. Please try again later.');
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
