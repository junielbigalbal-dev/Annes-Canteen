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
    
    // Get order details
    $order_query = "SELECT o.*, u.first_name, u.last_name, u.email 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.user_id 
                    WHERE o.order_id = ? AND o.user_id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $items_query = "SELECT oi.*, mi.name, mi.image_url, mi.description 
                    FROM order_items oi 
                    LEFT JOIN menu_items mi ON oi.item_id = mi.item_id 
                    WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = [];
    
    while ($item = $stmt->get_result()->fetch_assoc()) {
        $items[] = [
            'order_item_id' => $item['order_item_id'],
            'item_id' => $item['item_id'],
            'name' => $item['name'] ?? 'Menu Item',
            'description' => $item['description'] ?? 'No description available',
            'image_url' => $item['image_url'] ?? 'default.jpg',
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['unit_price'] * $item['quantity']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'order' => [
            'order_id' => $order['order_id'],
            'order_date' => $order['order_date'],
            'total_amount' => $order['total_amount'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'],
            'delivery_address' => $order['delivery_address'],
            'contact_number' => $order['contact_number'],
            'special_instructions' => $order['special_instructions']
        ],
        'items' => $items
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
