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
     $trip_id = $conn->insert_id;
        $stmt2 = $conn->prepare("
        SELECT 
            t.trip_id,
            t.pickup_stop_id,
            bs1.stop_name AS pickup_name,
            t.dropoff_stop_id,
            bs2.stop_name AS dropoff_name,
            t.fare_amount
        FROM user_trips t
        JOIN bus_stops bs1 ON t.pickup_stop_id = bs1.stop_id
        JOIN bus_stops bs2 ON t.dropoff_stop_id = bs2.stop_id
        WHERE t.trip_id = ?
    ");

    $stmt2->bind_param("i", $trip_id);
    $stmt2->execute();
    $trip = $stmt2->get_result()->fetch_assoc();

    // Save the names into session
    $_SESSION['recent_trip'] = [
        'pickup_name' => $trip['pickup_name'],
        'dropoff_name' => $trip['dropoff_name'],
        'fare' => $trip['fare_amount']
    ];
    echo json_encode(['status' => 'success', 'message' => 'Trip saved']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

}

?>