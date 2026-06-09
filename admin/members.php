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

        // Handle permanent deletion
        if ($action === 'delete') {
            $stmt = $conn->prepare("SELECT id, name, email, role, status FROM user WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();

            if (!$target) {
                $error = 'User was not found.';
            } else {
                $targetRole = normalizeRole($target['role']);
                
                // Prevent deletion of the last system admin
                if (isSystemAdminRole($targetRole) && activeSystemAdminCount($memberId) < 1) {
                    $error = 'The last active system administrator cannot be deleted.';
                } 
                // Prevent deletion of own account
                elseif ((int) $memberId === (int) currentUserId()) {
                    $error = 'You cannot delete your own account.';
                }
                else {
                    // Log the deletion before removing
                    logAuditAction(
                        'member_permanent_delete',
                        'user',
                        $memberId,
                        ['name' => $target['name'], 'email' => $target['email'], 'role' => $target['role']],
                        null
                    );
                    
                    // Delete related records first (optional - depends on your schema)
                    $conn->begin_transaction();
                    try {
                        // Delete user points
                        $stmt = $conn->prepare("DELETE FROM user_points WHERE user_id = ?");
                        $stmt->bind_param("i", $memberId);
                        $stmt->execute();
                        
                        // Delete audit logs for this user (optional)
                        $stmt = $conn->prepare("DELETE FROM audit_logs WHERE user_id = ?");
                        $stmt->bind_param("i", $memberId);
                        $stmt->execute();
                        
                        // Finally delete the user
                        $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
                        $stmt->bind_param("i", $memberId);
                        $stmt->execute();
                        
                        $conn->commit();
                        $success = 'Member permanently deleted successfully.';
                        
                        // Redirect to clear POST data
                        header("Location: members.php?success=" . urlencode($success));
                        exit();
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Failed to delete member: ' . $e->getMessage();
                    }
                }
            }
        }
        // Handle status change
        elseif ($action === 'status') {
            $stmt = $conn->prepare("SELECT id, role, status FROM user WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();

            if (!$target) {
                $error = 'User was not found.';
            } else {
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
            }
        } 
        // Handle role change
        elseif ($action === 'role' && $isSystemAdmin) {
            $stmt = $conn->prepare("SELECT id, role, status FROM user WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            
            if (!$target) {
                $error = 'User was not found.';
            } else {
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
            }
        } else {
            $error = 'Invalid member management request.';
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
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

<style>
    :root {
        --amsa-wine: #5f2626;
        --amsa-wine-dark: #3b1118;
        --amsa-gold: #f4b942;
        --amsa-cream: #fff8ef;
        --amsa-soft: #f7f1ea;
    }

    .amsa-card-modern {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(95, 38, 38, 0.1);
        transition: all 0.3s ease;
    }

    .amsa-table-wrap {
        overflow-x: auto;
    }

    .amsa-table {
        min-width: 800px;
    }

    .amsa-table thead th {
        background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark));
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 14px 12px;
    }

    .amsa-table tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f0e5dc;
    }

    .amsa-table tbody tr:hover {
        background: var(--amsa-cream);
    }

    .profile-avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--amsa-gold);
    }

    .amsa-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.75rem;
        letter-spacing: 0.3px;
    }

    .amsa-badge-active {
        background: #28a745;
        color: white;
    }

    .amsa-badge-inactive {
        background: #6c757d;
        color: white;
    }

    .amsa-btn-sm {
        padding: 5px 12px;
        font-size: 0.8rem;
        border-radius: 8px;
        margin: 0 2px;
    }

    .amsa-btn-primary {
        background: var(--amsa-wine);
        border-color: var(--amsa-wine);
        color: white;
    }

    .amsa-btn-primary:hover {
        background: var(--amsa-wine-dark);
        border-color: var(--amsa-wine-dark);
    }

    .amsa-btn-danger {
        background: #dc3545;
        border-color: #dc3545;
    }

    .amsa-btn-danger:hover {
        background: #c82333;
    }

    .amsa-btn-secondary {
        background: #6c757d;
        border-color: #6c757d;
    }

    .amsa-modal .modal-content {
        border: none;
        border-radius: 20px;
        overflow: hidden;
    }

    .amsa-modal .modal-header {
        background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark));
        color: white;
        border-bottom: 3px solid var(--amsa-gold);
    }

    .amsa-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .amsa-alert {
        border-radius: 12px;
        border: none;
        padding: 14px 20px;
        margin-bottom: 20px;
    }

    .amsa-alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .amsa-alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .amsa-empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--amsa-cream);
        border-radius: 16px;
    }

    .amsa-filter-form {
        background: linear-gradient(135deg, #ffffff, var(--amsa-cream));
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 768px) {
        .amsa-card-modern {
            padding: 15px !important;
        }
        
        .amsa-table thead th,
        .amsa-table tbody td {
            padding: 8px 6px;
            font-size: 0.8rem;
        }
        
        .profile-avatar-sm {
            width: 32px;
            height: 32px;
        }
    }
</style>

<div class="container-fluid px-0">
    <?php if ($success): ?>
        <div class="alert amsa-alert amsa-alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert amsa-alert amsa-alert-error alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="amsa-card-modern p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1" style="color: var(--amsa-wine-dark);">
                    <i class="fas fa-users me-2" style="color: var(--amsa-gold);"></i>
                    User Management
                </h4>
                <p class="text-muted mb-0">Manage members, roles, and account status</p>
            </div>
            <span class="badge amsa-badge" style="background: var(--amsa-cream); color: var(--amsa-wine);">
                <i class="fas fa-chart-line me-1"></i> <?php echo (int) $totalMembers; ?> Total Users
            </span>
        </div>

        <!-- Filter Form -->
        <div class="amsa-filter-form">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="search" name="search" class="form-control" value="<?php echo htmlspecialchars($memberSearch); ?>" placeholder="Name or email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role" class="form-select">
                        <option value="all">All Roles</option>
                        <?php foreach ($roleOptions as $roleOption): ?>
                            <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $roleFilter === $roleOption ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(roleLabel($roleOption)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="newest" <?php echo $sortOption === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sortOption === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="name_asc" <?php echo $sortOption === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="name_desc" <?php echo $sortOption === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                        <option value="role" <?php echo $sortOption === 'role' ? 'selected' : ''; ?>>Role</option>
                        <option value="status" <?php echo $sortOption === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold">Rows</label>
                    <select name="per_page" class="form-select">
                        <?php foreach ([10, 25, 50] as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <input type="hidden" name="page" value="1">
                    <button type="submit" class="btn amsa-btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <a href="members.php" class="btn btn-secondary w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Members Table -->
        <?php if (empty($members)): ?>
            <div class="amsa-empty-state">
                <i class="fas fa-users fa-3x mb-3" style="color: var(--amsa-gold);"></i>
                <h4 style="color: var(--amsa-wine-dark);">No Users Found</h4>
                <p class="mb-0">Try adjusting your search or filter criteria</p>
            </div>
        <?php else: ?>
            <div class="amsa-table-wrap">
                <table class="table amsa-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Photo</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): 
                            $memberRole = normalizeRole($member['role']);
                            $currentUserId = currentUserId();
                            $canDelete = ($isSystemAdmin && $memberRole !== 'system_admin') || 
                                        (!$isSystemAdmin && $memberRole === 'member');
                            $canDelete = $canDelete && ((int)$member['id'] !== (int)$currentUserId);
                        ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($member['name']); ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars(profileImageUrl($member['profile_image'] ?? null, '../')); ?>" 
                                         class="profile-avatar-sm" alt="Profile">
                                </td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td>
                                    <span class="badge amsa-badge" style="background: <?php echo $memberRole === 'system_admin' ? '#5f2626' : ($memberRole === 'member' ? '#6c757d' : '#f4b942'); ?>; color: <?php echo $memberRole === 'member' ? 'white' : 'white'; ?>;">
                                        <?php echo htmlspecialchars(roleLabel($member['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge amsa-badge <?php echo $member['status'] === 'active' ? 'amsa-badge-active' : 'amsa-badge-inactive'; ?>">
                                        <i class="fas <?php echo $member['status'] === 'active' ? 'fa-circle' : 'fa-circle-slash'; ?> me-1"></i>
                                        <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $member['created_at'] ? date('M d, Y', strtotime($member['created_at'])) : 'N/A'; ?></td>
                                <td><strong class="text-primary"><?php echo (int) $member['total_points']; ?></strong></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <!-- Status Toggle -->
                                        <?php if ($isSystemAdmin || $memberRole === 'member'): ?>
                                            <form method="POST" class="d-inline" id="memberStatusForm<?php echo (int) $member['id']; ?>">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="status">
                                                <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $member['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                
                                                <?php if ($member['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-sm amsa-btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deactivateModal<?php echo (int) $member['id']; ?>">
                                                        <i class="fas fa-ban me-1"></i> Deactivate
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm amsa-btn-sm amsa-btn-primary">
                                                        <i class="fas fa-check-circle me-1"></i> Activate
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Role Change (System Admin only) -->
                                        <?php if ($isSystemAdmin): ?>
                                            <form method="POST" class="d-inline">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="role">
                                                <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                                <div class="d-flex gap-1">
                                                    <select name="role" class="form-select form-select-sm" style="width: auto; min-width: 100px;">
                                                        <?php foreach ($roleOptions as $roleOption): ?>
                                                            <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $memberRole === $roleOption ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars(roleLabel($roleOption)); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm amsa-btn-sm btn-outline-primary">
                                                        <i class="fas fa-sync-alt me-1"></i> Update
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Delete Button -->
                                        <?php if ($canDelete): ?>
                                            <button type="button" class="btn btn-sm amsa-btn-sm btn-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo (int) $member['id']; ?>">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Deactivation Confirmation Modal -->
                            <?php if ($member['status'] === 'active' && ($isSystemAdmin || $memberRole === 'member')): ?>
                                <div class="modal fade amsa-modal" id="deactivateModal<?php echo (int) $member['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-ban me-2"></i> Confirm Deactivation
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to deactivate <strong><?php echo htmlspecialchars($member['name']); ?></strong>?</p>
                                                <p class="text-muted mb-0">Deactivated users will not be able to log in or access the system.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" form="memberStatusForm<?php echo (int) $member['id']; ?>" class="btn btn-warning">
                                                    <i class="fas fa-ban me-1"></i> Confirm Deactivation
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Permanent Delete Confirmation Modal -->
                            <?php if ($canDelete): ?>
                                <div class="modal fade amsa-modal" id="deleteModal<?php echo (int) $member['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                                                <h5 class="modal-title text-white">
                                                    <i class="fas fa-exclamation-triangle me-2"></i> Permanent Deletion Warning
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-danger mb-3">
                                                    <i class="fas fa-skull-crossbones me-2"></i>
                                                    <strong>This action cannot be undone!</strong>
                                                </div>
                                                <p>You are about to permanently delete:</p>
                                                <div class="bg-light p-3 rounded mb-3">
                                                    <strong>Name:</strong> <?php echo htmlspecialchars($member['name']); ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?><br>
                                                    <strong>Role:</strong> <?php echo htmlspecialchars(roleLabel($member['role'])); ?>
                                                </div>
                                                <p class="text-danger mb-0">
                                                    <i class="fas fa-trash-alt me-2"></i>
                                                    This will permanently remove this member and all associated data from the system.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="member_id" value="<?php echo (int) $member['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-trash-alt me-1"></i> Confirm Permanent Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                <span class="text-muted">
                    <i class="fas fa-chart-simple me-1"></i>
                    Showing <?php echo (int) $showingStart; ?> – <?php echo (int) $showingEnd; ?> of <?php echo (int) $totalMembers; ?> users
                </span>
                <div class="btn-group">
                    <a class="btn btn-outline-primary <?php echo $page <= 1 ? 'disabled' : ''; ?>" 
                       href="<?php echo htmlspecialchars(membersPageUrl(['page' => max(1, $page - 1)])); ?>">
                        <i class="fas fa-chevron-left me-1"></i> Previous
                    </a>
                    <span class="btn btn-secondary disabled">
                        Page <?php echo (int) $page; ?> of <?php echo (int) $totalPages; ?>
                    </span>
                    <a class="btn btn-outline-primary <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" 
                       href="<?php echo htmlspecialchars(membersPageUrl(['page' => min($totalPages, $page + 1)])); ?>">
                        Next <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>