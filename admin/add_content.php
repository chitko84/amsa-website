<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$type = $_GET['type'] ?? '';
if (!in_array($type, ['achievement', 'testimonial'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $uploaded_by = $_SESSION['admin_id'];
    
    // Check if testimonial with same title and content already exists
    if ($type == 'testimonial') {
        $checkStmt = $conn->prepare("SELECT id FROM post WHERE category = ? AND title = ? AND content = ?");
        $checkStmt->bind_param("sss", $type, $title, $content);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Duplicate found
            header('Location: dashboard.php?msg=duplicate');
            exit();
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO post (content, category, title, uploaded_by, upload_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $content, $type, $title, $uploaded_by);
    $stmt->execute();
    $post_id = $conn->insert_id;
    
    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $imgStmt = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
            $imgStmt->bind_param("is", $post_id, $file_name);
            $imgStmt->execute();
        }
    }
    
    header('Location: dashboard.php?msg=added');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add <?php echo ucfirst($type); ?> - AMSA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Nunito', sans-serif; }
        .container { max-width: 800px; margin: 50px auto; }
        .card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #8B3A3A, #B55A4A); color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        .btn-submit { background: linear-gradient(135deg, #8B3A3A, #B55A4A); color: white; border: none; padding: 10px 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Add New <?php echo ucfirst($type); ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo ($type == 'achievement') ? 'Achievement Title' : 'Person\'s Name'; ?></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo ($type == 'achievement') ? 'Description' : 'Testimonial'; ?></label>
                        <textarea class="form-control" name="content" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Image (Optional)</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-submit">Save <?php echo ucfirst($type); ?></button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>