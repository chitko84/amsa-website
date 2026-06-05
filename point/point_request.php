<?php
require_once '../config/database.php';
requireMember('login.php');

$userId = currentUserId();
$currentRole = currentUserRole();
$canSubmitPointRequest = $currentRole === 'member';
$categories = getAllPointCategories();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } elseif (!$canSubmitPointRequest) {
        $error = 'Admin accounts cannot submit point requests. Please use a member account to submit activities.';
    } else {
    $pointCategoryId = isset($_POST['point_category_id']) ? (int) $_POST['point_category_id'] : 0;
    $description = sanitize($_POST['description'] ?? '');
    $validCategoryIds = array_map(fn($category) => (int) $category['id'], $categories);

    if (!in_array($pointCategoryId, $validCategoryIds, true)) {
        $error = 'Please select a valid activity type.';
    } elseif ($description === '') {
        $error = 'Please describe your activity.';
    } else {
        $evidenceFile = uploadEvidenceSecure($_FILES['eop_evidence'] ?? null, __DIR__ . '/uploads/eop/', $error);
        $filePath = $evidenceFile ? 'uploads/eop/' . $evidenceFile : null;
        if ($filePath && createPointRequest($userId, $pointCategoryId, $description, $filePath)) {
            $success = 'Point request submitted successfully. It is now pending admin review.';
        } elseif (!$error) {
            $error = 'Failed to submit request. Please try again.';
        }
    }
    }
}

$userRequests = getUserPointRequests($userId);
$userPoints = getUserPoints($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Point Request System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        .point-card { background: var(--amsa-gradient-primary); border-radius: var(--amsa-radius-lg, 20px); padding: 30px; color: white; margin-bottom: 30px; }
        .request-card { border-radius: 15px; transition: transform 0.3s; margin-bottom: 20px; }
        .request-card:hover { transform: translateY(-5px); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: var(--amsa-color-gold, #f4b942); color: var(--amsa-color-text, #2b2020); }
        .status-approved { background: var(--amsa-color-success, #2f8f57); color: #fff; }
        .status-rejected { background: var(--amsa-color-error, #b44444); color: #fff; }
    </style>
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Point Request System</h1>
            <p class="text-white">Request points for your participation and achievements</p>
        </div>
    </div>

    <div class="container points-page-shell mb-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-4">
                <div class="point-card points-help-card text-center">
                    <i class="fas fa-star fa-3x mb-3"></i>
                    <h2><?php echo (int) $userPoints; ?></h2>
                    <p class="mb-0">Your Total Points</p>
                </div>
                <div class="amsa-card points-help-card">
                    <h5>Before You Submit</h5>
                    <ul class="points-help-list">
                        <li><i class="fas fa-check-circle"></i><span>Select the activity category that best matches your participation.</span></li>
                        <li><i class="fas fa-file-upload"></i><span>Upload clear evidence as PDF, JPG, JPEG, or PNG.</span></li>
                        <li><i class="fas fa-hourglass-half"></i><span>Your request will stay pending until an admin reviews it.</span></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm amsa-card points-form-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Submit Point Request</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$canSubmitPointRequest): ?>
                            <div class="alert alert-warning amsa-alert amsa-alert-warning mb-0">
                                Admin accounts cannot submit point requests. Please use a member account to submit activities.
                            </div>
                        <?php else: ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfInput(); ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Activity Type</label>
                                <select name="point_category_id" class="form-select amsa-form-control" required>
                                    <option value="">Choose an activity...</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int) $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?> (<?php echo (int) $category['points']; ?> points)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="amsa-upload-hint">Choose the category that matches your evidence and activity description.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Activity Description</label>
                                <textarea name="description" class="form-control amsa-form-control" rows="5" placeholder="Describe your participation or achievement..." required></textarea>
                            </div>
                            <div class="mb-4 points-upload-box">
                                <label class="form-label fw-bold">Evidence Upload</label>
                                <input type="file" name="eop_evidence" class="form-control amsa-form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                <small class="amsa-upload-hint">Allowed: PDF, JPG, JPEG, PNG. Maximum size: 5MB.</small>
                            </div>
                            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Submit Request</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="points-section-header">
                    <div>
                        <h3>Your Point Requests History</h3>
                        <p>Review your submitted activities and admin decisions.</p>
                    </div>
                </div>
                <?php if (empty($userRequests)): ?>
                    <div class="amsa-empty-state">
                        <i class="fas fa-clipboard-list fa-2x mb-3 text-primary"></i>
                        <h4>No Requests Yet</h4>
                        <p class="mb-0">Use the form above to submit your first activity request.</p>
                    </div>
                <?php else: ?>
                    <div class="amsa-card">
                        <div class="table-responsive amsa-table-wrap">
                            <table class="table align-middle amsa-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Description</th>
                                        <th>Requested Date</th>
                                        <th>Evidence</th>
                                        <th>Reviewed Date</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userRequests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['description']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <?php if (!empty($request['eop_evidence'])): ?>
                                                    <a href="evidence.php?id=<?php echo (int) $request['id']; ?>" target="_blank">View</a>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($request['review_date']) ? date('M d, Y', strtotime($request['review_date'])) : 'N/A'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['admin_remarks'] ?? '-'); ?></td>
                                            <td>
                                                <span class="amsa-badge amsa-badge-<?php echo htmlspecialchars($request['status']); ?> status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
