<?php
require_once 'config/database.php';

$allowedEventCategories = ['all', 'news', 'announcement', 'workshop', 'volunteer', 'community_engagement'];
$category = $_GET['category'] ?? 'all';
$categoryFilter = in_array($category, $allowedEventCategories, true) ? $category : 'all';
$contentPage = max(1, (int) ($_GET['page'] ?? 1));
$contentPerPage = (int) ($_GET['per_page'] ?? 9);
$contentPerPage = in_array($contentPerPage, [9, 18, 27], true) ? $contentPerPage : 9;
$selectedCategories = $categoryFilter === 'all'
    ? ['news', 'announcement', 'workshop', 'volunteer', 'community_engagement']
    : [$categoryFilter];
$postPageData = getPostsPaginated($selectedCategories, $contentPage, $contentPerPage);
$posts = $postPageData['posts'];
$totalPosts = $postPageData['total_count'];
$contentPage = $postPageData['current_page'];
$totalPages = $postPageData['total_pages'];
$contentPerPage = $postPageData['per_page'];
$featuredPost = $posts[0] ?? null;
$totalNews = countPostsByCategory('news');
$totalEvents = countPostsByCategory('community_engagement');
$filterLabels = [
    'all' => 'All',
    'news' => 'News',
    'announcement' => 'Announcements',
    'workshop' => 'Workshops',
    'volunteer' => 'Volunteer',
    'community_engagement' => 'Events'
];

function countPostsByCategory($category) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM post WHERE category = ?");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("s", $category);
    $stmt->execute();
    return (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}

function postCategoryLabel($category) {
    return $category === 'community_engagement' ? 'Event' : ucfirst(str_replace('_', ' ', $category));
}

function postExcerpt($content, $length = 135) {
    $text = trim(strip_tags(htmlspecialchars_decode($content)));

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . '...';
}

function postText($value) {
    return htmlspecialchars(htmlspecialchars_decode($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function postImage($postId, $fallback = 'img/blog-1.jpg') {
    $images = getEventImages($postId);

    return !empty($images) ? 'uploads/' . basename($images[0]['img_name']) : $fallback;
}

function eventsPageUrl(array $overrides = []) {
    global $categoryFilter, $contentPerPage, $contentPage;

    $params = array_merge([
        'category' => $categoryFilter,
        'per_page' => $contentPerPage,
        'page' => $contentPage,
    ], $overrides);
    return 'events.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>AMSA AIU | Events & News</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="AMSA events and news" name="keywords" />
    <meta content="Read AMSA AIU events, announcements, workshops, volunteer updates, and community news." name="description" />

    <link href="img/logo.png" rel="icon" type="image/png" />
    <link href="img/logo.png" rel="apple-touch-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <link href="lib/animate/animate.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />

    <style>
      :root {
        --amsa-wine: #2c0410;
        --amsa-red: #8b3a3a;
        --amsa-gold: #c6b511;
        --amsa-ink: #171820;
        --amsa-muted: #687080;
        --amsa-soft: #f7f4ef;
      }

      body {
        background: #ffffff;
        color: var(--amsa-ink);
      }

      .events-hero {
        min-height: 680px;
        padding: 150px 0 80px;
        background:
          linear-gradient(90deg, rgba(44, 4, 16, 0.92), rgba(44, 4, 16, 0.58)),
          url("img/pj_hope_4.JPG") center/cover;
        display: flex;
        align-items: end;
      }

      .hero-kicker,
      .section-kicker {
        color: var(--amsa-gold);
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
      }

      .events-hero h1 {
        max-width: 780px;
        font-size: clamp(2.4rem, 6vw, 5.8rem);
        line-height: 1;
        color: white;
        margin: 12px 0 22px;
      }

      .events-hero p {
        max-width: 650px;
        color: rgba(255, 255, 255, 0.84);
        font-size: 1.1rem;
      }

      .hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 34px;
      }

      .hero-stat {
        min-width: 140px;
        padding: 18px 20px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        border-radius: 8px;
        color: white;
      }

      .hero-stat strong {
        display: block;
        font-size: 2rem;
        line-height: 1;
      }

      .events-shell {
        background:
          linear-gradient(180deg, #f7f4ef 0%, #ffffff 45%, #f7f4ef 100%);
      }

      .toolbar {
        background: #ffffff;
        border: 1px solid rgba(44, 4, 16, 0.08);
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 18px 45px rgba(44, 4, 16, 0.08);
        max-width: 100%;
      }

      .toolbar .input-group {
        width: 100%;
      }

      .toolbar .form-control,
      .toolbar .input-group-text {
        border-color: transparent;
        background: #f7f4ef !important;
        min-height: 50px;
      }

      .toolbar .input-group-text {
        border-radius: 10px 0 0 10px;
        padding-left: 20px;
        color: var(--amsa-gold) !important;
      }

      .toolbar .form-control {
        border-radius: 0 10px 10px 0;
        color: var(--amsa-ink);
        font-weight: 600;
        padding-left: 6px;
      }

      .toolbar .form-control::placeholder {
        color: #7b8190;
        font-weight: 500;
      }

      .toolbar .form-control:focus {
        box-shadow: none;
      }

      .filter-button {
        border: 0;
        background: transparent;
        color: var(--amsa-ink);
        border-radius: 10px;
        padding: 13px 16px;
        font-weight: 700;
        font-size: 0.95rem;
        line-height: 1;
        white-space: nowrap;
        transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
      }

      .filter-button.active,
      .filter-button:hover {
        background: var(--amsa-wine);
        color: white;
        box-shadow: 0 10px 22px rgba(44, 4, 16, 0.18);
      }

      .filter-group {
        background: #f7f4ef;
        border: 1px solid rgba(44, 4, 16, 0.06);
        border-radius: 12px;
        padding: 5px;
        width: 100%;
        max-width: 100%;
      }

      .browse-heading {
        padding-left: 14px;
      }

      .news-card {
        border: 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(23, 24, 32, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
      }

      .news-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 24px 65px rgba(23, 24, 32, 0.14);
      }

      .post-media {
        aspect-ratio: 16 / 9;
        width: 100%;
        height: auto;
        background: #f7f7f5;
        border-bottom: 1px solid rgba(23, 24, 32, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 16px;
      }

      .post-media > img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        padding: 0;
      }

      .post-media .content-gallery {
        display: grid;
        width: 100%;
        height: 100%;
        gap: 6px;
      }

      .post-media .content-gallery.gallery-count-1 {
        grid-template-columns: 1fr;
      }

      .post-media .content-gallery.gallery-count-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .post-media .content-gallery.gallery-count-3 {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: repeat(2, minmax(0, 1fr));
      }

      .post-media .content-gallery.gallery-count-3 a:first-child {
        grid-row: 1 / span 2;
      }

      .post-media .content-gallery a,
      .post-media .content-gallery img {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 0;
      }

      .post-media .content-gallery img {
        object-fit: cover;
        padding: 0;
      }

      .news-card-body {
        padding: 24px;
        gap: 0;
        min-height: 0;
      }

      .badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1e9dd;
        color: var(--amsa-red);
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 0.8rem;
        font-weight: 800;
      }

      .post-title {
        color: var(--amsa-ink);
        margin: 16px 0 10px;
        font-size: 1.25rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
      }

      .post-excerpt {
        color: var(--amsa-muted);
        min-height: 76px;
        line-height: 1.65;
        overflow-wrap: anywhere;
      }

      .post-meta {
        color: var(--amsa-muted);
        font-size: 0.9rem;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 18px;
      }

      .featured-panel {
        background:
          linear-gradient(135deg, rgba(44, 4, 16, 0.98), rgba(84, 12, 32, 0.96));
        color: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(44, 4, 16, 0.18);
      }

      .featured-media {
        min-height: 360px;
        height: 100%;
        background:
          linear-gradient(135deg, #ffffff, #f4f0e9);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .featured-media img {
        height: 100%;
        width: 100%;
        max-height: 420px;
        object-fit: contain;
        padding: 22px;
      }

      .featured-copy {
        padding: clamp(28px, 5vw, 56px);
      }

      .featured-copy p {
        color: rgba(255, 255, 255, 0.78);
        font-size: 1.05rem;
        line-height: 1.75;
      }

      .featured-copy h2 {
        font-size: clamp(1.7rem, 3vw, 2.8rem);
        line-height: 1.1;
      }

      .empty-state {
        background: white;
        border-radius: 8px;
        padding: 70px 20px;
        text-align: center;
        box-shadow: 0 18px 45px rgba(23, 24, 32, 0.08);
      }

      .modal-content {
        border: 0;
        border-radius: 8px;
        overflow: hidden;
      }

      .modal-hero-image {
        width: 100%;
        max-height: 440px;
        object-fit: contain;
        background: #f7f7f5;
        padding: 14px;
      }

      @media (max-width: 991.98px) {
        .events-hero {
          min-height: 560px;
          padding-top: 120px;
        }

        .featured-copy {
          padding: 28px;
        }
      }

      @media (max-width: 767.98px) {
        .toolbar {
          border-radius: 14px;
          align-items: stretch !important;
        }

        .filter-group {
          border-radius: 12px;
          justify-content: center;
        }

        .filter-button {
          flex: 1 1 auto;
        }
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

    <div class="container-fluid position-relative p-0">
      <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
        <a href="index.html" class="navbar-brand p-0">
          <img src="img/logo.png" alt="AMSA" class="navbar-logo" />
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
          <span class="fa fa-bars"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
          <div class="navbar-nav ms-auto py-0">
            <a href="index.html" class="nav-item nav-link">Home</a>
            <a href="events.php" class="nav-item nav-link active">Events & News</a>
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
          <a href="point/login.php" class="btn amsa-btn amsa-login-btn py-2 px-4 ms-3">Login</a>
          <a href="point/register.php" class="btn btn-primary amsa-btn amsa-btn-primary amsa-register-btn py-2 px-4 ms-3">Register</a>
        </div>
      </nav>

      <header class="events-hero amsa-page-header">
        <div class="container">
          <div class="amsa-hero-breadcrumb mb-3"><a href="index.html">Home</a><span>Events & News</span></div>
          <span class="hero-kicker">AMSA updates</span>
          <h1>Stories, events, and student moments in one place.</h1>
          <p>Follow AMSA announcements, community engagement programs, workshops, and the latest news from our student community.</p>
          <div class="hero-stats">
            <div class="hero-stat">
              <strong><?php echo count($posts); ?></strong>
              <span>Total posts</span>
            </div>
            <div class="hero-stat">
              <strong><?php echo $totalNews; ?></strong>
              <span>News</span>
            </div>
            <div class="hero-stat">
              <strong><?php echo $totalEvents; ?></strong>
              <span>Events</span>
            </div>
          </div>
        </div>
      </header>
    </div>

    <main class="events-shell py-5">
      <div class="container py-4">
        <?php if ($featuredPost): ?>
          <section class="featured-panel mb-5 wow fadeInUp" data-wow-delay="0.1s">
            <div class="row g-0 align-items-stretch">
              <div class="col-lg-6">
                <div class="featured-media">
                  <?php $featuredImages = getEventImages($featuredPost['id']); ?>
                  <?php if (!empty($featuredImages)): ?>
                    <div class="content-gallery gallery-count-<?php echo min(count($featuredImages), 3); ?> w-100 h-100">
                      <?php foreach (array_slice($featuredImages, 0, 3) as $featuredImage): ?>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#postModal<?php echo (int) $featuredPost['id']; ?>">
                          <img src="uploads/<?php echo htmlspecialchars(basename($featuredImage['img_name'])); ?>" alt="<?php echo postText($featuredPost['title']); ?>">
                        </a>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <img src="<?php echo htmlspecialchars(postImage($featuredPost['id'], 'img/blog-2.jpg')); ?>" alt="<?php echo postText($featuredPost['title']); ?>">
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-lg-6 d-flex align-items-center">
                <div class="featured-copy">
                  <span class="badge-soft mb-3"><i class="fa fa-bolt"></i> Latest <?php echo htmlspecialchars(postCategoryLabel($featuredPost['category'])); ?></span>
                  <h2 class="text-white mb-3"><?php echo postText($featuredPost['title']); ?></h2>
                  <p><?php echo htmlspecialchars(postExcerpt($featuredPost['content'], 190)); ?></p>
                  <button class="btn btn-primary amsa-btn amsa-btn-primary mt-3" type="button" data-bs-toggle="modal" data-bs-target="#postModal<?php echo (int) $featuredPost['id']; ?>">
                    Read Latest <i class="fa fa-arrow-right ms-2"></i>
                  </button>
                </div>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <section class="mb-4">
          <div class="row align-items-end g-4">
            <div class="col-lg-4 browse-heading">
              <span class="section-kicker">Browse updates</span>
              <h2 class="mb-0">News & Events</h2>
            </div>
            <div class="col-lg-8">
              <div class="toolbar d-flex flex-column gap-2">
                <div class="filter-group d-flex gap-1 flex-wrap">
                  <?php foreach ($filterLabels as $filter => $label): ?>
                    <a class="filter-button text-decoration-none <?php echo $categoryFilter === $filter ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(eventsPageUrl(['category' => $filter, 'page' => 1])); ?>" data-filter="<?php echo htmlspecialchars($filter); ?>">
                      <?php echo htmlspecialchars($label); ?>
                    </a>
                  <?php endforeach; ?>
                </div>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-primary"></i></span>
                  <input id="postSearch" type="search" class="form-control border-start-0" placeholder="Search title or content" aria-label="Search posts">
                </div>
              </div>
            </div>
          </div>
        </section>

        <?php if (empty($posts)): ?>
          <div class="empty-state amsa-empty-state">
            <i class="fas fa-newspaper fa-3x text-primary mb-3"></i>
            <h3>No News or Events Yet</h3>
            <p class="mb-0">Add news from the admin dashboard and it will appear here automatically.</p>
          </div>
        <?php else: ?>
          <div class="row g-4" id="postGrid">
            <?php foreach ($posts as $post): ?>
              <?php
                $images = getEventImages($post['id']);
                $image = !empty($images) ? 'uploads/' . basename($images[0]['img_name']) : 'img/blog-1.jpg';
                $label = postCategoryLabel($post['category']);
                $searchText = strtolower(htmlspecialchars_decode($post['title']) . ' ' . strip_tags(htmlspecialchars_decode($post['content'])));
              ?>
              <div class="col-md-6 col-xl-4 post-item" data-category="<?php echo htmlspecialchars($post['category']); ?>" data-search="<?php echo htmlspecialchars($searchText); ?>">
                <article class="card news-card h-100 amsa-card">
                  <div class="post-media">
                    <?php if (!empty($images)): ?>
                      <div class="content-gallery gallery-count-<?php echo min(count($images), 3); ?> w-100 h-100">
                        <?php foreach (array_slice($images, 0, 3) as $cardImage): ?>
                          <a href="#" data-bs-toggle="modal" data-bs-target="#postModal<?php echo (int) $post['id']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars(basename($cardImage['img_name'])); ?>" alt="<?php echo postText($post['title']); ?>">
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo postText($post['title']); ?>">
                    <?php endif; ?>
                  </div>
                  <div class="news-card-body d-flex flex-column h-100">
                    <div>
                      <span class="badge-soft"><i class="fa fa-tag"></i><?php echo htmlspecialchars($label); ?></span>
                      <h3 class="post-title"><?php echo postText($post['title']); ?></h3>
                      <p class="post-excerpt"><?php echo htmlspecialchars(postExcerpt($post['content'])); ?></p>
                      <div class="post-meta">
                        <span><i class="fa fa-calendar-alt me-1"></i><?php echo date('d M Y', strtotime($post['upload_date'])); ?></span>
                      </div>
                    </div>
                    <button class="btn btn-primary btn-sm amsa-btn amsa-btn-primary amsa-btn-sm mt-4 align-self-start" type="button" data-bs-toggle="modal" data-bs-target="#postModal<?php echo (int) $post['id']; ?>">
                      Read More <i class="fa fa-arrow-right ms-2"></i>
                    </button>
                  </div>
                </article>
              </div>

              <div class="modal fade" id="postModal<?php echo (int) $post['id']; ?>" tabindex="-1" aria-labelledby="postModalLabel<?php echo (int) $post['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <?php if (!empty($images)): ?>
                      <div class="modal-gallery p-3">
                        <?php foreach (array_slice($images, 0, 3) as $modalImage): ?>
                          <a href="uploads/<?php echo htmlspecialchars(basename($modalImage['img_name'])); ?>" target="_blank" rel="noopener">
                            <img src="uploads/<?php echo htmlspecialchars(basename($modalImage['img_name'])); ?>" alt="<?php echo postText($post['title']); ?>">
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <img src="<?php echo htmlspecialchars($image); ?>" class="modal-hero-image" alt="<?php echo postText($post['title']); ?>">
                    <?php endif; ?>
                    <div class="modal-header">
                      <div>
                        <span class="badge-soft mb-2"><i class="fa fa-tag"></i><?php echo htmlspecialchars($label); ?></span>
                        <h5 class="modal-title" id="postModalLabel<?php echo (int) $post['id']; ?>"><?php echo postText($post['title']); ?></h5>
                      </div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p class="text-primary fw-bold mb-3"><i class="fa fa-calendar-alt me-2"></i><?php echo date('F d, Y', strtotime($post['upload_date'])); ?></p>
                      <p><?php echo nl2br(postText($post['content'])); ?></p>
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
            <span class="text-muted">Showing page <?php echo (int) $contentPage; ?> of <?php echo (int) $totalPages; ?> (<?php echo (int) $totalPosts; ?> posts)</span>
            <div class="d-flex gap-2 align-items-center">
              <form method="GET" class="d-inline-flex gap-2">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                <input type="hidden" name="page" value="1">
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                  <?php foreach ([9, 18, 27] as $option): ?><option value="<?php echo $option; ?>" <?php echo $contentPerPage === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?>
                </select>
              </form>
              <a class="btn btn-outline-primary amsa-btn <?php echo $contentPage <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(eventsPageUrl(['page' => max(1, $contentPage - 1)])); ?>">Previous</a>
              <a class="btn btn-outline-primary amsa-btn <?php echo $contentPage >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars(eventsPageUrl(['page' => min($totalPages, $contentPage + 1)])); ?>">Next</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </main>

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

    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded back-to-top"><i class="bi bi-arrow-up"></i></a>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    <script>
      const filterButtons = document.querySelectorAll('.filter-button');
      const searchInput = document.getElementById('postSearch');
      const posts = document.querySelectorAll('.post-item');

      function applyFilters() {
        const activeFilter = document.querySelector('.filter-button.active')?.dataset.filter || 'all';
        const query = (searchInput?.value || '').trim().toLowerCase();

        posts.forEach((post) => {
          const categoryMatch = activeFilter === 'all' || post.dataset.category === activeFilter;
          const searchMatch = !query || post.dataset.search.includes(query);
          post.style.display = categoryMatch && searchMatch ? '' : 'none';
        });
      }

      filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
          filterButtons.forEach((item) => item.classList.remove('active'));
          button.classList.add('active');
          applyFilters();
        });
      });

      searchInput?.addEventListener('input', applyFilters);
    </script>
  </body>
</html>
