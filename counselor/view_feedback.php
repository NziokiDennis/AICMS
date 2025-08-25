<?php
// counselor/view_feedback.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['COUNSELOR']);

$counselor_id = $_SESSION['user_id'];

// Get all feedback for this counselor
$stmt = $pdo->prepare("
    SELECT f.*, u.name as student_name, a.start_time, a.end_time
    FROM feedback f
    JOIN sessions s ON f.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON f.student_id = u.id
    WHERE a.counselor_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$counselor_id]);
$feedback_list = $stmt->fetchAll();

// Calculate statistics
$total_feedback = count($feedback_list);
$avg_rating = 0;
$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

if ($total_feedback > 0) {
    $total_ratings = 0;
    foreach ($feedback_list as $feedback) {
        $total_ratings += $feedback['rating'];
        $rating_counts[$feedback['rating']]++;
    }
    $avg_rating = $total_ratings / $total_feedback;
}

// Get recent sessions without feedback
$stmt = $pdo->prepare("
    SELECT s.*, a.start_time, a.end_time, u.name as student_name
    FROM sessions s
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON a.student_id = u.id
    WHERE a.counselor_id = ? AND a.status = 'COMPLETED'
    AND NOT EXISTS (
        SELECT 1 FROM feedback f 
        WHERE f.session_id = s.id
    )
    ORDER BY a.end_time DESC
    LIMIT 5
");
$stmt->execute([$counselor_id]);
$sessions_without_feedback = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback - Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Student Feedback</h2>
                        <p class="text-muted mb-0">Review feedback from your completed sessions</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-comments text-primary fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= $total_feedback ?></h3>
                        <p class="text-muted mb-0">Total Feedback</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-star text-warning fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= number_format($avg_rating, 1) ?></h3>
                        <p class="text-muted mb-0">Average Rating</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-thumbs-up text-success fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= $rating_counts[5] + $rating_counts[4] ?></h3>
                        <p class="text-muted mb-0">Positive (4-5★)</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-clock text-info fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count($sessions_without_feedback) ?></h3>
                        <p class="text-muted mb-0">Pending Feedback</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Feedback List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-star text-warning me-2"></i>Feedback History (<?= $total_feedback ?>)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($feedback_list)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">No feedback yet</h5>
                                <p class="text-muted">Feedback will appear here after students complete their sessions</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($feedback_list as $feedback): ?>
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($feedback['student_name']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Session: <?= date('M j, Y g:i A', strtotime($feedback['start_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="badge bg-primary"><?= $feedback['rating'] ?>/5</span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($feedback['comment']): ?>
                                            <div class="bg-light rounded p-3 mb-2">
                                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($feedback['comment'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Submitted <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Rating Distribution -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info bg-opacity-10 border-0 py-3">
                        <h5 class="mb-0 text-info">
                            <i class="fas fa-chart-bar me-2"></i>Rating Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($total_feedback > 0): ?>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-2" style="width: 60px;">
                                        <?php for ($j = 1; $j <= $i; $j++): ?>
                                            <i class="fas fa-star text-warning small"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?= $total_feedback > 0 ? ($rating_counts[$i] / $total_feedback * 100) : 0 ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-2 text-muted small" style="width: 30px;">
                                        <?= $rating_counts[$i] ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-chart-bar fs-2 mb-2"></i>
                                <p class="small">No ratings yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sessions Awaiting Feedback -->
                <?php if (!empty($sessions_without_feedback)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                            <h5 class="mb-0 text-warning">
                                <i class="fas fa-hourglass-half me-2"></i>Awaiting Feedback
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">Recent sessions that haven't received feedback yet</p>
                            <?php foreach ($sessions_without_feedback as $session): ?>
                                <div class="border-start border-warning border-3 ps-3 mb-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($session['student_name']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M j, Y', strtotime($session['start_time'])) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Students can provide feedback after session completion
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>