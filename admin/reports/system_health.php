<?php
session_start();
require_once '../../config/db.php';
require_once './../includes/auth_check.php';

requireAdminAuth();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'system_health_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Date', 'Available Slots', 'Booked Slots', 'Slot Utilization Rate',
        'Failed Appointments', 'Active Users', 'New Users'
    ]);

    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(asl.start_at, '%Y-%m-%d') as slot_date,
            SUM(CASE WHEN asl.status = 'OPEN' THEN 1 ELSE 0 END) as available_slots,
            SUM(CASE WHEN asl.status = 'BLOCKED' THEN 1 ELSE 0 END) as booked_slots,
            ROUND(SUM(CASE WHEN asl.status = 'BLOCKED' THEN 1 ELSE 0 END) / NULLIF(COUNT(asl.id), 0) * 100, 1) as utilization_rate,
            SUM(CASE WHEN a.status IN ('CANCELLED', 'DECLINED', 'NO_SHOW') THEN 1 ELSE 0 END) as failed_appointments,
            COUNT(DISTINCT a.student_id) as active_users,
            SUM(CASE WHEN u.created_at >= asl.start_at THEN 1 ELSE 0 END) as new_users
        FROM availability_slots asl
        LEFT JOIN appointments a ON asl.counselor_id = a.counselor_id AND asl.start_at = a.start_time
        LEFT JOIN users u ON u.id = a.student_id
        GROUP BY slot_date
        ORDER BY slot_date DESC
        LIMIT 30
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['slot_date'],
            $row['available_slots'],
            $row['booked_slots'],
            $row['utilization_rate'] . '%',
            $row['failed_appointments'] ?? 0,
            $row['active_users'] ?? 0,
            $row['new_users'] ?? 0
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
        $slot_date_condition = "AND asl.start_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $range_label = "Last 7 Days";
        break;
    case '30':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $slot_date_condition = "AND asl.start_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $range_label = "Last 30 Days";
        break;
    case '90':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $slot_date_condition = "AND asl.start_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $range_label = "Last 90 Days";
        break;
    case '365':
        $date_condition = "AND a.start_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $slot_date_condition = "AND asl.start_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
        $range_label = "Last 12 Months";
        break;
    default:
        $date_condition = "";
        $slot_date_condition = "";
        $range_label = "All Time";
}

// Key system metrics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_slots,
        SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as available_slots,
        SUM(CASE WHEN status = 'BLOCKED' THEN 1 ELSE 0 END) as booked_slots
    FROM availability_slots asl
    WHERE 1=1 $slot_date_condition
");
$slot_metrics = $stmt->fetch();
$slot_utilization_rate = $slot_metrics['total_slots'] > 0 ?
    round($slot_metrics['booked_slots'] / $slot_metrics['total_slots'] * 100, 1) : 0;

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status IN ('CANCELLED', 'DECLINED', 'NO_SHOW') THEN 1 ELSE 0 END) as failed_appointments
    FROM appointments a
    WHERE 1=1 $date_condition
");
$appointment_metrics = $stmt->fetch();
$failure_rate = $appointment_metrics['total_appointments'] > 0 ?
    round($appointment_metrics['failed_appointments'] / $appointment_metrics['total_appointments'] * 100, 1) : 0;

$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT student_id) as active_users,
        SUM(CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE 1=1 $date_condition
");
$user_metrics = $stmt->fetch();

// Daily slot utilization trends
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(asl.start_at, '%Y-%m-%d') as slot_date,
        SUM(CASE WHEN asl.status = 'OPEN' THEN 1 ELSE 0 END) as available_slots,
        SUM(CASE WHEN asl.status = 'BLOCKED' THEN 1 ELSE 0 END) as booked_slots
    FROM availability_slots asl
    WHERE 1=1 $slot_date_condition
    GROUP BY slot_date
    ORDER BY slot_date ASC
");
$slot_trends = $stmt->fetchAll();

// Appointment status distribution
$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM appointments a
    WHERE 1=1 $date_condition
    GROUP BY status
");
$status_distribution = $stmt->fetchAll();

$current_report = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health - Admin Reports</title>
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
                        <i class="fas fa-heartbeat me-2"></i>System Health
                    </h1>
                    <p class="text-muted mb-0">Platform performance and reliability metrics</p>
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
                    <i class="fas fa-calendar-check text-primary fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $slot_utilization_rate ?>%</h3>
                    <p class="text-muted mb-0">Slot Utilization Rate</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle text-danger fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $failure_rate ?>%</h3>
                    <p class="text-muted mb-0">Appointment Failure Rate</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users text-success fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($user_metrics['active_users'] ?? 0) ?></h3>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Slot Utilization Trends -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-line text-primary me-2"></i>Slot Utilization Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="slotTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-chart-pie text-info me-2"></i>Appointment Status Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    // Slot trends chart
    const slotTrendsCtx = document.getElementById('slotTrendsChart').getContext('2d');
    new Chart(slotTrendsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($slot_trends, 'slot_date')) ?>,
            datasets: [
                {
                    label: 'Available Slots',
                    data: <?= json_encode(array_column($slot_trends, 'available_slots')) ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Booked Slots',
                    data: <?= json_encode(array_column($slot_trends, 'booked_slots')) ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Status distribution chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($status_distribution, 'status')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($status_distribution, 'count')) ?>,
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>