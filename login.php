<?php
session_start();
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');
$response = ['status'=>'error', 'message'=>'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Create objects
    $db = new Database();
    $user = new User($db->getConnection());

    // 2. Call the login method
    $loginResult = $user->login(
        trim($_POST['username']),
        $_POST['password']
    );

    // 3. If login was a success, set the session
    if ($loginResult['status'] === 'success') {
        $userData = $loginResult['user'];
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];
    }
    
    // 4. Echo the JSON response
    $response['status'] = $loginResult['status'];
    $response['message'] = $loginResult['message'];
}

echo json_encode($response);
?>