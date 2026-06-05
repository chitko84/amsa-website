<?php
require_once '../config/database.php';
requireAdmin('login.php');

function scalarCount($sql) {
    global $conn;
    $result = $conn->query($sql);
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

function latestPostsByCategory($category, $limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM post WHERE category = ? ORDER BY upload_date DESC LIMIT ?");
    $stmt->bind_param("si", $category, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function latestPostsInCategories($categories, $limit = 8) {
    global $conn;
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $types = str_repeat('s', count($categories)) . 'i';
    $stmt = $conn->prepare("SELECT * FROM post WHERE category IN ($placeholders) ORDER BY upload_date DESC LIMIT ?");
    $params = array_merge($categories, [$limit]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function latestContactMessages($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, whatsapp_number, subject, submission_date FROM contact_messages ORDER BY submission_date DESC, id DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stats = [
    ['label' => 'Total Members', 'value' => scalarCount("SELECT COUNT(*) AS total FROM user WHERE role = 'member'"), 'icon' => 'fa-users'],
    ['label' => 'Total Points Requests', 'value' => scalarCount("SELECT COUNT(*) AS total FROM point_request"), 'icon' => 'fa-clipboard-list'],
    ['label' => 'Pending Requests', 'value' => scalarCount("SELECT COUNT(*) AS total FROM point_request WHERE status = 'pending'"), 'icon' => 'fa-clock'],
    ['label' => 'Approved Requests', 'value' => scalarCount("SELECT COUNT(*) AS total FROM point_request WHERE status = 'approved'"), 'icon' => 'fa-check-circle'],
    ['label' => 'Total News', 'value' => scalarCount("SELECT COUNT(*) AS total FROM post WHERE category IN ('news','announcement','workshop','volunteer')"), 'icon' => 'fa-newspaper'],
    ['label' => 'Total Events', 'value' => scalarCount("SELECT COUNT(*) AS total FROM post WHERE category = 'community_engagement'"), 'icon' => 'fa-calendar-alt'],
    ['label' => 'Total Achievements', 'value' => scalarCount("SELECT COUNT(*) AS total FROM post WHERE category = 'achievement'"), 'icon' => 'fa-award'],
    ['label' => 'Total Testimonials', 'value' => scalarCount("SELECT COUNT(*) AS total FROM post WHERE category = 'testimonial'"), 'icon' => 'fa-comment-dots'],
];

$events = latestPostsByCategory('community_engagement', 5);
$newsPosts = latestPostsInCategories(['news', 'announcement', 'workshop', 'volunteer'], 5);
$achievements = latestPostsByCategory('achievement', 5);
$testimonials = latestPostsByCategory('testimonial', 5);
$contactMessages = latestContactMessages(5);
$allPointRequests = function_exists('getAllPointRequests') ? getAllPointRequests() : [];
$pendingPointRequests = array_values(array_filter($allPointRequests, function ($request) {
    return ($request['status'] ?? '') === 'pending';
}));
$pendingPreview = array_slice($pendingPointRequests, 0, 5);
$dashboardAdminName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin User';
$dashboardAdminRole = roleLabel(currentUserRole());

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success amsa-alert amsa-alert-success">Action completed successfully.</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="admin-priority-card">
            <span class="priority-icon"><i class="fas fa-user-shield"></i></span>
            <h4><?php echo htmlspecialchars($dashboardAdminName); ?></h4>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($dashboardAdminRole); ?></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-priority-card">
            <span class="priority-icon"><i class="fas fa-clipboard-check"></i></span>
            <h4>Pending Point Requests</h4>
            <p class="text-muted mb-3"><?php echo count($pendingPointRequests); ?> requests need admin review.</p>
            <a href="../point/admin_points.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Review Requests</a>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-priority-card">
            <span class="priority-icon"><i class="fas fa-envelope-open-text"></i></span>
            <h4>New Contact Messages</h4>
            <p class="text-muted mb-3"><?php echo count($contactMessages); ?> recent public enquiries are available.</p>
            <a href="contact_messages.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Open Messages</a>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-priority-card">
            <span class="priority-icon"><i class="fas fa-newspaper"></i></span>
            <h4>Recent Content Activity</h4>
            <p class="text-muted mb-3">Manage news, events, achievements, and testimonials.</p>
            <a href="add_news.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Add Update</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card amsa-stat-card">
                <span class="icon"><i class="fas <?php echo htmlspecialchars($stat['icon']); ?>"></i></span>
                <h3 class="mb-1"><?php echo (int) $stat['value']; ?></h3>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($stat['label']); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>Pending Requests</h4>
                    <p>Newest member activities awaiting review.</p>
                </div>
                <a href="../point/admin_points.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">View All</a>
            </div>
            <?php if (empty($pendingPreview)): ?>
                <div class="amsa-empty-state">
                    <i class="fas fa-check-circle fa-2x mb-3 text-primary"></i>
                    <h5>No Pending Requests</h5>
                    <p class="mb-0">All point requests are currently reviewed.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive amsa-table-wrap">
                    <table class="table align-middle amsa-table">
                        <thead><tr><th>Member</th><th>Activity</th><th>Points</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($pendingPreview as $request): ?>
                                <tr>
                                    <td>
                                        <span class="profile-member-cell">
                                            <img src="<?php echo htmlspecialchars(profileImageUrl($request['user_profile_image'] ?? null, '../')); ?>" class="profile-avatar-sm" alt="Member profile image">
                                            <span><?php echo htmlspecialchars($request['user_name'] ?? 'Member'); ?></span>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['category_name'] ?? 'Activity'); ?></td>
                                    <td><strong><?php echo (int) ($request['points'] ?? 0); ?></strong></td>
                                    <td><?php echo !empty($request['request_date']) ? date('M d, Y', strtotime($request['request_date'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>New Contact Messages</h4>
                    <p>Latest enquiries from the public contact form.</p>
                </div>
                <a href="contact_messages.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Manage</a>
            </div>
            <?php if (empty($contactMessages)): ?>
                <div class="amsa-empty-state">
                    <i class="fas fa-envelope fa-2x mb-3 text-primary"></i>
                    <h5>No Messages Yet</h5>
                    <p class="mb-0">New contact form submissions will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive amsa-table-wrap">
                    <table class="table align-middle amsa-table">
                        <thead><tr><th>Name</th><th>Subject</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($contactMessages as $message): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($message['name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                    <td><?php echo $message['submission_date'] ? date('M d, Y', strtotime($message['submission_date'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>Recent News</h4>
                    <p>Latest published public updates.</p>
                </div>
                <a href="add_news.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Add News</a>
            </div>
            <?php if (empty($newsPosts)): ?>
                <div class="amsa-empty-state"><i class="fas fa-newspaper fa-2x mb-3 text-primary"></i><h5>No News Yet</h5><a href="add_news.php" class="btn btn-primary amsa-btn amsa-btn-primary admin-empty-action">Add News</a></div>
            <?php else: ?>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle amsa-table">
                    <thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($newsPosts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['category']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($post['upload_date'])); ?></td>
                                <td>
                                    <a href="edit_news.php?id=<?php echo (int) $post['id']; ?>" class="btn-edit amsa-btn amsa-btn-secondary amsa-btn-sm">Edit</a>
                                    <form method="POST" action="delete_content.php" class="d-inline" id="deleteNewsForm<?php echo (int) $post['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $post['id']; ?>">
                                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($post['category']); ?>">
                                        <button type="button" class="btn-delete amsa-btn amsa-btn-danger amsa-btn-sm border-0" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-form="deleteNewsForm<?php echo (int) $post['id']; ?>" data-delete-message="Are you sure you want to delete this news item?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>Recent Events</h4>
                    <p>Community engagement content.</p>
                </div>
                <a href="add_event.php" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Add Event</a>
            </div>
            <?php if (empty($events)): ?>
                <div class="amsa-empty-state"><i class="fas fa-calendar-alt fa-2x mb-3 text-primary"></i><h5>No Events Yet</h5><a href="add_event.php" class="btn btn-primary amsa-btn amsa-btn-primary admin-empty-action">Add Event</a></div>
            <?php else: ?>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle amsa-table">
                    <thead><tr><th>Title</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($event['upload_date'])); ?></td>
                                <td>
                                    <a href="edit_event.php?id=<?php echo (int) $event['id']; ?>" class="btn-edit amsa-btn amsa-btn-secondary amsa-btn-sm">Edit</a>
                                    <form method="POST" action="delete_content.php" class="d-inline" id="deleteEventForm<?php echo (int) $event['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $event['id']; ?>">
                                        <input type="hidden" name="type" value="community_engagement">
                                        <button type="button" class="btn-delete amsa-btn amsa-btn-danger amsa-btn-sm border-0" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-form="deleteEventForm<?php echo (int) $event['id']; ?>" data-delete-message="Are you sure you want to delete this event?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>Achievements</h4>
                    <p>Milestones shown on the public site.</p>
                </div>
                <a href="add_content.php?type=achievement" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Add Achievement</a>
            </div>
            <?php if (empty($achievements)): ?>
                <div class="amsa-empty-state"><i class="fas fa-award fa-2x mb-3 text-primary"></i><h5>No Achievements Yet</h5><a href="add_content.php?type=achievement" class="btn btn-primary amsa-btn amsa-btn-primary admin-empty-action">Add Achievement</a></div>
            <?php else: ?>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle amsa-table">
                    <thead><tr><th>Title</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($achievements as $achievement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($achievement['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($achievement['upload_date'])); ?></td>
                                <td>
                                    <a href="edit_content.php?id=<?php echo (int) $achievement['id']; ?>&type=achievement" class="btn-edit amsa-btn amsa-btn-secondary amsa-btn-sm">Edit</a>
                                    <form method="POST" action="delete_content.php" class="d-inline" id="deleteAchievementForm<?php echo (int) $achievement['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $achievement['id']; ?>">
                                        <input type="hidden" name="type" value="achievement">
                                        <button type="button" class="btn-delete amsa-btn amsa-btn-danger amsa-btn-sm border-0" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-form="deleteAchievementForm<?php echo (int) $achievement['id']; ?>" data-delete-message="Are you sure you want to delete this achievement?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card amsa-card p-4 h-100">
            <div class="admin-section-header">
                <div>
                    <h4>Testimonials</h4>
                    <p>Public appreciation and feedback posts.</p>
                </div>
                <a href="add_content.php?type=testimonial" class="btn btn-primary amsa-btn amsa-btn-primary btn-sm">Add Testimonial</a>
            </div>
            <?php if (empty($testimonials)): ?>
                <div class="amsa-empty-state"><i class="fas fa-comment-dots fa-2x mb-3 text-primary"></i><h5>No Testimonials Yet</h5><a href="add_content.php?type=testimonial" class="btn btn-primary amsa-btn amsa-btn-primary admin-empty-action">Add Testimonial</a></div>
            <?php else: ?>
            <div class="table-responsive amsa-table-wrap">
                <table class="table align-middle amsa-table">
                    <thead><tr><th>Name</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($testimonials as $testimonial): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($testimonial['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($testimonial['upload_date'])); ?></td>
                                <td>
                                    <a href="edit_content.php?id=<?php echo (int) $testimonial['id']; ?>&type=testimonial" class="btn-edit amsa-btn amsa-btn-secondary amsa-btn-sm">Edit</a>
                                    <form method="POST" action="delete_content.php" class="d-inline" id="deleteTestimonialForm<?php echo (int) $testimonial['id']; ?>">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $testimonial['id']; ?>">
                                        <input type="hidden" name="type" value="testimonial">
                                        <button type="button" class="btn-delete amsa-btn amsa-btn-danger amsa-btn-sm border-0" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-delete-form="deleteTestimonialForm<?php echo (int) $testimonial['id']; ?>" data-delete-message="Are you sure you want to delete this testimonial?">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content amsa-modal">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmDeleteMessage">
                Are you sure you want to delete this item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary amsa-btn amsa-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger amsa-btn amsa-btn-danger" id="confirmDeleteButton">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('confirmDeleteModal');
    const message = document.getElementById('confirmDeleteMessage');
    const confirmButton = document.getElementById('confirmDeleteButton');
    let targetFormId = null;

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        targetFormId = trigger ? trigger.getAttribute('data-delete-form') : null;
        message.textContent = trigger ? trigger.getAttribute('data-delete-message') : 'Are you sure you want to delete this item?';
    });

    confirmButton.addEventListener('click', function () {
        if (!targetFormId) {
            return;
        }
        const form = document.getElementById(targetFormId);
        if (form) {
            form.submit();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
