<?php
class BusStop {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all stops for the map/dropdowns
    public function getAll() {
        $sql = "SELECT stop_id, stop_name, latitude, longitude FROM bus_stops";
        $result = $this->conn->query($sql);
        
        $stops = [];
        while ($row = $result->fetch_assoc()) {
            $stops[] = $row;
        }
        return $stops;
    }

    // Get a single stop's name by ID (Optional helper)
    public function getName($id) {
        $stmt = $this->conn->prepare("SELECT stop_name FROM bus_stops WHERE stop_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        return $data ? $data['stop_name'] : "Unknown";
    }
}
?>