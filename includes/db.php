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

    require_once __DIR__ . '/session_handler.php';
    $sessionHandler = new DatabaseSessionHandler($pdo);

    if (session_status() === PHP_SESSION_NONE) {
        session_set_save_handler($sessionHandler, true);
        session_start();
    }
    
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage()));
}
?>