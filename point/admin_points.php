<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$statistics = getPointStatistics();
$requests = getAllPointRequests();

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null;
    
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    if (updatePointRequestStatus($requestId, $status, $userId, $remarks)) {
        $success = "Request has been " . $status . "!";
        // Refresh the page to show updated data
        header("Location: admin_points.php?success=1");
        exit();
    } else {
        $error = "Failed to update request. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Admin Point Management</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: #fff; }
        .status-rejected { background: #dc3545; color: #fff; }
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
<body>
    <div class="container-fluid bg-primary py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Point Request Management</h1>
            <p class="text-white">Review and manage member point requests</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Request updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-chart-line stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['total_points_awarded']; ?></h3>
                    <p class="text-muted mb-0">Total Points Awarded</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-file-alt stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['total_requests']; ?></h3>
                    <p class="text-muted mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-clock stat-icon"></i>
                    <h3 class="mt-2"><?php echo $statistics['pending_requests']; ?></h3>
                    <p class="text-muted mb-0">Pending Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-trophy stat-icon"></i>
                    <h3 class="mt-2"><?php echo isset($statistics['top_user']['name']) ? $statistics['top_user']['name'] : 'N/A'; ?></h3>
                    <p class="text-muted mb-0">Top Member (<?php echo isset($statistics['top_user']['total_points']) ? $statistics['top_user']['total_points'] : 0; ?> pts)</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Point Requests</h5>
            </div>
            <div class="card-body">
                <?php if(empty($requests)): ?>
                    <div class="alert alert-info">No point requests found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['user_name']); ?>
                                        <br><small class="text-muted"><?php echo $request['user_email']; ?></small>
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
                                        <?php if($request['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-success" onclick="reviewRequest(<?php echo $request['id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="reviewRequest(<?php echo $request['id']; ?>, 'reject')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <?php echo ucfirst($request['status']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

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
                                                if(in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                    <img src="<?php echo $request['eop_evidence']; ?>" class="modal-img" alt="Evidence">
                                                <?php else: ?>
                                                    <a href="<?php echo $request['eop_evidence']; ?>" target="_blank" class="btn btn-primary">
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

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Review Point Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="request_id">
                        <input type="hidden" name="action" id="action">
                        <div class="mb-3">
                            <label class="form-label">Remarks (Optional)</label>
                            <textarea name="remarks" class="form-control" rows="3" 
                                      placeholder="Add any comments about this request..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function reviewRequest(requestId, action) {
            document.getElementById('request_id').value = requestId;
            document.getElementById('action').value = action;
            var myModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            myModal.show();
        }
    </script>
</body>
</html>