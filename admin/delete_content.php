<?php
require_once '../config/database.php';
requireAdmin('login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken()) {
    header('Location: dashboard.php?error=invalid_request');
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$type = $_POST['type'] ?? '';
$allowedTypes = ['achievement', 'testimonial', 'news', 'announcement', 'workshop', 'volunteer', 'community_engagement'];

if (!$id || !in_array($type, $allowedTypes, true)) {
    header('Location: dashboard.php?error=invalid_request');
    exit();
}

// Delete images first
$imgStmt = $conn->prepare("SELECT img_name FROM image WHERE post_id = ?");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($images as $image) {
    $filepath = '../uploads/' . basename($image['img_name']);
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Delete post
$stmt = $conn->prepare("DELETE FROM post WHERE id = ? AND category = ?");
$stmt->bind_param("is", $id, $type);
$stmt->execute();
logAuditAction('delete_' . $type, 'post', $id);

$returnTo = $_POST['return_to'] ?? '';
$allowedReturn = false;
if (is_string($returnTo) && $returnTo !== '') {
    $allowedReturn = (bool) preg_match('/^add_event\.php(\?page=\d+)?$/', $returnTo)
        || (bool) preg_match('/^add_news\.php(\?page=\d+)?$/', $returnTo)
        || (bool) preg_match('/^add_content\.php\?type=(achievement|testimonial)(&page=\d+)?$/', $returnTo);
}

if ($allowedReturn) {
    $separator = strpos($returnTo, '?') === false ? '?' : '&';
    header('Location: ' . $returnTo . $separator . 'msg=deleted');
    exit();
}

header('Location: dashboard.php?msg=deleted');
exit();
?>
