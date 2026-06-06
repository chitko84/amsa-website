<?php
require_once '../config/database.php';
requireAdmin('login.php');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
        $messageId = (int) ($_POST['message_id'] ?? 0);

        if ($messageId > 0) {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->bind_param("i", $messageId);

            if ($stmt->execute()) {
                $success = 'Contact message deleted.';
                logAuditAction('contact_message_delete', 'contact_messages', $messageId);
            } else {
                $error = 'Failed to delete contact message.';
            }
        } else {
            $error = 'Invalid contact message request.';
        }
    }
}

$contactSearch = trim($_GET['search'] ?? '');
$sortMap = [
    'newest' => 'submission_date DESC, id DESC',
    'oldest' => 'submission_date ASC, id ASC',
    'subject_asc' => 'subject ASC, id ASC',
    'subject_desc' => 'subject DESC, id DESC',
];
$sortRaw = $_GET['sort'] ?? 'newest';
$sortOption = array_key_exists($sortRaw, $sortMap) ? $sortRaw : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 25, 50], true)) {
    $perPage = 10;
}
$orderBy = $sortMap[$sortOption] ?? $sortMap['newest'];
$whereSql = '';
$types = '';
$params = [];
if ($contactSearch !== '') {
    $whereSql = 'WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?';
    $types = 'ssss';
    $searchLike = '%' . $contactSearch . '%';
    $params = [$searchLike, $searchLike, $searchLike, $searchLike];
}
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM contact_messages $whereSql");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalMessages = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalMessages / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$stmt = $conn->prepare("
    SELECT id, name, email, whatsapp_number, subject, message, submission_date
    FROM contact_messages
    $whereSql
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
");
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$showingStart = $totalMessages > 0 ? $offset + 1 : 0;
$showingEnd = $totalMessages > 0 ? min($offset + count($messages), $totalMessages) : 0;

function contactMessagesUrl(array $overrides = []) {
    global $contactSearch, $sortOption, $perPage, $page;

    $params = array_merge([
        'search' => $contactSearch,
        'sort' => $sortOption,
        'per_page' => $perPage,
        'page' => $page,
    ], $overrides);
    return 'contact_messages.php?' . http_build_query($params);
}

$pageTitle = 'Contact Messages';
include 'includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card amsa-card p-4">
    <div class="admin-section-header">
        <div>
            <h3>Contact Messages</h3>
            <p>Review enquiries submitted from the public AMSA contact form.</p>
        </div>
        <span class="amsa-badge amsa-badge-info"><?php echo (int) $totalMessages; ?> messages</span>
    </div>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-5"><label class="form-label">Search</label><input type="search" name="search" class="form-control amsa-form-control" value="<?php echo htmlspecialchars($contactSearch); ?>" placeholder="Name, email, subject, or message"></div>
        <div class="col-md-3"><label class="form-label">Sort</label><select name="sort" class="form-select amsa-form-control"><option value="newest" <?php echo $sortOption === 'newest' ? 'selected' : ''; ?>>Newest First</option><option value="oldest" <?php echo $sortOption === 'oldest' ? 'selected' : ''; ?>>Oldest First</option><option value="subject_asc" <?php echo $sortOption === 'subject_asc' ? 'selected' : ''; ?>>Subject A-Z</option><option value="subject_desc" <?php echo $sortOption === 'subject_desc' ? 'selected' : ''; ?>>Subject Z-A</option></select></div>
        <div class="col-md-2"><label class="form-label">Rows</label><select name="per_page" class="form-select amsa-form-control"><?php foreach ([10,25,50] as $option): ?><option value="<?php echo $option; ?>" <?php echo $perPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><input type="hidden" name="page" value="1"><button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary">Apply</button><a href="contact_messages.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">Reset</a></div>
    </form>

    <?php if (empty($messages)): ?>
        <div class="amsa-empty-state">
            <i class="fas fa-envelope-open-text fa-2x mb-3 text-primary"></i>
            <h4>No Contact Messages</h4>
            <p class="mb-0">New public contact form submissions will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive amsa-table-wrap">
            <table class="table align-middle amsa-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Subject</th>
                        <th>Message Preview</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                        <?php
                            $messageText = trim((string) ($message['message'] ?? ''));
                            $messagePreview = strlen($messageText) > 100 ? substr($messageText, 0, 100) . '...' : $messageText;
                        ?>
                        <tr>
                            <td>#<?php echo (int) $message['id']; ?></td>
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($message['whatsapp_number']); ?></td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td class="message-preview">
                                <?php echo htmlspecialchars($messagePreview !== '' ? $messagePreview : 'No message provided.'); ?>
                                <?php if (strlen($messageText) > 100): ?>
                                    <br><a href="view_contact_message.php?id=<?php echo (int) $message['id']; ?>" class="fw-bold text-primary">Read More</a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $message['submission_date'] ? date('M d, Y h:i A', strtotime($message['submission_date'])) : 'N/A'; ?></td>
                            <td>
                                <div class="admin-action-group">
                                    <a href="view_contact_message.php?id=<?php echo (int) $message['id']; ?>" class="btn btn-sm btn-primary amsa-btn amsa-btn-primary amsa-btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form method="POST" id="deleteContactMessageForm<?php echo (int) $message['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="message_id" value="<?php echo (int) $message['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-danger amsa-btn amsa-btn-danger amsa-btn-sm" data-bs-toggle="modal" data-bs-target="#deleteContactMessageModal<?php echo (int) $message['id']; ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="deleteContactMessageModal<?php echo (int) $message['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content amsa-modal">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Deletion</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to permanently delete this contact message?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" form="deleteContactMessageForm<?php echo (int) $message['id']; ?>" class="btn btn-danger amsa-btn amsa-btn-danger">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <span class="text-muted">Showing <?php echo (int) $showingStart; ?>&ndash;<?php echo (int) $showingEnd; ?> of <?php echo (int) $totalMessages; ?> messages</span>
            <div class="btn-group">
                <a class="btn btn-outline-primary amsa-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(contactMessagesUrl(['page' => max(1, $page - 1)])); ?>">Previous</a>
                <a class="btn btn-outline-primary amsa-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(contactMessagesUrl(['page' => min($totalPages, $page + 1)])); ?>">Next</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
