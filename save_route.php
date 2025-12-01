<?php
session_start();
include 'Database.php';
include 'SavedRoute.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $routeManager = new SavedRoute($db->getConnection());
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action']; 
    $pickup = $_POST['pickup_id'];
    $dropoff = $_POST['dropoff_id'];

    $success = false;

    if ($action === 'save') {
        $success = $routeManager->save($user_id, $pickup, $dropoff);
    } else {
        // Now passing specific IDs to remove only that route
        $success = $routeManager->remove($user_id, $pickup, $dropoff);
    }

    if ($success) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>