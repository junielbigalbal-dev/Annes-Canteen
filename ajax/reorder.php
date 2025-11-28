<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_SESSION['user_id'];
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }
    
    // Verify order belongs to user and is delivered
    $verify_query = "SELECT order_id, status FROM orders WHERE order_id = ? AND user_id = ? AND status = 'delivered'";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not eligible for reorder']);
        exit();
    }
    
    // Get order items
    $items_query = "SELECT oi.item_id, oi.quantity, mi.name, mi.price 
                    FROM order_items oi 
                    LEFT JOIN menu_items mi ON oi.item_id = mi.item_id 
                    WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    if ($items->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No items found in order']);
        exit();
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $added_count = 0;
    
    // Add items to cart
    while ($item = $items->fetch_assoc()) {
        // Check if item is still available in menu
        if ($item['item_id'] && $item['price'] !== null) {
            $item_id = $item['item_id'];
            $quantity = $item['quantity'];
            
            // If item already in cart, update quantity
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity'] += $quantity;
            } else {
                // Add new item to cart
                $_SESSION['cart'][$item_id] = [
                    'id' => $item_id,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $quantity,
                    'image' => 'default.jpg' // Will be updated when needed
                ];
            }
            $added_count++;
        }
    }
    
    // Calculate cart count
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $cart_item) {
        $cart_count += $cart_item['quantity'];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Added $added_count items to cart",
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
