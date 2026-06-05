<?php
require_once '../config/database.php';
requireMember('login.php');

$filter = $_GET['filter'] ?? '25';
$limitMap = [
    '10' => 10,
    '25' => 25,
    'all' => 0,
];
$limit = $limitMap[$filter] ?? 25;
$leaderboard = getLeaderboard($limit);
$currentUserId = currentUserId();
$currentUserRole = currentUserRole();
$isAdminView = isAdminRole($currentUserRole);
$currentRank = $currentUserId ? getUserRank($currentUserId) : null;
$topThree = array_slice($leaderboard, 0, 3);

function leaderboardIdentity(array $row, $currentUserId, $isAdminView) {
    if ($isAdminView) {
        return htmlspecialchars($row['name']);
    }

    $memberLabel = 'Member #' . (int) $row['id'];
    if ((int) $row['id'] === (int) $currentUserId) {
        return 'You - ' . $memberLabel;
    }

    return $memberLabel;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Points Leaderboard</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        .current-user-row { background: #fff3cd; }
        .rank-badge { min-width: 42px; display: inline-block; }
    </style>
</head>
<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid bg-primary points-hero py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">AMSA Points Leaderboard</h1>
            <p class="text-white">Member rankings based on approved activities and total points</p>
        </div>
    </div>

    <div class="container points-page-shell mb-5">
        <?php if ($currentRank): ?>
            <div class="card shadow-sm mb-4 border-primary amsa-card leaderboard-summary">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <span class="text-muted d-block"><?php echo $isAdminView ? 'Member' : 'Identity'; ?></span>
                            <strong>
                                <?php
                                if ($isAdminView) {
                                    echo htmlspecialchars($currentRank['name']);
                                } else {
                                    echo 'You - Member #' . (int) $currentRank['id'];
                                }
                                ?>
                            </strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">Your Rank</span>
                            <h2 class="mb-0">#<?php echo (int) $currentRank['rank']; ?></h2>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">Total Points</span>
                            <strong><?php echo (int) $currentRank['total_points']; ?></strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">Approved Requests</span>
                            <strong><?php echo (int) $currentRank['approved_request_count']; ?></strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">Latest Approved</span>
                            <strong>
                                <?php echo $currentRank['latest_approved_activity_date'] ? date('M d, Y', strtotime($currentRank['latest_approved_activity_date'])) : 'N/A'; ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($topThree)): ?>
            <div class="leaderboard-podium">
                <?php foreach ($topThree as $row): ?>
                    <div class="amsa-card podium-card">
                        <span class="podium-rank">#<?php echo (int) $row['rank']; ?></span>
                        <h5><?php echo leaderboardIdentity($row, $currentUserId, $isAdminView); ?></h5>
                        <?php if ($isAdminView): ?>
                            <img src="<?php echo htmlspecialchars(profileImageUrl($row['profile_image'] ?? null, '../')); ?>" class="profile-avatar-sm mb-2" alt="Member profile image">
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($row['email']); ?></p>
                            <p class="text-muted small mb-2">User ID: <?php echo (int) $row['id']; ?></p>
                        <?php endif; ?>
                        <strong><?php echo (int) $row['total_points']; ?> points</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="points-section-header">
            <div>
                <h3>Rankings</h3>
                <p>Ranked by total points, approved requests, and latest approved activity.</p>
            </div>
            <div class="points-filter-group" role="group" aria-label="Leaderboard filter">
                <a href="leaderboard.php?filter=10" class="btn btn-outline-primary amsa-btn amsa-btn-ghost <?php echo $filter === '10' ? 'active' : ''; ?>">Top 10</a>
                <a href="leaderboard.php?filter=25" class="btn btn-outline-primary amsa-btn amsa-btn-ghost <?php echo !in_array($filter, ['10', 'all'], true) ? 'active' : ''; ?>">Top 25</a>
                <a href="leaderboard.php?filter=all" class="btn btn-outline-primary amsa-btn amsa-btn-ghost <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            </div>
        </div>

        <div class="card shadow-sm amsa-card">
            <div class="card-body">
                <?php if (empty($leaderboard)): ?>
                    <div class="amsa-empty-state mb-0">
                        <i class="fas fa-trophy fa-2x mb-3 text-primary"></i>
                        <h4>No Leaderboard Data Yet</h4>
                        <p class="mb-0">Rankings will appear after members receive approved points.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive amsa-table-wrap">
                        <table class="table table-hover align-middle amsa-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Member</th>
                                    <?php if ($isAdminView): ?>
                                        <th>Photo</th>
                                        <th>User ID</th>
                                        <th>Email</th>
                                    <?php endif; ?>
                                    <th>Total Points</th>
                                    <th>Approved Requests</th>
                                    <th>Latest Approved Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $row): ?>
                                    <?php $isCurrentUser = (int) $row['id'] === (int) $currentUserId; ?>
                                    <tr class="<?php echo $isCurrentUser ? 'current-user-row' : ''; ?>">
                                        <td><span class="badge bg-primary rank-badge amsa-badge">#<?php echo (int) $row['rank']; ?></span></td>
                                        <td>
                                            <?php echo leaderboardIdentity($row, $currentUserId, $isAdminView); ?>
                                            <?php if ($isCurrentUser && $isAdminView): ?>
                                                <span class="badge bg-warning text-dark ms-2 amsa-badge amsa-badge-pending">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($isAdminView): ?>
                                            <td><img src="<?php echo htmlspecialchars(profileImageUrl($row['profile_image'] ?? null, '../')); ?>" class="profile-avatar-sm" alt="Member profile image"></td>
                                            <td><?php echo (int) $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <?php endif; ?>
                                        <td><strong><?php echo (int) $row['total_points']; ?></strong></td>
                                        <td><?php echo (int) $row['approved_request_count']; ?></td>
                                        <td>
                                            <?php echo $row['latest_approved_activity_date'] ? date('M d, Y', strtotime($row['latest_approved_activity_date'])) : 'N/A'; ?>
                                        </td>
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
