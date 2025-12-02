<?php
session_start();
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');
$response = ['status'=>'error', 'message'=>'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];


    if ($username === 'bustleAdmin' && $password === '321') {
        $_SESSION['user_id'] = 999999; 
        $_SESSION['username'] = 'Admin';
        $_SESSION['is_admin'] = true; 
        
        $response['status'] = 'success';
        $response['message'] = 'Welcome, Admin!';
        echo json_encode($response);
        exit();
    }

    $db = new Database();
    $user = new User($db->getConnection());

    $loginResult = $user->login($username, $password);

    if ($loginResult['status'] === 'success') {
        $userData = $loginResult['user'];
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email']; 
        $_SESSION['birthdate'] = $userData['birthdate']; 
        $_SESSION['is_admin'] = false;

        $birthDate = new DateTime($userData['birthdate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        if ($age < 22 || $age >= 60) {
            $_SESSION['is_discounted'] = true;
            $_SESSION['discount_type'] = ($age >= 60) ? "Senior Citizen ($age)" : "Student ($age)";
        } else {
            $_SESSION['is_discounted'] = false;
            $_SESSION['discount_type'] = "Regular";
        }
    }
    
    $response['status'] = $loginResult['status'];
    $response['message'] = $loginResult['message'];
}

echo json_encode($response);
?>