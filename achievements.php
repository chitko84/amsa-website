<?php
require_once 'config/database.php';
$contentPage = max(1, (int) ($_GET['page'] ?? 1));
$contentPerPage = (int) ($_GET['per_page'] ?? 9);
if (!in_array($contentPerPage, [9, 18, 27], true)) {
    $contentPerPage = 9;
}
$achievementPageData = getPostsPaginated(['achievement'], $contentPage, $contentPerPage);
$achievements = $achievementPageData['posts'];
$totalAchievements = $achievementPageData['total_count'];
$contentPage = $achievementPageData['current_page'];
$totalPages = $achievementPageData['total_pages'];
$contentPerPage = $achievementPageData['per_page'];
$testimonials = getAllTestimonials();

function achievementsPageUrl(array $overrides = []) {
    global $contentPerPage, $contentPage;

    $params = array_merge([
        'per_page' => $contentPerPage,
        'page' => $contentPage,
    ], $overrides);
    return 'achievements.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AMSA AIU | Achievements</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Explore AMSA AIU achievements and student community milestones." name="description">

    <!-- Favicon -->
    <link href="img/logo.png" rel="icon" type="image/png">
    <link href="img/logo.png" rel="apple-touch-icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap"
        rel="stylesheet">

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
    
    <style>
        /* Custom styles for achievements page */
        .achievement-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .achievement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .achievement-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #8B3A3A, #B55A4A);
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1;
        }
        
        .achievement-card .position-relative {
            aspect-ratio: 16 / 9;
            width: 100%;
            overflow: hidden;
            flex: 0 0 auto;
        }

        .achievement-card .content-gallery {
            display: grid;
            width: 100%;
            height: 100%;
            gap: 6px;
        }

        .achievement-card .content-gallery.gallery-count-1 {
            grid-template-columns: 1fr;
        }

        .achievement-card .content-gallery.gallery-count-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .achievement-card .content-gallery.gallery-count-3 {
            grid-template-columns: 2fr 1fr;
            grid-template-rows: repeat(2, minmax(0, 1fr));
        }

        .achievement-card .content-gallery.gallery-count-3 a:first-child {
            grid-row: 1 / span 2;
        }

        .achievement-card .content-gallery a,
        .achievement-card .content-gallery img,
        .achievement-card .position-relative > img {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 0;
            object-fit: cover;
        }

        .achievement-card .p-4 {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .achievement-card h4 {
            line-height: 1.35;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .achievement-card p {
            line-height: 1.65;
            margin-bottom: 18px;
            overflow-wrap: anywhere;
        }

        .achievement-card .p-4 .d-flex {
            margin-top: auto !important;
        }
        
        /* Timeline Styles - Keeping Static */
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background: linear-gradient(to bottom, #8B3A3A, #B55A4A);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
            border-radius: 10px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            background: #8B3A3A;
            border-radius: 50%;
            top: 15px;
            z-index: 1;
        }
        
        .left {
            left: 0;
        }
        
        .right {
            left: 50%;
        }
        
        .left::after {
            right: -12px;
        }
        
        .right::after {
            left: -12px;
        }
        
        .timeline-content {
            padding: 20px 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .timeline-content h3 {
            color: #8B3A3A;
            margin-bottom: 10px;
        }
        
        /* Stats Counter */
        .stats-counter {
            background: linear-gradient(135deg, #8B3A3A, #5C2E2E);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-counter:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .stats-counter .counter {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stats-counter .counter-label {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* Testimonial Styles */
        .testimonial-item {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 15px;
            text-align: center;
        }
        
        .testimonial-img {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            position: relative;
        }
        
        .testimonial-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .testimonial-img .quote-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #8B3A3A;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .testimonial-text {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin: 20px 0;
        }
        
        .testimonial-name {
            font-weight: 700;
            color: #2c0410;
            margin-bottom: 5px;
        }
        
        .testimonial-position {
            font-size: 14px;
            color: #8B3A3A;
        }
        
        .rating-stars {
            color: #ffc107;
            margin-bottom: 15px;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 20px;
        }
        
        .no-data i {
            font-size: 64px;
            color: #8B3A3A;
            margin-bottom: 20px;
        }
        
        @media screen and (max-width: 768px) {
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item::after {
                left: 18px;
            }
            
            .left::after, .right::after {
                left: 18px;
            }
            
            .right {
                left: 0%;
            }
        }
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner"></div>
    </div>
    <!-- Spinner End -->

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

    <!-- Navbar Start -->
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
                            <a href="achievements.php" class="dropdown-item active">Achievements</a>
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
                <a href="point/login.php" class="btn amsa-btn amsa-login-btn py-2 px-4 ms-3">Login</a>
                <a href="point/register.php" class="btn btn-primary amsa-btn amsa-btn-primary amsa-register-btn py-2 px-4 ms-3">Register</a>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <div class="container-fluid bg-primary py-5 bg-header amsa-page-header hero-achievements d-flex align-items-center" style="min-height: 650px; margin-bottom: 90px;">
            <div class="container text-center">
                <h1 class="display-4 text-white animated zoomIn mb-3">Our Achievements</h1>
                <p class="amsa-page-subtitle animated fadeInUp">Celebrating AMSA milestones, service outcomes, and the people who helped make them possible.</p>
                
                <!-- Breadcrumb -->
                <div class="d-flex justify-content-center align-items-center">
                    <a href="index.html" class="h5 text-white text-decoration-none">Home</a>
                    <i class="far fa-circle text-white px-2"></i>
                    <a href="achievements.php" class="h5 text-white text-decoration-none">Achievements</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- Achievements Hero Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <h1 class="mb-4">Celebrating Our Success Together</h1>
                    <p class="mb-4">Over the years, AMSA has accomplished remarkable milestones that reflect our dedication, hard work, and commitment to excellence. Each achievement represents the collective effort of our members and the support of our community.</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-trophy fa-3x text-primary me-3"></i>
                                <div>
                                    <h2 class="mb-0"><?php echo count($achievements); ?>+</h2>
                                    <span>Awards Won</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-users fa-3x text-primary me-3"></i>
                                <div>
                                    <h2 class="mb-0"><?php echo count($testimonials); ?>+</h2>
                                    <span>Testimonials</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img class="img-fluid rounded wow zoomIn" data-wow-delay="0.5s" src="img/Mystery_of_Burma.jpeg" style="border-radius: 20px !important; width: 100%;">
                </div>
            </div>
        </div>
    </div>
    <!-- Achievements Hero End -->

    <!-- Stats Counter Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stats-counter wow fadeInUp" data-wow-delay="0.1s">
                        <div class="counter">30+</div>
                        <div class="counter-label">Successful Events</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter wow fadeInUp" data-wow-delay="0.3s">
                        <div class="counter">5</div>
                        <div class="counter-label">Years of Excellence</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter wow fadeInUp" data-wow-delay="0.5s">
                        <div class="counter">10K+</div>
                        <div class="counter-label">Funds Raised (RM)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-counter wow fadeInUp" data-wow-delay="0.7s">
                        <div class="counter">100+</div>
                        <div class="counter-label">Volunteers Engaged</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Stats Counter End -->

    <!-- Featured Achievements Start (DYNAMIC) -->
    <div class="container-fluid py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="text-primary text-uppercase">Our Proudest Moments</h6>
                <h1 class="mb-0">Featured Achievements</h1>
            </div>
            
            <?php if (empty($achievements)): ?>
                <div class="no-data amsa-empty-state">
                    <i class="fas fa-trophy"></i>
                    <h3>No Achievements Yet</h3>
                    <p>Check back soon for our latest achievements and awards!</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($achievements as $index => $achievement): ?>
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?php echo 0.1 + ($index * 0.2); ?>s">
                        <div class="achievement-card amsa-card">
                            <div class="position-relative">
                                <?php 
                                $achievementImages = getEventImages($achievement['id']);
                                $achievementImage = !empty($achievementImages) ? 'uploads/' . basename($achievementImages[0]['img_name']) : 'https://picsum.photos/id/48/800/500';
                                ?>
                                <?php if (!empty($achievementImages)): ?>
                                    <div class="content-gallery gallery-count-<?php echo min(count($achievementImages), 3); ?>">
                                        <?php foreach (array_slice($achievementImages, 0, 3) as $cardImage): ?>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#achievementModal<?php echo (int) $achievement['id']; ?>">
                                                <img src="uploads/<?php echo htmlspecialchars(basename($cardImage['img_name'])); ?>" alt="<?php echo htmlspecialchars($achievement['title']); ?>">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <img class="img-fluid" src="<?php echo $achievementImage; ?>" alt="<?php echo htmlspecialchars($achievement['title']); ?>">
                                <?php endif; ?>
                                <div class="achievement-badge"><?php echo date('Y', strtotime($achievement['upload_date'])); ?></div>
                            </div>
                            <div class="p-4">
                                <h4><?php echo htmlspecialchars($achievement['title']); ?></h4>
                                <p><?php echo substr(htmlspecialchars($achievement['content']), 0, 120) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#achievementModal<?php echo $achievement['id']; ?>">Read More</a>
                                    <div class="text-primary">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Achievement Modal -->
                    <div class="modal fade" id="achievementModal<?php echo $achievement['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($achievement['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($achievementImages)): ?>
                                        <div class="modal-gallery mb-4">
                                            <?php foreach (array_slice($achievementImages, 0, 3) as $idx => $modalImage): ?>
                                                <a href="uploads/<?php echo htmlspecialchars(basename($modalImage['img_name'])); ?>" target="_blank" rel="noopener">
                                                    <img src="uploads/<?php echo htmlspecialchars(basename($modalImage['img_name'])); ?>" alt="Achievement image <?php echo $idx + 1; ?>">
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <span class="badge bg-primary"><?php echo date('F d, Y', strtotime($achievement['upload_date'])); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($achievement['content'])); ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
                    <span class="text-muted">Showing page <?php echo (int) $contentPage; ?> of <?php echo (int) $totalPages; ?> (<?php echo (int) $totalAchievements; ?> achievements)</span>
                    <div class="d-flex gap-2 align-items-center">
                        <form method="GET" class="d-inline-flex gap-2">
                            <input type="hidden" name="page" value="1">
                            <select name="per_page" class="form-select" onchange="this.form.submit()">
                                <?php foreach ([9, 18, 27] as $option): ?><option value="<?php echo $option; ?>" <?php echo $contentPerPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?>
                            </select>
                        </form>
                        <a class="btn btn-outline-primary amsa-btn <?php echo $contentPage <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(achievementsPageUrl(['page' => max(1, $contentPage - 1)])); ?>">Previous</a>
                        <a class="btn btn-outline-primary amsa-btn <?php echo $contentPage >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(achievementsPageUrl(['page' => min($totalPages, $contentPage + 1)])); ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Featured Achievements End -->

    <!-- Milestones Timeline Start (STATIC - Keeping original content) -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="text-primary text-uppercase">Our Journey</h6>
                <h1 class="mb-0">Key Milestones</h1>
            </div>
            
            <div class="timeline">
                <!-- Milestone 1 -->
                <div class="timeline-item left wow fadeInUp" data-wow-delay="0.1s">
                    <div class="timeline-content">
                        <h3>2025</h3>
                        <p>Empowered over 100 Rohingya refugee children through 34 weekend literacy and numeracy sessions, expanded with support from Open Feed NGO and AIU faculty.</p>
                    </div>
                </div>
                
                <!-- Milestone 2 -->
                <div class="timeline-item right wow fadeInUp" data-wow-delay="0.2s">
                    <div class="timeline-content">
                        <h3>2024</h3>
                        <p>Delivered essential food supplies to flood-affected families and Rohingya refugee children in a madrasah where AMSA volunteers regularly teach.</p>
                    </div>
                </div>
                
                <!-- Milestone 3 -->
                <div class="timeline-item left wow fadeInUp" data-wow-delay="0.3s">
                    <div class="timeline-content">
                        <h3>2021</h3>
                        <p>Organized the largest cultural festival in university history with over 800 attendees.</p>
                    </div>
                </div>
                
                <!-- Milestone 4 -->
                <div class="timeline-item right wow fadeInUp" data-wow-delay="0.4s">
                    <div class="timeline-content">
                        <h3>2020</h3>
                        <p>Raised RM15,000 for COVID-19 relief efforts through various fundraising campaigns.</p>
                    </div>
                </div>
                
                <!-- Milestone 5 -->
                <div class="timeline-item left wow fadeInUp" data-wow-delay="0.5s">
                    <div class="timeline-content">
                        <h3>2019</h3>
                        <p>Established the AMSA Mentorship Program connecting 50 senior students with juniors.</p>
                    </div>
                </div>
                
                <!-- Milestone 6 -->
                <div class="timeline-item right wow fadeInUp" data-wow-delay="0.6s">
                    <div class="timeline-content">
                        <h3>2018</h3>
                        <p>Officially recognized as the most active student association on campus.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Milestones Timeline End -->

    <!-- Testimonials Start (DYNAMIC) -->
    <div class="container-fluid py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="text-primary text-uppercase">Words of Appreciation</h6>
                <h1 class="mb-0">What People Say About Us</h1>
            </div>
            
            <?php if (empty($testimonials)): ?>
                <div class="no-data amsa-empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>No Testimonials Yet</h3>
                    <p>Be the first to share your experience with AMSA!</p>
                </div>
            <?php else: ?>
                <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.3s">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <?php 
                    $testimonialImages = getEventImages($testimonial['id']);
                    $testimonialImage = !empty($testimonialImages) ? 'uploads/' . $testimonialImages[0]['img_name'] : 'img/user.jpg';
                    ?>
                    <div class="testimonial-item amsa-card">
                        <div class="testimonial-img">
                            <img class="img-fluid" src="<?php echo $testimonialImage; ?>" alt="<?php echo htmlspecialchars($testimonial['title']); ?>">
                            <div class="quote-icon">
                                <i class="fa fa-quote-right"></i>
                            </div>
                        </div>
                        <div class="rating-stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="testimonial-text">
                            "<?php echo htmlspecialchars($testimonial['content']); ?>"
                        </div>
                        <h5 class="testimonial-name"><?php echo htmlspecialchars($testimonial['title']); ?></h5>
                        <p class="testimonial-position">AMSA Community</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Testimonials End -->

    <!-- Call to Action Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-8">
                    <h1 class="mb-4">View Our Top Management</h1>
                    <p class="mb-4">Meet the dedicated individuals who lead AMSA and drive our mission forward.</p>
                    <div class="d-flex align-items-center">
                        <a href="committee.html" class="btn btn-primary amsa-btn amsa-btn-primary py-3 px-5 me-3">Top Management</a>
                        <a href="contact.html" class="btn btn-outline-primary amsa-btn amsa-btn-ghost py-3 px-5">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <img src="img/aboutus.jpg" class="img-fluid rounded" alt="AMSA AIU student community">
                </div>
            </div>
        </div>
    </div>
    <!-- Call to Action End -->

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
    
    <script>
        // Initialize WOW.js
        new WOW().init();
        
        // Initialize testimonial carousel
        $('.testimonial-carousel').owlCarousel({
            loop: true,
            margin: 30,
            nav: false,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            responsive: {
                0: {
                    items: 1
                },
                768: {
                    items: 2
                },
                992: {
                    items: 3
                }
            }
        });
    </script>
</body>
</html>
