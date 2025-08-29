<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../../config/db.php';
require_once '../includes/auth_check.php';

requireAdminAuth();

// Fetch basic stats for overview
$stmt = $pdo->query("SELECT COUNT(*) as appointment_count FROM appointments WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$appointment_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users WHERE role IN ('STUDENT', 'COUNSELOR')");
$user_count = $stmt->fetchColumn();

$report_count = 5; // Total number of reports
$current_page = 'reports';
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
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../manage_users.php">
                            <i class="fas fa-users me-1"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../manage_appointments.php">
                            <i class="fas fa-calendar-alt me-1"></i>Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'reports' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="btn btn-outline-light d-flex align-items-center" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Navigation Styles -->
    <style>
    .navbar-brand {
        font-size: 1.4rem;
        transition: opacity 0.2s ease;
    }
    .navbar-brand:hover {
        opacity: 0.9;
    }
    .nav-link {
        font-size: 1rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
    }
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .navbar {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        z-index: 1000;
    }
    .btn-outline-light {
        border-radius: 6px;
        padding: 6px 15px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .btn-outline-light:hover {
        background-color: #ffffff;
        color: #dc3545;
    }
    .navbar-toggler {
        border: none;
    }
    .navbar-toggler:focus {
        box-shadow: none;
    }
    </style>
    
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
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-analytics me-2 text-primary"></i>Usage Analytics
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            View system usage metrics, including appointment trends and completion rates over time.
                        </p>
                        <a href="usage_analytics.php" class="btn btn-primary mt-auto">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-user-md me-2 text-success"></i>Counselor Performance
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            Analyze counselor performance metrics.
                        </p>
                        <a href="counselor_performance.php" class="btn btn-success mt-auto">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-graduation-cap me-2 text-info"></i>Student Engagement
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            Track student engagement patterns.
                        </p>
                        <a href="student_engagement.php" class="btn btn-info mt-auto">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-heartbeat me-2 text-danger"></i>System Health
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            Monitor system performance and errors.
                        </p>
                        <a href="system_health.php" class="btn btn-danger mt-auto">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <i class="fas fa-clipboard-check me-2 text-warning"></i>Compliance
                        </h5>
                        <p class="card-text text-muted flex-grow-1">
                            Review compliance metrics and audit logs.
                        </p>
                        <a href="compliance_report.php" class="btn btn-warning mt-auto">
                            <i class="fas fa-eye me-2"></i>View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white border-top py-3 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">© 2025 Happy Hearts Counseling System</small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Admin Panel v1.0</small>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(dropdown => {
            new bootstrap.Dropdown(dropdown);
        });
    });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>