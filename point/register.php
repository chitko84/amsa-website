<?php
require_once '../config/database.php';

if (currentUserId()) {
    header('Location: my_points.php');
    exit();
}

$error = $success = '';

function oldValue($field) {
    global $success;
    if ($success) {
        return '';
    }

    return htmlspecialchars($_POST[$field] ?? '', ENT_QUOTES, 'UTF-8');
}

function isAllowedAiuEmail($email) {
    return preg_match('/@(?:student\.)?aiu\.edu\.my$/i', $email) === 1;
}

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
        } elseif (!isAllowedAiuEmail($email)) {
            $error = 'Please use your official AIU email address ending with @aiu.edu.my or @student.aiu.edu.my.';
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

    <style>
        :root {
            --amsa-primary: #8f3b3b;
            --amsa-primary-dark: #6f2929;
            --amsa-gold: #d4af37;
            --amsa-cream: #fff9f0;
            --amsa-text: #2f2f2f;
            --amsa-muted: #756b66;
        }

        * {
            box-sizing: border-box;
        }

        body.points-page {
            min-height: 100vh;
            margin: 0;
            font-family: "Poppins", "Segoe UI", Arial, sans-serif;
            color: var(--amsa-text);
            background:
                radial-gradient(circle at 15% 15%, rgba(212, 175, 55, 0.22), transparent 28%),
                linear-gradient(135deg, #7b3434 0%, #9c4b45 48%, #793333 100%);
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-layout {
            width: min(1080px, 100%);
            min-height: 720px;
            display: grid;
            grid-template-columns: 430px 1fr;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(42, 12, 12, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.85);
        }

        .auth-card-panel {
            background: #ffffff;
            padding: 44px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .amsa-card,
        .login-card,
        .card {
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 0;
            width: 100%;
        }

        .card-body {
            padding: 0;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
            background: #ffffff;
            padding: 11px;
            border-radius: 24px;
            margin-bottom: 18px;
            box-shadow: 0 14px 35px rgba(143, 59, 59, 0.18);
            border: 1px solid #f1ded8;
        }

        .auth-header h4 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--amsa-primary-dark);
            margin-bottom: 8px;
            letter-spacing: -0.04em;
        }

        .auth-header p {
            color: var(--amsa-muted);
            font-size: 0.92rem;
            margin-bottom: 0;
        }

        .auth-intro {
            background: linear-gradient(135deg, #fff8e1, #ffffff);
            border-left: 5px solid var(--amsa-gold);
            padding: 14px 16px;
            border-radius: 16px;
            font-size: 0.9rem;
            line-height: 1.55;
            color: #54463d;
            margin-bottom: 22px;
            border-top: 1px solid rgba(212, 175, 55, 0.22);
            border-right: 1px solid rgba(212, 175, 55, 0.22);
            border-bottom: 1px solid rgba(212, 175, 55, 0.22);
        }

        .form-label {
            font-weight: 700;
            font-size: 0.9rem;
            color: #2f2f2f;
            margin-bottom: 8px;
        }

        .amsa-form-control {
            height: 48px;
            width: 100%;
            border-radius: 14px;
            border: 1px solid #eadbd6;
            background: #fffdfb;
            padding: 12px 15px;
            font-size: 0.95rem;
            color: var(--amsa-text);
            transition: all 0.22s ease;
        }

        .amsa-form-control:focus {
            background: #ffffff;
            border-color: var(--amsa-gold);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.18);
            outline: none;
        }

        .amsa-upload-hint {
            display: block;
            margin-top: 6px;
            color: #857a74;
            font-size: 0.8rem;
        }

        .amsa-btn-primary {
            height: 50px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--amsa-primary), var(--amsa-primary-dark));
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 16px 30px rgba(143, 59, 59, 0.28);
            transition: all 0.22s ease;
            margin-top: 4px;
        }

        .amsa-btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--amsa-primary-dark), var(--amsa-primary));
            box-shadow: 0 20px 35px rgba(143, 59, 59, 0.34);
        }

        .amsa-alert {
            border: none;
            border-radius: 14px;
            padding: 13px 15px;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }

        .auth-actions {
            text-align: center;
            margin-top: 20px;
        }

        .auth-actions p {
            color: #655d59;
            margin-bottom: 14px;
            font-size: 0.9rem;
        }

        .auth-link {
            color: var(--amsa-primary-dark);
            font-weight: 800;
            text-decoration: none;
        }

        .auth-link:hover {
            color: var(--amsa-gold);
        }

        .auth-home-btn {
            border-radius: 14px;
            padding: 10px 24px;
            color: var(--amsa-primary-dark);
            border: 1px solid rgba(143, 59, 59, 0.25);
            background: #ffffff;
            font-weight: 700;
            transition: all 0.22s ease;
        }

        .auth-home-btn:hover {
            background: var(--amsa-primary-dark);
            color: #ffffff;
        }

        .auth-image-panel {
            position: relative;
            min-height: 720px;
            overflow: hidden;
        }

        .auth-image-panel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .auth-image-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(25, 10, 10, 0.08), rgba(95, 24, 24, 0.68)),
                linear-gradient(90deg, rgba(255, 255, 255, 0.18), transparent 35%);
            z-index: 1;
        }

        .auth-image-caption {
            position: absolute;
            left: 34px;
            right: 34px;
            bottom: 34px;
            z-index: 2;
            color: #ffffff;
            padding: 24px;
            border-radius: 24px;
            background: rgba(120, 55, 55, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.28);
            backdrop-filter: blur(12px);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
        }

        .auth-image-caption span {
            display: block;
            color: #ffe27a;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .auth-image-caption strong {
            display: block;
            font-size: 1.32rem;
            line-height: 1.4;
        }

        @media (max-width: 950px) {
            .auth-layout {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .auth-image-panel {
                order: -1;
                min-height: 280px;
            }

            .auth-card-panel {
                padding: 34px 28px;
            }

            .auth-image-caption {
                left: 22px;
                right: 22px;
                bottom: 22px;
                padding: 18px;
            }

            .auth-image-caption strong {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .auth-shell {
                padding: 14px;
            }

            .auth-layout {
                border-radius: 24px;
            }

            .auth-card-panel {
                padding: 28px 22px;
            }

            .auth-header h4 {
                font-size: 1.45rem;
            }

            .auth-logo {
                width: 72px;
                height: 72px;
            }

            .auth-image-panel {
                min-height: 220px;
            }

            .auth-image-caption {
                display: none;
            }
        }
    </style>
</head>

<body class="points-page">
    <div class="auth-shell">
        <div class="auth-layout">
            <section class="auth-card-panel">
                <div class="card login-card amsa-card">
                    <div class="auth-header">
                        <img src="../img/logo.png" class="auth-logo" alt="AMSA">
                        <h4>Register for AMSA Points</h4>
                        <p>Join the member activity and rewards system</p>
                    </div>

                    <div class="card-body">
                        <div class="auth-intro">
                            <strong>Start participating.</strong> Submit activities, upload evidence, and build your AMSA points record.
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger amsa-alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success amsa-alert">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrfInput(); ?>

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    class="form-control amsa-form-control" 
                                    value="<?php echo oldValue('name'); ?>" 
                                    autocomplete="name" 
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="form-control amsa-form-control" 
                                    value="<?php echo oldValue('email'); ?>" 
                                    pattern="^[A-Za-z0-9._%+\-]+@([Ss][Tt][Uu][Dd][Ee][Nn][Tt]\.)?[Aa][Ii][Uu]\.[Ee][Dd][Uu]\.[Mm][Yy]$" 
                                    title="Use your official AIU email ending with @aiu.edu.my or @student.aiu.edu.my." 
                                    autocomplete="email" 
                                    required
                                >
                                <small class="amsa-upload-hint">Use your official AIU email address.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    class="form-control amsa-form-control" 
                                    minlength="8" 
                                    autocomplete="new-password" 
                                    required
                                >
                                <small class="amsa-upload-hint">Use at least 8 characters.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    name="confirm_password" 
                                    class="form-control amsa-form-control" 
                                    minlength="8" 
                                    autocomplete="new-password" 
                                    required
                                >
                            </div>

                            <button type="submit" class="btn amsa-btn-primary w-100">
                                Register
                            </button>
                        </form>

                        <div class="auth-actions">
                            <p>Already have an account? <a class="auth-link" href="login.php">Log in</a></p>
                            <a class="btn auth-home-btn" href="../index.php">Back to Home</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="auth-image-panel" aria-label="AIU campus">
                <img src="https://ace-sedi.aiu.edu.my/assets/images/aiu-campus-1.jpg" alt="AIU campus">

                <div class="auth-image-caption">
                    <span>AMSA AIU</span>
                    <strong>Create your member account with an official AIU email to start earning points.</strong>
                </div>
            </section>
        </div>
    </div>
</body>
</html>