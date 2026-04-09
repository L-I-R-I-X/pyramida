<?php
/**
 * Скрипт для удаления всех таблиц из базы данных
 * Использование: BASE_URL/clean_db.php?confirm=1
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Проверяем параметр confirm
if (!isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Очистка базы данных</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #d32f2f; }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #d32f2f;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn:hover { background: #b71c1c; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Очистка базы данных</h1>
        <div class="warning">
            <strong>Внимание!</strong> Это действие удалит ВСЕ таблицы из базы данных <code>' . htmlspecialchars(DB_NAME) . '</code>.
            <br><br>
            Будут удалены все данные: заявки, пользователи, настройки и т.д.
            <br><br>
            <strong>Это действие необратимо!</strong>
        </div>
        <p>Если вы уверены, что хотите удалить все таблицы, нажмите кнопку ниже:</p>
        <a href="?confirm=1" class="btn" onclick="return confirm(\'Вы действительно уверены? Все данные будут удалены!\')">Удалить все таблицы</a>
        <br>
        <a href="' . BASE_URL . '" class="back-link">← Вернуться на сайт</a>
    </div>
</body>
</html>';
    exit;
}

// Удаляем все таблицы
try {
    // Получаем список всех таблиц
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>База данных пуста</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #4caf50; }
        a { color: #2196f3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✓ База данных уже пуста</h1>
        <p>В базе данных <code>' . htmlspecialchars(DB_NAME) . '</code> нет таблиц.</p>
        <p><a href="' . BASE_URL . '">← Вернуться на сайт</a></p>
    </div>
</body>
</html>';
        exit;
    }
    
    $deletedTables = [];
    $errors = [];
    
    // Отключаем проверки внешних ключей
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Удаляем каждую таблицу
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE `" . $table . "`");
            $deletedTables[] = $table;
        } catch (PDOException $e) {
            $errors[] = "Ошибка при удалении таблицы $table: " . $e->getMessage();
        }
    }
    
    // Включаем проверки внешних ключей обратно
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Выводим результат
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результат очистки базы данных</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #4caf50; }
        .error { color: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        a { color: #2196f3; text-decoration: none; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196f3; color: white; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✓ Таблицы удалены</h1>
        <p>Из базы данных <code>' . htmlspecialchars(DB_NAME) . '</code> было удалено таблиц: <strong>' . count($deletedTables) . '</strong></p>
        
        <h3>Удалённые таблицы:</h3>
        <table>
            <tr><th>#</th><th>Название таблицы</th></tr>';
    
    foreach ($deletedTables as $index => $table) {
        echo '<tr><td>' . ($index + 1) . '</td><td>' . htmlspecialchars($table) . '</td></tr>';
    }
    
    echo '</table>';
    
    if (!empty($errors)) {
        echo '<h3 class="error">Ошибки:</h3><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }
    
    echo '<p><strong>База данных теперь пуста.</strong></p>
        <p>При следующем обращении к сайту таблицы будут созданы заново с данными по умолчанию.</p>
        <a href="' . BASE_URL . '" class="btn">← Вернуться на сайт</a>
    </div>
</body>
</html>';

} catch (PDOException $e) {
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { color: #d32f2f; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
        a { color: #2196f3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error">✗ Ошибка</h1>
        <p>Произошла ошибка при удалении таблиц:</p>
        <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
        <p><a href="' . BASE_URL . '">← Вернуться на сайт</a></p>
    </div>
</body>
</html>';
}
?>
