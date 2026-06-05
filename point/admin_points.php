<?php
require_once '../config/database.php';
requireAdmin('../admin/login.php');

$userId = currentUserId();

$statistics = getPointStatistics();
$requests = getAllPointRequests();

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
            header("Location: admin_points.php?deleted=1");
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
            header("Location: admin_points.php?success=1");
            exit();
        } else {
            $error = "Failed to update request. Please try again.";
        }
    }
    }
}
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 40px;
            color: #8B3A3A;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: var(--amsa-color-gold, #f4b942); color: var(--amsa-color-text, #2b2020); }
        .status-approved { background: var(--amsa-color-success, #2f8f57); color: #fff; }
        .status-rejected { background: var(--amsa-color-error, #b44444); color: #fff; }
        .evidence-link {
            color: #8B3A3A;
            text-decoration: none;
        }
        .evidence-link:hover {
            text-decoration: underline;
        }
        .modal-img {
            max-width: 100%;
            border-radius: 10px;
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
                    <div class="alert alert-info amsa-alert amsa-alert-info amsa-empty-state">No point requests found.</div>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
