<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'amsa_website');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
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

// ============ POINT SYSTEM FUNCTIONS ============

// Function to get all point categories
function getAllPointCategories() {
    global $conn;
    $result = $conn->query("SELECT * FROM point_category WHERE status = 'active' ORDER BY points DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get user points
function getUserPoints($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT total_points FROM user_points WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['total_points'] : 0;
}

// Function to get user point requests
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

// Function to get all point requests (admin)
function getAllPointRequests() {
    global $conn;
    $result = $conn->query("
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
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to create point request
function createPointRequest($userId, $pointCategoryId, $description, $filePath) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO point_request (user_id, point_category_id, eop_evidence, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $userId, $pointCategoryId, $filePath, $description);
    return $stmt->execute();
}

// Function to update point request status
function updatePointRequestStatus($requestId, $status, $adminId, $remarks = null) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Get request details
        $stmt = $conn->prepare("SELECT * FROM point_request WHERE id = ?");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            throw new Exception("Request not found");
        }
        
        // Update the request
        $stmt = $conn->prepare("
            UPDATE point_request 
            SET status = ?, review_date = NOW(), reviewed_by = ?, admin_remarks = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $adminId, $remarks, $requestId);
        $stmt->execute();
        
        // If approved, add points to user
        if ($status == 'approved') {
            // Get category points
            $stmt = $conn->prepare("SELECT points FROM point_category WHERE id = ?");
            $stmt->bind_param("i", $request['point_category_id']);
            $stmt->execute();
            $category = $stmt->get_result()->fetch_assoc();
            
            // Update user points
            $stmt = $conn->prepare("
                INSERT INTO user_points (user_id, total_points) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE total_points = total_points + ?
            ");
            $points = $category['points'];
            $stmt->bind_param("iii", $request['user_id'], $points, $points);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Function to get point statistics
function getPointStatistics() {
    global $conn;
    
    $stats = [];
    
    // Total points awarded
    $result = $conn->query("SELECT SUM(points) as total FROM point_category pc 
                         JOIN point_request pr ON pc.id = pr.point_category_id 
                         WHERE pr.status = 'approved'");
    $row = $result->fetch_assoc();
    $stats['total_points_awarded'] = $row['total'] ?? 0;
    
    // Total requests
    $result = $conn->query("SELECT COUNT(*) as total FROM point_request");
    $row = $result->fetch_assoc();
    $stats['total_requests'] = $row['total'] ?? 0;
    
    // Pending requests
    $result = $conn->query("SELECT COUNT(*) as total FROM point_request WHERE status = 'pending'");
    $row = $result->fetch_assoc();
    $stats['pending_requests'] = $row['total'] ?? 0;
    
    // Top user
    $result = $conn->query("
        SELECT u.name, up.total_points 
        FROM user_points up 
        JOIN user u ON up.user_id = u.id 
        ORDER BY up.total_points DESC 
        LIMIT 1
    ");
    $stats['top_user'] = $result->fetch_assoc();
    
    return $stats;
}
?>