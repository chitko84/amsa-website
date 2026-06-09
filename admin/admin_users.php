<?php
require_once '../config/database.php';
requireSystemAdmin('login.php');

$success = $error = '';
$adminRoleOptions = adminRoleValues();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int) ($_POST['user_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id, name, email, role, status FROM user WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();

        if (!$target) {
            $error = 'User was not found.';
        } elseif ($action === 'promote' || $action === 'change_role') {
            $newRole = normalizeRole($_POST['role'] ?? '');
            $oldRole = normalizeRole($target['role']);

            if (!in_array($newRole, $adminRoleOptions, true)) {
                $error = 'Invalid admin role selected.';
            } elseif ($action === 'promote' && $oldRole !== 'member') {
                $error = 'Only member users can be promoted from this form.';
            } elseif ((int) $userId === (int) currentUserId() && isSystemAdminRole($oldRole) && !isSystemAdminRole($newRole) && activeSystemAdminCount($userId) < 1) {
                $error = 'You cannot lower your own role because you are the only active system administrator.';
            } elseif (isSystemAdminRole($oldRole) && !isSystemAdminRole($newRole) && activeSystemAdminCount($userId) < 1) {
                $error = 'The last active system administrator cannot be changed to another role.';
            } else {
                $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $newRole, $userId);
                $success = $stmt->execute() ? ($action === 'promote' ? 'Member promoted to admin role.' : 'Admin role updated.') : '';
                $error = $success ? '' : 'Failed to update admin role.';
                if ($success) {
                    logAuditAction(
                        $action === 'promote' ? 'admin_promotion' : 'role_change',
                        'user',
                        $userId,
                        ['role' => $oldRole],
                        ['role' => $newRole]
                    );
                }
            }
        } elseif ($action === 'status') {
            $newStatus = $_POST['status'] ?? '';
            $oldRole = normalizeRole($target['role']);

            if (!in_array($newStatus, ['active', 'inactive'], true)) {
                $error = 'Invalid status selected.';
            } elseif (!isAdminRole($oldRole)) {
                $error = 'Only admin users can be managed on this page.';
            } elseif (isSystemAdminRole($oldRole) && $newStatus !== 'active' && activeSystemAdminCount($userId) < 1) {
                $error = 'The last active system administrator cannot be deactivated.';
            } else {
                $stmt = $conn->prepare("UPDATE user SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $newStatus, $userId);
                $success = $stmt->execute() ? 'Admin status updated.' : '';
                $error = $success ? '' : 'Failed to update admin status.';
                if ($success) {
                    logAuditAction(
                        $newStatus === 'active' ? 'admin_reactivation' : 'admin_deactivation',
                        'user',
                        $userId,
                        ['status' => $target['status']],
                        ['status' => $newStatus]
                    );
                }
            }
        } else {
            $error = 'Invalid admin user request.';
        }
    }
}

$adminsResult = $conn->query("
    SELECT id, name, email, role, status, created_at
    FROM user
    WHERE role IN ('president', 'vice_president', 'secretary', 'male_treasurer', 'female_treasurer', 'system_admin', 'admin')
    ORDER BY FIELD(role, 'president', 'vice_president', 'secretary', 'male_treasurer', 'female_treasurer', 'system_admin', 'admin'), name ASC
");
$adminUsers = $adminsResult ? $adminsResult->fetch_all(MYSQLI_ASSOC) : [];

$membersResult = $conn->query("
    SELECT id, name, email
    FROM user
    WHERE role = 'member'
    ORDER BY name ASC, email ASC
");
$memberUsers = $membersResult ? $membersResult->fetch_all(MYSQLI_ASSOC) : [];

$pageTitle = 'Admin Users';
include 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-12">
        <div class="admin-card amsa-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Promote Member</h4>
                <span class="text-muted"><?php echo count($memberUsers); ?> members available</span>
            </div>
            <form method="POST" class="row g-3 align-items-end">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="promote">
                <div class="col-lg-6">
                    <label class="form-label">Member</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select member...</option>
                        <?php foreach ($memberUsers as $member): ?>
                            <option value="<?php echo (int) $member['id']; ?>">
                                <?php echo htmlspecialchars($member['name'] . ' - ' . $member['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Admin Role</label>
                    <select name="role" class="form-select" required>
                        <?php foreach ($adminRoleOptions as $role): ?>
                            <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(roleLabel($role)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary w-100">Promote</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="admin-card amsa-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Admin Users</h4>
                <span class="text-muted"><?php echo count($adminUsers); ?> admin users</span>
            </div>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle amsa-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adminUsers as $admin): ?>
                            <?php $adminRole = normalizeRole($admin['role']); ?>
                            <tr>
                                <td><?php echo (int) $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><span class="badge bg-primary amsa-badge"><?php echo htmlspecialchars(roleLabel($adminRole)); ?></span></td>
                                <td><span class="badge amsa-badge <?php echo $admin['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars(ucfirst($admin['status'])); ?></span></td>
                                <td><?php echo $admin['created_at'] ? date('M d, Y', strtotime($admin['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <form method="POST" class="d-inline-flex gap-1">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $admin['id']; ?>">
                                        <select name="role" class="form-select form-select-sm" aria-label="Admin role">
                                            <?php foreach ($adminRoleOptions as $role): ?>
                                                <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $adminRole === $role ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(roleLabel($role)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary amsa-btn amsa-btn-sm">Update</button>
                                    </form>
                                    <form method="POST" class="d-inline" id="adminStatusForm<?php echo (int) $admin['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $admin['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $admin['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="<?php echo $admin['status'] === 'active' ? 'button' : 'submit'; ?>" class="btn btn-sm amsa-btn amsa-btn-sm <?php echo $admin['status'] === 'active' ? 'btn-outline-danger amsa-btn-danger' : 'btn-primary amsa-btn-primary'; ?>" <?php echo $admin['status'] === 'active' ? 'data-bs-toggle="modal" data-bs-target="#adminDeactivateModal' . (int) $admin['id'] . '"' : ''; ?>>
                                            <?php echo $admin['status'] === 'active' ? 'Deactivate' : 'Reactivate'; ?>
                                        </button>
                                    </form>
                                    <?php if ($admin['status'] === 'active'): ?>
                                        <div class="modal fade" id="adminDeactivateModal<?php echo (int) $admin['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content amsa-modal">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Deletion</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to deactivate this admin user?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" form="adminStatusForm<?php echo (int) $admin['id']; ?>" class="btn btn-danger amsa-btn amsa-btn-danger">Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
