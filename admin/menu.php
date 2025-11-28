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
$page_title = "Menu Management";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Add new menu item
        $category_id = intval($_POST['category_id']);
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = floatval($_POST['price']);
        $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/menu/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $file_name;
            }
        }
        
        $query = "INSERT INTO menu_items (category_id, name, description, price, is_vegetarian, is_featured, image_url, is_available) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issdiiss", $category_id, $name, $description, $price, $is_vegetarian, $is_featured, $image_url, $is_available);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item added successfully!";
        } else {
            $_SESSION['error'] = "Error adding menu item: " . $conn->error;
        }
        
        header('Location: menu.php');
        exit();
        
    } elseif ($action === 'edit') {
        // Update existing menu item
        $item_id = intval($_POST['item_id']);
        $category_id = intval($_POST['category_id']);
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = floatval($_POST['price']);
        $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Handle image upload
        $image_update = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/menu/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_update = ", image_url = ?";
            }
        }
        
        $query = "UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, 
                  is_vegetarian = ?, is_featured = ?, is_available = ? $image_update WHERE item_id = ?";
        
        if ($image_update) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issdiissi", $category_id, $name, $description, $price, $is_vegetarian, $is_featured, $is_available, $file_name, $item_id);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issdiisi", $category_id, $name, $description, $price, $is_vegetarian, $is_featured, $is_available, $item_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating menu item: " . $conn->error;
        }
        
        header('Location: menu.php');
        exit();
        
    } elseif ($action === 'delete') {
        // Delete menu item
        $item_id = intval($_POST['item_id']);
        
        $query = "DELETE FROM menu_items WHERE item_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting menu item: " . $conn->error;
        }
        
        header('Location: menu.php');
        exit();
    }
}

// Get categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get menu items
$menu_items = [];
$query = "SELECT mi.*, c.name as category_name 
          FROM menu_items mi 
          LEFT JOIN categories c ON mi.category_id = c.category_id 
          ORDER BY c.name, mi.name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
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
                <h1 class="h2">Menu Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-1"></i>Add Menu Item
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

            <!-- Menu Items Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0">Menu Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="menuTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['image_url']): ?>
                                                <img src="../assets/images/menu/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <img src="../assets/images/default-food.jpg" 
                                                     alt="No image"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <?php if ($item['is_featured']): ?>
                                                <span class="badge bg-warning">Featured</span>
                                            <?php endif; ?>
                                            <?php if ($item['is_vegetarian']): ?>
                                                <span class="badge bg-success">Veg</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></small>
                                        </td>
                                        <td><strong>₱<?php echo number_format($item['price'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['is_available'] ? 'success' : 'danger'; ?>">
                                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editItem(<?php echo $item['item_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteItem(<?php echo $item['item_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_vegetarian" class="form-check-input" id="addVegetarian">
                                <label class="form-check-label" for="addVegetarian">Vegetarian</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="addFeatured">
                                <label class="form-check-label" for="addFeatured">Featured</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" id="addAvailable" checked>
                                <label class="form-check-label" for="addAvailable">Available</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="item_id" id="editItemId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" id="editCategory" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="editName" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" id="editDescription" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" step="0.01" class="form-control" id="editPrice" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_vegetarian" class="form-check-input" id="editVegetarian">
                                <label class="form-check-label" for="editVegetarian">Vegetarian</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="editFeatured">
                                <label class="form-check-label" for="editFeatured">Featured</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_available" class="form-check-input" id="editAvailable">
                                <label class="form-check-label" for="editAvailable">Available</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_id" id="deleteItemId">
                <div class="modal-body">
                    <p>Are you sure you want to delete this menu item?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#menuTable').DataTable({
        "pageLength": 25,
        "responsive": true
    });
});

function editItem(itemId) {
    // Fetch item data
    $.ajax({
        url: 'ajax/get_menu_item.php',
        method: 'POST',
        data: { item_id: itemId },
        dataType: 'json',
        success: function(data) {
            $('#editItemId').val(data.item_id);
            $('#editCategory').val(data.category_id);
            $('#editName').val(data.name);
            $('#editDescription').val(data.description);
            $('#editPrice').val(data.price);
            $('#editVegetarian').prop('checked', data.is_vegetarian);
            $('#editFeatured').prop('checked', data.is_featured);
            $('#editAvailable').prop('checked', data.is_available);
            
            $('#editModal').modal('show');
        },
        error: function() {
            alert('Error loading item data');
        }
    });
}

function deleteItem(itemId) {
    $('#deleteItemId').val(itemId);
    $('#deleteModal').modal('show');
}
</script>
?>
