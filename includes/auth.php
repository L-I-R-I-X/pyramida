<?php
/**
 * Система аутентификации с хранением сессий в БД
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Название cookie для сессии
define('SESSION_COOKIE_NAME', 'admin_session');
// Время жизни сессии (30 дней)
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);

/**
 * Создание таблицы пользователей и сессий, если они не существуют
 */
function initAuthTables() {
    global $pdo;
    
    try {
        // Таблица пользователей
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active TINYINT(1) DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Проверяем, создана ли таблица пользователей
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        if ($stmt->rowCount() == 0) {
            error_log('initAuthTables: Таблица admin_users НЕ была создана');
            return false;
        }
        
        // Таблица сессий
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(64) UNIQUE NOT NULL,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Проверяем, создана ли таблица сессий
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
        if ($stmt->rowCount() == 0) {
            error_log('initAuthTables: Таблица admin_sessions НЕ была создана');
            return false;
        }
        
        // Создаём индекс для быстрой проверки сессий
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_session_token ON admin_sessions(session_token)");
        } catch (PDOException $e) {
            // Индекс может уже существовать
            error_log('initAuthTables warning: idx_session_token - ' . $e->getMessage());
        }
        
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_expires_at ON admin_sessions(expires_at)");
        } catch (PDOException $e) {
            // Индекс может уже существовать
            error_log('initAuthTables warning: idx_expires_at - ' . $e->getMessage());
        }
        
    } catch (PDOException $e) {
        error_log('initAuthTables error: ' . $e->getMessage());
        error_log('initAuthTables error code: ' . $e->getCode());
        return false;
    }
    
    return true;
}

/**
 * Создание пользователя по умолчанию (если не существует)
 */
function createDefaultUser($username = 'admin', $password = 'admin123') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (:username, :password, :email)");
            $stmt->execute([
                'username' => $username,
                'password' => $passwordHash,
                'email' => 'admin@localhost'
            ]);
            return true;
        }
    } catch (PDOException $e) {
        error_log('createDefaultUser error: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * Генерация безопасного токена сессии
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Аутентификация пользователя
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function authenticate($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, is_active FROM admin_users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Учётная запись заблокирована'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Неверное имя пользователя или пароль'];
        }
        
        // Успешная аутентификация - создаём сессию
        return createSession($user['id']);
        
    } catch (PDOException $e) {
        error_log('authenticate error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}

/**
 * Создание сессии в БД
 * @param int $userId
 * @return array ['success' => bool, 'message' => string, 'token' => string|null]
 */
function createSession($userId) {
    global $pdo;
    
    try {
        $sessionToken = generateSessionToken();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'session_token' => $sessionToken,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);
        
        // Устанавливаем cookie с токеном сессии
        setcookie(
            SESSION_COOKIE_NAME,
            $sessionToken,
            [
                'expires' => time() + SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        return ['success' => true, 'message' => 'Вход выполнен успешно', 'token' => $sessionToken];
        
    } catch (PDOException $e) {
        error_log('createSession error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка создания сессии'];
    }
}

/**
 * Проверка текущей сессии
 * @return array|null Возвращает данные пользователя или null
 */
function checkAuth() {
    global $pdo;
    
    if (!isset($_COOKIE[SESSION_COOKIE_NAME])) {
        return null;
    }
    
    $sessionToken = $_COOKIE[SESSION_COOKIE_NAME];
    
    try {
        // Проверяем сессию в БД
        $stmt = $pdo->prepare("
            SELECT s.id, s.user_id, s.expires_at, u.username, u.email, u.is_active
            FROM admin_sessions s
            JOIN admin_users u ON s.user_id = u.id
            WHERE s.session_token = :session_token
            AND s.expires_at > NOW()
            AND u.is_active = 1
        ");
        
        $stmt->execute(['session_token' => $sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Сессия не найдена или истекла
            destroySession($sessionToken);
            return null;
        }
        
        // Обновляем время последней активности и продлеваем сессию
        $newExpiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $stmt = $pdo->prepare("
            UPDATE admin_sessions 
            SET last_activity = NOW(), expires_at = :expires_at 
            WHERE session_token = :session_token
        ");
        $stmt->execute([
            'expires_at' => $newExpiresAt,
            'session_token' => $sessionToken
        ]);
        
        // Обновляем cookie с новым временем истечения
        setcookie(
            SESSION_COOKIE_NAME,
            $sessionToken,
            [
                'expires' => time() + SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        return [
            'id' => $session['user_id'],
            'username' => $session['username'],
            'email' => $session['email']
        ];
        
    } catch (PDOException $e) {
        error_log('checkAuth error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Уничтожение сессии
 * @param string|null $sessionToken
 */
function destroySession($sessionToken = null) {
    global $pdo;
    
    if ($sessionToken === null && isset($_COOKIE[SESSION_COOKIE_NAME])) {
        $sessionToken = $_COOKIE[SESSION_COOKIE_NAME];
    }
    
    if ($sessionToken) {
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE session_token = :session_token");
            $stmt->execute(['session_token' => $sessionToken]);
        } catch (PDOException $e) {
            error_log('destroySession error: ' . $e->getMessage());
        }
    }
    
    // Удаляем cookie
    setcookie(
        SESSION_COOKIE_NAME,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

/**
 * Принудительная проверка авторизации (редирект на login если не авторизован)
 */
function requireAuth() {
    $user = checkAuth();
    
    if (!$user) {
        // Сохраняем текущий URL для редиректа после входа
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    
    return $user;
}

/**
 * Получение информации о пользователе по ID
 * @param int $userId
 * @return array|null
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, created_at, is_active FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('getUserById error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Изменение пароля пользователя
 * @param int $userId
 * @param string $newPassword
 * @return bool
 */
function changePassword($userId, $newPassword) {
    global $pdo;
    
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE id = :id");
        return $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId
        ]);
    } catch (PDOException $e) {
        error_log('changePassword error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Удаление всех сессий пользователя (для принудительного выхода везде)
 * @param int $userId
 */
function destroyAllUserSessions($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    } catch (PDOException $e) {
        error_log('destroyAllUserSessions error: ' . $e->getMessage());
    }
}

// Инициализация таблиц при подключении
initAuthTables();

// Создаём пользователя по умолчанию если нет пользователей
createDefaultUser();
