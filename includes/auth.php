<?php


require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';


define('SESSION_DIR', CACHE_DIR . 'sessions/');

define('SESSION_COOKIE_NAME', 'admin_session');

define('SESSION_LIFETIME', 30 * 24 * 60 * 60);


function ensureSessionDirExists() {
    if (!is_dir(SESSION_DIR)) {
        mkdir(SESSION_DIR, 0755, true);
    }
}


function initAuthTables() {
    global $pdo;
    
    try {
        
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
        
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        if ($stmt->rowCount() == 0) {
            error_log('initAuthTables: Таблица admin_users НЕ была создана');
            return false;
        }
        
        
        try {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role ENUM('main', 'regular') DEFAULT 'regular' AFTER email");
        } catch (PDOException $e) {
            
        }
        
        
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


function generateSessionToken() {
    return bin2hex(random_bytes(32));
}


function getSessionFile($sessionToken) {
    return SESSION_DIR . 'sess_' . $sessionToken . '.dat';
}


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
        
        
        return createSession($user['id']);
        
    } catch (PDOException $e) {
        error_log('authenticate error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}


function createSession($userId) {
    ensureSessionDirExists();
    
    try {
        $sessionToken = generateSessionToken();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $now = time();
        $expiresAt = $now + SESSION_LIFETIME;
        
        
        $sessionData = [
            'user_id' => $userId,
            'session_token' => $sessionToken,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'last_activity' => $now
        ];
        
        
        $sessionFile = getSessionFile($sessionToken);
        file_put_contents($sessionFile, json_encode($sessionData));
        
        
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


function checkAuth() {
    global $pdo;
    
    if (!isset($_COOKIE[SESSION_COOKIE_NAME])) {
        return null;
    }
    
    $sessionToken = $_COOKIE[SESSION_COOKIE_NAME];
    $session = readSessionFile($sessionToken);
    
    if (!$session) {
        
        destroySession($sessionToken);
        return null;
    }
    
    
    if ($session['expires_at'] < time()) {
        
        destroySession($sessionToken);
        return null;
    }
    
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, is_active FROM admin_users WHERE id = :user_id AND is_active = 1");
        $stmt->execute(['user_id' => $session['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            
            destroySession($sessionToken);
            return null;
        }
        
        
        $newExpiresAt = time() + SESSION_LIFETIME;
        $session['last_activity'] = time();
        $session['expires_at'] = $newExpiresAt;
        
        
        $sessionFile = getSessionFile($sessionToken);
        file_put_contents($sessionFile, json_encode($session));
        
        
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
        
        
        $email = null;
        try {
            $stmt = $pdo->prepare("SELECT email FROM admin_users WHERE id = :user_id");
            $stmt->execute(['user_id' => $session['user_id']]);
            $email = $stmt->fetchColumn();
        } catch (PDOException $e) {
            
            $email = null;
        }
        
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $email
        ];
        
    } catch (PDOException $e) {
        error_log('checkAuth error: ' . $e->getMessage());
        return null;
    }
}


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


function requireAuth() {
    $user = checkAuth();
    
    if (!$user) {
        
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    
    return $user;
}


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


function isMainAdmin($userId) {
    $user = getUserById($userId);
    return $user && $user['role'] === 'main';
}


function changeUsername($userId, $newUsername) {
    global $pdo;
    
    if (empty($newUsername) || strlen($newUsername) < 3) {
        return ['success' => false, 'message' => 'Логин должен быть не менее 3 символов'];
    }
    
    try {
        
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


function validatePasswordStrength($password) {
    $errors = [];
    
    if (empty($password)) {
        return ['valid' => false, 'errors' => ['Пароль обязателен']];
    }
    
    
    if (strlen($password) < 12) {
        $errors[] = 'Минимум 12 символов';
    }
    
    
    if (!preg_match('/[A-ZА-ЯЁ]/u', $password)) {
        $errors[] = 'Хотя бы одна заглавная буква';
    }
    
    
    if (!preg_match('/[a-zа-яё]/u', $password)) {
        $errors[] = 'Хотя бы одна строчная буква';
    }
    
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Хотя бы одна цифра';
    }
    
    
    if (!preg_match('/[^A-Za-z0-9А-Яа-яЁё]/', $password)) {
        $errors[] = 'Хотя бы один специальный символ';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}


function changePassword($userId, $newPassword) {
    global $pdo;
    
    
    $validation = validatePasswordStrength($newPassword);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => 'Требования к паролю не соблюдены: ' . implode(', ', $validation['errors'])];
    }
    
    try {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE id = :id");
        $result = $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId
        ]);
        
        if ($result) {
            
            destroyAllUserSessions($userId);
            return ['success' => true, 'message' => 'Пароль успешно изменён'];
        }
        return ['success' => false, 'message' => 'Ошибка при изменении пароля'];
    } catch (PDOException $e) {
        error_log('changePassword error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка базы данных'];
    }
}


function createAdminUser($username, $password, $role = 'regular') {
    global $pdo;
    
    if (empty($username) || strlen($username) < 3) {
        return ['success' => false, 'message' => 'Логин должен быть не менее 3 символов', 'user_id' => null];
    }
    
    
    $validation = validatePasswordStrength($password);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => 'Требования к паролю не соблюдены: ' . implode(', ', $validation['errors']), 'user_id' => null];
    }
    
    
    $role = 'regular';
    
    try {
        
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Такой логин уже занят', 'user_id' => null];
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, created_at, updated_at, is_active) VALUES (:username, :password, :role, :created_at, :updated_at, 1)");
        $stmt->execute([
            'username' => $username,
            'password' => $passwordHash,
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


function deleteAdminUser($userId) {
    global $pdo;
    
    try {
        
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
        
        
        destroyAllUserSessions($userId);
        
        
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


function getAllAdmins() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, username, role, created_at, is_active FROM admin_users WHERE is_active = 1 ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('getAllAdmins error: ' . $e->getMessage());
        return [];
    }
}


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


ensureSessionDirExists();
initAuthTables();


createDefaultUser();


if (rand(1, 100) <= 5) { 
    cleanupOldSessions();
}