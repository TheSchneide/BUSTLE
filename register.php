<?php
// Include the new classes
include 'Database.php';
include 'User.php';

header('Content-Type: application/json');

// Only proceed if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Create database connection
    $db = new Database();
    
    // 2. Create a User object, passing in the connection
    $user = new User($db->getConnection());

    // 3. Call the register method
    $response = $user->register(
        trim($_POST['username']),
        trim($_POST['email']),
        $_POST['password']
    );
    
    // 4. Echo the response from the method
    echo json_encode($response);
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>