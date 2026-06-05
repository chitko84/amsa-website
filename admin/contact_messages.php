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

$result = $conn->query("
    SELECT id, name, email, whatsapp_number, subject, message, submission_date
    FROM contact_messages
    ORDER BY submission_date DESC, id DESC
");
$messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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
        <span class="amsa-badge amsa-badge-info"><?php echo count($messages); ?> messages</span>
    </div>

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
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
