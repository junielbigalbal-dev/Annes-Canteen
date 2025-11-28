<?php
// Standalone database setup - DO NOT include config files that trigger auth
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'school_canteen';
$db_port = getenv('DB_PORT') ?: 3306;

echo "<h1>Database Setup</h1>";

// Create connection with SSL
$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_errno) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green'>✓ Connected to database successfully.</p>";

// Define tables directly
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        is_admin BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        remember_token VARCHAR(255),
        remember_token_expires TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS menu_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        image_url VARCHAR(255),
        is_vegetarian BOOLEAN DEFAULT FALSE,
        is_vegan BOOLEAN DEFAULT FALSE,
        is_gluten_free BOOLEAN DEFAULT FALSE,
        is_featured BOOLEAN DEFAULT FALSE,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        delivery_address TEXT,
        contact_number VARCHAR(20),
        special_instructions TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS order_items (
        order_item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        item_id INT,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        special_instructions TEXT,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        item_id INT,
        rating TINYINT NOT NULL,
        comment TEXT,
        review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
        FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS promotions (
        promotion_id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) UNIQUE NOT NULL,
        description TEXT,
        discount_type ENUM('percentage', 'fixed') NOT NULL,
        discount_value DECIMAL(10, 2) NOT NULL,
        min_order_amount DECIMAL(10, 2) DEFAULT 0,
        max_discount DECIMAL(10, 2),
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS user_addresses (
        address_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        address_type ENUM('home', 'work', 'other') DEFAULT 'home',
        address_line1 VARCHAR(255) NOT NULL,
        address_line2 VARCHAR(255),
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        country VARCHAR(100) NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS user_payment_methods (
        payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        payment_type ENUM('credit_card', 'debit_card', 'paypal', 'other') NOT NULL,
        provider VARCHAR(50) NOT NULL,
        account_number VARCHAR(100),
        expiry_date DATE,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )"
];

$success = 0;
$errors = 0;

echo "<p>Creating tables...</p>";
echo "<div style='max-height: 300px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        $success++;
        echo "<p style='color:green; margin: 5px 0;'>✓ Table created</p>";
    } else {
        $errors++;
        echo "<p style='color:red; margin: 5px 0;'>✗ Error: " . htmlspecialchars($conn->error) . "</p>";
    }
}

echo "</div>";
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>✅ Successful:</strong> $success</p>";
echo "<p><strong>❌ Errors:</strong> $errors</p>";

if ($errors == 0) {
    echo "<h2 style='color:green'>✅ Database setup completed successfully!</h2>";
    echo "<p>All tables have been created. You can now <a href='register.php'>register an account</a>.</p>";
} else {
    echo "<h2 style='color:orange'>⚠️ Setup completed with some errors</h2>";
}

echo "<p><strong>Security Warning:</strong> Delete this file after use.</p>";
$conn->close();
?>
