<?php
// Generate proper password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";
echo "<p>Password: <strong>$password</strong></p>";
echo "<p>Hash: <code>$hash</code></p>";

// Test the hash
if (password_verify($password, $hash)) {
    echo "<span style='color: green;'>✅ Hash verification PASSED</span><br>";
} else {
    echo "<span style='color: red;'>❌ Hash verification FAILED</span><br>";
}

echo "<h3>SQL INSERT Statement:</h3>";
echo "<pre>";
echo "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active) VALUES 
('admin', 'admin@schoolcanteen.com', '$hash', 'Admin', 'User', TRUE, TRUE);";
echo "</pre>";

echo "<p>Copy this hash and update your SQL file, or use the quick_admin_fix.php script.</p>";
?>
