<?php
// Start session and check if user is logged in and is admin
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?admin=1');
    exit();
}

require_once '../config/database.php';

// Set page title
$page_title = "Manage Orders";

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        if ($order_id > 0 && in_array($new_status, ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'])) {
            $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Order status updated successfully";
        } else {
            $_SESSION['error'] = "Invalid order data";
        }
        
        header('Location: orders.php');
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';

// Build base query
$query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, u.phone
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id";

$params = [];
$where_clauses = [];

// Add status filter
if ($status_filter !== 'all') {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
}

// Add date filter
if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_clauses[] = "DATE(o.order_date) = CURDATE()";
            break;
        case 'week':
            $where_clauses[] = "YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $where_clauses[] = "MONTH(o.order_date) = MONTH(CURDATE()) AND YEAR(o.order_date) = YEAR(CURDATE())";
            break;
    }
}

// Add WHERE clause if needed
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY o.order_date DESC";

// Prepare and execute
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query($query);
}

// Get order statistics
$stats_query = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                  SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                  SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
                  SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders,
                  SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                  SUM(total_amount) as total_revenue
                FROM orders";
$stats = $conn->query($stats_query)->fetch_assoc();

// Include admin header
include_once 'includes/header.php';
?>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include_once 'includes/sidebar.php'; ?>
    
    <!-- Content Wrapper -->
    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Orders</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportOrders()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Order Statistics -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total_orders']; ?></h5>
                            <p class="card-text small">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['pending_orders']; ?></h5>
                            <p class="card-text small">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['confirmed_orders']; ?></h5>
                            <p class="card-text small">Confirmed</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-secondary">
                        <div class="card-body">
                            <h5 class="card-title text-secondary"><?php echo $stats['preparing_orders']; ?></h5>
                            <p class="card-text small">Preparing</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['delivered_orders']; ?></h5>
                            <p class="card-text small">Delivered</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card text-center border-danger">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo $stats['cancelled_orders']; ?></h5>
                            <p class="card-text small">Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status Filter</label>
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Filter</label>
                            <select name="date" class="form-select">
                                <option value="" <?php echo empty($date_filter) ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Orders List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['phone']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            // Get item count for this order
                                            $item_count_query = "SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM order_items WHERE order_id = ?";
                                            $item_stmt = $conn->prepare($item_count_query);
                                            $item_stmt->bind_param("i", $order['order_id']);
                                            $item_stmt->execute();
                                            $item_data = $item_stmt->get_result()->fetch_assoc();
                                            echo $item_data['count'] . ' items (' . $item_data['total_qty'] . ' qty)';
                                            ?>
                                        </td>
                                        <td>
                                            <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                            <br>
                                            <small><?php echo ucfirst($order['payment_method']); ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <?php
                                                    $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
                                                    foreach ($statuses as $status) {
                                                        $selected = $order['status'] === $status ? 'selected' : '';
                                                        echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-info" onclick="printOrder(<?php echo $order['order_id']; ?>)" title="Print">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "responsive": true
    });
});

function exportOrders() {
    window.location.href = 'export_orders.php?status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>';
}

function printOrder(orderId) {
    window.open('print_order.php?id=' + orderId, '_blank', 'width=800,height=600');
}

<?php if (isset($_SESSION['success'])): ?>
    showNotification('<?php echo $_SESSION['success']; ?>', 'success');
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    showNotification('<?php echo $_SESSION['error']; ?>', 'error');
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

function showNotification(message, type) {
    const notification = document.createElement('div');
    
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
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
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
