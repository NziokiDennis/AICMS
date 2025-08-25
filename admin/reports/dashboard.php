<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../../config/db.php';
require_once './../includes/auth_check.php';

requireAdminAuth();

// Fetch basic stats for overview
$stmt = $pdo->query("SELECT COUNT(*) as appointment_count FROM appointments WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$appointment_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users WHERE role IN ('STUDENT', 'COUNSELOR')");
$user_count = $stmt->fetchColumn();

$report_count = 5; // Total number of reports
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Dashboard - Counseling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-danger">
                    <i class="fas fa-chart-bar me-2"></i>Reports Dashboard
                </h1>
                <p class="text-muted mb-0">Access and analyze system reports</p>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-bar text-danger fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $report_count ?></h4>
                        <p class="text-muted mb-0">Available Reports</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-users text-primary fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $user_count ?></h4>
                        <p class="text-muted mb-0">Active Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt text-success fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $appointment_count ?></h4>
                        <p class="text-muted mb-0">Appointments (Last 30 Days)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Cards -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-analytics me-2 text-primary"></i>Usage Analytics
                        </h5>
                        <p class="card-text text-muted">View system usage metrics, including appointment trends and completion rates over time.</p>
                        <a href="usage_analytics.php" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-user-md me-2 text-success"></i>Counselor Performance
                        </h5>
                        <p class="card-text text-muted">Analyze counselor performance metrics (Under Development).</p>
                        <a href="counselor_performance.php" class="btn btn-secondary disabled">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-graduation-cap me-2 text-info"></i>Student Engagement
                        </h5>
                        <p class="card-text text-muted">Track student engagement patterns (Under Development).</p>
                        <a href="student_engagement.php" class="btn btn-secondary disabled">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-heartbeat me-2 text-danger"></i>System Health
                        </h5>
                        <p class="card-text text-muted">Monitor system performance and errors (Under Development).</p>
                        <a href="system_health.php" class="btn btn-secondary disabled">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-clipboard-check me-2 text-warning"></i>Compliance
                        </h5>
                        <p class="card-text text-muted">Review compliance metrics and audit logs (Under Development).</p>
                        <a href="compliance_report.php" class="btn btn-secondary disabled">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>