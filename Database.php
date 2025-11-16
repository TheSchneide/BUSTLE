<?php
class Database {
    private $host = "localhost";
    private $user = "s24100966_bustle";
    private $pass = "Ciscocisco1";
    private $dbname = "s24100966_bustle";

    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>