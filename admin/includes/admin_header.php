<?php
// Admin Header - admin/includes/admin_header.php
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-shield-alt me-2"></i>Admin Portal
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                       href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>" 
                       href="manage_users.php">
                        <i class="fas fa-users me-1"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_appointments.php' ? 'active' : '' ?>" 
                       href="manage_appointments.php">
                        <i class="fas fa-calendar-alt me-1"></i>Appointments
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : '' ?>" 
                       href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="reports/usage_analytics.php">
                            <i class="fas fa-analytics me-2"></i>Usage Analytics</a></li>
                        <li><a class="dropdown-item" href="reports/counselor_performance.php">
                            <i class="fas fa-user-md me-2"></i>Counselor Performance</a></li>
                        <li><a class="dropdown-item" href="reports/student_engagement.php">
                            <i class="fas fa-graduation-cap me-2"></i>Student Engagement</a></li>
                        <li><a class="dropdown-item" href="reports/system_health.php">
                            <i class="fas fa-heartbeat me-2"></i>System Health</a></li>
                        <li><a class="dropdown-item" href="reports/compliance_report.php">
                            <i class="fas fa-clipboard-check me-2"></i>Compliance</a></li>
                    </ul>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i><?= htmlspecialchars($_SESSION['admin_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../index.php">
                            <i class="fas fa-home me-2"></i>View Main Site</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>