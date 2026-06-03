<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$newsCategories = ['news', 'announcement', 'workshop', 'volunteer'];

function deletePostWithImages($id, $mode) {
    global $conn;

    if ($mode === 'event') {
        $checkStmt = $conn->prepare("SELECT id FROM post WHERE id = ? AND category = 'community_engagement'");
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM post WHERE id = ? AND category IN ('news', 'announcement', 'workshop', 'volunteer')");
    }

    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $post = $checkStmt->get_result()->fetch_assoc();

    if (!$post) {
        return false;
    }

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

    $delStmt = $conn->prepare("DELETE FROM post WHERE id = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();

    return true;
}

if (isset($_GET['delete_event']) && is_numeric($_GET['delete_event'])) {
    deletePostWithImages((int) $_GET['delete_event'], 'event');
    header('Location: dashboard.php?msg=deleted');
    exit();
}

if (isset($_GET['delete_news']) && is_numeric($_GET['delete_news'])) {
    deletePostWithImages((int) $_GET['delete_news'], 'news');
    header('Location: dashboard.php?msg=news_deleted');
    exit();
}

$events = getAllEvents();
$newsPosts = getAllNews();
$achievements = getAllAchievements();
$testimonials = getAllTestimonials();
$imgCount = $conn->query("SELECT COUNT(*) as count FROM image")->fetch_assoc()['count'];

function dashboardCategoryLabel($category) {
    return $category === 'community_engagement'
        ? 'Community engagement'
        : ucfirst(str_replace('_', ' ', $category));
}

function dashboardExcerpt($content, $length = 70) {
    $text = trim(strip_tags(htmlspecialchars_decode($content ?? '')));

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . '...';
}

function dashboardPostImage($postId) {
    $images = getEventImages($postId);

    return !empty($images) ? '../uploads/' . $images[0]['img_name'] : '../img/logo.png';
}

$messages = [
    'added' => 'Event added successfully!',
    'updated' => 'Event updated successfully!',
    'deleted' => 'Post deleted successfully!',
    'news_added' => 'News post added successfully!',
    'news_updated' => 'News post updated successfully!',
    'news_deleted' => 'News post deleted successfully!'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - AMSA</title>
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
            --ink: #1b1820;
            --muted: #6d6878;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f5f6f8;
            color: var(--ink);
        }

        .sidebar {
            background: linear-gradient(135deg, var(--wine), #4a1a2a);
            min-height: 100vh;
            position: fixed;
            width: 280px;
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
            font-weight: 800;
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.68);
            font-size: 14px;
            margin: 5px 0 0;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 25px;
            color: rgba(255,255,255,0.82);
            text-decoration: none;
            transition: all 0.25s;
            font-weight: 600;
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
            padding: 32px;
            min-height: 100vh;
        }

        .top-navbar,
        .stat-card,
        .table-container {
            background: white;
            border: 1px solid rgba(27, 24, 32, 0.06);
            border-radius: 12px;
            box-shadow: 0 18px 45px rgba(27, 24, 32, 0.06);
        }

        .top-navbar {
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .welcome-text h3 {
            margin: 0;
            font-weight: 800;
            color: var(--wine);
        }

        .welcome-text p,
        .admin-info,
        .text-muted-soft {
            color: var(--muted);
        }

        .admin-info {
            text-align: right;
        }

        .stat-card {
            padding: 22px;
            margin-bottom: 24px;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, var(--red), #b55a4a);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .stat-icon i {
            font-size: 24px;
            color: white;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 800;
            color: var(--wine);
            line-height: 1;
            margin-bottom: 7px;
        }

        .table-container {
            padding: 24px;
            margin-bottom: 28px;
            overflow: hidden;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-heading h4 {
            margin: 0;
            font-weight: 800;
            color: var(--wine);
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #faf8f5;
            border-bottom: 2px solid var(--wine);
            color: var(--wine);
            font-weight: 800;
            white-space: nowrap;
        }

        .table td {
            color: #575166;
        }

        .post-image {
            width: 64px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            background: #f7f4ef;
        }

        .badge-category {
            background: linear-gradient(135deg, var(--red), #b55a4a);
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .btn-add,
        .btn-edit,
        .btn-delete {
            border: 0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--red), #b55a4a);
            color: white;
            padding: 10px 18px;
        }

        .btn-edit {
            background: #fff4c2;
            color: #6d5400;
            padding: 7px 11px;
        }

        .btn-delete {
            background: #ffe0e3;
            color: #9b1c2d;
            padding: 7px 11px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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

            .admin-info {
                text-align: left;
                margin-top: 12px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo.png" alt="AMSA Logo">
            <h4>AMSA Admin</h4>
            <p>Content Manager</p>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="add_event.php"><i class="fas fa-plus-circle"></i> Add CME</a>
            <a href="add_news.php"><i class="fas fa-newspaper"></i> Add News</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <section class="top-navbar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="welcome-text">
                        <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h3>
                        <p class="mb-0">Manage AMSA events, news, achievements, and testimonials.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-info">
                        <p class="mb-0"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
                        <p class="mb-0 mt-1"><i class="fas fa-clock me-2"></i><?php echo date('F d, Y h:i A'); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-number"><?php echo count($events); ?></div>
                    <div class="text-muted-soft">Community Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                    <div class="stat-number"><?php echo count($newsPosts); ?></div>
                    <div class="text-muted-soft">News Posts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-number"><?php echo count($achievements); ?></div>
                    <div class="text-muted-soft">Achievements</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-image"></i></div>
                    <div class="stat-number"><?php echo (int) $imgCount; ?></div>
                    <div class="text-muted-soft">Photos Uploaded</div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && isset($messages[$_GET['msg']])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($messages[$_GET['msg']]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <section class="table-container">
            <div class="section-heading">
                <h4><i class="fas fa-list me-2"></i> Community Engagement Events</h4>
                <a href="add_event.php" class="btn-add"><i class="fas fa-plus"></i> Add Event</a>
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
                            <tr><td colspan="7" class="text-center py-5 text-muted-soft">No community events found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>#<?php echo (int) $event['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars(dashboardPostImage($event['id'])); ?>" alt="Event" class="post-image"></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars(htmlspecialchars_decode($event['title'])); ?></td>
                                    <td><span class="badge-category"><?php echo htmlspecialchars(dashboardCategoryLabel($event['category'])); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($event['upload_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['author_name'] ?? 'Admin'); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_event.php?id=<?php echo (int) $event['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="?delete_event=<?php echo (int) $event['id']; ?>" class="btn-delete" onclick="return confirm('Delete this event?')"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="table-container">
            <div class="section-heading">
                <h4><i class="fas fa-newspaper me-2"></i> News & Updates</h4>
                <a href="add_news.php" class="btn-add"><i class="fas fa-plus"></i> Add News</a>
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
                        <?php if (empty($newsPosts)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted-soft">No news posts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($newsPosts as $news): ?>
                                <tr>
                                    <td>#<?php echo (int) $news['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars(dashboardPostImage($news['id'])); ?>" alt="News" class="post-image"></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars(htmlspecialchars_decode($news['title'])); ?></td>
                                    <td><span class="badge-category"><?php echo htmlspecialchars(dashboardCategoryLabel($news['category'])); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($news['upload_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($news['author_name'] ?? 'Admin'); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_news.php?id=<?php echo (int) $news['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="?delete_news=<?php echo (int) $news['id']; ?>" class="btn-delete" onclick="return confirm('Delete this news post?')"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="table-container">
            <div class="section-heading">
                <h4><i class="fas fa-trophy me-2"></i> Achievements</h4>
                <a href="add_content.php?type=achievement" class="btn-add"><i class="fas fa-plus"></i> Add Achievement</a>
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
                        <?php if (empty($achievements)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted-soft">No achievements found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($achievements as $achievement): ?>
                                <tr>
                                    <td>#<?php echo (int) $achievement['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars(htmlspecialchars_decode($achievement['title'])); ?></td>
                                    <td><?php echo htmlspecialchars(dashboardExcerpt($achievement['content'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($achievement['upload_date'])); ?></td>
                                    <td><a href="delete_content.php?id=<?php echo (int) $achievement['id']; ?>&type=achievement" class="btn-delete" onclick="return confirm('Delete this achievement?')"><i class="fas fa-trash"></i> Delete</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="table-container">
            <div class="section-heading">
                <h4><i class="fas fa-comments me-2"></i> Testimonials</h4>
                <a href="add_content.php?type=testimonial" class="btn-add"><i class="fas fa-plus"></i> Add Testimonial</a>
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
                        <?php if (empty($testimonials)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted-soft">No testimonials found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($testimonials as $testimonial): ?>
                                <tr>
                                    <td>#<?php echo (int) $testimonial['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars(htmlspecialchars_decode($testimonial['title'])); ?></td>
                                    <td><?php echo htmlspecialchars(dashboardExcerpt($testimonial['content'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($testimonial['upload_date'])); ?></td>
                                    <td><a href="delete_content.php?id=<?php echo (int) $testimonial['id']; ?>&type=testimonial" class="btn-delete" onclick="return confirm('Delete this testimonial?')"><i class="fas fa-trash"></i> Delete</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
