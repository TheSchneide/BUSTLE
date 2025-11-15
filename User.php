<?php
class User {
    // Property to hold the database connection
    private $conn;

    // The constructor now requires a database connection to be passed in
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Registers a new user.
     * Contains all the logic from your old register.php
     * @return array A response array ['status' => '', 'message' => '']
     */
    public function register($username, $email, $password) {
        $response = ['status' => '', 'message' => ''];
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check email
        $checkEmail = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();

        // Check username
        $checkUsername = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
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
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Registration successful!';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Registration failed. Try again.';
            }
        }
        
        return $response;
    }

    /**
     * Logs a user in.
     * Contains all the logic from your old login.php
     * @return array A response array ['status' => '', 'message' => '', 'user' => null]
     */
    public function login($username, $password) {
        $response = ['status' => 'error', 'message' => 'Login failed.'];

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Success! Return user data
                $response['status'] = 'success';
                $response['message'] = 'Login successful!';
                $response['user'] = $user; // Send user data back
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