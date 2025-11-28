<?php
session_start();
require_once 'config/database.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validate input
    $errors = [];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if (empty($password)) {
        $errors[] = "Please enter your password";
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: login.php');
        exit();
    }
    
    try {
        // Prepare and execute query - check for any active user with this email
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['is_admin'] ? 'admin' : 'customer';
                $_SESSION['user_name'] = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();
                
                // Set remember me cookie if checked
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    setcookie('remember_token', $token, [
                        'expires' => $expires,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    
                    // Store token in database (simple version without remember_token column)
                    // For now, just set a session flag
                    $_SESSION['remember_me'] = true;
                }
                
                // Redirect based on user role
                if ($user['is_admin']) {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                // Invalid password
                throw new Exception("Invalid email or password");
            }
        } else {
            // No user found with that email
            throw new Exception("Invalid email or password");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: login.php');
        exit();
    }
} else {
    // If someone tries to access this file directly
    header('Location: login.php');
    exit();
}
?>
