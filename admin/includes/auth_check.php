<?php
// includes/auth_check.php - Authentication functions

function requireAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        // Determine the correct path based on current location
        $login_path = str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'auth/login.php' : 'admin/auth/login.php';
        header("Location: $login_path");
        exit;
    }
}

function requireUserAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireCounselorAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'COUNSELOR') {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireStudentAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'STUDENT') {
        header('Location: /auth/login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getCurrentAdmin($pdo) {
    if (!isset($_SESSION['admin_id'])) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'ADMIN'");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}
?>