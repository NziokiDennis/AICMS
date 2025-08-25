<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php');
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold text-primary" href="/student/dashboard.php">
            <i class="fas fa-brain me-2"></i>Happy Hearts
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- LEFT NAV -->
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
                    <a class="nav-link <?= $current_page === 'book_appointment' ? 'active fw-bold' : '' ?>" href="book_appointment.php">
                        <i class="fas fa-calendar-plus me-1"></i>Book Appointment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'my_notes' ? 'active fw-bold' : '' ?>" href="my_notes.php">
                        <i class="fas fa-sticky-note me-1"></i>My Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'feedback' ? 'active fw-bold' : '' ?>" href="feedback.php">
                        <i class="fas fa-star me-1"></i>Feedback
                    </a>
                </li>
            </ul>

            <!-- RIGHT NAV -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger d-flex align-items-center" href="/Counseling-system/index.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-brand { font-size: 1.5rem; }
.nav-link.active {
    color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 6px;
}
.navbar { padding: 1rem 0; }
.btn-outline-danger {
    border-radius: 6px;
    padding: 6px 15px;
}
</style>
