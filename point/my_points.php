<?php
session_start();
require_once '../config/database.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$userPoints = getUserPoints($userId);
$userRequests = getUserPointRequests($userId);

// Count approved and pending requests
$approvedCount = 0;
$pendingCount = 0;
foreach($userRequests as $request) {
    if($request['status'] == 'approved') $approvedCount++;
    if($request['status'] == 'pending') $pendingCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - My Points</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid bg-primary py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">My Points Dashboard</h1>
            <p class="text-white">Track your achievements and points earned</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-star fa-3x text-primary mb-3"></i>
                        <h2><?php echo $userPoints; ?></h2>
                        <p class="text-muted">Total Points Earned</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h2><?php echo $approvedCount; ?></h2>
                        <p class="text-muted">Approved Activities</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <h2><?php echo $pendingCount; ?></h2>
                        <p class="text-muted">Pending Requests</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Points History</h5>
            </div>
            <div class="card-body">
                <?php if(empty($userRequests)): ?>
                    <div class="alert alert-info">No point requests found. <a href="point_request.php">Submit your first request</a></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($userRequests as $request): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['category_name']); ?></td>
                                    <td><strong><?php echo $request['status'] == 'approved' ? '+' . $request['points'] : $request['points']; ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['admin_remarks'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>