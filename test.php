<?php
/**
 * Тестовая страница для отладки процесса аутентификации
 * Выводит подробную информацию о каждом шаге
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
    <title>Тест авторизации - отладка</title>
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
            color: #00d9ff;
            margin-top: 30px;
        }
        .section {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .info {
            color: #00d9ff;
        }
        .success {
            color: #00ff88;
        }
        .error {
            color: #ff4757;
        }
        .warning {
            color: #ffa502;
        }
        .debug {
            color: #a4b0be;
            font-size: 0.9em;
        }
        pre {
            background: #0f0f1a;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #0f3460;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #0f3460;
            color: #00d9ff;
        }
        .login-form {
            background: #16213e;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #0f3460;
            border-radius: 4px;
            background: #0f0f1a;
            color: #eee;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            padding: 12px;
            background: #FF6B00;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-form button:hover {
            background: #E55E00;
        }
        .log-entry {
            border-left: 3px solid #0f3460;
            padding-left: 10px;
            margin: 5px 0;
        }
        .log-entry.info { border-left-color: #00d9ff; }
        .log-entry.success { border-left-color: #00ff88; }
        .log-entry.error { border-left-color: #ff4757; }
        .log-entry.warning { border-left-color: #ffa502; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Тест авторизации - Подробная отладка</h1>
    
    <?php
    
    // Массив для сбора логов
    $logs = [];
    
    function addLog($type, $message, $data = null) {
        global $logs;
        $logs[] = [
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'time' => date('Y-m-d H:i:s.u')
        ];
    }
    
    // ============================================================================
    // ШАГ 1: Проверка окружения
    // ============================================================================
    addLog('info', 'ШАГ 1: Проверка окружения');
    
    addLog('debug', 'PHP Version', phpversion());
    addLog('debug', 'Server Software', $_SERVER['SERVER_SOFTWARE'] ?? 'N/A');
    addLog('debug', 'Request Method', $_SERVER['REQUEST_METHOD']);
    addLog('debug', 'Remote Address', $_SERVER['REMOTE_ADDR'] ?? 'N/A');
    addLog('debug', 'User Agent', $_SERVER['HTTP_USER_AGENT'] ?? 'N/A');
    
    // Проверка session_start
    if (session_status() === PHP_SESSION_NONE) {
        addLog('info', 'Session не запущена - это нормально, мы используем кастомные сессии в БД');
    } else {
        addLog('debug', 'Session ID', session_id());
    }
    
    // ============================================================================
    // ШАГ 2: Проверка cookie перед обработкой
    // ============================================================================
    addLog('info', 'ШАГ 2: Проверка существующих cookie');
    
    $cookieName = 'admin_session';
    $existingCookie = $_COOKIE[$cookieName] ?? null;
    
    if ($existingCookie) {
        addLog('success', 'Найдена существующая сессия в cookie', [
            'cookie_name' => $cookieName,
            'token_length' => strlen($existingCookie),
            'token_preview' => substr($existingCookie, 0, 16) . '...',
            'all_cookies' => $_COOKIE
        ]);
    } else {
        addLog('warning', 'Существующая сессия в cookie НЕ найдена', [
            'cookie_name' => $cookieName,
            'all_cookies' => $_COOKIE
        ]);
    }
    
    // ============================================================================
    // ШАГ 3: Подключение к БД
    // ============================================================================
    addLog('info', 'ШАГ 3: Подключение к базе данных');
    
    try {
        require_once __DIR__ . '/includes/config.php';
        addLog('success', 'config.php подключён', [
            'BASE_URL' => BASE_URL,
            'UPLOAD_DIR_ORIGINALS' => UPLOAD_DIR_ORIGINALS
        ]);
        
        require_once __DIR__ . '/includes/db.php';
        addLog('success', 'db.php подключён, соединение с БД установлено', [
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER
        ]);
        
        // Подключаем auth.php с дополнительным логированием
        $authPath = __DIR__ . '/includes/auth.php';
        addLog('info', 'Подключение auth.php...', ['path' => $authPath]);
        
        require_once $authPath;
        
        // Проверяем, существуют ли таблицы после подключения auth.php
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        $usersTableExists = $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
        $sessionsTableExists = $stmt->rowCount() > 0;
        
        addLog($usersTableExists && $sessionsTableExists ? 'success' : 'error', 
            'auth.php подключён, статус таблиц:', [
                'admin_users_exists' => $usersTableExists,
                'admin_sessions_exists' => $sessionsTableExists,
                'note' => $sessionsTableExists ? '' : 'Таблица admin_sessions НЕ создана - это причина ошибки!'
            ]
        );
        
    } catch (Exception $e) {
        addLog('error', 'Ошибка подключения файлов', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
    // ============================================================================
    // ШАГ 4: Проверка структуры таблиц
    // ============================================================================
    addLog('info', 'ШАГ 4: Проверка структуры таблиц БД');
    
    try {
        // Проверяем таблицу пользователей
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        $usersTableExists = $stmt->rowCount() > 0;
        addLog($usersTableExists ? 'success' : 'error', 
            "Таблица admin_users " . ($usersTableExists ? "существует" : "НЕ существует"));
        
        if ($usersTableExists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
            $userCount = $stmt->fetchColumn();
            addLog('info', "Количество пользователей в таблице: $userCount");
            
            if ($userCount > 0) {
                $stmt = $pdo->query("SELECT id, username, email, is_active, created_at FROM admin_users LIMIT 5");
                $users = $stmt->fetchAll();
                addLog('debug', 'Пользователи в БД', $users);
            }
        }
        
        // Проверяем таблицу сессий
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
        $sessionsTableExists = $stmt->rowCount() > 0;
        addLog($sessionsTableExists ? 'success' : 'error', 
            "Таблица admin_sessions " . ($sessionsTableExists ? "существует" : "НЕ существует"));
        
        if ($sessionsTableExists) {
            // Проверяем структуру таблицы sessions
            try {
                $stmt = $pdo->query("DESCRIBE admin_sessions");
                $columns = $stmt->fetchAll();
                addLog('debug', 'Структура таблицы admin_sessions', $columns);
            } catch (PDOException $e) {
                addLog('error', 'Ошибка получения структуры admin_sessions', ['message' => $e->getMessage()]);
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM admin_sessions");
            $sessionCount = $stmt->fetchColumn();
            addLog('info', "Количество сессий в таблице: $sessionCount");
            
            if ($sessionCount > 0) {
                $stmt = $pdo->query("
                    SELECT s.id, s.session_token, s.user_id, s.ip_address, 
                           s.created_at, s.expires_at, u.username 
                    FROM admin_sessions s
                    JOIN admin_users u ON s.user_id = u.id
                    ORDER BY s.created_at DESC 
                    LIMIT 5
                ");
                $sessions = $stmt->fetchAll();
                // Маскируем токены
                foreach ($sessions as &$s) {
                    $s['session_token'] = substr($s['session_token'], 0, 16) . '...';
                }
                addLog('debug', 'Последние сессии в БД', $sessions);
            }
        } else {
            addLog('error', 'КРИТИЧЕСКАЯ ОШИБКА: Таблица admin_sessions не существует!');
            addLog('info', 'Попытка создать таблицу admin_sessions вручную...', []);
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS admin_sessions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        session_token VARCHAR(64) UNIQUE NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent VARCHAR(255),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        expires_at TIMESTAMP NOT NULL,
                        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Проверяем снова
                $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
                $nowExists = $stmt->rowCount() > 0;
                addLog($nowExists ? 'success' : 'error', 
                    'Результат ручного создания таблицы:', [
                        'created' => $nowExists,
                        'note' => $nowExists ? 'Таблица успешно создана!' : 'Не удалось создать таблицу - проверьте права доступа'
                    ]
                );
            } catch (PDOException $e) {
                addLog('error', 'Ошибка ручного создания таблицы', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
            }
        }
        
    } catch (PDOException $e) {
        addLog('error', 'Ошибка проверки таблиц БД', [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }
    
    // ============================================================================
    // ШАГ 5: Обработка формы входа (если POST)
    // ============================================================================
    $authResult = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        addLog('info', 'ШАГ 5: Обработка POST-запроса входа');
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        addLog('debug', 'Полученные данные формы', [
            'username' => $username,
            'password_length' => strlen($password),
            'password_first_char' => strlen($password) > 0 ? $password[0] : 'N/A'
        ]);
        
        if (empty($username) || empty($password)) {
            addLog('error', 'Поля логин или пароль пусты');
            $authResult = ['success' => false, 'message' => 'Введите логин и пароль'];
        } else {
            // Перед вызовом authenticate - проверяем таблицу ещё раз
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
                $tableExistsBeforeAuth = $stmt->rowCount() > 0;
                addLog('info', 'Проверка таблицы admin_sessions ПЕРЕД authenticate()', [
                    'table_exists' => $tableExistsBeforeAuth
                ]);
                
                if (!$tableExistsBeforeAuth) {
                    addLog('error', 'Таблица admin_sessions НЕ существует перед созданием сессии!');
                    addLog('info', 'Создаём таблицу принудительно...');
                    try {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS admin_sessions (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT NOT NULL,
                                session_token VARCHAR(64) UNIQUE NOT NULL,
                                ip_address VARCHAR(45),
                                user_agent VARCHAR(255),
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                expires_at TIMESTAMP NOT NULL,
                                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                        ");
                        
                        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
                        $nowExists = $stmt->rowCount() > 0;
                        addLog($nowExists ? 'success' : 'error', 'Результат создания:', ['created' => $nowExists]);
                    } catch (PDOException $e) {
                        addLog('error', 'Не удалось создать таблицу', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
                    }
                }
            } catch (PDOException $e) {
                addLog('error', 'Ошибка проверки таблицы перед authenticate', ['message' => $e->getMessage()]);
            }
            
            addLog('info', 'Вызов функции authenticate()');
            
            // Логирование внутри authenticate через хак
            $startTime = microtime(true);
            $authResult = authenticate($username, $password);
            $endTime = microtime(true);
            
            addLog('debug', 'authenticate() выполнился за ' . round(($endTime - $startTime) * 1000, 2) . ' мс');
            addLog($authResult['success'] ? 'success' : 'error', 
                'Результат authenticate()', $authResult);
            
            // После authenticate - проверяем таблицу и результат
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'admin_sessions'");
                $tableExistsAfterAuth = $stmt->rowCount() > 0;
                addLog('info', 'Проверка таблицы admin_sessions ПОСЛЕ authenticate()', [
                    'table_exists' => $tableExistsAfterAuth
                ]);
                
                if ($tableExistsAfterAuth) {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_sessions");
                    $count = $stmt->fetchColumn();
                    addLog('debug', 'Количество сессий в БД после попытки входа: ' . $count);
                }
            } catch (PDOException $e) {
                addLog('error', 'Ошибка проверки таблицы после authenticate', ['message' => $e->getMessage()]);
            }
            
            if ($authResult['success']) {
                addLog('info', 'ШАГ 6: Успешная аутентификация - проверка cookie после setcookie');
                
                // Проверяем, была ли установлена cookie
                $newCookie = $_COOKIE[$cookieName] ?? null;
                if ($newCookie) {
                    addLog('success', 'Cookie сессии установлена', [
                        'token_length' => strlen($newCookie),
                        'token_preview' => substr($newCookie, 0, 16) . '...'
                    ]);
                } else {
                    addLog('warning', 'Cookie НЕ установлена в $_COOKIE (это нормально - она будет отправлена в заголовках)', [
                        'note' => 'setcookie() устанавливает заголовок, значение появится в $_COOKIE только после перезагрузки страницы'
                    ]);
                }
                
                // Проверяем заголовки (если возможно)
                addLog('debug', 'Проверка сессии в БД после создания');
                try {
                    $stmt = $pdo->prepare("
                        SELECT s.*, u.username 
                        FROM admin_sessions s
                        JOIN admin_users u ON s.user_id = u.id
                        WHERE s.session_token = :token
                        ORDER BY s.created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute(['token' => $authResult['token']]);
                    $sessionRecord = $stmt->fetch();
                    
                    if ($sessionRecord) {
                        addLog('success', 'Сессия найдена в БД', [
                            'id' => $sessionRecord['id'],
                            'user_id' => $sessionRecord['user_id'],
                            'username' => $sessionRecord['username'],
                            'ip_address' => $sessionRecord['ip_address'],
                            'created_at' => $sessionRecord['created_at'],
                            'expires_at' => $sessionRecord['expires_at']
                        ]);
                    } else {
                        addLog('error', 'Сессия НЕ найдена в БД после создания!');
                    }
                } catch (PDOException $e) {
                    addLog('error', 'Ошибка проверки сессии в БД', ['message' => $e->getMessage()]);
                }
                
                // Перенаправление
                addLog('info', 'Перенаправление на applications.php...');
                header('Location: applications.php');
                exit;
            }
        }
    } else {
        addLog('info', 'Это GET-запрос - форма не обрабатывается');
    }
    
    // ============================================================================
    // ШАГ 7: Проверка текущей авторизации
    // ============================================================================
    addLog('info', 'ШАГ 7: Проверка текущей авторизации через checkAuth()');
    
    $currentUser = checkAuth();
    
    if ($currentUser) {
        addLog('success', 'Пользователь авторизован', $currentUser);
    } else {
        addLog('warning', 'Пользователь НЕ авторизован');
    }
    
    // ============================================================================
    // ВЫВОД ЛОГОВ
    // ============================================================================
    ?>
    
    <div class="section">
        <h2>📋 Логи выполнения</h2>
        <?php foreach ($logs as $log): ?>
            <div class="log-entry <?= $log['type'] ?>">
                <strong>[<?= $log['time'] ?>]</strong> 
                <span class="<?= $log['type'] ?>"><?= strtoupper($log['type']) ?>:</span> 
                <?= htmlspecialchars($log['message']) ?>
                <?php if ($log['data'] !== null): ?>
                    <pre><?= htmlspecialchars(json_encode($log['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Форма входа -->
    <div class="login-form">
        <h2>🔐 Форма входа</h2>
        <?php if (isset($authResult) && !$authResult['success']): ?>
            <div class="error" style="padding: 10px; background: rgba(255,71,87,0.2); border-radius: 4px; margin-bottom: 15px;">
                ❌ <?= htmlspecialchars($authResult['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($currentUser): ?>
            <div class="success" style="padding: 10px; background: rgba(0,255,136,0.2); border-radius: 4px; margin-bottom: 15px;">
                ✅ Вы авторизованы как: <strong><?= htmlspecialchars($currentUser['username']) ?></strong>
            </div>
            <div style="margin-top: 15px;">
                <a href="admin/logout.php" style="color: #ff4757;">Выйти</a> | 
                <a href="admin/applications.php" style="color: #00d9ff;">Перейти в админ-панель</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <label>Логин:</label>
                <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                
                <label>Пароль:</label>
                <input type="password" name="password" required>
                
                <button type="submit">Войти</button>
            </form>
            <p class="debug" style="margin-top: 15px;">
                💡 Данные по умолчанию: <strong>admin / admin123</strong>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Дополнительная информация -->
    <div class="section">
        <h2>ℹ️ Дополнительная информация</h2>
        <table>
            <tr>
                <th>Параметр</th>
                <th>Значение</th>
            </tr>
            <tr>
                <td>Имя cookie сессии</td>
                <td><?= $cookieName ?></td>
            </tr>
            <tr>
                <td>Cookie установлена?</td>
                <td><?= $existingCookie ? '✅ Да' : '❌ Нет' ?></td>
            </tr>
            <tr>
                <td>Текущий пользователь</td>
                <td><?= $currentUser ? htmlspecialchars($currentUser['username']) : 'Не авторизован' ?></td>
            </tr>
            <tr>
                <td>Путь к auth.php</td>
                <td><?= __DIR__ . '/includes/auth.php' ?></td>
            </tr>
            <tr>
                <td>Путь к db.php</td>
                <td><?= __DIR__ . '/includes/db.php' ?></td>
            </tr>
        </table>
    </div>
    
</div>
</body>
</html>
