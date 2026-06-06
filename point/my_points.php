<?php
require_once '../config/database.php';
requireMember('login.php');

$userId = currentUserId();
$userPoints = getUserPoints($userId);
$userRequests = getUserPointRequests($userId);
$historyStatusRaw = $_GET['status'] ?? 'all';
$historyStatus = in_array($historyStatusRaw, ['all', 'pending', 'approved', 'rejected'], true) ? $historyStatusRaw : 'all';
$historySortRaw = $_GET['sort'] ?? 'newest';
$historySort = in_array($historySortRaw, ['newest', 'oldest', 'points_desc', 'points_asc'], true) ? $historySortRaw : 'newest';
$historyPage = max(1, (int) ($_GET['page'] ?? 1));
$historyPerPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($historyPerPage, [10, 25, 50], true)) {
    $historyPerPage = 10;
}
$historyPageData = getUserPointRequestsPaginated($userId, $historyStatus, $historySort, $historyPage, $historyPerPage);
$historyRequests = $historyPageData['requests'];
$historyTotal = $historyPageData['total_count'];
$historyPage = $historyPageData['current_page'];
$historyTotalPages = $historyPageData['total_pages'];
$historyPerPage = $historyPageData['per_page'];
$historyStart = $historyTotal > 0 ? (($historyPage - 1) * $historyPerPage) + 1 : 0;
$historyEnd = $historyTotal > 0 ? min($historyStart + count($historyRequests) - 1, $historyTotal) : 0;
$currentRank = function_exists('getUserRank') ? getUserRank($userId) : null;

function myPointsHistoryUrl(array $overrides = []) {
    global $historyStatus, $historySort, $historyPerPage, $historyPage;

    $params = array_merge([
        'status' => $historyStatus,
        'sort' => $historySort,
        'per_page' => $historyPerPage,
        'page' => $historyPage,
    ], $overrides);
    return 'my_points.php?' . http_build_query($params);
}

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
    <link href="../assets/css/amsa-chatbot.css" rel="stylesheet">
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
                    <h2><?php echo $currentRank ? '#' . (int) $currentRank['rank'] : 'Not ranked yet'; ?></h2>
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
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select amsa-form-control"><option value="all" <?php echo $historyStatus === 'all' ? 'selected' : ''; ?>>All</option><option value="pending" <?php echo $historyStatus === 'pending' ? 'selected' : ''; ?>>Pending</option><option value="approved" <?php echo $historyStatus === 'approved' ? 'selected' : ''; ?>>Approved</option><option value="rejected" <?php echo $historyStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option></select></div>
                        <div class="col-md-3"><label class="form-label">Sort</label><select name="sort" class="form-select amsa-form-control"><option value="newest" <?php echo $historySort === 'newest' ? 'selected' : ''; ?>>Newest First</option><option value="oldest" <?php echo $historySort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option><option value="points_desc" <?php echo $historySort === 'points_desc' ? 'selected' : ''; ?>>Points High-Low</option><option value="points_asc" <?php echo $historySort === 'points_asc' ? 'selected' : ''; ?>>Points Low-High</option></select></div>
                        <div class="col-md-2"><label class="form-label">Rows</label><select name="per_page" class="form-select amsa-form-control"><?php foreach ([10,25,50] as $option): ?><option value="<?php echo $option; ?>" <?php echo $historyPerPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4 d-flex align-items-end gap-2"><input type="hidden" name="page" value="1"><button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Apply</button><a href="my_points.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Reset</a></div>
                    </form>
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
                    <?php if (empty($historyRequests)): ?>
                        <div class="amsa-empty-state mb-0">
                            <i class="fas fa-clipboard-list fa-2x mb-3 text-primary"></i>
                            <h4>No requests found for this filter.</h4>
                            <p class="mb-0">Try another status filter or reset your request history.</p>
                        </div>
                    <?php else: ?>
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
                                <?php foreach ($historyRequests as $request): ?>
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
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <span class="text-muted">Showing <?php echo (int) $historyStart; ?>&ndash;<?php echo (int) $historyEnd; ?> of <?php echo (int) $historyTotal; ?> requests</span>
                        <div class="btn-group">
                            <a class="btn btn-outline-primary amsa-btn <?php echo $historyPage <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(myPointsHistoryUrl(['page' => max(1, $historyPage - 1)])); ?>">Previous</a>
                            <a class="btn btn-outline-primary amsa-btn <?php echo $historyPage >= $historyTotalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(myPointsHistoryUrl(['page' => min($historyTotalPages, $historyPage + 1)])); ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/amsa-chatbot.js"></script>
    <script>
        window.AmsaChatbot && window.AmsaChatbot.init({ preset: 'points' });
    </script>
</body>
</html>
