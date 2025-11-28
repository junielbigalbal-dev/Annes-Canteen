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
$page_title = "Categories Management";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Add new category
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/categories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $file_name;
            }
        }
        
        $query = "INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $description, $image_url);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully!";
        } else {
            $_SESSION['error'] = "Error adding category: " . $conn->error;
        }
        
        header('Location: categories.php');
        exit();
        
    } elseif ($action === 'edit') {
        // Update category
        $category_id = intval($_POST['category_id']);
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Handle image upload
        $image_update = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/categories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_update = ", image_url = ?";
            }
        }
        
        $query = "UPDATE categories SET name = ?, description = ? $image_update WHERE category_id = ?";
        
        if ($image_update) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $name, $description, $file_name, $category_id);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $description, $category_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating category: " . $conn->error;
        }
        
        header('Location: categories.php');
        exit();
        
    } elseif ($action === 'delete') {
        // Delete category
        $category_id = intval($_POST['category_id']);
        
        // Check if category has menu items
        $check_query = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $count = $check_stmt->get_result()->fetch_assoc()['count'];
        
        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete category. It has menu items associated with it.";
        } else {
            $query = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Category deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting category: " . $conn->error;
            }
        }
        
        header('Location: categories.php');
        exit();
    }
}

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
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
                <h1 class="h2">Categories Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus me-1"></i>Add Category
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

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0">Categories</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Menu Items</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <?php if ($category['image_url']): ?>
                                                <img src="../assets/images/categories/<?php echo htmlspecialchars($category['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <img src="../assets/images/default-category.jpg" 
                                                     alt="No image"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($category['description'], 0, 50)) . '...'; ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $item_count_query = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?";
                                            $item_count_stmt = $conn->prepare($item_count_query);
                                            $item_count_stmt->bind_param("i", $category['category_id']);
                                            $item_count_stmt->execute();
                                            $item_count = $item_count_stmt->get_result()->fetch_assoc()['count'];
                                            echo $item_count;
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $category['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editCategory(<?php echo $category['category_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>)">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="editCategoryId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" id="editDescription" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
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
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="deleteCategoryId">
                <div class="modal-body">
                    <p>Are you sure you want to delete this category?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        "pageLength": 25,
        "responsive": true
    });
});

function editCategory(categoryId) {
    // Fetch category data
    $.ajax({
        url: 'ajax/get_category.php',
        method: 'POST',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(data) {
            $('#editCategoryId').val(data.category_id);
            $('#editName').val(data.name);
            $('#editDescription').val(data.description);
            
            $('#editModal').modal('show');
        },
        error: function() {
            alert('Error loading category data');
        }
    });
}

function deleteCategory(categoryId) {
    $('#deleteCategoryId').val(categoryId);
    $('#deleteModal').modal('show');
}
</script>
?>
