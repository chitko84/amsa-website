<?php
require_once '../config/database.php';
requireAdmin('login.php');

$success = $error = '';
$roleOptions = array_merge(['member'], adminRoleValues());
$isSystemAdmin = canManageAdminRoles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'status';
        $memberId = (int) ($_POST['member_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id, role, status FROM user WHERE id = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();

        if (!$target) {
            $error = 'User was not found.';
        } elseif ($action === 'status') {
            $status = $_POST['status'] ?? '';
            $targetRole = normalizeRole($target['role']);
            if (!in_array($status, ['active', 'inactive'], true)) {
                $error = 'Invalid member status request.';
            } elseif (!$isSystemAdmin && $targetRole !== 'member') {
                $error = 'Only system administrators can change admin user status.';
            } elseif (isSystemAdminRole($targetRole) && $status !== 'active' && activeSystemAdminCount($memberId) < 1) {
                $error = 'The last active system administrator cannot be deactivated.';
            } else {
                $stmt = $conn->prepare("UPDATE user SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $memberId);
                $success = $stmt->execute() ? 'User status updated.' : '';
                $error = $success ? '' : 'Failed to update user status.';
                if ($success) {
                    $statusAction = 'member_status_update';
                    if (isAdminRole($targetRole)) {
                        $statusAction = $status === 'active' ? 'admin_reactivation' : 'admin_deactivation';
                    }
                    logAuditAction(
                        $statusAction,
                        'user',
                        $memberId,
                        ['status' => $target['status']],
                        ['status' => $status]
                    );
                }
            }
        } elseif ($action === 'role' && $isSystemAdmin) {
            $newRole = normalizeRole($_POST['role'] ?? '');
            $oldRole = normalizeRole($target['role']);
            if (!in_array($newRole, $roleOptions, true)) {
                $error = 'Invalid role selected.';
            } elseif ((int) $memberId === (int) currentUserId() && isSystemAdminRole($oldRole) && !isSystemAdminRole($newRole) && activeSystemAdminCount($memberId) < 1) {
                $error = 'You cannot lower your own role because you are the only active system administrator.';
            } elseif (isSystemAdminRole($oldRole) && !isSystemAdminRole($newRole) && activeSystemAdminCount($memberId) < 1) {
                $error = 'The last active system administrator cannot be changed to another role.';
            } else {
                $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $newRole, $memberId);
                $success = $stmt->execute() ? 'User role updated.' : '';
                $error = $success ? '' : 'Failed to update user role.';
                if ($success) {
                    logAuditAction(
                        $oldRole === 'member' && isAdminRole($newRole) ? 'admin_promotion' : 'role_change',
                        'user',
                        $memberId,
                        ['role' => $oldRole],
                        ['role' => $newRole]
                    );
                }
            }
        } else {
            $error = 'Invalid member management request.';
        }
    }
}

ensureProfileImageColumn();

$result = $conn->query("
    SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.profile_image, COALESCE(up.total_points, 0) AS total_points
    FROM user u
    LEFT JOIN user_points up ON up.user_id = u.id
    ORDER BY FIELD(u.role, 'member', 'president', 'vice_president', 'secretary', 'male_treasurer', 'female_treasurer', 'system_admin', 'admin'), u.created_at DESC, u.name ASC
");
$members = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$pageTitle = 'Members';
include 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card amsa-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">User Management</h4>
        <span class="text-muted"><?php echo count($members); ?> users</span>
    </div>
    <?php if (empty($members)): ?>
        <div class="amsa-empty-state">
            <i class="fas fa-users fa-2x mb-3 text-primary"></i>
            <h4>No Users Yet</h4>
            <p class="mb-0">Registered AMSA users will appear here.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive amsa-table-wrap">
        <table class="table align-middle amsa-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Photo</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th>Total Points</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                        <td><img src="<?php echo htmlspecialchars(profileImageUrl($member['profile_image'] ?? null, '../')); ?>" class="profile-avatar-sm" alt="Member profile image"></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><span class="badge bg-primary amsa-badge"><?php echo htmlspecialchars(roleLabel($member['role'])); ?></span></td>
                        <td><span class="badge amsa-badge <?php echo $member['status'] === 'active' ? 'amsa-badge-active bg-success' : 'amsa-badge-inactive bg-secondary'; ?>"><?php echo htmlspecialchars(ucfirst($member['status'])); ?></span></td>
                        <td><?php echo $member['created_at'] ? date('M d, Y', strtotime($member['created_at'])) : 'N/A'; ?></td>
                        <td><strong><?php echo (int) $member['total_points']; ?></strong></td>
                        <td>
                            <?php $memberRole = normalizeRole($member['role']); ?>
                            <?php if ($isSystemAdmin || $memberRole === 'member'): ?>
                            <form method="POST" class="d-inline" id="memberStatusForm<?php echo (int) $member['id']; ?>">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $member['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                <button type="<?php echo $member['status'] === 'active' ? 'button' : 'submit'; ?>" class="btn btn-sm amsa-btn amsa-btn-sm <?php echo $member['status'] === 'active' ? 'btn-outline-danger amsa-btn-danger' : 'btn-primary amsa-btn-primary'; ?>" <?php echo $member['status'] === 'active' ? 'data-bs-toggle="modal" data-bs-target="#memberDeactivateModal' . (int) $member['id'] . '"' : ''; ?>>
                                    <?php echo $member['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <?php if ($member['status'] === 'active'): ?>
                                <div class="modal fade" id="memberDeactivateModal<?php echo (int) $member['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content amsa-modal">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to deactivate this user?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" form="memberStatusForm<?php echo (int) $member['id']; ?>" class="btn btn-danger amsa-btn amsa-btn-danger">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($isSystemAdmin): ?>
                                <form method="POST" class="d-inline-flex gap-1 mt-1">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                    <select name="role" class="form-select form-select-sm" aria-label="Role">
                                        <?php foreach ($roleOptions as $roleOption): ?>
                                            <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $memberRole === $roleOption ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(roleLabel($roleOption)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary amsa-btn amsa-btn-sm">Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
