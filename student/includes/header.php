<?php
// Determine current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <!-- Brand: keep users inside /student -->
        <a class="navbar-brand fw-bold text-primary" href="/student/dashboard.php">
            <i class="fas fa-brain me-2"></i>Happy Hearts
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- LEFT: Primary nav (all inside /student) -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard' ? 'active fw-bold' : '' ?>" href="/student/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'find_counselor' ? 'active fw-bold' : '' ?>" href="/student/find_counselor.php">
                        <i class="fas fa-search me-1"></i>Find Counselor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'book_appointment' ? 'active fw-bold' : '' ?>" href="/student/book_appointment.php">
                        <i class="fas fa-calendar-plus me-1"></i>Book Appointment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'my_notes' ? 'active fw-bold' : '' ?>" href="/student/my_notes.php">
                        <i class="fas fa-sticky-note me-1"></i>My Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'feedback' ? 'active fw-bold' : '' ?>" href="/student/feedback.php">
                        <i class="fas fa-star me-1"></i>Feedback
                    </a>
                </li>
            </ul>

            <!-- RIGHT: User menu -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/student/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="/student/find_counselor.php">
                                <i class="fas fa-search me-2"></i>Find Counselor
                            </a></li>
                            <li><a class="dropdown-item" href="/student/book_appointment.php">
                                <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                            </a></li>
                            <li><a class="dropdown-item" href="/student/my_notes.php">
                                <i class="fas fa-sticky-note me-2"></i>My Notes
                            </a></li>
                            <li><a class="dropdown-item" href="/student/feedback.php">
                                <i class="fas fa-star me-2"></i>Feedback
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <!-- Only Logout goes outside /student -->
                            <li><a class="dropdown-item" href="/index.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <!-- keep auth within app; if your login lives elsewhere, adjust path -->
                        <a class="nav-link" href="/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
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
.dropdown-menu {
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.dropdown-item {
    padding: 10px 20px;
    border-radius: 6px;
    margin: 2px 8px;
}
.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
}
.navbar { padding: 1rem 0; }
</style>
