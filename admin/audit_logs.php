<?php
require_once '../config/database.php';
requireSystemAdmin('login.php');

$actionTypes = [
    'all' => 'All',
    'login' => 'Login',
    'logout' => 'Logout',
    'create' => 'Create',
    'update' => 'Update',
    'delete' => 'Delete',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'role_change' => 'Role Change',
    'backup_export' => 'Backup Export',
    'member_status_change' => 'Member Status Change',
    'category_change' => 'Category Change',
    'content_change' => 'Content Change',
];
$entityTypes = [
    'all' => 'All',
    'user' => 'User',
    'post' => 'Post',
    'point_request' => 'Point Request',
    'point_category' => 'Point Category',
    'contact_message' => 'Contact Message',
    'database_backup' => 'Database Backup',
    'settings' => 'Settings',
    'admin_user' => 'Admin User',
];
$sortOptions = [
    'newest' => 'al.created_at DESC, al.id DESC',
    'oldest' => 'al.created_at ASC, al.id ASC',
    'action_asc' => 'al.action ASC, al.created_at DESC',
    'entity_asc' => 'al.entity_type ASC, al.created_at DESC',
];

$search = trim($_GET['search'] ?? '');
$actionTypeRaw = $_GET['action_type'] ?? 'all';
$actionType = array_key_exists($actionTypeRaw, $actionTypes) ? $actionTypeRaw : 'all';
$entityTypeRaw = $_GET['entity_type'] ?? 'all';
$entityType = array_key_exists($entityTypeRaw, $entityTypes) ? $entityTypeRaw : 'all';
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$sortRaw = $_GET['sort'] ?? 'newest';
$sort = array_key_exists($sortRaw, $sortOptions) ? $sortRaw : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 25);
if (!in_array($perPage, [10, 25, 50], true)) {
    $perPage = 25;
}

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR al.action LIKE ? OR al.entity_type LIKE ? OR CAST(al.entity_id AS CHAR) LIKE ? OR al.ip_address LIKE ?)";
    $types .= 'ssssss';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($actionType !== 'all') {
    $actionPatterns = [
        'login' => 'login',
        'logout' => 'logout',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'approve' => 'approve',
        'reject' => 'reject',
        'role_change' => 'role_change',
        'backup_export' => 'backup_export',
        'member_status_change' => 'member_status',
        'category_change' => 'category',
        'content_change' => 'content',
    ];
    $where[] = "al.action LIKE ?";
    $types .= 's';
    $params[] = '%' . ($actionPatterns[$actionType] ?? $actionType) . '%';
}
if ($entityType !== 'all') {
    $where[] = "al.entity_type = ?";
    $types .= 's';
    $params[] = $entityType;
}
if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $where[] = "al.created_at >= ?";
    $types .= 's';
    $params[] = $fromDate . ' 00:00:00';
}
if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $where[] = "al.created_at <= ?";
    $types .= 's';
    $params[] = $toDate . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM audit_logs al
    LEFT JOIN user u ON u.id = al.user_id
    $whereSql
");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalLogs = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalLogs / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare("
    SELECT al.*, u.name AS user_name, u.email AS user_email
    FROM audit_logs al
    LEFT JOIN user u ON u.id = al.user_id
    $whereSql
    ORDER BY {$sortOptions[$sort]}
    LIMIT ? OFFSET ?
");
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$showingStart = $totalLogs > 0 ? $offset + 1 : 0;
$showingEnd = $totalLogs > 0 ? min($offset + count($logs), $totalLogs) : 0;

function auditLogsUrl(array $overrides = []) {
    global $search, $actionType, $entityType, $fromDate, $toDate, $sort, $perPage, $page;

    $params = array_merge([
        'search' => $search,
        'action_type' => $actionType,
        'entity_type' => $entityType,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'sort' => $sort,
        'per_page' => $perPage,
        'page' => $page,
    ], $overrides);
    return 'audit_logs.php?' . http_build_query($params);
}

function auditPreview($value, $limit = 80) {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    $decoded = json_decode($value, true);
    $text = json_last_error() === JSON_ERROR_NONE ? json_encode($decoded, JSON_UNESCAPED_SLASHES) : $value;
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

function auditPrettyJson($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    return $value;
}

$pageTitle = 'Audit Logs';
include 'includes/header.php';
?>

<div class="admin-card amsa-card p-4">
    <div class="admin-section-header">
        <div>
            <h3>Audit Logs</h3>
            <p>Inspect sensitive system actions recorded by AMSA admin tools.</p>
        </div>
        <span class="amsa-badge amsa-badge-info"><?php echo (int) $totalLogs; ?> logs</span>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="search" name="search" class="form-control amsa-form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="User, action, entity, ID, or IP">
        </div>
        <div class="col-md-2">
            <label class="form-label">Action Type</label>
            <select name="action_type" class="form-select amsa-form-control">
                <?php foreach ($actionTypes as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $actionType === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Entity Type</label>
            <select name="entity_type" class="form-select amsa-form-control">
                <?php foreach ($entityTypes as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $entityType === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" name="from_date" class="form-control amsa-form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" name="to_date" class="form-control amsa-form-control" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sort</label>
            <select name="sort" class="form-select amsa-form-control">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                <option value="action_asc" <?php echo $sort === 'action_asc' ? 'selected' : ''; ?>>Action A-Z</option>
                <option value="entity_asc" <?php echo $sort === 'entity_asc' ? 'selected' : ''; ?>>Entity Type A-Z</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rows</label>
            <select name="per_page" class="form-select amsa-form-control">
                <?php foreach ([10, 25, 50] as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <input type="hidden" name="page" value="1">
            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Apply</button>
            <a href="audit_logs.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Reset</a>
        </div>
    </form>

    <?php if (empty($logs)): ?>
        <div class="amsa-empty-state">
            <i class="fas fa-history fa-2x mb-3 text-primary"></i>
            <h4>No Audit Logs Found</h4>
            <p class="mb-0">Try another search or filter combination.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive amsa-table-wrap">
            <table class="table align-middle amsa-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Admin/User</th>
                        <th>Action</th>
                        <th>Entity Type</th>
                        <th>Entity ID</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                        <th>Old Values</th>
                        <th>New Values</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $logId = (int) $log['id'];
                            $userLabel = trim(($log['user_name'] ?? '') . ' ' . (($log['user_email'] ?? '') ? '(' . $log['user_email'] . ')' : ''));
                            $userLabel = $userLabel !== '' ? $userLabel : 'System / Unknown';
                            $shortAgent = strlen((string) $log['user_agent']) > 60 ? substr((string) $log['user_agent'], 0, 60) . '...' : (string) $log['user_agent'];
                        ?>
                        <tr>
                            <td><?php echo $log['created_at'] ? date('M d, Y h:i A', strtotime($log['created_at'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($userLabel); ?></td>
                            <td><span class="amsa-badge amsa-badge-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($log['entity_id'] ?? '-')); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($shortAgent ?: '-'); ?></td>
                            <td><code><?php echo htmlspecialchars(auditPreview($log['old_values'])); ?></code></td>
                            <td><code><?php echo htmlspecialchars(auditPreview($log['new_values'])); ?></code></td>
                            <td><button type="button" class="btn btn-sm btn-primary amsa-btn amsa-btn-primary amsa-btn-sm" data-bs-toggle="modal" data-bs-target="#auditLogModal<?php echo $logId; ?>">View Details</button></td>
                        </tr>

                        <div class="modal fade" id="auditLogModal<?php echo $logId; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content amsa-modal">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Audit Log #<?php echo $logId; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6"><strong>Date/Time</strong><br><?php echo htmlspecialchars($log['created_at'] ? date('M d, Y h:i A', strtotime($log['created_at'])) : 'N/A'); ?></div>
                                            <div class="col-md-6"><strong>Admin/User</strong><br><?php echo htmlspecialchars($userLabel); ?></div>
                                            <div class="col-md-4"><strong>Action</strong><br><?php echo htmlspecialchars($log['action']); ?></div>
                                            <div class="col-md-4"><strong>Entity Type</strong><br><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?></div>
                                            <div class="col-md-4"><strong>Entity ID</strong><br><?php echo htmlspecialchars((string) ($log['entity_id'] ?? '-')); ?></div>
                                            <div class="col-md-6"><strong>IP Address</strong><br><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></div>
                                            <div class="col-md-6"><strong>User Agent</strong><br><?php echo htmlspecialchars($log['user_agent'] ?? '-'); ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Old Values</strong>
                                            <pre class="p-3 rounded bg-light border mt-2 mb-0"><?php echo htmlspecialchars(auditPrettyJson($log['old_values'])); ?></pre>
                                        </div>
                                        <div>
                                            <strong>New Values</strong>
                                            <pre class="p-3 rounded bg-light border mt-2 mb-0"><?php echo htmlspecialchars(auditPrettyJson($log['new_values'])); ?></pre>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <span class="text-muted">Showing <?php echo (int) $showingStart; ?>&ndash;<?php echo (int) $showingEnd; ?> of <?php echo (int) $totalLogs; ?> logs</span>
            <div class="btn-group">
                <a class="btn btn-outline-primary amsa-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(auditLogsUrl(['page' => max(1, $page - 1)])); ?>">Previous</a>
                <a class="btn btn-outline-primary amsa-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(auditLogsUrl(['page' => min($totalPages, $page + 1)])); ?>">Next</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
