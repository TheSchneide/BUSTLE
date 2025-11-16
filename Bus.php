<?php
class Bus {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function updateLocation($plateNumber, $latitude, $longitude) {
        $bus_query = $this->conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
        $bus_query->bind_param("s", $plateNumber);
        $bus_query->execute();
        $bus_result = $bus_query->get_result();

        if ($bus_result->num_rows > 0) {
            $bus = $bus_result->fetch_assoc();
            $bus_id = $bus['bus_id'];

            $stmt = $this->conn->prepare("INSERT INTO bus_locations (bus_id, latitude, longitude) VALUES (?, ?, ?)");
            $stmt->bind_param("idd", $bus_id, $latitude, $longitude);
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Location updated.'];
            } else {
                return ['status' => 'error', 'message' => 'Error updating location.'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Bus not found.'];
        }
    }
    public function getAllActiveBuses() {
        $sql = "
            SELECT b.bus_id, b.plate_number, b.route,
                   l.latitude, l.longitude, l.timestamp
            FROM buses b
            LEFT JOIN (
                SELECT bus_id, latitude, longitude, timestamp
                FROM bus_locations
                WHERE (bus_id, timestamp) IN (
                    SELECT bus_id, MAX(timestamp)
                    FROM bus_locations
                    GROUP BY bus_id
                )
            ) l ON b.bus_id = l.bus_id
            WHERE b.status = 'active'
        ";
        
        $result = $this->conn->query($sql);
        $buses = [];
        while ($row = $result->fetch_assoc()) {
            $buses[] = $row;
        }
        return $buses;
    }
}
?>