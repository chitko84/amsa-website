<?php
$pointsCurrentPage = basename($_SERVER['PHP_SELF']);
$pointsUser = currentUserId() ? getUserProfile(currentUserId()) : null;
$pointsUserName = $pointsUser['name'] ?? ($_SESSION['user_name'] ?? 'AMSA User');
$pointsUserRole = currentUserRole();
$pointsRoleLabel = roleLabel($pointsUserRole);
$pointsAvatar = profileImageUrl($pointsUser['profile_image'] ?? null, '../');
$pointsIsAdmin = isAdminRole($pointsUserRole);
$pointsIsSystemAdmin = isSystemAdminRole($pointsUserRole);

function pointsNavActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>
<nav class="navbar navbar-light bg-light points-navbar border-bottom mb-4">
    <div class="container points-nav-container">
        <a class="navbar-brand fw-bold points-nav-brand" href="my_points.php">
            <img src="../img/logo.png" class="points-nav-logo" alt="AMSA">
            <span>AMSA Points</span>
        </a>

        <div class="points-nav-links">
            <a class="nav-link <?php echo pointsNavActive('my_points.php'); ?>" href="my_points.php">Dashboard</a>
            <a class="nav-link <?php echo pointsNavActive('point_request.php'); ?>" href="point_request.php">Submit Activity</a>
            <a class="nav-link <?php echo pointsNavActive('leaderboard.php'); ?>" href="leaderboard.php">Leaderboard</a>
            <a class="nav-link <?php echo pointsNavActive('profile.php'); ?>" href="profile.php">Profile</a>
            <?php if ($pointsIsAdmin): ?>
                <a class="nav-link <?php echo pointsNavActive('admin_points.php'); ?>" href="admin_points.php">Admin Review</a>
                <a class="nav-link <?php echo pointsNavActive('point_categories_admin.php'); ?>" href="point_categories_admin.php">Categories</a>
            <?php endif; ?>
        </div>

        <div class="points-nav-profile dropdown">
            <button class="points-profile-toggle dropdown-toggle" type="button" id="pointsProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo htmlspecialchars($pointsAvatar); ?>" class="points-nav-avatar" alt="Profile image">
                <span><?php echo htmlspecialchars($pointsUserName); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end points-profile-menu" aria-labelledby="pointsProfileDropdown">
                <li class="dropdown-header">
                    <strong><?php echo htmlspecialchars($pointsUserName); ?></strong>
                    <span><?php echo htmlspecialchars($pointsRoleLabel); ?></span>
                </li>
                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                <li><a class="dropdown-item" href="my_points.php">Dashboard</a></li>
                <li><a class="dropdown-item" href="leaderboard.php">Leaderboard</a></li>
                <?php if ($pointsIsAdmin): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="admin_points.php">Admin Review</a></li>
                    <li><a class="dropdown-item" href="point_categories_admin.php">Categories</a></li>
                    <li><a class="dropdown-item" href="../admin/dashboard.php">Admin Panel</a></li>
                <?php endif; ?>
                <?php if ($pointsIsSystemAdmin): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../admin/admin_users.php">Admin Users</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>

        <button class="points-mobile-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#pointsMobileMenu" aria-controls="pointsMobileMenu" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <div class="collapse points-mobile-menu" id="pointsMobileMenu">
            <div class="points-mobile-profile">
                <img src="<?php echo htmlspecialchars($pointsAvatar); ?>" class="points-mobile-avatar" alt="Profile image">
                <strong><?php echo htmlspecialchars($pointsUserName); ?></strong>
                <span><?php echo htmlspecialchars($pointsRoleLabel); ?></span>
            </div>
            <div class="points-mobile-links">
                <a class="nav-link <?php echo pointsNavActive('my_points.php'); ?>" href="my_points.php">Dashboard</a>
                <a class="nav-link <?php echo pointsNavActive('point_request.php'); ?>" href="point_request.php">Submit Activity</a>
                <a class="nav-link <?php echo pointsNavActive('leaderboard.php'); ?>" href="leaderboard.php">Leaderboard</a>
                <a class="nav-link <?php echo pointsNavActive('profile.php'); ?>" href="profile.php">Profile</a>
                <?php if ($pointsIsAdmin): ?>
                    <a class="nav-link <?php echo pointsNavActive('admin_points.php'); ?>" href="admin_points.php">Admin Review</a>
                    <a class="nav-link <?php echo pointsNavActive('point_categories_admin.php'); ?>" href="point_categories_admin.php">Categories</a>
                    <a class="nav-link" href="../admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
                <?php if ($pointsIsSystemAdmin): ?>
                    <a class="nav-link" href="../admin/admin_users.php">Admin Users</a>
                <?php endif; ?>
                <a class="nav-link text-danger" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
