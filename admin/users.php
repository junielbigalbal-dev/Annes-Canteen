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
$page_title = "Manage Users";

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add':
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Validate input
            if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
                $_SESSION['error'] = "Please fill in all required fields";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Invalid email format";
            } elseif (strlen($password) < 6) {
                $_SESSION['error'] = "Password must be at least 6 characters";
            } else {
                // Check if email or username already exists
                $check_query = "SELECT user_id FROM users WHERE email = ? OR username = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("ss", $email, $username);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $_SESSION['error'] = "Email or username already exists";
                } else {
                    // Hash password and create user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert_query = "INSERT INTO users (first_name, last_name, email, username, password_hash, phone, address, is_admin, created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ssssssssi", $first_name, $last_name, $email, $username, $password_hash, $phone, $address, $is_admin);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "User created successfully";
                    } else {
                        $_SESSION['error'] = "Error creating user";
                    }
                }
            }
            header('Location: users.php');
            exit();
            break;
            
        case 'edit':
            $user_id = intval($_POST['user_id'] ?? 0);
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $username = $_POST['username'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            if ($user_id > 0 && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($username)) {
                // Check if email or username already exists for other users
                $check_query = "SELECT user_id FROM users WHERE (email = ? OR username = ?) AND user_id != ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("ssi", $email, $username, $user_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $_SESSION['error'] = "Email or username already exists";
                } else {
                    $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, username = ?, phone = ?, address = ?, is_admin = ?, updated_at = NOW() 
                                    WHERE user_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ssssssii", $first_name, $last_name, $email, $username, $phone, $address, $is_admin, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "User updated successfully";
                    } else {
                        $_SESSION['error'] = "Error updating user";
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid user data";
            }
            header('Location: users.php');
            exit();
            break;
            
        case 'delete':
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id > 0) {
                // Prevent deleting the current admin
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['error'] = "Cannot delete your own account";
                } else {
                    // Check if user has orders
                    $order_check = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
                    $order_stmt = $conn->prepare($order_check);
                    $order_stmt->bind_param("i", $user_id);
                    $order_stmt->execute();
                    $order_count = $order_stmt->get_result()->fetch_assoc()['count'];
                    
                    if ($order_count > 0) {
                        $_SESSION['error'] = "Cannot delete user with existing orders";
                    } else {
                        $delete_query = "DELETE FROM users WHERE user_id = ?";
                        $stmt = $conn->prepare($delete_query);
                        $stmt->bind_param("i", $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "User deleted successfully";
                        } else {
                            $_SESSION['error'] = "Error deleting user";
                        }
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid user ID";
            }
            header('Location: users.php');
            exit();
            break;
            
        case 'reset_password':
            $user_id = intval($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            
            if ($user_id > 0 && !empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $_SESSION['error'] = "Password must be at least 6 characters";
                } else {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("si", $password_hash, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Password reset successfully";
                    } else {
                        $_SESSION['error'] = "Error resetting password";
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid user data";
            }
            header('Location: users.php');
            exit();
            break;
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';

// Build base query
$query = "SELECT user_id, first_name, last_name, email, username, phone, address, is_admin, created_at, updated_at 
          FROM users";

$params = [];
$where_clauses = [];

// Add search filter
if (!empty($search)) {
    $where_clauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add role filter
if ($role_filter !== 'all') {
    $where_clauses[] = "is_admin = ?";
    $params[] = $role_filter === 'admin' ? 1 : 0;
}

// Add WHERE clause if needed
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query($query);
}

// Get user statistics
$stats_query = "SELECT 
                  COUNT(*) as total_users,
                  SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_users,
                  SUM(CASE WHEN is_admin = 0 THEN 1 ELSE 0 END) as customer_users,
                  SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users
                FROM users";
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
                <h1 class="h2">Manage Users</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-1"></i>Add User
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

            <!-- User Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total_users']; ?></h5>
                            <p class="card-text small">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['admin_users']; ?></h5>
                            <p class="card-text small">Admin Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['customer_users']; ?></h5>
                            <p class="card-text small">Customers</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['new_users']; ?></h5>
                            <p class="card-text small">New (30 days)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or username" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role Filter</label>
                            <select name="role" class="form-select">
                                <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin Users</option>
                                <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customers</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($user['user_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <?php if (!empty($user['address'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($user['address'], 0, 30)) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </td>
                                        <td>
                                            <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['is_admin'] ? 'warning' : 'primary'; ?>">
                                                <?php echo $user['is_admin'] ? 'Admin' : 'Customer'; ?>
                                            </span>
                                            <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                                <br><small class="text-muted">(You)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($user['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" onclick="resetPassword(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-outline-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin">
                            <label class="form-check-label" for="is_admin">
                                Admin User
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="edit_is_admin">
                            <label class="form-check-label" for="edit_is_admin">
                                Admin User
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="resetPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Users with existing orders cannot be deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "responsive": true
    });
});

function editUser(userId) {
    // Fetch user data via AJAX
    $.ajax({
        url: 'ajax/get_user.php',
        method: 'POST',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#edit_user_id').val(response.user.user_id);
                $('#edit_first_name').val(response.user.first_name);
                $('#edit_last_name').val(response.user.last_name);
                $('#edit_email').val(response.user.email);
                $('#edit_username').val(response.user.username);
                $('#edit_phone').val(response.user.phone);
                $('#edit_address').val(response.user.address);
                $('#edit_is_admin').prop('checked', response.user.is_admin == 1);
                
                $('#editModal').modal('show');
            } else {
                alert('Error loading user data');
            }
        },
        error: function() {
            alert('Error loading user data');
        }
    });
}

function resetPassword(userId) {
    $('#reset_user_id').val(userId);
    $('#resetPasswordModal').modal('show');
}

function deleteUser(userId) {
    $('#delete_user_id').val(userId);
    $('#deleteModal').modal('show');
}

// Password confirmation check
$('#resetPasswordForm').on('submit', function(e) {
    const newPassword = $('input[name="new_password"]').val();
    const confirmPassword = $('#confirm_password').val();
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    return true;
});
</script>
?>
