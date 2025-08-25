<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['STUDENT']);

// Get filter parameters
$specialty_filter = $_GET['specialty'] ?? '';
$mode_filter = $_GET['mode'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query with filters
$where_conditions = ['u.role = "COUNSELOR"'];
$params = [];

if ($specialty_filter) {
    $where_conditions[] = 'cp.specialty = ?';
    $params[] = $specialty_filter;
}

if ($mode_filter) {
    $where_conditions[] = 'cp.meeting_mode = ?';
    $params[] = $mode_filter;
}

// For date filter, find counselors with available slots in that week
$date_condition = '';
if ($date_filter) {
    $date = new DateTime($date_filter);
    $week_start = $date->modify('monday this week')->format('Y-m-d 00:00:00');
    $week_end = $date->modify('sunday this week')->format('Y-m-d 23:59:59');
    
    $date_condition = "AND EXISTS (
        SELECT 1 FROM availability_slots avs 
        WHERE avs.counselor_id = u.id 
        AND avs.start_at BETWEEN ? AND ? 
        AND avs.status = 'OPEN'
        AND NOT EXISTS (
            SELECT 1 FROM appointments a 
            WHERE a.counselor_id = u.id 
            AND a.start_time = avs.start_at 
            AND a.status IN ('PENDING', 'APPROVED')
        )
    )";
    $params[] = $week_start;
    $params[] = $week_end;
}

$where_clause = implode(' AND ', $where_conditions) . $date_condition;

$sql = "SELECT u.*, cp.specialty, cp.meeting_mode, cp.bio, cp.location,
        (SELECT AVG(f.rating) FROM feedback f 
         JOIN sessions s ON f.session_id = s.id 
         JOIN appointments a ON s.appointment_id = a.id 
         WHERE a.counselor_id = u.id) as avg_rating,
        (SELECT COUNT(*) FROM appointments a 
         WHERE a.counselor_id = u.id AND a.status = 'COMPLETED') as completed_sessions
        FROM users u 
        JOIN counselor_profiles cp ON u.id = cp.user_id 
        WHERE $where_clause
        ORDER BY u.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$counselors = $stmt->fetchAll();

// Get unique specialties for filter dropdown
$specialty_stmt = $pdo->query("SELECT DISTINCT specialty FROM counselor_profiles WHERE specialty IS NOT NULL ORDER BY specialty");
$specialties = $specialty_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Counselor - Counseling Portal</title>
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
                        <li class="breadcrumb-item active">Find Counselor</li>
                    </ol>
                </nav>
                
                <h2 class="fw-bold mb-4">Find Your Counselor</h2>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="specialty" class="form-label">Specialty</label>
                                    <select class="form-select" id="specialty" name="specialty">
                                        <option value="">All Specialties</option>
                                        <?php foreach ($specialties as $specialty): ?>
                                            <option value="<?= htmlspecialchars($specialty) ?>" 
                                                <?= $specialty_filter === $specialty ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($specialty) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="mode" class="form-label">Meeting Mode</label>
                                    <select class="form-select" id="mode" name="mode">
                                        <option value="">All Modes</option>
                                        <option value="IN_PERSON" <?= $mode_filter === 'IN_PERSON' ? 'selected' : '' ?>>In Person</option>
                                        <option value="VIDEO" <?= $mode_filter === 'VIDEO' ? 'selected' : '' ?>>Video Call</option>
                                        <option value="PHONE" <?= $mode_filter === 'PHONE' ? 'selected' : '' ?>>Phone Call</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="date" class="form-label">Available Week</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?= htmlspecialchars($date_filter) ?>">
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($counselors)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search text-muted fs-1 mb-3"></i>
                            <h5 class="text-muted">No counselors found</h5>
                            <p class="text-muted">Try adjusting your search filters or check back later</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($counselors as $counselor): ?>
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="me-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user-md fs-4"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1"><?= htmlspecialchars($counselor['name']) ?></h5>
                                                <p class="text-muted mb-1"><?= htmlspecialchars($counselor['specialty'] ?? 'General Counseling') ?></p>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="me-3">
                                                        <?php if ($counselor['avg_rating']): ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-star me-1"></i><?= number_format($counselor['avg_rating'], 1) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">New</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= $counselor['completed_sessions'] ?> sessions completed
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($counselor['bio']): ?>
                                            <p class="text-muted small mb-3">
                                                <?= htmlspecialchars(substr($counselor['bio'], 0, 150)) ?>
                                                <?= strlen($counselor['bio']) > 150 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Meeting Mode</small>
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
                                            <?php if ($counselor['location']): ?>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Location</small>
                                                    <small><?= htmlspecialchars($counselor['location']) ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-end">
                                            <a href="book_appointment.php?counselor_id=<?= $counselor['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>