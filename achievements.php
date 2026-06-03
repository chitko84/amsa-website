<?php
require_once 'config/database.php';
$achievements = getAllAchievements();
$testimonials = getAllTestimonials();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AMSA - Achievements</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <!-- Favicon -->
    <link href="img/logo.png" rel="icon">

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
        
        .achievement-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
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
                    <small class="me-3 text-light"><i class="fa fa-map-marker-alt me-2"></i>Jln Tun Razak, Bandar Alor Setar, 05200 Alor Setar, Kedah, Malaysia</small>
                    <small class="me-3 text-light"><i class="fa fa-phone-alt me-2"></i>+012 345 6789</small>
                    <small class="text-light"><i class="fa fa-envelope-open me-2"></i>amsa@gmail.com</small>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-twitter fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-facebook-f fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-linkedin-in fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-instagram fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href=""><i class="fab fa-youtube fw-normal"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar Start -->
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
            <a href="index.html" class="navbar-brand p-0">
                <img src="img/logo.png" alt="AMSA" class="navbar-logo" style="height: 60px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="index.html" class="nav-item nav-link">Home</a>
                    <a href="events.html" class="nav-item nav-link">Events & News</a>
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
                            <a href="fundrasing.html" class="dropdown-item">Fundrasing</a>
                        </div>
                    </div>
                    <a href="contact.html" class="nav-item nav-link">Contact</a>
                    <a href="devteam.html" class="nav-item nav-link">Dev Team</a>
                </div>
                <a href="#" class="btn btn-primary py-2 px-4 ms-3">Register</a>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <div class="container-fluid bg-primary py-5 bg-header d-flex align-items-center" style="min-height: 650px; margin-bottom: 90px;">
            <div class="container text-center">
                <h1 class="display-4 text-white animated zoomIn mb-3">Our Achievements</h1>
                
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
                <div class="no-data">
                    <i class="fas fa-trophy"></i>
                    <h3>No Achievements Yet</h3>
                    <p>Check back soon for our latest achievements and awards!</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($achievements as $index => $achievement): ?>
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?php echo 0.1 + ($index * 0.2); ?>s">
                        <div class="achievement-card">
                            <div class="position-relative">
                                <?php 
                                $achievementImages = getEventImages($achievement['id']);
                                $achievementImage = !empty($achievementImages) ? 'uploads/' . $achievementImages[0]['img_name'] : 'https://picsum.photos/id/48/800/500';
                                ?>
                                <img class="img-fluid" src="<?php echo $achievementImage; ?>" alt="<?php echo htmlspecialchars($achievement['title']); ?>">
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
                                        <img src="<?php echo $achievementImage; ?>" class="img-fluid rounded mb-4" style="width: 100%;">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <span class="badge bg-primary"><?php echo date('F d, Y', strtotime($achievement['upload_date'])); ?></span>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($achievement['content'])); ?></p>
                                    <?php if ($achievement['author_name']): ?>
                                        <div class="alert alert-light mt-3">
                                            <i class="fas fa-user me-2"></i> Posted by: <?php echo htmlspecialchars($achievement['author_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                <div class="no-data">
                    <i class="fas fa-comments"></i>
                    <h3>No Testimonials Yet</h3>
                    <p>Be the first to share your experience with AMSA!</p>
                </div>
            <?php else: ?>
                <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.3s">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <?php 
                    $testimonialImages = getEventImages($testimonial['id']);
                    $testimonialImage = !empty($testimonialImages) ? 'uploads/' . $testimonialImages[0]['img_name'] : 'img/default-avatar.jpg';
                    ?>
                    <div class="testimonial-item">
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
                        <p class="testimonial-position"><?php echo htmlspecialchars($testimonial['author_name'] ?? 'AMSA Member'); ?></p>
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
                        <a href="committee.html" class="btn btn-primary py-3 px-5 me-3">Top Management</a>
                        <a href="contact.html" class="btn btn-outline-primary py-3 px-5">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <img src="img/cta-achievements.png" class="img-fluid rounded" alt="">
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
                            <img src="img/logo.png" alt="AMSA" style="height: 80px;">
                        </a>
                        <p class="mt-3 mb-4">We Rise by Lifting Others</p>
                        <form action="">
                            <div class="input-group">
                                <input type="text" class="form-control border-white p-3" placeholder="Your Email">
                                <button class="btn btn-dark">Sign Up</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-4 col-md-12 pt-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Get In Touch</h3>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-geo-alt text-primary me-2"></i>
                                <p class="mb-0">Jln Tun Razak, Bandar Alor Setar, 05200 Alor Setar, Kedah, Malaysia</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-envelope-open text-primary me-2"></i>
                                <p class="mb-0">amsa@gmail.com</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-telephone text-primary me-2"></i>
                                <p class="mb-0">+012 345 67890</p>
                            </div>
                            <div class="d-flex mt-4">
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-twitter fw-normal"></i></a>
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-facebook-f fw-normal"></i></a>
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-linkedin-in fw-normal"></i></a>
                                <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram fw-normal"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Quick Links</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="index.html"><i class="bi bi-arrow-right text-primary me-2"></i>Home</a>
                                <a class="text-light mb-2" href="about.html"><i class="bi bi-arrow-right text-primary me-2"></i>About Us</a>
                                <a class="text-light mb-2" href="cme.php"><i class="bi bi-arrow-right text-primary me-2"></i>Community Engagements</a>
                                <a class="text-light mb-2" href="committee.html"><i class="bi bi-arrow-right text-primary me-2"></i>Meet The Team</a>
                                <a class="text-light mb-2" href="events.html"><i class="bi bi-arrow-right text-primary me-2"></i>Latest Blog</a>
                                <a class="text-light" href="contact.html"><i class="bi bi-arrow-right text-primary me-2"></i>Contact Us</a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Popular Links</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="index.html"><i class="bi bi-arrow-right text-primary me-2"></i>Home</a>
                                <a class="text-light mb-2" href="about.html"><i class="bi bi-arrow-right text-primary me-2"></i>About Us</a>
                                <a class="text-light mb-2" href="achievements.php"><i class="bi bi-arrow-right text-primary me-2"></i>Achievements</a>
                                <a class="text-light mb-2" href="fundrasing.html"><i class="bi bi-arrow-right text-primary me-2"></i>Fundraising</a>
                                <a class="text-light mb-2" href="cme.php"><i class="bi bi-arrow-right text-primary me-2"></i>Community</a>
                                <a class="text-light" href="devteam.html"><i class="bi bi-arrow-right text-primary me-2"></i>Dev Team</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid text-white" style="background: #2c0410;">
        <div class="container text-center">
            <div class="row justify-content-end">
                <div class="col-lg-8 col-md-6">
                    <div class="d-flex align-items-center justify-content-center" style="height: 75px;">
                        <p class="mb-0">&copy; <a class="text-white border-bottom" href="#">AIU Myanmar Student's Association</a>. All Rights Reserved.</p>
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