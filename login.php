<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

$response = ['status'=>'', 'message'=>''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // Get username from form
    $password = $_POST['password'];

    // Prepare query using correct variable
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // bind $username, not $email
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $response['status'] = 'success';
            $response['message'] = 'Login successful!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Invalid password!';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'User not found!';
    }
}

echo json_encode($response);
