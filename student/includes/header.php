<?php
// Determine current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="../index.php">
            <i class="fas fa-brain me-2"></i>Happy Hearts
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                    <a class="nav-link <?= $current_page === 'find_counselor' ? 'active fw-bold' : '' ?>" href="find_counselor.php">
                        <i class="fas fa-search me-1"></i>Find Counselor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'my_notes' ? 'active fw-bold' : '' ?>" href="my_notes.php">
                        <i class="fas fa-sticky-note me-1"></i>My Notes
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="find_counselor.php">
                                <i class="fas fa-search me-2"></i>Find Counselor
                            </a></li>
                            <li><a class="dropdown-item" href="my_notes.php">
                                <i class="fas fa-sticky-note me-2"></i>My Notes
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../index.php">
                                <i class="fas fa-home me-2"></i>Main Site
                            </a></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>