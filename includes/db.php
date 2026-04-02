<?php

// Учётные данные БД для хостинга pyramida.sibadi.org
$host = 'localhost';
$dbname = 'pyramida_1';
$username = 'pyramida_1';
$password = '%t5+66qh}&RMMT&L';

// Определяем константы БД СРАЗУ для использования в других файлах
if (!defined('DB_HOST')) define('DB_HOST', $host);
if (!defined('DB_NAME')) define('DB_NAME', $dbname);
if (!defined('DB_USER')) define('DB_USER', $username);
if (!defined('DB_PASS')) define('DB_PASS', $password);

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("USE `$dbname`");
    
    // Подключаем обработчик сессий
    require_once __DIR__ . '/DatabaseSessionHandler.php';
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}

/**
 * Инициализация сессии с использованием DatabaseSessionHandler
 * Вызывается явно перед работой с $_SESSION
 */
function initDatabaseSession($pdo) {
    // Если сессия уже запущена - ничего не делаем
    if (session_status() !== PHP_SESSION_NONE) {
        return true;
    }
    
    try {
        // Создаём обработчик сессий
        $sessionHandler = new DatabaseSessionHandler($pdo);
        
        // Регистрируем обработчик сессий
        session_set_save_handler($sessionHandler, true);
        
        // Настраиваем параметры сессии
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Запускаем сессию
        $result = session_start();
        
        if ($result === false) {
            error_log('Failed to start database session. Session status: ' . session_status());
            return false;
        }
        
        // Сохраняем обработчик в глобальной области чтобы не был уничтожен сборщиком мусора
        $GLOBALS['sessionHandler'] = $sessionHandler;
        
        if (!defined('SESSION_INITIALIZED')) {
            define('SESSION_INITIALIZED', true);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Session initialization failed: ' . $e->getMessage());
        return false;
    }
}
?>