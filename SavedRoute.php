<?php
include_once 'BaseModel.php';
include_once 'Savable.php';

class SavedRoute extends BaseModel implements Savable {
    
    public function save($user_id, $pickup_id, $dropoff_id, $fare = null) {
        $sql = "INSERT IGNORE INTO saved_routes (user_id, pickup_stop_id, dropoff_stop_id) 
                VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $pickup_id, $dropoff_id);
        return $stmt->execute();
    }

    public function remove($user_id, $pickup_id = null, $dropoff_id = null) {
        $sql = "DELETE FROM saved_routes WHERE user_id = ? AND pickup_stop_id = ? AND dropoff_stop_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $pickup_id, $dropoff_id);
        return $stmt->execute();
    }

    public function getAll($user_id) {
        $sql = "SELECT 
                s.pickup_stop_id, s.dropoff_stop_id,
                p.stop_name AS pickup_name, 
                d.stop_name AS dropoff_name
             FROM saved_routes s
             JOIN bus_stops p ON s.pickup_stop_id = p.stop_id
             JOIN bus_stops d ON s.dropoff_stop_id = d.stop_id
             WHERE s.user_id = ?
             ORDER BY s.save_id DESC"; 
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $routes[] = $row;
        }
        return $routes;
    }
}
?>