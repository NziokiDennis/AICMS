<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

// Get student's appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.name as counselor_name, cp.specialty, cp.meeting_mode
    FROM appointments a 
    JOIN users u ON a.counselor_id = u.id 
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id 
    WHERE a.student_id = ? 
    ORDER BY a.start_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

// Get published notes for this student
$stmt = $pdo->prepare("
    SELECT n.*, s.id as session_id, u.name as counselor_name, a.start_time
    FROM notes n
    JOIN sessions s ON n.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON n.counselor_id = u.id
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
    <title>Student Dashboard - Counseling Portal</title>
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
                        <h2 class="fw-bold">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
                        <p class="text-muted mb-0">Manage your counseling appointments and view session notes</p>
                    </div>
                    <a href="find_counselor.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Book New Appointment
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Stats -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-check text-primary fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count($appointments) ?></h3>
                        <p class="text-muted mb-0">Total Appointments</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-clock text-warning fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'PENDING')) ?></h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle text-success fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'COMPLETED')) ?></h3>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-sticky-note text-info fs-2 mb-3"></i>
                        <h3 class="fw-bold"><?= count($notes) ?></h3>
                        <p class="text-muted mb-0">Session Notes</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Recent Appointments -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>My Appointments
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($appointments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-plus text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">No appointments yet</h5>
                                <p class="text-muted">Book your first counseling session to get started</p>
                                <a href="find_counselor.php" class="btn btn-primary">Find a Counselor</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Counselor</th>
                                            <th>Specialty</th>
                                            <th>Date & Time</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($appointment['counselor_name']) ?></td>
                                                <td><?= htmlspecialchars($appointment['specialty'] ?? 'General') ?></td>
                                                <td><?= date('M j, Y g:i A', strtotime($appointment['start_time'])) ?></td>
                                                <td>
                                                    <?php
                                                    $mode_icons = [
                                                        'IN_PERSON' => 'fas fa-user-friends',
                                                        'VIDEO' => 'fas fa-video',
                                                        'PHONE' => 'fas fa-phone'
                                                    ];
                                                    $mode_text = str_replace('_', ' ', $appointment['meeting_mode'] ?? 'TBD');
                                                    ?>
                                                    <i class="<?= $mode_icons[$appointment['meeting_mode']] ?? 'fas fa-question' ?> me-1"></i>
                                                    <?= $mode_text ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_classes = [
                                                        'PENDING' => 'badge bg-warning',
                                                        'APPROVED' => 'badge bg-success',
                                                        'DECLINED' => 'badge bg-danger',
                                                        'COMPLETED' => 'badge bg-primary',
                                                        'CANCELLED' => 'badge bg-secondary'
                                                    ];
                                                    ?>
                                                    <span class="<?= $status_classes[$appointment['status']] ?? 'badge bg-secondary' ?>">
                                                        <?= $appointment['status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($appointment['status'] === 'COMPLETED'): ?>
                                                        <a href="feedback.php?session_id=<?= $appointment['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-star me-1"></i>Feedback
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Notes -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-sticky-note text-info me-2"></i>Recent Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt text-muted fs-3 mb-2"></i>
                                <p class="text-muted mb-0">No session notes available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($notes, 0, 3) as $note): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="text-primary"><?= htmlspecialchars($note['counselor_name']) ?></strong>
                                        <small class="text-muted"><?= date('M j', strtotime($note['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 small text-muted">
                                        <?= htmlspecialchars(substr($note['content'], 0, 100)) ?>
                                        <?= strlen($note['content']) > 100 ? '...' : '' ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($notes) > 3): ?>
                                <div class="text-center">
                                    <a href="my_notes.php" class="btn btn-sm btn-outline-info">View All Notes</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>