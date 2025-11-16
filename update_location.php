<?php
include 'Database.php';
include 'Bus.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $bus = new Bus($db->getConnection());

    $response = $bus->updateLocation(
        $_POST['plate_number'],
        $_POST['latitude'],
        $_POST['longitude']
    );
}

echo json_encode($response);
?>