<?php
function adminActive($pages) {
    $pages = (array) $pages;
    return in_array(basename($_SERVER['PHP_SELF']), $pages, true) ? 'active' : '';
}

$isSystemAdmin = canManageSettings();
?>
<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="../img/logo.png" alt="AMSA Logo">
        <h4>AMSA Admin</h4>
        <small>Organization Management</small>
    </div>
    <nav class="admin-menu" aria-label="Admin navigation">
        <div class="admin-menu-section">Dashboard</div>
        <a href="dashboard.php" class="<?php echo adminActive('dashboard.php'); ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>

        <div class="admin-menu-section">Content</div>
        <a href="add_news.php" class="<?php echo adminActive(['add_news.php','edit_news.php']); ?>"><i class="fas fa-newspaper"></i>News</a>
        <a href="add_event.php" class="<?php echo adminActive(['add_event.php','edit_event.php']); ?>"><i class="fas fa-calendar-alt"></i>Events</a>
        <a href="add_content.php?type=achievement" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'add_content.php' && ($_GET['type'] ?? '') === 'achievement') || (basename($_SERVER['PHP_SELF']) === 'edit_content.php' && ($_GET['type'] ?? '') === 'achievement') ? 'active' : ''; ?>"><i class="fas fa-award"></i>Achievements</a>
        <a href="add_content.php?type=testimonial" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'add_content.php' && ($_GET['type'] ?? '') === 'testimonial') || (basename($_SERVER['PHP_SELF']) === 'edit_content.php' && ($_GET['type'] ?? '') === 'testimonial') ? 'active' : ''; ?>"><i class="fas fa-comment-dots"></i>Testimonials</a>
        <a href="contact_messages.php" class="<?php echo adminActive('contact_messages.php'); ?>"><i class="fas fa-envelope-open-text"></i>Contact Messages</a>

        <div class="admin-menu-section">AMSA Points</div>
        <a href="../point/admin_points.php"><i class="fas fa-clipboard-check"></i>Review Requests</a>
        <a href="../point/point_categories_admin.php"><i class="fas fa-list"></i>Categories</a>
        <a href="../point/leaderboard.php"><i class="fas fa-trophy"></i>Leaderboard</a>

        <div class="admin-menu-section">Users</div>
        <a href="members.php" class="<?php echo adminActive('members.php'); ?>"><i class="fas fa-users"></i>Members</a>

        <?php if ($isSystemAdmin): ?>
            <div class="admin-menu-section">System</div>
            <a href="settings.php" class="<?php echo adminActive('settings.php'); ?>"><i class="fas fa-cog"></i>Settings</a>
            <a href="database_backup.php" class="<?php echo adminActive('database_backup.php'); ?>"><i class="fas fa-database"></i>Database Backup</a>
            <a href="admin_users.php" class="<?php echo adminActive('admin_users.php'); ?>"><i class="fas fa-user-shield"></i>Admin Users</a>
            <a href="audit_logs.php" class="<?php echo adminActive('audit_logs.php'); ?>"><i class="fas fa-history"></i>Audit Logs</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
</aside>
