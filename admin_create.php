<?php
// admin_create.php
require_once 'config/database.php';

// Admin user details
$admin_data = [
    'username' => 'admin',
    'email' => 'admin@schoolcanteen.com',
    'password' => 'Admin123', // Strong password
    'first_name' => 'Admin',
    'last_name' => 'User',
    'phone' => '1234567890',
    'address' => "Anne's Canteen",
    'is_admin' => 1
];

try {
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $admin_data['email'], $admin_data['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        die("Admin user already exists!");
    }
    
    // Hash the password
    $hashed_password = password_hash($admin_data['password'], PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $conn->prepare("
        INSERT INTO users 
        (username, email, password_hash, first_name, last_name, phone, address, is_admin, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    
    $stmt->bind_param(
        "sssssss",
        $admin_data['username'],
        $admin_data['email'],
        $hashed_password,
        $admin_data['first_name'],
        $admin_data['last_name'],
        $admin_data['phone'],
        $admin_data['address']
    );
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: " . $admin_data['username'] . "<br>";
        echo "Password: " . $admin_data['password'] . "<br>";
        echo "<strong>IMPORTANT:</strong> Change this password after first login!<br>";
        echo '<a href="admin/login.php">Go to Admin Login</a>';
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$conn->close();
?>
