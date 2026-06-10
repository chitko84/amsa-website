<?php
function adminListingExcerpt($value, $length = 130) {
    $text = trim(strip_tags(htmlspecialchars_decode((string) $value)));
    if ($text === '') {
        return '';
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . '...';
}

function adminListingPostImage($postId) {
    global $conn;

    $postId = (int) $postId;
    $stmt = $conn->prepare("SELECT img_name FROM image WHERE post_id = ? ORDER BY id ASC LIMIT 1");
    if (!$stmt) {
        return '';
    }

    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && !empty($row['img_name']) ? '../uploads/' . basename($row['img_name']) : '';
}

function adminFetchPostsPage(array $categories, $page = 1, $perPage = 6) {
    global $conn;

    $page = max(1, (int) $page);
    $perPage = in_array((int) $perPage, [6, 8], true) ? (int) $perPage : 6;
    $categories = array_values(array_filter($categories, function ($category) {
        return is_string($category) && $category !== '';
    }));

    if (!$categories) {
        return ['items' => [], 'total_count' => 0, 'current_page' => 1, 'total_pages' => 1, 'per_page' => $perPage];
    }

    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $types = str_repeat('s', count($categories));

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM post WHERE category IN ($placeholders)");
    $countStmt->bind_param($types, ...$categories);
    $countStmt->execute();
    $totalCount = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $totalPages = max(1, (int) ceil($totalCount / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare("
        SELECT p.*, u.name AS author_name
        FROM post p
        LEFT JOIN user u ON p.uploaded_by = u.id
        WHERE p.category IN ($placeholders)
        ORDER BY p.upload_date DESC, p.id DESC
        LIMIT ? OFFSET ?
    ");
    $queryTypes = $types . 'ii';
    $queryParams = array_merge($categories, [$perPage, $offset]);
    $stmt->bind_param($queryTypes, ...$queryParams);
    $stmt->execute();

    return [
        'items' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'total_count' => $totalCount,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
    ];
}

function adminListingUrl(array $params = []) {
    $base = basename($_SERVER['PHP_SELF']);
    $query = array_merge($_GET, $params);
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }

    return $base . ($query ? '?' . http_build_query($query) : '');
}

function adminRenderPagination(array $pageData) {
    $totalPages = (int) ($pageData['total_pages'] ?? 1);
    $currentPage = (int) ($pageData['current_page'] ?? 1);
    if ($totalPages <= 1) {
        return;
    }
    ?>
    <nav class="admin-pagination" aria-label="Listing pagination">
        <ul class="pagination mb-0">
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(adminListingUrl(['page' => max(1, $currentPage - 1)])); ?>">Previous</a>
            </li>
            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <li class="page-item <?php echo $pageNumber === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars(adminListingUrl(['page' => $pageNumber])); ?>"><?php echo (int) $pageNumber; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(adminListingUrl(['page' => min($totalPages, $currentPage + 1)])); ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php
}

function adminDeleteReturnTo() {
    return adminListingUrl(['msg' => null]);
}
