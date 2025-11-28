<?php
// Start session and check if user is logged in and is admin
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once '../config/database.php';

// Set page title
$page_title = "Admin Dashboard";

// Get counts for dashboard stats
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'total_products' => 0,
    'total_customers' => 0,
    'today_sales' => 0
];

// Get basic statistics
try {
    $stats['total_orders'] = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    $stats['pending_orders'] = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
    $stats['total_products'] = $conn->query("SELECT COUNT(*) as count FROM menu_items")->fetch_assoc()['count'];
    $stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
    
    $today_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'")->fetch_assoc()['total'];
    $stats['today_sales'] = number_format($today_sales, 2);
} catch (Exception $e) {
    // Handle errors gracefully
}

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
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="menu.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Menu Item
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title"><?php echo $stats['total_orders']; ?></h3>
                            <p class="card-text">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title"><?php echo $stats['pending_orders']; ?></h3>
                            <p class="card-text">Pending Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title"><?php echo $stats['total_products']; ?></h3>
                            <p class="card-text">Menu Items</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="card-title">₱<?php echo $stats['today_sales']; ?></h3>
                            <p class="card-text">Today's Sales</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="menu.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="fas fa-utensils fa-2x mb-2"></i>
                                        <span>Manage Menu</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="orders.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                        <span>Orders</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="users.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <span>Customers</span>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="categories.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="fas fa-tags fa-2x mb-2"></i>
                                        <span>Categories</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0">Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $recent_orders_query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
                                                            FROM orders o 
                                                            JOIN users u ON o.user_id = u.user_id 
                                                            ORDER BY o.order_date DESC LIMIT 5";
                                        $result = $conn->query($recent_orders_query);
                                        
                                        if ($result && $result->num_rows > 0) {
                                            while ($order = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>#" . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) . "</td>";
                                                echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
                                                echo "<td>" . date('M d, Y h:i A', strtotime($order['order_date'])) . "</td>";
                                                echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
                                                echo "<td><span class='badge bg-" . ($order['status'] == 'pending' ? 'warning' : 'success') . "'>" . ucfirst($order['status']) . "</span></td>";
                                                echo "<td><a href='order_details.php?id=" . $order['order_id'] . "' class='btn btn-sm btn-primary'><i class='fas fa-eye'></i></a></td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No orders found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
?>
