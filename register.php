<?php
include 'db_connect.php';

header('Content-Type: application/json');

$response = ['status' => '', 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check email
    $checkEmail = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $emailResult = $checkEmail->get_result();

    // Check username
    $checkUsername = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $userResult = $checkUsername->get_result();

    if ($emailResult->num_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = 'Email already registered!';
    } elseif ($userResult->num_rows > 0) {
        $response['status'] = 'error';
        $response['message'] = 'Username already taken!';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Registration successful!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Registration failed. Try again.';
        }
    }
}

echo json_encode($response);
