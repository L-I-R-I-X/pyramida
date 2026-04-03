<?php
// Учётные данные БД по умолчанию (localhost)
$host = 'localhost';
$dbname = 'pyramida_db';
$username = 'root';
$password = '';

// Определяем константы БД СРАЗУ для использования в других файлах
if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_NAME')) define('DB_NAME', $dbname);
if (!defined('DB_USER')) define('DB_USER', $username);
if (!defined('DB_PASS')) define('DB_PASS', $password);

// ============================================================================
// ФУНКЦИИ ДЛЯ СОЗДАНИЯ ДИРЕКТОРИЙ И БАЗЫ ДАННЫХ
// ============================================================================

/**
 * Создаёт необходимые директории проекта
 */
function ensureDirectoriesExist() {
    $basePath = __DIR__ . '/..';
    
    $requiredDirs = [
        $basePath . '/cache/',
        $basePath . '/cache/sessions/',
        $basePath . '/cache/certificates/',
        $basePath . '/uploads/',
        $basePath . '/uploads/originals/',
        $basePath . '/uploads/gallery/',
        $basePath . '/logs/',
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Подключение к MySQL серверу без выбора базы данных
 */
function getDbConnectionWithoutDb() {
    global $host, $username, $password;
    
    $dsn = "mysql:host=$host;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    return new PDO($dsn, $username, $password, $options);
}

/**
 * Создаёт базу данных, если она не существует
 */
function ensureDatabaseExists() {
    global $dbname;
    
    try {
        $pdo = getDbConnectionWithoutDb();
        
        // Создаём базу данных, если не существует
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        error_log('Database creation failed: ' . $e->getMessage());
        die('Ошибка создания базы данных: ' . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Создаёт таблицу settings и заполняет начальными значениями по умолчанию (0)
 */
function ensureSettingsTableExists() {
    global $pdo;
    
    try {
        // Создаём таблицу settings, если не существует
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value VARCHAR(255) DEFAULT '0',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Вставляем настройки по умолчанию со значением '0', если они ещё не существуют
        $defaultSettings = [
            'show_participants_table' => '0',
            'show_winners_table' => '0',
            'show_gallery' => '0',
            'show_certificates' => '0',
            'show_diplomas' => '0'
        ];
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO settings (setting_key, setting_value) 
            VALUES (:key, :value)
        ");
        
        foreach ($defaultSettings as $key => $defaultValue) {
            $stmt->execute(['key' => $key, 'value' => $defaultValue]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Settings table creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Создаёт таблицу applications для хранения заявок участников
 */
function ensureApplicationsTableExists() {
    global $pdo;
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fio VARCHAR(255) NOT NULL,
                educational_institution VARCHAR(255) NOT NULL,
                course VARCHAR(10) NOT NULL,
                nomination VARCHAR(50) NOT NULL,
                section VARCHAR(100) NOT NULL,
                work_title VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                work_file VARCHAR(255) NOT NULL,
                is_published TINYINT(1) DEFAULT 0,
                jury_score INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_nomination (nomination),
                INDEX idx_section (section),
                INDEX idx_published (is_published),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log('Applications table creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Создаёт таблицу admin_users для администраторов
 */
function ensureAdminUsersTableExists() {
    global $pdo;
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Создаём администратора по умолчанию, если таблица пуста
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:username, :password)");
            $stmt->execute(['username' => 'admin', 'password' => $defaultPassword]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Admin users table creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Создаёт таблицу admin_sessions для сессий администраторов
 */
function ensureAdminSessionsTableExists() {
    global $pdo;
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_session_token (session_token),
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log('Admin sessions table creation failed: ' . $e->getMessage());
        return false;
    }
}

// ============================================================================
// ОСНОВНОЕ ПОДКЛЮЧЕНИЕ К БД
// ============================================================================

// Сначала создаём директории
ensureDirectoriesExist();

// Затем создаём БД, если её нет
ensureDatabaseExists();

// Теперь подключаемся к созданной/существующей БД
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Создаём таблицу settings с начальными значениями
    ensureSettingsTableExists();
    
    // Создаём таблицу applications для заявок участников
    ensureApplicationsTableExists();
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>