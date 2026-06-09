<?php
require_once '../config/database.php';
requireAdmin('login.php');

$messageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($messageId <= 0) {
    header('Location: contact_messages.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT id, name, email, whatsapp_number, subject, message, submission_date
    FROM contact_messages
    WHERE id = ?
");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();

if (!$message) {
    header('Location: contact_messages.php');
    exit();
}

$pageTitle = 'View Contact Message';
include 'includes/header.php';
?>

<div class="admin-card amsa-card p-4">
    <div class="admin-section-header">
        <div>
            <h3>Contact Message #<?php echo (int) $message['id']; ?></h3>
            <p>Full public contact form submission.</p>
        </div>
        <a href="contact_messages.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="p-3 rounded bg-light border h-100">
                <strong>Name</strong>
                <div><?php echo htmlspecialchars($message['name']); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light border h-100">
                <strong>Email</strong>
                <div><a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light border h-100">
                <strong>WhatsApp</strong>
                <div><?php echo htmlspecialchars($message['whatsapp_number'] ?: 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light border h-100">
                <strong>Submission Date</strong>
                <div><?php echo $message['submission_date'] ? date('M d, Y h:i A', strtotime($message['submission_date'])) : 'N/A'; ?></div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h4 class="mb-2"><?php echo htmlspecialchars($message['subject']); ?></h4>
        <div class="p-4 rounded bg-light border">
            <?php echo nl2br(htmlspecialchars($message['message'] ?: 'No message provided.')); ?>
        </div>
    </div>

    <a href="contact_messages.php" class="btn btn-secondary amsa-btn amsa-btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Messages
    </a>
</div>

<?php include 'includes/footer.php'; ?>
