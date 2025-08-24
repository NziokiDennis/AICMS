<?php
// Determine current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">
            <i class="fas fa-brain me-2"></i>Happy Hearts
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index' ? 'active fw-bold' : '' ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'aboutus' ? 'active fw-bold' : '' ?>" href="aboutus.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'contact' ? 'active fw-bold' : '' ?>" href="contact.php">Contact</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['user_role'] === 'STUDENT'): ?>
                                <li><a class="dropdown-item" href="student/dashboard.php">Dashboard</a></li>
                            <?php elseif ($_SESSION['user_role'] === 'COUNSELOR'): ?>
                                <li><a class="dropdown-item" href="counselor/dashboard.php">Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>