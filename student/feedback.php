<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

// Get sessions needing feedback if no session_id provided
$session_id = $_GET['session_id'] ?? null;
$feedback_needed = [];
$session = null;

if ($session_id) {
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
        $_SESSION['error_message'] = 'Session not found or you do not have permission to provide feedback for this session.';
        header('Location: dashboard.php');
        exit;
    }
} else {
    // Fetch sessions needing feedback
    $stmt = $pdo->prepare("
        SELECT s.*, a.start_time, a.end_time, u.name as counselor_name, cp.specialty
        FROM sessions s
        JOIN appointments a ON s.appointment_id = a.id
        JOIN users u ON a.counselor_id = u.id
        LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
        WHERE a.student_id = ? AND a.status = 'COMPLETED'
        AND NOT EXISTS (
            SELECT 1 FROM feedback f 
            WHERE f.student_id = a.student_id AND f.session_id = s.id
        )
        ORDER BY a.end_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $feedback_needed = $stmt->fetchAll();
}

// Check if feedback already exists for specific session
$existing_feedback = null;
if ($session_id) {
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE student_id = ? AND session_id = ?");
    $stmt->execute([$_SESSION['user_id'], $session_id]);
    $existing_feedback = $stmt->fetch();
}

$success = '';
$error = '';

// Handle feedback submission
if ($_POST && !$existing_feedback && $session_id) {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $error = 'Please provide a valid rating (1-5 stars).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO feedback (student_id, counselor_id, session_id, rating, comment, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $session['counselor_id'], $session_id, $rating, $comment])) {
                $success = 'Thank you for your feedback! Your input helps improve our counseling services.';
                $existing_feedback = [
                    'rating' => $rating, 
                    'comment' => $comment, 
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Database error occurred. Please try again later.';
            error_log("Feedback submission error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Feedback - Happy Hearts Counseling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <!-- Header -->
    <div class="bg-gradient-warning text-white py-4">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">
                <i class="fas fa-star me-3"></i>Session Feedback
            </h1>
            <p class="lead mb-0">Share your experience to help us improve our services</p>
        </div>
    </div>
    
    <div class="container py-5">
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
                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-comments" style="font-size: 2rem;"></i>
                            </div>
                            <h3 class="fw-bold mb-3">How was your session?</h3>
                            <p class="text-muted mb-0">Your feedback helps us improve our counseling services</p>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (!$session_id && !empty($feedback_needed)): ?>
                            <!-- List sessions needing feedback -->
                            <h4 class="fw-bold mb-4">Sessions Needing Feedback</h4>
                            <?php foreach ($feedback_needed as $session): ?>
                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user-md"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1 fw-bold text-primary">
                                                            <?= htmlspecialchars($session['counselor_name']) ?>
                                                        </h5>
                                                        <?php if ($session['specialty']): ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($session['specialty']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('M j, Y', strtotime($session['start_time'])) ?>
                                                </small><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('g:i A', strtotime($session['start_time'])) ?> - <?= date('g:i A', strtotime($session['end_time'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="feedback.php?session_id=<?= $session['id'] ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-star me-1"></i>Provide Feedback
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (!$session_id && empty($feedback_needed)): ?>
                            <div class="alert alert-info border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                                    <div>
                                        <h6 class="mb-2">No sessions available for feedback</h6>
                                        <p class="mb-0">You have no completed sessions that need feedback. Check back after your next session!</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($session): ?>
                            <!-- Session Info -->
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-1 fw-bold text-primary">
                                                        <?= htmlspecialchars($session['counselor_name']) ?>
                                                    </h5>
                                                    <?php if ($session['specialty']): ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($session['specialty']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M j, Y', strtotime($session['start_time'])) ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('g:i A', strtotime($session['start_time'])) ?> - <?= date('g:i A', strtotime($session['end_time'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($success): ?>
                                <div class="alert alert-success border-0 shadow-sm">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fs-2 me-3 text-success"></i>
                                        <div>
                                            <h5 class="mb-1">Thank you for your feedback!</h5>
                                            <p class="mb-3"><?= $success ?></p>
                                            <div class="d-flex gap-2">
                                                <a href="dashboard.php" class="btn btn-success">
                                                    <i class="fas fa-tachometer-alt me-1"></i>Go to Dashboard
                                                </a>
                                                <a href="my_notes.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-sticky-note me-1"></i>View My Notes
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger border-0 shadow-sm">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($existing_feedback && !$success): ?>
                                <div class="alert alert-info border-0 shadow-sm">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-info-circle fs-4 me-3 text-info mt-1"></i>
                                        <div>
                                            <h6 class="mb-2">You have already submitted feedback for this session</h6>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <strong class="me-2">Your Rating:</strong>
                                                    <div class="me-2">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?= $i <= $existing_feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span>(<?= $existing_feedback['rating'] ?>/5)</span>
                                                </div>
                                                <?php if ($existing_feedback['comment']): ?>
                                                    <div class="mb-2">
                                                        <strong>Your Comment:</strong>
                                                        <div class="mt-1 p-2 bg-white rounded border-start border-primary border-3">
                                                            <?= nl2br(htmlspecialchars($existing_feedback['comment'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Submitted on <?= date('M j, Y g:i A', strtotime($existing_feedback['created_at'])) ?>
                                                </small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="dashboard.php" class="btn btn-primary">
                                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                                </a>
                                                <a href="my_notes.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-sticky-note me-1"></i>My Notes
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$existing_feedback): ?>
                                <form method="POST" id="feedbackForm">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label class="form-label fw-bold fs-5 mb-3">Rate your session experience *</label>
                                            <div class="rating-container text-center py-4 bg-light rounded">
                                                <div class="mb-3">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <input type="radio" class="btn-check" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                                        <label class="btn rating-star mx-1" for="star<?= $i ?>" data-rating="<?= $i ?>">
                                                            <i class="fas fa-star"></i>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                                <div id="rating-text" class="text-muted"></div>
                                            </div>
                                            <small class="text-muted">Click on the stars to rate your experience (1 = Poor, 5 = Excellent)</small>
                                        </div>

                                        <div class="col-12">
                                            <label for="comment" class="form-label fw-bold">Additional Comments</label>
                                            <textarea class="form-control form-control-lg" id="comment" name="comment" rows="5" 
                                                      placeholder="Share your thoughts about the session. What was helpful? What could be improved? Your feedback helps us provide better care..."></textarea>
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Your feedback helps us improve our services and helps other students find the right counselor.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                                <button type="submit" class="btn btn-warning btn-lg px-5 me-md-2">
                                                    <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                                </button>
                                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg px-4">
                                                    <i class="fas fa-arrow-left me-2"></i>Maybe Later
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .rating-star {
        background: none;
        border: none;
        font-size: 2.5rem;
        color: #dee2e6;
        transition: all 0.2s ease;
        cursor: pointer;
        padding: 10px;
    }
    
    .rating-star:hover,
    .rating-star.active {
        color: #ffc107;
        transform: scale(1.1);
    }
    
    .btn-check:checked + .rating-star {
        color: #ffc107;
        transform: scale(1.1);
    }
    
    .rating-container {
        border: 2px dashed #dee2e6;
        transition: border-color 0.2s ease;
    }
    
    .rating-container.has-rating {
        border-color: #ffc107;
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    
    .card {
        border-radius: 15px;
    }
    
    .btn-lg {
        border-radius: 10px;
        padding: 12px 30px;
    }
    
    .alert {
        border-radius: 12px;
    }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Interactive star rating system
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.rating-star');
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingContainer = document.querySelector('.rating-container');
        const ratingText = document.getElementById('rating-text');
        
        const ratingLabels = {
            1: 'Poor - Not satisfied',
            2: 'Fair - Below expectations',
            3: 'Good - Met expectations',
            4: 'Very Good - Above expectations',
            5: 'Excellent - Exceeded expectations'
        };
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', function() {
                const rating = index + 1;
                highlightStars(rating);
                updateRatingText(rating);
            });
            
            star.addEventListener('click', function() {
                const rating = index + 1;
                ratingInputs[index].checked = true;
                highlightStars(rating);
                updateRatingText(rating);
                ratingContainer.classList.add('has-rating');
                
                // Remove any previous validation errors
                const form = document.getElementById('feedbackForm');
                const existingError = form.querySelector('.rating-error');
                if (existingError) {
                    existingError.remove();
                }
            });
        });
        
        ratingContainer.addEventListener('mouseleave', function() {
            const checkedRating = document.querySelector('input[name="rating"]:checked');
            if (checkedRating) {
                const rating = parseInt(checkedRating.value);
                highlightStars(rating);
                updateRatingText(rating);
            } else {
                highlightStars(0);
                ratingText.textContent = 'Click on stars to rate';
            }
        });
        
        // Form validation
        document.getElementById('feedbackForm')?.addEventListener('submit', function(e) {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            if (!selectedRating) {
                e.preventDefault();
                
                // Show error message
                const existingError = this.querySelector('.rating-error');
                if (existingError) {
                    existingError.remove();
                }
                
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger rating-error';
                errorAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Please select a rating before submitting your feedback.';
                
                this.insertBefore(errorAlert, this.firstChild);
                
                // Scroll to error
                errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                return false;
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
        
        function updateRatingText(rating) {
            ratingText.textContent = ratingLabels[rating] || 'Click on stars to rate';
        }
        
        // Initialize
        updateRatingText(0);
    });
    </script>
</body>
</html>