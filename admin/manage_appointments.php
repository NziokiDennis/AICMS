<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAdminAuth();

// Handle appointment actions
$message = '';
$message_type = 'info';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'force_approve':
                $appointment_id = $_POST['appointment_id'];
                
                $pdo->beginTransaction();
                
                // Update appointment status
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'APPROVED' WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                // Create session if it doesn't exist
                $stmt = $pdo->prepare("SELECT id FROM sessions WHERE appointment_id = ?");
                $stmt->execute([$appointment_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO sessions (appointment_id, status) VALUES (?, 'SCHEDULED')");
                    $stmt->execute([$appointment_id]);
                }
                
                $pdo->commit();
                $message = "Appointment force-approved successfully";
                $message_type = 'success';
                break;
                
            case 'force_decline':
                $appointment_id = $_POST['appointment_id'];
                $reason = $_POST['reason'] ?? 'Declined by admin';
                
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'DECLINED' WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                $message = "Appointment declined: " . htmlspecialchars($reason);
                $message_type = 'warning';
                break;
                
            case 'cancel_appointment':
                $appointment_id = $_POST['appointment_id'];
                $reason = $_POST['reason'] ?? 'Cancelled by admin';
                
                $pdo->beginTransaction();
                
                // Update appointment
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'CANCELLED' WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                // Update related session if exists
                $stmt = $pdo->prepare("UPDATE sessions SET status = 'CANCELLED' WHERE appointment_id = ?");
                $stmt->execute([$appointment_id]);
                
                $pdo->commit();
                $message = "Appointment cancelled: " . htmlspecialchars($reason);
                $message_type = 'warning';
                break;
                
            case 'force_complete':
                $appointment_id = $_POST['appointment_id'];
                
                $pdo->beginTransaction();
                
                // Update appointment
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'COMPLETED' WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                // Update or create session
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (appointment_id, status, started_at, ended_at) 
                    VALUES (?, 'COMPLETED', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    status = 'COMPLETED', 
                    ended_at = COALESCE(ended_at, NOW()),
                    started_at = COALESCE(started_at, NOW())
                ");
                $stmt->execute([$appointment_id]);
                
                $pdo->commit();
                $message = "Appointment marked as completed";
                $message_type = 'success';
                break;
                
            case 'fix_stuck_session':
                $session_id = $_POST['session_id'];
                $action_type = $_POST['action_type'];
                
                if ($action_type === 'end') {
                    $stmt = $pdo->prepare("
                        UPDATE sessions SET status = 'COMPLETED', ended_at = NOW() WHERE id = ?
                    ");
                    $stmt->execute([$session_id]);
                    
                    $stmt = $pdo->prepare("
                        UPDATE appointments a 
                        JOIN sessions s ON a.id = s.appointment_id 
                        SET a.status = 'CANCELLED' 
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$session_id]);
                    
                    $message = "Stuck session cancelled successfully";
                }
                $message_type = 'success';
                break;
                
            case 'reschedule_appointment':
                $appointment_id = $_POST['appointment_id'];
                $new_start = $_POST['new_start_time'];
                $new_end = $_POST['new_end_time'];
                
                $stmt = $pdo->prepare("
                    UPDATE appointments 
                    SET start_time = ?, end_time = ?, status = 'APPROVED'
                    WHERE id = ?
                ");
                $stmt->execute([$new_start, $new_end, $appointment_id]);
                
                $message = "Appointment rescheduled successfully";
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$counselor_filter = $_GET['counselor'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT a.*, 
           s_user.name as student_name, s_user.email as student_email, s_user.phone as student_phone,
           c_user.name as counselor_name, c_user.email as counselor_email,
           cp.specialty, cp.meeting_mode, cp.location,
           sess.id as session_id, sess.status as session_status, sess.started_at, sess.ended_at,
           (CASE 
               WHEN sess.status = 'IN_PROGRESS' AND sess.started_at < DATE_SUB(NOW(), INTERVAL 2 HOUR) 
               THEN 1 ELSE 0 
           END) as is_stuck
    FROM appointments a
    JOIN users s_user ON a.student_id = s_user.id
    JOIN users c_user ON a.counselor_id = c_user.id
    LEFT JOIN counselor_profiles cp ON c_user.id = cp.user_id
    LEFT JOIN sessions sess ON a.id = sess.appointment_id
    WHERE 1=1
";

$params = [];
if ($status_filter) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}
if ($counselor_filter) {
    $query .= " AND a.counselor_id = ?";
    $params[] = $counselor_filter;
}
if ($date_from) {
    $query .= " AND DATE(a.start_time) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(a.start_time) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY a.start_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get counselors for filter
$stmt = $pdo->query("SELECT u.id, u.name FROM users u WHERE u.role = 'COUNSELOR' ORDER BY u.name");
$counselors = $stmt->fetchAll();

// Get stuck sessions
$stmt = $pdo->query("
    SELECT s.*, a.start_time, a.end_time,
           st_user.name as student_name, c_user.name as counselor_name
    FROM sessions s
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users st_user ON a.student_id = st_user.id
    JOIN users c_user ON a.counselor_id = c_user.id
    WHERE s.status = 'IN_PROGRESS' 
    AND s.started_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
");
$stuck_sessions = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 fw-bold text-danger">
                            <i class="fas fa-calendar-alt me-2"></i>Manage Appointments
                        </h1>
                        <p class="text-muted mb-0">Override and manage all system appointments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stuck Sessions Alert -->
        <?php if (!empty($stuck_sessions)): ?>
            <div class="alert alert-warning" id="stuck-sessions">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Stuck Sessions Detected
                        </h5>
                        <p class="mb-0"><?= count($stuck_sessions) ?> session(s) have been running for over 2 hours</p>
                    </div>
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#stuckSessionsList">
                        View Details
                    </button>
                </div>
                
                <div class="collapse mt-3" id="stuckSessionsList">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Counselor</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stuck_sessions as $session): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($session['student_name']) ?></td>
                                        <td><?= htmlspecialchars($session['counselor_name']) ?></td>
                                        <td><?= date('M j, g:i A', strtotime($session['started_at'])) ?></td>
                                        <td>
                                            <?php
                                            $duration = (time() - strtotime($session['started_at'])) / 3600;
                                            echo number_format($duration, 1) . ' hours';
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success me-1" 
                                                    data-bs-toggle="modal" data-bs-target="#fixSessionModal"
                                                    data-session-id="<?= $session['id'] ?>"
                                                    data-action="end">
                                                <i class="fas fa-check"></i> End
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#fixSessionModal"
                                                    data-session-id="<?= $session['id'] ?>"
                                                    data-action="cancel">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-clock text-warning fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= $stats['PENDING'] ?? 0 ?></h5>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-check text-success fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= $stats['APPROVED'] ?? 0 ?></h5>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-times text-danger fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= $stats['DECLINED'] ?? 0 ?></h5>
                        <small class="text-muted">Declined</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-check-circle text-info fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= $stats['COMPLETED'] ?? 0 ?></h5>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-ban text-secondary fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= $stats['CANCELLED'] ?? 0 ?></h5>
                        <small class="text-muted">Cancelled</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <i class="fas fa-calendar text-primary fs-4 mb-2"></i>
                        <h5 class="fw-bold"><?= array_sum($stats) ?></h5>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                            <option value="APPROVED" <?= $status_filter === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                            <option value="DECLINED" <?= $status_filter === 'DECLINED' ? 'selected' : '' ?>>Declined</option>
                            <option value="COMPLETED" <?= $status_filter === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                            <option value="CANCELLED" <?= $status_filter === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Counselor Filter</label>
                        <select name="counselor" class="form-select">
                            <option value="">All Counselors</option>
                            <?php foreach ($counselors as $counselor): ?>
                                <option value="<?= $counselor['id'] ?>" <?= $counselor_filter == $counselor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($counselor['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Appointments (<?= count($appointments) ?> found)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Appointment Details</th>
                                <th>Participants</th>
                                <th>Session Info</th>
                                <th>Status</th>
                                <th class="text-center">Admin Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr <?= $appointment['is_stuck'] ? 'class="table-warning"' : '' ?>>
                                    <td>
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M j, Y', strtotime($appointment['start_time'])) ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('g:i A', strtotime($appointment['start_time'])) ?> - 
                                                <?= date('g:i A', strtotime($appointment['end_time'])) ?>
                                            </p>
                                            <?php if ($appointment['specialty']): ?>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($appointment['specialty']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($appointment['meeting_mode']): ?>
                                                <span class="badge bg-secondary"><?= str_replace('_', ' ', $appointment['meeting_mode']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($appointment['is_stuck']): ?>
                                                <span class="badge bg-warning text-dark">STUCK SESSION</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-2">
                                            <strong>Student:</strong><br>
                                            <?= htmlspecialchars($appointment['student_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($appointment['student_email']) ?></small>
                                        </div>
                                        <div>
                                            <strong>Counselor:</strong><br>
                                            <?= htmlspecialchars($appointment['counselor_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($appointment['counselor_email']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($appointment['session_id']): ?>
                                            <span class="badge bg-<?= 
                                                $appointment['session_status'] === 'COMPLETED' ? 'success' : 
                                                ($appointment['session_status'] === 'IN_PROGRESS' ? 'warning' : 'info') 
                                            ?>">
                                                <?= $appointment['session_status'] ?>
                                            </span>
                                            <?php if ($appointment['started_at']): ?>
                                                <br><small class="text-muted">Started: <?= date('M j, g:i A', strtotime($appointment['started_at'])) ?></small>
                                            <?php endif; ?>
                                            <?php if ($appointment['ended_at']): ?>
                                                <br><small class="text-muted">Ended: <?= date('M j, g:i A', strtotime($appointment['ended_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">No Session</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $appointment['status'] === 'APPROVED' ? 'success' : 
                                            ($appointment['status'] === 'PENDING' ? 'warning' : 
                                            ($appointment['status'] === 'COMPLETED' ? 'info' : 'secondary'))
                                        ?>">
                                            <?= $appointment['status'] ?>
                                        </span>
                                        <br><small class="text-muted">
                                            Created: <?= date('M j', strtotime($appointment['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical" role="group">
                                            <?php if ($appointment['status'] === 'PENDING'): ?>
                                                <button class="btn btn-sm btn-success mb-1" 
                                                        onclick="forceAction('approve', <?= $appointment['id'] ?>)">
                                                    <i class="fas fa-check"></i> Force Approve
                                                </button>
                                                <button class="btn btn-sm btn-danger mb-1" 
                                                        data-bs-toggle="modal" data-bs-target="#declineModal"
                                                        data-appointment-id="<?= $appointment['id'] ?>">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            <?php elseif ($appointment['status'] === 'APPROVED'): ?>
                                                <button class="btn btn-sm btn-info mb-1" 
                                                        onclick="forceAction('complete', <?= $appointment['id'] ?>)">
                                                    <i class="fas fa-check-circle"></i> Mark Complete
                                                </button>
                                                <button class="btn btn-sm btn-warning mb-1" 
                                                        data-bs-toggle="modal" data-bs-target="#rescheduleModal"
                                                        data-appointment-id="<?= $appointment['id'] ?>"
                                                        data-start-time="<?= $appointment['start_time'] ?>"
                                                        data-end-time="<?= $appointment['end_time'] ?>">
                                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!in_array($appointment['status'], ['CANCELLED', 'COMPLETED'])): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#cancelModal"
                                                        data-appointment-id="<?= $appointment['id'] ?>">
                                                    <i class="fas fa-ban"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-muted fs-1"></i>
                        <h5 class="text-muted mt-3">No appointments found</h5>
                        <p class="text-muted">Try adjusting your filter criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Decline Modal -->
    <div class="modal fade" id="declineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fas fa-times-circle me-2"></i>Decline Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="force_decline">
                        <input type="hidden" name="appointment_id" id="declineAppointmentId">
                        
                        <div class="mb-3">
                            <label for="declineReason" class="form-label">Reason for Decline</label>
                            <textarea class="form-control" name="reason" id="declineReason" 
                                      rows="3" placeholder="Enter reason for declining..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-times me-2"></i>Decline Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-ban me-2"></i>Cancel Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cancel_appointment">
                        <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This will permanently cancel the appointment and any associated session.
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancelReason" class="form-label">Reason for Cancellation</label>
                            <textarea class="form-control" name="reason" id="cancelReason" 
                                      rows="3" placeholder="Enter reason for cancellation..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban me-2"></i>Cancel Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div class="modal fade" id="rescheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>Reschedule Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reschedule_appointment">
                        <input type="hidden" name="appointment_id" id="rescheduleAppointmentId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="newStartTime" class="form-label">New Start Time</label>
                                <input type="datetime-local" class="form-control" name="new_start_time" 
                                       id="newStartTime" required>
                            </div>
                            <div class="col-md-6">
                                <label for="newEndTime" class="form-label">New End Time</label>
                                <input type="datetime-local" class="form-control" name="new_end_time" 
                                       id="newEndTime" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Reschedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Fix Session Modal -->
    <div class="modal fade" id="fixSessionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fas fa-wrench me-2"></i>Fix Stuck Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="fix_stuck_session">
                        <input type="hidden" name="session_id" id="fixSessionId">
                        <input type="hidden" name="action_type" id="fixActionType">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This session has been running for over 2 hours. Choose how to resolve it:
                        </div>
                        
                        <p id="fixSessionAction"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="fixSessionBtn">
                            <i class="fas fa-wrench me-2"></i>Fix Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        function forceAction(action, appointmentId) {
            if (confirm(`Are you sure you want to force ${action} this appointment?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="force_${action}">
                    <input type="hidden" name="appointment_id" value="${appointmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Handle modal data
        document.addEventListener('DOMContentLoaded', function() {
            // Decline modal
            document.querySelectorAll('[data-bs-target="#declineModal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('declineAppointmentId').value = this.dataset.appointmentId;
                });
            });

            // Cancel modal
            document.querySelectorAll('[data-bs-target="#cancelModal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('cancelAppointmentId').value = this.dataset.appointmentId;
                });
            });

            // Reschedule modal
            document.querySelectorAll('[data-bs-target="#rescheduleModal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('rescheduleAppointmentId').value = this.dataset.appointmentId;
                    
                    // Set current times as default
                    const startTime = new Date(this.dataset.startTime).toISOString().slice(0, 16);
                    const endTime = new Date(this.dataset.endTime).toISOString().slice(0, 16);
                    document.getElementById('newStartTime').value = startTime;
                    document.getElementById('newEndTime').value = endTime;
                });
            });

            // Fix session modal
            document.querySelectorAll('[data-bs-target="#fixSessionModal"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const sessionId = this.dataset.sessionId;
                    const action = this.dataset.action;
                    
                    document.getElementById('fixSessionId').value = sessionId;
                    document.getElementById('fixActionType').value = action;
                    
                    if (action === 'end') {
                        document.getElementById('fixSessionAction').textContent = 
                            'This will end the session and mark it as completed.';
                        document.getElementById('fixSessionBtn').innerHTML = 
                            '<i class="fas fa-check me-2"></i>End Session';
                        document.getElementById('fixSessionBtn').className = 'btn btn-success';
                    } else {
                        document.getElementById('fixSessionAction').textContent = 
                            'This will cancel the session and mark the appointment as cancelled.';
                        document.getElementById('fixSessionBtn').innerHTML = 
                            '<i class="fas fa-times me-2"></i>Cancel Session';
                        document.getElementById('fixSessionBtn').className = 'btn btn-danger';
                    }
                });
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
                       