<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    header('Location: dashboard.php');
    exit;
}

// Validate session and check if student can give feedback
$stmt = $pdo->prepare("
    SELECT s.*, a.student_id, a.counselor_id, a.start_time, a.end_time, a.status,
           u.name as counselor_name, cp.specialty
    FROM sessions s
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON a.counselor_id = u.id
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
    WHERE s.id = ? AND a.student_id = ? AND a.status = 'COMPLETED'
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: dashboard.php');
    exit;
}

// Check if feedback already exists
$stmt = $pdo->prepare("SELECT * FROM feedback WHERE student_id = ? AND session_id = ?");
$stmt->execute([$_SESSION['user_id'], $session_id]);
$existing_feedback = $stmt->fetch();

$success = '';
$error = '';

// Handle feedback submission
if ($_POST && !$existing_feedback) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("
            INSERT INTO feedback (student_id, counselor_id, session_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $session['counselor_id'], $session_id, $rating, $comment])) {
            $success = 'Thank you for your feedback! Your input helps improve our counseling services.';
            $existing_feedback = ['rating' => $rating, 'comment' => $comment, 'created_at' => date('Y-m-d H:i:s')];
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    } else {
        $error = 'Please provide a valid rating (1-5 stars).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Feedback - Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
    <!-- Student Navigation -->
    <?php
    // Determine current page for active nav highlighting
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../index.php">
                <i class="fas fa-brain me-2"></i>Happy Hearts
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'dashboard' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'find_counselor' ? 'active fw-bold' : '' ?>" href="find_counselor.php">
                            <i class="fas fa-search me-1"></i>Find Counselor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'my_notes' ? 'active fw-bold' : '' ?>" href="my_notes.php">
                            <i class="fas fa-sticky-note me-1"></i>My Notes
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="find_counselor.php">
                                    <i class="fas fa-search me-2"></i>Find Counselor
                                </a></li>
                                <li><a class="dropdown-item" href="my_notes.php">
                                    <i class="fas fa-sticky-note me-2"></i>My Notes
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">
                                    <i class="fas fa-home me-2"></i>Main Site
                                </a></li>
                                <li><a class="dropdown-item" href="../auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="my_notes.php">My Notes</a></li>
                        <li class="breadcrumb-item active">Session Feedback</li>
                    </ol>
                </nav>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="text-center">
                            <i class="fas fa-comments text-primary fs-2 mb-3"></i>
                            <h3 class="fw-bold">Session Feedback</h3>
                            <p class="text-muted mb-0">Share your experience to help us improve our services</p>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Session Info -->
                        <div class="bg-light rounded p-3 mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-user-md me-2"></i><?= htmlspecialchars($session['counselor_name']) ?>
                                    </h6>
                                    <?php if ($session['specialty']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($session['specialty']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M j, Y g:i A', strtotime($session['start_time'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                                <div class="mt-3">
                                    <a href="dashboard.php" class="btn btn-outline-success me-2">Go to Dashboard</a>
                                    <a href="my_notes.php" class="btn btn-outline-primary">View My Notes</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($existing_feedback && !$success): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>You have already submitted feedback for this session.
                                <div class="mt-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="me-2">Your Rating:</strong>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $existing_feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2">(<?= $existing_feedback['rating'] ?>/5)</span>
                                    </div>
                                    <?php if ($existing_feedback['comment']): ?>
                                        <div>
                                            <strong>Your Comment:</strong>
                                            <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($existing_feedback['comment'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        Submitted on <?= date('M j, Y g:i A', strtotime($existing_feedback['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$existing_feedback): ?>
                            <form method="POST" id="feedbackForm">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">How would you rate this session? *</label>
                                        <div class="rating-container text-center py-3">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" class="btn-check" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                                <label class="btn rating-star" for="star<?= $i ?>">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted">Click on the stars to rate your experience</small>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="comment" class="form-label fw-bold">Additional Comments (Optional)</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="4" 
                                                  placeholder="Share your thoughts about the session, what was helpful, or suggestions for improvement..."></textarea>
                                        <small class="text-muted">Your feedback helps us provide better care and helps other students find the right counselor.</small>
                                    </div>

                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg me-2">
                                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Maybe Later</a>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .rating-star {
        background: none;
        border: none;
        font-size: 2rem;
        color: #dee2e6;
        margin: 0 5px;
        transition: color 0.2s;
        cursor: pointer;
    }
    
    .rating-star:hover,
    .rating-star.active {
        color: #ffc107;
    }
    
    .btn-check:checked + .rating-star {
        color: #ffc107;
    }
    
    .rating-container {
        background: #f8f9fa;
        border-radius: 10px;
        margin: 10px 0;
    }
    </style>
    
    <script>
    // Interactive star rating
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.rating-star');
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', function() {
                highlightStars(index + 1);
            });
            
            star.addEventListener('click', function() {
                ratingInputs[index].checked = true;
                highlightStars(index + 1);
            });
        });
        
        document.querySelector('.rating-container').addEventListener('mouseleave', function() {
            const checkedRating = document.querySelector('input[name="rating"]:checked');
            if (checkedRating) {
                highlightStars(parseInt(checkedRating.value));
            } else {
                highlightStars(0);
            }
        });
        
        function highlightStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
    });
    </script>
</body>
</html>