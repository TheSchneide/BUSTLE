<?php
class Analytics {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTopSavedRoutes() {
        $sql = "SELECT 
                    p.stop_name as pickup, 
                    d.stop_name as dropoff, 
                    COUNT(*) as count 
                FROM saved_routes s
                JOIN bus_stops p ON s.pickup_stop_id = p.stop_id
                JOIN bus_stops d ON s.dropoff_stop_id = d.stop_id
                GROUP BY s.pickup_stop_id, s.dropoff_stop_id
                ORDER BY count DESC
                LIMIT 5";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    public function getFrequentTrips() {
        $sql = "SELECT 
                    p.stop_name as pickup, 
                    d.stop_name as dropoff, 
                    COUNT(*) as count 
                FROM user_trips t
                JOIN bus_stops p ON t.pickup_stop_id = p.stop_id
                JOIN bus_stops d ON t.dropoff_stop_id = d.stop_id
                GROUP BY t.pickup_stop_id, t.dropoff_stop_id
                ORDER BY count DESC
                LIMIT 5";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserDemographics() {
        $sql = "SELECT birthdate FROM users";
        $result = $this->conn->query($sql);

        $stats = [
            'Student (< 22)' => 0,
            'Adult (22-59)' => 0,
            'Senior (60+)' => 0
        ];

        while ($row = $result->fetch_assoc()) {
            if (!$row['birthdate']) continue;
            
            $dob = new DateTime($row['birthdate']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;

            if ($age < 22) {
                $stats['Student (< 22)']++;
            } else if ($age >= 60) {
                $stats['Senior (60+)']++;
            } else {
                $stats['Adult (22-59)']++;
            }
        }
        return $stats;
    }

    public function getTotalUsers() {
        $res = $this->conn->query("SELECT COUNT(*) as c FROM users");
        return $res->fetch_assoc()['c'];
    }
}
?>