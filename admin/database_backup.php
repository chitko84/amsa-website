<?php
require_once '../config/database.php';
requireSystemAdmin('login.php');

function backupTimestamp($withTime = true) {
    return $withTime ? date('Y_m_d_His') : date('Y_m_d');
}

function sendDownloadHeaders($filename, $contentType) {
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function csvValue($value) {
    return $value === null ? '' : $value;
}

function streamCsv($filename, array $headers, array $rows) {
    sendDownloadHeaders($filename, 'text/csv; charset=utf-8');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, array_map('csvValue', $row));
    }

    fclose($output);
    exit();
}

function sqlIdentifier($name) {
    return '`' . str_replace('`', '``', $name) . '`';
}

function sqlValue($value) {
    global $conn;

    if ($value === null) {
        return 'NULL';
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

function exportFullDatabaseSql() {
    global $conn;

    $filename = 'amsa_backup_' . backupTimestamp(true) . '.sql';
    sendDownloadHeaders($filename, 'application/sql; charset=utf-8');

    echo "-- AMSA database backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Database: " . DB_NAME . "\n\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET time_zone = \"+00:00\";\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    echo "CREATE DATABASE IF NOT EXISTS " . sqlIdentifier(DB_NAME) . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\n";
    echo "USE " . sqlIdentifier(DB_NAME) . ";\n\n";

    $tablesResult = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
    if (!$tablesResult) {
        echo "-- Could not read table list.\n";
        exit();
    }

    $tables = [];
    while ($row = $tablesResult->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $quotedTable = sqlIdentifier($table);
        $createResult = $conn->query('SHOW CREATE TABLE ' . $quotedTable);
        $createRow = $createResult ? $createResult->fetch_assoc() : null;
        $createSql = $createRow['Create Table'] ?? '';

        echo "-- --------------------------------------------------------\n";
        echo "-- Table structure for {$quotedTable}\n";
        echo "-- --------------------------------------------------------\n\n";
        echo "DROP TABLE IF EXISTS {$quotedTable};\n";
        echo $createSql . ";\n\n";

        $dataResult = $conn->query('SELECT * FROM ' . $quotedTable);
        if (!$dataResult || $dataResult->num_rows === 0) {
            continue;
        }

        echo "-- Data for {$quotedTable}\n\n";
        while ($dataRow = $dataResult->fetch_assoc()) {
            $columns = array_map('sqlIdentifier', array_keys($dataRow));
            $values = array_map('sqlValue', array_values($dataRow));
            echo 'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }

    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit();
}

function exportContactMessagesCsv() {
    global $conn;

    $rows = [];
    $result = $conn->query("
        SELECT name, email, whatsapp_number, subject, message, submission_date
        FROM contact_messages
        ORDER BY submission_date DESC, id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                $row['name'],
                $row['email'],
                $row['whatsapp_number'],
                $row['subject'],
                $row['message'],
                $row['submission_date'],
            ];
        }
    }

    streamCsv(
        'contact_messages_' . backupTimestamp(false) . '.csv',
        ['Name', 'Email', 'WhatsApp Number', 'Subject', 'Message', 'Submission Date'],
        $rows
    );
}

function exportPointsReportCsv() {
    global $conn;

    $rows = [];
    $result = $conn->query("
        SELECT
            u.id,
            u.name,
            u.email,
            COALESCE(up.total_points, 0) AS total_points,
            COUNT(CASE WHEN pr.status = 'approved' THEN 1 END) AS approved_requests,
            COUNT(CASE WHEN pr.status = 'pending' THEN 1 END) AS pending_requests,
            COUNT(CASE WHEN pr.status = 'rejected' THEN 1 END) AS rejected_requests
        FROM user u
        LEFT JOIN user_points up ON up.user_id = u.id
        LEFT JOIN point_request pr ON pr.user_id = u.id
        WHERE u.role = 'member'
        GROUP BY u.id, u.name, u.email, up.total_points
        ORDER BY total_points DESC, approved_requests DESC, u.name ASC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['total_points'],
                $row['approved_requests'],
                $row['pending_requests'],
                $row['rejected_requests'],
            ];
        }
    }

    streamCsv(
        'points_report_' . backupTimestamp(false) . '.csv',
        ['User ID', 'Member Name', 'Email', 'Total Points', 'Approved Requests', 'Pending Requests', 'Rejected Requests'],
        $rows
    );
}

function exportLeaderboardCsv() {
    $rows = [];
    foreach (getLeaderboard(0) as $row) {
        $rows[] = [
            $row['rank'],
            $row['id'],
            $row['name'],
            $row['email'],
            $row['total_points'],
        ];
    }

    streamCsv(
        'leaderboard_' . backupTimestamp(false) . '.csv',
        ['Rank', 'User ID', 'Name', 'Email', 'Total Points'],
        $rows
    );
}

function exportMembersCsv() {
    global $conn;

    $rows = [];
    $result = $conn->query("
        SELECT id, name, email, role, status, created_at
        FROM user
        WHERE role = 'member'
        ORDER BY created_at DESC, id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['role'],
                $row['status'],
                $row['created_at'],
            ];
        }
    }

    streamCsv(
        'members_' . backupTimestamp(false) . '.csv',
        ['User ID', 'Name', 'Email', 'Role', 'Status', 'Registration Date'],
        $rows
    );
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please refresh the page and try again.';
    } else {
        $exportType = $_POST['export_type'] ?? '';

        logAuditAction('data_export', 'backup_center', null, null, ['export_type' => $exportType]);

        switch ($exportType) {
            case 'full_database':
                logAuditAction('database_backup_export', 'backup_center', null, null, ['export_type' => $exportType]);
                exportFullDatabaseSql();
                break;
            case 'contact_messages':
                exportContactMessagesCsv();
                break;
            case 'points_report':
                exportPointsReportCsv();
                break;
            case 'leaderboard':
                exportLeaderboardCsv();
                break;
            case 'members':
                exportMembersCsv();
                break;
            default:
                $error = 'Invalid export request.';
        }
    }
}

$pageTitle = 'Database Backup';
include 'includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="amsa-page-header mb-4">
    <span class="amsa-badge amsa-badge-info mb-3">Admin Only</span>
    <h1>Backup & Export Center</h1>
    <p class="mb-0">Export important AMSA website, member, contact, and points data for safekeeping and reporting.</p>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="amsa-card admin-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="fas fa-database fa-2x text-primary"></i>
                <div>
                    <h3 class="h5 mb-1">Database Backup</h3>
                    <p class="text-muted mb-0">Download a full SQL backup with current database structure and data.</p>
                </div>
            </div>
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="export_type" value="full_database">
                <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                    <i class="fas fa-download me-1"></i> Export Full Database (.sql)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="amsa-card admin-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="fas fa-envelope-open-text fa-2x text-primary"></i>
                <div>
                    <h3 class="h5 mb-1">Contact Message Export</h3>
                    <p class="text-muted mb-0">Export contact form submissions with email, WhatsApp, subject, and message.</p>
                </div>
            </div>
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="export_type" value="contact_messages">
                <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                    <i class="fas fa-file-csv me-1"></i> Export Contact Messages (CSV)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="amsa-card admin-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="fas fa-star fa-2x text-primary"></i>
                <div>
                    <h3 class="h5 mb-1">Points System Export</h3>
                    <p class="text-muted mb-0">Export member totals and request counts for approved, pending, and rejected activities.</p>
                </div>
            </div>
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="export_type" value="points_report">
                <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                    <i class="fas fa-file-csv me-1"></i> Export Points Report (CSV)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="amsa-card admin-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="fas fa-trophy fa-2x text-primary"></i>
                <div>
                    <h3 class="h5 mb-1">Leaderboard Export</h3>
                    <p class="text-muted mb-0">Export admin-visible leaderboard rankings with full member details.</p>
                </div>
            </div>
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="export_type" value="leaderboard">
                <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                    <i class="fas fa-file-csv me-1"></i> Export Leaderboard (CSV)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="amsa-card admin-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <i class="fas fa-users fa-2x text-primary"></i>
                <div>
                    <h3 class="h5 mb-1">Member Export</h3>
                    <p class="text-muted mb-0">Export member list with status, role, and registration date.</p>
                </div>
            </div>
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="export_type" value="members">
                <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">
                    <i class="fas fa-file-csv me-1"></i> Export Members (CSV)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="amsa-alert amsa-alert-warning h-100">
            <h3 class="h5 mb-2">Backup Handling Notes</h3>
            <p class="mb-2">Exports may contain personal information. Store downloaded files securely and share them only with authorized AMSA administrators.</p>
            <p class="mb-0">Use the SQL backup before major content or database maintenance changes.</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
