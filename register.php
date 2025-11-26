<?php
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $user = new User($db->getConnection());

    $response = $user->register(
        trim($_POST['username']),
        trim($_POST['email']),
        $_POST['password'],
        $_POST['birthdate'] // NEW INPUT
    );
    
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>