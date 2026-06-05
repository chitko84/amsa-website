<?php
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id']) && isAdminRole(currentUserRole())) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } else {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if ($user['status'] !== 'active' || !isAdminRole($user['role'])) {
            $error = 'Invalid email or password!';
        } elseif (password_verify($password, $user['password'])) {
            $role = normalizeRole($user['role']);
            regenerateAuthSession();
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $role;
            
            // Update last login
            $updateStmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password!';
        }
    } else {
        $error = 'Invalid email or password!';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Login - AMSA</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">
    
    <!-- Google Web Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/amsa-design-system.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #5f2626 0%, #8b3a3a 55%, #b55a4a 100%);
            font-family: 'Nunito', sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            padding: 40px;
            text-align: center;
        }
        .login-header img {
            width: 80px;
            margin-bottom: 20px;
        }
        .login-header h3 {
            color: white;
            margin: 0;
            font-weight: 700;
        }
        .login-header p {
            color: rgba(255,255,255,0.9);
            margin: 10px 0 0;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #8B3A3A;
            box-shadow: 0 0 0 0.2rem rgba(139,58,58,0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,58,58,0.3);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../img/logo.png" alt="AMSA Logo">
                <h3>Admin Login</h3>
                <p>Access the AMSA Management Panel</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger amsa-alert amsa-alert-error alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrfInput(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin@amsa.com" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i> Secure Admin Access Only
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
