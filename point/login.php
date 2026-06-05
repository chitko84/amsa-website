<?php
require_once '../config/database.php';

if (currentUserId()) {
    header('Location: ' . (isAdminRole(currentUserRole()) ? 'admin_points.php' : 'my_points.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            regenerateAuthSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = normalizeRole($user['role']);

            if (isAdminRole($user['role'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
            }

            $updateStmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            header('Location: ' . (isAdminRole($user['role']) ? 'admin_points.php' : 'my_points.php'));
            exit();
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA Points - Login</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
</head>
<body class="points-page">
    <div class="auth-shell">
        <div class="auth-card">
                <div class="card shadow-sm login-card amsa-card">
                    <div class="card-header auth-header text-center p-4">
                        <img src="../img/logo.png" class="auth-logo" alt="AMSA">
                        <h4 class="mb-1">AMSA Points Login</h4>
                        <p class="mb-0">Access your activity points dashboard</p>
                    </div>
                    <div class="card-body">
                        <div class="auth-intro">
                            <strong>Welcome back.</strong> Track your approved activities, rankings, and AMSA participation history.
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrfInput(); ?>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control amsa-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control amsa-form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary w-100">Login</button>
                        </form>

                        <div class="mt-3 text-center">
                            New member? <a class="auth-link" href="register.php">Create an account</a>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</body>
</html>
