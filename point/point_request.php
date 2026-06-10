<?php
require_once '../config/database.php';
requireMember('login.php');

$userId = currentUserId();
$currentRole = currentUserRole();
$canSubmitPointRequest = $currentRole === 'member';
$categories = getAllPointCategories();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session token expired. Please try again.';
    } elseif (!$canSubmitPointRequest) {
        $error = 'Admin accounts cannot submit point requests. Please use a member account to submit activities.';
    } else {
        $pointCategoryId = isset($_POST['point_category_id']) ? (int) $_POST['point_category_id'] : 0;
        $description = sanitize($_POST['description'] ?? '');
        $validCategoryIds = array_map(fn($category) => (int) $category['id'], $categories);

        if (!in_array($pointCategoryId, $validCategoryIds, true)) {
            $error = 'Please select a valid activity type.';
        } elseif ($description === '') {
            $error = 'Please describe your activity.';
        } else {
            $evidenceFile = uploadEvidenceSecure($_FILES['eop_evidence'] ?? null, __DIR__ . '/uploads/eop/', $error);
            $filePath = $evidenceFile ? 'uploads/eop/' . $evidenceFile : null;
            if ($filePath && createPointRequest($userId, $pointCategoryId, $description, $filePath)) {
                $success = 'Point request submitted successfully. It is now pending admin review.';
            } elseif (!$error) {
                $error = 'Failed to submit request. Please try again.';
            }
        }
    }
}

$userRequests = getUserPointRequests($userId);
$userPoints = getUserPoints($userId);

function amsaDecodeText($value) {
    $text = (string) ($value ?? '');

    // Decode HTML entities even if the text was encoded more than once.
    for ($i = 0; $i < 3; $i++) {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded === $text) {
            break;
        }
        $text = $decoded;
    }

    // Convert saved literal escape sequences into real readable formatting.
    $text = str_replace(["\\r\\n", "\\n", "\\r", "\\t"], ["\n", "\n", "\n", "    "], $text);

    // Remove unwanted slashes from saved escaped quotes, for example \"hello\".
    $text = stripslashes($text);

    return $text;
}

function amsaTextPreview($value, $limit = 85) {
    $decoded = trim(amsaDecodeText($value));
    $singleLine = preg_replace('/\s+/', ' ', $decoded);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($singleLine) > $limit ? mb_substr($singleLine, 0, $limit) . '...' : $singleLine;
    }
    return strlen($singleLine) > $limit ? substr($singleLine, 0, $limit) . '...' : $singleLine;
}

function amsaTextIsLong($value, $limit = 85) {
    $decoded = trim(amsaDecodeText($value));
    $singleLine = preg_replace('/\s+/', ' ', $decoded);
    if (function_exists('mb_strlen')) {
        return mb_strlen($singleLine) > $limit;
    }
    return strlen($singleLine) > $limit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Point Request System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="../img/logo.png" rel="icon" type="image/png">
    <link href="../img/logo.png" rel="apple-touch-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="points-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --amsa-wine: #6b1d1d;
            --amsa-wine-dark: #451010;
            --amsa-gold: #d4af37;
            --amsa-gold-light: #f4d03f;
            --amsa-bg: #f8f5f1;
            --amsa-text: #111111;
            --amsa-muted: #6b5757;
            --amsa-white: #ffffff;
            --amsa-border: rgba(107, 29, 29, 0.12);
            --amsa-shadow: 0 18px 45px rgba(69, 16, 16, 0.12);
            --amsa-shadow-soft: 0 10px 30px rgba(69, 16, 16, 0.08);
            --amsa-radius: 22px;
        }

        body.points-page {
            background:
                radial-gradient(circle at top left, rgba(212, 175, 55, 0.12), transparent 35%),
                radial-gradient(circle at top right, rgba(107, 29, 29, 0.08), transparent 32%),
                var(--amsa-bg);
            color: var(--amsa-text);
        }

        .points-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--amsa-wine-dark), var(--amsa-wine) 55%, #9b6323);
            border-radius: 0 0 42px 42px;
            padding: 85px 0 95px !important;
            margin-bottom: 70px !important;
        }

        .points-hero::before,
        .points-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .points-hero::before {
            width: 360px;
            height: 360px;
            background: rgba(244, 208, 63, 0.16);
            top: -130px;
            right: -90px;
            filter: blur(4px);
        }

        .points-hero::after {
            width: 240px;
            height: 240px;
            background: rgba(255, 255, 255, 0.08);
            bottom: -90px;
            left: 8%;
        }

        .points-hero .container {
            position: relative;
            z-index: 2;
        }

        .hero-icon {
            width: 68px;
            height: 68px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--amsa-gold-light), var(--amsa-gold));
            color: #000;
            border-radius: 22px;
            font-size: 1.8rem;
            box-shadow: 0 18px 35px rgba(212, 175, 55, 0.28);
            margin-bottom: 18px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: #fff4cc;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .points-hero h1 {
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 14px;
            text-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
        }

        .points-hero p {
            max-width: 650px;
            margin: 0 auto;
            font-size: 1.12rem;
            color: rgba(255, 255, 255, 0.88);
            line-height: 1.7;
        }

        .points-page-shell {
            margin-top: -35px;
            position: relative;
            z-index: 5;
        }

        .amsa-card {
            background: var(--amsa-white);
            border: 1px solid var(--amsa-border);
            border-radius: var(--amsa-radius);
            box-shadow: var(--amsa-shadow-soft);
            overflow: hidden;
        }

        .point-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #7a1f1f, #4a1313 55%, #b88a22);
            border-radius: 26px;
            padding: 34px 28px;
            color: white;
            margin-bottom: 24px;
            box-shadow: var(--amsa-shadow);
            transition: all 0.3s ease;
        }

        .point-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 55px rgba(69, 16, 16, 0.2);
        }

        .point-card-content {
            position: relative;
            z-index: 2;
        }

        .points-trophy {
            width: 72px;
            height: 72px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: linear-gradient(135deg, var(--amsa-gold-light), var(--amsa-gold));
            color: #000;
            font-size: 2rem;
            box-shadow: 0 14px 30px rgba(244, 208, 63, 0.28);
            margin-bottom: 18px;
        }

        .point-card h2 {
            font-size: 3.7rem;
            line-height: 1;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .point-card .points-label {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .point-card .points-subtitle {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .points-help-card {
            padding: 30px;
        }

        .points-help-card h5 {
            font-weight: 900;
            color: var(--amsa-wine);
            margin-bottom: 24px;
            font-size: 1.45rem;
            display: flex;
            align-items: center;
        }

        .points-help-card h5 i {
            width: 32px;
            height: 32px;
            background: var(--amsa-wine);
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .points-help-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 18px;
        }

        .points-help-list li {
            display: flex;
            gap: 18px;
            align-items: center;
            padding: 20px 18px;
            border-radius: 20px;
            background: linear-gradient(135deg, #fffaf0, #fffdf8);
            border: 1px solid rgba(212, 175, 55, 0.32);
            transition: all 0.25s ease;
        }

        .points-help-list li:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(69, 16, 16, 0.10);
            border-color: rgba(212, 175, 55, 0.7);
        }

        .points-help-list .help-icon {
            min-width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #ffda3d, #d4af37);
            color: #000 !important;
            font-size: 1.65rem;
            box-shadow:
                0 10px 22px rgba(212, 175, 55, 0.35),
                inset 0 1px 2px rgba(255, 255, 255, 0.55);
            transition: all 0.25s ease;
        }

        .points-help-list li:hover .help-icon {
            transform: scale(1.08) rotate(-3deg);
            box-shadow: 0 14px 28px rgba(212, 175, 55, 0.48);
        }

        .points-help-list .help-icon i {
            color: #000 !important;
            font-weight: 900;
            opacity: 1 !important;
            text-shadow: none;
        }

        .points-help-list strong {
            display: block;
            color: #000;
            font-size: 1rem;
            font-weight: 900;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .points-help-list span {
            display: block;
            color: var(--amsa-muted);
            font-size: 0.93rem;
            line-height: 1.5;
        }

        .points-form-card {
            border: 0;
            box-shadow: var(--amsa-shadow);
        }

        .points-form-card .card-header {
            background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark)) !important;
            color: white;
            padding: 23px 28px;
            border: 0;
        }

        .points-form-card .card-header h5 {
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .points-form-card .card-body {
            padding: 30px;
        }

        .form-label {
            color: var(--amsa-text);
            margin-bottom: 9px;
        }

        .form-label i {
            color: var(--amsa-gold);
            margin-right: 7px;
        }

        .amsa-form-control {
            border: 1px solid rgba(107, 29, 29, 0.16);
            border-radius: 16px;
            padding: 13px 15px;
            color: var(--amsa-text);
            background-color: #fff;
            transition: all 0.25s ease;
        }

        .amsa-form-control:focus {
            border-color: var(--amsa-gold);
            box-shadow: 0 0 0 0.22rem rgba(212, 175, 55, 0.16);
        }

        textarea.amsa-form-control {
            resize: vertical;
            min-height: 145px;
        }

        .amsa-upload-hint {
            display: block;
            margin-top: 8px;
            color: var(--amsa-muted);
            font-size: 0.86rem;
        }

        .points-upload-box {
            padding: 20px;
            border-radius: 20px;
            border: 1.8px dashed rgba(107, 29, 29, 0.24);
            background: linear-gradient(135deg, #fffaf0, #ffffff);
        }

        .points-upload-box .upload-mini-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--amsa-wine);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .amsa-btn-primary {
            width: 100%;
            border: 0;
            border-radius: 16px;
            padding: 14px 22px;
            font-weight: 800;
            letter-spacing: 0.2px;
            color: #000;
            background: linear-gradient(135deg, var(--amsa-gold-light), var(--amsa-gold));
            box-shadow: 0 13px 28px rgba(212, 175, 55, 0.24);
            transition: all 0.25s ease;
        }

        .amsa-btn-primary:hover {
            transform: translateY(-2px);
            color: #000;
            box-shadow: 0 18px 35px rgba(212, 175, 55, 0.32);
        }

        .amsa-alert {
            border: 0;
            border-radius: 16px;
            padding: 15px 18px;
            font-weight: 600;
            box-shadow: var(--amsa-shadow-soft);
        }

        .amsa-alert-success {
            color: #155d32;
            background: #e9f8ef;
        }

        .amsa-alert-error {
            color: #8f1f1f;
            background: #fff0f0;
        }

        .amsa-alert-warning {
            color: #7a4e00;
            background: #fff7df;
        }

        .points-section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 20px;
        }

        .points-section-header h3 {
            font-weight: 900;
            color: var(--amsa-wine);
            margin-bottom: 5px;
        }

        .points-section-header p {
            margin: 0;
            color: var(--amsa-muted);
        }

        .amsa-table-wrap {
            border-radius: var(--amsa-radius);
            overflow-x: auto;
        }

        .amsa-table {
            min-width: 980px;
            margin-bottom: 0;
        }

        .amsa-table thead th {
            background: linear-gradient(135deg, var(--amsa-wine), var(--amsa-wine-dark));
            color: white;
            border: 0;
            font-size: 0.86rem;
            letter-spacing: 0.2px;
            padding: 16px 18px;
            white-space: nowrap;
        }

        .amsa-table tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            border-color: rgba(107, 29, 29, 0.08);
            color: var(--amsa-text);
            font-size: 0.92rem;
        }

        .amsa-table tbody tr:nth-child(even) {
            background: #fffaf4;
        }

        .amsa-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.12);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-pending {
            background: rgba(212, 175, 55, 0.2);
            color: #8a6400;
        }

        .status-approved {
            background: rgba(47, 143, 87, 0.14);
            color: #237044;
        }

        .status-rejected {
            background: rgba(180, 68, 68, 0.14);
            color: #9c2d2d;
        }

        .evidence-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid rgba(212, 175, 55, 0.75);
            color: var(--amsa-wine);
            background: #fffaf0;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.22s ease;
        }

        .evidence-btn:hover {
            background: var(--amsa-gold);
            color: #000;
            transform: translateY(-1px);
        }

        .amsa-empty-state {
            text-align: center;
            padding: 55px 25px;
            border-radius: var(--amsa-radius);
            background: linear-gradient(135deg, rgba(255, 250, 240, 0.95), rgba(255, 255, 255, 0.98));
            border: 1px dashed rgba(107, 29, 29, 0.18);
            box-shadow: var(--amsa-shadow-soft);
        }

        .empty-icon {
            width: 78px;
            height: 78px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 28px;
            background: linear-gradient(135deg, var(--amsa-gold-light), var(--amsa-gold));
            color: #000;
            font-size: 2rem;
            margin-bottom: 18px;
            box-shadow: 0 14px 28px rgba(212, 175, 55, 0.25);
        }

        .amsa-empty-state h4 {
            color: var(--amsa-wine);
            font-weight: 900;
        }

        .amsa-empty-state p {
            color: var(--amsa-muted);
        }

        .description-preview-cell {
            max-width: 300px;
            min-width: 240px;
        }

        .description-preview-text,
        .remarks-preview-text {
            color: var(--amsa-text);
            line-height: 1.55;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .view-full-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(107, 29, 29, 0.18);
            background: #fffaf0;
            color: var(--amsa-wine);
            font-size: 0.78rem;
            font-weight: 800;
            transition: all 0.22s ease;
        }

        .view-full-btn:hover {
            background: var(--amsa-gold);
            color: #000;
            transform: translateY(-1px);
        }

        .full-description-box {
            background: #fffaf0;
            border: 1px solid rgba(107, 29, 29, 0.12);
            border-radius: 18px;
            padding: 18px;
            color: var(--amsa-text);
            line-height: 1.75;
            white-space: pre-wrap;
            word-break: break-word;
            text-align: left;
        }

        /* Fix modal z-index and backdrop issues */
        .modal {
            z-index: 1065 !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }


        @media (max-width: 991px) {
            .points-hero {
                padding: 70px 0 85px !important;
                border-radius: 0 0 30px 30px;
            }

            .points-page-shell {
                margin-top: -40px;
            }

            .point-card h2 {
                font-size: 3rem;
            }
        }

        @media (max-width: 575px) {
            .points-hero {
                padding: 58px 0 75px !important;
            }

            .points-hero h1 {
                font-size: 2.2rem;
            }

            .points-hero p {
                font-size: 0.98rem;
            }

            .hero-icon {
                width: 58px;
                height: 58px;
                font-size: 1.45rem;
            }

            .point-card,
            .points-help-card,
            .points-form-card .card-body {
                padding: 22px;
            }

            .points-form-card .card-header {
                padding: 20px 22px;
            }

            .points-section-header {
                display: block;
            }

            .points-help-list li {
                gap: 14px;
                padding: 16px;
            }

            .points-help-list .help-icon {
                min-width: 50px;
                height: 50px;
                font-size: 1.35rem;
            }

            .amsa-table tbody td,
            .amsa-table thead th {
                padding: 14px;
            }
        }
    </style>
</head>

<body class="points-page">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid points-hero">
        <div class="container text-center">
            <div class="hero-icon">
                <i class="fas fa-trophy"></i>
            </div>

            <div class="hero-badge">
                <i class="fas fa-star"></i>
                <span>AMSA Points Portal</span>
            </div>

            <h1 class="display-4 text-white">Point Request System</h1>
            <p>
                Submit your activities, upload valid evidence, and earn AMSA points after admin approval.
            </p>
        </div>
    </div>

    <div class="container points-page-shell mb-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-4">
                <div class="point-card text-center">
                    <div class="point-card-content">
                        <div class="points-trophy">
                            <i class="fas fa-star"></i>
                        </div>

                        <p class="points-label">Your Total Points</p>
                        <h2><?php echo (int) $userPoints; ?></h2>
                        <p class="points-subtitle">
                            Keep participating and earn more rewards.
                        </p>
                    </div>
                </div>

                <div class="amsa-card points-help-card">
                    <h5>
                        <i class="fas fa-info me-2"></i>
                        Before You Submit
                    </h5>

                    <ul class="points-help-list">
                        <li>
                            <span class="help-icon">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <span>
                                <strong>Choose Correct Category</strong>
                                <span>Select the category that best matches your activity.</span>
                            </span>
                        </li>

                        <li>
                            <span class="help-icon">
                                <i class="fas fa-file-upload"></i>
                            </span>
                            <span>
                                <strong>Upload Valid Evidence</strong>
                                <span>Provide clear proof as PDF, JPG, JPEG, or PNG.</span>
                            </span>
                        </li>

                        <li>
                            <span class="help-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </span>
                            <span>
                                <strong>Await Admin Review</strong>
                                <span>Your request will remain pending until reviewed.</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card amsa-card points-form-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i>
                            Submit Point Request
                        </h5>
                    </div>

                    <div class="card-body">
                        <?php if (!$canSubmitPointRequest): ?>
                            <div class="alert alert-warning amsa-alert amsa-alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Admin accounts cannot submit point requests. Please use a member account to submit activities.
                            </div>
                        <?php else: ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success amsa-alert amsa-alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger amsa-alert amsa-alert-error">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data">
                                <?php echo csrfInput(); ?>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-list"></i>
                                        Activity Type
                                    </label>

                                    <select name="point_category_id" class="form-select amsa-form-control" required>
                                        <option value="">Choose an activity...</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo (int) $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                (<?php echo (int) $category['points']; ?> points)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <small class="amsa-upload-hint">
                                        Choose the category that matches your evidence and activity description.
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-align-left"></i>
                                        Activity Description
                                    </label>

                                    <textarea
                                        name="description"
                                        class="form-control amsa-form-control"
                                        rows="5"
                                        placeholder="Describe your participation or achievement..."
                                        required></textarea>
                                </div>

                                <div class="mb-4 points-upload-box">
                                    <div class="upload-mini-title">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Evidence Upload
                                    </div>

                                    <input
                                        type="file"
                                        name="eop_evidence"
                                        class="form-control amsa-form-control"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required>

                                    <small class="amsa-upload-hint">
                                        Allowed: PDF, JPG, JPEG, PNG. Maximum size: 5MB.
                                    </small>
                                </div>

                                <button type="submit" class="btn amsa-btn amsa-btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Submit Point Request
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="points-section-header">
                    <div>
                        <h3>
                            <i class="fas fa-clipboard-list me-2"></i>
                            Your Point Requests History
                        </h3>
                        <p>Review your submitted activities and admin decisions.</p>
                    </div>
                </div>

                <?php if (empty($userRequests)): ?>
                    <div class="amsa-empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>

                        <h4>No Requests Yet</h4>
                        <p class="mb-0">
                            You have not submitted any point requests yet. Submit your first activity using the form above.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="amsa-card">
                        <div class="table-responsive amsa-table-wrap">
                            <table class="table align-middle amsa-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Description</th>
                                        <th>Requested Date</th>
                                        <th>Evidence</th>
                                        <th>Reviewed Date</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($userRequests as $request): ?>
                                        <?php
                                            $decodedDescription = amsaDecodeText($request['description'] ?? '');
                                            $descriptionPreview = amsaTextPreview($request['description'] ?? '', 85);
                                            $descriptionIsLong = amsaTextIsLong($request['description'] ?? '', 85);
                                            $descriptionModalId = 'descriptionModal' . (int) $request['id'];

                                            $decodedRemarks = amsaDecodeText($request['admin_remarks'] ?? '-');
                                            $remarksPreview = amsaTextPreview($request['admin_remarks'] ?? '-', 55);
                                            $remarksIsLong = amsaTextIsLong($request['admin_remarks'] ?? '-', 55);
                                            $remarksModalId = 'remarksModal' . (int) $request['id'];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['category_name']); ?></strong>
                                            </td>

                                            <td class="description-preview-cell">
                                                <div class="description-preview-text">
                                                    <?php echo nl2br(htmlspecialchars($descriptionPreview, ENT_QUOTES, 'UTF-8')); ?>
                                                </div>
                                                <?php if ($descriptionIsLong): ?>
                                                    <button type="button" class="btn view-full-btn" data-bs-toggle="modal" data-bs-target="#<?php echo htmlspecialchars($descriptionModalId); ?>">
                                                        <i class="fas fa-expand-alt"></i> View Full
                                                    </button>
                                                <?php endif; ?>
                                            </td>

                                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>

                                            <td>
                                                <?php if (!empty($request['eop_evidence'])): ?>
                                                    <a
                                                        href="evidence.php?id=<?php echo (int) $request['id']; ?>"
                                                        target="_blank"
                                                        class="evidence-btn">
                                                        <i class="fas fa-eye"></i>
                                                        View
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php echo !empty($request['review_date']) ? date('M d, Y', strtotime($request['review_date'])) : 'N/A'; ?>
                                            </td>

                                            <td class="description-preview-cell">
                                                <div class="remarks-preview-text">
                                                    <?php echo nl2br(htmlspecialchars($remarksPreview, ENT_QUOTES, 'UTF-8')); ?>
                                                </div>
                                                <?php if ($remarksIsLong): ?>
                                                    <button type="button" class="btn view-full-btn" data-bs-toggle="modal" data-bs-target="#<?php echo htmlspecialchars($remarksModalId); ?>">
                                                        <i class="fas fa-expand-alt"></i> View Full
                                                    </button>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <span class="amsa-badge amsa-badge-<?php echo htmlspecialchars($request['status']); ?> status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <i class="fas fa-clock"></i>
                                                    <?php elseif ($request['status'] === 'approved'): ?>
                                                        <i class="fas fa-check-circle"></i>
                                                    <?php elseif ($request['status'] === 'rejected'): ?>
                                                        <i class="fas fa-times-circle"></i>
                                                    <?php endif; ?>

                                                    <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($userRequests)): ?>
        <!-- All modals are placed outside the table and even outside the .container to avoid any Bootstrap nesting issues -->
        <?php foreach ($userRequests as $request): ?>
            <?php
                $decodedDescription = amsaDecodeText($request['description'] ?? '');
                $descriptionModalId = 'descriptionModal' . (int) $request['id'];
                $descriptionIsLong = amsaTextIsLong($request['description'] ?? '', 85);

                $decodedRemarks = amsaDecodeText($request['admin_remarks'] ?? '-');
                $remarksModalId = 'remarksModal' . (int) $request['id'];
                $remarksIsLong = amsaTextIsLong($request['admin_remarks'] ?? '-', 55);
            ?>
            <?php if ($descriptionIsLong): ?>
                <div class="modal fade" id="<?php echo htmlspecialchars($descriptionModalId); ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Full Activity Description</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2"><strong>Activity:</strong> <?php echo htmlspecialchars($request['category_name']); ?></p>
                                <p class="mb-3"><strong>Requested Date:</strong> <?php echo date('M d, Y', strtotime($request['request_date'])); ?></p>
                                <div class="full-description-box"><?php echo nl2br(htmlspecialchars($decodedDescription, ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($remarksIsLong): ?>
                <div class="modal fade" id="<?php echo htmlspecialchars($remarksModalId); ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Full Admin Remarks</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2"><strong>Activity:</strong> <?php echo htmlspecialchars($request['category_name']); ?></p>
                                <p class="mb-3"><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($request['status'])); ?></p>
                                <div class="full-description-box"><?php echo nl2br(htmlspecialchars($decodedRemarks, ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            // Ensure that any lingering modal backdrops are cleaned up when a modal is hidden.
            document.addEventListener('hidden.bs.modal', function () {
                // Remove any stray backdrop elements.
                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.remove();
                });
                // Restore body styles that Bootstrap might have left behind.
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });

            // Additional safety: if any modal fails to close properly via backdrop click,
            // manually clean up after 300ms.
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                modal.addEventListener('hide.bs.modal', function() {
                    setTimeout(function() {
                        if (document.querySelectorAll('.modal-backdrop').length > 0) {
                            document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                                backdrop.remove();
                            });
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }
                    }, 150);
                });
            });
        })();
    </script>
</body>
</html>