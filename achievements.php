<?php if(isset($_SESSION['user_id'])): ?>
    <div class="nav-item dropdown">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-star"></i> Points: <?php echo getUserPoints($_SESSION['user_id']); ?>
        </a>
        <div class="dropdown-menu m-0">
            <a href="point_request.php" class="dropdown-item">Request Points</a>
            <a href="my_points.php" class="dropdown-item">My Points History</a>
            <?php if($_SESSION['user_id'] == 1): // Admin check ?>
                <div class="dropdown-divider"></div>
                <a href="admin_points.php" class="dropdown-item">Manage Requests</a>
                <a href="point_categories_admin.php" class="dropdown-item">Manage Categories</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>