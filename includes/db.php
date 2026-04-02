<?php

// Учётные данные БД для хостинга pyramida.sibadi.org
$host = 'localhost';
$dbname = 'pyramida_1';
$username = 'pyramida_1';
$password = '%t5+66qh}&RMMT&L';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    
    $pdo = new PDO($dsn, $username, $password, $options);

    $pdo->exec("USE `$dbname`");
    
    // Создаем таблицу сессий, если она не существует
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        data TEXT,
        expires INT(11) UNSIGNED NOT NULL,
        INDEX idx_expires (expires)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    require_once __DIR__ . '/DatabaseSessionHandler.php';
    $sessionHandler = new DatabaseSessionHandler($pdo);

    // Регистрируем обработчик сессий ТОЛЬКО если сессия ещё не запущена
    if (session_status() === PHP_SESSION_NONE) {
        // Важно: true означает, что register_shutdown_function() будет вызван
        // Это необходимо для корректной записи данных сессии при завершении скрипта
        session_set_save_handler($sessionHandler, true);
        
        // Обновляем флаг инициализации
        if (!defined('SESSION_INITIALIZED')) {
            define('SESSION_INITIALIZED', true);
        }
    }
    
    // Сохраняем $sessionHandler в глобальной области, чтобы он не был уничтожен сборщиком мусора
    $GLOBALS['sessionHandler'] = $sessionHandler;
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>