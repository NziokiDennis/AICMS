<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
requireAdminAuth();
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/Counseling-system/admin/dashboard.php">
            <i class="fas fa-shield-alt me-2"></i>Admin Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'manage_users' ? 'active fw-bold' : '' ?>" href="manage_users.php">
                        <i class="fas fa-users me-1"></i>Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'manage_appointments' ? 'active fw-bold' : '' ?>" href="manage_appointments.php">
                        <i class="fas fa-calendar-alt me-1"></i>Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard' && strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active fw-bold' : '' ?>" href="reports/dashboard.php">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="btn btn-outline-light d-flex align-items-center" href="/Counseling-system/admin/auth/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        new bootstrap.Dropdown(dropdown);
    });
});
</script>