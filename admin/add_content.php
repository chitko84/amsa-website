<?php
require_once '../config/database.php';
requireAdmin('login.php');

$type = $_GET['type'] ?? '';
if (!in_array($type, ['achievement', 'testimonial'], true)) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $uploaded_by = currentUserId();

    if ($title === '' || $content === '') {
        $error = 'Title and content are required.';
    } elseif (!validateImageUploadSelection($_FILES['images'] ?? null, $uploadPreflightErrors)) {
        $error = implode(' ', $uploadPreflightErrors);
    } else {
        $stmt = $conn->prepare("INSERT INTO post (content, category, title, uploaded_by, upload_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $content, $type, $title, $uploaded_by);

        if ($stmt->execute()) {
            $post_id = $conn->insert_id;

            $uploadErrors = [];
            $uploadedImages = uploadMultipleImagesSecure($_FILES['images'] ?? null, '../uploads/', $uploadErrors);
            foreach ($uploadedImages as $file_name) {
                    $imgStmt = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
                    $imgStmt->bind_param("is", $post_id, $file_name);
                    $imgStmt->execute();
            }
            if (!empty($uploadErrors)) {
                $error = implode(' ', $uploadErrors);
            }

            if ($error === '') {
                logAuditAction('add_' . $type, 'post', $post_id, null, ['title' => $title, 'category' => $type]);
                header('Location: dashboard.php?msg=added');
                exit();
            }
        }

        if ($error === '') {
            $error = 'Failed to save content.';
        }
    }
    }
}

$pageTitle = 'Add ' . ucfirst($type);
include 'includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card amsa-card p-4">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>
        <div class="mb-3">
            <label class="form-label fw-bold"><?php echo $type === 'achievement' ? 'Achievement Title' : 'Person Name'; ?></label>
            <input type="text" class="form-control amsa-form-control" name="title" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold"><?php echo $type === 'achievement' ? 'Description' : 'Testimonial'; ?></label>
            <textarea class="form-control amsa-form-control" name="content" rows="7" required></textarea>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Images</label>
            <input type="file" class="form-control amsa-form-control" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp">
            <small class="admin-help-text">Upload 1 to 3 JPG, JPEG, PNG, or WEBP images. Maximum 2 MB per image.</small>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Save <?php echo ucfirst($type); ?></button>
            <a href="dashboard.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
