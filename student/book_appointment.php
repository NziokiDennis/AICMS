<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

$counselor_id = $_GET['counselor_id'] ?? null;
if (!$counselor_id) {
    header('Location: find_counselor.php');
    exit;
}

// Get counselor info
$stmt = $pdo->prepare("
    SELECT u.*, cp.* 
    FROM users u 
    JOIN counselor_profiles cp ON u.id = cp.user_id 
    WHERE u.id = ? AND u.role = 'COUNSELOR'
");
$stmt->execute([$counselor_id]);
$counselor = $stmt->fetch();

if (!$counselor) {
    header('Location: find_counselor.php');
    exit;
}

$success = '';
$error = '';

// Handle appointment booking
if ($_POST) {
    $slot_id = $_POST['slot_id'];
    $message = trim($_POST['message']);
    
    if ($slot_id) {
        // Get slot details
        $stmt = $pdo->prepare("SELECT * FROM availability_slots WHERE id = ? AND counselor_id = ? AND status = 'OPEN'");
        $stmt->execute([$slot_id, $counselor_id]);
        $slot = $stmt->fetch();
        
        if ($slot) {
            // Check if slot is still available (no pending/approved appointments)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM appointments 
                WHERE counselor_id = ? AND start_time = ? AND status IN ('PENDING', 'APPROVED')
            ");
            $stmt->execute([$counselor_id, $slot['start_at']]);
            $existing = $stmt->fetchColumn();
            
            if ($existing == 0) {
                // Book the appointment
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (student_id, counselor_id, start_time, end_time, message, status) 
                    VALUES (?, ?, ?, ?, ?, 'PENDING')
                ");
                
                if ($stmt->execute([$_SESSION['user_id'], $counselor_id, $slot['start_at'], $slot['end_at'], $message])) {
                    $success = 'Appointment request sent successfully! You will be notified once the counselor approves it.';
                } else {
                    $error = 'Failed to book appointment. Please try again.';
                }
            } else {
                $error = 'This slot is no longer available. Please select another time.';
            }
        } else {
            $error = 'Invalid slot selected.';
        }
    } else {
        $error = 'Please select a time slot.';
    }
}

// Get available slots for the next 2 weeks
$stmt = $pdo->prepare("
    SELECT avs.* 
    FROM availability_slots avs
    WHERE avs.counselor_id = ? 
    AND avs.status = 'OPEN'
    AND avs.start_at >= NOW()
    AND avs.start_at <= DATE_ADD(NOW(), INTERVAL 14 DAY)
    AND NOT EXISTS (
        SELECT 1 FROM appointments a 
        WHERE a.counselor_id = avs.counselor_id 
        AND a.start_time = avs.start_at 
        AND a.status IN ('PENDING', 'APPROVED')
    )
    ORDER BY avs.start_at
");
$stmt->execute([$counselor_id]);
$available_slots = $stmt->fetchAll();

// Group slots by date
$slots_by_date = [];
foreach ($available_slots as $slot) {
    $date = date('Y-m-d', strtotime($slot['start_at']));
    if (!isset($slots_by_date[$date])) {
        $slots_by_date[$date] = [];
    }
    $slots_by_date[$date][] = $slot;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Counseling Portal</title>
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
                        <li class="breadcrumb-item"><a href="find_counselor.php">Find Counselor</a></li>
                        <li class="breadcrumb-item active">Book Appointment</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <!-- Counselor Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-user-md fs-2"></i>
                        </div>
                        <h4><?= htmlspecialchars($counselor['name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($counselor['specialty'] ?? 'General Counseling') ?></p>
                        
                        <?php if ($counselor['bio']): ?>
                            <div class="text-start mt-3">
                                <h6>About</h6>
                                <p class="small text-muted"><?= htmlspecialchars($counselor['bio']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-2 mt-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Meeting Mode:</small>
                                    <span class="badge bg-info">
                                        <?php
                                        $mode_icons = [
                                            'IN_PERSON' => 'fas fa-user-friends',
                                            'VIDEO' => 'fas fa-video',
                                            'PHONE' => 'fas fa-phone'
                                        ];
                                        ?>
                                        <i class="<?= $mode_icons[$counselor['meeting_mode']] ?? 'fas fa-question' ?> me-1"></i>
                                        <?= str_replace('_', ' ', $counselor['meeting_mode'] ?? 'TBD') ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($counselor['location']): ?>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Location:</small>
                                        <small><?= htmlspecialchars($counselor['location']) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-plus text-primary me-2"></i>Select Appointment Time
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                                <div class="mt-2">
                                    <a href="dashboard.php" class="btn btn-sm btn-outline-success">Go to Dashboard</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($available_slots)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">No Available Slots</h5>
                                <p class="text-muted">This counselor has no available appointment slots in the next 2 weeks.</p>
                                <a href="find_counselor.php" class="btn btn-outline-primary">Find Another Counselor</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="bookingForm">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Available Time Slots</label>
                                        <div class="row g-3">
                                            <?php foreach ($slots_by_date as $date => $slots): ?>
                                                <div class="col-12">
                                                    <h6 class="text-primary mb-2">
                                                        <?= date('l, F j, Y', strtotime($date)) ?>
                                                    </h6>
                                                    <div class="row g-2">
                                                        <?php foreach ($slots as $slot): ?>
                                                            <div class="col-6 col-md-4 col-lg-3">
                                                                <input type="radio" class="btn-check" name="slot_id" 
                                                                       value="<?= $slot['id'] ?>" id="slot_<?= $slot['id'] ?>">
                                                                <label class="btn btn-outline-primary w-100 small" for="slot_<?= $slot['id'] ?>">
                                                                    <?= date('g:i A', strtotime($slot['start_at'])) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="message" class="form-label">Message to Counselor (Optional)</label>
                                        <textarea class="form-control" id="message" name="message" rows="3" 
                                                  placeholder="Briefly describe what you'd like to discuss..."></textarea>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Request Appointment
                                        </button>
                                        <a href="find_counselor.php" class="btn btn-outline-secondary btn-lg ms-2">Cancel</a>
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
    
    <script>
        // Form validation
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            const selectedSlot = document.querySelector('input[name="slot_id"]:checked');
            if (!selectedSlot) {
                e.preventDefault();
                alert('Please select a time slot for your appointment.');
                return false;
            }
        });
    </script>
</body>
</html>