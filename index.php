<?php
require_once 'config/database.php';

function homeCount($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    return (int) ($result->fetch_assoc()['total'] ?? 0);
}

function homeLatestPostsByCategories($categories, $limit = 3) {
    global $conn;
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $types = str_repeat('s', count($categories)) . 'i';
    $stmt = $conn->prepare("
        SELECT p.*
        FROM post p
        WHERE p.category IN ($placeholders)
        ORDER BY p.upload_date DESC, p.id DESC
        LIMIT ?
    ");
    $params = array_merge($categories, [(int) $limit]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function homeLatestPosts($limit = 3) {
    return homeLatestPostsByCategories(['news', 'announcement', 'workshop', 'volunteer', 'community_engagement', 'achievement'], $limit);
}

function homePostImage($postId, $fallback = 'img/blog-1.jpg') {
    $images = getEventImages($postId);
    return !empty($images) ? 'uploads/' . basename($images[0]['img_name']) : $fallback;
}

function homeExcerpt($content, $length = 120) {
    $text = trim(strip_tags(htmlspecialchars_decode($content ?? '')));
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

function homeCategoryLabel($category) {
    if ($category === 'community_engagement') {
        return 'Event';
    }
    return ucwords(str_replace('_', ' ', $category));
}

$memberCount = homeCount("SELECT COUNT(*) AS total FROM user WHERE role = 'member' AND status = 'active'");
$eventsNewsCount = homeCount("SELECT COUNT(*) AS total FROM post WHERE category IN ('news', 'announcement', 'workshop', 'volunteer', 'community_engagement')");
$projectCount = homeCount("SELECT COUNT(*) AS total FROM post WHERE category = 'community_engagement'");
$achievementCount = homeCount("SELECT COUNT(*) AS total FROM post WHERE category = 'achievement'");
$latestEventsNews = homeLatestPostsByCategories(['news', 'announcement', 'workshop', 'volunteer', 'community_engagement'], 3);
$latestAchievements = homeLatestPostsByCategories(['achievement'], 3);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AMSA AIU | Home</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Official website of AMSA AIU, the Myanmar student association at Albukhary International University." name="description">

    <!-- Favicon -->
    <link href="img/logo.png" rel="icon" type="image/png">
    <link href="img/logo.png" rel="apple-touch-icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <link href="assets/css/amsa-chatbot.css" rel="stylesheet">
    <style>
        .home-preview-grid > [class*="col-"] {
            display: flex;
        }

        .home-preview-card {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .home-preview-card .blog-img {
            aspect-ratio: 16 / 9;
            width: 100%;
            height: auto;
            flex: 0 0 auto;
            overflow: hidden;
        }

        .home-preview-card .blog-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .home-preview-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .home-preview-body h4 {
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .home-preview-body p {
            line-height: 1.65;
            overflow-wrap: anywhere;
        }

        .home-preview-body > a.text-uppercase {
            margin-top: auto;
            align-self: flex-start;
        }
    </style>
</head>
<body>
    <!-- Topbar Start -->
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
    <!-- Topbar End -->

    <!-- Navbar & Carousel Start -->
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
                    <a href="index.html" class="nav-item nav-link active">Home</a>
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
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Projects</a>
                        <div class="dropdown-menu m-0">
                            <a href="cme.php" class="dropdown-item">Community Engagements</a>
                            <a href="fundraising.php" class="dropdown-item">Fundraising</a>
                        </div>
                    </div>
                    <a href="contact.html" class="nav-item nav-link">Contact</a>
                    <a href="devteam.html" class="nav-item nav-link">Dev Team</a>
                </div>
                <a href="point/register.php" class="btn btn-primary amsa-btn amsa-btn-primary py-2 px-4 ms-3">Register</a>
            </div>
        </nav>

        <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100" src="img/pj_hope.JPG" alt="AMSA Project Hope activity">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-3 animated slideInDown">We Rise by Lifting Others</h5>
                            <h1 class="display-1 text-white mb-md-4 animated zoomIn">AMSA AIU</h1>
                            <p class="text-white mb-4 animated slideInUp">Supporting Myanmar Students at Albukhary International University through leadership, community, culture, and service.</p>
                            <a href="about.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5 me-2">Explore AMSA</a>
                            <a href="committee.html" class="btn btn-outline-light amsa-btn amsa-btn-ghost py-3 px-5">Meet Our Committee</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100" src="img/pj_hope_4.JPG" alt="AMSA community activity">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-3 animated slideInDown">We Rise by Lifting Others</h5>
                            <h1 class="display-1 text-white mb-md-4 animated zoomIn">AMSA AIU</h1>
                            <p class="text-white mb-4 animated slideInUp">Supporting Myanmar Students at Albukhary International University through leadership, community, culture, and service.</p>
                            <a href="about.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5 me-2">Explore AMSA</a>
                            <a href="committee.html" class="btn btn-outline-light amsa-btn amsa-btn-ghost py-3 px-5">Meet Our Committee</a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
    <!-- Navbar & Carousel End -->

    <!-- About Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="section-title position-relative pb-3 mb-4">
                        <h5 class="fw-bold text-primary text-uppercase">About AMSA AIU</h5>
                        <h1 class="mb-0">A supportive Myanmar student community at AIU</h1>
                    </div>
                    <p class="mb-4">AMSA AIU brings Myanmar students together at Albukhary International University through leadership, community support, cultural engagement, and student welfare initiatives.</p>
                    <p class="mb-4">Our mission is to help members feel supported academically, socially, and personally while encouraging service, responsibility, and active participation in university life. Our vision is a connected student association where every member can grow, contribute, and represent Myanmar with pride.</p>
                    <a href="about.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">Learn More</a>
                </div>
                <div class="col-lg-6">
                    <img class="img-fluid rounded wow zoomIn" data-wow-delay="0.3s" src="img/aboutus.jpg" alt="AMSA AIU student community">
                </div>
            </div>
        </div>
    </div>
    <!-- About Preview End -->

    <!-- Top Management Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 700px;">
                <h5 class="fw-bold text-primary text-uppercase">Top Management</h5>
                <h1 class="mb-0">Student leadership guiding AMSA activities</h1>
            </div>
            <div class="row g-5">
                <div class="col-lg-3 col-md-6 wow slideInUp" data-wow-delay="0.2s">
                    <div class="team-item bg-light rounded overflow-hidden amsa-card text-center p-4">
                        <i class="fa fa-user-tie text-primary mb-3" style="font-size: 42px;"></i>
                        <h5 class="text-primary">Ei Nandar Soe</h5>
                        <p class="text-uppercase m-0">President</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow slideInUp" data-wow-delay="0.3s">
                    <div class="team-item bg-light rounded overflow-hidden amsa-card text-center p-4">
                        <i class="fa fa-users text-primary mb-3" style="font-size: 42px;"></i>
                        <h5 class="text-primary">Soe Min Si Thu</h5>
                        <p class="text-uppercase m-0">Vice President</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow slideInUp" data-wow-delay="0.4s">
                    <div class="team-item bg-light rounded overflow-hidden amsa-card text-center p-4">
                        <i class="fa fa-file-alt text-primary mb-3" style="font-size: 42px;"></i>
                        <h5 class="text-primary">Yamin Wah Wah Soe Aung</h5>
                        <p class="text-uppercase m-0">Secretary</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow slideInUp" data-wow-delay="0.5s">
                    <div class="team-item bg-light rounded overflow-hidden amsa-card text-center p-4">
                        <i class="fa fa-hand-holding-usd text-primary mb-3" style="font-size: 42px;"></i>
                        <h5 class="text-primary">Hmuu Thet Pan Kyaw</h5>
                        <p class="text-uppercase m-0">Treasurer</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="committee.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">View Full Committee</a>
            </div>
        </div>
    </div>
    <!-- Top Management Preview End -->

    <!-- Events News Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 700px;">
                <h5 class="fw-bold text-primary text-uppercase">Events & News</h5>
                <h1 class="mb-0">Latest association updates and activities</h1>
            </div>
            <?php if (empty($latestEventsNews)): ?>
                <div class="amsa-empty-state text-center bg-light rounded p-5">
                    <h4 class="mb-2">No Events Or News Yet</h4>
                    <p class="mb-0">Published AMSA updates will appear here.</p>
                </div>
            <?php else: ?>
                <div class="row g-5 home-preview-grid">
                    <?php foreach ($latestEventsNews as $index => $post): ?>
                        <?php
                        $category = $post['category'] ?? '';
                        $targetPage = $category === 'community_engagement' ? 'cme.php' : 'events.php';
                        $delay = 0.3 + ($index * 0.3);
                        ?>
                        <div class="col-lg-4 wow slideInUp" data-wow-delay="<?php echo htmlspecialchars(number_format($delay, 1)); ?>s">
                            <div class="blog-item bg-light rounded overflow-hidden home-preview-card">
                                <div class="blog-img position-relative overflow-hidden">
                                    <img class="img-fluid" src="<?php echo htmlspecialchars(homePostImage((int) $post['id'], 'img/blog-1.jpg')); ?>" alt="<?php echo htmlspecialchars($post['title'] ?? 'AMSA event or news'); ?>">
                                    <a class="position-absolute top-0 start-0 bg-primary text-white rounded-end mt-5 py-2 px-4" href="<?php echo htmlspecialchars($targetPage); ?>"><?php echo htmlspecialchars(homeCategoryLabel($category)); ?></a>
                                </div>
                                <div class="p-4 home-preview-body">
                                    <div class="d-flex mb-3">
                                        <small><i class="far fa-calendar-alt text-primary me-2"></i><?php echo htmlspecialchars(date('d M, Y', strtotime($post['upload_date'] ?? 'now'))); ?></small>
                                    </div>
                                    <h4 class="mb-3"><?php echo htmlspecialchars($post['title'] ?? 'AMSA Update'); ?></h4>
                                    <p><?php echo htmlspecialchars(homeExcerpt($post['content'] ?? '')); ?></p>
                                    <a class="text-uppercase" href="<?php echo htmlspecialchars($targetPage); ?>">Read More <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-5">
                <a href="events.php" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">View All Events & News</a>
            </div>
        </div>
    </div>
    <!-- Events News Preview End -->

    <!-- Achievements Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 700px;">
                <h5 class="fw-bold text-primary text-uppercase">Achievements</h5>
                <h1 class="mb-0">Milestones and student recognition</h1>
            </div>
            <?php if (empty($latestAchievements)): ?>
                <div class="amsa-empty-state text-center bg-light rounded p-5">
                    <h4 class="mb-2">No Achievements Yet</h4>
                    <p class="mb-0">AMSA achievements will appear here once published.</p>
                </div>
            <?php else: ?>
                <div class="row g-5 home-preview-grid">
                    <?php foreach ($latestAchievements as $index => $post): ?>
                        <?php $delay = 0.3 + ($index * 0.3); ?>
                        <div class="col-lg-4 wow slideInUp" data-wow-delay="<?php echo htmlspecialchars(number_format($delay, 1)); ?>s">
                            <div class="blog-item bg-light rounded overflow-hidden home-preview-card">
                                <div class="blog-img position-relative overflow-hidden">
                                    <img class="img-fluid" src="<?php echo htmlspecialchars(homePostImage((int) $post['id'], 'img/Culture_Night.jpeg')); ?>" alt="<?php echo htmlspecialchars($post['title'] ?? 'AMSA achievement'); ?>">
                                    <a class="position-absolute top-0 start-0 bg-primary text-white rounded-end mt-5 py-2 px-4" href="achievements.php">Achievement</a>
                                </div>
                                <div class="p-4 home-preview-body">
                                    <h4 class="mb-3"><?php echo htmlspecialchars($post['title'] ?? 'AMSA Achievement'); ?></h4>
                                    <p><?php echo htmlspecialchars(homeExcerpt($post['content'] ?? '')); ?></p>
                                    <a class="text-uppercase" href="achievements.php">Read More <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-5">
                <a href="achievements.php" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">View Achievements</a>
            </div>
        </div>
    </div>
    <!-- Achievements Preview End -->

    <!-- Community Engagement Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <img class="img-fluid rounded wow zoomIn" data-wow-delay="0.3s" src="img/pj_hope_4.JPG" alt="AMSA community engagement activity">
                </div>
                <div class="col-lg-6">
                    <div class="section-title position-relative pb-3 mb-4">
                        <h5 class="fw-bold text-primary text-uppercase">Community Engagement</h5>
                        <h1 class="mb-0">Service, welfare, volunteer work, and culture</h1>
                    </div>
                    <p class="mb-4">AMSA community engagement connects students with meaningful service opportunities, student welfare support, volunteer participation, and cultural activities that strengthen the wider AIU community.</p>
                    <a href="cme.php" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">Explore Community Engagement</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Community Engagement Preview End -->

    <!-- Fundraising Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="section-title position-relative pb-3 mb-4">
                        <h5 class="fw-bold text-primary text-uppercase">Fundraising</h5>
                        <h1 class="mb-0">Support for students and community initiatives</h1>
                    </div>
                    <p class="mb-4">AMSA fundraising helps coordinate student assistance, emergency support, welfare needs, and community fundraising initiatives with transparency and care.</p>
                    <a href="fundraising.php" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">Learn About Fundraising</a>
                </div>
                <div class="col-lg-6">
                    <img class="img-fluid rounded wow zoomIn" data-wow-delay="0.3s" src="img/pj_hope.JPG" alt="AMSA fundraising and student support">
                </div>
            </div>
        </div>
    </div>
    <!-- Fundraising Preview End -->

    <!-- Developer Team Preview Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <img class="img-fluid rounded wow zoomIn" data-wow-delay="0.3s" src="img/team-1.jpg" alt="AMSA website development team">
                </div>
                <div class="col-lg-6">
                    <div class="section-title position-relative pb-3 mb-4">
                        <h5 class="fw-bold text-primary text-uppercase">Developer Team</h5>
                        <h1 class="mb-0">Website contributors behind AMSA online systems</h1>
                    </div>
                    <p class="mb-4">The AMSA website development team maintains the public website, member points system, admin tools, and digital workflows that support association operations.</p>
                    <a href="devteam.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5">Meet The Developers</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Developer Team Preview End -->

    <!-- Impact Start -->
    <div class="container-fluid facts py-5 pt-lg-0">
        <div class="container py-5 pt-lg-0">
            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 700px;">
                <h5 class="fw-bold text-primary text-uppercase">AMSA Impact</h5>
                <h1 class="mb-0">A snapshot of association activity</h1>
            </div>
            <div class="row gx-0">
                <div class="col-lg-3 col-md-6 wow zoomIn" data-wow-delay="0.1s">
                    <div class="bg-dark shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-users text-dark"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-white mb-0">Members Supported</h5>
                            <h1 class="text-white mb-0" data-toggle="counter-up"><?php echo (int) $memberCount; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow zoomIn" data-wow-delay="0.2s">
                    <div class="bg-light shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-dark d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-calendar text-white"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-dark mb-0">Events Organized</h5>
                            <h1 class="mb-0" data-toggle="counter-up"><?php echo (int) $eventsNewsCount; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow zoomIn" data-wow-delay="0.3s">
                    <div class="bg-dark shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-hands-helping text-dark"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-white mb-0">Community Programs</h5>
                            <h1 class="text-white mb-0" data-toggle="counter-up"><?php echo (int) $projectCount; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow zoomIn" data-wow-delay="0.4s">
                    <div class="bg-light shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
                        <div class="bg-dark d-flex align-items-center justify-content-center rounded mb-2" style="width: 60px; height: 60px;">
                            <i class="fa fa-award text-white"></i>
                        </div>
                        <div class="ps-4">
                            <h5 class="text-dark mb-0">Achievements</h5>
                            <h1 class="mb-0" data-toggle="counter-up"><?php echo (int) $achievementCount; ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Impact End -->

        <!-- Footer Start -->
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
    <!-- Footer End -->
    
    
        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded back-to-top"><i class="bi bi-arrow-up"></i></a>
    
    
        <!-- JavaScript Libraries -->
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="lib/wow/wow.min.js"></script>
        <script src="lib/easing/easing.min.js"></script>
        <script src="lib/waypoints/waypoints.min.js"></script>
        <script src="lib/counterup/counterup.min.js"></script>
        <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    
        <!-- Template Javascript -->
        <script src="js/main.js"></script>
        <script src="assets/js/amsa-chatbot.js"></script>
        <script>
            window.AmsaChatbot && window.AmsaChatbot.init({ preset: 'public' });
        </script>
    </body>
    
    </html>
