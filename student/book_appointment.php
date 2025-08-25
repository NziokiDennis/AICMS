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
    $slot_id = $_POST['slot_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
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
    <title>Book Appointment - Happy Hearts Counseling</title>
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
    
    <!-- Header -->
    <div class="bg-gradient-primary text-white py-4">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">
                <i class="fas fa-calendar-plus me-3"></i>Book Appointment
            </h1>
            <p class="lead mb-0">Schedule your session with <?= htmlspecialchars($counselor['name']) ?></p>
        </div>
    </div>
    
    <div class="container py-5">
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

        <div class="row g-4">
            <!-- Counselor Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body text-center p-4">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-user-md" style="font-size: 2.5rem;"></i>
                        </div>
                        <h4 class="fw-bold"><?= htmlspecialchars($counselor['name']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($counselor['specialty'] ?? 'General Counseling') ?></p>
                        
                        <?php if ($counselor['bio']): ?>
                            <div class="text-start mb-4">
                                <h6 class="fw-bold text-primary">About</h6>
                                <p class="small text-muted"><?= htmlspecialchars($counselor['bio']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-3 text-start">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <small class="text-muted fw-bold">Meeting Mode:</small>
                                    <span class="badge bg-info fs-6">
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
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <small class="text-muted fw-bold">Location:</small>
                                        <small class="text-end"><?= htmlspecialchars($counselor['location']) ?></small>
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
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0 fw-bold">
                            <i class="fas fa-clock text-primary me-2"></i>Select Appointment Time
                        </h4>
                        <p class="text-muted mb-0 mt-2">Choose from available time slots in the next 2 weeks</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fs-2 me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Booking Successful!</h5>
                                        <p class="mb-3"><?= $success ?></p>
                                        <div class="d-flex gap-2">
                                            <a href="dashboard.php" class="btn btn-success">
                                                <i class="fas fa-tachometer-alt me-1"></i>Go to Dashboard
                                            </a>
                                            <a href="find_counselor.php" class="btn btn-outline-success">
                                                <i class="fas fa-search me-1"></i>Find Another Counselor
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

                        <?php if (empty($available_slots)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-muted fw-bold mb-3">No Available Slots</h4>
                                <p class="text-muted mb-4">This counselor has no available appointment slots in the next 2 weeks. Please check back later or try another counselor.</p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="find_counselor.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-search me-2"></i>Find Another Counselor
                                    </a>
                                    <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="bookingForm">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold fs-5 mb-3">Available Time Slots</label>
                                        <div class="row g-4">
                                            <?php foreach ($slots_by_date as $date => $slots): ?>
                                                <div class="col-12">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-header bg-primary text-white">
                                                            <h6 class="mb-0 fw-bold">
                                                                <i class="fas fa-calendar-day me-2"></i>
                                                                <?= date('l, F j, Y', strtotime($date)) ?>
                                                            </h6>
                                                        </div>
                                                        <div class="card-body p-3">
                                                            <div class="row g-2">
                                                                <?php foreach ($slots as $slot): ?>
                                                                    <div class="col-6 col-md-4 col-lg-3">
                                                                        <input type="radio" class="btn-check" name="slot_id" 
                                                                               value="<?= $slot['id'] ?>" id="slot_<?= $slot['id'] ?>">
                                                                        <label class="btn btn-outline-primary w-100 p-3 time-slot" for="slot_<?= $slot['id'] ?>">
                                                                            <i class="fas fa-clock me-1"></i>
                                                                            <?= date('g:i A', strtotime($slot['start_at'])) ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="message" class="form-label fw-bold">Message to Counselor (Optional)</label>
                                        <textarea class="form-control form-control-lg" id="message" name="message" rows="4" 
                                                  placeholder="Briefly describe what you'd like to discuss in this session..."></textarea>
                                        <small class="text-muted">This helps your counselor prepare for your session</small>
                                    </div>

                                    <div class="col-12 text-center">
                                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                                <i class="fas fa-paper-plane me-2"></i>Request Appointment
                                            </button>
                                            <a href="find_counselor.php" class="btn btn-outline-secondary btn-lg px-4">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Search
                                            </a>
                                        </div>
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
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .time-slot {
        transition: all 0.2s ease;
        border-radius: 10px !important;
        font-weight: 600;
    }
    
    .time-slot:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-check:checked + .time-slot {
        background-color: #667eea !important;
        border-color: #667eea !important;
        color: white !important;
        transform: scale(1.05);
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
    
    .form-control-lg {
        border-radius: 10px;
    }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            const selectedSlot = document.querySelector('input[name="slot_id"]:checked');
            if (!selectedSlot) {
                e.preventDefault();
                
                // Show nice alert
                const alertHtml = `
                    <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Please select a time slot</strong> before submitting your appointment request.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                const form = document.getElementById('bookingForm');
                form.insertAdjacentHTML('afterbegin', alertHtml);
                
                // Scroll to top of form
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                return false;
            }
        });

        // Add visual feedback for time slot selection
        document.querySelectorAll('input[name="slot_id"]').forEach(input => {
            input.addEventListener('change', function() {
                // Remove previous selection styling
                document.querySelectorAll('.time-slot').forEach(label => {
                    label.classList.remove('selected-slot');
                });
                
                // Add styling to selected slot
                if (this.checked) {
                    document.querySelector(`label[for="${this.id}"]`).classList.add('selected-slot');
                }
            });
        });
    </script>
</body>
</html>