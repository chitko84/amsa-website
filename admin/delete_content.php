<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';

// Delete images first
$imgStmt = $conn->prepare("SELECT img_name FROM image WHERE post_id = ?");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($images as $image) {
    $filepath = '../uploads/' . $image['img_name'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Delete post
$stmt = $conn->prepare("DELETE FROM post WHERE id = ? AND category = ?");
$stmt->bind_param("is", $id, $type);
$stmt->execute();

header('Location: dashboard.php?msg=deleted');
exit();
?>