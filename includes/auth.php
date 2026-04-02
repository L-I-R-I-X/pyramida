<?php
// ============================================================================
// АВТОРИЗАЦИЯ И ПРОВЕРКА ДОСТУПА
// ============================================================================

/**
 * Инициализация сессии с использованием DatabaseSessionHandler
 * Вызывается один раз при первом обращении к функциям авторизации
 * @param object|null $pdo PDO подключение (опционально)
 * @return bool true если сессия успешно инициализирована
 */
function initSession($pdo = null) {
    // Если сессия уже запущена - ничего не делаем
    if (session_status() !== PHP_SESSION_NONE) {
        return true;
    }
    
    // Проверяем, что PDO подключен
    if ($pdo === null) {
        global $pdo;
    }
    
    if (!isset($pdo)) {
        // Подключаем config и db если они ещё не подключены
        $configFile = __DIR__ . '/config.php';
        $dbFile = __DIR__ . '/db.php';
        
        if (file_exists($configFile)) {
            require_once $configFile;
        } else {
            die('Ошибка: Файл config.php не найден.');
        }
        
        if (file_exists($dbFile)) {
            require_once $dbFile;
        } else {
            die('Ошибка: Файл db.php не найден. Запустите install.php для настройки базы данных.');
        }
        
        // После подключения db.php у нас должна быть функция initDatabaseSession
        if (function_exists('initDatabaseSession')) {
            return initDatabaseSession($pdo);
        }
        
        return false;
    }
    
    // Используем функцию из db.php для инициализации сессии
    if (function_exists('initDatabaseSession')) {
        return initDatabaseSession($pdo);
    }
    
    return false;
}

/**
 * Проверка авторизации пользователя
 * Перенаправляет на страницу входа если пользователь не авторизован
 */
function requireAuth() {
    // Инициализируем сессию если ещё не запущена
    if (session_status() === PHP_SESSION_NONE) {
        initSession();
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

/**
 * Получить логин администратора из сессии
 * @return string Логин администратора или 'admin' по умолчанию
 */
function getAdminLogin() {
    // Инициализируем сессию если ещё не запущена
    if (session_status() === PHP_SESSION_NONE) {
        initSession();
    }
    return $_SESSION['admin_login'] ?? 'admin';
}

/**
 * Функция входа в систему
 * @param string $login Логин
 * @param string $password Пароль
 * @return array ['success' => bool, 'blocked' => bool, ...]
 */
function login($login, $password) {
    global $pdo;
    
    // Убеждаемся, что сессия запущена
    if (session_status() === PHP_SESSION_NONE) {
        initSession($pdo);
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

/**
 * Функция выхода из системы
 */
function logout() {
    // Сессия должна быть уже запущена
    if (session_status() === PHP_SESSION_NONE) {
        initSession();
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
