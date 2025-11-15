<?php
include 'Database.php';
include 'Bus.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Create objects
    $db = new Database();
    $bus = new Bus($db->getConnection());

    // 2. Call the method
    $response = $bus->updateLocation(
        $_POST['plate_number'],
        $_POST['latitude'],
        $_POST['longitude']
    );
}

// 3. Echo the JSON response
echo json_encode($response);
?>