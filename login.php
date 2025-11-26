<?php
session_start();
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');
$response = ['status'=>'error', 'message'=>'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $user = new User($db->getConnection());

    $loginResult = $user->login(trim($_POST['username']), $_POST['password']);

    if ($loginResult['status'] === 'success') {
        $userData = $loginResult['user'];
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];

        // --- AGE CALCULATION LOGIC ---
        $birthDate = new DateTime($userData['birthdate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        // Define logic: Student (Under 22) OR Senior (60+)
        // You can adjust the age 22 to whatever limit you prefer for students
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