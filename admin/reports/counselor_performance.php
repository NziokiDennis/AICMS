<?php
session_start();
require_once '../../config/db.php';
require_once './../includes/auth_check.php';

requireAdminAuth();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'counselor_performance_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Counselor Name', 'Total Appointments', 'Completed Sessions', 'Completion Rate', 'Average Rating', 'Total Reviews', 'Specialties', 'Response Time (Hours)']);
    
    try {
        $stmt = $pdo->query("
            SELECT u.name,
                   COUNT(a.id) as total_appointments,
                   SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_sessions,
                   ROUND(SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0) * 100, 2) as completion_rate,
                   COALESCE(AVG(f.rating), 0) as avg_rating,
                   COUNT(f.id) as review_count,
                   cp.specialty,
                   COALESCE(AVG(TIMESTAMPDIFF(
                       HOUR, a.created_at, 
                       CASE WHEN a.status IN ('APPROVED', 'DECLINED') THEN COALESCE(a.updated_at, a.created_at) END
                   )), 0) as avg_response_hours
            FROM users u
            LEFT JOIN appointments a ON u.id = a.counselor_id
            LEFT JOIN sessions s ON a.id = s.appointment_id
            LEFT JOIN feedback f ON s.id = f.session_id
            LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
            WHERE u.role = 'COUNSELOR'
            GROUP BY u.id, u.name, cp.specialty
            ORDER BY total_appointments DESC
        ");
        
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['name'],
                $row['total_appointments'] ?? 0,
                $row['completed_sessions'] ?? 0,
                $row['completion_rate'] . '%' ?? '0%',
                number_format($row['avg_rating'], 1),
                $row['review_count'] ?? 0,
                $row['specialty'] ?: 'General Counseling',
                number_format($row['avg_response_hours'], 1)
            ]);
        }
    } catch (PDOException $e) {
        error_log("CSV Export Query failed: " . $e->getMessage() . "\nQuery: SELECT u.name, ...");
        throw $e;
    }
    
    fclose($output);
    exit;
}

// Get date range filter
$date_range = $_GET['range'] ?? '30';
switch ($date_range) {
    case '7':
        $date_condition = "AND a.start_time BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND DATE_ADD(NOW(), INTERVAL 14 DAY)";
        $range_label = "Last 7 Days";
        break;
    case '30':
        $date_condition = "AND a.start_time BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND DATE_ADD(NOW(), INTERVAL 14 DAY)";
        $range_label = "Last 30 Days";
        break;
    case '90':
        $date_condition = "AND a.start_time BETWEEN DATE_SUB(NOW(), INTERVAL 90 DAY) AND DATE_ADD(NOW(), INTERVAL 14 DAY)";
        $range_label = "Last 90 Days";
        break;
    case '365':
        $date_condition = "AND a.start_time BETWEEN DATE_SUB(NOW(), INTERVAL 365 DAY) AND DATE_ADD(NOW(), INTERVAL 14 DAY)";
        $range_label = "Last 12 Months";
        break;
    default:
        $date_condition = "";
        $range_label = "All Time";
}

// Overall counselor statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'COUNSELOR'");
    $total_counselors = $stmt->fetchColumn() ?? 0;
} catch (PDOException $e) {
    error_log("Total Counselors Query failed: " . $e->getMessage());
    $total_counselors = 0;
}

// Average response time across all appointments
try {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(AVG(TIMESTAMPDIFF(
                HOUR, a.created_at,
                CASE WHEN a.status IN ('APPROVED', 'DECLINED') THEN COALESCE(a.updated_at, a.created_at) END
            )), 0) AS avg_response_hours
        FROM appointments a
        WHERE 1=1 AND a.status IN ('APPROVED', 'DECLINED') $date_condition
    ");
    $avg_response_time = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    error_log("Avg Response Time Query failed: " . $e->getMessage() . "\nQuery: SELECT COALESCE(AVG(TIMESTAMPDIFF(HOUR, a.created_at, ...)) FROM appointments a WHERE 1=1 AND a.status IN ('APPROVED', 'DECLINED') $date_condition");
    $avg_response_time = 0;
}

// Counselor performance data for average rating in Key Metrics
try {
    $stmt = $pdo->query("
        SELECT 
            u.id, 
            u.name, 
            u.email,
            COUNT(a.id) AS total_appointments,
            SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_sessions,
            ROUND(
                (SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0)) * 100,
                2
            ) AS completion_rate,
            COALESCE(AVG(f.rating), 0) AS avg_rating,
            COUNT(f.id) AS review_count,
            cp.specialty,
            cp.meeting_mode,
            COALESCE(AVG(TIMESTAMPDIFF(
                HOUR, a.created_at,
                CASE WHEN a.status IN ('APPROVED', 'DECLINED') THEN COALESCE(a.updated_at, a.created_at) END
            )), 0) AS avg_response_hours,
            MAX(a.start_time) AS last_appointment
        FROM users u
        LEFT JOIN appointments a ON u.id = a.counselor_id
        LEFT JOIN sessions s ON a.id = s.appointment_id
        LEFT JOIN feedback f ON s.id = f.session_id
        LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
        WHERE u.role = 'COUNSELOR' $date_condition
        GROUP BY u.id, u.name, u.email, cp.specialty, cp.meeting_mode
        ORDER BY total_appointments DESC
    ");
    $counselor_data = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Counselor Performance Query failed: " . $e->getMessage() . "\nQuery: SELECT u.id, u.name, ... FROM users u LEFT JOIN appointments a ... WHERE u.role = 'COUNSELOR' $date_condition");
    $counselor_data = [];
}

// Top performing counselors by rating
try {
    $stmt = $pdo->query("
        SELECT u.name, AVG(f.rating) as avg_rating, COUNT(f.id) as review_count
        FROM users u
        JOIN appointments a ON u.id = a.counselor_id
        JOIN sessions s ON a.id = s.appointment_id
        JOIN feedback f ON s.id = f.session_id
        WHERE u.role = 'COUNSELOR' AND 1=1 $date_condition
        GROUP BY u.id, u.name
        HAVING COUNT(f.id) >= 3
        ORDER BY avg_rating DESC
        LIMIT 10
    ");
    $top_rated_counselors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Top Rated Counselors Query failed: " . $e->getMessage() . "\nQuery: SELECT u.name, AVG(f.rating) ... FROM users u JOIN appointments a ... WHERE u.role = 'COUNSELOR' AND 1=1 $date_condition");
    $top_rated_counselors = [];
}

// Counselor availability trends
try {
    $stmt = $pdo->query("
        SELECT u.name, 
               COUNT(ts.id) as total_slots,
               SUM(CASE WHEN ts.status = 'OPEN' THEN 1 ELSE 0 END) as available_slots,
               ROUND(SUM(CASE WHEN ts.status = 'OPEN' THEN 1 ELSE 0 END) / NULLIF(COUNT(ts.id), 0) * 100, 1) as availability_rate
        FROM users u
        LEFT JOIN availability_slots ts ON u.id = ts.counselor_id
        WHERE u.role = 'COUNSELOR' AND 1=1
        GROUP BY u.id, u.name
        HAVING COUNT(ts.id) > 0
        ORDER BY availability_rate DESC
        LIMIT 10
    ");
    $availability_data = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Availability Trends Query failed: " . $e->getMessage());
    $availability_data = [];
}

$current_report = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Performance - Admin Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="../../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Reports Navigation -->
<div class="container-fluid bg-white border-bottom mb-4">
    <nav class="nav nav-pills px-3 py-2">
        <a class="nav-link <?= $current_report === 'usage_analytics.php' ? 'active' : '' ?>" href="usage_analytics.php">
            <i class="fas fa-chart-line me-1"></i>Usage Analytics
        </a>
        <a class="nav-link <?= $current_report === 'counselor_performance.php' ? 'active' : '' ?>" href="counselor_performance.php">
            <i class="fas fa-user-md me-1"></i>Counselor Performance
        </a>
        <a class="nav-link <?= $current_report === 'student_engagement.php' ? 'active' : '' ?>" href="student_engagement.php">
            <i class="fas fa-graduation-cap me-1"></i>Student Engagement
        </a>
        <a class="nav-link <?= $current_report === 'system_health.php' ? 'active' : '' ?>" href="system_health.php">
            <i class="fas fa-heartbeat me-1"></i>System Health
        </a>
        <a class="nav-link <?= $current_report === 'compliance_report.php' ? 'active' : '' ?>" href="compliance_report.php">
            <i class="fas fa-clipboard-check me-1"></i>Compliance
        </a>
        <a class="nav-link ms-auto text-danger fw-bold" href="dashboard.php">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </nav>
</div>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-danger">
                        <i class="fas fa-user-md me-2"></i>Counselor Performance
                    </h1>
                    <p class="text-muted mb-0">Performance metrics and analytics for counselors</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt me-2"></i><?= $range_label ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?range=7">Last 7 Days</a></li>
                            <li><a class="dropdown-item" href="?range=30">Last 30 Days</a></li>
                            <li><a class="dropdown-item" href="?range=90">Last 90 Days</a></li>
                            <li><a class="dropdown-item" href="?range=365">Last 12 Months</a></li>
                            <li><a class="dropdown-item" href="?range=all">All Time</a></li>
                        </ul>
                    </div>
                    <a href="?export=csv&range=<?= $date_range ?>" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users text-primary fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($total_counselors) ?></h3>
                    <p class="text-muted mb-0">Total Counselors</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-clock text-info fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($avg_response_time, 1) ?>h</h3>
                    <p class="text-muted mb-0">Avg Response Time</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-star text-warning fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format(array_sum(array_column($counselor_data, 'avg_rating')) / max(count(array_filter(array_column($counselor_data, 'avg_rating'))),1), 1) ?></h3>
                    <p class="text-muted mb-0">Average Rating</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Rated Counselors -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>Top Rated Counselors
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_rated_counselors)): ?>
                        <?php foreach ($top_rated_counselors as $index => $counselor): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="badge bg-warning text-dark me-2"><?= $index + 1 ?></span>
                                    <strong><?= htmlspecialchars($counselor['name']) ?></strong>
                                    <small class="text-muted d-block ms-4">
                                        <?= $counselor['review_count'] ?> reviews
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-warning">
                                        <i class="fas fa-star"></i>
                                        <?= number_format($counselor['avg_rating'], 1) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No rating data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Availability Trends -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-calendar-alt text-info me-2"></i>Availability Trends
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Counselor</th>
                                    <th>Total Slots</th>
                                    <th>Available Slots</th>
                                    <th>Availability Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availability_data as $counselor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($counselor['name']) ?></td>
                                    <td><?= $counselor['total_slots'] ?? 0 ?></td>
                                    <td><span class="text-success"><?= $counselor['available_slots'] ?? 0 ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $counselor['availability_rate'] ?? 0 ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $counselor['availability_rate'] ?? 0 ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>