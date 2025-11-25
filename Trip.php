<?php
class Trip {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Records a new trip (Used in save_trip.php)
    public function create($user_id, $pickup_id, $dropoff_id, $fare) {
        $stmt = $this->conn->prepare("INSERT INTO user_trips (user_id, pickup_stop_id, dropoff_stop_id, fare_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $pickup_id, $dropoff_id, $fare);
        return $stmt->execute();
    }

    // Fetches the most recent trip (Used in index.php)
    public function getMostRecent($user_id) {
        $sql = "SELECT 
                    t.fare_amount, 
                    p.stop_name AS pickup_name, 
                    d.stop_name AS dropoff_name
                FROM user_trips t
                JOIN bus_stops p ON t.pickup_stop_id = p.stop_id
                JOIN bus_stops d ON t.dropoff_stop_id = d.stop_id
                WHERE t.user_id = ? 
                ORDER BY t.trip_id DESC 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc(); // Returns the array or null
    }
}
?>