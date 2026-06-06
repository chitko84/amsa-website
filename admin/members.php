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

$memberSearch = trim($_GET['search'] ?? '');
$roleFilter = normalizeRole($_GET['role'] ?? 'all');
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 25, 50], true)) {
    $perPage = 10;
}
$allowedRoleFilters = array_merge(['all'], $roleOptions);
$roleFilter = in_array($roleFilter, $allowedRoleFilters, true) ? $roleFilter : 'all';
$statusFilter = in_array($statusFilter, ['all', 'active', 'inactive'], true) ? $statusFilter : 'all';
$sortMap = [
    'newest' => 'u.created_at DESC, u.id DESC',
    'oldest' => 'u.created_at ASC, u.id ASC',
    'name_asc' => 'u.name ASC, u.id ASC',
    'name_desc' => 'u.name DESC, u.id DESC',
    'role' => "FIELD(u.role, 'member', 'president', 'vice_president', 'secretary', 'male_treasurer', 'female_treasurer', 'system_admin', 'admin'), u.name ASC",
    'status' => 'u.status ASC, u.name ASC',
];
$sortRaw = $_GET['sort'] ?? 'newest';
$sortOption = array_key_exists($sortRaw, $sortMap) ? $sortRaw : 'newest';
$orderBy = $sortMap[$sortOption] ?? $sortMap['newest'];
$where = [];
$types = '';
$params = [];
if ($memberSearch !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $types .= 'ss';
    $searchLike = '%' . $memberSearch . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
}
if ($roleFilter !== 'all') {
    $where[] = 'u.role = ?';
    $types .= 's';
    $params[] = $roleFilter;
}
if ($statusFilter !== 'all') {
    $where[] = 'u.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM user u $whereSql");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalMembers = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalMembers / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.profile_image, COALESCE(up.total_points, 0) AS total_points
    FROM user u
    LEFT JOIN user_points up ON up.user_id = u.id
    $whereSql
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
");
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$showingStart = $totalMembers > 0 ? $offset + 1 : 0;
$showingEnd = $totalMembers > 0 ? min($offset + count($members), $totalMembers) : 0;

function membersPageUrl(array $overrides = []) {
    global $memberSearch, $roleFilter, $statusFilter, $sortOption, $perPage, $page;

    $params = array_merge([
        'search' => $memberSearch,
        'role' => $roleFilter,
        'status' => $statusFilter,
        'sort' => $sortOption,
        'per_page' => $perPage,
        'page' => $page,
    ], $overrides);
    return 'members.php?' . http_build_query($params);
}

$pageTitle = 'Members';
include 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card amsa-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">User Management</h4>
        <span class="text-muted">Showing <?php echo (int) $showingStart; ?>&ndash;<?php echo (int) $showingEnd; ?> of <?php echo (int) $totalMembers; ?> users</span>
    </div>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3"><label class="form-label">Search</label><input type="search" name="search" class="form-control amsa-form-control" value="<?php echo htmlspecialchars($memberSearch); ?>" placeholder="Name or email"></div>
        <div class="col-md-2"><label class="form-label">Role</label><select name="role" class="form-select amsa-form-control"><option value="all">All</option><?php foreach ($roleOptions as $roleOption): ?><option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $roleFilter === $roleOption ? 'selected' : ''; ?>><?php echo htmlspecialchars(roleLabel($roleOption)); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select amsa-form-control"><option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option><option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
        <div class="col-md-2"><label class="form-label">Sort</label><select name="sort" class="form-select amsa-form-control"><option value="newest" <?php echo $sortOption === 'newest' ? 'selected' : ''; ?>>Newest First</option><option value="oldest" <?php echo $sortOption === 'oldest' ? 'selected' : ''; ?>>Oldest First</option><option value="name_asc" <?php echo $sortOption === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option><option value="name_desc" <?php echo $sortOption === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option><option value="role" <?php echo $sortOption === 'role' ? 'selected' : ''; ?>>Role</option><option value="status" <?php echo $sortOption === 'status' ? 'selected' : ''; ?>>Status</option></select></div>
        <div class="col-md-1"><label class="form-label">Rows</label><select name="per_page" class="form-select amsa-form-control"><?php foreach ([10,25,50] as $option): ?><option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><input type="hidden" name="page" value="1"><button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Apply</button><a href="members.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Reset</a></div>
    </form>
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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <span class="text-muted">Page <?php echo (int) $page; ?> of <?php echo (int) $totalPages; ?></span>
        <div class="btn-group">
            <a class="btn btn-outline-primary amsa-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(membersPageUrl(['page' => max(1, $page - 1)])); ?>">Previous</a>
            <a class="btn btn-outline-primary amsa-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(membersPageUrl(['page' => min($totalPages, $page + 1)])); ?>">Next</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
