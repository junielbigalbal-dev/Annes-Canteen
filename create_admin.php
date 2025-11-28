<?php
// Create admin account
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'school_canteen';
$db_port = getenv('DB_PORT') ?: 3306;

echo "<h1>Create Admin Account</h1>";

// Create connection with SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_errno) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

// Check if admin already exists
$check = $conn->query("SELECT * FROM users WHERE email = 'admin@annescanteen.com'");
if ($check && $check->num_rows > 0) {
    echo "<p style='color:orange'>⚠️ Admin account already exists!</p>";
    echo "<p><strong>Email:</strong> admin@annescanteen.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit;
}

// Create admin account
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active, created_at, updated_at) 
        VALUES ('admin', 'admin@annescanteen.com', ?, 'Admin', 'User', 1, 1, NOW(), NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: green;'>✅ Admin Account Created Successfully!</h2>";
    echo "<p><strong>Email:</strong> admin@annescanteen.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Go to Login</a></p>";
    echo "</div>";
    echo "<p style='color: red;'><strong>IMPORTANT:</strong> Change this password after logging in!</p>";
} else {
    echo "<p style='color:red'>Error creating admin: " . $conn->error . "</p>";
}

$conn->close();
?>
