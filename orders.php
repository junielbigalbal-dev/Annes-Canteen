<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle status filter
$status_filter = $_GET['status'] ?? 'all';

// Get user's orders with filtering
$query = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.order_id = oi.order_id 
          WHERE o.user_id = ?";

$params = ["i", $user_id];

if ($status_filter !== 'all') {
    $query .= " AND o.status = ?";
    $params[0] .= "s";
    $params[] = $status_filter;
}

$query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param(...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Get order statistics
$stats_query = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(total_amount) as total_spent,
                  COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                  COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
                FROM orders WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$page_title = "My Orders";
include_once 'includes/header.php';
?>

<!-- Orders Section -->
<section class="orders-section">
    <div class="container">
        <!-- Page Header -->
        <div class="section-header">
            <h1>My Orders</h1>
            <p>View and track all your orders</p>
        </div>

        <!-- Order Statistics -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₱<?php echo number_format($stats['total_spent'] ?: 0, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['delivered_orders']; ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_orders']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <!-- Filter Options -->
        <div class="filter-section">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    All Orders
                </a>
                <a href="?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?status=confirmed" class="filter-tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                    Confirmed
                </a>
                <a href="?status=preparing" class="filter-tab <?php echo $status_filter === 'preparing' ? 'active' : ''; ?>">
                    Preparing
                </a>
                <a href="?status=ready" class="filter-tab <?php echo $status_filter === 'ready' ? 'active' : ''; ?>">
                    Ready
                </a>
                <a href="?status=delivered" class="filter-tab <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                    Delivered
                </a>
                <a href="?status=cancelled" class="filter-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                    Cancelled
                </a>
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-container">
            <?php if ($orders->num_rows > 0): ?>
                <div class="orders-grid">
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-number">
                                    <h4>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h4>
                                    <span class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></span>
                                </div>
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-content">
                                <div class="order-summary">
                                    <div class="order-items-count">
                                        <i class="fas fa-utensils"></i>
                                        <span><?php echo $order['item_count']; ?> items</span>
                                    </div>
                                    <div class="order-total">
                                        <span class="total-label">Total:</span>
                                        <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <div class="delivery-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 50)) . '...'; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['special_instructions'])): ?>
                                    <div class="special-instructions">
                                        <i class="fas fa-sticky-note"></i>
                                        <span><?php echo htmlspecialchars(substr($order['special_instructions'], 0, 60)) . '...'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-actions">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </button>
                                
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <button class="btn btn-outline-success btn-sm" onclick="reorderItems(<?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-redo me-1"></i>Reorder
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-orders">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3>No orders found</h3>
                    <p>
                        <?php 
                        if ($status_filter !== 'all') {
                            echo "You don't have any " . htmlspecialchars($status_filter) . " orders.";
                        } else {
                            echo "You haven't placed any orders yet.";
                        }
                        ?>
                    </p>
                    <a href="menu.php" class="btn btn-primary">
                        <i class="fas fa-utensils me-2"></i>Browse Menu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
include_once 'includes/footer.php';
$conn->close();
?>

<style>
.orders-section {
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

.section-header p {
    color: #6c757d;
    font-size: 1.1rem;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-info h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.filter-section {
    margin-bottom: 30px;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 5px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-tab {
    padding: 12px 20px;
    text-decoration: none;
    color: #6c757d;
    border-radius: 8px;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 500;
}

.filter-tab:hover {
    background: #f8f9fa;
    color: #667eea;
}

.filter-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.orders-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 25px;
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.order-number h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 1.2rem;
}

.order-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cfe2ff; color: #084298; }
.status-preparing { background: #d1ecf1; color: #0c5460; }
.status-ready { background: #d4edda; color: #155724; }
.status-delivered { background: #d1f2eb; color: #0f5132; }
.status-cancelled { background: #f8d7da; color: #842029; }

.order-content {
    margin-bottom: 20px;
}

.order-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.order-items-count {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
}

.order-total {
    display: flex;
    align-items: center;
    gap: 8px;
}

.total-label {
    color: #6c757d;
}

.total-amount {
    font-weight: 700;
    color: #667eea;
    font-size: 1.1rem;
}

.delivery-info, .special-instructions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: #6c757d;
    font-size: 0.9rem;
}

.delivery-info i, .special-instructions i {
    color: #667eea;
}

.order-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.empty-orders {
    text-align: center;
    padding: 80px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #dee2e6;
}

.empty-orders h3 {
    margin-bottom: 10px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .orders-section {
        padding: 80px 0 30px;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        margin-bottom: 15px;
    }
    
    .filter-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .order-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .order-actions {
        justify-content: flex-start;
    }
}
</style>

<script>
// View Order Details
function viewOrderDetails(orderId) {
    window.location.href = `order_details.php?id=${orderId}`;
}

// Reorder Items
function reorderItems(orderId) {
    if (confirm('Do you want to add all items from this order to your cart?')) {
        fetch(`ajax/reorder.php?order_id=${orderId}`, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Items added to cart!', 'success');
                updateCartCount(data.cart_count);
                setTimeout(() => location.href = 'cart.php', 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error reordering items', 'error');
        });
    }
}

// Cancel Order
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('ajax/cancel_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order cancelled successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error cancelling order', 'error');
        });
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    
    // Use website colors
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #00b894 0%, #00a085 100%)';
        notification.style.color = 'white';
    } else {
        notification.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%)';
        notification.style.color = 'white';
    }
    
    notification.style.position = 'fixed';
    notification.style.top = '100px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.padding = '15px 20px';
    notification.style.borderRadius = '10px';
    notification.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
    notification.style.fontFamily = 'Poppins, sans-serif';
    notification.style.fontSize = '0.95rem';
    notification.style.fontWeight = '500';
    notification.style.transform = 'translateX(400px)';
    notification.style.opacity = '0';
    notification.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    notification.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; opacity: 0.8; margin-left: 15px;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Remove notification after 4 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 400);
    }, 4000);
}
</script>
?>
