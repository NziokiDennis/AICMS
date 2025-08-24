<?php
// Authentication helper functions

function requireAuth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /counseling-system/auth/login.php');
        exit;
    }
    
    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles)) {
        header('Location: /counseling-system/index.php');
        exit;
    }
}

function requireAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /counseling-system/admin/auth/login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout() {
    session_destroy();
    header('Location: /counseling-system/index.php');
    exit;
}

function adminLogout() {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    header('Location: /counseling-system/admin/auth/login.php');
    exit;
}
?>