<?php
require_once 'config/database.php';

const AMSA_CONTACT_EMAIL = 'amsa@student.aiu.edu.my';

function redirectContact($status) {
    header('Location: contact.html?contact=' . urlencode($status));
    exit();
}

function ensureContactMessagesWhatsappColumn(mysqli $conn): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'contact_messages'
          AND COLUMN_NAME = 'whatsapp_number'
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    if ((int) ($row['total'] ?? 0) > 0) {
        return true;
    }

    return (bool) $conn->query("
        ALTER TABLE contact_messages
        ADD COLUMN whatsapp_number varchar(30) NOT NULL DEFAULT '' AFTER email
    ");
}

function sendContactNotification($name, $email, $whatsappNumber, $subject, $message, $submissionDate): bool {
    $bodyText = "New AMSA website contact form submission\n\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "WhatsApp Number: {$whatsappNumber}\n"
        . "Subject: {$subject}\n"
        . "Message: " . ($message !== '' ? $message : 'No message provided') . "\n"
        . "Submission Date: {$submissionDate}\n";

    $bodyHtml = '<h2>New AMSA Website Contact Form Submission</h2>'
        . '<p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>WhatsApp Number:</strong> ' . htmlspecialchars($whatsappNumber, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message !== '' ? $message : 'No message provided', ENT_QUOTES, 'UTF-8')) . '</p>'
        . '<p><strong>Submission Date:</strong> ' . htmlspecialchars($submissionDate, ENT_QUOTES, 'UTF-8') . '</p>';

    $safeMailSubject = str_replace(["\r", "\n"], ' ', $subject);
    $safeReplyName = trim(str_replace(["\r", "\n"], ' ', $name));
    $mailSubject = '[AMSA Website Contact Form] ' . $safeMailSubject;

    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mailerClass = '\\PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $mailerClass(true);
            $mail->setFrom(AMSA_CONTACT_EMAIL, 'AMSA Website');
            $mail->addAddress(AMSA_CONTACT_EMAIL, 'AMSA AIU');
            $mail->addReplyTo($email, $safeReplyName);
            $mail->Subject = $mailSubject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText;
            return $mail->send();
        } catch (Throwable $exception) {
            error_log('AMSA contact PHPMailer notification failed: ' . $exception->getMessage());
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: AMSA Website <' . AMSA_CONTACT_EMAIL . '>',
        'Reply-To: ' . $safeReplyName . ' <' . $email . '>',
    ];

    $sent = mail(AMSA_CONTACT_EMAIL, $mailSubject, $bodyText, implode("\r\n", $headers));
    if (!$sent) {
        error_log('AMSA contact mail() notification failed.');
    }

    return $sent;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectContact('invalid');
}

if (!verifyCsrfToken()) {
    redirectContact('invalid');
}

if (trim($_POST['website'] ?? '') !== '') {
    redirectContact('success');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (
    $name === '' ||
    $email === '' ||
    $whatsappNumber === '' ||
    $subject === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[0-9+\-\s().]{7,30}$/', $whatsappNumber) ||
    strlen($name) > 100 ||
    strlen($email) > 150 ||
    strlen($whatsappNumber) > 30 ||
    strlen($subject) > 200 ||
    strlen($message) > 5000
) {
    redirectContact('invalid');
}

if (!ensureContactMessagesWhatsappColumn($conn)) {
    redirectContact('error');
}

$submissionDate = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO contact_messages (name, email, whatsapp_number, subject, message, submission_date)
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    redirectContact('error');
}

$stmt->bind_param('ssssss', $name, $email, $whatsappNumber, $subject, $message, $submissionDate);

if ($stmt->execute()) {
    sendContactNotification($name, $email, $whatsappNumber, $subject, $message, $submissionDate);
    redirectContact('success');
}

redirectContact('error');
