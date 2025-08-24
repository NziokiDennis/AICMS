<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['COUNSELOR']);

// Get pending appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as student_name, u.email as student_email
    FROM appointments a 
    JOIN users u ON a.student_id = u.id 
    WHERE a.counselor_id = ? AND a.status = 'PENDING'
    ORDER BY a.start_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$pending_appointments = $stmt->fetchAll();

// Get today's approved appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as student_name, s.status as session_status
    FROM appointments a 
    JOIN users u ON a.student_id = u.id 
    LEFT JOIN sessions s ON s.appointment_id = a.id
    WHERE a.counselor_id = ? AND a.status = 'APPROVED' 
    AND DATE(a.start_time) = CURDATE()
    ORDER BY a.start_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
$today_appointments = $stmt->fetchAll();

// Get completed sessions count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed_count
    FROM appointments a 
    WHERE a.counselor_id = ? AND a.status = 'COMPLETED'
");
$stmt->execute([$_SESSION['user_id']]);
$completed_count = $stmt->fetch()['completed_count'];

// Get average rating
$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating
    FROM feedback f
    JOIN sessions s ON f.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    WHERE a.counselor_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$avg_rating = $stmt->fetch()['avg_rating'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard - Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Welcome, Dr. <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
                        <p class="text-muted mb-0">Manage your appointments and sessions</p>
                    </div>
                    <div>
                        <a href="add_note.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-plus me-2"></i>Add Note
                        </a>
                        <a href="view_feedback.php" class="btn btn-info">
                            <i class="fas fa-comments me-2"></i>View Feedback
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Stats -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-clock text-warning fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count($pending_appointments) ?></h3>
                        <p class="text-muted mb-0">Pending Approvals</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-day text-primary fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count($today_appointments) ?></h3>
                        <p class="text-muted mb-0">Today's Sessions</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle text-success fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= $completed_count ?></h3>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-star text-info fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= number_format($avg_rating, 1) ?></h3>
                        <p class="text-muted mb-0">Avg Rating</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Pending Appointments -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-clock text-warning me-2"></i>Pending Approvals
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pending_appointments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle text-success fs-1 mb-3"></i>
                                <h5 class="text-muted">All caught up!</h5>
                                <p class="text-muted">No pending appointments to review</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_appointments as $appointment): ?>
                                <div class="border-bottom p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($appointment['student_name']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($appointment['start_time'])) ?>
                                            </small>
                                            <?php if ($appointment['message']): ?>
                                                <p class="mb-2 mt-2 small"><?= htmlspecialchars($appointment['message']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <a href="approve.php?approve=<?= $appointment['id'] ?>" class="btn btn-sm btn-success me-1">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="approve.php?decline=<?= $appointment['id'] ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Sessions -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-day text-primary me-2"></i>Today's Sessions
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($today_appointments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-coffee text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">No sessions today</h5>
                                <p class="text-muted">Enjoy your free time!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($today_appointments as $appointment): ?>
                                <div class="border-bottom p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($appointment['student_name']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('g:i A', strtotime($appointment['start_time'])) ?> - 
                                                <?= date('g:i A', strtotime($appointment['end_time'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if (!$appointment['session_status'] || $appointment['session_status'] === 'SCHEDULED'): ?>
                                                <a href="session.php?start=<?= $appointment['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-play me-1"></i>Start
                                                </a>
                                            <?php elseif ($appointment['session_status'] === 'IN_PROGRESS'): ?>
                                                <a href="session.php?end=<?= $appointment['id'] ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-stop me-1"></i>End
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>