<?php
class User {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function register($username, $email, $password, $birthdate) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check = $this->conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Username or Email already exists.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, birthdate) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $birthdate);
        
        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Registration successful!'];
        } else {
            return ['status' => 'error', 'message' => 'Registration failed.'];
        }
    }

    public function login($username, $password) {
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

    public function updateProfile($userId, $confirmEmail, $confirmPass, $newUsername, $newPassword = null) {
        $stmt = $this->conn->prepare("SELECT password, email FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $currentUser = $res->fetch_assoc();

        if (!$currentUser || $currentUser['email'] !== $confirmEmail || !password_verify($confirmPass, $currentUser['password'])) {
            return ['status' => 'error', 'message' => 'Verification failed. Incorrect Email or Password.'];
        }

        if (!empty($newPassword)) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $this->conn->prepare("UPDATE users SET username = ?, password = ? WHERE user_id = ?");
            $update->bind_param("ssi", $newUsername, $newHash, $userId);
        } else {
            $update = $this->conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
            $update->bind_param("si", $newUsername, $userId);
        }

        if ($update->execute()) {
            return ['status' => 'success', 'message' => 'Profile updated successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Update failed. Username might be taken.'];
        }
    }

    public function getAllUsers() {
        $result = $this->conn->query("SELECT user_id, username, email, birthdate FROM users");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
}
?>