<?php
class Bus {
    private $conn;

    // The constructor requires a database connection
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Updates the location of a bus.
     * Logic from old update_location.php
     * @return array A response array
     */
    public function updateLocation($plateNumber, $latitude, $longitude) {
        // 1. Find bus
        $bus_query = $this->conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
        $bus_query->bind_param("s", $plateNumber);
        $bus_query->execute();
        $bus_result = $bus_query->get_result();

        if ($bus_result->num_rows > 0) {
            $bus = $bus_result->fetch_assoc();
            $bus_id = $bus['bus_id'];

            // 2. Insert location
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

    /**
     * Gets all active buses with their latest location.
     * Logic from old get_buses.php
     * @return array An array of bus data
     */
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