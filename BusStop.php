<?php
class BusStop {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $sql = "SELECT stop_id, stop_name, latitude, longitude FROM bus_stops";
        $result = $this->conn->query($sql);
        $stops = [];
        while ($row = $result->fetch_assoc()) {
            $stops[] = $row;
        }
        return $stops;
    }

    public function getName($id) {
        $stmt = $this->conn->prepare("SELECT stop_name FROM bus_stops WHERE stop_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        return $data ? $data['stop_name'] : "Unknown";
    }

    // --- NEW: Admin Functions ---
    public function addStop($name, $lat, $lng) {
        $stmt = $this->conn->prepare("INSERT INTO bus_stops (stop_name, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $name, $lat, $lng);
        return $stmt->execute();
    }

    public function updateStop($id, $name, $lat, $lng) {
        $stmt = $this->conn->prepare("UPDATE bus_stops SET stop_name = ?, latitude = ?, longitude = ? WHERE stop_id = ?");
        $stmt->bind_param("sddi", $name, $lat, $lng, $id);
        return $stmt->execute();
    }
}
?>