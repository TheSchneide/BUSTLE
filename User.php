<?php
class User {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // UPDATED: Now accepts $birthdate
    public function register($username, $email, $password, $birthdate) {
        $response = ['status' => '', 'message' => ''];
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email exists
        $checkEmail = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Email already registered!'];
        }

        // Check if username exists
        $checkUsername = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $checkUsername->bind_param("s", $username);
        $checkUsername->execute();
        if ($checkUsername->get_result()->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Username already taken!'];
        }

        // INSERT including Birthdate
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, birthdate) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $birthdate);
        
        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Registration successful!'];
        } else {
            return ['status' => 'error', 'message' => 'Registration failed.'];
        }
    }

    public function login($username, $password) {
        // ... (Keep your existing login code, we will handle the age calculation in login.php) ...
        // Copy your existing login function here exactly as it was.
        $response = ['status' => 'error', 'message' => 'Login failed.'];
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $response['status'] = 'success';
                $response['message'] = 'Login successful!';
                $response['user'] = $user; 
            } else {
                $response['message'] = 'Invalid password!';
            }
        } else {
            $response['message'] = 'User not found!';
        }
        return $response;
    }
}
?>