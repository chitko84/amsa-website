<?php
require_once '../config/database.php';
requireAdmin('login.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$categories = ['news' => 'News', 'announcement' => 'Announcement', 'workshop' => 'Workshop', 'volunteer' => 'Volunteer', 'community_engagement' => 'Community Engagement'];
$stmt = $conn->prepare("SELECT * FROM post WHERE id = ? AND category IN ('news','announcement','workshop','volunteer','community_engagement')");
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
if (!$news) { header('Location: dashboard.php'); exit(); }

$imgStmt = $conn->prepare("SELECT * FROM image WHERE post_id = ?");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $title = sanitize($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'news';
    $content = sanitize($_POST['content'] ?? '');
    $edited_by = currentUserId();
    if ($title === '' || $content === '' || !array_key_exists($category, $categories)) {
        $error = 'Please complete all required fields with a valid category.';
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
        $update = $conn->prepare("UPDATE post SET title = ?, content = ?, category = ?, edit_date = NOW(), edited_by = ? WHERE id = ?");
        $update->bind_param("sssii", $title, $content, $category, $edited_by, $id);
        if ($update->execute()) {
            if (!empty($deleteImageIds)) {
                foreach ($deleteImageIds as $imageId) {
                    $imageId = (int) $imageId;
                    $find = $conn->prepare("SELECT img_name FROM image WHERE id = ? AND post_id = ?");
                    $find->bind_param("ii", $imageId, $id);
                    $find->execute();
                    $img = $find->get_result()->fetch_assoc();
                    $safeImageName = $img ? basename($img['img_name']) : '';
                    if ($safeImageName && is_file('../uploads/' . $safeImageName)) unlink('../uploads/' . $safeImageName);
                    $del = $conn->prepare("DELETE FROM image WHERE id = ? AND post_id = ?");
                    $del->bind_param("ii", $imageId, $id);
                    $del->execute();
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
                logAuditAction('edit_news', 'post', $id, ['title' => $news['title'], 'category' => $news['category']], ['title' => $title, 'category' => $category]);
                header('Location: dashboard.php?msg=news_updated');
                exit();
            }
        }
        if ($error === '') {
            $error = 'Failed to update post.';
        }
    }
    }
}

$pageTitle = 'Edit News';
include 'includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="admin-card amsa-card p-4">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>
        <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" class="form-control amsa-form-control" name="title" value="<?php echo htmlspecialchars(htmlspecialchars_decode($news['title'])); ?>" required></div>
        <div class="mb-3"><label class="form-label fw-bold">Category *</label><select class="form-select amsa-form-control" name="category" required><?php foreach ($categories as $value => $label): ?><option value="<?php echo htmlspecialchars($value); ?>" <?php echo $news['category'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label fw-bold">Content *</label><textarea class="form-control amsa-form-control" name="content" rows="9" required><?php echo htmlspecialchars(htmlspecialchars_decode($news['content'])); ?></textarea></div>
        <?php if ($images): ?><div class="mb-3"><label class="form-label fw-bold">Current Images</label><div class="d-flex flex-wrap gap-3"><?php foreach ($images as $img): ?><label class="border rounded p-2 bg-white"><img src="../uploads/<?php echo htmlspecialchars(basename($img['img_name'])); ?>" class="admin-thumb" alt="Current news image"><br><input type="checkbox" name="delete_images[]" value="<?php echo (int) $img['id']; ?>"> Delete</label><?php endforeach; ?></div></div><?php endif; ?>
        <div class="mb-4"><label class="form-label fw-bold">Add Images</label><input type="file" class="form-control amsa-form-control" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp"><small class="admin-help-text">Keep up to 3 total JPG, JPEG, PNG, or WEBP images. Maximum 2 MB per image.</small></div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Update Post</button>
            <a href="dashboard.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
