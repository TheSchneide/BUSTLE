<?php
session_start();
include 'Database.php';
include 'Trip.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $trip = new Trip($db->getConnection());

    $user_id = $_SESSION['user_id'];
    $pickup_id = $_POST['pickup_id'];
    $dropoff_id = $_POST['dropoff_id'];
    $fare = $_POST['fare'];

    if ($trip->save($user_id, $pickup_id, $dropoff_id, $fare)) {
        echo json_encode(['status' => 'success', 'message' => 'Trip history updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}
?>