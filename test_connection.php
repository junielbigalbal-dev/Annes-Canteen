<?php
echo "<h2>🔍 Database Connection Test</h2>";

// Test database connection step by step
echo "<h3>Step 1: Test Database Connection</h3>";

try {
    // Check if database file exists
    $db_file = 'config/database.php';
    if (!file_exists($db_file)) {
        echo "<span style='color: red;'>❌ Database config file not found: $db_file</span><br>";
        exit();
    } else {
        echo "<span style='color: green;'>✅ Database config file found</span><br>";
    }
    
    require_once 'config/database.php';
    echo "<span style='color: green;'>✅ Database connection successful</span><br>";
    echo "<span style='color: blue;'>Connected to: " . $conn->host_info . "</span><br>";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database credentials in config/database.php</p>";
    exit();
}

echo "<h3>Step 2: Check Database Tables</h3>";

// List all tables
$tables = $conn->query("SHOW TABLES");
if ($tables && $tables->num_rows > 0) {
    echo "<span style='color: green;'>✅ Found " . $tables->num_rows . " tables:</span><br>";
    echo "<ul>";
    while ($table = $tables->fetch_array()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<span style='color: red;'>❌ No tables found in database</span><br>";
    echo "<p>You need to import the database file: database/school_canteen.sql</p>";
    exit();
}

echo "<h3>Step 3: Check Users Table Structure</h3>";

$users_table = $conn->query("DESCRIBE users");
if ($users_table && $users_table->num_rows > 0) {
    echo "<span style='color: green;'>✅ Users table structure:</span><br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($field = $users_table->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color: red;'>❌ Users table not found or has no columns</span><br>";
}

echo "<h3>Step 4: Test Simple Admin Creation</h3>";

// Try to create admin with simple query
try {
    // Delete existing admin first
    $conn->query("DELETE FROM users WHERE email = 'admin@schoolcanteen.com'");
    
    // Create new admin
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active, created_at, updated_at) 
            VALUES ('admin', 'admin@schoolcanteen.com', '$hashed_password', 'Admin', 'User', 1, 1, NOW(), NOW())";
    
    if ($conn->query($sql)) {
        echo "<span style='color: green;'>✅ Admin user created successfully!</span><br>";
        
        // Verify it was created
        $result = $conn->query("SELECT * FROM users WHERE email = 'admin@schoolcanteen.com'");
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            echo "<span style='color: green;'>✅ Admin user verified in database</span><br>";
            
            // Test password
            if (password_verify($password, $admin['password_hash'])) {
                echo "<span style='color: green;'>✅ Password verification works!</span><br>";
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>🎉 SUCCESS! Use these credentials:</h4>";
                echo "<p><strong>Email:</strong> admin@schoolcanteen.com</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
                echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Go to Login</a>";
                echo "</div>";
            } else {
                echo "<span style='color: red;'>❌ Password verification failed</span><br>";
            }
        } else {
            echo "<span style='color: red;'>❌ Admin user not found after creation</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ Failed to create admin: " . $conn->error . "</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>Step 5: Current Users in Database</h3>";

$all_users = $conn->query("SELECT user_id, username, email, is_admin, is_active FROM users");
if ($all_users && $all_users->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Active</th></tr>";
    while ($user = $all_users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color: orange;'>⚠️ No users found in database</span><br>";
}

echo "<br><hr>";
echo "<p><strong>If you're still having issues:</strong></p>";
echo "<ol>";
echo "<li>Make sure you imported database/school_canteen.sql</li>";
echo "<li>Check your database credentials in config/database.php</li>";
echo "<li>Try the credentials above exactly as shown</li>";
echo "</ol>";

echo "<br><a href='login.php'>🔐 Go to Login</a>";

$conn->close();
?>
