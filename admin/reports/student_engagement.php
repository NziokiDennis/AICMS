<?php
session_start();
require_once '../../config/db.php';
require_once './../includes/auth_check.php';
requireAdminAuth();


// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'student_engagement_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // Export student engagement data
    fputcsv($output, [
        'Student Name', 'Email', 'Total Appointments', 'Completed Sessions',
        'Cancelled', 'Engagement Score', 'First Appointment', 'Last Appointment', 'Average Rating Given'
    ]);

    $stmt = $pdo->query("
        SELECT u.name, u.email,
               COUNT(a.id) as total_appointments,
               SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_sessions,
               SUM(CASE WHEN a.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
               ROUND((SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) * 2 + 
                     COUNT(a.id) - SUM(CASE WHEN a.status = 'CANCELLED' THEN 1 ELSE 0 END)) / 3, 1) as engagement_score,
               MIN(a.start_time) as first_appointment,
               MAX(a.start_time) as last_appointment,
               AVG(f.rating) as avg_rating_given
        FROM users u
        LEFT JOIN appointments a ON u.id = a.student_id
        LEFT JOIN sessions s ON a.id = s.appointment_id
        LEFT JOIN feedback f ON s.id = f.session_id
        WHERE u.role = 'STUDENT'
        GROUP BY u.id, u.name, u.email
        HAVING COUNT(a.id) > 0
        ORDER BY engagement_score DESC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            $row['email'],
            $row['total_appointments'],
            $row['completed_sessions'],
            $row['cancelled'],
            $row['engagement_score'],
            $row['first_appointment'],
            $row['last_appointment'],
            $row['avg_rating_given'] ? number_format($row['avg_rating_given'], 1) : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}

// Get date range filter
$date_range = $_GET['range'] ?? '30';

switch ($date_range) {
    case '7':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $range_label = "Last 7 Days";
        break;
    case '30':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $range_label = "Last 30 Days";
        break;
    case '90':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $range_label = "Last 90 Days";
        break;
    case '365':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $range_label = "Last 12 Months";
        break;
    default:
        $date_condition = "";
        $range_label = "All Time";
}

// Overall student statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'STUDENT'");
$total_students = (int)$stmt->fetchColumn();

// Active students (distinct students with appointments in range)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT a.student_id) 
    FROM appointments a 
    WHERE a.student_id IS NOT NULL $date_condition
");
$active_students = (int)$stmt->fetchColumn();

// Engaged students (students with completed sessions in range)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT a.student_id) 
    FROM appointments a
    JOIN sessions s ON a.id = s.appointment_id
    WHERE s.status = 'COMPLETED' $date_condition
");
$engaged_students = (int)$stmt->fetchColumn();

// Student engagement levels
$stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN appointment_count >= 5 THEN 'Highly Engaged'
            WHEN appointment_count >= 2 THEN 'Moderately Engaged'
            WHEN appointment_count = 1 THEN 'Low Engagement'
            ELSE 'No Engagement'
        END as engagement_level,
        COUNT(*) as student_count
    FROM (
        SELECT a.student_id, COUNT(*) as appointment_count
        FROM appointments a
        WHERE a.student_id IS NOT NULL $date_condition
        GROUP BY a.student_id
    ) student_stats
    GROUP BY engagement_level
    ORDER BY 
        CASE engagement_level
            WHEN 'Highly Engaged' THEN 1
            WHEN 'Moderately Engaged' THEN 2
            WHEN 'Low Engagement' THEN 3
            ELSE 4
        END
");
$engagement_levels = $stmt->fetchAll();


// Most requested specialties by students
$stmt = $pdo->query("
    SELECT cp.specialty, COUNT(*) as request_count
    FROM appointments a
    JOIN counselor_profiles cp ON a.counselor_id = cp.user_id
    WHERE a.student_id IS NOT NULL $date_condition
    GROUP BY cp.specialty
    ORDER BY request_count DESC
    LIMIT 8
");
$specialty_preferences = $stmt->fetchAll();

// Student feedback patterns
$stmt = $pdo->query("
    SELECT 
        f.rating,
        COUNT(*) as feedback_count,
        AVG(LENGTH(f.comment)) as avg_comment_length
    FROM feedback f
    JOIN sessions s ON f.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    WHERE f.rating IS NOT NULL $date_condition
    GROUP BY f.rating
    ORDER BY f.rating DESC
");
$feedback_patterns = $stmt->fetchAll();

// Peak booking patterns by students
$stmt = $pdo->query("
    SELECT HOUR(a.start_time) as hour, COUNT(*) as booking_count
    FROM appointments a
    WHERE a.student_id IS NOT NULL $date_condition
    GROUP BY hour
    ORDER BY hour
");
$hourly_bookings = array_fill(0, 24, 0);
while ($row = $stmt->fetch()) {
    $hour = (int)$row['hour'];
    $hourly_bookings[$hour] = (int)$row['booking_count'];
}

// Top engaged students
$stmt = $pdo->query("
    SELECT u.name, u.email,
           COUNT(a.id) as total_appointments,
           SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_sessions,
           SUM(CASE WHEN a.status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
           ROUND((SUM(CASE WHEN s.status = 'COMPLETED' THEN 1 ELSE 0 END) * 2 + 
                 COUNT(a.id) - SUM(CASE WHEN a.status = 'CANCELLED' THEN 1 ELSE 0 END)) / 3, 1) as engagement_score,
           MIN(a.start_time) as first_appointment,
           MAX(a.start_time) as last_appointment,
           AVG(f.rating) as avg_rating_given
    FROM users u
    LEFT JOIN appointments a ON u.id = a.student_id
    LEFT JOIN sessions s ON a.id = s.appointment_id
    LEFT JOIN feedback f ON s.id = f.session_id
    WHERE u.role = 'STUDENT' $date_condition
    GROUP BY u.id, u.name, u.email
    HAVING COUNT(a.id) > 0
    ORDER BY engagement_score DESC
    LIMIT 15
");
$top_students = $stmt->fetchAll();

// Monthly student activity trends
$stmt = $pdo->query("
    SELECT DATE_FORMAT(a.start_time, '%Y-%m') as month,
           COUNT(DISTINCT a.student_id) as unique_students,
           COUNT(*) as total_appointments,
           AVG(CASE WHEN a.status = 'COMPLETED' THEN 1.0 ELSE 0.0 END) * 100 as completion_rate
    FROM appointments a
    WHERE a.student_id IS NOT NULL 
      AND a.start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month ASC
");
$monthly_trends = $stmt->fetchAll();

// No-show analysis
$stmt = $pdo->query("
    SELECT COUNT(*) as total_no_shows,
           COUNT(DISTINCT student_id) as students_with_no_shows,
           AVG(no_show_count) as avg_no_shows_per_student
    FROM (
        SELECT student_id, COUNT(*) as no_show_count
        FROM appointments a
        WHERE a.status = 'NO_SHOW' $date_condition
        GROUP BY student_id
    ) no_show_stats
");
$no_show_data = $stmt->fetch();

// safe max helpers for progress width calculations
$max_specialty_requests = !empty($specialty_preferences) ? max(array_column($specialty_preferences, 'request_count')) : 1;
$max_retention_count = !empty($retention_data) ? max(array_column($retention_data, 'student_count')) : 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Engagement - Admin Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="../../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Reports Navigation -->
<div class="container-fluid bg-white border-bottom mb-4">
    <nav class="nav nav-pills px-3 py-2">
        <a class="nav-link <?= $current_report === 'usage_analytics.php' ? 'active' : '' ?>" 
           href="usage_analytics.php">
           <i class="fas fa-chart-line me-1"></i>Usage Analytics
        </a>
        <a class="nav-link <?= $current_report === 'counselor_performance.php' ? 'active' : '' ?>" 
           href="counselor_performance.php">
           <i class="fas fa-user-md me-1"></i>Counselor Performance
        </a>
        <a class="nav-link <?= $current_report === 'student_engagement.php' ? 'active' : '' ?>" 
           href="student_engagement.php">
           <i class="fas fa-graduation-cap me-1"></i>Student Engagement
        </a>
        <a class="nav-link <?= $current_report === 'system_health.php' ? 'active' : '' ?>" 
           href="system_health.php">
           <i class="fas fa-heartbeat me-1"></i>System Health
        </a>
        <a class="nav-link <?= $current_report === 'compliance_report.php' ? 'active' : '' ?>" 
           href="compliance_report.php">
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
                        <i class="fas fa-graduation-cap me-2"></i>Student Engagement
                    </h1>
                    <p class="text-muted mb-0">Student participation and engagement analytics</p>
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
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users text-primary fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($total_students) ?></h3>
                    <p class="text-muted mb-0">Total Students</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-user-check text-success fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($active_students) ?></h3>
                    <p class="text-muted mb-0">Active Students</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-heart text-danger fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($engaged_students) ?></h3>
                    <p class="text-muted mb-0">Engaged Students</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-percentage text-info fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $total_students > 0 ? number_format(($active_students / $total_students) * 100, 1) : 0 ?>%</h3>
                    <p class="text-muted mb-0">Engagement Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Engagement Levels -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Engagement Levels
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="engagementChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Trends -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-line text-success me-2"></i>Monthly Activity Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Preferred Specialties -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-heart text-danger me-2"></i>Preferred Specialties
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($specialty_preferences as $index => $specialty): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge bg-success me-2"><?= $index + 1 ?></span>
                                <?= htmlspecialchars($specialty['specialty'] ?: 'General Counseling') ?>
                            </div>
                            <span class="fw-bold"><?= $specialty['request_count'] ?></span>
                        </div>
                        <div class="progress mb-3" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: <?= ($specialty['request_count'] / $max_specialty_requests) * 100 ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Feedback Patterns -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-star text-warning me-2"></i>Feedback Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($feedback_patterns as $feedback): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-2"><?= $feedback['rating'] ?> Stars</span>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold"><?= $feedback['feedback_count'] ?></div>
                                <small class="text-muted"><?= number_format($feedback['avg_comment_length']) ?> chars</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Peak Booking Hours -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-clock text-info me-2"></i>Student Booking Patterns (Hourly)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Engaged Students -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>Most Engaged Students
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Student</th>
                                    <th>Appointments</th>
                                    <th>Completed</th>
                                    <th>Cancelled</th>
                                    <th>Engagement Score</th>
                                    <th>Avg Rating Given</th>
                                    <th>First Visit</th>
                                    <th>Last Visit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_students as $index => $student): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span class="badge bg-<?= $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'dark') ?>">
                                                <?= $index + 1 ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark"><?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($student['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                    </td>
                                    <td><span class="fw-bold"><?= $student['total_appointments'] ?></span></td>
                                    <td><span class="text-success"><?= $student['completed_sessions'] ?></span></td>
                                    <td><span class="text-danger"><?= $student['cancelled'] ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                <div class="progress-bar bg-primary" style="width: <?= min(100, $student['engagement_score'] * 10) ?>%"></div>
                                            </div>
                                            <span class="fw-bold"><?= $student['engagement_score'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($student['avg_rating_given']): ?>
                                            <span class="text-warning">
                                                <i class="fas fa-star"></i>
                                                <?= number_format($student['avg_rating_given'], 1) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $student['first_appointment'] ? date('M j, Y', strtotime($student['first_appointment'])) : '-' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $student['last_appointment'] ? date('M j, Y', strtotime($student['last_appointment'])) : '-' ?>
                                        </small>
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

    <!-- No-Show Analysis -->
    <?php if ($no_show_data && $no_show_data['total_no_shows'] > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="fw-bold mb-0 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>No-Show Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h4 class="fw-bold text-danger"><?= number_format($no_show_data['total_no_shows']) ?></h4>
                            <p class="text-muted">Total No-Shows</p>
                        </div>
                        <div class="col-md-4">
                            <h4 class="fw-bold text-warning"><?= number_format($no_show_data['students_with_no_shows']) ?></h4>
                            <p class="text-muted">Students with No-Shows</p>
                        </div>
                        <div class="col-md-4">
                            <h4 class="fw-bold text-info"><?= number_format($no_show_data['avg_no_shows_per_student'], 1) ?></h4>
                            <p class="text-muted">Average No-Shows per Student</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include '../../includes/footer.php'; ?>

<script>
    // Engagement levels chart
    const engagementCtx = document.getElementById('engagementChart').getContext('2d');
    new Chart(engagementCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($engagement_levels, 'engagement_level')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($engagement_levels, 'student_count')) ?>,
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Monthly trends chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($monthly_trends, 'month')) ?>,
            datasets: [{
                label: 'Unique Students',
                data: <?= json_encode(array_column($monthly_trends, 'unique_students')) ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Total Appointments',
                data: <?= json_encode(array_column($monthly_trends, 'total_appointments')) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // Hourly booking patterns
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($h) { return $h . ':00'; }, range(0, 23))) ?>,
            datasets: [{
                label: 'Student Bookings',
                data: <?= json_encode($hourly_bookings) ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.8)',
                borderColor: '#17a2b8',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
