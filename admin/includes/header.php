<?php
$pageTitle = $pageTitle ?? 'Admin Panel';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentAdminName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin User';
$currentAdminRole = roleLabel(currentUserRole());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - AMSA Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/amsa-design-system.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-primary admin-mobile-toggle" type="button" onclick="document.body.classList.toggle('admin-sidebar-open')">
                    <i class="fas fa-bars"></i>
                </button>
                <img src="../img/logo.png" class="admin-topbar-logo" alt="AMSA">
                <div>
                    <h5 class="mb-0 admin-page-title"><?php echo htmlspecialchars($pageTitle); ?></h5>
                    <small class="text-muted">AMSA Management System</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <strong class="d-block"><?php echo htmlspecialchars($currentAdminName); ?></strong>
                    <small class="text-muted"><?php echo htmlspecialchars($currentAdminRole); ?></small>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </header>
        <section class="admin-content">
