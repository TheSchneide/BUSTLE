<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must log in first!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plate_number = $_POST['plate_number'];
    $destination = $_POST['destination'];

    $bus_query = $conn->prepare("SELECT bus_id FROM buses WHERE plate_number = ?");
    $bus_query->bind_param("s", $plate_number);
    $bus_query->execute();
    $bus_result = $bus_query->get_result();

    if ($bus_result->num_rows > 0) {
        $bus = $bus_result->fetch_assoc();
        $bus_id = $bus['bus_id'];

        $stmt = $conn->prepare("INSERT INTO rides (user_id, bus_id, destination) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $_SESSION['user_id'], $bus_id, $destination);
        if ($stmt->execute()) {
            echo "Ride recorded successfully.";
        } else {
            echo "Error recording ride.";
        }
    } else {
        echo "Bus not found.";
    }
}
?>
