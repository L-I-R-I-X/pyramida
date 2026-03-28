<?php
// Подключаем config.php и db.php для работы с БД и сессиями
$configFile = __DIR__ . '/config.php';
$dbFile = __DIR__ . '/db.php';

if (!file_exists($configFile)) {
    die('Ошибка: Файл config.php не найден.');
}
require_once $configFile;

if (!file_exists($dbFile)) {
    die('Ошибка: Файл db.php не найден. Запустите install.php для настройки базы данных.');
}
require_once $dbFile;

function requireAuth() {
    // Сессия уже запущена в db.php, но проверяем на всякий случай
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Регенерация session ID для защиты от session fixation
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
    
    // Сессия уже должна быть запущена в db.php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Проверка количества неудачных попыток входа для этого IP
    $maxAttempts = 5;
    $lockoutTime = 300; // 5 минут
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attemptKey = 'login_attempts_' . $ipAddress;
    
    $attempts = $_SESSION[$attemptKey] ?? ['count' => 0, 'time' => time()];
    
    // Сброс счётчика если прошло достаточно времени
    if (time() - $attempts['time'] > $lockoutTime) {
        $attempts = ['count' => 0, 'time' => time()];
    }
    
    // Проверка блокировки
    if ($attempts['count'] >= $maxAttempts) {
        $waitTime = ceil($lockoutTime - (time() - $attempts['time']));
        return ['success' => false, 'blocked' => true, 'wait_time' => $waitTime];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Успешный вход - сбрасываем счётчик попыток
        unset($_SESSION[$attemptKey]);
        
        // Регенерация session ID после успешного логина для защиты от session fixation
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login'] = $admin['login'];
        $_SESSION['admin_id'] = $admin['id'];
        return ['success' => true, 'blocked' => false];
    } else {
        // Неудачная попытка - увеличиваем счётчик
        $attempts['count']++;
        $attempts['time'] = time();
        $_SESSION[$attemptKey] = $attempts;
        
        $remainingAttempts = $maxAttempts - $attempts['count'];
        return [
            'success' => false, 
            'blocked' => false, 
            'remaining_attempts' => $remainingAttempts,
            'attempts_left_before_block' => max(0, $remainingAttempts)
        ];
    }
}

function logout() {
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Полная очистка всех переменных сессии
    $_SESSION = [];

    // Удаляем cookie сессии
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

    // Уничтожаем сессию
    session_destroy();
    
    // Дополнительная очистка для суперглобальных переменных
    unset($_COOKIE[session_name()]);
}
?>