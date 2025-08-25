<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'COUNSELOR') {
    header('Location: /Counseling-system/auth/login.php');
    exit;
}
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/Counseling-system/counselor/dashboard.php">
            <i class="fas fa-brain me-2"></i>Happy Hearts
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'add_note' ? 'active fw-bold' : '' ?>" href="add_note.php">
                        <i class="fas fa-sticky-note me-1"></i>Session Notes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'view_feedback' ? 'active fw-bold' : '' ?>" href="view_feedback.php">
                        <i class="fas fa-star me-1"></i>Feedback
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-light d-flex align-items-center" href="/Counseling-system/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/Counseling-system/auth/login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                    </li>
                <?php endif; ?>
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
}
.btn-outline-light {
    border-radius: 6px;
    padding: 6px 15px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}
.btn-outline-light:hover {
    background-color: #ffffff;
    color: #0d6efd;
}
.navbar-toggler {
    border: none;
}
.navbar-toggler:focus {
    box-shadow: none;
}
</style>