<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to index after successful login
            header("Location: index.html");
            exit();
        } else {
            // Redirect back to login page with error
            header("Location: login.php?error=invalid_password");
            exit();
        }
    } else {
        // Redirect back to login page with error
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>
