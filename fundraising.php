<?php
require_once 'config/database.php';
ensureFundraisingTables();

function fundraisingText($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fundraisingPhotoLabel($count) {
    $count = (int) $count;
    return $count . ' ' . ($count === 1 ? 'Photo' : 'Photos');
}

$perPage = 6;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $conn->prepare("
    SELECT COUNT(DISTINCT f.id) AS total
    FROM fundraising f
    INNER JOIN fundraising_images fi ON fi.fundraising_id = f.id
    WHERE f.status = 'published'
");

$totalItems = 0;
if ($countStmt) {
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $totalItems = (int) ($countResult['total'] ?? 0);
}

$totalPages = max(1, (int) ceil($totalItems / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;
$fundraisingIds = [];

$idStmt = $conn->prepare("
    SELECT DISTINCT f.id
    FROM fundraising f
    INNER JOIN fundraising_images fi ON fi.fundraising_id = f.id
    WHERE f.status = 'published'
    ORDER BY f.created_at DESC, f.id DESC
    LIMIT ? OFFSET ?
");

if ($idStmt) {
    $idStmt->bind_param("ii", $perPage, $offset);
    $idStmt->execute();
    $idResult = $idStmt->get_result();

    while ($row = $idResult->fetch_assoc()) {
        $fundraisingIds[] = (int) $row['id'];
    }
}

$fundraisingItems = [];

if (!empty($fundraisingIds)) {
    $safeIds = implode(',', array_map('intval', $fundraisingIds));

    $stmt = $conn->prepare("
        SELECT f.id, f.title, f.description, f.created_at,
               fi.id AS image_id, fi.image_path, fi.display_order
        FROM fundraising f
        INNER JOIN fundraising_images fi ON fi.fundraising_id = f.id
        WHERE f.status = 'published'
        AND f.id IN ($safeIds)
        ORDER BY f.created_at DESC, f.id DESC, fi.display_order ASC, fi.id ASC
    ");

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $id = (int) $row['id'];

            if (!isset($fundraisingItems[$id])) {
                $fundraisingItems[$id] = [
                    'id' => $id,
                    'title' => $row['title'],
                    'description' => $row['description'] ?? '',
                    'created_at' => $row['created_at'],
                    'images' => [],
                ];
            }

            if (!empty($row['image_path'])) {
                $fundraisingItems[$id]['images'][] = [
                    'id' => (int) $row['image_id'],
                    'path' => ltrim(str_replace('\\', '/', $row['image_path']), '/'),
                ];
            }
        }
    }
}

$fundraisingItems = array_values(array_filter($fundraisingItems, function ($item) {
    return !empty($item['images']);
}));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AMSA AIU | Fundraising</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="AMSA fundraising, student association, community support" name="keywords">
    <meta content="AMSA AIU fundraising activities and community support photo gallery." name="description">

    <link href="img/logo.png" rel="icon" type="image/png">
    <link href="img/logo.png" rel="apple-touch-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <style>
        :root {
            --fundraising-maroon: #4b0717;
            --fundraising-gold: #c6a22a;
            --fundraising-soft: #fff8ea;
            --fundraising-border: #eadbd2;
        }

        .fundraising-section {
            background: linear-gradient(180deg, #fff 0%, #fffaf0 100%);
        }

        .fundraising-intro {
            background: #fff;
            border: 1px solid var(--fundraising-border);
            border-radius: 14px;
            box-shadow: 0 14px 34px rgba(75, 7, 23, 0.08);
            padding: 35px;
            margin-bottom: 45px;
        }

        .fundraising-intro h2 {
            color: var(--fundraising-maroon);
            font-weight: 800;
        }

        .fundraising-intro p {
            color: #667085;
            max-width: 850px;
            margin: 0 auto 28px;
        }

        .fundraising-intro-card {
            background: linear-gradient(180deg, #fffaf0, #ffffff);
            border: 1px solid rgba(198, 162, 42, 0.28);
            border-radius: 14px;
            padding: 24px 18px;
            height: 100%;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .fundraising-intro-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 26px rgba(75, 7, 23, 0.10);
        }

        .fundraising-intro-icon {
            width: 58px;
            height: 58px;
            background: var(--fundraising-maroon);
            color: var(--fundraising-gold);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            margin-bottom: 14px;
        }

        .fundraising-intro-card h5 {
            color: var(--fundraising-maroon);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .fundraising-intro-card span {
            color: #667085;
            font-size: 0.92rem;
        }

        .fundraising-card {
            background: #fff;
            border: 1px solid var(--fundraising-border);
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(75, 7, 23, 0.09);
            height: 100%;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .fundraising-card:hover {
            box-shadow: 0 18px 40px rgba(75, 7, 23, 0.14);
            transform: translateY(-4px);
        }

        .fundraising-cover {
            height: 240px;
            object-fit: cover;
            width: 100%;
        }

        .fundraising-photo-badge {
            background: var(--fundraising-soft);
            border: 1px solid rgba(198, 162, 42, 0.35);
            border-radius: 999px;
            color: var(--fundraising-maroon);
            font-size: 0.82rem;
            font-weight: 800;
            padding: 6px 12px;
        }

        .fundraising-card h3 {
            color: var(--fundraising-maroon);
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .fundraising-card p {
            color: #667085;
        }

        .fundraising-empty {
            background: #fff;
            border: 1px dashed var(--fundraising-border);
            border-radius: 8px;
            color: #667085;
            padding: 56px 24px;
            text-align: center;
        }

        .fundraising-empty i {
            color: var(--fundraising-gold);
        }

        .fundraising-modal-image,
        .fundraising-carousel-image {
            background: #f7f4ef;
            max-height: 70vh;
            object-fit: contain;
            width: 100%;
        }

        .fundraising-pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }

        .fundraising-pagination .page-link {
            color: var(--fundraising-maroon);
            border-color: var(--fundraising-border);
            font-weight: 700;
        }

        .fundraising-pagination .page-item.active .page-link {
            background: var(--fundraising-maroon);
            border-color: var(--fundraising-maroon);
            color: #fff;
        }

        .fundraising-pagination .page-link:hover {
            background: var(--fundraising-soft);
            color: var(--fundraising-maroon);
        }

        .modal-title {
            color: var(--fundraising-maroon);
            font-weight: 800;
        }

        @media (max-width: 575.98px) {
            .fundraising-cover {
                height: 220px;
            }

            .fundraising-intro {
                padding: 25px 18px;
            }
        }
    </style>
</head>

<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner"></div>
    </div>

    <div class="container-fluid bg-dark px-5 d-none d-lg-block">
        <div class="row gx-0">
            <div class="col-lg-8 text-center text-lg-start mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <small class="me-3 text-light"><i class="fa fa-map-marker-alt me-2"></i>Albukhary International University</small>
                    <small class="text-light"><i class="fa fa-envelope-open me-2"></i><a class="text-light" href="mailto:amsa@student.aiu.edu.my">amsa@student.aiu.edu.my</a></small>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="https://www.facebook.com/amsa.aiu/about/?locale=ms_MY&_rdr" target="_blank" rel="noopener" aria-label="AMSA Facebook"><i class="fab fa-facebook-f fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="https://my.linkedin.com/company/amsa-aiu-myanmar-student-association" target="_blank" rel="noopener" aria-label="AMSA LinkedIn"><i class="fab fa-linkedin-in fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href="https://www.instagram.com/amsa_aiu/" target="_blank" rel="noopener" aria-label="AMSA Instagram"><i class="fab fa-instagram fw-normal"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
            <a href="index.html" class="navbar-brand p-0">
                <img src="img/logo.png" alt="AMSA" class="navbar-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="index.html" class="nav-item nav-link">Home</a>
                    <a href="events.php" class="nav-item nav-link">Events & News</a>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">About</a>
                        <div class="dropdown-menu m-0">
                            <a href="about.html" class="dropdown-item">About Us</a>
                            <a href="achievements.php" class="dropdown-item">Achievements</a>
                            <a href="committee.html" class="dropdown-item">Top Management</a>
                        </div>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Projects</a>
                        <div class="dropdown-menu m-0">
                            <a href="cme.php" class="dropdown-item">Community Engagements</a>
                            <a href="fundraising.php" class="dropdown-item active">Fundraising</a>
                        </div>
                    </div>
                    <a href="contact.html" class="nav-item nav-link">Contact</a>
                    <a href="devteam.html" class="nav-item nav-link">Dev Team</a>
                </div>
                <a href="point/login.php" class="btn amsa-btn amsa-login-btn py-2 px-4 ms-3">Login</a>
                <a href="point/register.php" class="btn btn-primary amsa-btn amsa-btn-primary amsa-register-btn py-2 px-4 ms-3">Register</a>
            </div>
        </nav>

        <div class="container-fluid bg-primary py-5 bg-header amsa-page-header hero-fundraising" style="margin-bottom: 90px;">
            <div class="row py-5">
                <div class="col-12 pt-lg-5 mt-lg-5 text-center">
                    <h1 class="display-1 text-white animated zoomIn">Fundraising</h1>
                    <p class="amsa-page-subtitle animated fadeInUp">Photo stories from AMSA fundraising activities and student-led community support.</p>
                    <a href="index.html" class="h5 text-white">Home</a>
                    <i class="far fa-circle text-white px-2"></i>
                    <span class="h5 text-white">Fundraising</span>
                </div>
            </div>
        </div>
    </div>

    <section class="container-fluid py-5 fundraising-section wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">

            <div class="fundraising-intro text-center">
                <h2 class="mb-3">Supporting meaningful AMSA initiatives</h2>
                <p>
                    AMSA fundraising activities help strengthen student welfare, community support, and volunteer-led programs.
                    Through these efforts, AIU Myanmar Students' Association continues to create positive impact for students and the wider community.
                </p>

                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="fundraising-intro-card">
                            <div class="fundraising-intro-icon">
                                <i class="fa fa-hands-helping"></i>
                            </div>
                            <h5>Community Support</h5>
                            <span>Helping communities through student-led fundraising actions.</span>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="fundraising-intro-card">
                            <div class="fundraising-intro-icon">
                                <i class="fa fa-user-graduate"></i>
                            </div>
                            <h5>Student Welfare</h5>
                            <span>Supporting students through meaningful care and assistance.</span>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="fundraising-intro-card">
                            <div class="fundraising-intro-icon">
                                <i class="fa fa-users"></i>
                            </div>
                            <h5>Volunteer Effort</h5>
                            <span>Encouraging teamwork, service, and active participation.</span>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="fundraising-intro-card">
                            <div class="fundraising-intro-icon">
                                <i class="fa fa-chart-line"></i>
                            </div>
                            <h5>Transparent Impact</h5>
                            <span>Sharing activity outcomes through photos and updates.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 720px;">
                <h5 class="fw-bold text-primary text-uppercase">AMSA Fundraising</h5>
                <h1 class="mb-0">Fundraising activities by AIU Myanmar Students' Association (AMSA)</h1>
            </div>

            <?php if (empty($fundraisingItems)): ?>
                <div class="fundraising-empty">
                    <i class="fa fa-hand-holding-heart fa-3x mb-3"></i>
                    <h4 class="mb-2">No fundraising activities have been posted yet.</h4>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($fundraisingItems as $item): ?>
                        <?php
                        $images = $item['images'];
                        $photoCount = count($images);
                        $modalId = 'fundraisingModal' . (int) $item['id'];
                        $carouselId = 'fundraisingCarousel' . (int) $item['id'];
                        $description = trim((string) $item['description']);
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <article class="fundraising-card">
                                <img class="fundraising-cover" src="<?php echo fundraisingText($images[0]['path']); ?>" alt="<?php echo fundraisingText($item['title']); ?>">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                        <span class="fundraising-photo-badge"><i class="far fa-images me-1"></i><?php echo fundraisingPhotoLabel($photoCount); ?></span>
                                    </div>
                                    <h3><?php echo fundraisingText($item['title']); ?></h3>
                                    <?php if ($description !== ''): ?>
                                        <p class="mb-4"><?php echo fundraisingText($description); ?></p>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-primary amsa-btn amsa-btn-primary w-100" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
                                        View
                                    </button>
                                </div>
                            </article>
                        </div>

                        <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title" id="<?php echo $modalId; ?>Label"><?php echo fundraisingText($item['title']); ?></h5>
                                            <small class="text-muted"><?php echo fundraisingPhotoLabel($photoCount); ?></small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if ($photoCount > 1): ?>
                                            <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="false">
                                                <div class="carousel-inner">
                                                    <?php foreach ($images as $index => $image): ?>
                                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                            <img class="fundraising-carousel-image" src="<?php echo fundraisingText($image['path']); ?>" alt="<?php echo fundraisingText($item['title']); ?> photo <?php echo $index + 1; ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Previous</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Next</span>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <img class="fundraising-modal-image" src="<?php echo fundraisingText($images[0]['path']); ?>" alt="<?php echo fundraisingText($item['title']); ?>">
                                        <?php endif; ?>

                                        <?php if ($description !== ''): ?>
                                            <p class="mt-4 mb-0"><?php echo nl2br(fundraisingText($description)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="fundraising-pagination" aria-label="Fundraising pagination">
                        <ul class="pagination">
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="fundraising.php?page=<?php echo max(1, $currentPage - 1); ?>">Previous</a>
                            </li>

                            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="fundraising.php?page=<?php echo $page; ?>">
                                        <?php echo $page; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="fundraising.php?page=<?php echo min($totalPages, $currentPage + 1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="container-fluid bg-dark text-light mt-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-4 col-md-6 footer-about">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-primary p-4">
                        <a href="index.html" class="navbar-brand p-0">
                            <img src="img/logo.png" alt="AMSA AIU" class="navbar-logo">
                        </a>
                        <p class="mt-3 mb-4">AMSA AIU supports Myanmar students at Albukhary International University through community, culture, leadership, and service.</p>
                        <div class="d-flex">
                            <a class="btn btn-primary btn-square me-2" href="https://www.facebook.com/amsa.aiu/about/?locale=ms_MY&_rdr" target="_blank" rel="noopener" aria-label="AMSA Facebook"><i class="fab fa-facebook-f fw-normal"></i></a>
                            <a class="btn btn-primary btn-square me-2" href="https://my.linkedin.com/company/amsa-aiu-myanmar-student-association" target="_blank" rel="noopener" aria-label="AMSA LinkedIn"><i class="fab fa-linkedin-in fw-normal"></i></a>
                            <a class="btn btn-primary btn-square" href="https://www.instagram.com/amsa_aiu/" target="_blank" rel="noopener" aria-label="AMSA Instagram"><i class="fab fa-instagram fw-normal"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-4 col-md-12 pt-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Contact</h3>
                            </div>
                            <div class="d-flex mb-2"><i class="bi bi-geo-alt text-primary me-2"></i><p class="mb-0">Albukhary International University</p></div>
                            <div class="d-flex mb-2"><i class="bi bi-envelope-open text-primary me-2"></i><p class="mb-0"><a class="text-light" href="mailto:amsa@student.aiu.edu.my">amsa@student.aiu.edu.my</a></p></div>
                        </div>

                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">AMSA</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="index.html"><i class="bi bi-arrow-right text-primary me-2"></i>Home</a>
                                <a class="text-light mb-2" href="about.html"><i class="bi bi-arrow-right text-primary me-2"></i>About AMSA</a>
                                <a class="text-light mb-2" href="committee.html"><i class="bi bi-arrow-right text-primary me-2"></i>Committee</a>
                                <a class="text-light" href="contact.html"><i class="bi bi-arrow-right text-primary me-2"></i>Contact</a>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Programs</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="events.php"><i class="bi bi-arrow-right text-primary me-2"></i>Events & News</a>
                                <a class="text-light mb-2" href="achievements.php"><i class="bi bi-arrow-right text-primary me-2"></i>Achievements</a>
                                <a class="text-light mb-2" href="cme.php"><i class="bi bi-arrow-right text-primary me-2"></i>Community Engagement</a>
                                <a class="text-light" href="fundraising.php"><i class="bi bi-arrow-right text-primary me-2"></i>Fundraising</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid text-white footer-copyright" style="background: #320010; border-top: 1px solid rgba(255,255,255,0.08);">
            <div class="container text-center">
                <div class="row justify-content-end">
                    <div class="col-lg-8 col-md-6">
                        <div class="d-flex align-items-center justify-content-center" style="height: 75px;">
                            <p class="mb-0">&copy; <a class="text-white border-bottom" href="index.html">AMSA AIU</a>. All Rights Reserved.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>
