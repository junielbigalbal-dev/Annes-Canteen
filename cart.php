<?php
session_start();
require_once 'config/database.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $item_id = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        // Get item details from database
        $query = "SELECT * FROM menu_items WHERE item_id = ? AND is_available = 1";
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
                    'quantity' => $quantity
                ];
            }
            
            $_SESSION['success'] = "Item added to cart successfully!";
        } else {
            $_SESSION['error'] = "Item not found or unavailable!";
        }
        
        header('Location: cart.php');
        exit();
    }
    
    // Remove from cart
    if ($_POST['action'] === 'remove') {
        $item_id = intval($_POST['item_id']);
        if (isset($_SESSION['cart'][$item_id])) {
            unset($_SESSION['cart'][$item_id]);
            $_SESSION['success'] = "Item removed from cart!";
        }
        header('Location: cart.php');
        exit();
    }
    
    // Update quantity
    if ($_POST['action'] === 'update') {
        $item_id = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        
        if (isset($_SESSION['cart'][$item_id]) && $quantity > 0) {
            $_SESSION['cart'][$item_id]['quantity'] = $quantity;
            $_SESSION['success'] = "Cart updated!";
        }
        header('Location: cart.php');
        exit();
    }
    
    // Clear cart
    if ($_POST['action'] === 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Cart cleared!";
        header('Location: cart.php');
        exit();
    }
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get messages
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Anne's Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        .cart-item-details {
            flex: 1;
        }
        .cart-item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .cart-item-price {
            color: #e74a3b;
            font-weight: 500;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .cart-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container cart-container">
        <h2 class="mb-4">
            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
        </h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some delicious items from our menu!</p>
                <a href="menu.php" class="btn btn-primary">
                    <i class="fas fa-utensils me-2"></i>Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo file_exists('assets/images/' . $item['image']) ? 'assets/images/' . $item['image'] : 'assets/images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                            <div class="cart-item-details">
                                <h5 class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div class="quantity-controls">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="button" class="quantity-btn" onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value) - 1); this.form.submit();">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="10" onchange="this.form.submit();">
                                    <button type="button" class="quantity-btn" onclick="this.form.quantity.value = Math.min(10, parseInt(this.form.quantity.value) + 1); this.form.submit();">+</button>
                                </form>
                            </div>
                            <div class="ms-3">
                                <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                            <div class="ms-3">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h5 class="mb-3">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span>₱0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary">₱<?php echo number_format($total, 2); ?></strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </a>
                            <form method="POST">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fas fa-trash me-2"></i>Clear Cart
                                </button>
                            </form>
                            <a href="menu.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update cart count when items are removed or quantity is changed
        function updateCartCount() {
            // Count total items from the displayed cart
            const quantityInputs = document.querySelectorAll('.quantity-input');
            let totalCount = 0;
            quantityInputs.forEach(input => {
                totalCount += parseInt(input.value) || 0;
            });
            
            // Update cart count in header
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = totalCount;
            }
        }
        
        // Update cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
        
        // Override form submissions to update cart count
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                // For remove actions, the cart count will be updated on page reload
                // For update actions, the cart count will be updated on page reload
                // This is handled by the PHP session on the next page load
            });
        });
    </script>
</body>
</html>
