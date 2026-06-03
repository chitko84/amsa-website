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

// Helper function to get all news posts
function getAllNews() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p
                            LEFT JOIN user u ON p.uploaded_by = u.id
                            WHERE p.category IN ('news', 'announcement', 'workshop', 'volunteer')
                            ORDER BY p.upload_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Helper function to get news and event posts for the public Events & News page
function getAllNewsAndEvents() {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name FROM post p
                            LEFT JOIN user u ON p.uploaded_by = u.id
                            WHERE p.category IN ('news', 'announcement', 'workshop', 'volunteer', 'community_engagement')
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
?>
