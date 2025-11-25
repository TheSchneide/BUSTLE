<?php
class SavedRoute {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Save (or Update) a route
    public function save($user_id, $pickup_id, $dropoff_id) {
        // The logic: Insert, but if user already has a saved route, update it
        $sql = "INSERT INTO saved_routes (user_id, pickup_stop_id, dropoff_stop_id) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE pickup_stop_id=?, dropoff_stop_id=?";
        
        $stmt = $this->conn->prepare($sql);
        // Bind params: user, pick, drop, pick, drop
        $stmt->bind_param("iiiii", $user_id, $pickup_id, $dropoff_id, $pickup_id, $dropoff_id);
        
        return $stmt->execute();
    }

    // Remove a saved route
    public function remove($user_id) {
        $sql = "DELETE FROM saved_routes WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }

    // Get the saved route details (Join with bus_stops to get names)
    public function get($user_id) {
        $sql = "SELECT 
                    s.pickup_stop_id, s.dropoff_stop_id,
                    p.stop_name AS pickup_name, 
                    d.stop_name AS dropoff_name
                 FROM saved_routes s
                 JOIN bus_stops p ON s.pickup_stop_id = p.stop_id
                 JOIN bus_stops d ON s.dropoff_stop_id = d.stop_id
                 WHERE s.user_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc(); // Returns array or null
    }
}
?>