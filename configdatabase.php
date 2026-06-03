<?php
$host = 'localhost';
$dbname = 'amsa_website';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to get all point categories
function getAllPointCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM point_category WHERE status = 'active' ORDER BY points DESC");
    return $stmt->fetchAll();
}

// Function to get user points
function getUserPoints($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT total_points FROM user_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['total_points'] : 0;
}

// Function to get user point requests
function getUserPointRequests($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT pr.*, pc.category_name, pc.points 
        FROM point_request pr
        JOIN point_category pc ON pr.point_category_id = pc.id
        WHERE pr.user_id = ?
        ORDER BY pr.request_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Function to get all point requests (admin)
function getAllPointRequests() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT pr.*, pc.category_name, pc.points, u.name as user_name, u.email as user_email,
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
            END,
            pr.request_date DESC
    ");
    return $stmt->fetchAll();
}

// Function to create point request
function createPointRequest($userId, $pointCategoryId, $description, $filePath) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO point_request (user_id, point_category_id, eop_evidence, description)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $pointCategoryId, $filePath, $description]);
}

// Function to update point request status
function updatePointRequestStatus($requestId, $status, $adminId, $remarks = null) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM point_request WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception("Request not found");
        }
        
        // Update the request
        $stmt = $pdo->prepare("
            UPDATE point_request 
            SET status = ?, review_date = NOW(), reviewed_by = ?, admin_remarks = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $adminId, $remarks, $requestId]);
        
        // If approved, add points to user
        if ($status == 'approved') {
            // Get category points
            $stmt = $pdo->prepare("SELECT points FROM point_category WHERE id = ?");
            $stmt->execute([$request['point_category_id']]);
            $category = $stmt->fetch();
            
            // Update user points
            $stmt = $pdo->prepare("
                INSERT INTO user_points (user_id, total_points) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE total_points = total_points + ?
            ");
            $stmt->execute([$request['user_id'], $category['points'], $category['points']]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Function to get point statistics
function getPointStatistics() {
    global $pdo;
    
    $stats = [];
    
    // Total points awarded
    $stmt = $pdo->query("SELECT SUM(points) as total FROM point_category pc 
                         JOIN point_request pr ON pc.id = pr.point_category_id 
                         WHERE pr.status = 'approved'");
    $stats['total_points_awarded'] = $stmt->fetch()['total'] ?? 0;
    
    // Total requests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM point_request");
    $stats['total_requests'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending requests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM point_request WHERE status = 'pending'");
    $stats['pending_requests'] = $stmt->fetch()['total'] ?? 0;
    
    // Top user
    $stmt = $pdo->query("
        SELECT u.name, up.total_points 
        FROM user_points up 
        JOIN user u ON up.user_id = u.id 
        ORDER BY up.total_points DESC 
        LIMIT 1
    ");
    $stats['top_user'] = $stmt->fetch();
    
    return $stats;
}

// Function to get all achievements (posts with category 'achievement')
function getAllAchievements() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT * FROM post 
        WHERE category = 'achievement' 
        ORDER BY upload_date DESC
    ");
    return $stmt->fetchAll();
}

// Function to get all testimonials
function getAllTestimonials() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT * FROM post 
        WHERE category = 'testimonial' 
        ORDER BY upload_date DESC
    ");
    return $stmt->fetchAll();
}

// Function to get images for a specific post
function getEventImages($postId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM image WHERE post_id = ?");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}
?>