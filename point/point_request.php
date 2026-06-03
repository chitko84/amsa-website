<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$categories = getAllPointCategories();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pointCategoryId = intval($_POST['point_category_id']);
    $description = sanitize($_POST['description']);
    
    // Handle file upload
    if (isset($_FILES['eop_evidence']) && $_FILES['eop_evidence']['error'] == 0) {
        $uploadDir = 'uploads/eop/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['eop_evidence']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['eop_evidence']['tmp_name'], $filePath)) {
            if (createPointRequest($userId, $pointCategoryId, $description, $filePath)) {
                $success = "Point request submitted successfully!";
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        } else {
            $error = "Failed to upload file.";
        }
    } else {
        $error = "Please upload your evidence file.";
    }
}

$userRequests = getUserPointRequests($userId);
$userPoints = getUserPoints($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AMSA - Point Request System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        .point-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }
        .request-card {
            border-radius: 15px;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .request-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: #fff; }
        .status-rejected { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid bg-primary py-5 mb-5">
        <div class="container text-center">
            <h1 class="display-4 text-white">Point Request System</h1>
            <p class="text-white">Request points for your participation and achievements</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-4">
                <div class="point-card text-center">
                    <i class="fas fa-star fa-3x mb-3"></i>
                    <h2><?php echo $userPoints; ?></h2>
                    <p class="mb-0">Your Total Points</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Submit Point Request</h5>
                    </div>
                    <div class="card-body">
                        <?php if($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select Activity Type</label>
                                <select name="point_category_id" class="form-select" required>
                                    <option value="">Choose an activity...</option>
                                    <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?> 
                                        (<?php echo $category['points']; ?> points)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Points will be awarded based on the activity type</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" 
                                    placeholder="Describe your participation or achievement..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload Evidence (EOP)</label>
                                <input type="file" name="eop_evidence" class="form-control" accept=".pdf" required>
                                <small class="text-muted">Upload certificate, participation proof, or any supporting document</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Your Point Requests History</h3>
                <?php if(empty($userRequests)): ?>
                    <div class="alert alert-info">You haven't submitted any point requests yet.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach($userRequests as $request): ?>
                        <div class="col-md-6">
                            <div class="card request-card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($request['category_name']); ?></h5>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars($request['description']); ?></p>
                                    <div class="small text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i> Requested: <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                        <?php if($request['status'] != 'pending'): ?>
                                            <br><i class="fas fa-check-circle me-1"></i> Reviewed: <?php echo date('M d, Y', strtotime($request['review_date'])); ?>
                                        <?php endif; ?>
                                        <?php if($request['admin_remarks']): ?>
                                            <br><i class="fas fa-comment me-1"></i> Remarks: <?php echo htmlspecialchars($request['admin_remarks']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>