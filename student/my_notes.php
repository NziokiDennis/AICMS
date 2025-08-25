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
    </nav><?php
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
    <title>My Session Notes - Counseling Portal</title>
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
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Session Notes</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">My Session Notes</h2>
                        <p class="text-muted mb-0">Review notes from your completed counseling sessions</p>
                    </div>
                    <span class="badge bg-info fs-6"><?= count($notes) ?> Notes</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if (empty($notes)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-file-alt text-muted fs-1 mb-3"></i>
                            <h5 class="text-muted">No Session Notes Available</h5>
                            <p class="text-muted">Session notes will appear here after your counselor publishes them following completed sessions.</p>
                            <a href="find_counselor.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Book Your First Session
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($notes as $note): ?>
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white border-0 py-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1 fw-bold text-primary">
                                                    <i class="fas fa-user-md me-2"></i><?= htmlspecialchars($note['counselor_name']) ?>
                                                </h5>
                                                <div class="d-flex align-items-center text-muted">
                                                    <small class="me-3">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Session: <?= date('M j, Y g:i A', strtotime($note['start_time'])) ?>
                                                    </small>
                                                    <?php if ($note['specialty']): ?>
                                                        <small class="me-3">
                                                            <i class="fas fa-tag me-1"></i>
                                                            <?= htmlspecialchars($note['specialty']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <small>
                                                        <i class="fas fa-sticky-note me-1"></i>
                                                        Published: <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <span class="badge bg-success">Published</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="note-content">
                                            <?= nl2br(htmlspecialchars($note['content'])) ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Session Duration: <?= date('g:i A', strtotime($note['start_time'])) ?> - <?= date('g:i A', strtotime($note['end_time'])) ?>
                                            </small>
                                            <div>
                                                <?php
                                                // Check if feedback already submitted for this session
                                                $feedback_stmt = $pdo->prepare("SELECT id FROM feedback WHERE student_id = ? AND session_id = ?");
                                                $feedback_stmt->execute([$_SESSION['user_id'], $note['session_id']]);
                                                $has_feedback = $feedback_stmt->fetch();
                                                ?>
                                                <?php if (!$has_feedback): ?>
                                                    <a href="feedback.php?session_id=<?= $note['session_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-star me-1"></i>Leave Feedback
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Feedback Submitted
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination could be added here for large numbers of notes -->
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .note-content {
        line-height: 1.6;
        font-size: 15px;
        color: #495057;
    }
    
    .card-header {
        border-bottom: 1px solid #e9ecef;
    }
    
    .badge {
        font-size: 0.8em;
    }
    </style>
</body>
</html>