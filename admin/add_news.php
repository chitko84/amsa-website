<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$newsCategories = [
    'news' => 'News',
    'announcement' => 'Announcement',
    'workshop' => 'Workshop',
    'volunteer' => 'Volunteer',
    'community_engagement' => 'Event'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category = $_POST['category'] ?? 'news';
    if (!array_key_exists($category, $newsCategories)) {
        $category = 'news';
    }
    $uploaded_by = $_SESSION['admin_id'];

    $stmt = $conn->prepare("INSERT INTO post (content, category, title, uploaded_by, upload_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $content, $category, $title, $uploaded_by);
    $stmt->execute();
    $post_id = $conn->insert_id;

    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $original_name = basename($_FILES['images']['name'][$key]);
            $file_name = time() . '_' . $key . '_' . $original_name;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $imgStmt = $conn->prepare("INSERT INTO image (post_id, img_name) VALUES (?, ?)");
                $imgStmt->bind_param("is", $post_id, $file_name);
                $imgStmt->execute();
            }
        }
    }

    header('Location: dashboard.php?msg=news_added');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add News - AMSA Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --wine: #2c0410;
            --red: #8b3a3a;
            --gold: #c6b511;
            --soft: #f7f4ef;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--soft);
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

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(139,58,58,0.65);
            color: white;
        }

        .sidebar-menu a.active {
            border-left: 4px solid var(--gold);
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 25px;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 34px;
        }

        .form-shell {
            max-width: 960px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(23, 24, 32, 0.1);
        }

        .form-hero {
            background: linear-gradient(135deg, var(--wine), var(--red));
            color: white;
            padding: 34px;
        }

        .form-body {
            padding: 34px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 14px;
        }

        .form-select {
            border-radius: 8px;
            padding: 12px 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 0.2rem rgba(139, 58, 58, 0.18);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--red), #b55a4a);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 28px;
            font-weight: 700;
        }

        .preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 14px;
        }

        .preview-grid img {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
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
                <i class="fas fa-plus-circle"></i> Add CME
            </a>
            <a href="add_news.php" class="active">
                <i class="fas fa-newspaper"></i> Add News
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <main class="main-content">
        <section class="form-shell">
            <div class="form-hero">
                <p class="mb-2 text-uppercase fw-bold" style="color: var(--gold);">Events & News</p>
                <h1 class="mb-2">Add News Post</h1>
                <p class="mb-0">Publish announcements and updates directly to the public Events & News page.</p>
            </div>
            <div class="form-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">News Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="Enter news title">
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label fw-bold">Category *</label>
                        <select class="form-select" id="category" name="category" required>
                            <?php foreach ($newsCategories as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">This value is saved in the post category column and used for filtering on Events & News.</small>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label fw-bold">News Content *</label>
                        <textarea class="form-control" id="content" name="content" rows="9" required placeholder="Write the full news update..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="images" class="form-label fw-bold">Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                        <small class="text-muted">Optional. Upload one or more JPG, PNG, or GIF images.</small>
                        <div id="imagePreview" class="preview-grid"></div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane me-2"></i> Publish News
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </section>
    </main>

    <script>
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            Array.from(input.files || []).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
