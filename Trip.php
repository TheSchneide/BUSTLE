<?php
// Load required dependencies
include_once 'BaseModel.php';
include_once 'Savable.php'; 

class Trip extends BaseModel implements Savable {
    
    // Matches the Savable Interface signature
    public function save($user_id, $pickup_id, $dropoff_id, $fare = null) {
        // Logic: Insert new, or Update if user_id already exists
        $sql = "INSERT INTO user_trips (user_id, pickup_stop_id, dropoff_stop_id, fare_amount, date_created) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) 
                ON DUPLICATE KEY UPDATE 
                pickup_stop_id = VALUES(pickup_stop_id), 
                dropoff_stop_id = VALUES(dropoff_stop_id), 
                fare_amount = VALUES(fare_amount),
                date_created = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiid", $user_id, $pickup_id, $dropoff_id, $fare);
        return $stmt->execute();
    }

    public function getMostRecent($user_id) {
        $sql = "SELECT 
                t.fare_amount, 
                p.stop_name AS pickup_name, 
                d.stop_name AS dropoff_name
            FROM user_trips t
            JOIN bus_stops p ON t.pickup_stop_id = p.stop_id
            JOIN bus_stops d ON t.dropoff_stop_id = d.stop_id
            WHERE t.user_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc(); 
    }
}
?>