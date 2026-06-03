<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category = 'community_engagement';
    $uploaded_by = $_SESSION['admin_id'];
    
    // Insert post
    $stmt = $conn->prepare("INSERT INTO post (content, category, title, uploaded_by, upload_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $content, $category, $title, $uploaded_by);
    $stmt->execute();
    $post_id = $conn->insert_id;
    
    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . '_' . $_FILES['images']['name'][$key];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($tmp_name, $file_path)) {
                $imgStmt = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
                $imgStmt->bind_param("is", $post_id, $file_name);
                $imgStmt->execute();
            }
        }
    }
    
    header('Location: dashboard.php?msg=added');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add New Event - AMSA Admin</title>
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
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #2c0410, #4a1a2a);
            min-height: 100vh;
            position: fixed;
            width: 280px;
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
            font-weight: 700;
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
        }
        
        .sidebar-menu a:hover {
            background: rgba(139,58,58,0.5);
            color: white;
            padding-left: 35px;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 25px;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-container h2 {
            color: #2c0410;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c0410;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #8B3A3A;
            box-shadow: 0 0 0 0.2rem rgba(139,58,58,0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,58,58,0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo.png" alt="AMSA Logo">
            <h4>AMSA Admin</h4>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="add_event.php" class="active" style="background: rgba(139,58,58,0.5);">
                <i class="fas fa-plus-circle"></i> Add New Event
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <div class="mb-4">
                <h2><i class="fas fa-plus-circle me-2" style="color: #8B3A3A;"></i> Add New Community Event</h2>
                <p class="text-muted">Create a new community engagement event to share with your audience</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Event Title *</label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           placeholder="Enter event title">
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Event Description *</label>
                    <textarea class="form-control" id="content" name="content" rows="8" required
                              placeholder="Describe the event in detail..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Event Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple 
                           accept="image/*" onchange="previewImages(this)">
                    <small class="text-muted">You can select multiple images (JPG, PNG, GIF)</small>
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save me-2"></i> Publish Event
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        div.appendChild(img);
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>