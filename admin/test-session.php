<?php
// admin/test-session.php - Тест сессии и CSRF
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Тест сессии и CSRF токена</h2>";
echo "<style>body { font-family: Arial; }</style>";

// 1. Подключаем config.php
echo "<h3>1. Подключение config.php</h3>";
require_once '../includes/config.php';
echo "✅ config.php подключен<br>";

// 2. Подключаем db.php
echo "<h3>2. Подключение db.php</h3>";
require_once '../includes/db.php';
echo "✅ db.php подключен<br>";

// Проверяем константы БД
echo "<h3>3. Проверка констант БД</h3>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '<span style="color:red;">NOT DEFINED</span>') . "<br>";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '<span style="color:red;">NOT DEFINED</span>') . "<br>";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : '<span style="color:red;">NOT DEFINED</span>') . "<br>";
echo "DB_PASS: " . (defined('DB_PASS') ? '***DEFINED***' : '<span style="color:red;">NOT DEFINED</span>') . "<br>";

// 4. Запускаем сессию
echo "<h3>4. Запуск сессии</h3>";
echo "Session status before: " . session_status() . "<br>";
if (session_status() === PHP_SESSION_NONE) {
    $result = session_start();
    echo "Session start result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
} else {
    echo "Session already started<br>";
}
echo "Session status after: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";

// 5. Генерация CSRF токена
echo "<h3>5. Генерация CSRF токена</h3>";
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo "CSRF Token in session: " . (empty($_SESSION['csrf_token']) ? '<span style="color:red;">EMPTY</span>' : '✅ GENERATED (' . strlen($_SESSION['csrf_token']) . ' chars)') . "<br>";

// 6. Проверка записи в БД
echo "<h3>6. Проверка сессий в БД</h3>";
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM sessions');
    $count = $stmt->fetchColumn();
    echo "Сессий в БД: $count<br>";
    
    // Проверяем нашу сессию
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ?');
    $stmt->execute([session_id()]);
    $sessionData = $stmt->fetch();
    if ($sessionData) {
        echo "✅ Текущая сессия найдена в БД<br>";
        echo "Data: " . htmlspecialchars(substr($sessionData['data'], 0, 200)) . "...<br>";
    } else {
        echo "⚠️ Текущая сессия ещё не записана в БД (запишется при завершении скрипта)<br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// 7. Имитация входа
echo "<h3>7. Имитация успешного входа</h3>";
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_login'] = 'test_admin';
echo "Установлено \$_SESSION['admin_logged_in'] = true<br>";
echo "Установлено \$_SESSION['admin_login'] = 'test_admin'<br>";

// Принудительно записываем сессию
session_write_close();
echo "✅ session_write_close() вызван<br>";

// Проверяем запись снова
echo "<h3>8. Проверка после session_write_close()</h3>";
require_once '../includes/db.php'; // Переподключаем PDO если нужно
$stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ?');
$stmt->execute([session_id()]);
$sessionData = $stmt->fetch();
if ($sessionData) {
    echo "✅ Сессия записана в БД<br>";
    echo "Data: " . htmlspecialchars($sessionData['data']) . "<br>";
} else {
    echo "<span style='color:red'>❌ Сессия НЕ записана в БД</span><br>";
}

echo "<hr>";
echo "<a href='login.php'>← Вернуться на страницу входа</a><br>";
echo "<a href='test-session.php'>🔄 Обновить тест</a><br>";
?>
