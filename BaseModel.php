<?php
class BaseModel {
    protected $conn; 

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
}
?>