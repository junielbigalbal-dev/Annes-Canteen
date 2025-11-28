<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once 'config/database.php';

// Get order ID from URL
$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch order details with security check (user can only view their own orders)
$query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.address 
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id 
          WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Order not found or access denied";
    header('Location: index.php');
    exit();
}

$order = $result->fetch_assoc();

// Fetch order items
$items_query = "SELECT oi.*, mi.name, mi.image_url, mi.description 
               FROM order_items oi 
               JOIN menu_items mi ON oi.item_id = mi.item_id 
               WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Include header
include_once 'includes/header.php';
?>

<!-- Menu Page Section -->
<section class="menu-page-section">
    <div class="container">
        <!-- Page Header -->
        <div class="section-header">
            <h1>Order Details</h1>
            <p>Complete information about your order</p>
        </div>

        <!-- Order Header -->
        <div class="menu-item">
            <div class="order-header">
                <div class="order-info">
                    <h2>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                    <p class="order-date"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <div class="order-badges">
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                    <span class="payment-status status-<?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="order-details">
                <div class="order-info-section">
                    <h3><i class="fas fa-info-circle me-2"></i>Order Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Order Date:</strong><br>
                            <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?>
                        </div>
                        <div class="info-item">
                            <strong>Payment Method:</strong><br>
                            <?php echo ucfirst($order['payment_method']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Payment Status:</strong><br>
                            <span class="payment-status status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="order-info-section">
                    <h3><i class="fas fa-map-marker-alt me-2"></i>Delivery Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Name:</strong><br>
                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Address:</strong><br>
                            <?php echo htmlspecialchars($order['address']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="menu-item">
            <div class="order-header">
                <div class="order-info">
                    <h2>Order Items</h2>
                </div>
            </div>
            
            <div class="order-items-list">
                <?php 
                $subtotal = 0;
                while ($item = $items->fetch_assoc()): 
                    $item_total = $item['price'] * $item['quantity'];
                    $subtotal += $item_total;
                ?>
                    <div class="order-item-row">
                        <div class="item-image">
                            <?php if ($item['image_url']): ?>
                                <img src="assets/images/menu/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <img src="assets/images/placeholder.jpg" alt="No image">
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                        <div class="item-price">
                            <div class="price-info">
                                <span class="unit-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                <span class="quantity">× <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="total-price">₱<?php echo number_format($item_total, 2); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="menu-item">
            <div class="order-header">
                <div class="order-info">
                    <h2>Order Summary</h2>
                </div>
            </div>
            
            <div class="order-summary-section">
                <div class="summary-row">
                    <div class="summary-details">
                        <div class="summary-item">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Delivery Fee:</span>
                            <span>₱<?php echo number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Tax:</span>
                            <span>₱<?php echo number_format($order['tax_amount'], 2); ?></span>
                        </div>
                        <div class="summary-item total">
                            <span>Total Amount:</span>
                            <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-timeline">
                        <h3>Order Timeline</h3>
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Order Placed</h4>
                                    <small><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></small>
                                </div>
                            </div>
                            <?php if ($order['status'] !== 'pending'): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4>Order Confirmed</h4>
                                        <small><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($order['status'], ['preparing', 'ready', 'delivered'])): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4>Preparing</h4>
                                        <small>In progress</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($order['status'], ['ready', 'delivered'])): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4>Ready for Delivery</h4>
                                        <small>Ready to be delivered</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'delivered'): ?>
                                <div class="timeline-item completed">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4>Delivered</h4>
                                        <small>Order completed</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="order-actions-section">
            <a href="my_orders.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Print Order
                </button>
                <?php if ($order['status'] === 'pending'): ?>
                    <button onclick="cancelOrder(<?php echo $order['order_id']; ?>)" class="btn btn-outline-danger">
                        <i class="fas fa-times me-2"></i>Cancel Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
/* Order Details Page Styles */
.order-info-section {
    margin-bottom: 2rem;
}

.order-info-section h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    border-left: 4px solid var(--primary-color);
}

.info-item strong {
    color: var(--text-color);
    font-weight: 600;
}

/* Order Items List */
.order-items-list {
    padding: 1rem;
}

.order-item-row {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: var(--transition);
}

.order-item-row:hover {
    background: #f8f9fa;
}

.order-item-row:last-child {
    border-bottom: none;
}

.item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    margin-right: 1.5rem;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
    font-size: 1.1rem;
}

.item-details p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

.item-price {
    text-align: right;
    min-width: 120px;
}

.price-info {
    margin-bottom: 0.5rem;
}

.unit-price {
    display: block;
    color: var(--text-light);
    font-size: 0.9rem;
}

.quantity {
    color: var(--primary-color);
    font-weight: 600;
}

.total-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary-color);
}

/* Order Summary Section */
.order-summary-section {
    padding: 1.5rem;
}

.summary-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

.summary-details {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    background: var(--primary-color);
    color: white;
    margin: 1rem -1.5rem -1.5rem -1.5rem;
    padding: 1rem 1.5rem;
    border-radius: 0 0 10px 10px;
    font-weight: 700;
    font-size: 1.1rem;
}

.summary-item.total span {
    color: white;
}

/* Order Timeline */
.order-timeline {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.order-timeline h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--primary-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--primary-color);
    border: 3px solid white;
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
}

.timeline-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color);
}

.timeline-content small {
    color: var(--text-light);
    font-size: 0.85rem;
}

/* Action Buttons Section */
.order-actions-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 0;
    border-top: 1px solid #e9ecef;
    margin-top: 2rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
}

.action-buttons .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 500;
    transition: var(--transition);
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-item-row {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .item-image img {
        margin-right: 0;
    }
    
    .item-price {
        text-align: center;
        min-width: auto;
    }
    
    .summary-row {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .order-actions-section {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .order-actions-section {
        display: none !important;
    }
    
    .menu-item {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
        margin-bottom: 1rem !important;
    }
    
    .timeline {
        display: none;
    }
}
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        $.ajax({
            url: 'ajax/cancel_order.php',
            method: 'POST',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Order cancelled successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Error cancelling order');
                }
            },
            error: function() {
                alert('Error cancelling order');
            }
        });
    }
}

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'preparing': return 'primary';
        case 'ready': return 'success';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>
</script>

<?php include_once 'includes/footer.php'; ?>
