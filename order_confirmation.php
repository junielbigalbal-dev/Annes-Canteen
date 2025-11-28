<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL
$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify order belongs to user and get order details
$order_query = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Get order items
$items_query = "SELECT oi.*, mi.name, mi.image_url, mi.description 
                FROM order_items oi 
                LEFT JOIN menu_items mi ON oi.item_id = mi.item_id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

$page_title = "Order Confirmation";
include_once 'includes/header.php';
?>

<!-- Order Confirmation Section -->
<section class="order-confirmation-section">
    <div class="container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We've received it and are preparing it for you.</p>
        </div>

        <!-- Order Details Card -->
        <div class="order-details-card">
            <div class="order-header">
                <div class="order-number">
                    <h3>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                    <span class="order-date"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="order-status">
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="order-timeline">
                <div class="timeline-item completed">
                    <div class="timeline-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Order Placed</h6>
                        <small><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                    </div>
                </div>
                <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'preparing', 'ready', 'delivered']) ? 'completed' : 'pending'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Order Confirmed</h6>
                        <small>Processing your order</small>
                    </div>
                </div>
                <div class="timeline-item <?php echo in_array($order['status'], ['preparing', 'ready', 'delivered']) ? 'completed' : 'pending'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Preparing</h6>
                        <small>Your food is being prepared</small>
                    </div>
                </div>
                <div class="timeline-item <?php echo in_array($order['status'], ['ready', 'delivered']) ? 'completed' : 'pending'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Ready for Delivery</h6>
                        <small>Your order is ready for delivery</small>
                    </div>
                </div>
                <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'completed' : 'pending'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Delivered</h6>
                        <small>Order completed</small>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items-section">
                <h4>Order Items</h4>
                <div class="order-items">
                    <?php while ($item = $order_items->fetch_assoc()): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="assets/images/menu/<?php echo htmlspecialchars($item['image_url'] ?? 'default.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name'] ?? 'Menu Item'); ?>">
                            </div>
                            <div class="item-details">
                                <h6><?php echo htmlspecialchars($item['name'] ?? 'Menu Item'); ?></h6>
                                <small class="item-desc"><?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?></small>
                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="item-price">
                                ₱<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Service Fee</span>
                    <span>₱0.00</span>
                </div>
                <div class="summary-row total">
                    <strong>Total Paid</strong>
                    <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="delivery-info">
                <h4>Delivery Information</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Delivery Address</label>
                        <p><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Contact Number</label>
                        <p><?php echo htmlspecialchars($order['contact_number']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Payment Method</label>
                        <p><?php echo ucfirst($order['payment_method']); ?></p>
                    </div>
                    <?php if (!empty($order['special_instructions'])): ?>
                        <div class="info-item full-width">
                            <label>Special Instructions</label>
                            <p><?php echo htmlspecialchars($order['special_instructions']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="menu.php" class="btn btn-primary">
                <i class="fas fa-utensils me-2"></i>Order More
            </a>
            <a href="orders.php" class="btn btn-outline-primary">
                <i class="fas fa-list me-2"></i>View All Orders
            </a>
            <a href="profile.php" class="btn btn-outline-secondary">
                <i class="fas fa-user me-2"></i>My Profile
            </a>
        </div>

        <!-- Estimated Time -->
        <div class="estimated-time">
            <div class="time-card">
                <div class="time-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="time-content">
                    <h5>Estimated Delivery Time</h5>
                    <p>30-45 minutes</p>
                    <small>We'll notify you when your order is ready for pickup</small>
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
.order-confirmation-section {
    padding: 100px 0 50px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.success-header {
    text-align: center;
    margin-bottom: 50px;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    animation: successPulse 2s ease-in-out infinite;
}

.success-icon i {
    font-size: 3rem;
    color: white;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.success-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 700;
}

.success-header p {
    font-size: 1.1rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
}

.order-details-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.order-number h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 1.5rem;
}

.order-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cfe2ff; color: #084298; }
.status-preparing { background: #d1ecf1; color: #0c5460; }
.status-ready { background: #d4edda; color: #155724; }
.status-delivered { background: #d1f2eb; color: #0f5132; }

.order-timeline {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
    padding: 20px 0;
}

.order-timeline::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e9ecef;
    z-index: 0;
}

.timeline-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
    flex: 1;
}

.timeline-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.timeline-item.completed .timeline-icon {
    background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    color: white;
}

.timeline-item.pending .timeline-icon {
    background: #e9ecef;
    color: #6c757d;
}

.timeline-content {
    text-align: center;
}

.timeline-content h6 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.timeline-item.completed .timeline-content h6 {
    color: #00b894;
}

.timeline-item.pending .timeline-content h6 {
    color: #6c757d;
}

.timeline-content small {
    font-size: 0.8rem;
    color: #6c757d;
}

.order-items-section h4 {
    margin-bottom: 20px;
    color: #2c3e50;
    font-size: 1.3rem;
}

.order-items {
    margin-bottom: 30px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border: 1px solid #f0f0f0;
    border-radius: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.order-item:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-details h6 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 1.1rem;
}

.item-desc {
    color: #6c757d;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 5px;
}

.item-quantity {
    color: #ff6b6b;
    font-weight: 600;
    font-size: 0.9rem;
}

.item-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ff6b6b;
}

.order-summary {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.summary-row.total {
    font-size: 1.2rem;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #ff6b6b;
    color: #ff6b6b;
}

.delivery-info h4 {
    margin-bottom: 20px;
    color: #2c3e50;
    font-size: 1.3rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.info-item p {
    margin: 0;
    color: #6c757d;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.estimated-time {
    max-width: 600px;
    margin: 0 auto;
}

.time-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 25px;
}

.time-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.time-content h5 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.time-content p {
    margin: 0 0 5px 0;
    font-size: 1.3rem;
    font-weight: 700;
    color: #ff6b6b;
}

.time-content small {
    color: #6c757d;
}

@media (max-width: 768px) {
    .order-confirmation-section {
        padding: 80px 0 30px;
    }
    
    .order-details-card {
        padding: 25px 20px;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-timeline {
        flex-direction: column;
        gap: 20px;
    }
    
    .order-timeline::before {
        display: none;
    }
    
    .timeline-item {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
    }
    
    .timeline-icon {
        margin-right: 15px;
        margin-bottom: 0;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: center;
    }
    
    .item-image {
        margin: 0 auto 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .btn {
        width: 100%;
        max-width: 250px;
    }
    
    .time-card {
        flex-direction: column;
        text-align: center;
    }
    
    .time-icon {
        margin: 0 auto 20px;
    }
}
</style>
?>
