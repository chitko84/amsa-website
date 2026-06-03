<?php
session_start();
require_once '../config/database.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle event deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First delete images
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
    $stmt = $conn->prepare("DELETE FROM post WHERE id = ? AND category = 'community_engagement'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    header('Location: dashboard.php?msg=deleted');
    exit();
}

$events = getAllEvents();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - AMSA</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="../img/logo.png" rel="icon">
    
    <!-- Google Web Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --secondary: #8B3A3A;
            --secondary-light: #B55A4A;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: #f8f9fa;
        }


/* Dropdown Styles */
.sidebar-menu .dropdown-menu-wrapper {
    position: relative;
}

.sidebar-menu .dropdown-toggle-btn {
    display: block;
    padding: 12px 25px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 500;
    cursor: pointer;
}

.sidebar-menu .dropdown-toggle-btn:hover {
    background: rgba(139,58,58,0.5);
    color: white;
    padding-left: 35px;
}

.sidebar-menu .dropdown-toggle-btn i:first-child {
    margin-right: 10px;
    width: 25px;
}

.dropdown-arrow {
    float: right;
    margin-top: 5px;
    transition: transform 0.3s;
}

.sidebar-menu .dropdown-items {
    display: none;
    background: rgba(0,0,0,0.3);
    width: 100%;
}

.sidebar-menu .dropdown-items a {
    display: block;
    padding: 10px 25px 10px 55px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.sidebar-menu .dropdown-items a:hover {
    background: rgba(139,58,58,0.5);
    color: white;
    padding-left: 65px;
}

.sidebar-menu .dropdown-items a i {
    margin-right: 10px;
    width: 20px;
}

.sidebar-menu .dropdown-menu-wrapper.active .dropdown-items {
    display: block;
}

.sidebar-menu .dropdown-menu-wrapper.active .dropdown-arrow {
    transform: rotate(180deg);
}


        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, #2c0410, #4a1a2a);
            min-height: 100vh;
            position: fixed;
            width: 280px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header img {
            width: 80px;
            margin-bottom: 15px;
        }
        
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 700;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin: 5px 0 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background: rgba(139,58,58,0.5);
            color: white;
            padding-left: 35px;
        }
        
        .sidebar-menu a.active {
            background: #8B3A3A;
            color: white;
            border-left: 4px solid #c6b511;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 25px;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-text h3 {
            margin: 0;
            font-weight: 700;
            color: #2c0410;
        }
        
        .welcome-text p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .admin-info {
            text-align: right;
        }
        
        .admin-name {
            font-weight: 700;
            color: #8B3A3A;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stat-icon i {
            font-size: 30px;
            color: white;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #2c0410;
            margin: 10px 0 5px;
        }
        
        .stat-label {
            color: #666;
            font-weight: 500;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-container h4 {
            margin-bottom: 20px;
            font-weight: 700;
            color: #2c0410;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            color: #2c0410;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge-category {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
            border: none;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,58,58,0.3);
        }
        
        .event-image {
            width: 60px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -280px;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <!-- <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo.png" alt="AMSA Logo">
            <h4>AMSA Admin</h4>
            <p>Community Engagement Manager</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="add_event.php">
                <i class="fas fa-plus-circle"></i> Add New Event
            </a>
            <a href="#">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="#">
                <i class="fas fa-users"></i> Volunteers
            </a>
            <a href="../point/admin_points.php">
                <i class="fas fa-users"></i> PointManagement
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div> -->
    <div class="sidebar">
    <div class="sidebar-header">
        <img src="../img/logo.png" alt="AMSA Logo">
        <h4>AMSA Admin</h4>
        <p>Community Engagement Manager</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="add_event.php">
            <i class="fas fa-plus-circle"></i> Add New Event
        </a>
        <a href="#">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="#">
            <i class="fas fa-users"></i> Volunteers
        </a>
        
        <div class="dropdown-menu-wrapper">
            <div class="dropdown-toggle-btn">
                <i class="fas fa-star"></i> Point Management
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>
            <div class="dropdown-items">
                <a href="../point/point_categories_admin.php">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="../point/admin_points.php">
                    <i class="fas fa-clipboard-list"></i> Manage Requests
                </a>
                <a href="../point/point_request.php">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </a>
                <a href="../point/my_points.php">
                    <i class="fas fa-chart-line"></i> My Points
                </a>
            </div>
        </div>
        
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="welcome-text">
                        <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h3>
                        <p>Here's what's happening with your community events today.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-info">
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($_SESSION['admin_email']); ?>
                        </p>
                        <p class="mb-0 mt-1">
                            <i class="fas fa-clock me-2"></i> Last login: <?php echo date('F d, Y h:i A'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo count($events); ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">1,234</div>
                    <div class="stat-label">Volunteers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number">5,678</div>
                    <div class="stat-label">Beneficiaries</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="stat-number"><?php 
                        $imgCount = $conn->query("SELECT COUNT(*) as count FROM image")->fetch_assoc()['count'];
                        echo $imgCount;
                    ?></div>
                    <div class="stat-label">Photos Uploaded</div>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
                if ($_GET['msg'] == 'added') echo 'Event added successfully!';
                if ($_GET['msg'] == 'updated') echo 'Event updated successfully!';
                if ($_GET['msg'] == 'deleted') echo 'Event deleted successfully!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Events Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-list me-2"></i> Community Engagement Events</h4>
                <a href="add_event.php" class="btn btn-add">
                    <i class="fas fa-plus me-2"></i> Add New Event
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date Posted</th>
                            <th>Author</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No events found. Click "Add New Event" to get started!</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td>#<?php echo $event['id']; ?></td>
                                <td>
                                    <?php 
                                    $images = getEventImages($event['id']);
                                    $firstImage = !empty($images) ? '../uploads/' . $images[0]['img_name'] : 'https://picsum.photos/60/50';
                                    ?>
                                    <img src="<?php echo $firstImage; ?>" alt="Event" class="event-image">
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><span class="badge-category"><?php echo ucfirst(str_replace('_', ' ', $event['category'])); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($event['upload_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['author_name'] ?? 'Admin'); ?></td>
                                <td>
                                    <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $event['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone!')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add this after your existing events table -->
<div class="table-container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-trophy me-2"></i> Achievements</h4>
        <a href="add_content.php?type=achievement" class="btn btn-add">
            <i class="fas fa-plus me-2"></i> Add Achievement
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $achievements = getAllAchievements();
                foreach ($achievements as $achievement): 
                ?>
                <tr>
                    <td>#<?php echo $achievement['id']; ?></td>
                    <td><?php echo htmlspecialchars($achievement['title']); ?></td>
                    <td><?php echo substr(htmlspecialchars($achievement['content']), 0, 50); ?>...</td>
                    <td><?php echo date('M d, Y', strtotime($achievement['upload_date'])); ?></td>
                    <td>
                        <a href="edit_content.php?id=<?php echo $achievement['id']; ?>&type=achievement" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_content.php?id=<?php echo $achievement['id']; ?>&type=achievement" class="btn-delete" onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Testimonials Table -->
<div class="table-container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-comments me-2"></i> Testimonials</h4>
        <a href="add_content.php?type=testimonial" class="btn btn-add">
            <i class="fas fa-plus me-2"></i> Add Testimonial
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Testimonial</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $testimonials = getAllTestimonials();
                foreach ($testimonials as $testimonial): 
                ?>
                <tr>
                    <td>#<?php echo $testimonial['id']; ?></td>
                    <td><?php echo htmlspecialchars($testimonial['title']); ?></td>
                    <td><?php echo substr(htmlspecialchars($testimonial['content']), 0, 50); ?>...</td>
                    <td><?php echo date('M d, Y', strtotime($testimonial['upload_date'])); ?></td>
                    <td>
                        <a href="edit_content.php?id=<?php echo $testimonial['id']; ?>&type=testimonial" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_content.php?id=<?php echo $testimonial['id']; ?>&type=testimonial" class="btn-delete" onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Add this button at the top of the testimonials section -->
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i> 
    Total Testimonials: <?php echo count($testimonials); ?> 
    <button class="btn btn-sm btn-warning ms-3" onclick="checkDuplicates()">
        <i class="fas fa-search"></i> Check for Duplicates
    </button>
</div>

<script>
function checkDuplicates() {
    let titles = [];
    document.querySelectorAll('.testimonial-title').forEach(el => {
        let title = el.innerText;
        if (titles.includes(title)) {
            el.closest('tr').style.backgroundColor = '#fff3cd';
        }
        titles.push(title);
    });
    alert('Duplicate check complete! Yellow rows indicate possible duplicates.');
}

// Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    var dropdownToggle = document.querySelector('.dropdown-toggle-btn');
    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function() {
            var wrapper = this.parentElement;
            wrapper.classList.toggle('active');
        });
    }
});

</script>

<!-- Update the testimonial title cell to have class testimonial-title -->
<td class="testimonial-title"><?php echo htmlspecialchars($testimonial['title']); ?></td>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>