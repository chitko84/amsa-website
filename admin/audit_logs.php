<?php
require_once '../config/database.php';
requireSystemAdmin('login.php');

function auditH($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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
    'audit_log' => 'Audit Log',
];

$sortOptions = [
    'newest' => 'al.created_at DESC, al.id DESC',
    'oldest' => 'al.created_at ASC, al.id ASC',
    'action_asc' => 'al.action ASC, al.created_at DESC',
    'entity_asc' => 'al.entity_type ASC, al.created_at DESC',
];

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

function auditSanitizeFilters(array $source, array $actionTypes, array $entityTypes, array $sortOptions) {
    $search = trim($source['search'] ?? '');
    $actionTypeRaw = $source['action_type'] ?? 'all';
    $entityTypeRaw = $source['entity_type'] ?? 'all';
    $fromDate = trim($source['from_date'] ?? '');
    $toDate = trim($source['to_date'] ?? '');
    $sortRaw = $source['sort'] ?? 'newest';
    $perPage = (int) ($source['per_page'] ?? 25);
    $page = max(1, (int) ($source['page'] ?? 1));

    if (!array_key_exists($actionTypeRaw, $actionTypes)) {
        $actionTypeRaw = 'all';
    }

    if (!array_key_exists($entityTypeRaw, $entityTypes)) {
        $entityTypeRaw = 'all';
    }

    if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $fromDate = '';
    }

    if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $toDate = '';
    }

    if (!array_key_exists($sortRaw, $sortOptions)) {
        $sortRaw = 'newest';
    }

    if (!in_array($perPage, [10, 25, 50], true)) {
        $perPage = 25;
    }

    return [
        'search' => $search,
        'action_type' => $actionTypeRaw,
        'entity_type' => $entityTypeRaw,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'sort' => $sortRaw,
        'per_page' => $perPage,
        'page' => $page,
    ];
}

function auditBuildWhere(array $filters, array $actionPatterns) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR al.action LIKE ? OR al.entity_type LIKE ? OR CAST(al.entity_id AS CHAR) LIKE ? OR al.ip_address LIKE ?)";
        $types .= 'ssssss';
        $like = '%' . $filters['search'] . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    if ($filters['action_type'] !== 'all') {
        $where[] = 'al.action LIKE ?';
        $types .= 's';
        $params[] = '%' . ($actionPatterns[$filters['action_type']] ?? $filters['action_type']) . '%';
    }

    if ($filters['entity_type'] !== 'all') {
        $where[] = 'al.entity_type = ?';
        $types .= 's';
        $params[] = $filters['entity_type'];
    }

    if ($filters['from_date'] !== '') {
        $where[] = 'al.created_at >= ?';
        $types .= 's';
        $params[] = $filters['from_date'] . ' 00:00:00';
    }

    if ($filters['to_date'] !== '') {
        $where[] = 'al.created_at <= ?';
        $types .= 's';
        $params[] = $filters['to_date'] . ' 23:59:59';
    }

    return [
        'sql' => $where ? 'WHERE ' . implode(' AND ', $where) : '',
        'types' => $types,
        'params' => $params,
    ];
}

function auditHasActiveFilters(array $filters) {
    return $filters['search'] !== ''
        || $filters['action_type'] !== 'all'
        || $filters['entity_type'] !== 'all'
        || $filters['from_date'] !== ''
        || $filters['to_date'] !== '';
}

function auditFilterSummary(array $filters, array $actionTypes, array $entityTypes) {
    $summary = [];

    if ($filters['search'] !== '') {
        $summary[] = 'Search: "' . $filters['search'] . '"';
    }

    if ($filters['action_type'] !== 'all') {
        $summary[] = 'Action: ' . ($actionTypes[$filters['action_type']] ?? $filters['action_type']);
    }

    if ($filters['entity_type'] !== 'all') {
        $summary[] = 'Entity: ' . ($entityTypes[$filters['entity_type']] ?? $filters['entity_type']);
    }

    if ($filters['from_date'] !== '') {
        $summary[] = 'From: ' . $filters['from_date'];
    }

    if ($filters['to_date'] !== '') {
        $summary[] = 'To: ' . $filters['to_date'];
    }

    return $summary ?: ['No filters active'];
}

function auditBindAndExecute(mysqli_stmt $stmt, string $types, array $params) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    return $stmt->execute();
}

function auditPreparedDeleteByIds(mysqli $conn, array $ids) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

    if (empty($ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM audit_logs WHERE id IN ($placeholders)");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param($types, ...$ids);

    if (!$stmt->execute()) {
        return false;
    }

    return $stmt->affected_rows;
}

function auditPreview($value, $limit = 120) {
    $value = trim((string) $value);

    if ($value === '') {
        return '-';
    }

    $decoded = json_decode($value, true);
    $text = json_last_error() === JSON_ERROR_NONE ? json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value;

    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }

    return $text;
}

function auditPrettyJson($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return '-';
    }

    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return $value;
}

function auditActionClass($action) {
    $action = strtolower((string) $action);

    if (strpos($action, 'login') !== false) return 'action-login';
    if (strpos($action, 'logout') !== false) return 'action-logout';
    if (strpos($action, 'create') !== false) return 'action-create';
    if (strpos($action, 'update') !== false) return 'action-update';
    if (strpos($action, 'delete') !== false) return 'action-delete';
    if (strpos($action, 'approve') !== false) return 'action-approve';
    if (strpos($action, 'reject') !== false) return 'action-reject';
    if (strpos($action, 'role') !== false) return 'action-role';

    return 'action-default';
}

$filters = auditSanitizeFilters($_GET, $actionTypes, $entityTypes, $sortOptions);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'delete_single') {
            $logId = (int) ($_POST['log_id'] ?? 0);

            if ($logId <= 0) {
                $error = 'Invalid audit log selected.';
            } else {
                $deleted = auditPreparedDeleteByIds($conn, [$logId]);

                if ($deleted === false) {
                    $error = 'Audit log deletion failed.';
                } elseif ($deleted < 1) {
                    $error = 'Audit log record was not found or was already deleted.';
                } else {
                    logAuditAction('AUDIT_LOG_DELETE', 'audit_log', $logId, null, ['deleted_count' => $deleted]);
                    $success = 'Audit log deleted successfully.';
                }
            }
        } elseif ($postAction === 'delete_selected') {
            $selectedIds = $_POST['selected_logs'] ?? [];

            if (!is_array($selectedIds) || empty($selectedIds)) {
                $error = 'Please select at least one audit log to delete.';
            } else {
                $deleted = auditPreparedDeleteByIds($conn, $selectedIds);

                if ($deleted === false) {
                    $error = 'Selected audit logs could not be deleted.';
                } elseif ($deleted < 1) {
                    $error = 'No selected audit logs were deleted.';
                } else {
                    logAuditAction('AUDIT_LOG_BULK_DELETE', 'audit_log', null, null, [
                        'deleted_count' => $deleted,
                        'selected_ids' => array_values(array_unique(array_filter(array_map('intval', $selectedIds), fn($id) => $id > 0))),
                    ]);
                    $success = $deleted . ' selected audit log record' . ($deleted === 1 ? '' : 's') . ' deleted successfully.';
                }
            }
        } elseif ($postAction === 'delete_filtered') {
            $deleteFilters = auditSanitizeFilters($_POST, $actionTypes, $entityTypes, $sortOptions);

            if (!auditHasActiveFilters($deleteFilters)) {
                $error = 'Apply at least one filter before deleting filtered results.';
            } else {
                $deleteWhere = auditBuildWhere($deleteFilters, $actionPatterns);
                $stmt = $conn->prepare("
                    DELETE al
                    FROM audit_logs al
                    LEFT JOIN user u ON u.id = al.user_id
                    {$deleteWhere['sql']}
                ");

                if (!$stmt) {
                    $error = 'Filtered audit logs could not be prepared for deletion.';
                } elseif (!auditBindAndExecute($stmt, $deleteWhere['types'], $deleteWhere['params'])) {
                    $error = 'Filtered audit logs could not be deleted.';
                } else {
                    $deleted = $stmt->affected_rows;
                    logAuditAction('AUDIT_LOG_FILTER_DELETE', 'audit_log', null, null, [
                        'deleted_count' => $deleted,
                        'filters' => $deleteFilters,
                    ]);
                    $success = $deleted . ' filtered audit log record' . ($deleted === 1 ? '' : 's') . ' deleted successfully.';
                }
            }
        } elseif ($postAction === 'delete_all') {
            $confirmation = trim($_POST['delete_all_confirmation'] ?? '');

            if ($confirmation !== 'DELETE ALL') {
                $error = 'Type DELETE ALL to confirm full audit log deletion.';
            } else {
                $stmt = $conn->prepare('DELETE FROM audit_logs');

                if (!$stmt || !$stmt->execute()) {
                    $error = 'Audit log history could not be deleted.';
                } else {
                    $deleted = $stmt->affected_rows;
                    logAuditAction('AUDIT_LOG_DELETE_ALL', 'audit_log', null, null, ['deleted_count' => $deleted]);
                    $success = 'Entire audit log history deleted successfully. A new audit record was created for this action.';
                }
            }
        } else {
            $error = 'Invalid audit log management request.';
        }
    }
}

$whereData = auditBuildWhere($filters, $actionPatterns);
$whereSql = $whereData['sql'];
$types = $whereData['types'];
$params = $whereData['params'];

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

$totalAllStmt = $conn->prepare('SELECT COUNT(*) AS total FROM audit_logs');
$totalAllStmt->execute();
$totalAllLogs = (int) ($totalAllStmt->get_result()->fetch_assoc()['total'] ?? 0);

$totalPages = max(1, (int) ceil($totalLogs / $filters['per_page']));

if ($filters['page'] > $totalPages) {
    $filters['page'] = $totalPages;
}

$offset = ($filters['page'] - 1) * $filters['per_page'];

$stmt = $conn->prepare("
    SELECT al.*, u.name AS user_name, u.email AS user_email
    FROM audit_logs al
    LEFT JOIN user u ON u.id = al.user_id
    $whereSql
    ORDER BY {$sortOptions[$filters['sort']]}
    LIMIT ? OFFSET ?
");

$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$filters['per_page'], $offset]);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$showingStart = $totalLogs > 0 ? $offset + 1 : 0;
$showingEnd = $totalLogs > 0 ? min($offset + count($logs), $totalLogs) : 0;
$hasActiveFilters = auditHasActiveFilters($filters);
$filterSummary = auditFilterSummary($filters, $actionTypes, $entityTypes);

function auditLogsUrl(array $overrides = []) {
    global $filters;

    $params = array_merge([
        'search' => $filters['search'],
        'action_type' => $filters['action_type'],
        'entity_type' => $filters['entity_type'],
        'from_date' => $filters['from_date'],
        'to_date' => $filters['to_date'],
        'sort' => $filters['sort'],
        'per_page' => $filters['per_page'],
        'page' => $filters['page'],
    ], $overrides);

    return 'audit_logs.php?' . http_build_query($params);
}

$pageTitle = 'Audit Logs';
include 'includes/header.php';
?>

<style>
    :root {
        --audit-wine: #5f2626;
        --audit-wine-dark: #3b1118;
        --audit-gold: #d5a72f;
        --audit-gold-dark: #8f6910;
        --audit-cream: #fff8ef;
        --audit-soft: #f7f1ea;
        --audit-text: #2b2020;
        --audit-muted: #776b66;
        --audit-border: #eadbd2;
        --audit-danger: #b82f2f;
        --audit-warning: #c46a1f;
    }

    .audit-page-shell {
        background: radial-gradient(circle at top left, rgba(213, 167, 47, 0.18), transparent 30%),
                    linear-gradient(180deg, #fff8ef 0%, #f7f1ea 50%, #ffffff 100%);
        padding: 24px;
        border-radius: 26px;
    }

    .audit-hero {
        background: linear-gradient(135deg, rgba(59, 17, 24, 0.98), rgba(95, 38, 38, 0.94)),
                    radial-gradient(circle at top right, rgba(213, 167, 47, 0.28), transparent 36%);
        border-radius: 28px;
        padding: 34px;
        margin-bottom: 22px;
        box-shadow: 0 24px 60px rgba(59, 17, 24, 0.24);
    }

    .audit-hero-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .audit-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(213, 167, 47, 0.14);
        color: #ffe08a;
        border: 1px solid rgba(213, 167, 47, 0.24);
        border-radius: 999px;
        padding: 8px 14px;
        font-weight: 900;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 12px;
    }

    .audit-hero h2 {
        font-weight: 900;
        letter-spacing: -0.04em;
        margin-bottom: 8px;
        color: #ffffff;
    }

    .audit-hero p {
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.85);
        max-width: 680px;
    }

    .audit-hero-icon {
        width: 86px;
        height: 86px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: #ffffff;
    }

    .audit-alert {
        border: 0;
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 18px;
        font-weight: 800;
    }

    .audit-alert-success {
        background: #e8f6ee;
        color: #176d40;
        border-left: 5px solid #2f8f57;
    }

    .audit-alert-danger {
        background: #fdeaea;
        color: #9d2f2f;
        border-left: 5px solid var(--audit-danger);
    }

    .audit-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .audit-stat,
    .audit-card,
    .audit-filter-card,
    .audit-toolbar {
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid rgba(95, 38, 38, 0.08);
        box-shadow: 0 16px 42px rgba(95, 38, 38, 0.09);
    }

    .audit-stat {
        border-radius: 22px;
        padding: 22px;
        overflow: hidden;
        position: relative;
    }

    .audit-stat::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: linear-gradient(90deg, var(--audit-gold), var(--audit-wine));
    }

    .audit-stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #fff4cf, #ffffff);
        color: var(--audit-wine);
        font-size: 1.45rem;
        margin-bottom: 14px;
    }

    .audit-stat-value {
        color: var(--audit-wine-dark);
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1.1;
        word-break: break-word;
    }

    .audit-stat-label {
        color: var(--audit-muted);
        font-weight: 800;
        font-size: 0.9rem;
        margin-top: 5px;
    }

    .audit-card {
        border-radius: 26px;
        overflow: hidden;
    }

    .audit-card-inner {
        padding: 26px;
    }

    .audit-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        padding-bottom: 20px;
        margin-bottom: 22px;
        border-bottom: 1px solid rgba(95, 38, 38, 0.08);
    }

    .audit-section-header h3 {
        color: var(--audit-wine-dark);
        font-weight: 900;
        margin-bottom: 5px;
    }

    .audit-section-header p {
        color: var(--audit-muted);
        margin-bottom: 0;
    }

    .audit-filter-card,
    .audit-toolbar {
        border-radius: 22px;
        padding: 22px;
        margin-bottom: 20px;
    }

    .audit-filter-card {
        background: linear-gradient(135deg, #ffffff, #fff8ef);
    }

    .audit-filter-card .form-label,
    .audit-toolbar-title {
        font-weight: 900;
        color: var(--audit-wine-dark);
        font-size: 0.88rem;
    }

    .audit-filter-card .form-control,
    .audit-filter-card .form-select {
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid var(--audit-border);
        background-color: #fffdfb;
        font-weight: 700;
    }

    .audit-btn-primary,
    .audit-btn-secondary,
    .audit-btn-warning,
    .audit-btn-gold,
    .audit-btn-danger {
        border-radius: 999px !important;
        font-weight: 900;
        min-height: 44px;
        padding: 0.62rem 1.1rem;
        white-space: nowrap;
    }

    .audit-btn-primary {
        background: linear-gradient(135deg, var(--audit-wine), var(--audit-wine-dark)) !important;
        border: none !important;
        color: #fff !important;
    }

    .audit-btn-secondary {
        background: #fff !important;
        color: var(--audit-wine) !important;
        border: 1px solid rgba(95, 38, 38, 0.18) !important;
    }

    .audit-btn-warning {
        background: linear-gradient(135deg, #e98a2a, var(--audit-warning)) !important;
        border: none !important;
        color: #fff !important;
    }

    .audit-btn-gold {
        background: linear-gradient(135deg, var(--audit-gold), var(--audit-gold-dark)) !important;
        border: none !important;
        color: #2b2020 !important;
    }

    .audit-btn-danger {
        background: linear-gradient(135deg, #d94343, var(--audit-danger)) !important;
        border: none !important;
        color: #fff !important;
    }

    .audit-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .audit-toolbar-actions,
    .audit-toolbar-checks {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .audit-toolbar .form-check {
        margin: 0;
        font-weight: 800;
        color: var(--audit-muted);
    }

    .audit-table-card {
        background: #ffffff;
        border-radius: 22px;
        border: 1px solid rgba(95, 38, 38, 0.08);
        overflow-x: auto;
        box-shadow: 0 14px 36px rgba(95, 38, 38, 0.08);
    }

    .audit-table-card .table {
        margin-bottom: 0;
        vertical-align: top;
        min-width: 1480px;
    }

    .audit-table-card thead th {
        background: var(--audit-wine-dark);
        color: white;
        border: none;
        padding: 15px 14px;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .audit-table-card tbody td {
        padding: 15px 14px;
        border-color: #f0e5dc;
        font-size: 0.9rem;
        vertical-align: top;
    }

    .audit-table-card tbody tr:hover {
        background: #fff8ef;
    }

    .audit-select-col {
        min-width: 54px !important;
        text-align: center;
    }

    .audit-actions-col {
        min-width: 170px !important;
    }

    .audit-json-preview {
        display: block;
        font-family: 'SF Mono', Monaco, Consolas, 'Courier New', monospace;
        font-size: 0.75rem;
        line-height: 1.5;
        color: #3b2c28;
        background: #faf7f2;
        padding: 8px 10px;
        border-radius: 6px;
        white-space: pre-wrap;
        word-break: break-word;
        overflow-wrap: anywhere;
        max-height: 150px;
        overflow-y: auto;
        border: none;
        margin: 0;
    }

    .audit-user-cell {
        min-width: 230px;
        font-weight: 800;
        color: var(--audit-text);
    }

    .audit-action-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 0.74rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    .action-login,
    .action-approve { background: #e6f6ed; color: #167241; }
    .action-logout { background: #eef2ff; color: #3949ab; }
    .action-create { background: #fff4cf; color: #7a5800; }
    .action-update { background: #e7f0ff; color: #2759b8; }
    .action-delete,
    .action-reject { background: #fde9e9; color: #a92d2d; }
    .action-role { background: #f3e8ff; color: #7e22ce; }
    .action-default { background: #f2ede7; color: var(--audit-wine); }

    .audit-view-btn,
    .audit-row-delete-btn {
        border-radius: 999px !important;
        padding: 7px 12px !important;
        font-weight: 900;
        white-space: nowrap;
    }

    .audit-empty-state {
        background: #fff8ef;
        border: 1px dashed rgba(95, 38, 38, 0.22);
        border-radius: 22px;
        padding: 50px 20px;
        text-align: center;
    }

    .audit-footer-row {
        background: #ffffff;
        border: 1px solid rgba(95, 38, 38, 0.08);
        border-radius: 18px;
        padding: 16px 18px;
        margin-top: 18px;
        box-shadow: 0 10px 28px rgba(95, 38, 38, 0.07);
    }

    .audit-modal .modal-content {
        border: none;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 28px 76px rgba(43, 32, 32, 0.25);
    }

    .audit-modal .modal-header {
        background: linear-gradient(135deg, var(--audit-wine), var(--audit-wine-dark));
        color: #ffffff;
        border-bottom: 4px solid var(--audit-gold);
    }

    .audit-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .audit-modal-danger .modal-header {
        background: linear-gradient(135deg, #d94343, #7d1515);
        border-bottom-color: #ffb2b2;
    }

    .audit-detail-grid {
        background: #fff8ef;
        border: 1px solid rgba(95, 38, 38, 0.08);
        border-radius: 18px;
        padding: 16px;
    }

    .audit-detail-grid strong {
        color: var(--audit-wine-dark);
        font-size: 0.84rem;
        text-transform: uppercase;
    }

    .audit-modal pre {
        max-height: 450px;
        overflow: auto;
        font-size: 0.82rem;
        border-radius: 12px !important;
        background: #1e1e1e !important;
        color: #d4d4d4;
        border: none !important;
        padding: 16px !important;
        margin: 0;
        font-family: 'SF Mono', Monaco, Consolas, monospace;
        white-space: pre-wrap;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .audit-danger-copy {
        background: #fff1f1;
        border: 1px solid #f5bcbc;
        color: #8c1f1f;
        border-radius: 14px;
        padding: 14px;
        font-weight: 800;
    }

    .audit-filter-summary {
        background: #fff8ef;
        border: 1px solid var(--audit-border);
        border-radius: 14px;
        padding: 12px 14px;
        margin-top: 12px;
    }

    @media (max-width: 991.98px) {
        .audit-page-shell {
            padding: 16px;
        }

        .audit-hero,
        .audit-card-inner,
        .audit-filter-card,
        .audit-toolbar {
            padding: 20px;
        }
    }

    @media (max-width: 575.98px) {
        .audit-hero {
            border-radius: 22px;
        }

        .audit-hero h2 {
            font-size: 1.45rem;
        }

        .audit-btn-primary,
        .audit-btn-secondary,
        .audit-btn-warning,
        .audit-btn-gold,
        .audit-btn-danger,
        .audit-toolbar-actions,
        .audit-toolbar-actions .btn {
            width: 100%;
        }

        .audit-toolbar {
            align-items: stretch;
        }

        .audit-toolbar-checks {
            align-items: flex-start;
            flex-direction: column;
        }

        .audit-footer-row {
            text-align: center;
        }

        .audit-footer-row .btn-group {
            width: 100%;
        }

        .audit-footer-row .btn-group .btn {
            flex: 1;
        }
    }
</style>

<div class="audit-page-shell">
    <div class="audit-hero">
        <div class="audit-hero-content">
            <div>
                <span class="audit-kicker">
                    <i class="fas fa-shield-alt"></i>
                    System Security Record
                </span>
                <h2>Audit Logs Center</h2>
                <p>Monitor sensitive system actions, administrator activities, data changes, and security-related events across the AMSA admin tools.</p>
            </div>

            <div class="audit-hero-icon">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert audit-alert audit-alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo auditH($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert audit-alert audit-alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo auditH($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="audit-summary">
        <div class="audit-stat">
            <div class="audit-stat-icon"><i class="fas fa-history"></i></div>
            <div class="audit-stat-value"><?php echo (int) $totalLogs; ?></div>
            <div class="audit-stat-label">Total Matching Logs</div>
        </div>

        <div class="audit-stat">
            <div class="audit-stat-icon"><i class="fas fa-archive"></i></div>
            <div class="audit-stat-value"><?php echo (int) $totalAllLogs; ?></div>
            <div class="audit-stat-label">All Audit Logs</div>
        </div>

        <div class="audit-stat">
            <div class="audit-stat-icon"><i class="fas fa-list"></i></div>
            <div class="audit-stat-value"><?php echo count($logs); ?></div>
            <div class="audit-stat-label">Records on This Page</div>
        </div>

        <div class="audit-stat">
            <div class="audit-stat-icon"><i class="fas fa-filter"></i></div>
            <div class="audit-stat-value"><?php echo auditH($actionTypes[$filters['action_type']] ?? 'All'); ?></div>
            <div class="audit-stat-label">Current Action Filter</div>
        </div>

        <div class="audit-stat">
            <div class="audit-stat-icon"><i class="fas fa-database"></i></div>
            <div class="audit-stat-value"><?php echo auditH($entityTypes[$filters['entity_type']] ?? 'All'); ?></div>
            <div class="audit-stat-label">Current Entity Filter</div>
        </div>
    </div>

    <div class="audit-card">
        <div class="audit-card-inner">
            <div class="audit-section-header">
                <div>
                    <h3>Audit Logs</h3>
                    <p>Inspect, filter, and manage recorded administrative and system activities.</p>
                </div>

                <span class="audit-action-badge action-default">
                    <?php echo (int) $totalLogs; ?> logs
                </span>
            </div>

            <div class="audit-filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="search" name="search" class="form-control" value="<?php echo auditH($filters['search']); ?>" placeholder="User, action, entity, ID, or IP">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Action Type</label>
                        <select name="action_type" class="form-select">
                            <?php foreach ($actionTypes as $value => $label): ?>
                                <option value="<?php echo auditH($value); ?>" <?php echo $filters['action_type'] === $value ? 'selected' : ''; ?>>
                                    <?php echo auditH($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Entity Type</label>
                        <select name="entity_type" class="form-select">
                            <?php foreach ($entityTypes as $value => $label): ?>
                                <option value="<?php echo auditH($value); ?>" <?php echo $filters['entity_type'] === $value ? 'selected' : ''; ?>>
                                    <?php echo auditH($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo auditH($filters['from_date']); ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo auditH($filters['to_date']); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Sort</label>
                        <select name="sort" class="form-select">
                            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                            <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                            <option value="action_asc" <?php echo $filters['sort'] === 'action_asc' ? 'selected' : ''; ?>>Action A-Z</option>
                            <option value="entity_asc" <?php echo $filters['sort'] === 'entity_asc' ? 'selected' : ''; ?>>Entity Type A-Z</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Rows</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ([10, 25, 50] as $option): ?>
                                <option value="<?php echo (int) $option; ?>" <?php echo $filters['per_page'] === $option ? 'selected' : ''; ?>>
                                    <?php echo (int) $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2 flex-wrap">
                        <input type="hidden" name="page" value="1">
                        <button type="submit" class="btn audit-btn-primary">
                            <i class="fas fa-filter me-1"></i> Apply
                        </button>
                        <a href="audit_logs.php" class="btn audit-btn-secondary">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <form method="POST" id="bulkDeleteForm">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="delete_selected">

                <div class="audit-toolbar">
                    <div>
                        <div class="audit-toolbar-title">Deletion Management</div>
                        <small class="text-muted">Use selection controls carefully. Deleted audit logs cannot be recovered.</small>
                    </div>

                    <div class="audit-toolbar-checks">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectVisibleLogs">
                            <label class="form-check-label" for="selectVisibleLogs">Select Visible</label>
                        </div>
                    </div>

                    <div class="audit-toolbar-actions">
                        <button type="button" class="btn audit-btn-warning" id="deleteSelectedBtn" data-bs-toggle="modal" data-bs-target="#deleteSelectedModal" disabled>
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected
                        </button>

                        <button type="button" class="btn audit-btn-gold" data-bs-toggle="modal" data-bs-target="#deleteFilteredModal" <?php echo $hasActiveFilters && $totalLogs > 0 ? '' : 'disabled'; ?>>
                            <i class="fas fa-filter me-1"></i> Delete Filtered Results
                        </button>

                        <button type="button" class="btn audit-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal" <?php echo $totalAllLogs > 0 ? '' : 'disabled'; ?>>
                            <i class="fas fa-trash me-1"></i> Delete All Audit Logs
                        </button>
                    </div>
                </div>

                <?php if (empty($logs)): ?>
                    <div class="audit-empty-state">
                        <i class="fas fa-history fa-2x mb-3"></i>
                        <h4>No Audit Logs Found</h4>
                        <p class="mb-0">Try another search or filter combination.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive audit-table-card">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th class="audit-select-col">
                                        <input class="form-check-input" type="checkbox" id="selectAllLogs" aria-label="Select all audit logs on this page">
                                    </th>
                                    <th>Date/Time</th>
                                    <th>Admin/User</th>
                                    <th>Action</th>
                                    <th>Entity Type</th>
                                    <th>Entity ID</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Old Values</th>
                                    <th>New Values</th>
                                    <th class="audit-actions-col">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                        $logId = (int) $log['id'];
                                        $userLabel = trim(($log['user_name'] ?? '') . ' ' . (($log['user_email'] ?? '') ? '(' . $log['user_email'] . ')' : ''));
                                        $userLabel = $userLabel !== '' ? $userLabel : 'System / Unknown';
                                        $shortAgent = strlen((string) $log['user_agent']) > 60 ? substr((string) $log['user_agent'], 0, 60) . '...' : (string) $log['user_agent'];
                                        $actionClass = auditActionClass($log['action'] ?? '');
                                        $createdLabel = $log['created_at'] ? date('M d, Y h:i A', strtotime($log['created_at'])) : 'N/A';
                                    ?>

                                    <tr>
                                        <td class="audit-select-col">
                                            <input class="form-check-input audit-log-checkbox" type="checkbox" name="selected_logs[]" value="<?php echo $logId; ?>" aria-label="Select audit log <?php echo $logId; ?>">
                                        </td>

                                        <td>
                                            <strong><?php echo auditH($log['created_at'] ? date('M d, Y', strtotime($log['created_at'])) : 'N/A'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo auditH($log['created_at'] ? date('h:i A', strtotime($log['created_at'])) : ''); ?></small>
                                        </td>

                                        <td class="audit-user-cell">
                                            <?php echo auditH($userLabel); ?>
                                        </td>

                                        <td>
                                            <span class="audit-action-badge <?php echo auditH($actionClass); ?>">
                                                <?php echo auditH($log['action']); ?>
                                            </span>
                                        </td>

                                        <td><?php echo auditH($log['entity_type'] ?? '-'); ?></td>
                                        <td><?php echo auditH((string) ($log['entity_id'] ?? '-')); ?></td>
                                        <td><?php echo auditH($log['ip_address'] ?? '-'); ?></td>
                                        <td><?php echo auditH($shortAgent ?: '-'); ?></td>

                                        <td>
                                            <code class="audit-json-preview"><?php echo auditH(auditPreview($log['old_values'])); ?></code>
                                        </td>

                                        <td>
                                            <code class="audit-json-preview"><?php echo auditH(auditPreview($log['new_values'])); ?></code>
                                        </td>

                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="button" class="btn btn-sm audit-btn-primary audit-view-btn" data-bs-toggle="modal" data-bs-target="#auditLogModal<?php echo $logId; ?>">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </button>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm audit-btn-danger audit-row-delete-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteSingleModal"
                                                    data-log-id="<?php echo $logId; ?>"
                                                    data-action="<?php echo auditH($log['action']); ?>"
                                                    data-user="<?php echo auditH($userLabel); ?>"
                                                    data-created="<?php echo auditH($createdLabel); ?>"
                                                >
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade audit-modal" id="auditLogModal<?php echo $logId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-clipboard-list me-2"></i>
                                                        Audit Log #<?php echo $logId; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="audit-detail-grid row g-3 mb-3">
                                                        <div class="col-md-6">
                                                            <strong>Date/Time</strong><br>
                                                            <?php echo auditH($createdLabel); ?>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <strong>Admin/User</strong><br>
                                                            <?php echo auditH($userLabel); ?>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <strong>Action</strong><br>
                                                            <?php echo auditH($log['action']); ?>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <strong>Entity Type</strong><br>
                                                            <?php echo auditH($log['entity_type'] ?? '-'); ?>
                                                        </div>

                                                        <div class="col-md-4">
                                                            <strong>Entity ID</strong><br>
                                                            <?php echo auditH((string) ($log['entity_id'] ?? '-')); ?>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <strong>IP Address</strong><br>
                                                            <?php echo auditH($log['ip_address'] ?? '-'); ?>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <strong>User Agent</strong><br>
                                                            <?php echo auditH($log['user_agent'] ?? '-'); ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <strong>Old Values</strong>
                                                        <pre class="p-3 rounded mt-2 mb-0"><?php echo auditH(auditPrettyJson($log['old_values'])); ?></pre>
                                                    </div>

                                                    <div>
                                                        <strong>New Values</strong>
                                                        <pre class="p-3 rounded mt-2 mb-0"><?php echo auditH(auditPrettyJson($log['new_values'])); ?></pre>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn audit-btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="audit-footer-row d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted">
                            Showing <?php echo (int) $showingStart; ?>-<?php echo (int) $showingEnd; ?> of <?php echo (int) $totalLogs; ?> logs
                        </span>

                        <div class="btn-group">
                            <a class="btn btn-outline-primary <?php echo $filters['page'] <= 1 ? 'disabled' : ''; ?>" href="<?php echo auditH(auditLogsUrl(['page' => max(1, $filters['page'] - 1)])); ?>">
                                Previous
                            </a>

                            <a class="btn btn-outline-primary <?php echo $filters['page'] >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo auditH(auditLogsUrl(['page' => min($totalPages, $filters['page'] + 1)])); ?>">
                                Next
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="singleDeleteForm">
    <?php echo csrfInput(); ?>
    <input type="hidden" name="action" value="delete_single">
    <input type="hidden" name="log_id" id="singleDeleteLogId">
</form>

<form method="POST" id="filteredDeleteForm">
    <?php echo csrfInput(); ?>
    <input type="hidden" name="action" value="delete_filtered">
    <input type="hidden" name="search" value="<?php echo auditH($filters['search']); ?>">
    <input type="hidden" name="action_type" value="<?php echo auditH($filters['action_type']); ?>">
    <input type="hidden" name="entity_type" value="<?php echo auditH($filters['entity_type']); ?>">
    <input type="hidden" name="from_date" value="<?php echo auditH($filters['from_date']); ?>">
    <input type="hidden" name="to_date" value="<?php echo auditH($filters['to_date']); ?>">
    <input type="hidden" name="sort" value="<?php echo auditH($filters['sort']); ?>">
    <input type="hidden" name="per_page" value="<?php echo (int) $filters['per_page']; ?>">
</form>

<form method="POST" id="deleteAllForm">
    <?php echo csrfInput(); ?>
    <input type="hidden" name="action" value="delete_all">
    <input type="hidden" name="delete_all_confirmation" id="deleteAllConfirmationHidden">
</form>

<div class="modal fade audit-modal audit-modal-danger" id="deleteSingleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Audit Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="audit-detail-grid row g-3 mb-3">
                    <div class="col-6"><strong>Audit Log ID</strong><br><span id="singleDeleteLogIdText">-</span></div>
                    <div class="col-6"><strong>Action Type</strong><br><span id="singleDeleteActionText">-</span></div>
                    <div class="col-12"><strong>User/Admin Name</strong><br><span id="singleDeleteUserText">-</span></div>
                    <div class="col-12"><strong>Date/Time</strong><br><span id="singleDeleteCreatedText">-</span></div>
                </div>
                <div class="audit-danger-copy">
                    This audit log record will be permanently deleted and cannot be recovered.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn audit-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="singleDeleteForm" class="btn audit-btn-danger">Confirm Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade audit-modal audit-modal-danger" id="deleteSelectedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Delete Selected Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    You are about to permanently delete <strong id="selectedDeleteCount">0</strong> selected audit log records. This action cannot be undone.
                </p>
                <div class="audit-danger-copy">
                    Review your selection before continuing.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn audit-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="bulkDeleteForm" class="btn audit-btn-warning" id="confirmDeleteSelectedBtn">Delete Selected</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade audit-modal audit-modal-danger" id="deleteFilteredModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Delete Filtered Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You are about to permanently delete all audit logs matching the current filters.</p>
                <div class="audit-filter-summary">
                    <strong>Current filter summary</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($filterSummary as $summaryItem): ?>
                            <li><?php echo auditH($summaryItem); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <p class="mt-3 mb-0"><strong>Records affected:</strong> <?php echo (int) $totalLogs; ?></p>
                <div class="audit-danger-copy mt-3">
                    This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn audit-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="filteredDeleteForm" class="btn audit-btn-gold" <?php echo $hasActiveFilters && $totalLogs > 0 ? '' : 'disabled'; ?>>Delete Filtered Results</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade audit-modal audit-modal-danger" id="deleteAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>DANGER: Delete Entire Audit Log History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="audit-danger-copy mb-3">
                    This will permanently remove ALL audit log records from the system.
                    <br><br>
                    This action cannot be undone.
                    <br><br>
                    Are you absolutely sure?
                </div>
                <label for="deleteAllConfirmationInput" class="form-label fw-bold">Type <code>DELETE ALL</code> to confirm</label>
                <input type="text" class="form-control" id="deleteAllConfirmationInput" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn audit-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAllForm" class="btn audit-btn-danger" id="confirmDeleteAllBtn" disabled>Delete All Audit Logs</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(document.querySelectorAll('.audit-log-checkbox'));
    const selectAll = document.getElementById('selectAllLogs');
    const selectVisible = document.getElementById('selectVisibleLogs');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedDeleteCount = document.getElementById('selectedDeleteCount');
    const confirmDeleteSelectedBtn = document.getElementById('confirmDeleteSelectedBtn');

    function selectedCount() {
        return checkboxes.filter((checkbox) => checkbox.checked).length;
    }

    function syncSelectionState() {
        const count = selectedCount();
        const allChecked = checkboxes.length > 0 && count === checkboxes.length;
        const someChecked = count > 0 && count < checkboxes.length;

        if (selectAll) {
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked;
        }

        if (selectVisible) {
            selectVisible.checked = allChecked;
            selectVisible.indeterminate = someChecked;
        }

        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = count === 0;
        }

        if (selectedDeleteCount) {
            selectedDeleteCount.textContent = String(count);
        }

        if (confirmDeleteSelectedBtn) {
            confirmDeleteSelectedBtn.disabled = count === 0;
        }
    }

    function setAllVisible(checked) {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });
        syncSelectionState();
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            setAllVisible(this.checked);
        });
    }

    if (selectVisible) {
        selectVisible.addEventListener('change', function () {
            setAllVisible(this.checked);
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectionState);
    });

    const deleteSingleModal = document.getElementById('deleteSingleModal');
    if (deleteSingleModal) {
        deleteSingleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) {
                return;
            }

            document.getElementById('singleDeleteLogId').value = button.dataset.logId || '';
            document.getElementById('singleDeleteLogIdText').textContent = button.dataset.logId || '-';
            document.getElementById('singleDeleteActionText').textContent = button.dataset.action || '-';
            document.getElementById('singleDeleteUserText').textContent = button.dataset.user || '-';
            document.getElementById('singleDeleteCreatedText').textContent = button.dataset.created || '-';
        });
    }

    const deleteAllInput = document.getElementById('deleteAllConfirmationInput');
    const deleteAllHidden = document.getElementById('deleteAllConfirmationHidden');
    const confirmDeleteAllBtn = document.getElementById('confirmDeleteAllBtn');

    if (deleteAllInput && deleteAllHidden && confirmDeleteAllBtn) {
        deleteAllInput.addEventListener('input', function () {
            deleteAllHidden.value = this.value;
            confirmDeleteAllBtn.disabled = this.value !== 'DELETE ALL';
        });
    }

    syncSelectionState();
});
</script>

<?php include 'includes/footer.php'; ?>
