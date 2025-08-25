<?php
// Authentication helper functions

function requireAuth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        $redirect_path = '/counseling-system/auth/login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/student/') !== false || 
            strpos($_SERVER['REQUEST_URI'], '/counselor/') !== false) {
            $redirect_path = '../auth/login.php';
        }
        header('Location: ' . $redirect_path);
        exit;
    }

    if ($allowed_roles && !in_array($_SESSION['user_role'], $allowed_roles)) {
        header('Location: /counseling-system/index.php');
        exit;
    }
}

function requireAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        $redirect_path = '/counseling-system/admin/auth/login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false &&
            strpos($_SERVER['REQUEST_URI'], '/admin/auth/') === false) {
            $redirect_path = 'auth/login.php';
        }
        header('Location: ' . $redirect_path);
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function getCurrentUser(PDO $pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout() {
    unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);
    header('Location: /counseling-system/index.php');
    exit;
}

function adminLogout() {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    header('Location: /counseling-system/admin/auth/login.php');
    exit;
}
?>
