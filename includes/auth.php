<?php
/**
 * Система аутентификации с хранением сессий в файлах (/cache/sessions)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Путь для хранения файлов сессий
define('SESSION_DIR', CACHE_DIR . 'sessions/');
// Название cookie для сессии
define('SESSION_COOKIE_NAME', 'admin_session');
// Время жизни сессии (30 дней)
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);

/**
 * Убедиться, что директория сессий существует
 */
function ensureSessionDirExists() {
    if (!is_dir(SESSION_DIR)) {
        mkdir(SESSION_DIR, 0755, true);
    }
}

/**
 * Создание таблицы пользователей, если она не существует
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
                role ENUM('main', 'regular') DEFAULT 'regular',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                INDEX idx_username (username),
                INDEX idx_is_active (is_active),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Проверяем, создана ли таблица пользователей
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        if ($stmt->rowCount() == 0) {
            error_log('initAuthTables: Таблица admin_users НЕ была создана');
            return false;
        }
        
        // Добавляем поле role, если оно отсутствует (для обратной совместимости)
        try {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role ENUM('main', 'regular') DEFAULT 'regular' AFTER email");
        } catch (PDOException $e) {
            // Поле уже существует или другая ошибка - игнорируем
        }
        
        // Устанавливаем роль 'main' для первого пользователя (admin), если у него нет роли
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'main'");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("UPDATE admin_users SET role = 'main' WHERE username = 'admin' AND (role IS NULL OR role = 'regular') LIMIT 1");
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
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email, created_at, updated_at, is_active) VALUES (:username, :password, :email, :created_at, :updated_at, 1)");
            $stmt->execute([
                'username' => $username,
                'password' => $passwordHash,
                'email' => 'admin@localhost',
                'created_at' => $now,
                'updated_at' => $now
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
 * Получение пути к файлу сессии по токену
 */
function getSessionFile($sessionToken) {
    return SESSION_DIR . 'sess_' . $sessionToken . '.dat';
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
 * Создание сессии в файле
 * @param int $userId
 * @return array ['success' => bool, 'message' => string, 'token' => string|null]
 */
function createSession($userId) {
    ensureSessionDirExists();
    
    try {
        $sessionToken = generateSessionToken();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $now = time();
        $expiresAt = $now + SESSION_LIFETIME;
        
        // Данные сессии
        $sessionData = [
            'user_id' => $userId,
            'session_token' => $sessionToken,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'last_activity' => $now
        ];
        
        // Сохраняем сессию в файл
        $sessionFile = getSessionFile($sessionToken);
        file_put_contents($sessionFile, json_encode($sessionData));
        
        // Устанавливаем cookie с токеном сессии
        setcookie(
            SESSION_COOKIE_NAME,
            $sessionToken,
            [
                'expires' => $expiresAt,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        return ['success' => true, 'message' => 'Вход выполнен успешно', 'token' => $sessionToken];
        
    } catch (Exception $e) {
        error_log('createSession error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка создания сессии'];
    }
}

/**
 * Чтение данных сессии из файла
 * @param string $sessionToken
 * @return array|null
 */
function readSessionFile($sessionToken) {
    $sessionFile = getSessionFile($sessionToken);
    
    if (!file_exists($sessionFile)) {
        return null;
    }
    
    $data = file_get_contents($sessionFile);
    $session = json_decode($data, true);
    
    if (!$session) {
        return null;
    }
    
    return $session;
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
    $session = readSessionFile($sessionToken);
    
    if (!$session) {
        // Сессия не найдена
        destroySession($sessionToken);
        return null;
    }
    
    // Проверяем срок действия
    if ($session['expires_at'] < time()) {
        // Сессия истекла
        destroySession($sessionToken);
        return null;
    }
    
    // Получаем данные пользователя из БД
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, is_active FROM admin_users WHERE id = :user_id AND is_active = 1");
        $stmt->execute(['user_id' => $session['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Пользователь не найден или заблокирован
            destroySession($sessionToken);
            return null;
        }
        
        // Обновляем время последней активности и продлеваем сессию
        $newExpiresAt = time() + SESSION_LIFETIME;
        $session['last_activity'] = time();
        $session['expires_at'] = $newExpiresAt;
        
        // Сохраняем обновлённую сессию
        $sessionFile = getSessionFile($sessionToken);
        file_put_contents($sessionFile, json_encode($session));
        
        // Обновляем cookie с новым временем истечения
        setcookie(
            SESSION_COOKIE_NAME,
            $sessionToken,
            [
                'expires' => $newExpiresAt,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
        
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
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
    if ($sessionToken === null && isset($_COOKIE[SESSION_COOKIE_NAME])) {
        $sessionToken = $_COOKIE[SESSION_COOKIE_NAME];
    }
    
    if ($sessionToken) {
        $sessionFile = getSessionFile($sessionToken);
        if (file_exists($sessionFile)) {
            unlink($sessionFile);
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
 * Очистка старых сессий (сборщик мусора)
 */
function cleanupOldSessions() {
    ensureSessionDirExists();
    
    $currentTime = time();
    $files = glob(SESSION_DIR . 'sess_*.dat');
    
    if ($files) {
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $session = json_decode($data, true);
            
            if ($session && isset($session['expires_at']) && $session['expires_at'] < $currentTime) {
                unlink($file);
            }
        }
    }
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
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, is_active FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('getUserById error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Проверка, является ли пользователь главным администратором
 * @param int $userId
 * @return bool
 */
function isMainAdmin($userId) {
    $user = getUserById($userId);
    return $user && $user['role'] === 'main';
}

/**
 * Изменение логина пользователя
 * @param int $userId
 * @param string $newUsername
 * @return array ['success' => bool, 'message' => string]
 */
function changeUsername($userId, $newUsername) {
    global $pdo;
    
    if (empty($newUsername) || strlen($newUsername) < 3) {
        return ['success' => false, 'message' => 'Логин должен быть не менее 3 символов'];
    }
    
    try {
        // Проверяем, не занят ли логин другим пользователем
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = :username AND id != :id");
        $stmt->execute(['username' => $newUsername, 'id' => $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Такой логин уже занят'];
        }
        
        $stmt = $pdo->prepare("UPDATE admin_users SET username = :username WHERE id = :id");
        $result = $stmt->execute([
            'username' => $newUsername,
            'id' => $userId
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Логин успешно изменён'];
        }
        return ['success' => false, 'message' => 'Ошибка при изменении логина'];
    } catch (PDOException $e) {
        error_log('changeUsername error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}

/**
 * Изменение пароля пользователя
 * @param int $userId
 * @param string $newPassword
 * @return array ['success' => bool, 'message' => string]
 */
function changePassword($userId, $newPassword) {
    global $pdo;
    
    if (empty($newPassword) || strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Пароль должен быть не менее 6 символов'];
    }
    
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE id = :id");
        $result = $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId
        ]);
        
        if ($result) {
            // Уничтожаем все сессии пользователя для безопасности
            destroyAllUserSessions($userId);
            return ['success' => true, 'message' => 'Пароль успешно изменён'];
        }
        return ['success' => false, 'message' => 'Ошибка при изменении пароля'];
    } catch (PDOException $e) {
        error_log('changePassword error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}

/**
 * Создание нового администратора
 * @param string $username
 * @param string $password
 * @param string $role
 * @param string $email
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function createAdminUser($username, $password, $role = 'regular', $email = '') {
    global $pdo;
    
    if (empty($username) || strlen($username) < 3) {
        return ['success' => false, 'message' => 'Логин должен быть не менее 3 символов', 'user_id' => null];
    }
    
    if (empty($password) || strlen($password) < 6) {
        return ['success' => false, 'message' => 'Пароль должен быть не менее 6 символов', 'user_id' => null];
    }
    
    if (!in_array($role, ['main', 'regular'])) {
        $role = 'regular';
    }
    
    try {
        // Проверяем, не занят ли логин
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Такой логин уже занят', 'user_id' => null];
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email, role, created_at, updated_at, is_active) VALUES (:username, :password, :email, :role, :created_at, :updated_at, 1)");
        $stmt->execute([
            'username' => $username,
            'password' => $passwordHash,
            'email' => $email,
            'role' => $role,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        return ['success' => true, 'message' => 'Администратор успешно создан', 'user_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log('createAdminUser error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных', 'user_id' => null];
    }
}

/**
 * Удаление администратора
 * @param int $userId
 * @return array ['success' => bool, 'message' => string]
 */
function deleteAdminUser($userId) {
    global $pdo;
    
    try {
        // Нельзя удалить последнего главного администратора
        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'main' AND is_active = 1");
        $mainAdminCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Пользователь не найден'];
        }
        
        if ($user['role'] === 'main' && $mainAdminCount <= 1) {
            return ['success' => false, 'message' => 'Нельзя удалить последнего главного администратора'];
        }
        
        // Удаляем все сессии пользователя
        destroyAllUserSessions($userId);
        
        // Мягкое удаление - деактивируем пользователя
        $stmt = $pdo->prepare("UPDATE admin_users SET is_active = 0 WHERE id = :id");
        $result = $stmt->execute(['id' => $userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Администратор успешно удалён'];
        }
        return ['success' => false, 'message' => 'Ошибка при удалении'];
    } catch (PDOException $e) {
        error_log('deleteAdminUser error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}

/**
 * Изменение роли администратора
 * @param int $userId
 * @param string $newRole
 * @return array ['success' => bool, 'message' => string]
 */
function changeAdminRole($userId, $newRole) {
    global $pdo;
    
    if (!in_array($newRole, ['main', 'regular'])) {
        return ['success' => false, 'message' => 'Недопустимая роль'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE admin_users SET role = :role WHERE id = :id");
        $result = $stmt->execute([
            'role' => $newRole,
            'id' => $userId
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Роль успешно изменена'];
        }
        return ['success' => false, 'message' => 'Ошибка при изменении роли'];
    } catch (PDOException $e) {
        error_log('changeAdminRole error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}

/**
 * Получение списка всех администраторов
 * @return array
 */
function getAllAdmins() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, username, email, role, created_at, is_active FROM admin_users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('getAllAdmins error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Удаление всех сессий пользователя (для принудительного выхода везде)
 * @param int $userId
 */
function destroyAllUserSessions($userId) {
    ensureSessionDirExists();
    
    $files = glob(SESSION_DIR . 'sess_*.dat');
    
    if ($files) {
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $session = json_decode($data, true);
            
            if ($session && isset($session['user_id']) && $session['user_id'] == $userId) {
                unlink($file);
            }
        }
    }
}

// Инициализация таблиц при подключении
ensureSessionDirExists();
initAuthTables();

// Создаём пользователя по умолчанию если нет пользователей
createDefaultUser();

// Очищаем старые сессии периодически
if (rand(1, 100) <= 5) { // 5% шанс при каждом запросе
    cleanupOldSessions();
}
