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
    
    // Verify address belongs to user and is not default
    $verify_query = "SELECT address_id, is_default FROM user_addresses WHERE address_id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Address not found']);
        exit();
    }
    
    $address = $result->fetch_assoc();
    if ($address['is_default']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete default address']);
        exit();
    }
    
    // Delete address
    $delete_query = "DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting address']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
