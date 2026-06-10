<?php
require_once '../config/database.php';
requireAdmin('login.php');
require_once 'includes/listing_helpers.php';

$error = '';
$success = '';

$categories = [
    'news' => 'News',
    'announcement' => 'Announcement',
    'workshop' => 'Workshop',
    'volunteer' => 'Volunteer',
    'community_engagement' => 'Community Engagement'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'news';
        $content = sanitize($_POST['content'] ?? '');
        $uploaded_by = currentUserId();

        if ($title === '' || $content === '' || !array_key_exists($category, $categories)) {
            $error = 'Please complete all required fields with a valid category.';
        } elseif (!validateImageUploadSelection($_FILES['images'] ?? null, $uploadPreflightErrors)) {
            $error = implode(' ', $uploadPreflightErrors);
        } else {
            $stmt = $conn->prepare("INSERT INTO post (content, category, title, uploaded_by, upload_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $content, $category, $title, $uploaded_by);

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
                    logAuditAction('add_news', 'post', $post_id, null, [
                        'title' => $title,
                        'category' => $category
                    ]);
                    header('Location: add_news.php?msg=added');
                    exit();
                }
            }

            if ($error === '') {
                $error = 'Failed to add news post.';
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $messages = [
        'added' => 'News post created successfully.',
        'deleted' => 'News post deleted successfully.',
    ];
    $success = $messages[$_GET['msg']] ?? '';
}

$listingPage = max(1, (int) ($_GET['page'] ?? 1));
$newsPageData = adminFetchPostsPage(['news', 'announcement', 'workshop', 'volunteer'], $listingPage, 6);
$newsPosts = $newsPageData['items'];

$pageTitle = 'Add News';
include 'includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success amsa-alert amsa-alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger amsa-alert amsa-alert-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="admin-card amsa-card p-4 mb-4">
    <form method="POST" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>

        <div class="mb-3">
            <label class="form-label fw-bold">Title *</label>
            <input type="text" class="form-control amsa-form-control" name="title" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Category *</label>
            <select class="form-select amsa-form-control" name="category" required>
                <?php foreach ($categories as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Content *</label>
            <textarea class="form-control amsa-form-control" name="content" rows="9" required></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Images</label>
            <input type="file" class="form-control amsa-form-control" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp">
            <small class="admin-help-text">
                Upload 1 to 3 JPG, JPEG, PNG, or WEBP images. Maximum 2 MB per image.
            </small>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                Publish
            </button>
            <a href="dashboard.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<div class="admin-card amsa-card p-4">
    <div class="admin-section-header">
        <div>
            <h3>Existing News</h3>
            <p>Manage published news, announcements, workshops, and volunteer posts.</p>
        </div>
    </div>

    <?php if (empty($newsPosts)): ?>
        <div class="amsa-empty-state text-center py-4">
            <i class="fas fa-newspaper fa-2x mb-3 text-primary"></i>
            <h5>No records found yet.</h5>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle amsa-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($newsPosts as $post): ?>
                        <?php
                        $cover = adminListingPostImage($post['id']);
                        $category = $post['category'] ?? 'news';
                        $deleteModalId = 'deleteNewsModal' . (int) $post['id'];
                        $preview = adminListingExcerpt($post['content'] ?? '', 90);
                        ?>
                        <tr>
                            <td style="width: 110px;">
                                <?php if ($cover): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($cover); ?>"
                                        alt="<?php echo htmlspecialchars(htmlspecialchars_decode($post['title'] ?? 'News')); ?>"
                                        class="admin-thumb"
                                        style="width: 90px; height: 60px; object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light border rounded"
                                         style="width: 90px; height: 60px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong class="text-dark">
                                    <?php echo htmlspecialchars(htmlspecialchars_decode($post['title'] ?? 'Untitled News')); ?>
                                </strong>
                            </td>

                            <td>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars($preview !== '' ? $preview : 'No content'); ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($categories[$category] ?? ucfirst($category)); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo !empty($post['upload_date']) ? date('M d, Y', strtotime($post['upload_date'])) : 'N/A'; ?>
                            </td>

                            <td class="text-end">
                                <a
                                    href="edit_news.php?id=<?php echo (int) $post['id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Edit
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#<?php echo $deleteModalId; ?>"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="<?php echo $deleteModalId; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Delete News Post</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        Delete "<?php echo htmlspecialchars(htmlspecialchars_decode($post['title'] ?? 'this post')); ?>"?
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>

                                        <form method="POST" action="delete_content.php">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($category); ?>">
                                            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars(adminDeleteReturnTo()); ?>">
                                            <button type="submit" class="btn btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php adminRenderPagination($newsPageData); ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>