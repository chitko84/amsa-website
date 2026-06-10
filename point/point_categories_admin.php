<?php
require_once '../config/database.php';
requireAdmin('../admin/login.php');

// Handle CRUD operations for point categories
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken()) {
        $error = "Your session token expired. Please try again.";
    } elseif (isset($_POST['add_category'])) {
        $categoryName = sanitize($_POST['category_name']);
        $points = intval($_POST['points']);
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO point_category (category_name, points, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $categoryName, $points, $description);
        if ($stmt->execute()) {
            $success = "Category added successfully!";
            logAuditAction('point_category_create', 'point_category', $conn->insert_id, null, ['category_name' => $categoryName, 'points' => $points]);
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
        if (!in_array($status, ['active', 'inactive'], true)) {
            $error = "Invalid category status.";
        } else {
        
        $stmt = $conn->prepare("UPDATE point_category SET category_name = ?, points = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sissi", $categoryName, $points, $description, $status, $id);
        if ($stmt->execute()) {
            $success = "Category updated successfully!";
            logAuditAction('point_category_update', 'point_category', $id, null, ['category_name' => $categoryName, 'points' => $points, 'status' => $status]);
        } else {
            $error = "Failed to update category.";
        }
        $stmt->close();
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        $inactiveStatus = 'inactive';
        $stmt = $conn->prepare("UPDATE point_category SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $inactiveStatus, $id);
        if ($stmt->execute()) {
            $success = "Category disabled successfully!";
            logAuditAction('point_category_disable', 'point_category', $id);
        } else {
            $error = "Failed to disable category.";
        }
        $stmt->close();
    } elseif (isset($_POST['enable_category'])) {
        $id = intval($_POST['category_id']);
        $activeStatus = 'active';
        $stmt = $conn->prepare("UPDATE point_category SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $activeStatus, $id);
        if ($stmt->execute()) {
            $success = "Category enabled successfully!";
            logAuditAction('point_category_enable', 'point_category', $id);
        } else {
            $error = "Failed to enable category.";
        }
        $stmt->close();
    } elseif (isset($_POST['permanent_delete_category'])) {
        $id = intval($_POST['category_id']);

        $stmt = $conn->prepare("DELETE FROM point_category WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "Category permanently deleted successfully!";
                logAuditAction('point_category_permanent_delete', 'point_category', $id);
            } else {
                $error = "Category not found or already deleted.";
            }
        } else {
            $error = "Failed to permanently delete category. It may be linked to existing point requests. Disable it instead if you need to keep history safe.";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_all_categories'])) {
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM point_category");
        $totalBeforeDelete = 0;
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $totalBeforeDelete = (int) ($countRow['total'] ?? 0);
        }

        if ($conn->query("DELETE FROM point_category")) {
            $success = "All point categories permanently deleted successfully!";
            logAuditAction('point_category_delete_all', 'point_category', null, null, ['deleted_count' => $totalBeforeDelete]);
        } else {
            $error = "Failed to delete all categories. Some categories may be linked to existing point requests. Disable categories instead if you need to keep history safe.";
        }
    }
}

// Get all categories
$result = $conn->query("SELECT * FROM point_category ORDER BY status ASC, points DESC");
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Manage Point Categories</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
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
        .badge-active { background: var(--amsa-color-success, #2f8f57); color: #fff; }
        .badge-inactive { background: var(--amsa-color-inactive, #8f7a72); color: #fff; }
        .danger-zone-btn {
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .category-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .danger-note {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
            padding: 12px 14px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Manage Point Categories</h1>
            <p class="text-white">Configure activity types and their point values</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if(isset($success)): ?>
            <div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm amsa-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo csrfInput(); ?>
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control amsa-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Points</label>
                                <input type="number" name="points" class="form-control amsa-form-control" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control amsa-form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary amsa-btn amsa-btn-primary w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm amsa-card">
                    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h5 class="mb-0">Existing Categories</h5>
                        <?php if (!empty($categories)): ?>
                            <button type="button" class="btn btn-sm btn-danger amsa-btn amsa-btn-danger danger-zone-btn" onclick="confirmDeleteAllCategories()">
                                <i class="fas fa-trash-alt"></i> Delete All Categories
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="amsa-empty-state">
                                <i class="fas fa-list fa-2x mb-3 text-primary"></i>
                                <h4>No Categories Yet</h4>
                                <p class="mb-0">Add the first category so members can submit point requests.</p>
                            </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach($categories as $category): ?>
                            <div class="col-md-6">
                                <div class="card category-card amsa-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                                            <span class="badge amsa-badge <?php echo $category['status'] == 'active' ? 'badge-active amsa-badge-active' : 'badge-inactive amsa-badge-inactive'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($category['status'])); ?>
                                            </span>
                                        </div>
                                        <h6 class="text-primary"><?php echo (int) $category['points']; ?> points</h6>
                                        <p class="card-text small"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                                        <div class="category-actions">
                                            <button class="btn btn-sm btn-outline-primary amsa-btn amsa-btn-ghost amsa-btn-sm"
                                                onclick='editCategory(<?php echo json_encode([
                                                    'id' => (int) $category['id'],
                                                    'name' => $category['category_name'],
                                                    'points' => (int) $category['points'],
                                                    'description' => $category['description'] ?? '',
                                                    'status' => $category['status'],
                                                ]); ?>)'>
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($category['status'] === 'active'): ?>
                                                <button class="btn btn-sm btn-outline-danger amsa-btn amsa-btn-danger amsa-btn-sm" onclick="disableCategory(<?php echo (int) $category['id']; ?>, <?php echo htmlspecialchars(json_encode($category['category_name']), ENT_QUOTES); ?>)">
                                                    <i class="fas fa-ban"></i> Disable
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="category_id" value="<?php echo (int) $category['id']; ?>">
                                                    <button type="submit" name="enable_category" class="btn btn-sm btn-outline-success amsa-btn amsa-btn-primary amsa-btn-sm">
                                                        <i class="fas fa-check"></i> Enable
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger amsa-btn amsa-btn-danger amsa-btn-sm" onclick="confirmPermanentDeleteCategory(<?php echo (int) $category['id']; ?>, <?php echo htmlspecialchars(json_encode($category['category_name']), ENT_QUOTES); ?>)">
                                                <i class="fas fa-trash-alt"></i> Permanent Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
                    <?php echo csrfInput(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" id="edit_name" class="form-control amsa-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" name="points" id="edit_points" class="form-control amsa-form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control amsa-form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select amsa-form-control">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary amsa-btn amsa-btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Disable Category Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <?php echo csrfInput(); ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="delete_id">
                        <p>Are you sure you want to disable <strong id="delete_name"></strong>?</p>
                        <p class="text-muted">Disabled categories are hidden from member submissions but existing request history remains intact.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_category" class="btn btn-danger amsa-btn amsa-btn-danger">Disable Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Permanent Delete Single Category Modal -->
    <div class="modal fade" id="permanentDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <?php echo csrfInput(); ?>
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Permanent Delete Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="permanent_delete_id">
                        <div class="danger-note mb-3">
                            <strong>Warning:</strong> This action will permanently remove this point category from the database.
                        </div>
                        <p>Are you sure you want to permanently delete <strong id="permanent_delete_name"></strong>?</p>
                        <p class="text-muted mb-0">This cannot be undone. If this category is connected to existing point requests, the deletion may fail to protect your records.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="permanent_delete_category" class="btn btn-danger amsa-btn amsa-btn-danger">
                            <i class="fas fa-trash-alt"></i> Permanently Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete All Categories Modal -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <?php echo csrfInput(); ?>
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete All Categories</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="danger-note mb-3">
                            <strong>Very dangerous action:</strong> This will permanently delete every point category from the database.
                        </div>
                        <p>Are you sure you want to delete <strong>all point categories</strong>?</p>
                        <p class="text-muted mb-0">This cannot be undone. If categories are connected to existing point requests, the deletion may fail to protect your records.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_all_categories" class="btn btn-danger amsa-btn amsa-btn-danger">
                            <i class="fas fa-trash-alt"></i> Yes, Delete All
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_points').value = category.points;
            document.getElementById('edit_description').value = category.description;
            document.getElementById('edit_status').value = category.status;
            var myModal = new bootstrap.Modal(document.getElementById('editModal'));
            myModal.show();
        }
        
        function disableCategory(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            myModal.show();
        }

        function confirmPermanentDeleteCategory(id, name) {
            document.getElementById('permanent_delete_id').value = id;
            document.getElementById('permanent_delete_name').textContent = name;
            var myModal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
            myModal.show();
        }

        function confirmDeleteAllCategories() {
            var myModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
            myModal.show();
        }
    </script>
</body>
</html>
