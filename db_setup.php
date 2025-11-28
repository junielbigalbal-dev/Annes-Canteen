<?php
require_once 'config/database.php';

echo "<h1>Database Setup</h1>";

// Check connection
if ($conn->connect_errno) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p>Connected to database successfully.</p>";

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

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Skip comments
    if (strpos(trim($statement), '--') === 0) continue;
    
    if ($conn->query($statement . ';')) {
        $success_count++;
        echo "<p style='color:green'>✓ Executed statement successfully</p>";
    } else {
        $error_count++;
        echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
        echo "<pre>" . htmlspecialchars(substr($statement, 0, 200)) . "...</pre>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>✅ Successful: $success_count</p>";
echo "<p>❌ Errors: $error_count</p>";

if ($error_count == 0) {
    echo "<h2 style='color:green'>✅ Database setup completed successfully!</h2>";
    echo "<p>You can now <a href='index.php'>go to the home page</a>.</p>";
} else {
    echo "<h2 style='color:orange'>⚠️ Setup completed with some errors</h2>";
    echo "<p>Some statements failed, but basic tables may have been created.</p>";
}

echo "<p><strong>Security Warning:</strong> Please delete this file (db_setup.php) from your repository after use.</p>";

$conn->close();
?>
