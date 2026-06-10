<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'amsa_web');

date_default_timezone_set('Asia/Kuala_Lumpur');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+08:00'");

// Start session with production-friendly cookie flags while preserving local XAMPP use.
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('SESSION_IDLE_TIMEOUT', 1800);

function enforceSessionTimeout() {
    if (!currentUserId()) {
        return;
    }

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }

    $_SESSION['last_activity'] = time();
}

function regenerateAuthSession() {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrf($redirect = null) {
    if (verifyCsrfToken()) {
        return true;
    }

    if ($redirect) {
        header('Location: ' . $redirect);
        exit();
    }

    http_response_code(400);
    exit('Invalid request token. Please go back and try again.');
}

function logAuditAction($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
    global $conn;

    $userId = currentUserId();
    $oldJson = $oldValues === null ? null : json_encode($oldValues);
    $newJson = $newValues === null ? null : json_encode($newValues);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        error_log('Audit log prepare failed: ' . $conn->error);
        return false;
    }

    $stmt->bind_param("ississss", $userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip, $agent);
    return $stmt->execute();
}

function uploadFileSecure($file, $uploadDir, $allowedExtensions, $allowedMimes, $maxBytes, &$error) {
    $error = '';

    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose a file to upload.';
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed. Please try again.';
        return null;
    }

    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        $error = 'File size is too large.';
        return null;
    }

    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'File type is not allowed.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        $error = 'File content type is not allowed.';
        return null;
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        $error = 'Upload folder is not available.';
        return null;
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Could not save uploaded file.';
        return null;
    }

    return $safeName;
}

function uploadImageSecure($file, $uploadDir, &$error, $required = true, $maxBytes = null) {
    if ((!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) && !$required) {
        $error = '';
        return null;
    }

    $maxBytes = $maxBytes ?? (2 * 1024 * 1024);

    return uploadFileSecure(
        $file,
        $uploadDir,
        ['jpg', 'jpeg', 'png', 'webp'],
        ['image/jpeg', 'image/png', 'image/webp'],
        $maxBytes,
        $error
    );
}

function selectedUploadFileCount($files) {
    if (!$files || empty($files['name']) || !is_array($files['name'])) {
        return 0;
    }

    $count = 0;
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $count++;
        }
    }

    return $count;
}

function validateImageUploadSelection($files, &$errors = [], $maxFiles = 3, $maxBytes = null) {
    $errors = [];
    $maxBytes = $maxBytes ?? (2 * 1024 * 1024);

    if (!$files || empty($files['name']) || !is_array($files['name'])) {
        return true;
    }

    if (selectedUploadFileCount($files) > $maxFiles) {
        $errors[] = 'You can upload a maximum of ' . $maxFiles . ' images per item.';
        return false;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed. Please try again.';
            continue;
        }

        $size = (int) ($files['size'][$index] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $errors[] = 'Each image must be 2 MB or smaller.';
            continue;
        }

        $extension = strtolower(pathinfo(basename($name), PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'Only JPG, JPEG, PNG, or WEBP images are allowed.';
            continue;
        }

        $tmpName = $files['tmp_name'][$index] ?? '';
        $mime = is_file($tmpName) ? $finfo->file($tmpName) : '';
        if (!in_array($mime, $allowedMimes, true)) {
            $errors[] = 'Uploaded image content type is not allowed.';
        }
    }

    $errors = array_values(array_unique($errors));
    return empty($errors);
}

function uploadMultipleImagesSecure($files, $uploadDir, &$errors = [], $maxFiles = 3, $maxBytes = null) {
    $savedFiles = [];
    $errors = [];
    $maxBytes = $maxBytes ?? (2 * 1024 * 1024);

    if (!$files || empty($files['name']) || !is_array($files['name'])) {
        return $savedFiles;
    }

    if (selectedUploadFileCount($files) > $maxFiles) {
        $errors[] = 'You can upload a maximum of ' . $maxFiles . ' images per item.';
        return $savedFiles;
    }

    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $file = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];

        $error = '';
        $saved = uploadImageSecure($file, $uploadDir, $error, true, $maxBytes);
        if ($saved) {
            $savedFiles[] = $saved;
        } elseif ($error) {
            $errors[] = $error;
        }
    }

    if (!empty($errors)) {
        foreach ($savedFiles as $savedFile) {
            $path = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($savedFile);
            if (is_file($path)) {
                unlink($path);
            }
        }
        return [];
    }

    return $savedFiles;
}

function ensureFundraisingTables() {
    global $conn;

    $fundraisingSql = "
        CREATE TABLE IF NOT EXISTS fundraising (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            status ENUM('published','draft') DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    $imagesSql = "
        CREATE TABLE IF NOT EXISTS fundraising_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fundraising_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            display_order INT DEFAULT 1,
            CONSTRAINT fk_fundraising_images_fundraising
                FOREIGN KEY (fundraising_id)
                REFERENCES fundraising(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";

    return $conn->query($fundraisingSql) && $conn->query($imagesSql);
}

function uploadEvidenceSecure($file, $uploadDir, &$error) {
    return uploadFileSecure(
        $file,
        $uploadDir,
        ['pdf', 'jpg', 'jpeg', 'png'],
        ['application/pdf', 'image/jpeg', 'image/png'],
        5 * 1024 * 1024,
        $error
    );
}

function ensureProfileImageColumn() {
    global $conn;

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'user'
          AND COLUMN_NAME = 'profile_image'
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $exists = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    if ($exists > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE `user` ADD COLUMN `profile_image` varchar(255) DEFAULT NULL AFTER `status`");
}

function profileImageUrl($profileImage = null, $prefix = '../') {
    $profileImage = trim((string) $profileImage);
    if ($profileImage !== '') {
        $relative = ltrim(str_replace('\\', '/', $profileImage), '/');
        $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($absolute)) {
            return rtrim($prefix, '/') . '/' . $relative;
        }
    }

    return rtrim($prefix, '/') . '/img/user.jpg';
}

function getUserProfile($userId) {
    global $conn;

    ensureProfileImageColumn();

    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.profile_image,
               COALESCE(up.total_points, 0) AS total_points
        FROM user u
        LEFT JOIN user_points up ON up.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function deleteStoredProfileImage($profileImage) {
    $profileImage = trim((string) $profileImage);
    if ($profileImage === '') {
        return;
    }

    $relative = ltrim(str_replace('\\', '/', $profileImage), '/');
    if (strpos($relative, 'uploads/profiles/') !== 0) {
        return;
    }

    $absolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($absolute)) {
        unlink($absolute);
    }
}

function saveCroppedProfileImage($userId, $dataUrl, &$error) {
    global $conn;

    $error = '';
    ensureProfileImageColumn();

    if (!is_string($dataUrl) || !preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $dataUrl, $matches)) {
        $error = 'Please preview and crop an image before saving.';
        return false;
    }

    $rawBase64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $rawBase64 = str_replace(' ', '+', trim($rawBase64));
    $imageBytes = base64_decode($rawBase64, true);
    if ($imageBytes === false || strlen($imageBytes) === 0) {
        $error = 'The cropped image could not be processed.';
        return false;
    }

    if (strlen($imageBytes) > 3 * 1024 * 1024) {
        $error = 'Profile image must be 3MB or smaller.';
        return false;
    }

    $imageInfo = @getimagesizefromstring($imageBytes);
    if ($imageInfo === false || empty($imageInfo['mime'])) {
        $error = 'The uploaded file is not a valid image.';
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($imageBytes);
    if (!$mime || $mime === 'application/octet-stream') {
        $mime = $imageInfo['mime'];
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($mimeMap[$mime])) {
        $error = 'Only JPG, PNG, or WEBP profile images are allowed.';
        return false;
    }

    if (function_exists('imagecreatefromstring')) {
        $imageResource = @imagecreatefromstring($imageBytes);
        if ($imageResource !== false) {
            imagedestroy($imageResource);
        }
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        $error = 'Profile upload folder is not available.';
        return false;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $mimeMap[$mime];
    $relativePath = 'uploads/profiles/' . $filename;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($targetPath, $imageBytes) === false) {
        $error = 'Could not save profile image.';
        return false;
    }

    $profile = getUserProfile($userId);
    $oldImage = $profile['profile_image'] ?? null;

    $stmt = $conn->prepare("UPDATE user SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $relativePath, $userId);
    if (!$stmt->execute()) {
        unlink($targetPath);
        $error = 'Could not update your profile image.';
        return false;
    }

    deleteStoredProfileImage($oldImage);
    logAuditAction('profile_image_update', 'user', $userId);
    return true;
}

function removeProfileImage($userId, &$error) {
    global $conn;

    $error = '';
    ensureProfileImageColumn();
    $profile = getUserProfile($userId);
    if (!$profile) {
        $error = 'Profile not found.';
        return false;
    }

    $oldImage = $profile['profile_image'] ?? null;
    $stmt = $conn->prepare("UPDATE user SET profile_image = NULL, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        $error = 'Could not remove profile image.';
        return false;
    }

    deleteStoredProfileImage($oldImage);
    logAuditAction('profile_image_remove', 'user', $userId);
    return true;
}

// Helper function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function currentUserId() {
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    if (isset($_SESSION['admin_id'])) {
        return (int) $_SESSION['admin_id'];
    }

    return null;
}

function normalizeRole($role) {
    return $role === 'admin' ? 'system_admin' : $role;
}

function adminRoleValues() {
    return [
        'president',
        'vice_president',
        'secretary',
        'male_treasurer',
        'female_treasurer',
        'system_admin',
    ];
}

function executiveRoleValues() {
    return [
        'president',
        'vice_president',
        'secretary',
        'male_treasurer',
        'female_treasurer',
    ];
}

function isAdminRole($role) {
    return in_array(normalizeRole($role), adminRoleValues(), true);
}

function isExecutiveRole($role) {
    return in_array(normalizeRole($role), executiveRoleValues(), true);
}

function isSystemAdminRole($role) {
    return normalizeRole($role) === 'system_admin';
}

function roleLabel($role) {
    $labels = [
        'member' => 'Member',
        'president' => 'President',
        'vice_president' => 'Vice President',
        'secretary' => 'Secretary',
        'male_treasurer' => 'Male Treasurer',
        'female_treasurer' => 'Female Treasurer',
        'system_admin' => 'System Administrator',
        'admin' => 'System Administrator',
    ];

    $normalized = normalizeRole($role);
    return $labels[$normalized] ?? 'Unknown';
}

function currentUserRole() {
    global $conn;

    $userId = currentUserId();
    if (!$userId) {
        return null;
    }

    $stmt = $conn->prepare("SELECT role FROM user WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $role = normalizeRole($user['role']);
        $_SESSION['user_role'] = $role;
        return $role;
    }

    unset($_SESSION['user_role']);
    return null;
}

function requireLogin($redirect = 'login.php') {
    if (!currentUserId()) {
        header('Location: ' . $redirect);
        exit();
    }

    enforceSessionTimeout();

    $role = currentUserRole();
    if (!$role) {
        session_unset();
        session_destroy();
        header('Location: ' . $redirect);
        exit();
    }
}

function requireMember($redirect = 'login.php') {
    requireLogin($redirect);
    $role = currentUserRole();

    if ($role !== 'member' && !isAdminRole($role)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function requireAdminRole($redirect = 'login.php') {
    requireLogin($redirect);

    if (!isAdminRole(currentUserRole())) {
        http_response_code(403);
        exit('Admin access required.');
    }
}

function requireAdmin($redirect = '../admin/login.php') {
    requireAdminRole($redirect);
}

function requireSystemAdmin($redirect = 'login.php') {
    requireLogin($redirect);

    if (!isSystemAdminRole(currentUserRole())) {
        http_response_code(403);
        exit('System administrator access required.');
    }
}

function canManageContent() {
    return isAdminRole(currentUserRole());
}

function canManagePoints() {
    return isAdminRole(currentUserRole());
}

function canViewMembers() {
    return isAdminRole(currentUserRole());
}

function canViewContactMessages() {
    return isAdminRole(currentUserRole());
}

function canExportReports() {
    return isAdminRole(currentUserRole());
}

function canAccessDatabaseBackup() {
    return isSystemAdminRole(currentUserRole());
}

function canManageSettings() {
    return isSystemAdminRole(currentUserRole());
}

function canManageAdminRoles() {
    return isSystemAdminRole(currentUserRole());
}

function activeSystemAdminCount($excludeUserId = null) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM user WHERE role IN ('system_admin', 'admin') AND status = 'active'";
    if ($excludeUserId !== null) {
        $stmt = $conn->prepare($sql . " AND id <> ?");
        $excludeUserId = (int) $excludeUserId;
        $stmt->bind_param("i", $excludeUserId);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    return (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}

// Helper function to get event by ID
function getEventById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.id = ? AND p.category = 'community_engagement'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Helper function to get all events
function getAllEvents() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.category = 'community_engagement' 
                            ORDER BY p.upload_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Helper function to get images for an event
function getEventImages($post_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM image WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllNewsAndEvents() {
    global $conn;
    $categories = ['news', 'announcement', 'workshop', 'volunteer', 'community_engagement'];
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $types = str_repeat('s', count($categories));

    $stmt = $conn->prepare("
        SELECT p.*, u.name as author_name
        FROM post p
        LEFT JOIN user u ON p.uploaded_by = u.id
        WHERE p.category IN ($placeholders)
        ORDER BY p.upload_date DESC
    ");
    $stmt->bind_param($types, ...$categories);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllAchievements() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.category = 'achievement' 
                            ORDER BY p.upload_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get achievement by ID
function getAchievementById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.id = ? AND p.category = 'achievement'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get all testimonials (with duplicate prevention)
function getAllTestimonials() {
    global $conn;
    // Use DISTINCT to prevent duplicates based on title and content
    $stmt = $conn->prepare("SELECT DISTINCT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.category = 'testimonial' 
                            GROUP BY p.title, p.content
                            ORDER BY p.upload_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get testimonial by ID
function getTestimonialById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p 
                            LEFT JOIN user u ON p.uploaded_by = u.id 
                            WHERE p.id = ? AND p.category = 'testimonial'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get star rating HTML
function getStarRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    return $stars;
}

function getAllPointCategories() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM point_category WHERE status = 'active' ORDER BY points DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserPoints($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT total_points FROM user_points WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? (int) $result['total_points'] : 0;
}

function getUserPointRequests($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT pr.*, pc.category_name, pc.points
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        WHERE pr.user_id = ?
        ORDER BY pr.request_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserPointRequestsPaginated($userId, $status = 'all', $sort = 'newest', $page = 1, $perPage = 10) {
    global $conn;

    $allowedStatuses = ['all', 'pending', 'approved', 'rejected'];
    $status = in_array($status, $allowedStatuses, true) ? $status : 'all';
    $sortMap = [
        'newest' => 'pr.request_date DESC, pr.id DESC',
        'oldest' => 'pr.request_date ASC, pr.id ASC',
        'points_desc' => 'pc.points DESC, pr.request_date DESC',
        'points_asc' => 'pc.points ASC, pr.request_date DESC',
    ];
    $orderBy = $sortMap[$sort] ?? $sortMap['newest'];
    $perPage = in_array((int) $perPage, [10, 25, 50], true) ? (int) $perPage : 10;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;

    $where = 'WHERE pr.user_id = ?';
    $types = 'i';
    $params = [(int) $userId];
    if ($status !== 'all') {
        $where .= ' AND pr.status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM point_request pr JOIN point_category pc ON pr.point_category_id = pc.id $where");
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $totalCount = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalPages = max(1, (int) ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $stmt = $conn->prepare("
        SELECT pr.*, pc.category_name, pc.points
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        $where
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");
    $queryTypes = $types . 'ii';
    $queryParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($queryTypes, ...$queryParams);
    $stmt->execute();

    return [
        'requests' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'total_count' => $totalCount,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
    ];
}

function getPostsPaginated(array $categories, $page = 1, $perPage = 9) {
    global $conn;

    $allowedPerPage = [9, 18, 27];
    $perPage = in_array((int) $perPage, $allowedPerPage, true) ? (int) $perPage : 9;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;
    $categories = array_values(array_filter($categories, fn($category) => is_string($category) && $category !== ''));

    if (empty($categories)) {
        return ['posts' => [], 'total_count' => 0, 'current_page' => 1, 'total_pages' => 1, 'per_page' => $perPage];
    }

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $types = str_repeat('s', count($categories));

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post WHERE category IN ($placeholders)");
    $countStmt->bind_param($types, ...$categories);
    $countStmt->execute();
    $totalCount = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalPages = max(1, (int) ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $stmt = $conn->prepare("
        SELECT p.*, u.name as author_name
        FROM post p
        LEFT JOIN user u ON p.uploaded_by = u.id
        WHERE p.category IN ($placeholders)
        ORDER BY p.upload_date DESC, p.id DESC
        LIMIT ? OFFSET ?
    ");
    $queryTypes = $types . 'ii';
    $queryParams = array_merge($categories, [$perPage, $offset]);
    $stmt->bind_param($queryTypes, ...$queryParams);
    $stmt->execute();

    return [
        'posts' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'total_count' => $totalCount,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
    ];
}

function getAllPointRequests() {
    global $conn;
    ensureProfileImageColumn();

    $stmt = $conn->prepare("
        SELECT pr.*, pc.category_name, pc.points, u.name as user_name, u.email as user_email, u.role as user_role, u.profile_image as user_profile_image,
               r.name as reviewer_name
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        JOIN user u ON pr.user_id = u.id
        LEFT JOIN user r ON pr.reviewed_by = r.id
        ORDER BY
            CASE pr.status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rejected' THEN 3
                ELSE 4
            END,
            pr.request_date DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPointRequestsPaginated($status = 'all', $sort = 'newest', $page = 1, $perPage = 10) {
    global $conn;
    ensureProfileImageColumn();

    $allowedStatuses = ['all', 'pending', 'approved', 'rejected'];
    $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

    $sortMap = [
        'newest' => 'pr.request_date DESC, pr.id DESC',
        'oldest' => 'pr.request_date ASC, pr.id ASC',
        'points_desc' => 'pc.points DESC, pr.request_date DESC',
        'points_asc' => 'pc.points ASC, pr.request_date DESC',
        'status' => "CASE pr.status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END, pr.request_date DESC",
    ];
    $orderBy = $sortMap[$sort] ?? $sortMap['newest'];

    $perPageOptions = [10, 25, 50];
    $perPage = in_array((int) $perPage, $perPageOptions, true) ? (int) $perPage : 10;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;

    $where = '';
    $types = '';
    $params = [];
    if ($status !== 'all') {
        $where = 'WHERE pr.status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $countSql = "
        SELECT COUNT(*) AS total
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        JOIN user u ON pr.user_id = u.id
        $where
    ";
    $countStmt = $conn->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalCount = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalPages = max(1, (int) ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT pr.*, pc.category_name, pc.points, u.name as user_name, u.email as user_email, u.role as user_role, u.profile_image as user_profile_image,
               r.name as reviewer_name
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        JOIN user u ON pr.user_id = u.id
        LEFT JOIN user r ON pr.reviewed_by = r.id
        $where
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $queryTypes = $types . 'ii';
    $queryParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($queryTypes, ...$queryParams);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'requests' => $requests,
        'total_count' => $totalCount,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
    ];
}

function getPointRequestById($requestId) {
    global $conn;
    ensureProfileImageColumn();

    $stmt = $conn->prepare("
        SELECT pr.*, pc.category_name, pc.points, u.name as user_name, u.email as user_email, u.role as user_role, u.profile_image as user_profile_image
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        JOIN user u ON pr.user_id = u.id
        WHERE pr.id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createPointRequest($userId, $pointCategoryId, $description, $filePath) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO point_request (user_id, point_category_id, eop_evidence, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $userId, $pointCategoryId, $filePath, $description);
    return $stmt->execute();
}

function updatePointRequestStatus($requestId, $newStatus, $adminId, $remarks = null) {
    global $conn;

    if (!in_array($newStatus, ['pending', 'approved', 'rejected'], true)) {
        return false;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT pr.*, pc.points, pc.category_name
            FROM point_request pr
            JOIN point_category pc ON pr.point_category_id = pc.id
            WHERE pr.id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();

        if (!$request) {
            throw new Exception("Point request not found");
        }

        $previousStatus = $request['status'];
        $requestUserId = (int) $request['user_id'];
        $requestPoints = (int) $request['points'];

        $stmt = $conn->prepare("
            UPDATE point_request
            SET status = ?, review_date = NOW(), reviewed_by = ?, admin_remarks = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $newStatus, $adminId, $remarks, $requestId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update point request");
        }

        if ($newStatus === 'approved') {
            $description = 'Approved point request: ' . $request['category_name'];
            $transactionType = 'award';

            $stmt = $conn->prepare("
                INSERT IGNORE INTO point_transactions
                    (user_id, point_request_id, points, transaction_type, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiissi",
                $requestUserId,
                $requestId,
                $requestPoints,
                $transactionType,
                $description,
                $adminId
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to create point transaction");
            }

            if ($stmt->affected_rows === 1) {
                $stmt = $conn->prepare("
                    INSERT INTO user_points (user_id, total_points)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE total_points = total_points + ?
                ");
                $stmt->bind_param("iii", $requestUserId, $requestPoints, $requestPoints);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update user points");
                }
            }
        } elseif ($previousStatus === 'approved') {
            $txnStmt = $conn->prepare("SELECT points FROM point_transactions WHERE point_request_id = ? FOR UPDATE");
            $txnStmt->bind_param("i", $requestId);
            $txnStmt->execute();
            $txnRows = $txnStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $pointsToRemove = 0;
            foreach ($txnRows as $txnRow) {
                $pointsToRemove += (int) $txnRow['points'];
            }

            if ($pointsToRemove > 0) {
                $userPointsStmt = $conn->prepare("SELECT total_points FROM user_points WHERE user_id = ? FOR UPDATE");
                $userPointsStmt->bind_param("i", $requestUserId);
                $userPointsStmt->execute();
                $currentPoints = (int) ($userPointsStmt->get_result()->fetch_assoc()['total_points'] ?? 0);
                $newTotal = max(0, $currentPoints - $pointsToRemove);

                $updatePoints = $conn->prepare("UPDATE user_points SET total_points = ? WHERE user_id = ?");
                $updatePoints->bind_param("ii", $newTotal, $requestUserId);
                if (!$updatePoints->execute()) {
                    throw new Exception("Failed to reverse user points");
                }

                $deleteTxn = $conn->prepare("DELETE FROM point_transactions WHERE point_request_id = ?");
                $deleteTxn->bind_param("i", $requestId);
                if (!$deleteTxn->execute()) {
                    throw new Exception("Failed to remove point transaction");
                }
            }
        }

        $conn->commit();
        logAuditAction('point_request_' . $newStatus, 'point_request', $requestId, ['status' => $previousStatus], ['status' => $newStatus]);
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function deletePointRequestIfAllowed($requestId, $adminId, &$message = '') {
    global $conn;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT * FROM point_request WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();

        if (!$request) {
            $message = 'Point request was not found.';
            throw new Exception('missing_request');
        }

        $pointsToRemove = 0;
        $txnStmt = $conn->prepare("SELECT points FROM point_transactions WHERE point_request_id = ? FOR UPDATE");
        $txnStmt->bind_param("i", $requestId);
        $txnStmt->execute();
        $txnRows = $txnStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $pointsToRemove = 0;
        foreach ($txnRows as $txnRow) {
            $pointsToRemove += (int) $txnRow['points'];
        }

        if ($pointsToRemove > 0) {
            $userPointsStmt = $conn->prepare("SELECT total_points FROM user_points WHERE user_id = ? FOR UPDATE");
            $requestUserId = (int) $request['user_id'];
            $userPointsStmt->bind_param("i", $requestUserId);
            $userPointsStmt->execute();
            $currentPoints = (int) ($userPointsStmt->get_result()->fetch_assoc()['total_points'] ?? 0);
            $newPoints = max(0, $currentPoints - $pointsToRemove);

            $updatePoints = $conn->prepare("UPDATE user_points SET total_points = ? WHERE user_id = ?");
            $updatePoints->bind_param("ii", $newPoints, $requestUserId);
            if (!$updatePoints->execute()) {
                $message = 'Failed to update member points.';
                throw new Exception('points_update_failed');
            }

            $deleteTransactions = $conn->prepare("DELETE FROM point_transactions WHERE point_request_id = ?");
            $deleteTransactions->bind_param("i", $requestId);
            if (!$deleteTransactions->execute()) {
                $message = 'Failed to delete point transactions.';
                throw new Exception('transaction_delete_failed');
            }
        }

        $delete = $conn->prepare("DELETE FROM point_request WHERE id = ?");
        $delete->bind_param("i", $requestId);
        if (!$delete->execute() || $delete->affected_rows !== 1) {
            $message = 'Failed to delete point request.';
            throw new Exception('delete_failed');
        }

        $relativePath = $request['eop_evidence'] ?? '';
        $baseDir = realpath(__DIR__ . '/../point/uploads/eop');
        $fileName = basename($relativePath);
        $filePath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . $fileName) : false;
        if ($baseDir && $filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
            unlink($filePath);
        }

        logAuditAction(
            'point_request_delete',
            'point_request',
            $requestId,
            ['status' => $request['status'], 'points_removed' => $pointsToRemove, 'user_id' => $request['user_id']],
            null
        );
        $conn->commit();
        $message = 'Point request deleted successfully.';
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        if ($message === '') {
            $message = 'Point request could not be deleted.';
        }
        return false;
    }
}

function getPointStatistics() {
    global $conn;

    $stats = [
        'total_points_awarded' => 0,
        'total_requests' => 0,
        'pending_requests' => 0,
        'top_user' => null,
    ];

    $result = $conn->query("SELECT COALESCE(SUM(points), 0) as total FROM point_transactions");
    if ($result) {
        $stats['total_points_awarded'] = (int) $result->fetch_assoc()['total'];
    }

    $result = $conn->query("SELECT COUNT(*) as total FROM point_request");
    if ($result) {
        $stats['total_requests'] = (int) $result->fetch_assoc()['total'];
    }

    $result = $conn->query("SELECT COUNT(*) as total FROM point_request WHERE status = 'pending'");
    if ($result) {
        $stats['pending_requests'] = (int) $result->fetch_assoc()['total'];
    }

    $result = $conn->query("
        SELECT u.name, up.total_points
        FROM user_points up
        JOIN user u ON up.user_id = u.id
        ORDER BY up.total_points DESC, u.name ASC
        LIMIT 1
    ");
    if ($result) {
        $stats['top_user'] = $result->fetch_assoc();
    }

    return $stats;
}

function getLeaderboard($limit = 25) {
    global $conn;
    ensureProfileImageColumn();

    $allowedLimits = [10, 25, 0];
    $limit = in_array((int) $limit, $allowedLimits, true) ? (int) $limit : 25;
    $limitClause = $limit > 0 ? "LIMIT ?" : "";

    $sql = "
        SELECT
            u.id,
            u.name,
            u.email,
            u.profile_image,
            COALESCE(up.total_points, 0) AS total_points,
            COUNT(DISTINCT CASE WHEN pr.status = 'approved' THEN pr.id END) AS approved_request_count,
            MAX(CASE WHEN pr.status = 'approved' THEN pr.review_date END) AS latest_approved_activity_date
        FROM user u
        LEFT JOIN user_points up ON up.user_id = u.id
        LEFT JOIN point_request pr ON pr.user_id = u.id
        WHERE u.role = 'member'
          AND u.status = 'active'
        GROUP BY u.id, u.name, u.email, u.profile_image, up.total_points
        HAVING total_points > 0 OR approved_request_count > 0
        ORDER BY total_points DESC, approved_request_count DESC, latest_approved_activity_date DESC
        $limitClause
    ";

    $stmt = $conn->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param("i", $limit);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $rank = 1;
    foreach ($rows as &$row) {
        $row['rank'] = $rank++;
        $row['total_points'] = (int) $row['total_points'];
        $row['approved_request_count'] = (int) $row['approved_request_count'];
    }
    unset($row);

    return $rows;
}

function getUserRank($userId) {
    $rows = getLeaderboard(0);

    foreach ($rows as $row) {
        if ((int) $row['id'] === (int) $userId) {
            return $row;
        }
    }

    return null;
}
?>
