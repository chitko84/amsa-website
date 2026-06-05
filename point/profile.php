<?php
require_once '../config/database.php';
requireMember('login.php');

$userId = currentUserId();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload_profile_image') {
            $croppedImage = $_POST['cropped_image'] ?? '';
            if (saveCroppedProfileImage($userId, $croppedImage, $error)) {
                $success = 'Profile image updated successfully.';
            }
        } elseif ($action === 'remove_profile_image') {
            if (removeProfileImage($userId, $error)) {
                $success = 'Profile image removed. The default avatar is now shown.';
            }
        } else {
            $error = 'Invalid profile request.';
        }
    }
}

$profile = getUserProfile($userId);
if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

$profileImageUrl = profileImageUrl($profile['profile_image'] ?? null, '../');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - My Profile</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">My Profile</h1>
            <p class="text-white">Manage your AMSA Points profile and profile image</p>
        </div>
    </div>

    <div class="container points-page-shell mb-5">
        <?php if ($success): ?>
            <div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="amsa-card profile-card text-center h-100">
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" class="profile-avatar-lg" alt="Profile image">
                    <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($profile['name']); ?></h3>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($profile['email']); ?></p>
                    <div class="profile-meta-list">
                        <div><span>User ID</span><strong>#<?php echo (int) $profile['id']; ?></strong></div>
                        <div><span>Total Points</span><strong><?php echo (int) $profile['total_points']; ?></strong></div>
                        <div><span>Status</span><strong><?php echo htmlspecialchars(ucfirst($profile['status'])); ?></strong></div>
                    </div>
                    <?php if (!empty($profile['profile_image'])): ?>
                        <form method="POST" class="mt-4" id="removeProfileImageForm">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="remove_profile_image">
                            <button type="button" class="btn btn-outline-danger amsa-btn amsa-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProfileImageModal">
                                <i class="fas fa-trash me-1"></i> Remove Image
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="amsa-card profile-editor-card">
                    <div class="points-section-header mt-0">
                        <div>
                            <h3>Profile Image</h3>
                            <p>Choose an image, preview the crop, then save the cropped version.</p>
                        </div>
                    </div>

                    <form method="POST" id="profileImageForm">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="upload_profile_image">
                        <input type="hidden" name="cropped_image" id="croppedImage">
                        <input type="hidden" id="preferredImageType" value="image/jpeg">

                        <div class="mb-3">
                            <label class="form-label">Choose Image</label>
                            <input type="file" id="profileImageInput" class="form-control amsa-form-control" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                            <div class="amsa-upload-hint">Allowed: JPG, JPEG, PNG, WEBP. Maximum cropped image size: 3MB.</div>
                        </div>

                        <div class="profile-crop-layout">
                            <div class="profile-crop-stage">
                                <img id="cropperImage" src="" alt="Profile crop preview">
                                <div id="cropPlaceholder" class="amsa-empty-state">
                                    <i class="fas fa-user-circle fa-2x mb-3 text-primary"></i>
                                    <h4>No Image Selected</h4>
                                    <p class="mb-0">Select an image to preview, crop, zoom, and rotate before saving.</p>
                                </div>
                            </div>
                            <div class="profile-preview-panel">
                                <span class="text-muted d-block mb-2">Live Preview</span>
                                <div class="profile-preview"></div>
                                <div class="profile-crop-actions mt-3">
                                    <button type="button" class="btn btn-outline-primary amsa-btn amsa-btn-ghost" id="zoomInBtn" disabled><i class="fas fa-search-plus"></i> Zoom In</button>
                                    <button type="button" class="btn btn-outline-primary amsa-btn amsa-btn-ghost" id="zoomOutBtn" disabled><i class="fas fa-search-minus"></i> Zoom Out</button>
                                    <button type="button" class="btn btn-outline-primary amsa-btn amsa-btn-ghost" id="rotateLeftBtn" disabled><i class="fas fa-undo"></i> Rotate Left</button>
                                    <button type="button" class="btn btn-outline-primary amsa-btn amsa-btn-ghost" id="rotateRightBtn" disabled><i class="fas fa-redo"></i> Rotate Right</button>
                                    <button type="button" class="btn btn-outline-secondary amsa-btn amsa-btn-secondary" id="resetBtn" disabled><i class="fas fa-sync"></i> Reset</button>
                                </div>
                            </div>
                        </div>

                        <div class="amsa-button-group mt-4">
                            <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" id="previewCropBtn" disabled>
                                <i class="fas fa-eye me-1"></i> Preview Crop
                            </button>
                            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary" id="saveProfileImageBtn" disabled>
                                <i class="fas fa-save me-1"></i> Save Cropped Image
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($profile['profile_image'])): ?>
        <div class="modal fade" id="deleteProfileImageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content amsa-modal">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete your profile image?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="removeProfileImageForm" class="btn btn-danger amsa-btn amsa-btn-danger">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>
        const imageInput = document.getElementById('profileImageInput');
        const cropperImage = document.getElementById('cropperImage');
        const croppedImageInput = document.getElementById('croppedImage');
        const preferredImageType = document.getElementById('preferredImageType');
        const cropPlaceholder = document.getElementById('cropPlaceholder');
        const previewCropBtn = document.getElementById('previewCropBtn');
        const saveBtn = document.getElementById('saveProfileImageBtn');
        const controls = {
            zoomIn: document.getElementById('zoomInBtn'),
            zoomOut: document.getElementById('zoomOutBtn'),
            rotateLeft: document.getElementById('rotateLeftBtn'),
            rotateRight: document.getElementById('rotateRightBtn'),
            reset: document.getElementById('resetBtn')
        };
        let cropper = null;

        function setCropControls(enabled) {
            previewCropBtn.disabled = !enabled;
            Object.values(controls).forEach((control) => {
                control.disabled = !enabled;
            });
        }

        function clearCroppedPreview() {
            croppedImageInput.value = '';
            saveBtn.disabled = true;
        }

        imageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            clearCroppedPreview();

            if (!file) {
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const extension = file.name.split('.').pop().toLowerCase();
            const extensionMap = {
                jpg: 'image/jpeg',
                jpeg: 'image/jpeg',
                png: 'image/png',
                webp: 'image/webp'
            };
            const detectedType = allowedTypes.includes(file.type) ? file.type : extensionMap[extension];
            if (!detectedType || !allowedTypes.includes(detectedType)) {
                alert('Please choose a JPG, PNG, or WEBP image.');
                imageInput.value = '';
                return;
            }
            preferredImageType.value = detectedType;

            if (file.size > 3 * 1024 * 1024) {
                alert('Please choose an image that is 3MB or smaller.');
                imageInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                cropperImage.src = reader.result;
                cropperImage.style.display = 'block';
                cropPlaceholder.style.display = 'none';

                if (cropper) {
                    cropper.destroy();
                }

                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    dragMode: 'move',
                    preview: '.profile-preview',
                    autoCropArea: 0.9,
                    background: false
                });
                setCropControls(true);
            };
            reader.readAsDataURL(file);
        });

        cropperImage.addEventListener('cropstart', clearCroppedPreview);
        controls.zoomIn.addEventListener('click', () => {
            if (cropper) {
                cropper.zoom(0.1);
                clearCroppedPreview();
            }
        });
        controls.zoomOut.addEventListener('click', () => {
            if (cropper) {
                cropper.zoom(-0.1);
                clearCroppedPreview();
            }
        });
        controls.rotateLeft.addEventListener('click', () => {
            if (cropper) {
                cropper.rotate(-90);
                clearCroppedPreview();
            }
        });
        controls.rotateRight.addEventListener('click', () => {
            if (cropper) {
                cropper.rotate(90);
                clearCroppedPreview();
            }
        });
        controls.reset.addEventListener('click', () => {
            if (cropper) {
                cropper.reset();
                clearCroppedPreview();
            }
        });

        previewCropBtn.addEventListener('click', () => {
            if (!cropper) {
                return;
            }

            const canvas = cropper.getCroppedCanvas({
                width: 600,
                height: 600,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            if (!canvas) {
                alert('Could not create cropped preview. Please try another image.');
                return;
            }

            let outputType = preferredImageType.value || 'image/jpeg';
            let dataUrl = canvas.toDataURL(outputType, 0.9);
            if (!dataUrl.startsWith('data:' + outputType)) {
                dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            }
            croppedImageInput.value = dataUrl;
            saveBtn.disabled = false;
        });

        document.getElementById('profileImageForm').addEventListener('submit', (event) => {
            if (!croppedImageInput.value) {
                event.preventDefault();
                alert('Please preview the crop before saving.');
            }
        });
    </script>
</body>
</html>
