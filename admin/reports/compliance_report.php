<?php
session_start();
require_once '../../config/db.php';
require_once './../includes/auth_check.php';

requireAdminAuth();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'compliance_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Counselor Name', 'Total Sessions', 'Sessions with Notes', 'Notes Compliance Rate',
        'Feedback Received', 'Feedback Rate', 'No-Shows', 'Timely Responses'
    ]);

    $stmt = $pdo->query("
        SELECT 
            u.name,
            COUNT(s.id) as total_sessions,
            SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) as sessions_with_notes,
            ROUND(SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(s.id), 0) * 100, 1) as notes_compliance_rate,
            SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) as feedback_received,
            ROUND(SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(s.id), 0) * 100, 1) as feedback_rate,
            SUM(CASE WHEN a.status = 'NO_SHOW' THEN 1 ELSE 0 END) as no_shows,
            SUM(CASE WHEN TIMESTAMPDIFF(HOUR, a.created_at, COALESCE(a.updated_at, a.created_at)) <= 24 THEN 1 ELSE 0 END) as timely_responses
        FROM users u
        LEFT JOIN appointments a ON u.id = a.counselor_id
        LEFT JOIN sessions s ON a.id = s.appointment_id
        LEFT JOIN notes n ON s.id = n.session_id
        LEFT JOIN feedback f ON s.id = f.session_id
        WHERE u.role = 'COUNSELOR'
        GROUP BY u.id, u.name
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            $row['total_sessions'],
            $row['sessions_with_notes'],
            $row['notes_compliance_rate'] . '%',
            $row['feedback_received'],
            $row['feedback_rate'] . '%',
            $row['no_shows'],
            $row['timely_responses']
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

// Key compliance metrics
$stmt = $pdo->query("
    SELECT 
        COUNT(s.id) as total_sessions,
        SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) as sessions_with_notes,
        SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) as feedback_received,
        SUM(CASE WHEN a.status = 'NO_SHOW' THEN 1 ELSE 0 END) as no_shows
    FROM sessions s
    LEFT JOIN appointments a ON s.appointment_id = a.id
    LEFT JOIN notes n ON s.id = n.session_id
    LEFT JOIN feedback f ON s.id = f.session_id
    WHERE s.status = 'COMPLETED' $date_condition
");
$compliance_metrics = $stmt->fetch();

$notes_compliance_rate = $compliance_metrics['total_sessions'] > 0 ?
    round($compliance_metrics['sessions_with_notes'] / $compliance_metrics['total_sessions'] * 100, 1) : 0;
$feedback_rate = $compliance_metrics['total_sessions'] > 0 ?
    round($compliance_metrics['feedback_received'] / $compliance_metrics['total_sessions'] * 100, 1) : 0;

// Counselor compliance details
$stmt = $pdo->query("
    SELECT 
        u.name,
        COUNT(s.id) as total_sessions,
        SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) as sessions_with_notes,
        ROUND(SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(s.id), 0) * 100, 1) as notes_compliance_rate,
        SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) as feedback_received,
        ROUND(SUM(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(s.id), 0) * 100, 1) as feedback_rate,
        SUM(CASE WHEN a.status = 'NO_SHOW' THEN 1 ELSE 0 END) as no_shows,
        SUM(CASE WHEN TIMESTAMPDIFF(HOUR, a.created_at, COALESCE(a.updated_at, a.created_at)) <= 24 THEN 1 ELSE 0 END) as timely_responses
    FROM users u
    LEFT JOIN appointments a ON u.id = a.counselor_id $date_condition
    LEFT JOIN sessions s ON a.id = s.appointment_id
    LEFT JOIN notes n ON s.id = n.session_id
    LEFT JOIN feedback f ON s.id = f.session_id
    WHERE u.role = 'COUNSELOR'
    GROUP BY u.id, u.name
    ORDER BY notes_compliance_rate DESC
");
$counselor_compliance = $stmt->fetchAll();

// Profile completeness
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_counselors,
        SUM(CASE WHEN cp.specialty IS NOT NULL AND cp.meeting_mode IS NOT NULL AND cp.bio IS NOT NULL THEN 1 ELSE 0 END) as complete_profiles
    FROM users u
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
    WHERE u.role = 'COUNSELOR'
");
$profile_data = $stmt->fetch();
$profile_completeness_rate = $profile_data['total_counselors'] > 0 ?
    round($profile_data['complete_profiles'] / $profile_data['total_counselors'] * 100, 1) : 0;

$current_report = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Report - Admin Reports</title>
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
                        <i class="fas fa-clipboard-check me-2"></i>Compliance Report
                    </h1>
                    <p class="text-muted mb-0">Counselor adherence to protocols and system compliance metrics</p>
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
                    <i class="fas fa-clipboard-list text-primary fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $notes_compliance_rate ?>%</h3>
                    <p class="text-muted mb-0">Notes Compliance Rate</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-star text-warning fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $feedback_rate ?>%</h3>
                    <p class="text-muted mb-0">Feedback Submission Rate</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-user-check text-success fs-2 mb-2"></i>
                    <h3 class="fw-bold"><?= $profile_completeness_rate ?>%</h3>
                    <p class="text-muted mb-0">Profile Completeness</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Counselor Compliance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-table text-secondary me-2"></i>Counselor Compliance Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Counselor</th>
                                    <th>Sessions</th>
                                    <th>Notes Completed</th>
                                    <th>Notes Compliance</th>
                                    <th>Feedback Received</th>
                                    <th>Feedback Rate</th>
                                    <th>No-Shows</th>
                                    <th>Timely Responses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($counselor_compliance as $counselor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($counselor['name']) ?></td>
                                    <td><?= $counselor['total_sessions'] ?></td>
                                    <td><?= $counselor['sessions_with_notes'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?= $counselor['notes_compliance_rate'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $counselor['notes_compliance_rate'] ?>%</small>
                                    </td>
                                    <td><?= $counselor['feedback_received'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $counselor['feedback_rate'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $counselor['feedback_rate'] ?>%</small>
                                    </td>
                                    <td><span class="text-danger"><?= $counselor['no_shows'] ?></span></td>
                                    <td><?= $counselor['timely_responses'] ?></td>
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