<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

// Get all published notes for this student
$stmt = $pdo->prepare("
    SELECT n.*, s.id as session_id, u.name as counselor_name, 
           a.start_time, a.end_time, cp.specialty
    FROM notes n
    JOIN sessions s ON n.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON n.counselor_id = u.id
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
    WHERE a.student_id = ? AND n.visibility = 'PUBLISHED'
    ORDER BY n.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Session Notes - Happy Hearts Counseling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Student Navigation -->
    <?php
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
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
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
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
    
    <!-- Header Section -->
    <div class="bg-gradient-success text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-6 fw-bold mb-2">
                        <i class="fas fa-sticky-note me-3"></i>My Session Notes
                    </h1>
                    <p class="lead mb-0">Review insights and progress from your counseling sessions</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="bg-white bg-opacity-20 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <span class="badge bg-white text-success fs-4 px-3 py-2"><?= count($notes) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Session Notes</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if (empty($notes)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-file-medical-alt text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-muted fw-bold mb-3">No Session Notes Available Yet</h4>
                            <p class="text-muted mb-4">Session notes will appear here after your counselor publishes them following completed sessions. These notes help track your progress and provide valuable insights from your sessions.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="find_counselor.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Your First Session
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($notes as $index => $note): ?>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm hover-lift">
                                    <div class="card-header bg-white border-0 py-4">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user-md fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1 fw-bold text-primary">
                                                            <?= htmlspecialchars($note['counselor_name']) ?>
                                                        </h5>
                                                        <?php if ($note['specialty']): ?>
                                                            <span class="badge bg-light text-dark">
                                                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($note['specialty']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap align-items-center text-muted small gap-3">
                                                    <span>
                                                        <i class="fas fa-calendar me-1 text-primary"></i>
                                                        Session: <?= date('l, M j, Y', strtotime($note['start_time'])) ?>
                                                    </span>
                                                    <span>
                                                        <i class="fas fa-clock me-1 text-primary"></i>
                                                        <?= date('g:i A', strtotime($note['start_time'])) ?> - <?= date('g:i A', strtotime($note['end_time'])) ?>
                                                    </span>
                                                    <span>
                                                        <i class="fas fa-edit me-1 text-primary"></i>
                                                        Published: <?= date('M j, Y', strtotime($note['created_at'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-success fs-6 px-3 py-2">
                                                    <i class="fas fa-check me-1"></i>Published
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="note-content bg-light rounded p-4">
                                            <?= nl2br(htmlspecialchars($note['content'])) ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                This note was created by your counselor to help track your progress
                                            </small>
                                            <div>
                                                <?php
                                                // Check if feedback already submitted for this session
                                                $feedback_stmt = $pdo->prepare("SELECT id FROM feedback WHERE student_id = ? AND session_id = ?");
                                                $feedback_stmt->execute([$_SESSION['user_id'], $note['session_id']]);
                                                $has_feedback = $feedback_stmt->fetch();
                                                ?>
                                                <?php if (!$has_feedback): ?>
                                                    <a href="feedback.php?session_id=<?= $note['session_id'] ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-star me-1"></i>Leave Feedback
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Feedback Given
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-lg me-3">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="find_counselor.php" class="btn btn-success btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i>Book Another Session
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .bg-gradient-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    }
    
    .hover-lift {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .note-content {
        line-height: 1.7;
        font-size: 15px;
        color: #495057;
        border-left: 4px solid #28a745;
    }
    
    .card {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .badge {
        font-size: 0.8em;
    }
    
    .btn-lg {
        border-radius: 10px;
        padding: 12px 30px;
    }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>