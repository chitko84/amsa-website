<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
$event = getEventById($id);
$images = getEventImages($id);

if (!$event) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $edited_by = $_SESSION['admin_id'];
    
    // Update post
    $stmt = $conn->prepare("UPDATE post SET title = ?, content = ?, edit_date = NOW(), edited_by = ? WHERE id = ?");
    $stmt->bind_param("ssii", $title, $content, $edited_by, $id);
    $stmt->execute();
    
    // Handle new image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/';
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . '_' . $_FILES['images']['name'][$key];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($tmp_name, $file_path)) {
                $imgStmt = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
                $imgStmt->bind_param("is", $id, $file_name);
                $imgStmt->execute();
            }
        }
    }
    
    // Delete specific images
    if (isset($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $img_id) {
            $imgStmt = $conn->prepare("SELECT img_name FROM image WHERE id = ?");
            $imgStmt->bind_param("i", $img_id);
            $imgStmt->execute();
            $img = $imgStmt->get_result()->fetch_assoc();
            
            if ($img) {
                $filepath = '../uploads/' . $img['img_name'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            
            $delStmt = $conn->prepare("DELETE FROM image WHERE id = ?");
            $delStmt->bind_param("i", $img_id);
            $delStmt->execute();
        }
    }
    
    header('Location: dashboard.php?msg=updated');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Event - AMSA Admin</title>
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
        
        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .image-card {
            position: relative;
            width: 150px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }
        
        .image-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .image-card input {
            margin-top: 5px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
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
            <a href="add_event.php">
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
            <h2 class="mb-4"><i class="fas fa-edit me-2" style="color: #8B3A3A;"></i> Edit Event: <?php echo htmlspecialchars($event['title']); ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Event Title</label>
                    <input type="text" class="form-control" name="title" 
                           value="<?php echo htmlspecialchars($event['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Event Description</label>
                    <textarea class="form-control" name="content" rows="8" required><?php 
                        echo htmlspecialchars($event['content']); 
                    ?></textarea>
                </div>
                
                <?php if (!empty($images)): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Current Images</label>
                    <div class="current-images">
                        <?php foreach ($images as $img): ?>
                        <div class="image-card">
                            <img src="../uploads/<?php echo $img['img_name']; ?>" alt="Event Image">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="delete_images[]" value="<?php echo $img['id']; ?>" id="img<?php echo $img['id']; ?>">
                                <label class="form-check-label" for="img<?php echo $img['id']; ?>">
                                    Delete this image
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Add New Images</label>
                    <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                    <small class="text-muted">You can select multiple new images to add</small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save me-2"></i> Update Event
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>