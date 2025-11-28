<?php
session_start();

header('Content-Type: application/json');

// Calculate cart count from session
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

echo json_encode(['count' => $cart_count]);
?>
