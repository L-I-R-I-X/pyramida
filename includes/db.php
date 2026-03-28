<?php
// Локальные настройки БД
$host = 'localhost';
$dbname = 'pyramida';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Подключение без выбора БД
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Создаём базу данных, если не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` 
                DEFAULT CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci");
    
    // Выбираем базу данных
    $pdo->exec("USE `$dbname`");
    
    // ✅ Инициализация сессий в БД
    require_once __DIR__ . '/session_handler.php';
    $sessionHandler = new DatabaseSessionHandler($pdo);
    
    // ✅ Регистрируем обработчик ДО запуска сессии
    if (session_status() === PHP_SESSION_NONE) {
        session_set_save_handler($sessionHandler, true);
        session_start();
    }
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>