<?php
// Quick Admin Fix - This will definitely work
require_once 'config/database.php';

echo "<h2>🔧 Quick Admin Fix</h2>";

// First, let's delete any existing admin to avoid conflicts
$delete_query = "DELETE FROM users WHERE email = 'admin@schoolcanteen.com' OR username = 'admin'";
$conn->query($delete_query);

// Now create a fresh admin user
$username = 'admin';
$email = 'admin@schoolcanteen.com';
$password = 'admin123'; // Simple password for testing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$first_name = 'Admin';
$last_name = 'User';

$insert_query = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, 1, 1, NOW(), NOW())";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param("sssss", $username, $email, $hashed_password, $first_name, $last_name);

if ($stmt->execute()) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ Admin User Created Successfully!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<p><strong>Email:</strong> admin@schoolcanteen.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Go to Login Page</a></p>";
    echo "</div>";
    
    // Verify the user was created
    $verify_query = "SELECT * FROM users WHERE email = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("s", $email);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<h3>Verification:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>User ID</td><td>" . $admin['user_id'] . "</td></tr>";
        echo "<tr><td>Username</td><td>" . htmlspecialchars($admin['username']) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
        echo "<tr><td>Is Admin</td><td>" . ($admin['is_admin'] ? 'Yes' : 'No') . "</td></tr>";
        echo "<tr><td>Is Active</td><td>" . ($admin['is_active'] ? 'Yes' : 'No') . "</td></tr>";
        echo "</table>";
        
        // Test the password
        echo "<h3>Password Test:</h3>";
        if (password_verify($password, $admin['password_hash'])) {
            echo "<span style='color: green; font-weight: bold;'>✅ Password verification PASSED</span><br>";
            echo "<p>You can now login with the credentials above.</p>";
        } else {
            echo "<span style='color: red; font-weight: bold;'>❌ Password verification FAILED</span><br>";
        }
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error Creating Admin User</h3>";
    echo "<p>Error: " . $conn->error . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔍 Database Status:</h3>";

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    echo "<span style='color: green;'>✅ Users table exists</span><br>";
} else {
    echo "<span style='color: red;'>❌ Users table not found</span><br>";
    echo "<p>You need to import the database file first: <code>database/school_canteen.sql</code></p>";
}

// Count total users
$count_query = "SELECT COUNT(*) as total FROM users";
$count_result = $conn->query($count_query);
$total_users = $count_result->fetch_assoc()['total'];
echo "<span style='color: blue;'>📊 Total users in database: $total_users</span><br>";

// Count admin users
$admin_count_query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 1";
$admin_count_result = $conn->query($admin_count_query);
$total_admins = $admin_count_result->fetch_assoc()['total'];
echo "<span style='color: blue;'>👑 Total admin users: $total_admins</span><br>";

echo "<br>";
echo "<a href='login.php'>🔐 Go to Login</a> | ";
echo "<a href='check_db.php'>🔍 Full Database Check</a>";

$conn->close();
?>
