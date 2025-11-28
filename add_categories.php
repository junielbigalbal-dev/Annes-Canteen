<?php
// Add default categories
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'school_canteen';
$db_port = getenv('DB_PORT') ?: 3306;

echo "<h1>Add Default Categories</h1>";

// Create connection with SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_errno) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

// Default categories
$categories = [
    ['Breakfast', 'Delicious breakfast items to start your day'],
    ['Lunch', 'Hearty meals for lunch'],
    ['Dinner', 'Satisfying dinner options'],
    ['Sides', 'Tasty side dishes'],
    ['Beverages', 'Refreshing drinks'],
    ['Desserts', 'Sweet treats'],
    ['Snacks', 'Quick bites and snacks']
];

$added = 0;
$skipped = 0;

echo "<p>Adding categories...</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";

foreach ($categories as $cat) {
    // Check if category already exists
    $check = $conn->prepare("SELECT category_id FROM categories WHERE name = ?");
    $check->bind_param("s", $cat[0]);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color:orange; margin: 5px 0;'>⚠️ {$cat[0]} - already exists</p>";
        $skipped++;
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, description, is_active) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $cat[0], $cat[1]);
        
        if ($stmt->execute()) {
            echo "<p style='color:green; margin: 5px 0;'>✓ {$cat[0]} - added</p>";
            $added++;
        } else {
            echo "<p style='color:red; margin: 5px 0;'>✗ {$cat[0]} - error: " . $conn->error . "</p>";
        }
    }
}

echo "</div>";
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>✅ Added:</strong> $added</p>";
echo "<p><strong>⚠️ Skipped (already exist):</strong> $skipped</p>";

if ($added > 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: green;'>✅ Categories Added Successfully!</h2>";
    echo "<p>You can now <a href='admin/menu-items.php'>add menu items</a> to these categories.</p>";
    echo "</div>";
}

$conn->close();
?>
