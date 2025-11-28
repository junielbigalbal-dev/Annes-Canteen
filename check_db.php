<?php
// Database connection test
echo "<h2>Database Connection & Admin Check</h2>";

// Test database connection
try {
    require_once 'config/database.php';
    echo "<span style='color: green;'>✓ Database connection successful</span><br><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</span>";
    exit();
}

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    echo "<span style='color: green;'>✓ Users table exists</span><br>";
} else {
    echo "<span style='color: red;'>✗ Users table not found</span><br>";
    echo "Please run the database setup first: school_canteen.sql<br>";
    exit();
}

// Check all users in database
echo "<h3>All Users in Database:</h3>";
$users_query = "SELECT user_id, username, email, is_admin, is_active, created_at FROM users ORDER BY user_id";
$users_result = $conn->query($users_query);

if ($users_result && $users_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th><th>Is Active</th><th>Created</th></tr>";
    
    while ($user = $users_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color: red;'>No users found in database</span><br>";
}

// Check specifically for admin user
echo "<h3>Admin User Details:</h3>";
$admin_query = "SELECT * FROM users WHERE email = 'admin@schoolcanteen.com'";
$admin_result = $conn->query($admin_query);

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>User ID</td><td>" . $admin['user_id'] . "</td></tr>";
    echo "<tr><td>Username</td><td>" . htmlspecialchars($admin['username']) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
    echo "<tr><td>First Name</td><td>" . htmlspecialchars($admin['first_name']) . "</td></tr>";
    echo "<tr><td>Last Name</td><td>" . htmlspecialchars($admin['last_name']) . "</td></tr>";
    echo "<tr><td>Is Admin</td><td>" . ($admin['is_admin'] ? 'Yes' : 'No') . "</td></tr>";
    echo "<tr><td>Is Active</td><td>" . ($admin['is_active'] ? 'Yes' : 'No') . "</td></tr>";
    echo "<tr><td>Password Hash</td><td><code>" . htmlspecialchars($admin['password_hash']) . "</code></td></tr>";
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Testing:</h3>";
    $test_passwords = ['admin123', 'Admin123', 'admin', 'password'];
    foreach ($test_passwords as $password) {
        $verify = password_verify($password, $admin['password_hash']);
        $status = $verify ? '<span style="color: green;">✓ WORKS</span>' : '<span style="color: red;">✗ Failed</span>';
        echo "Password '$password': $status<br>";
    }
    
    // Show working credentials
    echo "<h3>Working Credentials:</h3>";
    foreach ($test_passwords as $password) {
        if (password_verify($password, $admin['password_hash'])) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "<strong>Use these credentials:</strong><br>";
            echo "Email: admin@schoolcanteen.com<br>";
            echo "Password: $password<br>";
            echo "<a href='login.php'>Go to Login</a>";
            echo "</div>";
            break;
        }
    }
} else {
    echo "<span style='color: red;'>No admin user found with email 'admin@schoolcanteen.com'</span><br>";
    
    // Create admin user
    echo "<h3>Creating Admin User...</h3>";
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $insert_query = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active, created_at, updated_at) 
                     VALUES ('admin', 'admin@schoolcanteen.com', ?, 'Admin', 'User', 1, 1, NOW(), NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "<span style='color: green;'>✓ Admin user created successfully!</span><br>";
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
        echo "<strong>Use these credentials:</strong><br>";
        echo "Email: admin@schoolcanteen.com<br>";
        echo "Password: $password<br>";
        echo "<a href='login.php'>Go to Login</a>";
        echo "</div>";
    } else {
        echo "<span style='color: red;'>✗ Error creating admin: " . $conn->error . "</span>";
    }
}

echo "<br><br>";
echo "<a href='login.php'>Go to Login Page</a> | ";
echo "<a href='fix_admin.php'>Run Fix Admin Script</a>";

$conn->close();
?>
