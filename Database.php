<?php
class Database {
    private $host = "localhost";
    private $user = "s24100966_bustle";
    private $pass = "Ciscocisco1";
    private $dbname = "s24100966_bustle";

    private $conn;

    // The "constructor" runs when you create a new Database object
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // A method to get the connection for other classes to use
    public function getConnection() {
        return $this->conn;
    }
}
?>