<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_type = $_POST['address_type'] ?? 'home';
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate required fields
    if (empty($address_line1) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required address fields']);
        exit();
    }
    
    // If setting as default, unset other default addresses
    if ($is_default) {
        $unset_default = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $stmt = $conn->prepare($unset_default);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Insert new address
    $insert_query = "INSERT INTO user_addresses (user_id, address_type, address_line1, address_line2, city, state, postal_code, country, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isssssssi", $user_id, $address_type, $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Address added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding address']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
