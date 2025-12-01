<?php
// This is the PARENT class
class BaseModel {
    protected $conn; // 'protected' means children can access it

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
}
?>