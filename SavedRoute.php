<?php
// Load required dependencies
include_once 'BaseModel.php';
include_once 'Savable.php';

class SavedRoute extends BaseModel implements Savable {
    
    // Matches the Savable Interface signature
    public function save($user_id, $pickup_id, $dropoff_id, $fare = null) {
        $sql = "INSERT INTO saved_routes (user_id, pickup_stop_id, dropoff_stop_id) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE pickup_stop_id=?, dropoff_stop_id=?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiii", $user_id, $pickup_id, $dropoff_id, $pickup_id, $dropoff_id);
        return $stmt->execute();
    }

    public function remove($user_id) {
         $sql = "DELETE FROM saved_routes WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }

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
        
        return $result->fetch_assoc();
    }
}
?>