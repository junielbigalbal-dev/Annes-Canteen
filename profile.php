<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's orders
$orders_query = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                 WHERE o.user_id = ? 
                 GROUP BY o.order_id 
                 ORDER BY o.order_date DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

// Get user's addresses
$addresses_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$addresses_stmt = $conn->prepare($addresses_query);
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses = $addresses_stmt->get_result();

$page_title = "My Profile";
include_once 'includes/header.php';
?>

<!-- Profile Section -->
<section class="profile-section">
    <div class="container">
        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="profile-info">
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($user['address'] ?? 'No address provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar"></i>
                            <span>Member since <?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-1"></i>Edit Profile
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-1"></i>Change Password
                        </button>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="stats-card">
                    <h5>Order Statistics</h5>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $orders->num_rows; ?></span>
                            <span class="stat-label">Total Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">₱<?php 
                                $total_spent = 0;
                                $orders->data_seek(0);
                                while ($order = $orders->fetch_assoc()) {
                                    $total_spent += $order['total_amount'];
                                }
                                echo number_format($total_spent, 2);
                            ?></span>
                            <span class="stat-label">Total Spent</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div class="col-lg-8">
                <div class="orders-section">
                    <div class="section-header">
                        <h4>My Orders</h4>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                    </div>
                    
                    <?php if ($orders->num_rows > 0): ?>
                        <div class="orders-list">
                            <?php 
                            $orders->data_seek(0);
                            while ($order = $orders->fetch_assoc()): 
                            ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h6>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></small>
                                        </div>
                                        <div class="order-status">
                                            <span class="badge bg-<?php 
                                                $status_colors = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'preparing' => 'primary',
                                                    'ready' => 'success',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                echo $status_colors[$order['status']] ?? 'secondary';
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-items">
                                            <span class="item-count"><?php echo $order['item_count']; ?> items</span>
                                            <span class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="order-actions">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <?php if ($order['status'] === 'delivered'): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="reorderItems(<?php echo $order['order_id']; ?>)">
                                                    <i class="fas fa-redo me-1"></i>Reorder
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-orders">
                            <i class="fas fa-shopping-bag"></i>
                            <h5>No orders yet</h5>
                            <p>You haven't placed any orders yet. Start ordering from our menu!</p>
                            <a href="menu.php" class="btn btn-primary">
                                <i class="fas fa-utensils me-2"></i>Browse Menu
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Addresses Section -->
                <div class="addresses-section">
                    <div class="section-header">
                        <h4>Delivery Addresses</h4>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="fas fa-plus me-1"></i>Add Address
                        </button>
                    </div>
                    
                    <?php if ($addresses->num_rows > 0): ?>
                        <div class="addresses-list">
                            <?php while ($address = $addresses->fetch_assoc()): ?>
                                <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                                    <div class="address-header">
                                        <h6><?php echo ucfirst($address['address_type']); ?></h6>
                                        <?php if ($address['is_default']): ?>
                                            <span class="badge bg-primary">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-details">
                                        <p><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                        <?php if (!empty($address['address_line2'])): ?>
                                            <p><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                        <?php endif; ?>
                                        <p><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?></p>
                                        <p><?php echo htmlspecialchars($address['country']); ?></p>
                                    </div>
                                    <div class="address-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editAddress(<?php echo $address['address_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (!$address['is_default']): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="setDefaultAddress(<?php echo $address['address_id']; ?>)">
                                                <i class="fas fa-star"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAddress(<?php echo $address['address_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-addresses">
                            <i class="fas fa-map-marker-alt"></i>
                            <h5>No addresses saved</h5>
                            <p>Add your delivery addresses for faster checkout!</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="fas fa-plus me-2"></i>Add Address
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProfileForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Delivery Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAddressForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Address Type</label>
                        <select class="form-select" name="address_type" required>
                            <option value="home">Home</option>
                            <option value="work">Work</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" name="address_line1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" name="address_line2">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" name="country" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="isDefault">
                            <label class="form-check-label" for="isDefault">
                                Set as default address
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once 'includes/footer.php';
$conn->close();
?>

<style>
.profile-section {
    padding: 100px 0 50px;
    background-color: #f8f9fa;
}

.profile-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.profile-header {
    text-align: center;
    margin-bottom: 25px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 2.5rem;
    color: white;
}

.profile-header h3 {
    margin-bottom: 5px;
    color: #2c3e50;
}

.profile-info .info-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.profile-info .info-item i {
    width: 20px;
    color: #667eea;
    margin-right: 15px;
}

.profile-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #667eea;
}

.stat-label {
    display: block;
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 5px;
}

.orders-section, .addresses-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.order-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.order-info h6 {
    margin: 0;
    color: #2c3e50;
}

.order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-items {
    display: flex;
    gap: 20px;
    align-items: center;
}

.item-count {
    color: #6c757d;
}

.order-total {
    font-weight: 600;
    color: #667eea;
    font-size: 1.1rem;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.empty-orders, .empty-addresses {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-orders i, .empty-addresses i {
    font-size: 3rem;
    margin-bottom: 20px;
    color: #dee2e6;
}

.address-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.address-card.default {
    border-color: #667eea;
    background: linear-gradient(to right, rgba(102, 126, 234, 0.05), rgba(102, 126, 234, 0.02));
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.address-details p {
    margin: 5px 0;
    color: #6c757d;
}

.address-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .profile-section {
        padding: 80px 0 30px;
    }
    
    .order-details, .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Edit Profile Form
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('ajax/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Profile updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating profile', 'error');
    });
});

// Change Password Form
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showNotification('Passwords do not match', 'error');
        return;
    }
    
    fetch('ajax/change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Password changed successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            this.reset();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error changing password', 'error');
    });
});

// Add Address Form
document.getElementById('addAddressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('ajax/add_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Address added successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addAddressModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error adding address', 'error');
    });
});

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

// Set Default Address
function setDefaultAddress(addressId) {
    fetch('ajax/set_default_address.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `address_id=${addressId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Default address updated!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating default address', 'error');
    });
}

// Delete Address
function deleteAddress(addressId) {
    if (confirm('Are you sure you want to delete this address?')) {
        fetch('ajax/delete_address.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `address_id=${addressId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Address deleted!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting address', 'error');
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
