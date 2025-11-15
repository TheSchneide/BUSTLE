<?php
class Ride {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Records a new ride for a user.
     * Logic from old record_ride.php
     * @return array A response array
     */
    public function record($userId, $plateNumber, $destination) {
        // 1. Find bus
        $bus_query = $this->conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
        $bus_query->bind_param("s", $plateNumber);
        $bus_query->execute();
        $bus_result = $bus_query->get_result();

        if ($bus_result->num_rows > 0) {
            $bus = $bus_result->fetch_assoc();
            $bus_id = $bus['bus_id'];

            // 2. Insert ride
            $stmt = $this->conn->prepare("INSERT INTO rides (user_id, bus_id, destination) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userId, $bus_id, $destination);
            
            if ($stmt->execute()) {
                return ['status' => 'success', 'message' => 'Ride recorded successfully.'];
            } else {
                return ['status' => 'error', 'message' => 'Error recording ride.'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Bus not found.'];
        }
    }
}
?>