<?php
/**
 * Диагностический скрипт для проверки авторизации
 * Доступ: только по прямому URL (не защищен auth)
 */

// Отключаем буферизацию для немедленного вывода
while (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Диагностика авторизации</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #ccc; background: #252526; }
        .success { border-color: #4ec9b0; background: #2d3a2f; }
        .error { border-color: #f48771; background: #3a2d2d; }
        .info { border-color: #569cd6; background: #252f3a; }
        h1 { color: #569cd6; }
        h2 { color: #dcdcaa; font-size: 1.2em; margin-top: 30px; }
        code { color: #ce9178; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
        .val { color: #b5cea8; }
    </style>
</head>
<body>
<h1>🔍 Диагностика авторизации Pyramida</h1>
<p>Этот скрипт проверяет каждый этап инициализации сессии и входа в админ-панель.</p>

<?php

// Вспомогательная функция для вывода статуса
function step($title, $status, $details = '', $isError = false) {
    $class = $isError ? 'error' : ($status === 'OK' ? 'success' : 'info');
    echo "<div class=\"step {$class}\">";
    echo "<strong>[{$status}]</strong> {$title}";
    if ($details) {
        echo "<br><pre>" . htmlspecialchars($details) . "</pre>";
    }
    echo "</div>";
    flush();
}

// ==========================================
// ЭТАП 1: Проверка файловой системы
// ==========================================
echo "<h2>1. Файловая система и права доступа</h2>";

$dirsToCheck = [
    __DIR__ . '/../cache/sessions',
    __DIR__ . '/../cache/fonts',
    __DIR__ . '/../cache/temp',
    __DIR__ . '/../logs'
];

foreach ($dirsToCheck as $dir) {
    if (!is_dir($dir)) {
        step("Директория: {$dir}", 'MISSING', 'Директория не существует. Попробуйте создать вручную.', true);
        // Попытка создания
        if (@mkdir($dir, 0755, true)) {
            step("Создание директории", 'OK', "Успешно создано: {$dir}");
        } else {
            step("Создание директории", 'FAILED', "Не удалось создать: {$dir}. Проверьте права на родительскую папку.", true);
        }
    } else {
        step("Директория существует", 'OK', $dir);
    }

    if (is_dir($dir)) {
        $testFile = $dir . '/test_write_' . uniqid() . '.tmp';
        if (@file_put_contents($testFile, 'test') !== false) {
            step("Запись в {$dir}", 'OK', 'Права на запись подтверждены');
            @unlink($testFile);
        } else {
            step("Запись в {$dir}", 'FAILED', 'Нет прав на запись! Проверьте chmod/chown.', true);
        }
    }
}

// ==========================================
// ЭТАП 2: Подключение конфигурации
// ==========================================
echo "<h2>2. Конфигурация (config.php)</h2>";

if (file_exists(__DIR__ . '/../includes/config.php')) {
    step('Наличие config.php', 'OK');
    
    // Сохраняем текущие значения, чтобы восстановить потом, если нужно
    $oldErrorReporting = error_reporting();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    try {
        require_once __DIR__ . '/../includes/config.php';
        step('Подключение config.php', 'OK');
        
        // Проверка констант
        $constants = [
            'AUTH_SECRET_KEY',
            'AUTH_COOKIE_NAME',
            'DB_HOST',
            'DB_NAME',
            'DB_USER'
        ];
        
        foreach ($constants as $const) {
            if (defined($const)) {
                $val = constant($const);
                $masked = (strlen($val) > 10 && strpos($const, 'KEY') !== false) ? substr($val, 0, 5) . '...' : $val;
                step("Константа {$const}", 'OK', "Значение: {$masked}");
            } else {
                step("Константа {$const}", 'MISSING', 'Не определена!', true);
            }
        }
        
        // Проверка пути сессий
        if (defined('PATH_CACHE_SESSIONS') || defined('CACHE_DIR')) {
            $sessionPath = defined('PATH_CACHE_SESSIONS') ? PATH_CACHE_SESSIONS : (defined('CACHE_DIR') ? CACHE_DIR . 'sessions/' : 'не определен');
            step("Путь для сессий", 'OK', $sessionPath);
        } else {
            step("Путь для сессий", 'MISSING', 'Константы путей не найдены', true);
        }
        
    } catch (Exception $e) {
        step('Ошибка при подключении config.php', 'ERROR', $e->getMessage(), true);
        die('</body></html>');
    }
    
    error_reporting($oldErrorReporting);
} else {
    step('Наличие config.php', 'MISSING', 'Файл не найден!', true);
    die('</body></html>');
}

// ==========================================
// ЭТАП 3: Подключение к БД
// ==========================================
echo "<h2>3. Подключение к базе данных</h2>";

try {
    require_once __DIR__ . '/../includes/db.php';
    step('Подключение db.php', 'OK');
    
    if (isset($pdo) && $pdo instanceof PDO) {
        step('Объект PDO', 'OK', 'Соединение установлено');
        
        // Проверка таблицы admins
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        $count = $stmt->fetchColumn();
        step("Таблица 'admins'", 'OK', "Найдено пользователей: {$count}");
        
        if ($count == 0) {
            step("Таблица 'admins'", 'WARNING', 'В таблице нет ни одного пользователя! Вход невозможен.', true);
        } else {
            // Выводим логины (без паролей)
            $stmt = $pdo->query("SELECT login, created_at FROM admins LIMIT 5");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $list = "";
            foreach ($admins as $a) {
                $list .= "Login: {$a['login']}, Created: {$a['created_at']}\n";
            }
            step("Список админов", 'OK', $list);
        }
        
        // Проверка таблицы sessions (если используется БД для сессий)
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM php_sessions");
            $sessCount = $stmt->fetchColumn();
            step("Таблица 'php_sessions'", 'OK', "Активных сессий: {$sessCount}");
        } catch (Exception $e) {
            step("Таблица 'php_sessions'", 'MISSING', 'Таблица не существует. Сессии должны писаться в файлы.', true);
        }
        
    } else {
        step('Объект PDO', 'FAILED', '$pdo не инициализирован', true);
    }
} catch (Exception $e) {
    step('Ошибка подключения к БД', 'ERROR', $e->getMessage(), true);
}

// ==========================================
// ЭТАП 4: Инициализация сессии
// ==========================================
echo "<h2>4. Инициализация сессии</h2>";

// Принудительно закрываем любую открытую сессию перед тестом
if (session_status() === PHP_SESSION_ACTIVE) {
    session_abort();
    step("Предыдущая сессия", 'INFO', 'Была активна, прервана для чистоты теста');
}

// Настройки перед запуском
ini_set('session.save_handler', 'files');
if (defined('PATH_CACHE_SESSIONS')) {
    $savePath = PATH_CACHE_SESSIONS;
    ini_set('session.save_path', $savePath);
    step("session.save_path установлен", 'OK', $savePath);
} elseif (defined('CACHE_DIR')) {
    $savePath = CACHE_DIR . 'sessions/';
    ini_set('session.save_path', $savePath);
    step("session.save_path установлен (через CACHE_DIR)", 'OK', $savePath);
} else {
    step("session.save_path", 'WARNING', 'Используется системный путь по умолчанию', true);
}

// Запуск сессии
$sessionName = 'PYRAMIDA_DIAG_TEST';
session_name($sessionName);
$started = @session_start();

if ($started) {
    step('session_start()', 'OK', "ID сессии: " . session_id());
    
    // Проверка файла сессии
    $sessionId = session_id();
    $savePath = ini_get('session.save_path');
    $expectedFile = $savePath . '/sess_' . $sessionId;
    
    if (file_exists($expectedFile)) {
        step('Файл сессии создан', 'OK', $expectedFile);
        $content = file_get_contents($expectedFile);
        step('Содержимое файла сессии', 'INFO', $content ?: '(пусто)');
    } else {
        step('Файл сессии создан', 'FAILED', "Файл не найден по пути: {$expectedFile}. Проверьте права на папку {$savePath}", true);
        // Список файлов в папке
        if (is_dir($savePath)) {
            $files = scandir($savePath);
            step("Файлы в папке сессий", 'INFO', implode("\n", $files));
        }
    }
    
    // Запись тестовых данных
    $_SESSION['diag_test_time'] = time();
    $_SESSION['diag_test_rand'] = random_bytes(8);
    
    if (isset($_SESSION['diag_test_time'])) {
        step('Запись в $_SESSION', 'OK', 'Данные записаны успешно');
    } else {
        step('Запись в $_SESSION', 'FAILED', 'Не удалось записать данные', true);
    }
    
} else {
    $err = error_get_last();
    step('session_start()', 'FAILED', ($err ? $err['message'] : 'Неизвестная ошибка'), true);
}

// ==========================================
// ЭТАП 5: CSRF Токен
// ==========================================
echo "<h2>5. Генерация CSRF токена</h2>";

if (file_exists(__DIR__ . '/../includes/csrf.php')) {
    require_once __DIR__ . '/../includes/csrf.php';
    step('Подключение csrf.php', 'OK');
    
    if (function_exists('generateCsrfToken')) {
        $token = generateCsrfToken();
        if (!empty($token)) {
            step('Генерация токена', 'OK', "Токен: " . substr($token, 0, 15) . '...');
            
            // Проверка сохранения в сессии
            $sessionToken = $_SESSION['csrf_token'] ?? null;
            if ($sessionToken === $token) {
                step('Сохранение токена в сессии', 'OK', 'Совпадает');
            } else {
                step('Сохранение токена в сессии', 'FAILED', "Токен в сессии: " . ($sessionToken ? substr($sessionToken, 0, 10) . '...' : 'отсутствует'), true);
            }
        } else {
            step('Генерация токена', 'FAILED', 'Пустой токен', true);
        }
    } else {
        step('Функция generateCsrfToken', 'MISSING', 'Функция не найдена', true);
    }
} else {
    step('Файл csrf.php', 'MISSING', 'Файл не найден', true);
}

// ==========================================
// ЭТАП 6: Проверка Auth Helper
// ==========================================
echo "<h2>6. Проверка функций авторизации</h2>";

if (file_exists(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
    step('Подключение auth.php', 'OK');
    
    // Проверка функции requireAuth
    if (function_exists('requireAuth')) {
        step('Функция requireAuth', 'OK', 'Существует');
    } else {
        step('Функция requireAuth', 'MISSING', 'Критическая функция не найдена!', true);
    }
    
    // Проверка функции verifyPassword
    if (function_exists('verifyPassword')) {
        step('Функция verifyPassword', 'OK', 'Существует');
        
        // Тест хеширования (если есть функция hashPassword)
        if (function_exists('hashPassword')) {
            $testPass = 'test123';
            $hash = hashPassword($testPass);
            if (verifyPassword($testPass, $hash)) {
                step('Тест хеширования пароля', 'OK', 'hashPassword -> verifyPassword работают корректно');
            } else {
                step('Тест хеширования пароля', 'FAILED', 'Несоответствие хеша', true);
            }
        }
    } else {
        step('Функция verifyPassword', 'MISSING', 'Функция не найдена', true);
    }
} else {
    step('Файл auth.php', 'MISSING', 'Файл не найден', true);
}

// ==========================================
// ЭТАП 7: Имитация входа
// ==========================================
echo "<h2>7. Имитация входа (Simulation)</h2>";

// Получаем первого админа из БД для теста
$testAdmin = null;
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT * FROM admins LIMIT 1");
    $testAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($testAdmin) {
    step('Тестовый пользователь', 'OK', "Логин: {$testAdmin['login']}");
    
    // Проверяем, можем ли мы проверить пароль
    if (function_exists('verifyPassword')) {
        // Поскольку мы не знаем реальный пароль, просто проверяем формат хеша
        $hash = $testAdmin['password_hash'];
        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0) {
            step('Формат хеша пароля', 'OK', 'Хеш выглядит валидным (bcrypt/argon2)');
        } else {
            step('Формат хеша пароля', 'WARNING', "Странный формат хеша: " . substr($hash, 0, 10), true);
        }
    }
} else {
    step('Тестовый пользователь', 'FAILED', 'Не удалось получить данные пользователя', true);
}

// ==========================================
// ФИНАЛ
// ==========================================
echo "<h2>📊 Итоговый отчет</h2>";
echo "<div class='step info'>";
echo "<p><strong>Рекомендации:</strong></p>";
echo "<ul>";
echo "<li>Если этап 1 (Файловая система) красный: выполните <code>chmod -R 755 cache/ logs/ uploads/</code> и <code>chown -R www-data:www-data ...</code></li>";
echo "<li>Если этап 4 (Сессия) красный: проверьте, что папка <code>cache/sessions</code> существует и доступна для записи пользователю веб-сервера.</li>";
echo "<li>Если этап 3 (БД) красный: проверьте credentials в <code>includes/config.php</code>.</li>";
echo "<li>Если все этапы зеленые, но вход не работает: очистите куки браузера для этого домена и попробуйте снова.</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='login.php' style='color:#569cd6'>← Вернуться на страницу входа</a></p>";

?>
</body>
</html>
