<?php
// Standalone database setup - DO NOT include config files that trigger auth
// Connect directly to avoid auth.php being loaded

// Get database credentials from environment
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'school_canteen';
$db_port = getenv('DB_PORT') ?: 3306;

echo "<h1>Database Setup</h1>";
echo "<p>Connecting to database...</p>";

// Create connection with SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

// Check connection
if ($conn->connect_errno) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green'>✓ Connected to database successfully.</p>";

// Read SQL file
$sql_file = 'database/school_canteen.sql';
if (!file_exists($sql_file)) {
    die("<p style='color:red'>SQL file not found: $sql_file</p>");
}

$sql_content = file_get_contents($sql_file);

// Remove the CREATE DATABASE and USE statements since we're already connected
$sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
$sql_content = preg_replace('/USE.*?;/i', '', $sql_content);

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

$success_count = 0;
$error_count = 0;

echo "<p>Executing SQL statements...</p>";
echo "<div style='max-height: 300px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Skip comments
    if (strpos(trim($statement), '--') === 0) continue;
    
    if ($conn->query($statement . ';')) {
        $success_count++;
        echo "<p style='color:green; margin: 5px 0;'>✓ Statement executed</p>";
    } else {
        $error_count++;
        echo "<p style='color:red; margin: 5px 0;'>✗ Error: " . htmlspecialchars($conn->error) . "</p>";
        echo "<pre style='font-size: 11px;'>" . htmlspecialchars(substr($statement, 0, 150)) . "...</pre>";
    }
}

echo "</div>";
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>✅ Successful:</strong> $success_count</p>";
echo "<p><strong>❌ Errors:</strong> $error_count</p>";

if ($error_count == 0) {
    echo "<h2 style='color:green'>✅ Database setup completed successfully!</h2>";
    echo "<p>All tables have been created. You can now <a href='index.php'>go to the home page</a> and register an account.</p>";
} else {
    echo "<h2 style='color:orange'>⚠️ Setup completed with some errors</h2>";
    echo "<p>Some statements failed. Check the errors above.</p>";
}

echo "<p><strong>Security Warning:</strong> Please delete this file (db_setup.php) from your repository after use.</p>";

$conn->close();
?>
