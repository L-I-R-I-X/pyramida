<?php
/**
 * Скрипт диагностики авторизации
 * Запускать через браузер: /admin/test_auth.php
 */

// Отключаем буферизацию для пошагового вывода
ob_implicit_flush(true);
@ob_end_flush();

echo "<pre style='font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "=== ДИАГНОСТИКА АВТОРИЗАЦИИ ===\n\n";

// Шаг 0: Проверка конфигурации
echo "[ШАГ 0] Проверка конфигурации...\n";
echo "   Текущий скрипт: " . __FILE__ . "\n";
echo "   Директория скрипта: " . __DIR__ . "\n";

$configPath = __DIR__ . '/../config.php';
echo "   Путь к config.php: $configPath\n";
echo "   realpath: " . (realpath($configPath) ?: 'не найден') . "\n";

if (!file_exists($configPath)) {
    echo "❌ ОШИБКА: config.php не найден по пути: $configPath\n";
    
    // Попробуем найти config.php другими способами
    echo "\n   Поиск альтернативных путей:\n";
    $altPaths = [
        dirname(__DIR__) . '/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config.php',
        getcwd() . '/config.php',
    ];
    
    foreach ($altPaths as $altPath) {
        $exists = file_exists($altPath) ? '✅ НАЙДЕН' : '❌ не найден';
        echo "   - $altPath: $exists\n";
    }
    exit;
}
echo "✅ config.php найден\n";

require_once $configPath;

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    die("❌ ОШИБКА: Константы БД не определены в config.php!\n");
}
echo "✅ Константы БД определены\n";

// Шаг 1: Подключение к БД
echo "\n[ШАГ 1] Подключение к базе данных...\n";
try {
    require_once __DIR__ . '/../includes/db.php';
    $pdo = getDB();
    echo "✅ Подключение к БД успешно\n";
} catch (Exception $e) {
    die("❌ ОШИБКА подключения к БД: " . $e->getMessage() . "\n");
}

// Шаг 2: Проверка таблицы sessions
echo "\n[ШАГ 2] Проверка таблицы sessions...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->rowCount() === 0) {
        echo "⚠️ Таблица sessions не найдена. Создаём...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT,
            last_access INT(11)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "✅ Таблица sessions создана\n";
    } else {
        echo "✅ Таблица sessions существует\n";
    }
    
    // Проверка структуры
    $stmt = $pdo->query("DESCRIBE sessions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['id', 'data', 'last_access'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            echo "❌ ОШИБКА: В таблице sessions отсутствует колонка '$col'\n";
            exit;
        }
    }
    echo "✅ Структура таблицы корректна\n";
} catch (Exception $e) {
    die("❌ ОШИБКА при работе с таблицей sessions: " . $e->getMessage() . "\n");
}

// Шаг 3: Инициализация сессии
echo "\n[ШАГ 3] Инициализация сессии...\n";
echo "session_status() до init: " . session_status() . " (" . 
     (session_status() === PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'ACTIVE') . ")\n";

require_once __DIR__ . '/../includes/auth.php';

// Проверяем, установлен ли наш кастомный хендлер
$handler = ini_get('session.save_handler');
echo "Текущий обработчик сессий: $handler\n";

try {
    initSession($pdo);
    echo "✅ initSession() выполнен без ошибок\n";
} catch (Exception $e) {
    die("❌ ОШИБКА в initSession(): " . $e->getMessage() . "\n");
}

echo "session_status() после init: " . session_status() . " (" . 
     (session_status() === PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'ACTIVE') . ")\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
    die("❌ ОШИБКА: Сессия не активна после initSession()!\n");
}

$session_id = session_id();
echo "✅ ID сессии: $session_id\n";

// Шаг 4: Проверка записи в БД
echo "\n[ШАГ 4] Проверка записи сессии в БД...\n";
try {
    $stmt = $pdo->prepare("SELECT id, data, last_access FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "✅ Запись в БД найдена:\n";
        echo "   ID: {$row['id']}\n";
        echo "   Last Access: " . date('Y-m-d H:i:s', $row['last_access']) . "\n";
        echo "   Data (raw): " . substr($row['data'], 0, 200) . (strlen($row['data']) > 200 ? '...' : '') . "\n";
        
        // Декодируем данные сессии
        $decoded = [];
        if (function_exists('session_decode')) {
            session_decode($row['data']);
            $decoded = $_SESSION;
        }
        echo "   Decoded SESSION: " . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️ Запись в БД НЕ найдена сразу после создания. Это может быть нормально (запись происходит при shutdown).\n";
    }
} catch (Exception $e) {
    echo "❌ ОШИБКА при чтении из БД: " . $e->getMessage() . "\n";
}

// Шаг 5: Тестовая запись в сессию
echo "\n[ШАГ 5] Тестовая запись в сессию...\n";
$_SESSION['test_value'] = 'TEST_' . time();
$_SESSION['test_time'] = date('Y-m-d H:i:s');
echo "✅ Записали в \$_SESSION: test_value = '{$_SESSION['test_value']}'\n";

// Принудительно сохраняем сессию
session_write_close();
echo "✅ session_write_close() выполнен\n";

// Проверяем запись снова
echo "\n[ШАГ 6] Проверка сохранности данных после write_close...\n";
try {
    $stmt = $pdo->prepare("SELECT id, data, last_access FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "✅ Запись в БД подтверждена:\n";
        echo "   Data length: " . strlen($row['data']) . " bytes\n";
        
        // Временное открытие сессии для декодирования
        session_start();
        if (isset($_SESSION['test_value'])) {
            echo "✅ Данные восстановлены: test_value = '{$_SESSION['test_value']}'\n";
        } else {
            echo "❌ ОШИБКА: Данные не восстановлены из БД!\n";
            echo "   Raw data: " . $row['data'] . "\n";
        }
        session_write_close();
    } else {
        echo "❌ ОШИБКА: Запись в БД отсутствует после session_write_close()!\n";
    }
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
}

// Шаг 7: Проверка куки
echo "\n[ШАГ 7] Проверка cookie...\n";
if (isset($_COOKIE[session_name()])) {
    echo "✅ Cookie '" . session_name() . "' присутствует:\n";
    echo "   Значение: " . $_COOKIE[session_name()] . "\n";
    echo "   Совпадает с session_id(): " . ($_COOKIE[session_name()] === $session_id ? 'ДА' : 'НЕТ') . "\n";
} else {
    echo "⚠️ Cookie '" . session_name() . "' НЕ обнаружен в этом запросе.\n";
    echo "   Это нормально для первого запроса, если куки ещё не отправлены браузером.\n";
    echo "   Обновите страницу для проверки.\n";
}

// Шаг 8: Эмуляция входа
echo "\n[ШАГ 8] Эмуляция успешного входа...\n";
session_start();
$_SESSION['admin_id'] = 999;
$_SESSION['admin_login'] = 'test_admin';
$_SESSION['logged_in'] = true;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo "✅ Установлены переменные сессии для админа:\n";
echo "   admin_id: {$_SESSION['admin_id']}\n";
echo "   admin_login: {$_SESSION['admin_login']}\n";
echo "   logged_in: " . ($_SESSION['logged_in'] ? 'true' : 'false') . "\n";
echo "   csrf_token: " . substr($_SESSION['csrf_token'], 0, 16) . "...\n";
session_write_close();

// Финальная проверка
echo "\n[ФИНАЛ] Перезапуск сессии для проверки сохранения...\n";
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo "✅ УСПЕХ! Сессия сохраняется корректно.\n";
    echo "   logged_in = " . ($_SESSION['logged_in'] ? 'true' : 'false') . "\n";
    echo "   admin_login = " . ($_SESSION['admin_login'] ?? 'не установлено') . "\n";
    echo "   csrf_token = " . (isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 16) . '...' : 'не установлено') . "\n";
    echo "\n🎉 АВТОРИЗАЦИЯ РАБОТАЕТ КОРРЕКТНО!\n";
    echo "Теперь попробуйте войти через /admin/login.php\n";
} else {
    echo "❌ ОШИБКА: Данные сессии не сохранились!\n";
    echo "   Доступные ключи: " . implode(', ', array_keys($_SESSION)) . "\n";
}
session_write_close();

echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
echo "</pre>";

// Для наглядности добавим кнопку обновления
echo "<div style='margin-top: 20px;'>";
echo "<button onclick='location.reload()' style='padding: 10px 20px; font-size: 16px; cursor: pointer;'>🔄 Обновить страницу (проверить куки)</button>";
echo "<br><br>";
echo "<a href='login.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Перейти к форме входа</a>";
echo "</div>";
