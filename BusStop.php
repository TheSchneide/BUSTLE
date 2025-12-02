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


    public function deleteStop($id) {

        $this->conn->begin_transaction();

        try {

            $stmt1 = $this->conn->prepare("DELETE FROM saved_routes WHERE pickup_stop_id = ? OR dropoff_stop_id = ?");
            $stmt1->bind_param("ii", $id, $id);
            $stmt1->execute();
            $stmt1->close();


            $stmt2 = $this->conn->prepare("DELETE FROM user_trips WHERE pickup_stop_id = ? OR dropoff_stop_id = ?");
            $stmt2->bind_param("ii", $id, $id);
            $stmt2->execute();
            $stmt2->close();


            $stmt3 = $this->conn->prepare("DELETE FROM bus_stops WHERE stop_id = ?");
            $stmt3->bind_param("i", $id);
            $success = $stmt3->execute();
            $stmt3->close();

            if ($success) {

                $this->conn->commit();
                return true;
            } else {

                $this->conn->rollback();
                return false;
            }
        } catch (Exception $e) {

            $this->conn->rollback();
            return false;
        }
    }
}
?>