<?php
/**
 * Тестовый файл для отладки процесса авторизации в админ-панели
 * Выводит все этапы: ввод логина-пароля, проверку БД, создание сессии и т.д.
 */

// Отключаем буферизацию вывода для немедленного отображения
ob_implicit_flush(true);
ob_end_flush();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест авторизации - Отладка</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #FF6B00;
            border-bottom: 2px solid #FF6B00;
            padding-bottom: 10px;
        }
        h2 {
            color: #4CAF50;
            margin-top: 30px;
        }
        .step {
            background: #16213e;
            border-left: 4px solid #FF6B00;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .step.success {
            border-left-color: #4CAF50;
        }
        .step.error {
            border-left-color: #f44336;
            background: #2d1b1b;
        }
        .step.info {
            border-left-color: #2196F3;
        }
        .step.warning {
            border-left-color: #ff9800;
        }
        pre {
            background: #0f0f23;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        code {
            color: #4CAF50;
        }
        .label {
            color: #FF6B00;
            font-weight: bold;
        }
        .value {
            color: #4CAF50;
        }
        .error-text {
            color: #f44336;
        }
        .warning-text {
            color: #ff9800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #0f3460;
            color: #FF6B00;
        }
        tr:nth-child(even) {
            background: #1a1a2e;
        }
        .form-section {
            background: #16213e;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #FF6B00;
        }
        .form-group input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            background: #0f0f23;
            color: #eee;
            font-family: inherit;
        }
        .btn {
            background: #FF6B00;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn:hover {
            background: #E55E00;
        }
        .btn-secondary {
            background: #4CAF50;
        }
        .btn-secondary:hover {
            background: #45a049;
        }
        .cookie-info, .session-info {
            background: #0f3460;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .check-mark {
            color: #4CAF50;
            font-size: 20px;
        }
        .cross-mark {
            color: #f44336;
            font-size: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Тест авторизации в админ-панель</h1>
    <p>Этот скрипт пошагово проверяет весь процесс аутентификации и выводит детальную информацию о каждом этапе.</p>

<?php

// =====================================================
// ЭТАП 0: Инициализация и загрузка зависимостей
// =====================================================
echo '<div class="step info">';
echo '<h2>📋 Этап 0: Инициализация</h2>';

$testResults = [];

// Проверка существования файлов
$configFile = __DIR__ . '/../includes/config.php';
$dbFile = __DIR__ . '/../includes/db.php';
$authFile = __DIR__ . '/../includes/auth.php';

echo '<div class="form-group">';
echo '<span class="label">Файл config.php:</span> ';
if (file_exists($configFile)) {
    echo '<span class="value"><span class="check-mark">✓</span> Существует</span>';
    $testResults['config_exists'] = true;
} else {
    echo '<span class="error-text"><span class="cross-mark">✗</span> Не найден</span>';
    $testResults['config_exists'] = false;
}
echo '</div>';

echo '<div class="form-group">';
echo '<span class="label">Файл db.php:</span> ';
if (file_exists($dbFile)) {
    echo '<span class="value"><span class="check-mark">✓</span> Существует</span>';
    $testResults['db_exists'] = true;
} else {
    echo '<span class="error-text"><span class="cross-mark">✗</span> Не найден</span>';
    $testResults['db_exists'] = false;
}
echo '</div>';

echo '<div class="form-group">';
echo '<span class="label">Файл auth.php:</span> ';
if (file_exists($authFile)) {
    echo '<span class="value"><span class="check-mark">✓</span> Существует</span>';
    $testResults['auth_exists'] = true;
} else {
    echo '<span class="error-text"><span class="cross-mark">✗</span> Не найден</span>';
    $testResults['auth_exists'] = false;
}
echo '</div>';

// Подключаем файлы
try {
    require_once $configFile;
    echo '<div class="form-group"><span class="label">Подключение config.php:</span> <span class="value"><span class="check-mark">✓</span> Успешно</span></div>';
    $testResults['config_loaded'] = true;
} catch (Exception $e) {
    echo '<div class="form-group"><span class="label">Подключение config.php:</span> <span class="error-text"><span class="cross-mark">✗</span> Ошибка: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
    $testResults['config_loaded'] = false;
}

try {
    require_once $dbFile;
    echo '<div class="form-group"><span class="label">Подключение db.php:</span> <span class="value"><span class="check-mark">✓</span> Успешно</span></div>';
    $testResults['db_loaded'] = true;
} catch (Exception $e) {
    echo '<div class="form-group"><span class="label">Подключение db.php:</span> <span class="error-text"><span class="cross-mark">✗</span> Ошибка: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
    $testResults['db_loaded'] = false;
}

try {
    require_once $authFile;
    echo '<div class="form-group"><span class="label">Подключение auth.php:</span> <span class="value"><span class="check-mark">✓</span> Успешно</span></div>';
    $testResults['auth_loaded'] = true;
} catch (Exception $e) {
    echo '<div class="form-group"><span class="label">Подключение auth.php:</span> <span class="error-text"><span class="cross-mark">✗</span> Ошибка: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
    $testResults['auth_loaded'] = false;
}

echo '</div>';

// =====================================================
// ЭТАП 1: Проверка директории сессий
// =====================================================
echo '<div class="step">';
echo '<h2>📁 Этап 1: Проверка директории сессий</h2>';

echo '<div class="form-group">';
echo '<span class="label">SESSION_DIR:</span> <span class="value">' . htmlspecialchars(defined('SESSION_DIR') ? SESSION_DIR : 'НЕ ОПРЕДЕЛЕНА') . '</span>';
echo '</div>';

if (defined('SESSION_DIR')) {
    echo '<div class="form-group">';
    echo '<span class="label">Директория существует:</span> ';
    if (is_dir(SESSION_DIR)) {
        echo '<span class="value"><span class="check-mark">✓</span> Да</span>';
    } else {
        echo '<span class="warning-text"><span class="cross-mark">✗</span> Нет (будет создана)</span>';
    }
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<span class="label">Права на запись:</span> ';
    if (is_writable(SESSION_DIR) || (!is_dir(SESSION_DIR) && is_writable(dirname(SESSION_DIR)))) {
        echo '<span class="value"><span class="check-mark">✓</span> Запись разрешена</span>';
    } else {
        echo '<span class="error-text"><span class="cross-mark">✗</span> Запись запрещена!</span>';
        echo '<br><span class="error-text">Решение: chmod 755 ' . htmlspecialchars(dirname(SESSION_DIR)) . '</span>';
    }
    echo '</div>';
    
    // Пробуем создать тестовый файл
    ensureSessionDirExists();
    $testToken = 'test_' . time();
    $testFile = getSessionFile($testToken);
    $testData = ['test' => true, 'time' => time()];
    
    echo '<div class="form-group">';
    echo '<span class="label">Тест записи файла сессии:</span> ';
    try {
        $writeResult = file_put_contents($testFile, json_encode($testData));
        if ($writeResult !== false) {
            echo '<span class="value"><span class="check-mark">✓</span> Успешно записано ' . $writeResult . ' байт</span>';
            // Удаляем тестовый файл
            unlink($testFile);
            echo '<br><span class="value">Тестовый файл удалён</span>';
        } else {
            echo '<span class="error-text"><span class="cross-mark">✗</span> Ошибка записи</span>';
        }
    } catch (Exception $e) {
        echo '<span class="error-text"><span class="cross-mark">✗</span> Исключение: ' . htmlspecialchars($e->getMessage()) . '</span>';
    }
    echo '</div>';
}

echo '</div>';

// =====================================================
// ЭТАП 2: Проверка подключения к БД
// =====================================================
echo '<div class="step">';
echo '<h2>🗄️ Этап 2: Проверка подключения к базе данных</h2>';

global $pdo;

if (isset($pdo) && $pdo instanceof PDO) {
    echo '<div class="form-group"><span class="label">PDO объект:</span> <span class="value"><span class="check-mark">✓</span> Существует</span></div>';
    
    try {
        $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        echo '<div class="form-group"><span class="label">Драйвер БД:</span> <span class="value">' . htmlspecialchars($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) . '</span></div>';
        
        // Проверяем таблицу admin_users
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        $tableExists = $stmt->rowCount() > 0;
        
        echo '<div class="form-group">';
        echo '<span class="label">Таблица admin_users:</span> ';
        if ($tableExists) {
            echo '<span class="value"><span class="check-mark">✓</span> Существует</span>';
        } else {
            echo '<span class="error-text"><span class="cross-mark">✗</span> Не найдена</span>';
        }
        echo '</div>';
        
        if ($tableExists) {
            // Проверяем структуру таблицы
            $stmt = $pdo->query("DESCRIBE admin_users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="form-group"><span class="label">Колонки таблицы:</span></div>';
            echo '<table><tr><th>Поле</th><th>Тип</th><th>Null</th><th>Key</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Считаем пользователей
            $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
            $userCount = $stmt->fetchColumn();
            echo '<div class="form-group"><span class="label">Всего пользователей:</span> <span class="value">' . $userCount . '</span></div>';
        }
        
    } catch (PDOException $e) {
        echo '<div class="form-group"><span class="label">Ошибка проверки БД:</span> <span class="error-text">' . htmlspecialchars($e->getMessage()) . '</span></div>';
    }
} else {
    echo '<div class="form-group"><span class="label">PDO объект:</span> <span class="error-text"><span class="cross-mark">✗</span> Не создан</span></div>';
}

echo '</div>';

// =====================================================
// ЭТАП 3: Проверка существующих пользователей
// =====================================================
echo '<div class="step">';
echo '<h2>👤 Этап 3: Существующие пользователи</h2>';

if (isset($pdo) && $tableExists) {
    try {
        $stmt = $pdo->query("SELECT id, username, email, role, is_active, created_at FROM admin_users ORDER BY id");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Активен</th><th>Создан</th></tr>';
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '<td>' . htmlspecialchars($user['role'] ?? 'N/A') . '</td>';
                echo '<td>' . ($user['is_active'] ? '<span class="value">Да</span>' : '<span class="error-text">Нет</span>') . '</td>';
                echo '<td>' . htmlspecialchars($user['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            echo '<div class="form-group">';
            echo '<span class="label">💡 Подсказка:</span> ';
            echo '<span class="value">По умолчанию создан пользователь <strong>admin</strong> с паролем <strong>admin123</strong></span>';
            echo '</div>';
        } else {
            echo '<div class="form-group warning">';
            echo '<span class="warning-text">⚠️ Таблица пуста! Необходимо создать пользователя.</span>';
            echo '</div>';
            
            // Предлагаем создать пользователя по умолчанию
            if (function_exists('createDefaultUser')) {
                echo '<div class="form-group">';
                echo '<span class="label">Создание пользователя по умолчанию:</span> ';
                $created = createDefaultUser('admin', 'admin123');
                if ($created) {
                    echo '<span class="value"><span class="check-mark">✓</span> Пользователь admin создан</span>';
                } else {
                    echo '<span class="error-text"><span class="cross-mark">✗</span> Ошибка создания</span>';
                }
                echo '</div>';
            }
        }
    } catch (PDOException $e) {
        echo '<div class="form-group"><span class="label">Ошибка получения пользователей:</span> <span class="error-text">' . htmlspecialchars($e->getMessage()) . '</span></div>';
    }
} else {
    echo '<div class="form-group"><span class="error-text">Невозможно проверить пользователей (нет подключения к БД или таблицы)</span></div>';
}

echo '</div>';

// =====================================================
// ЭТАП 4: Форма тестирования входа
// =====================================================
echo '<div class="step">';
echo '<h2>🔐 Этап 4: Тестирование входа</h2>';

$formSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login']);
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

echo '<div class="form-section">';
echo '<form method="POST">';
echo '<input type="hidden" name="test_login" value="1">';

echo '<div class="form-group">';
echo '<label for="username">Логин:</label>';
echo '<input type="text" id="username" name="username" value="' . htmlspecialchars($username) . '" required>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="password">Пароль:</label>';
echo '<input type="password" id="password" name="password" value="' . htmlspecialchars($password) . '" required>';
echo '</div>';

echo '<button type="submit" class="btn">🔍 Протестировать вход</button>';
echo ' <button type="button" class="btn btn-secondary" onclick="document.getElementById(\'credentials\').value=\'admin/admin123\'; document.getElementById(\'username\').value=\'admin\'; document.getElementById(\'password\').value=\'admin123\';">Заполнить данными по умолчанию</button>';
echo '</form>';
echo '</div>';

if ($formSubmitted) {
    echo '<div class="step info" style="margin-top: 20px;">';
    echo '<h3>📊 Результаты тестирования входа</h3>';
    
    echo '<div class="form-group">';
    echo '<span class="label">Введённый логин:</span> <span class="value">' . htmlspecialchars($username) . '</span>';
    echo '</div>';
    
    echo '<div class="form-group">';
    echo '<span class="label">Введённый пароль:</span> <span class="value">' . str_repeat('*', strlen($password)) . ' (' . strlen($password) . ' симв.)</span>';
    echo '</div>';
    
    // Этап 4.1: Проверка входных данных
    echo '<div class="step">';
    echo '<h4>4.1. Проверка входных данных</h4>';
    
    if (empty($username)) {
        echo '<div class="form-group error"><span class="error-text"><span class="cross-mark">✗</span> Логин пуст</span></div>';
    } else {
        echo '<div class="form-group success"><span class="value"><span class="check-mark">✓</span> Логин не пуст</span></div>';
    }
    
    if (empty($password)) {
        echo '<div class="form-group error"><span class="error-text"><span class="cross-mark">✗</span> Пароль пуст</span></div>';
    } else {
        echo '<div class="form-group success"><span class="value"><span class="check-mark">✓</span> Пароль не пуст</span></div>';
    }
    
    echo '</div>';
    
    // Этап 4.2: Поиск пользователя в БД
    echo '<div class="step">';
    echo '<h4>4.2. Поиск пользователя в базе данных</h4>';
    
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, is_active, role FROM admin_users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo '<div class="form-group success"><span class="value"><span class="check-mark">✓</span> Пользователь найден</span></div>';
                echo '<div class="form-group"><span class="label">ID:</span> <span class="value">' . $user['id'] . '</span></div>';
                echo '<div class="form-group"><span class="label">Логин:</span> <span class="value">' . htmlspecialchars($user['username']) . '</span></div>';
                echo '<div class="form-group"><span class="label">Роль:</span> <span class="value">' . htmlspecialchars($user['role'] ?? 'N/A') . '</span></div>';
                echo '<div class="form-group"><span class="label">Активен:</span> <span class="value">' . ($user['is_active'] ? 'Да' : 'Нет') . '</span></div>';
                
                if (!$user['is_active']) {
                    echo '<div class="form-group error"><span class="error-text"><span class="cross-mark">✗</span> Пользователь заблокирован</span></div>';
                }
                
                // Этап 4.3: Проверка пароля
                echo '<div class="step">';
                echo '<h4>4.3. Проверка пароля</h4>';
                
                echo '<div class="form-group">';
                echo '<span class="label">Хэш пароля в БД:</span><br>';
                echo '<pre><code>' . htmlspecialchars(substr($user['password_hash'], 0, 60)) . '...</code></pre>';
                echo '</div>';
                
                $passwordValid = password_verify($password, $user['password_hash']);
                
                echo '<div class="form-group">';
                echo '<span class="label">Результат password_verify():</span> ';
                if ($passwordValid) {
                    echo '<span class="value"><span class="check-mark">✓</span> Пароль верный</span>';
                } else {
                    echo '<span class="error-text"><span class="cross-mark">✗</span> Пароль неверный</span>';
                    echo '<br><span class="warning-text">Возможные причины:</span>';
                    echo '<ul>';
                    echo '<li>Неправильно введён пароль</li>';
                    echo '<li>Хэш пароля повреждён</li>';
                    echo '<li>Пароль был изменён вручную в БД без хеширования</li>';
                    echo '</ul>';
                }
                echo '</div>';
                
                echo '</div>'; // конец 4.3
                
                // Этап 4.4: Создание сессии
                if ($passwordValid && $user['is_active']) {
                    echo '<div class="step">';
                    echo '<h4>4.4. Создание сессии</h4>';
                    
                    // Вызываем функцию authenticate
                    $authResult = authenticate($username, $password);
                    
                    echo '<div class="form-group">';
                    echo '<span class="label">Результат authenticate():</span><br>';
                    echo '<pre>';
                    print_r($authResult);
                    echo '</pre>';
                    echo '</div>';
                    
                    if ($authResult['success']) {
                        echo '<div class="form-group success"><span class="value"><span class="check-mark">✓</span> Сессия успешно создана</span></div>';
                        echo '<div class="form-group"><span class="label">Токен сессии:</span> <span class="value">' . htmlspecialchars($authResult['token'] ?? 'не получен') . '</span></div>';
                        
                        // Проверяем cookie
                        echo '<div class="step">';
                        echo '<h4>4.5. Проверка cookie</h4>';
                        
                        echo '<div class="form-group">';
                        echo '<span class="label">SESSION_COOKIE_NAME:</span> <span class="value">' . htmlspecialchars(SESSION_COOKIE_NAME) . '</span>';
                        echo '</div>';
                        
                        echo '<div class="form-group">';
                        echo '<span class="label">Cookie установлен:</span> ';
                        if (isset($_COOKIE[SESSION_COOKIE_NAME])) {
                            echo '<span class="value"><span class="check-mark">✓</span> Да</span>';
                            echo '<br><span class="label">Значение cookie:</span> <span class="value">' . htmlspecialchars($_COOKIE[SESSION_COOKIE_NAME]) . '</span>';
                        } else {
                            echo '<span class="error-text"><span class="cross-mark">✗</span> Нет</span>';
                            echo '<br><span class="warning-text">Примечание: cookie может быть установлен, но ещё не доступен в $_COOKIE до следующего запроса</span>';
                        }
                        echo '</div>';
                        
                        echo '</div>'; // конец 4.5
                        
                        // Проверяем файл сессии
                        if (isset($authResult['token'])) {
                            echo '<div class="step">';
                            echo '<h4>4.6. Проверка файла сессии</h4>';
                            
                            $sessionFile = getSessionFile($authResult['token']);
                            echo '<div class="form-group"><span class="label">Путь к файлу:</span> <span class="value">' . htmlspecialchars($sessionFile) . '</span></div>';
                            
                            echo '<div class="form-group"><span class="label">Файл существует:</span> ';
                            if (file_exists($sessionFile)) {
                                echo '<span class="value"><span class="check-mark">✓</span> Да</span>';
                                
                                $sessionData = file_get_contents($sessionFile);
                                $sessionArray = json_decode($sessionData, true);
                                
                                echo '<div class="form-group">';
                                echo '<span class="label">Содержимое файла сессии:</span><br>';
                                echo '<pre>';
                                print_r($sessionArray);
                                echo '</pre>';
                                echo '</div>';
                            } else {
                                echo '<span class="error-text"><span class="cross-mark">✗</span> Нет</span>';
                            }
                            echo '</div>';
                            
                            echo '</div>'; // конец 4.6
                        }
                        
                        // Финальный результат
                        echo '<div class="step success">';
                        echo '<h3>✅ АВТОРИЗАЦИЯ УСПЕШНА!</h3>';
                        echo '<p>Теперь вы можете перейти в <a href="' . BASE_URL . 'admin/applications.php" class="value">админ-панель</a></p>';
                        echo '</div>';
                        
                    } else {
                        echo '<div class="step error">';
                        echo '<h3>❌ ОШИБКА СОЗДАНИЯ СЕССИИ</h3>';
                        echo '<p><span class="error-text">' . htmlspecialchars($authResult['message']) . '</span></p>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // конец 4.4
                }
                
            } else {
                echo '<div class="form-group error"><span class="error-text"><span class="cross-mark">✗</span> Пользователь не найден</span></div>';
                echo '<div class="form-group"><span class="label">Возможные причины:</span></div>';
                echo '<ul>';
                echo '<li>Пользователь с таким логином не существует</li>';
                echo '<li>Опечатка в логине</li>';
                echo '<li>Таблица admin_users пуста</li>';
                echo '</ul>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="form-group error"><span class="error-text"><span class="cross-mark">✗</span> Ошибка БД: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
        }
    } else {
        echo '<div class="form-group error"><span class="error-text">Нет подключения к базе данных</span></div>';
    }
    
    echo '</div>'; // конец 4.2
    
    echo '</div>'; // конец результатов тестирования
}

echo '</div>';

// =====================================================
// ЭТАП 5: Текущее состояние авторизации
// =====================================================
echo '<div class="step">';
echo '<h2>📝 Этап 5: Текущее состояние авторизации</h2>';

echo '<div class="form-group">';
echo '<span class="label">Есть cookie сессии:</span> ';
if (isset($_COOKIE[SESSION_COOKIE_NAME])) {
    echo '<span class="value"><span class="check-mark">✓</span> Да</span>';
    echo '<br><span class="label">Название cookie:</span> <span class="value">' . htmlspecialchars(SESSION_COOKIE_NAME) . '</span>';
    echo '<br><span class="label">Значение:</span> <span class="value">' . htmlspecialchars($_COOKIE[SESSION_COOKIE_NAME]) . '</span>';
} else {
    echo '<span class="warning-text"><span class="cross-mark">✗</span> Нет</span>';
}
echo '</div>';

if (isset($_COOKIE[SESSION_COOKIE_NAME])) {
    $currentSession = readSessionFile($_COOKIE[SESSION_COOKIE_NAME]);
    
    echo '<div class="form-group">';
    echo '<span class="label">Данные из файла сессии:</span><br>';
    if ($currentSession) {
        echo '<pre>';
        print_r($currentSession);
        echo '</pre>';
        
        // Проверяем срок действия
        echo '<div class="form-group">';
        echo '<span class="label">Срок действия:</span> ';
        if (isset($currentSession['expires_at'])) {
            $expiresAt = $currentSession['expires_at'];
            $now = time();
            if ($expiresAt > $now) {
                echo '<span class="value"><span class="check-mark">✓</span> Действительна</span>';
                $remaining = $expiresAt - $now;
                echo '<br><span class="label">Осталось времени:</span> <span class="value">' . floor($remaining / 3600) . ' ч. ' . floor(($remaining % 3600) / 60) . ' мин.</span>';
            } else {
                echo '<span class="error-text"><span class="cross-mark">✗</span> Истёк ' . date('Y-m-d H:i:s', $expiresAt) . '</span>';
            }
        }
        echo '</div>';
    } else {
        echo '<span class="error-text">Файл сессии не найден или повреждён</span>';
    }
    echo '</div>';
}

// Проверяем через checkAuth()
$currentUser = checkAuth();

echo '<div class="form-group">';
echo '<span class="label">Результат checkAuth():</span><br>';
if ($currentUser) {
    echo '<span class="value"><span class="check-mark">✓</span> Авторизован</span>';
    echo '<pre>';
    print_r($currentUser);
    echo '</pre>';
} else {
    echo '<span class="warning-text"><span class="cross-mark">✗</span> Не авторизован</span>';
}
echo '</div>';

echo '</div>';

// =====================================================
// ЭТАП 6: Информация о системе
// =====================================================
echo '<div class="step">';
echo '<h2>ℹ️ Этап 6: Информация о системе</h2>';

echo '<table>';
echo '<tr><th>Параметр</th><th>Значение</th></tr>';
echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>DOCUMENT_ROOT</td><td>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</td></tr>';
echo '<tr><td>SCRIPT_FILENAME</td><td>' . htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . '</td></tr>';
echo '<tr><td>BASE_URL</td><td>' . htmlspecialchars(BASE_URL) . '</td></tr>';
echo '<tr><td>UPLOAD_DIR_ORIGINALS</td><td>' . htmlspecialchars(UPLOAD_DIR_ORIGINALS) . '</td></tr>';
echo '<tr><td>CACHE_DIR</td><td>' . htmlspecialchars(CACHE_DIR) . '</td></tr>';
echo '<tr><td>SESSION_DIR</td><td>' . htmlspecialchars(defined('SESSION_DIR') ? SESSION_DIR : 'N/A') . '</td></tr>';
echo '<tr><td>session.save_handler</td><td>' . ini_get('session.save_handler') . '</td></tr>';
echo '<tr><td>session.use_cookies</td><td>' . ini_get('session.use_cookies') . '</td></tr>';
echo '<tr><td>open_basedir</td><td>' . htmlspecialchars(ini_get('open_basedir') ?: 'не установлено') . '</td></tr>';
echo '</table>';

echo '</div>';

?>

    <div class="step info">
        <h2>📖 Как использовать этот тест</h2>
        <ol>
            <li>Проверьте <strong>Этап 0</strong> - все файлы должны загрузиться без ошибок</li>
            <li>Проверьте <strong>Этап 1</strong> - директория сессий должна существовать и быть доступна для записи</li>
            <li>Проверьте <strong>Этап 2</strong> - подключение к БД должно работать, таблица admin_users должна существовать</li>
            <li>Проверьте <strong>Этап 3</strong> - должен быть хотя бы один пользователь (по умолчанию admin/admin123)</li>
            <li>В <strong>Этапе 4</strong> введите логин и пароль, нажмите "Протестировать вход"</li>
            <li>Изучите результаты каждого подэтапа (4.1 - 4.6) для поиска проблемы</li>
            <li>В <strong>Этапе 5</strong> проверьте текущее состояние авторизации</li>
        </ol>
        <p><strong>Частые проблемы:</strong></p>
        <ul>
            <li>❌ Директория /cache/sessions не существует или нет прав на запись → создайте директорию и установите chmod 755</li>
            <li>❌ Таблица admin_users не существует → вызовите initAuthTables() или переустановите БД</li>
            <li>❌ Пользователь не найден → создайте пользователя через форму или напрямую в БД</li>
            <li>❌ Пароль неверный → убедитесь, что используете правильный пароль (по умолчанию admin123)</li>
            <li>❌ Cookie не устанавливается → проверьте настройки браузера и домен</li>
        </ul>
    </div>

</div>
</body>
</html>
