<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

// Get student's upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as counselor_name, cp.specialty, cp.meeting_mode, cp.location
    FROM appointments a
    JOIN users u ON a.counselor_id = u.id
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
    WHERE a.student_id = ? AND a.status IN ('PENDING', 'APPROVED') 
    AND a.start_time >= NOW()
    ORDER BY a.start_time ASC
    LIMIT 3
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_appointments = $stmt->fetchAll();

// Get recent completed sessions count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'COMPLETED'");
$stmt->execute([$_SESSION['user_id']]);
$completed_sessions = $stmt->fetchColumn();

// Get pending appointments count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'PENDING'");
$stmt->execute([$_SESSION['user_id']]);
$pending_appointments = $stmt->fetchColumn();

// Get published notes count
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notes n
    JOIN sessions s ON n.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    WHERE a.student_id = ? AND n.visibility = 'PUBLISHED'
");
$stmt->execute([$_SESSION['user_id']]);
$available_notes = $stmt->fetchColumn();

// Get recent feedback requests (completed sessions without feedback)
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
    LIMIT 2
");
$stmt->execute([$_SESSION['user_id']]);
$feedback_needed = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Happy Hearts Counseling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Student Navigation -->
    <?php
    // Determine current page for active nav highlighting
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
    
    <!-- Hero Section -->
    <div class="bg-gradient-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">
                        Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>! 👋
                    </h1>
                    <p class="lead mb-4">Ready to continue your wellness journey? Let's see what's on your schedule today.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                        <i class="fas fa-heart text-white" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 text-center hover-lift">
                    <div class="card-body">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-calendar-check fs-4"></i>
                        </div>
                        <h3 class="fw-bold text-primary"><?= $completed_sessions ?></h3>
                        <p class="text-muted mb-0">Completed Sessions</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 text-center hover-lift">
                    <div class="card-body">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-clock fs-4"></i>
                        </div>
                        <h3 class="fw-bold text-warning"><?= $pending_appointments ?></h3>
                        <p class="text-muted mb-0">Pending Requests</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 text-center hover-lift">
                    <div class="card-body">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-file-alt fs-4"></i>
                        </div>
                        <h3 class="fw-bold text-success"><?= $available_notes ?></h3>
                        <p class="text-muted mb-0">Session Notes</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100 text-center hover-lift">
                    <div class="card-body">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-star fs-4"></i>
                        </div>
                        <h3 class="fw-bold text-info"><?= count($feedback_needed) ?></h3>
                        <p class="text-muted mb-0">Feedback Needed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <h3 class="fw-bold mb-4">
                    <i class="fas fa-bolt text-primary me-2"></i>Quick Actions
                </h3>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-md" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Find a Counselor</h5>
                        <p class="text-muted mb-3">Browse our qualified counselors and book your next appointment</p>
                        <a href="find_counselor.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Counselors
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-sticky-note" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">View Session Notes</h5>
                        <p class="text-muted mb-3">Access notes from your completed counseling sessions</p>
                        <a href="my_notes.php" class="btn btn-success btn-lg">
                            <i class="fas fa-eye me-2"></i>View Notes
                            <?php if ($available_notes > 0): ?>
                                <span class="badge bg-white text-success ms-2"><?= $available_notes ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body text-center p-4">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-question-circle" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Get Support</h5>
                        <p class="text-muted mb-3">Need help or have questions? We're here to support you</p>
                        <a href="../index.php#contact" class="btn btn-info btn-lg">
                            <i class="fas fa-life-ring me-2"></i>Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upcoming Appointments -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="fw-bold mb-0">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>Upcoming Appointments
                            </h4>
                            <a href="find_counselor.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Book New
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_appointments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">No upcoming appointments</h5>
                                <p class="text-muted mb-3">Schedule your next session to continue your wellness journey</p>
                                <a href="find_counselor.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($appointment['counselor_name']) ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('l, M j, Y', strtotime($appointment['start_time'])) ?>
                                                </p>
                                                <p class="mb-1 text-muted small">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('g:i A', strtotime($appointment['start_time'])) ?> - 
                                                    <?= date('g:i A', strtotime($appointment['end_time'])) ?>
                                                </p>
                                                <?php if ($appointment['specialty']): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($appointment['specialty']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?= $appointment['status'] === 'APPROVED' ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= ucfirst(strtolower($appointment['status'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Feedback Reminders -->
                <?php if (!empty($feedback_needed)): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                            <h5 class="fw-bold mb-0 text-warning">
                                <i class="fas fa-star me-2"></i>Feedback Needed
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">Help us improve by sharing your experience</p>
                            <?php foreach ($feedback_needed as $session): ?>
                                <div class="border-start border-warning ps-3 mb-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($session['counselor_name']) ?></h6>
                                    <p class="small text-muted mb-2">
                                        Session: <?= date('M j, Y', strtotime($session['start_time'])) ?>
                                    </p>
                                    <a href="feedback.php?session_id=<?= $session['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-star me-1"></i>Leave Feedback
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Tips -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-info bg-opacity-10 border-0 py-3">
                        <h5 class="fw-bold mb-0 text-info">
                            <i class="fas fa-lightbulb me-2"></i>Wellness Tips
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="mb-3">
                                <h6 class="text-primary">🧘‍♀️ Daily Mindfulness</h6>
                                <p class="text-muted small mb-0">Try 5 minutes of deep breathing each morning</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-primary">📝 Journal Your Thoughts</h6>
                                <p class="text-muted small mb-0">Writing helps process emotions and track progress</p>
                            </div>
                            <div>
                                <h6 class="text-primary">💬 Stay Connected</h6>
                                <p class="text-muted small mb-0">Regular check-ins with your counselor work best</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .hover-lift {
        transition: transform 0.2s ease-in-out;
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
    }
    
    .card {
        border-radius: 15px;
    }
    
    .btn-lg {
        border-radius: 10px;
        padding: 12px 30px;
    }
    
    .list-group-item:last-child {
        border-bottom: none !important;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .navbar-brand {
        font-size: 1.5rem;
    }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>