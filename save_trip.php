<?php
session_start();
include 'Database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    $user_id = $_SESSION['user_id'];
    $pickup_id = $_POST['pickup_id'];
    $dropoff_id = $_POST['dropoff_id'];
    $fare = $_POST['fare'];

    $stmt = $conn->prepare("INSERT INTO user_trips (user_id, pickup_stop_id, dropoff_stop_id, fare_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $user_id, $pickup_id, $dropoff_id, $fare);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Trip saved']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}
?>