<?php
require_once 'config/database.php';

echo "<h1>Database Setup</h1>";

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p>Connected to database successfully.</p>";

// Read SQL file
$sql_file = 'database/school_canteen.sql';
if (!file_exists($sql_file)) {
    die("<p style='color:red'>SQL file not found: $sql_file</p>");
}

$sql = file_get_contents($sql_file);

// Execute multi-query
if ($conn->multi_query($sql)) {
    echo "<p style='color:green'>Database setup started...</p>";
    
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for errors
        if ($conn->errno) {
            echo "<p style='color:red'>Error: " . $conn->error . "</p>";
        }
    } while ($conn->next_result());
    
    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<p>You can now <a href='index.php'>go to the home page</a>.</p>";
    echo "<p><strong>Security Warning:</strong> Please delete this file (db_setup.php) from your repository after use.</p>";
} else {
    echo "<p style='color:red'>Error executing SQL: " . $conn->error . "</p>";
}

$conn->close();
?>
