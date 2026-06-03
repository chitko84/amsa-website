<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Handle CRUD operations for point categories
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $categoryName = sanitize($_POST['category_name']);
        $points = intval($_POST['points']);
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO point_category (category_name, points, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $categoryName, $points, $description);
        if ($stmt->execute()) {
            $success = "Category added successfully!";
        } else {
            $error = "Failed to add category.";
        }
        $stmt->close();
    } elseif (isset($_POST['update_category'])) {
        $id = intval($_POST['category_id']);
        $categoryName = sanitize($_POST['category_name']);
        $points = intval($_POST['points']);
        $description = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE point_category SET category_name = ?, points = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sissi", $categoryName, $points, $description, $status, $id);
        if ($stmt->execute()) {
            $success = "Category updated successfully!";
        } else {
            $error = "Failed to update category.";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        $stmt = $conn->prepare("DELETE FROM point_category WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Failed to delete category.";
        }
        $stmt->close();
    }
}

// Get all categories
$result = $conn->query("SELECT * FROM point_category ORDER BY points DESC");
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Manage Point Categories</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        .category-card {
            border-radius: 15px;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .badge-active { background: #28a745; }
        .badge-inactive { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid bg-primary py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Manage Point Categories</h1>
            <p class="text-white">Configure activity types and their point values</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Points</label>
                                <input type="number" name="points" class="form-control" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Existing Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach($categories as $category): ?>
                            <div class="col-md-6">
                                <div class="card category-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                            <span class="badge <?php echo $category['status'] == 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo ucfirst($category['status']); ?>
                                            </span>
                                        </div>
                                        <h6 class="text-primary"><?php echo $category['points']; ?> points</h6>
                                        <p class="card-text small"><?php echo htmlspecialchars($category['description']); ?></p>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>', <?php echo $category['points']; ?>, '<?php echo htmlspecialchars($category['description']); ?>', '<?php echo $category['status']; ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" name="points" id="edit_points" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="delete_id">
                        <p>Are you sure you want to delete <strong id="delete_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone and will affect all related point requests.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(id, name, points, description, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_points').value = points;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_status').value = status;
            var myModal = new bootstrap.Modal(document.getElementById('editModal'));
            myModal.show();
        }
        
        function deleteCategory(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            myModal.show();
        }
    </script>
</body>
</html>