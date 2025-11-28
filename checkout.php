<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user addresses
$addresses_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$addresses_stmt = $conn->prepare($addresses_query);
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses = $addresses_stmt->get_result();

// Calculate cart total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? $user['phone'] ?? '');
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $address_id = intval($_POST['address_id'] ?? 0);
    
    // Validate input
    if (empty($delivery_address) && $address_id === 0) {
        $_SESSION['error'] = "Please select or enter a delivery address";
        header('Location: checkout.php');
        exit();
    }
    
    if (empty($contact_number)) {
        $_SESSION['error'] = "Please provide a contact number";
        header('Location: checkout.php');
        exit();
    }
    
    if (empty($payment_method)) {
        $_SESSION['error'] = "Please select a payment method";
        header('Location: checkout.php');
        exit();
    }
    
    // Get full address if address_id is provided
    if ($address_id > 0) {
        $addr_query = "SELECT * FROM user_addresses WHERE address_id = ? AND user_id = ?";
        $addr_stmt = $conn->prepare($addr_query);
        $addr_stmt->bind_param("ii", $address_id, $user_id);
        $addr_stmt->execute();
        $address_data = $addr_stmt->get_result()->fetch_assoc();
        
        if ($address_data) {
            $delivery_address = $address_data['address_line1'];
            if (!empty($address_data['address_line2'])) {
                $delivery_address .= ', ' . $address_data['address_line2'];
            }
            $delivery_address .= ', ' . $address_data['city'] . ', ' . $address_data['state'] . ' ' . $address_data['postal_code'] . ', ' . $address_data['country'];
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create order
        $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_status, payment_method, delivery_address, contact_number, special_instructions) 
                        VALUES (?, ?, 'pending', 'pending', ?, ?, ?, ?)";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("idssss", $user_id, $total, $payment_method, $delivery_address, $contact_number, $special_instructions);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        
        // Add order items
        foreach ($_SESSION['cart'] as $item) {
            $item_query = "INSERT INTO order_items (order_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_query);
            $item_stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $item_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Set success message
        $_SESSION['success'] = "Order placed successfully! Your order ID is #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
        
        // Redirect to order confirmation
        header('Location: order_confirmation.php?id=' . $order_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error'] = "Error placing order. Please try again.";
        header('Location: checkout.php');
        exit();
    }
}

$page_title = "Checkout";
include_once 'includes/header.php';
?>

<!-- Checkout Section -->
<section class="checkout-section">
    <div class="container">
        <div class="section-header">
            <h1>Checkout</h1>
            <p>Complete your order details</p>
        </div>

        <div class="checkout-container">
            <div class="row">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <form id="checkoutForm" method="POST">
                        <!-- Delivery Information -->
                        <div class="checkout-card">
                            <div class="card-header">
                                <h5><i class="fas fa-truck me-2"></i>Delivery Information</h5>
                            </div>
                            <div class="card-body">
                                <!-- Saved Addresses -->
                                <?php if ($addresses->num_rows > 0): ?>
                                    <div class="saved-addresses">
                                        <h6>Saved Addresses</h6>
                                        <div class="address-options">
                                            <?php while ($address = $addresses->fetch_assoc()): ?>
                                                <div class="address-option">
                                                    <label class="address-label">
                                                        <input type="radio" name="address_id" value="<?php echo $address['address_id']; ?>" 
                                                               <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                                        <div class="address-content">
                                                            <strong><?php echo ucfirst($address['address_type']); ?></strong>
                                                            <?php if ($address['is_default']): ?>
                                                                <span class="badge bg-primary ms-2">Default</span>
                                                            <?php endif; ?>
                                                            <br>
                                                            <small>
                                                                <?php echo htmlspecialchars($address['address_line1']); ?>
                                                                <?php if (!empty($address['address_line2'])): ?>
                                                                    , <?php echo htmlspecialchars($address['address_line2']); ?>
                                                                <?php endif; ?>
                                                                , <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                                            </small>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <div class="divider">
                                            <span>OR</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- New Address -->
                                <div class="new-address">
                                    <h6>Enter New Address</h6>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="address_id" value="0" 
                                               <?php echo $addresses->num_rows === 0 ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Use a new address</label>
                                    </div>
                                    <div class="address-fields">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="form-label">Delivery Address *</label>
                                                <textarea class="form-control" name="delivery_address" rows="3" 
                                                          placeholder="Enter complete delivery address" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contact Information -->
                                <div class="contact-info mt-4">
                                    <h6>Contact Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Number *</label>
                                            <input type="tel" class="form-control" name="contact_number" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                                   placeholder="Enter your phone number" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Special Instructions -->
                                <div class="special-instructions mt-4">
                                    <label class="form-label">Special Instructions (Optional)</label>
                                    <textarea class="form-control" name="special_instructions" rows="3" 
                                              placeholder="Any special requests or delivery instructions"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-card">
                            <div class="card-header">
                                <h5><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                            </div>
                            <div class="card-body">
                                <div class="payment-options">
                                    <div class="payment-option">
                                        <label class="payment-label">
                                            <input type="radio" name="payment_method" value="cash" checked required>
                                            <div class="payment-content">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <div>
                                                    <strong>Cash on Delivery</strong>
                                                    <br>
                                                    <small>Pay when you receive your order</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <label class="payment-label">
                                            <input type="radio" name="payment_method" value="gcash">
                                            <div class="payment-content">
                                                <i class="fas fa-wallet"></i>
                                                <div>
                                                    <strong>GCash</strong>
                                                    <br>
                                                    <small>Pay with GCash wallet</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <label class="payment-label">
                                            <input type="radio" name="payment_method" value="card">
                                            <div class="payment-content">
                                                <i class="fas fa-credit-card"></i>
                                                <div>
                                                    <strong>Credit/Debit Card</strong>
                                                    <br>
                                                    <small>Pay with your card</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary sticky">
                        <div class="card-header">
                            <h5><i class="fas fa-shopping-cart me-2"></i>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <!-- Cart Items -->
                            <div class="cart-items">
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                    <div class="cart-item">
                                        <div class="item-info">
                                            <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <small>Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?></small>
                                        </div>
                                        <div class="item-price">
                                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <!-- Price Breakdown -->
                            <div class="price-breakdown">
                                <div class="price-row">
                                    <span>Subtotal</span>
                                    <span>₱<?php echo number_format($total, 2); ?></span>
                                </div>
                                <div class="price-row">
                                    <span>Delivery Fee</span>
                                    <span>₱0.00</span>
                                </div>
                                <div class="price-row">
                                    <span>Service Fee</span>
                                    <span>₱0.00</span>
                                </div>
                                <hr>
                                <div class="price-row total">
                                    <strong>Total</strong>
                                    <strong>₱<?php echo number_format($total, 2); ?></strong>
                                </div>
                            </div>
                            
                            <!-- Place Order Button -->
                            <button type="submit" form="checkoutForm" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                            
                            <!-- Back to Cart -->
                            <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once 'includes/footer.php';
$conn->close();
?>

<style>
.checkout-section {
    padding: 100px 0 50px;
    background-color: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
}

.checkout-card {
    background: white;
    border-radius: 15px;
    margin-bottom: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
}

.card-header h5 {
    margin: 0;
    font-size: 1.2rem;
}

.card-body {
    padding: 30px 25px;
}

.saved-addresses h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.address-options {
    margin-bottom: 20px;
}

.address-option {
    margin-bottom: 15px;
}

.address-label {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    cursor: pointer;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.address-label:hover {
    border-color: #667eea;
    background: #f8f9fa;
}

.address-label input[type="radio"] {
    margin-top: 5px;
}

.address-content strong {
    color: #2c3e50;
}

.divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #6c757d;
    font-size: 0.9rem;
}

.new-address h6 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.contact-info h6, .special-instructions label {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
}

.payment-options {
    display: grid;
    gap: 15px;
}

.payment-option {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.payment-option:hover {
    border-color: #667eea;
}

.payment-label {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    cursor: pointer;
    margin: 0;
}

.payment-label input[type="radio"] {
    margin: 0;
}

.payment-content {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.payment-content i {
    font-size: 1.5rem;
    color: #667eea;
    width: 30px;
}

.order-summary {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    position: sticky;
    top: 100px;
}

.cart-items {
    margin-bottom: 20px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.item-info h6 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 0.95rem;
}

.item-info small {
    color: #6c757d;
}

.item-price {
    font-weight: 600;
    color: #667eea;
}

.price-breakdown {
    margin-bottom: 25px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.price-row.total {
    font-size: 1.1rem;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .checkout-section {
        padding: 80px 0 30px;
    }
    
    .order-summary {
        position: relative;
        top: 0;
        margin-top: 20px;
    }
    
    .card-body {
        padding: 20px 15px;
    }
    
    .payment-label {
        padding: 15px;
    }
    
    .payment-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .payment-content i {
        width: auto;
    }
}
</style>
?>
