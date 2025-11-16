<?php
include 'Database.php';
include 'Ride.php';
session_start();

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must log in first!';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $ride = new Ride($db->getConnection());

    $response = $ride->record(
        $_SESSION['user_id'],
        $_POST['plate_number'],
        $_POST['destination']
    );
}

echo json_encode($response);
?>