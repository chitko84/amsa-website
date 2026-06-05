<?php
require_once '../config/database.php';
requireMember('login.php');

$userId = currentUserId();
$userPoints = getUserPoints($userId);
$userRequests = getUserPointRequests($userId);
$currentRank = function_exists('getUserRank') ? getUserRank($userId) : null;

$approvedCount = 0;
$pendingCount = 0;
$rejectedCount = 0;
foreach ($userRequests as $request) {
    if ($request['status'] === 'approved') {
        $approvedCount++;
    } elseif ($request['status'] === 'pending') {
        $pendingCount++;
    } elseif ($request['status'] === 'rejected') {
        $rejectedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - My Points</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">My Points Dashboard</h1>
            <p class="text-white">Track your achievements and points earned</p>
        </div>
    </div>

    <div class="container points-page-shell mb-5">
        <div class="points-stat-grid">
            <div>
                <div class="amsa-stat-card points-stat-card primary-metric">
                    <span class="stat-icon"><i class="fas fa-star"></i></span>
                    <h2><?php echo (int) $userPoints; ?></h2>
                    <p class="text-muted mb-0">Total Points</p>
                </div>
            </div>
            <div>
                <div class="amsa-stat-card points-stat-card">
                    <span class="stat-icon"><i class="fas fa-trophy"></i></span>
                    <h2><?php echo $currentRank ? '#' . (int) $currentRank['rank'] : 'N/A'; ?></h2>
                    <p class="text-muted mb-0">Current Rank</p>
                </div>
            </div>
            <div>
                <div class="amsa-stat-card points-stat-card">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <h2><?php echo (int) $pendingCount; ?></h2>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
            <div>
                <div class="amsa-stat-card points-stat-card">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <h2><?php echo (int) $approvedCount; ?></h2>
                    <p class="text-muted mb-0">Approved</p>
                </div>
            </div>
            <div>
                <div class="amsa-stat-card points-stat-card">
                    <span class="stat-icon"><i class="fas fa-times-circle"></i></span>
                    <h2><?php echo (int) $rejectedCount; ?></h2>
                    <p class="text-muted mb-0">Rejected</p>
                </div>
            </div>
        </div>

        <div class="points-section-header">
            <div>
                <h3>Recent Submissions</h3>
                <p>Track each request from pending review to approved points.</p>
            </div>
            <a href="point_request.php" class="btn btn-primary amsa-btn amsa-btn-primary">Submit Activity</a>
        </div>

        <div class="card shadow-sm amsa-card">
            <div class="card-body">
                <?php if (empty($userRequests)): ?>
                    <div class="amsa-empty-state mb-0">
                        <i class="fas fa-clipboard-list fa-2x mb-3 text-primary"></i>
                        <h4>No Submissions Yet</h4>
                        <p>Submit your first AMSA activity to start building your points history.</p>
                        <a href="point_request.php" class="btn btn-primary amsa-btn amsa-btn-primary">Submit Activity</a>
                    </div>
                <?php else: ?>
                    <div class="points-card-list mb-4">
                        <?php foreach (array_slice($userRequests, 0, 4) as $request): ?>
                            <div class="amsa-card points-activity-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($request['category_name']); ?></h5>
                                    <span class="amsa-badge amsa-badge-<?php echo htmlspecialchars($request['status']); ?>"><?php echo ucfirst(htmlspecialchars($request['status'])); ?></span>
                                </div>
                                <p class="mb-3"><?php echo htmlspecialchars($request['description']); ?></p>
                                <div class="points-activity-meta">
                                    <span><i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($request['request_date'])); ?></span>
                                    <span><i class="fas fa-star me-1"></i><?php echo (int) $request['points']; ?> points</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="table-responsive amsa-table-wrap">
                        <table class="table align-middle amsa-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Evidence</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRequests as $request): ?>
                                    <?php
                                    $badge = $request['status'] === 'approved' ? 'success' : ($request['status'] === 'pending' ? 'warning text-dark' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['category_name']); ?></td>
                                        <td><strong><?php echo $request['status'] === 'approved' ? '+' : ''; ?><?php echo (int) $request['points']; ?></strong></td>
                                        <td><span class="badge bg-<?php echo $badge; ?> amsa-badge amsa-badge-<?php echo htmlspecialchars($request['status']); ?>"><?php echo ucfirst(htmlspecialchars($request['status'])); ?></span></td>
                                        <td>
                                            <?php if ($request['eop_evidence']): ?>
                                                <a href="evidence.php?id=<?php echo (int) $request['id']; ?>" target="_blank">View</a>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
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
