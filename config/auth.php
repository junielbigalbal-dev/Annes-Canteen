<?php
/**
 * Authentication Helper Functions
 * Handles remember me functionality and session management
 */

require_once 'database.php';

/**
 * Check if user is remembered via cookie and auto-login
 * @return bool True if user was successfully logged in via remember me
 */
function checkRememberMe() {
    if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
        global $conn;
        
        $token = $_COOKIE['remember_token'];
        
        try {
            // Find user with valid remember token
            $query = "SELECT * FROM users WHERE remember_token IS NOT NULL AND remember_token_expires > NOW()";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($user = $result->fetch_assoc()) {
                    // Verify the token
                    if (password_verify($token, $user['remember_token'])) {
                        // Token is valid, log in the user
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['is_admin'] ? 'admin' : 'customer';
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
                        $_SESSION['is_admin'] = $user['is_admin'];
                        
                        // Update last login time
                        $updateStmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
                        $updateStmt->bind_param("i", $user['user_id']);
                        $updateStmt->execute();
                        
                        // Generate new remember token for security
                        generateRememberToken($user['user_id']);
                        
                        return true;
                    }
                }
            }
            
            // If we reach here, token is invalid, clear it
            clearRememberToken();
            
        } catch (Exception $e) {
            // Log error and clear token
            error_log("Remember me error: " . $e->getMessage());
            clearRememberToken();
        }
    }
    
    return false;
}

/**
 * Generate and set remember me token for user
 * @param int $userId User ID
 * @return bool Success status
 */
function generateRememberToken($userId) {
    global $conn;
    
    try {
        // Generate secure random token
        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        $expiresDateTime = date('Y-m-d H:i:s', $expires);
        
        // Update user record with new token
        $query = "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $hashedToken, $expiresDateTime, $userId);
        
        if ($stmt->execute()) {
            // Set cookie
            setcookie('remember_token', $token, [
                'expires' => $expires,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            return true;
        }
        
    } catch (Exception $e) {
        error_log("Generate remember token error: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Clear remember me token and cookie
 * @return void
 */
function clearRememberToken() {
    global $conn;
    
    // Clear cookie
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Clear from database if user is logged in
    if (isset($_SESSION['user_id'])) {
        try {
            $query = "UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Clear remember token error: " . $e->getMessage());
        }
    }
}

/**
 * Check if user is logged in (either via session or remember me)
 * @return bool True if user is authenticated
 */
function isLoggedIn() {
    // Check session first
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check remember me
    return checkRememberMe();
}

/**
 * Get current user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        global $conn;
        
        try {
            $query = "SELECT user_id, username, email, first_name, last_name, phone, address, is_admin, is_active FROM users WHERE user_id = ? AND is_active = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
        }
    }
    
    return null;
}

/**
 * Logout user and clear all authentication data
 * @return void
 */
function logout() {
    // Clear remember me token
    clearRememberToken();
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

/**
 * Clean up expired remember tokens
 * Should be called periodically (e.g., via cron job)
 * @return int Number of tokens cleaned up
 */
function cleanupExpiredTokens() {
    global $conn;
    
    try {
        $query = "UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE remember_token_expires < NOW()";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        return $stmt->affected_rows;
    } catch (Exception $e) {
        error_log("Cleanup expired tokens error: " . $e->getMessage());
        return 0;
    }
}

// Auto-check remember me on include
checkRememberMe();
?>
