<?php
include_once 'BaseModel.php';

class Logger extends BaseModel {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function log($action, $description) {
        $adminName = $_SESSION['username'] ?? 'Admin';
        
        $stmt = $this->conn->prepare("INSERT INTO admin_logs (action_type, description, admin_username) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $action, $description, $adminName);
        return $stmt->execute();
    }

    public function getLogs() {
        $sql = "SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 50";
        $result = $this->conn->query($sql);
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }
}
?>