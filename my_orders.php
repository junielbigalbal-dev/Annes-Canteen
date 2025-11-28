<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once 'config/database.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';

// Build base query
$query = "SELECT o.*, COUNT(oi.item_id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.order_id = oi.order_id 
          WHERE o.user_id = ?";

$params = [$_SESSION['user_id']];
$where_clauses = [];

// Add status filter
if ($status_filter !== 'all') {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
}

// Add WHERE clause if needed
if (!empty($where_clauses)) {
    $query .= " AND " . implode(" AND ", $where_clauses);
}

$query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

// Prepare and execute
$types = str_repeat('i', count($params));
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Include header
include_once 'includes/header.php';
?>

<!-- Menu Page Section -->
<section class="menu-page-section">
    <div class="container">
        <!-- Page Header -->
        <div class="section-header">
            <h1>My Orders</h1>
            <p>Track and manage your food orders</p>
        </div>

        <!-- Filter Tabs -->
        <div class="menu-controls">
            <div class="filter-options">
                <select id="statusFilter" class="form-select filter-select" onchange="window.location.href='my_orders.php?status=' + this.value">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                </a>
            </div>
        </div>

        <!-- Orders List -->
        <div class="menu-grid">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="menu-item order-item">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                <p class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
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
                            <div class="order-summary">
                                <p><i class="fas fa-utensils me-2"></i><strong><?php echo $order['item_count']; ?></strong> items</p>
                                <p><i class="fas fa-tag me-2"></i><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></p>
                                <p><i class="fas fa-credit-card me-2"></i><?php echo ucfirst($order['payment_method']); ?></p>
                            </div>
                            
                            <div class="order-actions">
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button onclick="cancelOrder(<?php echo $order['order_id']; ?>)" class="btn btn-outline-danger">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-shopping-bag fa-3x"></i>
                    <h3>No Orders Found</h3>
                    <p>
                        <?php if ($status_filter === 'all'): ?>
                            You haven't placed any orders yet.
                        <?php else: ?>
                            You don't have any <?php echo $status_filter; ?> orders.
                        <?php endif; ?>
                    </p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

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
</script>

<style>
/* Order Item Styles */
.order-item {
    background: white;
    border-radius: 15px;
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: var(--transition);
    border: 1px solid #f0f0f0;
}

.order-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.order-header {
    background: linear-gradient(135deg, var(--primary-color), #ff5252);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.order-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.order-date {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.order-badges {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}

.order-status, .payment-status {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d1ecf1; color: #0c5460; }
.status-preparing { background: #cce5ff; color: #004085; }
.status-ready { background: #d4edda; color: #155724; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }

.order-details {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-summary p {
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    color: var(--text-color);
}

.order-summary i {
    color: var(--primary-color);
    width: 20px;
}

.order-actions {
    display: flex;
    gap: 0.5rem;
    flex-direction: column;
}

.order-actions .btn {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 500;
    transition: var(--transition);
    min-width: 120px;
}

.order-actions .btn:hover {
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-badges {
        align-items: flex-start;
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .order-details {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .order-actions .btn {
        flex: 1;
    }
}
</style>

<?php include_once 'includes/footer.php'; ?>
