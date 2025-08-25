<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth_check.php';

requireAdminAuth();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'usage_analytics_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Export monthly data
    fputcsv($output, ['Month', 'Total Appointments', 'Completed', 'Cancelled', 'Completion Rate']);
    
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(start_time, '%Y-%m') as month,
               COUNT(*) as total,
               SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
               SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
               ROUND(SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as completion_rate
        FROM appointments 
        WHERE start_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month DESC
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [$row['month'], $row['total'], $row['completed'], $row['cancelled'], $row['completion_rate'] . '%']);
    }
    
    fclose($output);
    exit;
}

// Get date range filter
$date_range = $_GET['range'] ?? '30';

switch ($date_range) {
    case '7':
        $date_condition = "WHERE a.start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $range_label = "Last 7 Days";
        break;
    case '30':
        $date_condition = "WHERE a.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $range_label = "Last 30 Days";
        break;
    case '90':
        $date_condition = "WHERE a.start_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $range_label = "Last 90 Days";
        break;
    case '365':
        $date_condition = "WHERE a.start_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $range_label = "Last 12 Months";
        break;
    default:
        $date_condition = "";
        $range_label = "All Time";
}

// Overall Statistics
$stats = [];

// Total appointments in range
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments a $date_condition");
$stats['total_appointments'] = $stmt->fetchColumn();

// Appointments by status
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM appointments a $date_condition 
    GROUP BY status
");
$status_counts = [];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

$stats['completed'] = $status_counts['COMPLETED'] ?? 0;
$stats['pending'] = $status_counts['PENDING'] ?? 0;
$stats['approved'] = $status_counts['APPROVED'] ?? 0;
$stats['cancelled'] = $status_counts['CANCELLED'] ?? 0;
$stats['declined'] = $status_counts['DECLINED'] ?? 0;

// Completion rate
$stats['completion_rate'] = $stats['total_appointments'] > 0 ? 
    round(($stats['completed'] / $stats['total_appointments']) * 100, 1) : 0;

// Peak booking times (hour of day)
$stmt = $pdo->query("
    SELECT HOUR(start_time) as hour, COUNT(*) as count
    FROM appointments a $date_condition
    GROUP BY hour
    ORDER BY hour
");
$hourly_data = array_fill(0, 24, 0);
while ($row = $stmt->fetch()) {
    $hourly_data[$row['hour']] = $row['count'];
}

// Peak booking days (day of week)
$stmt = $pdo->query("
    SELECT DAYNAME(start_time) as day_name, DAYOFWEEK(start_time) as day_num, COUNT(*) as count
    FROM appointments a $date_condition
    GROUP BY day_name, day_num
    ORDER BY day_num
");
$daily_data = [];
while ($row = $stmt->fetch()) {
    $daily_data[$row['day_name']] = $row['count'];
}

// Most popular specialties
$stmt = $pdo->query("
    SELECT cp.specialty, COUNT(*) as count
    FROM appointments a
    JOIN counselor_profiles cp ON a.counselor_id = cp.user_id
    $date_condition
    GROUP BY cp.specialty
    ORDER BY count DESC
    LIMIT 5
");
$specialty_data = $stmt->fetchAll();

// Meeting mode preferences
$stmt = $pdo->query("
    SELECT cp.meeting_mode, COUNT(*) as count
    FROM appointments a
    JOIN counselor_profiles cp ON a.counselor_id = cp.user_id
    $date_condition
    GROUP BY cp.meeting_mode
    ORDER BY count DESC
");
$mode_data = $stmt->fetchAll();

// Monthly trends for chart
$stmt = $pdo->query("
    SELECT DATE_FORMAT(start_time, '%Y-%m') as month,
           COUNT(*) as total,
           SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed
    FROM appointments 
    WHERE start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month ASC
");
$monthly_trends = $stmt->fetchAll();

// Top counselors by appointment count
$stmt = $pdo->query("
    SELECT u.name, COUNT(*) as appointment_count,
           AVG(f.rating) as avg_rating,
           COUNT(f.id) as feedback_count
    FROM appointments a
    JOIN users u ON a.counselor_id = u.id
    LEFT JOIN sessions s ON a.id = s.appointment_id
    LEFT JOIN feedback f ON s.id = f.session_id
    $date_condition
    GROUP BY u.id, u.name
    ORDER BY appointment_count DESC
    LIMIT 10
");
$top_counselors = $stmt->fetchAll();

// Student engagement metrics
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT student_id) as unique_students,
        AVG(student_appointments) as avg_appointments_per_student,
        MAX(student_appointments) as max_appointments_per_student
    FROM (
        SELECT student_id, COUNT(*) as student_appointments
        FROM appointments a $date_condition
        GROUP BY student_id
    ) student_stats
");
$student_metrics = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usage Analytics - Admin Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="../../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 fw-bold text-danger">
                            <i class="fas fa-chart-line me-2"></i>Usage Analytics
                        </h1>
                        <p class="text-muted mb-0">System usage patterns and appointment trends</p>
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
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-calendar-check text-primary fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($stats['total_appointments']) ?></h3>
                        <p class="text-muted mb-0">Total Appointments</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-check-circle text-success fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($stats['completed']) ?></h3>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-percentage text-info fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= $stats['completion_rate'] ?>%</h3>
                        <p class="text-muted mb-0">Completion Rate</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users text-warning fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($student_metrics['unique_students']) ?></h3>
                        <p class="text-muted mb-0">Unique Students</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-clock text-secondary fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($stats['pending']) ?></h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-ban text-danger fs-2 mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($stats['cancelled'] + $stats['declined']) ?></h3>
                        <p class="text-muted mb-0">Cancelled/Declined</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Monthly Trends Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>Monthly Appointment Trends (Last 6 Months)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyTrendsChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Appointment Status Distribution -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-pie-chart text-success me-2"></i>Status Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Peak Hours -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-clock text-warning me-2"></i>Peak Booking Hours
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="hourlyChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Peak Days -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-calendar-week text-info me-2"></i>Peak Booking Days
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Popular Specialties -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-tags text-primary me-2"></i>Popular Specialties
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($specialty_data)): ?>
                            <?php foreach ($specialty_data as $index => $specialty): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                        <?= htmlspecialchars($specialty['specialty'] ?: 'General Counseling') ?>
                                    </div>
                                    <span class="fw-bold"><?= $specialty['count'] ?></span>
                                </div>
                                <div class="progress mb-3" style="height: 5px;">
                                    <div class="progress-bar" style="width: <?= ($specialty['count'] / max(array_column($specialty_data, 'count'))) * 100 ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No specialty data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Meeting Mode Preferences -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-video text-success me-2"></i>Meeting Preferences
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($mode_data as $mode): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <i class="fas fa-<?= $mode['meeting_mode'] === 'VIDEO' ? 'video' : ($mode['meeting_mode'] === 'PHONE' ? 'phone' : 'map-marker-alt') ?> me-2"></i>
                                    <?= str_replace('_', ' ', $mode['meeting_mode']) ?>
                                </div>
                                <div>
                                    <span class="fw-bold me-2"><?= $mode['count'] ?></span>
                                    <span class="text-muted small">
                                        (<?= round(($mode['count'] / $stats['total_appointments']) * 100, 1) ?>%)
                                    </span>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 5px;">
                                <div class="progress-bar bg-<?= $mode['meeting_mode'] === 'VIDEO' ? 'success' : ($mode['meeting_mode'] === 'PHONE' ? 'info' : 'warning') ?>" 
                                     style="width: <?= ($mode['count'] / $stats['total_appointments']) * 100 ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Counselors -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-user-md text-info me-2"></i>Most Active Counselors
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($top_counselors, 0, 5) as $index => $counselor): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($counselor['name']) ?></div>
                                    <?php if ($counselor['avg_rating']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-star text-warning"></i>
                                            <?= number_format($counselor['avg_rating'], 1) ?> 
                                            (<?= $counselor['feedback_count'] ?> reviews)
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary"><?= $counselor['appointment_count'] ?></div>
                                    <small class="text-muted">appointments</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-table text-secondary me-2"></i>Student Engagement Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h4 class="fw-bold text-primary"><?= number_format($student_metrics['unique_students']) ?></h4>
                                <p class="text-muted">Unique Students Served</p>
                            </div>
                            <div class="col-md-4">
                                <h4 class="fw-bold text-success"><?= number_format($student_metrics['avg_appointments_per_student'], 1) ?></h4>
                                <p class="text-muted">Average Appointments per Student</p>
                            </div>
                            <div class="col-md-4">
                                <h4 class="fw-bold text-info"><?= number_format($student_metrics['max_appointments_per_student']) ?></h4>
                                <p class="text-muted">Most Appointments by Single Student</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Monthly trends chart
        const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthly_trends, 'month')) ?>,
                datasets: [{
                    label: 'Total Appointments',
                    data: <?= json_encode(array_column($monthly_trends, 'total')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Completed',
                    data: <?= json_encode(array_column($monthly_trends, 'completed')) ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Status distribution chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Approved', 'Cancelled', 'Declined'],
                datasets: [{
                    data: [
                        <?= $stats['completed'] ?>,
                        <?= $stats['pending'] ?>,
                        <?= $stats['approved'] ?>,
                        <?= $stats['cancelled'] ?>,
                        <?= $stats['declined'] ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Hourly distribution chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function($h) { return $h . ':00'; }, range(0, 23))) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($hourly_data) ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Daily distribution chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: [{
                    label: 'Appointments',
                    data: [
                        <?= $daily_data['Sunday'] ?? 0 ?>,
                        <?= $daily_data['Monday'] ?? 0 ?>,
                        <?= $daily_data['Tuesday'] ?? 0 ?>,
                        <?= $daily_data['Wednesday'] ?? 0 ?>,
                        <?= $daily_data['Thursday'] ?? 0 ?>,
                        <?= $daily_data['Friday'] ?? 0 ?>,
                        <?= $daily_data['Saturday'] ?? 0 ?>
                    ],
                    backgroundColor: 'rgba(23, 162, 184, 0.8)',
                    borderColor: '#17a2b8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>