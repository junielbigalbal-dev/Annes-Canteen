<?php
require_once 'config/database.php';

echo "<h2>Admin User Check & Fix</h2>";

// Check if admin user exists
$check_query = "SELECT * FROM users WHERE email = 'admin@schoolcanteen.com'";
$result = $conn->query($check_query);

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<h3>Admin user found:</h3>";
    echo "<pre>";
    echo "ID: " . $admin['user_id'] . "\n";
    echo "Username: " . $admin['username'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Is Admin: " . ($admin['is_admin'] ? 'Yes' : 'No') . "\n";
    echo "Is Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Password Hash: " . $admin['password_hash'] . "\n";
    echo "</pre>";
    
    // Test password verification
    $test_passwords = ['admin123', 'Admin123', 'admin'];
    echo "<h3>Password Test:</h3>";
    foreach ($test_passwords as $password) {
        if (password_verify($password, $admin['password_hash'])) {
            echo "<span style='color: green;'><strong>✓ Password '$password' works!</strong></span><br>";
            echo "<p>Use these credentials:<br>";
            echo "Email: admin@schoolcanteen.com<br>";
            echo "Password: $password</p>";
            break;
        } else {
            echo "<span style='color: red;'>✗ Password '$password' failed</span><br>";
        }
    }
} else {
    echo "<h3>No admin user found. Creating one...</h3>";
    
    // Create admin user with known password
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active, created_at, updated_at) 
                     VALUES ('admin', 'admin@schoolcanteen.com', ?, 'Admin', 'User', 1, 1, NOW(), NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "<span style='color: green;'><strong>Admin user created successfully!</strong></span><br>";
        echo "<p>Use these credentials:<br>";
        echo "Email: admin@schoolcanteen.com<br>";
        echo "Password: $password</p>";
        
        // Verify it was created
        $verify_query = "SELECT * FROM users WHERE email = 'admin@schoolcanteen.com'";
        $verify_result = $conn->query($verify_query);
        if ($verify_result->num_rows > 0) {
            $admin_data = $verify_result->fetch_assoc();
            echo "<h4>Verification:</h4>";
            echo "Password verification for '$password': " . 
                 (password_verify($password, $admin_data['password_hash']) ? 
                  '<span style="color: green;">✓ PASSED</span>' : 
                  '<span style="color: red;">✗ FAILED</span>') . "<br>";
        }
    } else {
        echo "<span style='color: red;'>Error creating admin: " . $conn->error . "</span>";
    }
}

echo "<br><a href='login.php'>Go to Login Page</a>";
$conn->close();
?>
