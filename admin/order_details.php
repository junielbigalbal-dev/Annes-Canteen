<?php
// Start session and check if user is logged in and is admin
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

require_once '../config/database.php';

// Get order ID
$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        
        if (in_array($new_status, ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'])) {
            $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Order status updated successfully";
        } else {
            $_SESSION['error'] = "Invalid status";
        }
        
        header("Location: order_details.php?id=$order_id");
        exit();
    }
}

// Get order details
$order_query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.address
                FROM orders o 
                JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
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
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();

// Set page title
$page_title = "Order Details - #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);

// Include admin header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Orders
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Order Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h5>
                            <small>Placed on <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></small>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'preparing' => 'secondary',
                                    'ready' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?> fs-6">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order['contact_number']); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                            <?php if (!empty($order['special_instructions'])): ?>
                                <p><strong>Special Instructions:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0">Update Order Status</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <select name="status" class="form-select" required>
                                    <?php
                                    $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
                                    foreach ($statuses as $status) {
                                        $selected = $order['status'] === $status ? 'selected' : '';
                                        echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sync me-1"></i>Update Status
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0">Order Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while ($item = $items->fetch_assoc()): 
                                    $item_total = $item['unit_price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image_url'])): ?>
                                                    <img src="../assets/images/menu/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 5px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['name'] ?? 'Menu Item'); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?>
                                            </small>
                                        </td>
                                        <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><strong>₱<?php echo number_format($item_total, 2); ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">Subtotal</th>
                                    <th>₱<?php echo number_format($subtotal, 2); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="4">Delivery Fee</th>
                                    <th>₱0.00</th>
                                </tr>
                                <tr>
                                    <th colspan="4">Service Fee</th>
                                    <th>₱0.00</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="4">Total Amount</th>
                                    <th>₱<?php echo number_format($order['total_amount'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0">Order Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Order Placed</h6>
                                <small class="text-muted"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></small>
                            </div>
                        </div>
                        
                        <?php if (in_array($order['status'], ['confirmed', 'preparing', 'ready', 'delivered'])): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Order Confirmed</h6>
                                    <small class="text-muted">Processing your order</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['preparing', 'ready', 'delivered'])): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Preparing</h6>
                                    <small class="text-muted">Your food is being prepared</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['ready', 'delivered'])): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Ready for Delivery</h6>
                                    <small class="text-muted">Your order is ready for delivery</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'delivered'): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Delivered</h6>
                                    <small class="text-muted">Order completed successfully</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'cancelled'): ?>
                            <div class="timeline-item cancelled">
                                <div class="timeline-marker bg-danger"></div>
                                <div class="timeline-content">
                                    <h6 class="text-danger">Order Cancelled</h6>
                                    <small class="text-muted">Order was cancelled</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    top: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #28a745;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-item.cancelled .timeline-marker {
    background: #dc3545;
}

.timeline-content h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
}

.timeline-item.completed .timeline-content h6 {
    color: #28a745;
}

.timeline-item.cancelled .timeline-content h6 {
    color: #dc3545;
}

@media print {
    .btn-toolbar, .sidebar, nav {
        display: none !important;
    }
    
    main {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
    }
}
</style>
?>
