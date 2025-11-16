<?php
session_start();
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');
$response = ['status'=>'error', 'message'=>'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $user = new User($db->getConnection());

    $loginResult = $user->login(
        trim($_POST['username']),
        $_POST['password']
    );

    if ($loginResult['status'] === 'success') {
        $userData = $loginResult['user'];
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];
    }
    
    $response['status'] = $loginResult['status'];
    $response['message'] = $loginResult['message'];
}

echo json_encode($response);
?>