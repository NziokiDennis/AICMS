<?php
// includes/header.php (UPDATED)
// Purpose: centralized session start + navbar (role-aware)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current page for active state
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// Base path detection (simplified)
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$base_path = '';
if (strpos($request_uri, '/admin/') !== false) {
    $base_path = '../../';
} elseif (
    strpos($request_uri, '/student/') !== false ||
    strpos($request_uri, '/counselor/') !== false ||
    strpos($request_uri, '/auth/') !== false
) {
    $base_path = '../';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="<?= $base_path ?>index.php">
      <i class="fas fa-brain me-2" aria-hidden="true"></i>Happy Hearts
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown"
               aria-expanded="false">
              <i class="fas fa-user-circle me-1" aria-hidden="true"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'STUDENT'): ?>
                <li><a class="dropdown-item" href="<?= $base_path ?>student/dashboard.php">Dashboard</a></li>
              <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'COUNSELOR'): ?>
                <li><a class="dropdown-item" href="<?= $base_path ?>counselor/dashboard.php">Dashboard</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= $base_path ?>auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
            </ul>
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
