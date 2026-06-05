<?php
require_once '../config/database.php';

if (currentUserId()) {
    header('Location: my_points.php');
    exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'member';
            $status = 'active';
            $stmt = $conn->prepare("
                INSERT INTO user (name, email, password, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $status);

            if ($stmt->execute()) {
                $success = 'Registration successful. You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA Points - Register</title>
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
                        <h4 class="mb-1">Create AMSA Points Account</h4>
                        <p class="mb-0">Join the member activity and rewards system</p>
                    </div>
                    <div class="card-body">
                        <div class="auth-intro">
                            <strong>Start participating.</strong> Submit activities, upload evidence, and build your AMSA points record.
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger amsa-alert amsa-alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success amsa-alert amsa-alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrfInput(); ?>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control amsa-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control amsa-form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control amsa-form-control" minlength="8" required>
                                <small class="amsa-upload-hint">Use at least 8 characters.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control amsa-form-control" minlength="8" required>
                            </div>
                            <button type="submit" class="btn btn-primary amsa-btn amsa-btn-primary w-100">Register</button>
                        </form>

                        <div class="mt-3 text-center">
                            Already have an account? <a class="auth-link" href="login.php">Log in</a>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</body>
</html>
