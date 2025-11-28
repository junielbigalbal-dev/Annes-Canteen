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
    $address_id = intval($_POST['address_id'] ?? 0);
    
    if ($address_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
        exit();
    }
    
    // Verify address belongs to user
    $verify_query = "SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Address not found']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Unset all default addresses for this user
        $unset_default = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $stmt = $conn->prepare($unset_default);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Set new default address
        $set_default = "UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?";
        $stmt = $conn->prepare($set_default);
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error updating default address']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
