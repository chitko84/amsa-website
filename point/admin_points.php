<?php
require_once '../config/database.php';
requireAdmin('../admin/login.php');

$userId = currentUserId();

$statistics = getPointStatistics();
$allowedStatuses = ['all', 'pending', 'approved', 'rejected'];
$allowedSorts = ['newest', 'oldest', 'points_desc', 'points_asc', 'status'];
$allowedPerPage = [10, 25, 50];
$status = $_GET['status'] ?? 'all';
$statusFilter = in_array($status, $allowedStatuses, true) ? $status : 'all';
$sort = $_GET['sort'] ?? 'newest';
$sortOption = in_array($sort, $allowedSorts, true) ? $sort : 'newest';
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

function adminPointsUrl(array $overrides = []) {
    global $statusFilter, $sortOption, $currentPage, $perPage;

    $params = array_merge([
        'status' => $statusFilter,
        'sort' => $sortOption,
        'page' => $currentPage,
        'per_page' => $perPage,
    ], $overrides);

    return 'admin_points.php?' . http_build_query($params);
}

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        $error = "Your session token expired. Please try again.";
    } else {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null;
    
    if ($action === 'delete') {
        $deleteMessage = '';
        if (deletePointRequestIfAllowed($requestId, $userId, $deleteMessage)) {
            header("Location: " . adminPointsUrl(['deleted' => 1]));
            exit();
        }
        $error = $deleteMessage;
    } elseif (!in_array($action, ['approve', 'reject', 'pending'], true)) {
        $error = "Invalid review action.";
    } else {
        $statusMap = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'pending' => 'pending',
        ];
        $status = $statusMap[$action];
        
        if (updatePointRequestStatus($requestId, $status, $userId, $remarks)) {
            header("Location: " . adminPointsUrl(['success' => 1]));
            exit();
        } else {
            $error = "Failed to update request. Please try again.";
        }
    }
    }
}

$requestPage = getPointRequestsPaginated($statusFilter, $sortOption, $currentPage, $perPage);
$requests = $requestPage['requests'];
$totalRequests = $requestPage['total_count'];
$currentPage = $requestPage['current_page'];
$totalPages = $requestPage['total_pages'];
$perPage = $requestPage['per_page'];
$showingStart = $totalRequests > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
$showingEnd = $totalRequests > 0 ? min($showingStart + count($requests) - 1, $totalRequests) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Admin Point Management</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --amsa-wine: #5f2626;
        --amsa-wine-dark: #3b1118;
        --amsa-gold: #f4b942;
        --amsa-cream: #fff8ef;
        --amsa-soft: #f7f1ea;
        --amsa-text: #2b2020;
        --amsa-muted: #7b6f6a;
    }

    body.points-page {
        background:
            radial-gradient(circle at top left, rgba(244,185,66,.18), transparent 30%),
            linear-gradient(180deg, #fff8ef 0%, #f7f1ea 45%, #ffffff 100%);
        color: var(--amsa-text);
    }

    .points-hero {
        background:
            linear-gradient(135deg, rgba(59,17,24,.96), rgba(95,38,38,.92)),
            radial-gradient(circle at top right, rgba(244,185,66,.28), transparent 35%) !important;
        padding: 70px 0 !important;
        margin-bottom: 40px !important;
        border-bottom: 5px solid var(--amsa-gold);
        box-shadow: 0 18px 45px rgba(95,38,38,.22);
    }

    .points-hero h1 {
        font-weight: 900;
        letter-spacing: -1px;
    }

    .points-hero p {
        opacity: .9;
        font-size: 1.1rem;
    }

    .amsa-alert {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 26px rgba(95,38,38,.08);
    }

    .amsa-card,
    .card {
        border: 1px solid rgba(95,38,38,.08) !important;
        border-radius: 22px !important;
        box-shadow: 0 18px 45px rgba(95,38,38,.10) !important;
        background: rgba(255,255,255,.96);
        overflow: hidden;
    }

    .amsa-card form {
        padding: 6px;
    }

    .form-label {
        color: var(--amsa-wine-dark);
        font-size: .9rem;
    }

    .amsa-form-control,
    .form-select {
        border-radius: 14px !important;
        border: 1px solid #eadbd2 !important;
        min-height: 48px;
        background-color: #fffdfb;
        font-weight: 600;
    }

    .amsa-form-control:focus,
    .form-select:focus {
        border-color: var(--amsa-gold) !important;
        box-shadow: 0 0 0 .22rem rgba(244,185,66,.22) !important;
    }

    .amsa-btn,
    .btn {
        border-radius: 999px !important;
        font-weight: 800;
        transition: all .22s ease;
    }

    .amsa-btn:hover,
    .btn:hover {
        transform: translateY(-2px);
    }

    .amsa-btn-primary,
    .btn-primary {
        background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark)) !important;
        border: none !important;
        color: #fff !important;
        box-shadow: 0 12px 26px rgba(95,38,38,.22);
    }

    .amsa-btn-ghost,
    .btn-outline-primary {
        border: 1px solid var(--amsa-wine) !important;
        color: var(--amsa-wine) !important;
        background: transparent !important;
    }

    .amsa-btn-ghost:hover,
    .btn-outline-primary:hover {
        background: var(--amsa-wine) !important;
        color: #fff !important;
    }

    .stat-card {
        position: relative;
        background: #ffffff;
        border-radius: 22px;
        padding: 26px 20px;
        margin-bottom: 20px;
        box-shadow: 0 18px 42px rgba(95,38,38,.10);
        border: 1px solid rgba(95,38,38,.08);
        transition: .25s ease;
        overflow: hidden;
    }

    .stat-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: linear-gradient(90deg, var(--amsa-gold), var(--amsa-wine));
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 26px 60px rgba(95,38,38,.16);
    }

    .stat-icon {
        width: 58px;
        height: 58px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: linear-gradient(135deg, #fff4cf, #ffffff);
        color: var(--amsa-wine);
        font-size: 28px;
        margin-bottom: 8px;
        box-shadow: inset 0 0 0 1px rgba(244,185,66,.35);
    }

    .stat-card h3 {
        color: var(--amsa-wine-dark);
        font-size: 1.8rem;
        font-weight: 900;
        margin-bottom: 4px;
        word-break: break-word;
    }

    .stat-card p {
        font-weight: 700;
        color: var(--amsa-muted) !important;
    }

    .card-header {
        background:
            linear-gradient(135deg, #ffffff, #fff8ef) !important;
        border-bottom: 1px solid rgba(95,38,38,.08);
        padding: 20px 24px;
    }

    .card-header h5 {
        font-weight: 900;
        color: var(--amsa-wine-dark);
    }

    .table-responsive {
        border-radius: 18px;
        overflow-x: auto;
    }

    .amsa-table {
        margin-bottom: 0;
        vertical-align: middle;
    }

    .amsa-table thead th {
        background: var(--amsa-wine-dark);
        color: #fff;
        border: none;
        padding: 15px;
        font-size: .85rem;
        white-space: nowrap;
    }

    .amsa-table tbody td {
        padding: 16px 14px;
        border-color: #f0e5dc;
        font-size: .92rem;
    }

    .amsa-table tbody tr:hover {
        background: #fff8ef;
    }

    .profile-member-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .profile-avatar-sm {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--amsa-gold);
        background: #fff;
    }

    .status-badge {
        padding: 7px 13px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .status-pending {
        background: #fff4cf;
        color: #7a5800;
    }

    .status-approved {
        background: #e6f6ed;
        color: #167241;
    }

    .status-rejected {
        background: #fde9e9;
        color: #a92d2d;
    }

    .evidence-link {
        color: var(--amsa-wine);
        font-weight: 800;
        text-decoration: none;
    }

    .evidence-link:hover {
        color: var(--amsa-gold);
        text-decoration: none;
    }

    .amsa-btn-sm,
    .btn-sm {
        padding: 7px 12px !important;
        font-size: .78rem;
        margin: 2px;
    }

    .btn-success {
        background: #2f8f57 !important;
        border: none !important;
    }

    .btn-danger,
    .amsa-btn-danger {
        background: #b44444 !important;
        border: none !important;
        color: #fff !important;
    }

    .btn-outline-danger {
        border: 1px solid #b44444 !important;
        color: #b44444 !important;
        background: transparent !important;
    }

    .btn-outline-danger:hover {
        background: #b44444 !important;
        color: #fff !important;
    }

    .btn-outline-warning {
        border: 1px solid var(--amsa-gold) !important;
        color: #7a5800 !important;
        background: #fff9e6 !important;
    }

    .modal-content,
    .amsa-modal {
        border: none;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 26px 70px rgba(43,32,32,.25);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark));
        color: #fff;
        border-bottom: 4px solid var(--amsa-gold);
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        background: #fff8ef;
        border-top: 1px solid #eadbd2;
    }

    .modal-img {
        max-width: 100%;
        border-radius: 18px;
        box-shadow: 0 12px 30px rgba(0,0,0,.12);
    }

    .amsa-empty-state {
        background: #fff8ef;
        border: 1px dashed rgba(95,38,38,.22);
        border-radius: 22px;
        padding: 45px 20px;
        text-align: center;
    }

    .amsa-empty-state i {
        color: var(--amsa-wine) !important;
    }

    .pagination .page-link {
        border: none;
        color: var(--amsa-wine);
        font-weight: 800;
        border-radius: 12px;
        margin: 2px;
    }

    .pagination .page-item.active .page-link {
        background: var(--amsa-wine);
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        color: #aaa;
        background: #f7f1ea;
    }

    @media (max-width: 991.98px) {
        .points-hero {
            padding: 50px 0 !important;
        }

        .points-hero h1 {
            font-size: 2rem;
        }

        .stat-card {
            padding: 22px 16px;
        }

        .card-body {
            padding: 18px;
        }
    }

    @media (max-width: 575.98px) {
        .container {
            padding-left: 14px;
            padding-right: 14px;
        }

        .points-hero h1 {
            font-size: 1.7rem;
        }

        .points-hero p {
            font-size: .95rem;
        }

        .amsa-btn-sm,
        .btn-sm {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Point Request Management</h1>
            <p class="text-white">Review and manage member point requests</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success amsa-alert amsa-alert-success alert-dismissible fade show" role="alert">
                Request updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert alert-success amsa-alert amsa-alert-success alert-dismissible fade show" role="alert">
                Point request deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger amsa-alert amsa-alert-error alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="alert alert-info amsa-alert amsa-alert-info">
            Only member accounts are eligible for leaderboard ranking.
        </div>

        <div class="amsa-card mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status Filter</label>
                    <select name="status" class="form-select amsa-form-control">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Sort By</label>
                    <select name="sort" class="form-select amsa-form-control">
                        <option value="newest" <?php echo $sortOption === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                        <option value="oldest" <?php echo $sortOption === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                        <option value="points_desc" <?php echo $sortOption === 'points_desc' ? 'selected' : ''; ?>>Points high to low</option>
                        <option value="points_asc" <?php echo $sortOption === 'points_asc' ? 'selected' : ''; ?>>Points low to high</option>
                        <option value="status" <?php echo $sortOption === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Rows Per Page</label>
                    <select name="per_page" class="form-select amsa-form-control">
                        <?php foreach ([10, 25, 50] as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="page" value="1">
                    <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary w-100">Apply</button>
                </div>
                <div class="col-md-2">
                    <a href="admin_points.php" class="btn btn-outline-primary amsa-btn amsa-btn-ghost w-100">Reset</a>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card amsa-stat-card text-center">
                    <i class="fas fa-chart-line stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['total_points_awarded']; ?></h3>
                    <p class="text-muted mb-0">Total Points Awarded</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card amsa-stat-card text-center">
                    <i class="fas fa-file-alt stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['total_requests']; ?></h3>
                    <p class="text-muted mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card amsa-stat-card text-center">
                    <i class="fas fa-clock stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['pending_requests']; ?></h3>
                    <p class="text-muted mb-0">Pending Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card amsa-stat-card text-center">
                    <i class="fas fa-trophy stat-icon"></i>
                    <h3 class="mt-2"><?php echo isset($statistics['top_user']['name']) ? $statistics['top_user']['name'] : 'N/A'; ?></h3>
                    <p class="text-muted mb-0">Top Member (<?php echo isset($statistics['top_user']['total_points']) ? $statistics['top_user']['total_points'] : 0; ?> pts)</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm amsa-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Point Requests</h5>
            </div>
            <div class="card-body">
                <?php if(empty($requests)): ?>
                    <div class="amsa-empty-state mb-0">
                        <i class="fas fa-clipboard-list fa-2x mb-3 text-primary"></i>
                        <h4>No point requests found for this filter.</h4>
                        <p class="mb-0">Try another status filter or reset the request list.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive amsa-table-wrap">
                        <table class="table table-hover amsa-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Activity</th>
                                    <th>Points</th>
                                    <th>Description</th>
                                    <th>Evidence</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requests as $request): ?>
                                <?php $requestUserRole = normalizeRole($request['user_role'] ?? 'member'); ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <span class="profile-member-cell">
                                            <img src="<?php echo htmlspecialchars(profileImageUrl($request['user_profile_image'] ?? null, '../')); ?>" class="profile-avatar-sm" alt="Member profile image">
                                            <span>
                                                <?php echo htmlspecialchars($request['user_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($request['user_email']); ?></small>
                                                <?php if ($requestUserRole !== 'member'): ?>
                                                    <br><span class="badge bg-warning text-dark amsa-badge amsa-badge-pending">Admin Account</span>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['category_name']); ?></td>
                                    <td><strong><?php echo $request['points']; ?></strong></td>
                                    <td><?php echo substr(htmlspecialchars($request['description']), 0, 50); ?>...</td>
                                    <td>
                                        <?php if($request['eop_evidence']): ?>
                                            <a href="#" class="evidence-link" data-bs-toggle="modal" 
                                               data-bs-target="#evidenceModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-file-alt"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php
                                            $statusActions = [];
                                            if ($request['status'] === 'pending') {
                                                $statusActions = [
                                                    ['action' => 'approve', 'label' => 'Approve', 'class' => 'btn-success amsa-btn-primary', 'icon' => 'fa-check', 'title' => 'Confirm Approval', 'message' => 'Are you sure you want to approve this point request and award points?'],
                                                    ['action' => 'reject', 'label' => 'Reject', 'class' => 'btn-danger amsa-btn-danger', 'icon' => 'fa-times', 'title' => 'Confirm Rejection', 'message' => 'Are you sure you want to reject this point request?'],
                                                ];
                                            } elseif ($request['status'] === 'approved') {
                                                $statusActions = [
                                                    ['action' => 'pending', 'label' => 'Revert to Pending', 'class' => 'btn-outline-warning', 'icon' => 'fa-undo', 'title' => 'Confirm Revert to Pending', 'message' => 'Are you sure you want to move this request back to pending? If points were already awarded, they will be removed.'],
                                                    ['action' => 'reject', 'label' => 'Mark Rejected', 'class' => 'btn-outline-danger amsa-btn-danger', 'icon' => 'fa-times', 'title' => 'Confirm Rejection', 'message' => 'Are you sure you want to reject this point request?'],
                                                ];
                                            } elseif ($request['status'] === 'rejected') {
                                                $statusActions = [
                                                    ['action' => 'pending', 'label' => 'Revert to Pending', 'class' => 'btn-outline-warning', 'icon' => 'fa-undo', 'title' => 'Confirm Revert to Pending', 'message' => 'Are you sure you want to move this request back to pending? If points were already awarded, they will be removed.'],
                                                    ['action' => 'approve', 'label' => 'Approve', 'class' => 'btn-success amsa-btn-primary', 'icon' => 'fa-check', 'title' => 'Confirm Approval', 'message' => 'Are you sure you want to approve this point request and award points?'],
                                                ];
                                            }
                                            $deleteMessage = $request['status'] === 'approved'
                                                ? 'This action will delete the request and remove awarded points from the member. This cannot be undone.'
                                                : 'Are you sure you want to delete this point request?';
                                            $deleteTitle = $request['status'] === 'approved' ? 'Confirm Delete Approved Request' : 'Confirm Deletion';
                                        ?>
                                        <?php if ($request['status'] === 'approved'): ?>
                                            <small class="d-block text-warning fw-bold mb-2">Changing this approved request will adjust the member's total points.</small>
                                        <?php endif; ?>
                                        <?php foreach ($statusActions as $statusAction): ?>
                                            <?php $modalId = 'statusActionModal' . (int) $request['id'] . ucfirst($statusAction['action']); ?>
                                            <button type="button" class="btn btn-sm amsa-btn amsa-btn-sm <?php echo htmlspecialchars($statusAction['class']); ?>" data-bs-toggle="modal" data-bs-target="#<?php echo htmlspecialchars($modalId); ?>">
                                                <i class="fas <?php echo htmlspecialchars($statusAction['icon']); ?>"></i> <?php echo htmlspecialchars($statusAction['label']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                            <form method="POST" class="d-inline" id="deletePointRequestForm<?php echo (int) $request['id']; ?>">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="btn btn-sm btn-outline-danger amsa-btn amsa-btn-danger amsa-btn-sm" data-bs-toggle="modal" data-bs-target="#deletePointRequestModal<?php echo (int) $request['id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                    </td>
                                </tr>

                                <?php foreach ($statusActions as $statusAction): ?>
                                    <?php $modalId = 'statusActionModal' . (int) $request['id'] . ucfirst($statusAction['action']); ?>
                                    <div class="modal fade" id="<?php echo htmlspecialchars($modalId); ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content amsa-modal">
                                                <form method="POST">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                                    <input type="hidden" name="action" value="<?php echo htmlspecialchars($statusAction['action']); ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?php echo htmlspecialchars($statusAction['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><?php echo htmlspecialchars($statusAction['message']); ?></p>
                                                        <?php if ($request['status'] === 'approved'): ?>
                                                            <p class="small text-warning fw-bold">Changing this approved request will adjust the member's total points.</p>
                                                        <?php endif; ?>
                                                        <div class="mb-0">
                                                            <label class="form-label">Remarks (Optional)</label>
                                                            <textarea name="remarks" class="form-control amsa-form-control" rows="3" placeholder="Add any comments about this request..."><?php echo htmlspecialchars($request['admin_remarks'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Confirm</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="modal fade" id="deletePointRequestModal<?php echo (int) $request['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content amsa-modal">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($deleteTitle); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php echo htmlspecialchars($deleteMessage); ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" form="deletePointRequestForm<?php echo (int) $request['id']; ?>" class="btn btn-danger amsa-btn amsa-btn-danger">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evidence Modal -->
                                <div class="modal fade" id="evidenceModal<?php echo $request['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Evidence - <?php echo htmlspecialchars($request['category_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <?php 
                                                $fileExt = pathinfo($request['eop_evidence'], PATHINFO_EXTENSION);
                                                if(in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png'])): ?>
                                                    <img src="evidence.php?id=<?php echo (int) $request['id']; ?>" class="modal-img" alt="Evidence">
                                                <?php else: ?>
                                                    <a href="evidence.php?id=<?php echo (int) $request['id']; ?>" target="_blank" class="btn btn-primary amsa-btn amsa-btn-primary">
                                                        <i class="fas fa-download"></i> Download File
                                                    </a>
                                                <?php endif; ?>
                                                <p class="mt-3"><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="amsa-card mt-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="text-muted">
                                Showing <?php echo (int) $showingStart; ?>&ndash;<?php echo (int) $showingEnd; ?> of <?php echo (int) $totalRequests; ?> requests
                            </div>
                            <nav aria-label="Point request pagination">
                                <ul class="pagination mb-0 flex-wrap">
                                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo htmlspecialchars(adminPointsUrl(['page' => max(1, $currentPage - 1), 'per_page' => $perPage])); ?>">Previous</a>
                                    </li>
                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                                    ?>
                                        <li class="page-item <?php echo $pageNumber === $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars(adminPointsUrl(['page' => $pageNumber, 'per_page' => $perPage])); ?>"><?php echo (int) $pageNumber; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo htmlspecialchars(adminPointsUrl(['page' => min($totalPages, $currentPage + 1), 'per_page' => $perPage])); ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
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
