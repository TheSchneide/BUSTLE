<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plate_number = $_POST['plate_number'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $bus_query = $conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
    $bus_query->bind_param("s", $plate_number);
    $bus_query->execute();
    $bus_result = $bus_query->get_result();

    if ($bus_result->num_rows > 0) {
        $bus = $bus_result->fetch_assoc();
        $bus_id = $bus['bus_id'];

        $stmt = $conn->prepare("INSERT INTO bus_locations (bus_id, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->bind_param("idd", $bus_id, $latitude, $longitude);
        if ($stmt->execute()) {
            echo "Location updated.";
        } else {
            echo "Error updating location.";
        }
    } else {
        echo "Bus not found.";
    }
}
?>
