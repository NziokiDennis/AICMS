<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$base_path = '';
if (strpos($request_uri, '/admin/') !== false) {
    $base_path = '../../';
} elseif (strpos($request_uri, '/student/') !== false || strpos($request_uri, '/counselor/') !== false || strpos($request_uri, '/auth/') !== false) {
    $base_path = '../';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?= $base_path ?>index.php">
            <i class="fas fa-brain me-2" aria-hidden="true"></i>Happy Hearts
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index' ? 'active fw-bold' : '' ?>" href="<?= $base_path ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'aboutus' ? 'active fw-bold' : '' ?>" href="<?= $base_path ?>aboutus.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'contact' ? 'active fw-bold' : '' ?>" href="<?= $base_path ?>contact.php">Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger d-flex align-items-center" href="<?= $base_path ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_path ?>auth/login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
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