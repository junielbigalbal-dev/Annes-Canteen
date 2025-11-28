<?php
session_start();
require_once 'config/database.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // Validate input
    if ($item_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item or quantity']);
        exit();
    }
    
    // Get item details from database
    $query = "SELECT mi.*, c.name as category_name FROM menu_items mi 
              LEFT JOIN categories c ON mi.category_id = c.category_id 
              WHERE mi.item_id = ? AND mi.is_available = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        // Check if item already in cart
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$item_id] = [
                'id' => $item_id,
                'name' => $item['name'],
                'price' => $item['price'],
                'image' => $item['image_url'],
                'quantity' => $quantity,
                'category' => $item['category_name']
            ];
        }
        
        // Calculate cart count
        $cart_count = 0;
        foreach ($_SESSION['cart'] as $cart_item) {
            $cart_count += $cart_item['quantity'];
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item added to cart successfully!',
            'cart_count' => $cart_count,
            'item_name' => $item['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found or unavailable!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
