<?php
require_once '../config/database.php';
requireAdmin('login.php');
require_once 'includes/listing_helpers.php';
ensureFundraisingTables();

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fundraising';
$uploadDbPrefix = 'uploads/fundraising/';
$error = '';
$success = '';
$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$editItem = null;
$editImages = [];

function fundraisingUploadFileCount($files) {
    return selectedUploadFileCount($files);
}

function fundraisingImagesFor($fundraisingId) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, image_path, display_order FROM fundraising_images WHERE fundraising_id = ? ORDER BY display_order ASC, id ASC");
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $fundraisingId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteFundraisingImageFiles(array $images) {
    foreach ($images as $image) {
        $relative = ltrim(str_replace('\\', '/', $image['image_path'] ?? ''), '/');
        if ($relative === '' || strpos($relative, 'uploads/fundraising/') !== 0) {
            continue;
        }

        $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($absolute)) {
            unlink($absolute);
        }
    }
}

function fetchFundraisingItem($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, title, description, status, created_at FROM fundraising WHERE id = ?");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

if (isset($_GET['msg'])) {
    $messages = [
        'added' => 'Fundraising activity added successfully.',
        'updated' => 'Fundraising activity updated successfully.',
        'deleted' => 'Fundraising activity deleted successfully.',
    ];
    $success = $messages[$_GET['msg']] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'save';
        $id = max(0, (int) ($_POST['id'] ?? 0));

        if ($action === 'delete') {
            $item = fetchFundraisingItem($id);
            if (!$item) {
                $error = 'Fundraising activity was not found.';
            } else {
                $images = fundraisingImagesFor($id);
                $stmt = $conn->prepare("DELETE FROM fundraising WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        deleteFundraisingImageFiles($images);
                        logAuditAction('delete_fundraising', 'fundraising', $id, $item, null);
                        header('Location: manage_fundraising.php?msg=deleted');
                        exit();
                    }
                }
                $error = 'Could not delete fundraising activity.';
            }
        } else {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'published';
            $status = in_array($status, ['published', 'draft'], true) ? $status : 'published';
            $selectedCount = fundraisingUploadFileCount($_FILES['images'] ?? null);
            $isEdit = $id > 0;
            $existingImages = $isEdit ? fundraisingImagesFor($id) : [];

            if ($title === '') {
                $error = 'Title is required.';
            } elseif (!$isEdit && $selectedCount < 1) {
                $error = 'Image 1 is required.';
            } elseif ($selectedCount > 0 && !validateImageUploadSelection($_FILES['images'] ?? null, $uploadPreflightErrors, 3)) {
                $error = implode(' ', $uploadPreflightErrors);
            } elseif ($isEdit && !$existingImages && $selectedCount < 1) {
                $error = 'At least one image is required.';
            } else {
                $uploadedFiles = [];
                if ($selectedCount > 0) {
                    $uploadErrors = [];
                    $uploadedFiles = uploadMultipleImagesSecure($_FILES['images'] ?? null, $uploadDir, $uploadErrors, 3);
                    if (!empty($uploadErrors)) {
                        $error = implode(' ', $uploadErrors);
                    } elseif (count($uploadedFiles) !== $selectedCount) {
                        $error = 'One or more images could not be uploaded.';
                    }
                }

                if ($error === '') {
                    $conn->begin_transaction();
                    try {
                        if ($isEdit) {
                            $item = fetchFundraisingItem($id);
                            if (!$item) {
                                throw new Exception('Fundraising activity was not found.');
                            }

                            $stmt = $conn->prepare("UPDATE fundraising SET title = ?, description = ?, status = ? WHERE id = ?");
                            if (!$stmt) {
                                throw new Exception('Could not prepare update.');
                            }

                            $stmt->bind_param("sssi", $title, $description, $status, $id);

                            if (!$stmt->execute()) {
                                throw new Exception('Could not update fundraising activity.');
                            }

                            if ($selectedCount > 0) {
                                $deleteStmt = $conn->prepare("DELETE FROM fundraising_images WHERE fundraising_id = ?");
                                $deleteStmt->bind_param("i", $id);
                                $deleteStmt->execute();
                            }

                            $fundraisingId = $id;
                        } else {
                            $stmt = $conn->prepare("INSERT INTO fundraising (title, description, status) VALUES (?, ?, ?)");
                            if (!$stmt) {
                                throw new Exception('Could not prepare insert.');
                            }

                            $stmt->bind_param("sss", $title, $description, $status);

                            if (!$stmt->execute()) {
                                throw new Exception('Could not save fundraising activity.');
                            }

                            $fundraisingId = (int) $conn->insert_id;
                        }

                        if ($selectedCount > 0) {
                            $order = 1;
                            foreach ($uploadedFiles as $fileName) {
                                $imagePath = $GLOBALS['uploadDbPrefix'] . basename($fileName);
                                $imgStmt = $conn->prepare("INSERT INTO fundraising_images (fundraising_id, image_path, display_order) VALUES (?, ?, ?)");
                                if (!$imgStmt) {
                                    throw new Exception('Could not prepare image save.');
                                }

                                $imgStmt->bind_param("isi", $fundraisingId, $imagePath, $order);

                                if (!$imgStmt->execute()) {
                                    throw new Exception('Could not save fundraising image.');
                                }

                                $order++;
                            }
                        }

                        $conn->commit();

                        if ($isEdit && $selectedCount > 0) {
                            deleteFundraisingImageFiles($existingImages);
                        }

                        logAuditAction($isEdit ? 'update_fundraising' : 'add_fundraising', 'fundraising', $fundraisingId, null, [
                            'title' => $title,
                            'status' => $status,
                        ]);

                        header('Location: manage_fundraising.php?msg=' . ($isEdit ? 'updated' : 'added'));
                        exit();
                    } catch (Exception $exception) {
                        $conn->rollback();

                        deleteFundraisingImageFiles(array_map(function ($fileName) use ($uploadDbPrefix) {
                            return ['image_path' => $uploadDbPrefix . basename($fileName)];
                        }, $uploadedFiles));

                        $error = $exception->getMessage();
                    }
                }
            }
        }
    }
}

if ($editingId > 0) {
    $editItem = fetchFundraisingItem($editingId);
    if ($editItem) {
        $editImages = fundraisingImagesFor($editingId);
    } else {
        $error = $error ?: 'Fundraising activity was not found.';
        $editingId = 0;
    }
}

$listingPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM fundraising");
$totalFundraising = $countResult ? (int) ($countResult->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = max(1, (int) ceil($totalFundraising / $perPage));

if ($listingPage > $totalPages) {
    $listingPage = $totalPages;
}

$offset = ($listingPage - 1) * $perPage;

$stmt = $conn->prepare("
    SELECT f.id, f.title, f.description, f.status, f.created_at,
           COUNT(fi.id) AS photo_count,
           MIN(CASE WHEN fi.display_order = 1 THEN fi.image_path ELSE NULL END) AS cover_image
    FROM fundraising f
    LEFT JOIN fundraising_images fi ON fi.fundraising_id = f.id
    GROUP BY f.id, f.title, f.description, f.status, f.created_at
    ORDER BY f.created_at DESC, f.id DESC
    LIMIT ? OFFSET ?
");

$fundraisingItems = [];

if ($stmt) {
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $fundraisingItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$fundraisingPageData = [
    'items' => $fundraisingItems,
    'total_count' => $totalFundraising,
    'current_page' => $listingPage,
    'total_pages' => $totalPages,
    'per_page' => $perPage,
];

$pageTitle = 'Manage Fundraising';
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
    <div class="admin-section-header">
        <div>
            <h3><?php echo $editItem ? 'Edit Fundraising Activity' : 'Add Fundraising Activity'; ?></h3>
            <p>Upload one required cover image and up to two additional photos.</p>
        </div>

        <?php if ($editItem): ?>
            <a href="manage_fundraising.php" class="btn btn-outline-secondary btn-sm">Add New</a>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <?php echo csrfInput(); ?>

        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int) ($editItem['id'] ?? 0); ?>">

        <div class="row g-3">
            <div class="col-lg-8">
                <label class="form-label fw-bold">Title *</label>
                <input
                    type="text"
                    class="form-control amsa-form-control"
                    name="title"
                    required
                    maxlength="255"
                    value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>"
                >
            </div>

            <div class="col-lg-4">
                <label class="form-label fw-bold">Status</label>
                <select class="form-select amsa-form-control" name="status">
                    <?php $selectedStatus = $editItem['status'] ?? 'published'; ?>
                    <option value="published" <?php echo $selectedStatus === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $selectedStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea class="form-control amsa-form-control" name="description" rows="4"><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
            </div>

            <?php if ($editImages): ?>
                <div class="col-12">
                    <label class="form-label fw-bold">Current Photos</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($editImages as $image): ?>
                            <img
                                class="admin-thumb"
                                src="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                alt="Fundraising photo"
                            >
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <label class="form-label fw-bold"><?php echo $editItem ? 'Replace Photos' : 'Photos *'; ?></label>
                <input
                    type="file"
                    class="form-control amsa-form-control"
                    name="images[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp"
                    <?php echo $editItem ? '' : 'required'; ?>
                >
                <small class="admin-help-text">
                    <?php echo $editItem ? 'Leave empty to keep current photos. Uploading new photos replaces all current photos.' : 'Image 1 is required.'; ?>
                    Upload 1 to 3 JPG, JPEG, PNG, or WEBP images. Maximum 2 MB per image.
                </small>
            </div>
        </div>

        <div class="admin-form-actions mt-3">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                <?php echo $editItem ? 'Update Activity' : 'Add Activity'; ?>
            </button>

            <?php if ($editItem): ?>
                <a href="manage_fundraising.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card amsa-card p-4">
    <div class="admin-section-header">
        <div>
            <h3>Fundraising Activities</h3>
            <p>Published activities appear on the public fundraising page.</p>
        </div>
    </div>

    <?php if (empty($fundraisingItems)): ?>
        <div class="amsa-empty-state text-center py-4">
            <i class="fas fa-hand-holding-heart fa-2x mb-3 text-primary"></i>
            <h5>No records found yet.</h5>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle amsa-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Photos</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($fundraisingItems as $item): ?>
                        <?php
                        $deleteModalId = 'deleteFundraisingModal' . (int) $item['id'];
                        $descriptionPreview = adminListingExcerpt($item['description'] ?? '', 90);
                        ?>
                        <tr>
                            <td style="width: 110px;">
                                <?php if (!empty($item['cover_image'])): ?>
                                    <img
                                        src="../<?php echo htmlspecialchars($item['cover_image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['title']); ?>"
                                        class="admin-thumb"
                                        style="width: 90px; height: 60px; object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div
                                        class="d-flex align-items-center justify-content-center bg-light border rounded"
                                        style="width: 90px; height: 60px;"
                                    >
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong class="text-dark">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </strong>
                            </td>

                            <td>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars($descriptionPreview !== '' ? $descriptionPreview : 'No description'); ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge <?php echo $item['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($item['status'])); ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-primary">
                                    <?php echo (int) $item['photo_count']; ?>
                                    <?php echo (int) $item['photo_count'] === 1 ? 'Photo' : 'Photos'; ?>
                                </span>
                            </td>

                            <td>
                                <?php echo !empty($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'N/A'; ?>
                            </td>

                            <td class="text-end">
                                <a
                                    href="manage_fundraising.php?edit=<?php echo (int) $item['id']; ?>"
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
                                        <h5 class="modal-title">Delete Fundraising Activity</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        Delete "<?php echo htmlspecialchars($item['title']); ?>"?
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>

                                        <form method="POST">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
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

        <?php adminRenderPagination($fundraisingPageData); ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>