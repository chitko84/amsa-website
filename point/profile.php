<?php
require_once '../config/database.php';
requireMember('login.php');

function profile_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function profile_ends_with_allowed_domain($email)
{
    $email = strtolower(trim((string) $email));
    return substr($email, -strlen('@aiu.edu.my')) === '@aiu.edu.my'
        || substr($email, -strlen('@student.aiu.edu.my')) === '@student.aiu.edu.my';
}

$userId = currentUserId();
if (!$userId) {
    header('Location: login.php');
    exit();
}

$profile = getUserProfile($userId);
if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

$globalSuccess = '';
$globalError = '';
$imageSuccess = '';
$imageError = '';
$nameError = '';
$emailError = '';
$passwordError = '';

$postedName = $profile['name'] ?? '';
$postedEmail = $profile['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $globalError = 'Your session token expired. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_account_info') {
            $postedName = trim($_POST['name'] ?? '');
            $postedEmail = trim($_POST['email'] ?? '');

            if ($postedName === '') {
                $nameError = 'Full name is required.';
            } elseif (strlen($postedName) < 2) {
                $nameError = 'Full name must be at least 2 characters.';
            }

            if ($postedEmail === '') {
                $emailError = 'Email address is required.';
            } elseif (!filter_var($postedEmail, FILTER_VALIDATE_EMAIL)) {
                $emailError = 'Please enter a valid email address.';
            } elseif (!profile_ends_with_allowed_domain($postedEmail)) {
                $emailError = 'Email must end with @aiu.edu.my or @student.aiu.edu.my.';
            }

            if ($emailError === '') {
                $stmt = $conn->prepare('SELECT id FROM user WHERE email = ? AND id <> ? LIMIT 1');
                if (!$stmt) {
                    $globalError = 'Database error while checking email address.';
                } else {
                    $stmt->bind_param('si', $postedEmail, $userId);
                    $stmt->execute();
                    $existingEmail = $stmt->get_result()->fetch_assoc();
                    if ($existingEmail) {
                        $emailError = 'This email address is already used by another user.';
                    }
                }
            }

            if ($nameError === '' && $emailError === '') {
                $stmt = $conn->prepare('UPDATE user SET name = ?, email = ?, updated_at = NOW() WHERE id = ?');
                if (!$stmt) {
                    $globalError = 'Database error while updating account information.';
                } else {
                    $stmt->bind_param('ssi', $postedName, $postedEmail, $userId);
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $postedName;
                        $_SESSION['user_email'] = $postedEmail;
                        $globalSuccess = 'Account information updated successfully.';
                        logAuditAction(
                            'profile_account_update',
                            'user',
                            $userId,
                            [
                                'name' => $profile['name'] ?? null,
                                'email' => $profile['email'] ?? null,
                            ],
                            [
                                'name' => $postedName,
                                'email' => $postedEmail,
                            ]
                        );
                        $profile = getUserProfile($userId);
                    } else {
                        $globalError = 'Unable to update your account information right now.';
                    }
                }
            } elseif ($globalError === '') {
                $globalError = 'Please correct the highlighted fields.';
            }
        } elseif ($action === 'change_password') {
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' && $newPassword === '' && $confirmPassword === '') {
                $passwordError = 'Enter your current password and new password to change it.';
            } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $passwordError = 'All password fields are required when changing your password.';
            } elseif (strlen($newPassword) < 8) {
                $passwordError = 'New password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $passwordError = 'New password and confirmation do not match.';
            } else {
                $stmt = $conn->prepare('SELECT password FROM user WHERE id = ? LIMIT 1');
                if (!$stmt) {
                    $globalError = 'Database error while verifying your password.';
                } else {
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();

                    if (!$row || empty($row['password']) || !password_verify($currentPassword, $row['password'])) {
                        $passwordError = 'Current password is incorrect.';
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE user SET password = ?, updated_at = NOW() WHERE id = ?');
                        if (!$stmt) {
                            $globalError = 'Database error while changing your password.';
                        } else {
                            $stmt->bind_param('si', $hashedPassword, $userId);
                            if ($stmt->execute()) {
                                $globalSuccess = 'Password changed successfully.';
                                logAuditAction('profile_password_change', 'user', $userId);
                            } else {
                                $globalError = 'Unable to change your password right now.';
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'upload_profile_image') {
            $croppedImage = $_POST['cropped_image'] ?? '';
            if (saveCroppedProfileImage($userId, $croppedImage, $imageError)) {
                $imageSuccess = 'Profile image updated successfully.';
                $profile = getUserProfile($userId);
            }
        } elseif ($action === 'remove_profile_image') {
            if (removeProfileImage($userId, $imageError)) {
                $imageSuccess = 'Profile image removed successfully.';
                $profile = getUserProfile($userId);
            }
        } else {
            $globalError = 'Invalid profile request.';
        }
    }
}

$profileImageUrl = profileImageUrl($profile['profile_image'] ?? null, '../');
$createdAt = !empty($profile['created_at']) ? date('M d, Y', strtotime($profile['created_at'])) : '-';
$statusLabel = !empty($profile['status']) ? ucfirst((string) $profile['status']) : 'Unknown';
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
    <style>
        body.points-page {
            background:
                linear-gradient(180deg, rgba(255, 249, 243, 0.96), rgba(248, 243, 239, 1)),
                var(--amsa-bg);
        }

        .profile-hero {
            background: linear-gradient(135deg, var(--amsa-maroon-dark), var(--amsa-maroon), #a24f45) !important;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::after {
            content: "";
            position: absolute;
            inset: auto -8% -42% -8%;
            height: 72%;
            background: rgba(244, 185, 66, 0.16);
            transform: rotate(-3deg);
        }

        .profile-hero .container {
            position: relative;
            z-index: 1;
        }

        .profile-shell {
            padding-bottom: 3rem;
        }

        .profile-summary-card,
        .profile-panel-card {
            background: #fff;
            border: 1px solid var(--amsa-border);
            border-radius: 16px;
            box-shadow: 0 16px 36px rgba(95, 38, 38, 0.08);
        }

        .profile-summary-card {
            padding: 1.5rem;
            position: sticky;
            top: 1rem;
        }

        .profile-avatar-lg {
            width: 156px;
            height: 156px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--amsa-gold-soft);
            box-shadow: 0 14px 34px rgba(95, 38, 38, 0.14);
            background: #fff;
        }

        .profile-summary-meta {
            display: grid;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .profile-summary-meta div {
            align-items: center;
            background: #fffaf0;
            border: 1px solid var(--amsa-border);
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
        }

        .profile-summary-meta span {
            color: var(--amsa-muted);
            font-weight: 700;
        }

        .profile-summary-meta strong {
            color: var(--amsa-maroon-dark);
            text-align: right;
        }

        .profile-section-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid var(--amsa-border);
        }

        .profile-section-title h3 {
            margin: 0;
            color: var(--amsa-maroon-dark);
            font-weight: 900;
            font-size: 1.1rem;
        }

        .profile-section-title p {
            margin: 0.35rem 0 0;
            color: var(--amsa-muted);
            font-size: 0.92rem;
        }

        .profile-panel-card {
            padding: 1.35rem;
            margin-bottom: 1.25rem;
        }

        .profile-form .form-label {
            color: var(--amsa-maroon-dark);
            font-weight: 800;
            margin-bottom: 0.45rem;
        }

        .profile-form .form-control,
        .profile-form .amsa-form-control {
            min-height: 50px;
        }

        .profile-help {
            color: var(--amsa-muted);
            font-size: 0.85rem;
            margin-top: 0.45rem;
        }

        .profile-alert {
            border: 0;
            border-radius: 12px;
            padding: 0.95rem 1rem;
            margin-bottom: 1rem;
        }

        .profile-alert-success {
            background: #edf9f0;
            color: #1f6a3f;
            border-left: 4px solid #2f8f57;
        }

        .profile-alert-error {
            background: #fef0f0;
            color: #9d2f2f;
            border-left: 4px solid #d9534f;
        }

        .profile-alert-info {
            background: #fff7e5;
            color: var(--amsa-maroon-dark);
            border-left: 4px solid var(--amsa-gold);
        }

        .profile-inline-error {
            display: block;
            color: #b32727;
            font-size: 0.83rem;
            margin-top: 0.35rem;
        }

        .profile-image-layout {
            display: grid;
            gap: 1.2rem;
            grid-template-columns: minmax(0, 1.35fr) minmax(260px, 0.65fr);
        }

        .profile-crop-stage {
            position: relative;
            min-height: 420px;
            background: #fff8ed;
            border: 1px dashed var(--amsa-border);
            border-radius: 14px;
            overflow: hidden;
        }

        #cropperImage {
            display: block;
            max-width: 100%;
            width: 100%;
            max-height: 520px;
        }

        .profile-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5rem;
            color: var(--amsa-muted);
            background: linear-gradient(135deg, rgba(255, 248, 237, 0.95), rgba(255, 255, 255, 0.9));
        }

        .profile-preview-panel {
            display: grid;
            gap: 1rem;
            align-content: start;
            justify-items: center;
        }

        .profile-preview-label {
            color: var(--amsa-maroon-dark);
            font-weight: 800;
            margin-bottom: 0;
        }

        .profile-preview {
            width: 220px;
            height: 220px;
            margin: 0 auto;
            border-radius: 50%;
            border: 2px solid var(--amsa-gold);
            overflow: hidden;
            background: #fff8ed;
            box-shadow: inset 0 0 0 1px rgba(95, 38, 38, 0.05);
        }

        .profile-preview img,
        #finalCropPreview {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-final-preview {
            display: none;
            margin-top: 0.75rem;
            padding: 0.9rem;
            background: #fffdf8;
            border: 1px solid var(--amsa-border);
            border-radius: 14px;
            width: 100%;
            text-align: center;
        }

        .profile-final-preview.is-visible {
            display: block;
        }

        .profile-final-preview strong {
            display: block;
            color: var(--amsa-maroon-dark);
            margin-bottom: 0.6rem;
        }

        .profile-final-preview .profile-preview {
            width: 180px;
            height: 180px;
        }

        .profile-preview-note {
            color: var(--amsa-muted);
            font-size: 0.86rem;
            text-align: center;
        }

        .profile-crop-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
            width: 100%;
            max-width: 420px;
        }

        .profile-crop-actions .btn,
        .profile-save-action .btn {
            align-items: center;
            display: inline-flex;
            justify-content: center;
            gap: 0.45rem;
            min-width: 0;
            white-space: nowrap;
        }

        .profile-crop-actions .btn {
            width: 100%;
            min-height: 48px;
            padding: 0.8rem 1rem;
            font-size: 0.92rem;
            line-height: 1.1;
            text-align: center;
        }

        .profile-crop-actions .btn i,
        .profile-save-action .btn i {
            flex: 0 0 auto;
        }

        .profile-save-action {
            width: 100%;
            max-width: 420px;
        }

        .profile-save-action .btn {
            width: 100%;
            min-height: 56px;
            padding: 0.95rem 1.1rem;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 14px;
            box-shadow: 0 16px 30px rgba(95, 38, 38, 0.16);
        }

        .profile-save-action .btn:disabled {
            box-shadow: none;
        }

        .profile-note {
            color: var(--amsa-muted);
            font-size: 0.86rem;
            margin-bottom: 0;
        }

        .profile-card-tiny {
            background: #fffaf0;
            border: 1px solid var(--amsa-border);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            color: var(--amsa-maroon-dark);
        }

        .is-invalid {
            border-color: #dc3545;
        }

        @media (max-width: 991.98px) {
            .profile-summary-card {
                position: static;
            }

            .profile-image-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .profile-panel-card,
            .profile-summary-card {
                padding: 1rem;
            }

            .profile-preview {
                width: 180px;
                height: 180px;
            }

            .profile-crop-actions {
                grid-template-columns: 1fr;
                max-width: 100%;
            }

            .profile-save-action {
                max-width: 100%;
            }

            .profile-save-action .btn {
                width: 100%;
            }

            .profile-section-title {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid profile-hero py-5 mb-4">
        <div class="container text-center">
            <h1 class="display-5 text-white mb-2">My Profile</h1>
            <p class="text-white mb-0">Manage your account details, security, and profile image in one place.</p>
        </div>
    </div>

    <div class="container profile-shell">
        <?php if ($globalSuccess): ?>
            <div class="alert profile-alert profile-alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo profile_h($globalSuccess); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($globalError): ?>
            <div class="alert profile-alert profile-alert-error alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo profile_h($globalError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="profile-summary-card h-100">
                    <div class="text-center">
                        <img src="<?php echo profile_h($profileImageUrl); ?>" class="profile-avatar-lg" alt="Profile image">
                        <h3 class="mt-3 mb-1 fw-bold" style="color: var(--amsa-maroon-dark);"><?php echo profile_h($profile['name'] ?? ''); ?></h3>
                        <p class="text-muted mb-0"><?php echo profile_h($profile['email'] ?? ''); ?></p>
                    </div>

                    <div class="profile-summary-meta">
                        <div>
                            <span>Member since</span>
                            <strong><?php echo profile_h($createdAt); ?></strong>
                        </div>
                        <div>
                            <span>Total points</span>
                            <strong><?php echo (int) ($profile['total_points'] ?? 0); ?></strong>
                        </div>
                        <div>
                            <span>Status</span>
                            <strong><?php echo profile_h($statusLabel); ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($profile['profile_image'])): ?>
                        <form method="POST" class="mt-4" id="removeProfileImageForm">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="remove_profile_image">
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteProfileImageModal">
                                <i class="fas fa-trash me-1"></i> Remove Profile Image
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-panel-card">
                    <div class="profile-section-title">
                        <div>
                            <h3>Account Information</h3>
                            <p>Update your full name and AIU email address.</p>
                        </div>
                    </div>

                    <form method="POST" class="profile-form">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="update_account_info">

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control <?php echo $nameError ? 'is-invalid' : ''; ?>"
                                id="name"
                                name="name"
                                value="<?php echo profile_h($postedName); ?>"
                                required
                                autocomplete="name"
                            >
                            <?php if ($nameError): ?>
                                <span class="profile-inline-error"><?php echo profile_h($nameError); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control <?php echo $emailError ? 'is-invalid' : ''; ?>"
                                id="email"
                                name="email"
                                value="<?php echo profile_h($postedEmail); ?>"
                                required
                                autocomplete="email"
                            >
                            <?php if ($emailError): ?>
                                <span class="profile-inline-error"><?php echo profile_h($emailError); ?></span>
                            <?php endif; ?>
                            <div class="profile-help">Use an official AIU email ending with <code>@aiu.edu.my</code> or <code>@student.aiu.edu.my</code>.</div>
                        </div>

                        <div class="profile-footer-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Account Information
                            </button>
                        </div>
                    </form>
                </div>

                <div class="profile-panel-card">
                    <div class="profile-section-title">
                        <div>
                            <h3>Change Password</h3>
                            <p>Leave password fields empty if you do not want to change your password.</p>
                        </div>
                    </div>

                    <form method="POST" class="profile-form" autocomplete="off">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input
                                type="password"
                                class="form-control <?php echo $passwordError ? 'is-invalid' : ''; ?>"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input
                                type="password"
                                class="form-control <?php echo $passwordError ? 'is-invalid' : ''; ?>"
                                id="new_password"
                                name="new_password"
                                autocomplete="new-password"
                            >
                            <div class="profile-help">Minimum 8 characters.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input
                                type="password"
                                class="form-control <?php echo $passwordError ? 'is-invalid' : ''; ?>"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                            >
                            <?php if ($passwordError): ?>
                                <span class="profile-inline-error"><?php echo profile_h($passwordError); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="profile-footer-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="profile-panel-card">
                    <div class="profile-section-title">
                        <div>
                            <h3>Profile Image</h3>
                            <p>Crop an image before saving. JPG, PNG, and WEBP are supported.</p>
                        </div>
                    </div>

                    <?php if ($imageSuccess): ?>
                        <div class="alert profile-alert profile-alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo profile_h($imageSuccess); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($imageError): ?>
                        <div class="alert profile-alert profile-alert-error" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo profile_h($imageError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="profileImageForm" class="profile-form">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="upload_profile_image">
                        <input type="hidden" name="cropped_image" id="croppedImage">
                        <input type="hidden" id="preferredImageType" value="image/jpeg">

                        <div class="mb-3">
                            <label for="profileImageInput" class="form-label">Choose Image</label>
                            <input
                                type="file"
                                id="profileImageInput"
                                class="form-control amsa-form-control"
                                accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                            >
                            <div class="profile-help">Select an image, adjust the crop, preview it, then save the cropped version.</div>
                        </div>

                        <div class="profile-image-layout">
                            <div class="profile-crop-stage">
                                <img id="cropperImage" src="" alt="Selected profile image" hidden>
                                <div id="cropPlaceholder" class="profile-placeholder">
                                    <i class="fas fa-user-circle fa-3x mb-3" style="color: var(--amsa-gold);"></i>
                                    <h4 class="mb-2" style="color: var(--amsa-maroon-dark);">No image selected</h4>
                                    <p class="mb-0">Choose an image to start cropping.</p>
                                </div>
                            </div>

                            <div class="profile-preview-panel">
                                <p class="profile-preview-label text-center">Live Preview</p>
                                <div class="profile-preview profile-live-preview">
                                    <img alt="Live profile preview" src="<?php echo profile_h($profileImageUrl); ?>">
                                </div>
                                <p class="profile-preview-note mb-0">This preview updates as you move, zoom, or rotate the crop.</p>

                                <div id="finalCropWrap" class="profile-final-preview">
                                    <strong>Preview Crop</strong>
                                    <div class="profile-preview">
                                        <img id="finalCropPreview" alt="Cropped image preview" src="">
                                    </div>
                                </div>

                                <div class="profile-crop-actions">
                                    <button type="button" class="btn btn-outline-primary profile-control-btn" id="zoomInBtn" disabled>
                                        <i class="fas fa-search-plus me-1"></i> Zoom In
                                    </button>
                                    <button type="button" class="btn btn-outline-primary profile-control-btn" id="zoomOutBtn" disabled>
                                        <i class="fas fa-search-minus me-1"></i> Zoom Out
                                    </button>
                                    <button type="button" class="btn btn-outline-primary profile-control-btn" id="rotateLeftBtn" disabled>
                                        <i class="fas fa-undo me-1"></i> Rotate Left
                                    </button>
                                    <button type="button" class="btn btn-outline-primary profile-control-btn" id="rotateRightBtn" disabled>
                                        <i class="fas fa-redo me-1"></i> Rotate Right
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary profile-control-btn" id="resetBtn" disabled>
                                        <i class="fas fa-sync me-1"></i> Reset
                                    </button>
                                    <button type="button" class="btn btn-secondary profile-control-btn" id="previewCropBtn" disabled>
                                        <i class="fas fa-eye me-1"></i> Preview Crop
                                    </button>
                                </div>

                                <div class="profile-save-action">
                                    <button type="submit" class="btn btn-primary" id="saveProfileImageBtn" disabled>
                                        <i class="fas fa-save me-1"></i> Save Cropped Image
                                    </button>
                                </div>

                                <div id="imageFormMessage" class="profile-help text-center"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($profile['profile_image'])): ?>
        <div class="modal fade" id="deleteProfileImageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px;">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: var(--amsa-maroon-dark);">Remove Profile Image</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove your current profile image?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="removeProfileImageForm" class="btn btn-danger">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const imageInput = document.getElementById('profileImageInput');
            const cropperImage = document.getElementById('cropperImage');
            const cropPlaceholder = document.getElementById('cropPlaceholder');
            const croppedImageInput = document.getElementById('croppedImage');
            const preferredImageType = document.getElementById('preferredImageType');
            const previewCropBtn = document.getElementById('previewCropBtn');
            const saveBtn = document.getElementById('saveProfileImageBtn');
            const finalCropWrap = document.getElementById('finalCropWrap');
            const finalCropPreview = document.getElementById('finalCropPreview');
            const imageFormMessage = document.getElementById('imageFormMessage');
            const controls = {
                zoomIn: document.getElementById('zoomInBtn'),
                zoomOut: document.getElementById('zoomOutBtn'),
                rotateLeft: document.getElementById('rotateLeftBtn'),
                rotateRight: document.getElementById('rotateRightBtn'),
                reset: document.getElementById('resetBtn'),
            };

            let cropper = null;
            let previewReady = false;

            function setMessage(text, type) {
                if (!imageFormMessage) {
                    return;
                }

                if (!text) {
                    imageFormMessage.textContent = '';
                    imageFormMessage.className = 'profile-help text-center';
                    return;
                }

                imageFormMessage.className = 'profile-help text-center';
                if (type === 'success') {
                    imageFormMessage.innerHTML = '<span class="text-success fw-bold">' + text + '</span>';
                } else if (type === 'error') {
                    imageFormMessage.innerHTML = '<span class="text-danger fw-bold">' + text + '</span>';
                } else {
                    imageFormMessage.innerHTML = '<span class="fw-bold" style="color: var(--amsa-maroon-dark);">' + text + '</span>';
                }
            }

            function setCropControls(enabled) {
                Object.values(controls).forEach(function (button) {
                    if (button) {
                        button.disabled = !enabled;
                    }
                });
                if (previewCropBtn) {
                    previewCropBtn.disabled = !enabled;
                }
            }

            function setPreviewVisible(visible) {
                if (!finalCropWrap) {
                    return;
                }
                finalCropWrap.classList.toggle('is-visible', visible);
            }

            function clearPreviewState(message) {
                previewReady = false;
                if (croppedImageInput) {
                    croppedImageInput.value = '';
                }
                if (saveBtn) {
                    saveBtn.disabled = true;
                }
                setPreviewVisible(false);
                if (finalCropPreview) {
                    finalCropPreview.removeAttribute('src');
                }
                if (message) {
                    setMessage(message, 'info');
                } else {
                    setMessage('', '');
                }
            }

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            function showPlaceholder(show) {
                if (cropPlaceholder) {
                    cropPlaceholder.style.display = show ? 'flex' : 'none';
                }
                if (cropperImage) {
                    cropperImage.hidden = show;
                }
            }

            function createCropper() {
                destroyCropper();

                if (!cropperImage || !cropperImage.src) {
                    return;
                }

                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    dragMode: 'move',
                    preview: '.profile-live-preview',
                    autoCropArea: 0.9,
                    background: false,
                    responsive: true,
                    checkOrientation: true,
                    ready() {
                        setCropControls(true);
                        clearPreviewState('');
                        setMessage('Use the controls to adjust the crop, then click Preview Crop.', 'info');
                    },
                    crop() {
                        if (previewReady) {
                            clearPreviewState('Crop changed. Preview updated after you click Preview Crop again.');
                        }
                    }
                });
            }

            if (imageInput) {
                imageInput.addEventListener('change', function (event) {
                    const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                    clearPreviewState('');
                    setCropControls(false);
                    destroyCropper();

                    if (!file) {
                        showPlaceholder(true);
                        setMessage('', '');
                        return;
                    }

                    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    const extension = (file.name.split('.').pop() || '').toLowerCase();
                    const extensionMap = {
                        jpg: 'image/jpeg',
                        jpeg: 'image/jpeg',
                        png: 'image/png',
                        webp: 'image/webp'
                    };
                    const detectedType = allowedTypes.includes(file.type) ? file.type : extensionMap[extension] || '';

                    if (!detectedType) {
                        imageInput.value = '';
                        showPlaceholder(true);
                        setMessage('Please choose a JPG, PNG, or WEBP image.', 'error');
                        return;
                    }

                    if (file.size > 3 * 1024 * 1024) {
                        imageInput.value = '';
                        showPlaceholder(true);
                        setMessage('Please choose an image that is 3MB or smaller.', 'error');
                        return;
                    }

                    if (preferredImageType) {
                        preferredImageType.value = detectedType;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (!cropperImage) {
                            return;
                        }

                        cropperImage.onload = function () {
                            showPlaceholder(false);
                            createCropper();
                        };

                        cropperImage.src = e.target.result;
                        cropperImage.hidden = false;
                    };
                    reader.onerror = function () {
                        imageInput.value = '';
                        showPlaceholder(true);
                        setMessage('The selected image could not be read.', 'error');
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (controls.zoomIn) {
                controls.zoomIn.addEventListener('click', function () {
                    if (cropper) {
                        cropper.zoom(0.1);
                        clearPreviewState('');
                    }
                });
            }

            if (controls.zoomOut) {
                controls.zoomOut.addEventListener('click', function () {
                    if (cropper) {
                        cropper.zoom(-0.1);
                        clearPreviewState('');
                    }
                });
            }

            if (controls.rotateLeft) {
                controls.rotateLeft.addEventListener('click', function () {
                    if (cropper) {
                        cropper.rotate(-90);
                        clearPreviewState('');
                    }
                });
            }

            if (controls.rotateRight) {
                controls.rotateRight.addEventListener('click', function () {
                    if (cropper) {
                        cropper.rotate(90);
                        clearPreviewState('');
                    }
                });
            }

            if (controls.reset) {
                controls.reset.addEventListener('click', function () {
                    if (cropper) {
                        cropper.reset();
                        clearPreviewState('');
                    }
                });
            }

            if (previewCropBtn) {
                previewCropBtn.addEventListener('click', function () {
                    if (!cropper) {
                        setMessage('Choose an image first.', 'error');
                        return;
                    }

                    const canvas = cropper.getCroppedCanvas({
                        width: 600,
                        height: 600,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    });

                    if (!canvas) {
                        setMessage('Could not create the cropped preview. Please try another image.', 'error');
                        return;
                    }

                    const outputType = preferredImageType && preferredImageType.value ? preferredImageType.value : 'image/jpeg';
                    let dataUrl = canvas.toDataURL(outputType, 0.92);

                    if (!dataUrl || dataUrl.indexOf('data:' + outputType) !== 0) {
                        dataUrl = canvas.toDataURL('image/jpeg', 0.92);
                    }

                    if (croppedImageInput) {
                        croppedImageInput.value = dataUrl;
                    }
                    if (finalCropPreview) {
                        finalCropPreview.src = dataUrl;
                    }
                    setPreviewVisible(true);
                    previewReady = true;
                    if (saveBtn) {
                        saveBtn.disabled = false;
                    }
                    setMessage('Preview ready. Save the cropped image when you are satisfied.', 'success');
                });
            }

            const profileImageForm = document.getElementById('profileImageForm');
            if (profileImageForm) {
                profileImageForm.addEventListener('submit', function (event) {
                    if (!croppedImageInput || !croppedImageInput.value) {
                        event.preventDefault();
                        setMessage('Click Preview Crop before saving the image.', 'error');
                    }
                });
            }

            showPlaceholder(true);
            setCropControls(false);
            setMessage('', '');
        });
    </script>
</body>
</html>
