<?php
require_once '../config/database.php';
requireMember('login.php');

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$request = $requestId ? getPointRequestById($requestId) : null;

if (!$request) {
    http_response_code(404);
    exit('Evidence not found.');
}

$role = currentUserRole();
$userId = currentUserId();
if (!isAdminRole($role) && (int) $request['user_id'] !== (int) $userId) {
    http_response_code(403);
    exit('Access denied.');
}

$relativePath = $request['eop_evidence'] ?? '';
$baseDir = realpath(__DIR__ . '/uploads/eop');
$fileName = basename($relativePath);
$filePath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . $fileName) : false;

if (!$baseDir || !$filePath || strpos($filePath, $baseDir) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('Evidence file not found.');
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

if (!isset($mimeTypes[$extension])) {
    http_response_code(403);
    exit('File type is not allowed.');
}

header('Content-Type: ' . $mimeTypes[$extension]);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="evidence-' . $requestId . '.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit();
