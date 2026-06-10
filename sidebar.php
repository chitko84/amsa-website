<!-- sidebar.php - Include this file in all admin pages -->
<style>
/* Sidebar Styles */
:root {
    --primary-dark: #2c0410;
    --primary-medium: #4a1a2a;
    --primary: #8B3A3A;
    --primary-light: #B55A4A;
    --accent: #c6b511;
}

.sidebar {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
    min-height: 100vh;
    position: fixed;
    width: 280px;
    transition: all 0.3s;
    z-index: 1000;
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 100vh;
}

/* Custom scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 5px;
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
    padding-bottom: 50px;
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
    background: var(--primary);
    color: white;
    border-left: 4px solid var(--accent);
}

.sidebar-menu a i {
    margin-right: 10px;
    width: 25px;
}

/* Dropdown Styles */
.sidebar-menu .dropdown-menu-wrapper {
    position: relative;
    width: 100%;
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
    max-height: 200px;
    overflow-y: auto;
}

.sidebar-menu .dropdown-items::-webkit-scrollbar {
    width: 3px;
}

.sidebar-menu .dropdown-items::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
}

.sidebar-menu .dropdown-items::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
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

/* Main Content Styles */
.main-content {
    margin-left: 280px;
    padding: 30px;
    min-height: 100vh;
    background: #f8f9fa;
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
    color: var(--primary-dark);
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
    color: var(--primary);
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

<div class="sidebar">
    <div class="sidebar-header">
        <img src="img/logo.png" alt="AMSA Logo">
        <h4>AMSA Admin</h4>
        <p>Community Engagement Manager</p>
    </div>
    <div class="sidebar-menu">
        <a href="admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="admin/add_event.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_event.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Add New Event
        </a>
        <a href="admin/manage_fundraising.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_fundraising.php' ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-heart"></i> Manage Fundraising
        </a>
        <a href="admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard Analytics
        </a>
        <a href="admin/members.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Members
        </a>
        
        <!-- Point Management Dropdown -->
        <div class="dropdown-menu-wrapper <?php echo (basename($_SERVER['PHP_SELF']) == 'point_categories_admin.php' || basename($_SERVER['PHP_SELF']) == 'admin_points.php' || basename($_SERVER['PHP_SELF']) == 'point_request.php' || basename($_SERVER['PHP_SELF']) == 'my_points.php') ? 'active' : ''; ?>">
            <div class="dropdown-toggle-btn">
                <i class="fas fa-star"></i> Point Management
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>
            <div class="dropdown-items">
                <a href="point/point_categories_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'point_categories_admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="point/admin_points.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_points.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Manage Requests
                </a>
                <a href="point/point_request.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'point_request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </a>
                <a href="point/my_points.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_points.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> My Points
                </a>
            </div>
        </div>
        
        <a href="admin/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
// Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    var dropdownToggle = document.querySelector('.dropdown-toggle-btn');
    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var wrapper = this.parentElement;
            wrapper.classList.toggle('active');
        });
    }
});
</script>
