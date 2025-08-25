<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAuth($allowed_roles = []) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || !isset($_SESSION['user_role']) || empty($_SESSION['user_role'])) {
        $redirect_path = '/Counseling-system/auth/login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/student/') !== false || 
            strpos($_SERVER['REQUEST_URI'], '/counselor/') !== false) {
            $redirect_path = '../auth/login.php';
        }
        header('Location: ' . $redirect_path);
        exit;
    }
    if ($allowed_roles && !in_array($_SESSION['user_role'], $allowed_roles)) {
        header('Location: /Counseling-system/index.php');
        exit;
    }
}

function requireAdminAuth() {
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        $redirect_path = '/Counseling-system/admin/auth/login.php';
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false &&
            strpos($_SERVER['REQUEST_URI'], '/admin/auth/') === false) {
            $redirect_path = 'auth/login.php';
        }
        header('Location: ' . $redirect_path);
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function getCurrentUser(PDO $pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout() {
    session_start();
    $_SESSION = [];
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    header('Location: /Counseling-system/index.php');
    exit;
}

function adminLogout() {
    session_start();
    $_SESSION = [];
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    header('Location: /Counseling-system/admin/auth/login.php');
    exit;
}
?>