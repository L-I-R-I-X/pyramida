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
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>