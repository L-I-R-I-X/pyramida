<?php
require_once __DIR__ . '/config.php';

function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $loginUrl = BASE_URL . 'admin/login.php';
        if (!headers_sent()) {
            header('Location: ' . $loginUrl);
            exit;
        } else {
            echo "<script>window.location.href='$loginUrl';</script>";
            exit;
        }
    }
}

function getAdminLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['admin_login'] ?? 'admin';
}

function login($login, $password) {
    global $pdo;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login'] = $admin['login'];
        $_SESSION['admin_id'] = $admin['id'];
        return true;
    }
    
    return false;
}

// ✅ ИСПРАВЛЕННАЯ ФУНКЦИЯ ВЫХОДА
function logout() {
    // Убедимся, что сессия запущена
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 1. Очищаем все переменные сессии
    $_SESSION = [];
    
    // 2. Удаляем сессионную куку
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // 3. Уничтожаем сессию
    session_destroy();
}
?>