<?php
require_once '../config/database.php';
requireAdmin('login.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = $_GET['type'] ?? '';
if (!$id || !in_array($type, ['achievement', 'testimonial'], true)) {
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM post WHERE id = ? AND category = ?");
$stmt->bind_param("is", $id, $type);
$stmt->execute();
$contentItem = $stmt->get_result()->fetch_assoc();
if (!$contentItem) {
    header('Location: dashboard.php');
    exit();
}

$imgStmt = $conn->prepare("SELECT * FROM image WHERE post_id = ? ORDER BY id ASC");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $edited_by = currentUserId();

    if ($title === '' || $content === '') {
        $error = 'Title and content are required.';
    } else {
        $deleteImageIds = array_unique(array_map('intval', $_POST['delete_images'] ?? []));
        $existingImageIds = array_map('intval', array_column($images, 'id'));
        $remainingImageCount = count($images) - count(array_intersect($existingImageIds, $deleteImageIds));
        $newImageCount = selectedUploadFileCount($_FILES['images'] ?? null);
        if ($remainingImageCount + $newImageCount > 3) {
            $error = 'Each item can have a maximum of 3 images. Delete existing images before adding more.';
        } elseif (!validateImageUploadSelection($_FILES['images'] ?? null, $uploadPreflightErrors)) {
            $error = implode(' ', $uploadPreflightErrors);
        }
    }

    if ($error === '') {
        $update = $conn->prepare("UPDATE post SET title = ?, content = ?, edit_date = NOW(), edited_by = ? WHERE id = ? AND category = ?");
        $update->bind_param("ssiis", $title, $content, $edited_by, $id, $type);

        if ($update->execute()) {
            if (!empty($deleteImageIds)) {
                foreach ($deleteImageIds as $imageId) {
                    $find = $conn->prepare("SELECT img_name FROM image WHERE id = ? AND post_id = ?");
                    $find->bind_param("ii", $imageId, $id);
                    $find->execute();
                    $img = $find->get_result()->fetch_assoc();
                    $safeImageName = $img ? basename($img['img_name']) : '';
                    if ($safeImageName && is_file('../uploads/' . $safeImageName)) {
                        unlink('../uploads/' . $safeImageName);
                    }
                    $deleteImage = $conn->prepare("DELETE FROM image WHERE id = ? AND post_id = ?");
                    $deleteImage->bind_param("ii", $imageId, $id);
                    $deleteImage->execute();
                }
            }

            $uploadErrors = [];
            $uploadedImages = uploadMultipleImagesSecure($_FILES['images'] ?? null, '../uploads/', $uploadErrors);
            foreach ($uploadedImages as $file_name) {
                $newImg = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
                $newImg->bind_param("is", $id, $file_name);
                $newImg->execute();
            }
            if (!empty($uploadErrors)) {
                $error = implode(' ', $uploadErrors);
            }

            if ($error === '') {
                logAuditAction('edit_' . $type, 'post', $id, ['title' => $contentItem['title']], ['title' => $title]);
                header('Location: dashboard.php?msg=updated');
                exit();
            }
        }

        if ($error === '') {
            $error = 'Failed to update content.';
        }
    }
    }
}

$pageTitle = 'Edit ' . ucfirst($type);
include 'includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card amsa-card p-4">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>
        <div class="mb-3">
            <label class="form-label fw-bold"><?php echo $type === 'achievement' ? 'Achievement Title' : 'Person Name'; ?></label>
            <input type="text" class="form-control amsa-form-control" name="title" value="<?php echo htmlspecialchars(htmlspecialchars_decode($contentItem['title'])); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold"><?php echo $type === 'achievement' ? 'Description' : 'Testimonial'; ?></label>
            <textarea class="form-control amsa-form-control" name="content" rows="7" required><?php echo htmlspecialchars(htmlspecialchars_decode($contentItem['content'])); ?></textarea>
        </div>
        <?php if (!empty($images)): ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Current Images</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($images as $img): ?>
                        <label class="border rounded p-2 bg-white">
                            <img src="../uploads/<?php echo htmlspecialchars(basename($img['img_name'])); ?>" alt="Current content image" class="admin-thumb">
                            <br>
                            <input type="checkbox" name="delete_images[]" value="<?php echo (int) $img['id']; ?>"> Delete
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="form-label fw-bold">Add Images</label>
            <input type="file" class="form-control amsa-form-control" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp">
            <small class="admin-help-text">Keep up to 3 total JPG, JPEG, PNG, or WEBP images. Maximum 2 MB per image.</small>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Update <?php echo ucfirst($type); ?></button>
            <a href="dashboard.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
