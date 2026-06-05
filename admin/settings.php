<?php
require_once '../config/database.php';
requireSystemAdmin('login.php');
logAuditAction('settings_access', 'settings');

$adminName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin';
$adminEmail = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? 'Not available';
$adminRole = currentUserRole();
$phpVersion = PHP_VERSION;
$dbVersion = $conn->server_info ?? 'Not available';
$serverName = $_SERVER['SERVER_NAME'] ?? 'Local server';

$pageTitle = 'Settings';
include 'includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <h4 class="mb-3">System Information</h4>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle mb-0 amsa-table">
                    <tbody>
                        <tr><th>Application</th><td>AMSA AIU Website Management</td></tr>
                        <tr><th>Server</th><td><?php echo htmlspecialchars($serverName); ?></td></tr>
                        <tr><th>PHP Version</th><td><?php echo htmlspecialchars($phpVersion); ?></td></tr>
                        <tr><th>Database</th><td>amsa_web</td></tr>
                        <tr><th>Database Server</th><td><?php echo htmlspecialchars($dbVersion); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <h4 class="mb-3">AMSA Contact Email</h4>
            <p class="text-muted mb-2">Use this official address consistently across public website contact areas and member communication.</p>
            <div class="p-3 rounded bg-light border">
                <strong>amsa@student.aiu.edu.my</strong>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <h4 class="mb-3">Website Branding Info</h4>
            <ul class="mb-0 ps-3">
                <li>Primary identity: AMSA AIU</li>
                <li>Logo source: <code>img/logo.png</code></li>
                <li>Theme direction: maroon, reddish brown, and gold accents</li>
                <li>Public pages, admin panel, and points system should keep consistent branding.</li>
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <h4 class="mb-3">Admin Account Info</h4>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle mb-0 amsa-table">
                    <tbody>
                        <tr><th>Name</th><td><?php echo htmlspecialchars($adminName); ?></td></tr>
                        <tr><th>Email</th><td><?php echo htmlspecialchars($adminEmail); ?></td></tr>
                        <tr><th>Role</th><td><span class="badge bg-primary amsa-badge"><?php echo htmlspecialchars(roleLabel($adminRole)); ?></span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-card amsa-card p-4">
            <h4 class="mb-3">Quick Maintenance Notes</h4>
            <ul class="mb-0 ps-3">
                <li>Review public news, events, achievements, and testimonials regularly from the dashboard.</li>
                <li>Keep points requests pending only while evidence is being reviewed.</li>
                <li>Deactivate members who should no longer access the points system; do not delete member accounts.</li>
                <li>Disable outdated point categories instead of deleting them.</li>
                <li>Import the latest <code>amsa_web.sql</code> only when database structure changes are required.</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
