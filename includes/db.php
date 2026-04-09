<?php
$host = 'localhost';
$dbname = 'pyramida_1';
$username = 'pyramida_1';
$password = '%t5+66qh}&RMMT&L';

if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_NAME')) define('DB_NAME', $dbname);
if (!defined('DB_USER')) define('DB_USER', $username);
if (!defined('DB_PASS')) define('DB_PASS', $password);

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

function ensureDatabaseExists() {
    global $dbname;

    try {
        $pdo = getDbConnectionWithoutDb();
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        error_log('Database creation failed: ' . $e->getMessage());
        die('Ошибка создания базы данных: ' . htmlspecialchars($e->getMessage()));
    }
}

function ensureSettingsTableExists() {
    global $pdo;

    try {
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

        $defaultSettings = [
            'show_participants_table' => '0',
            'show_winners_table' => '0',
            'show_gallery' => '0',
            'show_certificates' => '0',
            'show_diplomas' => '0',
            'gallery_sort_order' => 'date_desc',
        ];

        foreach ($defaultSettings as $key => $value) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = :key");
            $stmt->execute(['key' => $key]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
                $stmt->execute(['key' => $key, 'value' => $value]);
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log('Settings table creation failed: ' . $e->getMessage());
        die('Ошибка создания таблицы настроек: ' . htmlspecialchars($e->getMessage()));
    }
}

function ensureApplicationsTableExists() {
    global $pdo;

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fio VARCHAR(255) NOT NULL,
                educational_institution VARCHAR(255) NOT NULL,
                course INT NOT NULL,
                nomination VARCHAR(100) NOT NULL,
                section VARCHAR(100) NOT NULL,
                work_title VARCHAR(255),
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
        die('Ошибка создания таблицы заявок: ' . htmlspecialchars($e->getMessage()));
    }
}

function ensureAdminTablesExist() {
    global $pdo;

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('main', 'regular') DEFAULT 'regular',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_role (role),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
        if ($stmt->fetchColumn() == 0) {
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO admin_users (username, password_hash, role, is_active) VALUES ('admin', '$passwordHash', 'main', 1)");
        }

        return true;
    } catch (PDOException $e) {
        error_log('Admin tables creation failed: ' . $e->getMessage());
        die('Ошибка создания таблиц администратора: ' . htmlspecialchars($e->getMessage()));
    }
}

ensureDirectoriesExist();
ensureDatabaseExists();

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);

    ensureSettingsTableExists();
    ensureApplicationsTableExists();
    ensureAdminTablesExist();

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>
