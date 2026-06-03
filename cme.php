<?php
require_once 'config/database.php';
$events = getAllEvents();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>AMSA - Community Engagements</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <!-- Favicon -->
    <link href="img/logo.png" rel="icon">

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
    
    <!-- Custom overrides: Reddish-brown theme + aesthetic modal -->
    <style>
        /* Override green (secondary) to reddish brown */
        :root {
            --secondary: #8B3A3A !important;
            --secondary-light: #B55A4A !important;
            --secondary-dark: #5E2A2A !important;
            --gradient-secondary: linear-gradient(135deg, #8B3A3A, #B55A4A) !important;
        }
        /* Also override any other green elements */
        .team-text p {
            color: #8B3A3A !important;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #8B3A3A, #B55A4A) !important;
            color: white !important;
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #B55A4A, #8B3A3A) !important;
        }
        .bg-secondary {
            background-color: #8B3A3A !important;
        }
        .text-secondary {
            color: #8B3A3A !important;
        }
        .border-secondary {
            border-color: #8B3A3A !important;
        }
        /* Fix any gradient issues */
        .service-item .service-icon {
            background: linear-gradient(135deg, var(--primary), #8B3A3A) !important;
        }
        .section-title::before {
            background: linear-gradient(90deg, var(--primary), #8B3A3A) !important;
        }
        
        /* Aesthetic Modal Styles */
        .modal.fade .modal-dialog {
            transform: scale(0.9) translateY(-20px);
            transition: transform 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1), opacity 0.3s;
        }
        .modal.fade.show .modal-dialog {
            transform: scale(1) translateY(0);
        }
        .modal-content {
            border: none;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(2px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .modal-header {
            border-bottom: none;
            padding: 1.5rem 2rem 0.5rem 2rem;
            background: linear-gradient(135deg, #fff8f0, #ffffff);
        }
        .modal-header .modal-title {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #c6b511, #8B3A3A);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }
        .modal-body {
            padding: 1rem 2rem 2rem 2rem;
        }
        .modal-body img {
            border-radius: 20px;
            margin-bottom: 1.2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .modal-body img:hover {
            transform: scale(1.02);
        }
        .event-full-date {
            font-size: 1rem;
            color: #8B3A3A;
            font-weight: 600;
            background: #fae9e6;
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            margin-bottom: 1rem;
        }
        .event-full-description {
            font-size: 1rem;
            line-height: 1.7;
            color: #2c2c2c;
        }
        .event-extra-details {
            background: linear-gradient(135deg, #fef5e7, #fff2e6);
            padding: 1.2rem;
            border-radius: 16px;
            margin-top: 1.5rem;
            border-left: 5px solid #c6b511;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1.2rem 2rem;
            background: #fefaf5;
        }
        .btn-close {
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%238B3A3A'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            opacity: 0.7;
            transition: all 0.2s;
        }
        .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        /* Custom button inside modal */
        .modal-footer .btn-secondary {
            background: #8B3A3A !important;
            border-radius: 40px;
            padding: 0.5rem 1.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .modal-footer .btn-secondary:hover {
            background: #6b2c2c !important;
            transform: translateY(-2px);
        }
        /* Ensure blog images look consistent */
        .blog-img {
            background-color: #e9ecef;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .blog-img img {
            width: 100%;
            height: 220px;
            display: block;
            object-fit: cover;
            background-color: #e9ecef;
        }
        /* Adjust navbar active/hover primary color */
        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active {
            color: #c6b511 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #c6b511, #b59e0e) !important;
        }
        
        /* Carousel styles */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 20px;
        }
        .carousel-item img {
            height: 400px;
            object-fit: cover;
        }
        
        /* No events message */
        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 20px;
        }
        .no-events i {
            font-size: 64px;
            color: #8B3A3A;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
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
                            <a href="achievements.html" class="dropdown-item">Achievements</a>
                            <a href="committee.html" class="dropdown-item">Top Management</a>
                        </div>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Projects</a>
                        <div class="dropdown-menu m-0">
                            <a href="cme.php" class="dropdown-item active">Community Engagements</a>
                            <a href="fundrasing.html" class="dropdown-item">Fundrasing</a>
                        </div>
                    </div>
                    <a href="contact.html" class="nav-item nav-link">Contact</a>
                    <a href="devteam.html" class="nav-item nav-link">Dev Team</a>
                </div>
                <a href="#" class="btn btn-primary py-2 px-4 ms-3">Register</a>
            </div>
        </nav>
    </div>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid bg-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="display-1 text-white mb-3 animated slideInDown">Community Engagements</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center mb-0 animated slideInDown">
                            <li class="breadcrumb-item"><a class="text-white" href="index.html">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="#">Projects</a></li>
                            <li class="breadcrumb-item text-primary active" aria-current="page">Community Engagements</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Community Engagements Grid Section -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 600px;">
                <h5 class="fw-bold text-primary text-uppercase">Our Impact</h5>
                <h1 class="mb-0">Community Engagement Programs</h1>
            </div>
            
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>No Events Found</h3>
                    <p>Check back soon for our upcoming community engagement programs!</p>
                </div>
            <?php else: ?>
                <div class="row g-5">
                    <?php foreach ($events as $index => $event): ?>
                    <!-- Event Card <?php echo $event['id']; ?> -->
                    <div class="col-lg-4 col-md-6 wow slideInUp" data-wow-delay="<?php echo 0.2 + ($index * 0.1); ?>s">
                        <div class="blog-item bg-light rounded overflow-hidden h-100 d-flex flex-column">
                            <div class="blog-img position-relative overflow-hidden">
                                <?php 
                                $images = getEventImages($event['id']);
                                $firstImage = !empty($images) ? 'uploads/' . $images[0]['img_name'] : 'https://picsum.photos/id/48/800/500';
                                ?>
                                <img class="img-fluid w-100" src="<?php echo $firstImage; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <div class="position-absolute top-0 start-0 bg-primary text-white rounded-end mt-3 py-2 px-3">
                                    <small><i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($event['upload_date'])); ?></small>
                                </div>
                            </div>
                            <div class="p-4 flex-grow-1">
                                <div class="d-flex mb-3">
                                    <small class="text-muted">
                                        <i class="far fa-user text-primary me-2"></i>Posted by: <?php echo htmlspecialchars($event['author_name'] ?? 'Admin'); ?>
                                    </small>
                                </div>
                                <h4 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p class="mb-0"><?php echo substr(strip_tags(htmlspecialchars_decode($event['content'])), 0, 120) . '...'; ?></p>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="#" class="text-uppercase fw-bold text-primary read-more-btn" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['id']; ?>">
                                    Read More <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Event <?php echo $event['id']; ?> -->
                    <div class="modal fade" id="eventModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="eventModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="eventModalLabel<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php 
                                    $modalImages = getEventImages($event['id']);
                                    if (!empty($modalImages)): 
                                    ?>
                                        <div id="carousel<?php echo $event['id']; ?>" class="carousel slide mb-4" data-bs-ride="carousel">
                                            <div class="carousel-indicators">
                                                <?php foreach ($modalImages as $idx => $img): ?>
                                                <button type="button" data-bs-target="#carousel<?php echo $event['id']; ?>" data-bs-slide-to="<?php echo $idx; ?>" class="<?php echo $idx == 0 ? 'active' : ''; ?>"></button>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="carousel-inner">
                                                <?php foreach ($modalImages as $idx => $img): ?>
                                                <div class="carousel-item <?php echo $idx == 0 ? 'active' : ''; ?>">
                                                    <img src="uploads/<?php echo $img['img_name']; ?>" class="d-block w-100 rounded" alt="Event image <?php echo $idx + 1; ?>">
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if (count($modalImages) > 1): ?>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?php echo $event['id']; ?>" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carousel<?php echo $event['id']; ?>" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <img src="https://picsum.photos/id/48/800/500" alt="Event Image" class="img-fluid rounded mb-4">
                                    <?php endif; ?>
                                    
                                    <div class="event-full-date">
                                        <i class="far fa-calendar-alt me-2"></i><?php echo date('F d, Y', strtotime($event['upload_date'])); ?>
                                        <?php if ($event['edit_date']): ?>
                                        <span class="ms-3"><i class="fas fa-edit me-1"></i>Updated: <?php echo date('F d, Y', strtotime($event['edit_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="event-full-description">
                                        <p><?php echo nl2br(htmlspecialchars_decode($event['content'])); ?></p>
                                        
                                        <div class="event-extra-details">
                                            <strong><i class="fas fa-info-circle me-2"></i>Event Details:</strong>
                                            <ul class="mt-2 mb-0">
                                                <li><i class="fas fa-user me-2"></i>Organized by: <?php echo htmlspecialchars($event['author_name'] ?? 'AMSA Team'); ?></li>
                                                <li><i class="fas fa-tag me-2"></i>Category: <?php echo ucfirst(str_replace('_', ' ', $event['category'])); ?></li>
                                                <?php if ($event['edit_date']): ?>
                                                <li><i class="fas fa-edit me-2"></i>Last edited: <?php echo date('F d, Y \a\t g:i A', strtotime($event['edit_date'])); ?></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <a href="#" class="btn btn-primary">Share This Event <i class="fas fa-share-alt ms-2"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
                                <a class="text-light mb-2" href="#"><i class="bi bi-arrow-right text-primary me-2"></i>Our Services</a>
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
                                <a class="text-light mb-2" href="cme.php"><i class="bi bi-arrow-right text-primary me-2"></i>Community Engagements</a>
                                <a class="text-light mb-2" href="fundrasing.html"><i class="bi bi-arrow-right text-primary me-2"></i>Fundraising</a>
                                <a class="text-light mb-2" href="achievements.html"><i class="bi bi-arrow-right text-primary me-2"></i>Achievements</a>
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
        
        // Add hover effect for blog items
        document.querySelectorAll('.blog-item').forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateY(-5px)';
                item.style.transition = 'all 0.3s ease';
            });
            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>