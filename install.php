<?php

$installed = false;
$errors = [];
$success = [];

// Проверяем, был ли уже установлен сайт
$installLockFile = __DIR__ . '/.installed';
if (file_exists($installLockFile)) {
    $installed = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // Получаем данные из формы
    $host = $_POST['db_host'] ?? 'localhost';
    $dbname = $_POST['db_name'] ?? 'pyramida';
    $username = $_POST['db_username'] ?? 'root';
    $password = $_POST['db_password'] ?? '';
    
    $adminLogin = $_POST['admin_login'] ?? 'admin';
    $adminPassword = $_POST['admin_password'] ?? '';
    
    // Валидация данных администратора
    if (empty($adminLogin) || strlen($adminLogin) < 3) {
        $errors[] = 'Логин администратора должен быть не менее 3 символов';
    }
    if (empty($adminPassword) || strlen($adminPassword) < 6) {
        $errors[] = 'Пароль администратора должен быть не менее 6 символов';
    }
    
    if (empty($errors)) {
        try {
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` 
                        DEFAULT CHARACTER SET utf8mb4 
                        COLLATE utf8mb4_unicode_ci");
            
            $pdo->exec("USE `$dbname`");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS applications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fio VARCHAR(255) NOT NULL,
                    educational_institution VARCHAR(255) NOT NULL,
                    course TINYINT NOT NULL,
                    nomination VARCHAR(100) NOT NULL,
                    section VARCHAR(100) NOT NULL,
                    work_title VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(50),
                    work_file VARCHAR(255) NOT NULL,
                    is_published TINYINT(1) DEFAULT 0,
                    jury_score TINYINT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_published (is_published),
                    INDEX idx_nomination (nomination, section)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $success[] = 'Таблица applications создана';

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $success[] = 'Таблица settings создана';

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS admins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    login VARCHAR(100) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $success[] = 'Таблица admins создана';

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    data TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $success[] = 'Таблица sessions создана';

            $pdo->exec("
                INSERT INTO settings (setting_key, setting_value) VALUES
                ('show_participants_table', '1'),
                ('show_winners_table', '1'),
                ('show_gallery', '1'),
                ('show_certificates', '1'),
                ('show_diplomas', '1'),
                ('gallery_sort_order', 'date_desc')
                ON DUPLICATE KEY UPDATE setting_value = setting_value
            ");
            $success[] = 'Настройки добавлены';

            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO admins (login, password_hash) VALUES
                (:login, :password_hash)
            ");
            $stmt->execute([
                'login' => $adminLogin,
                'password_hash' => $passwordHash
            ]);
            $success[] = "Администратор создан (логин: " . htmlspecialchars($adminLogin) . ")";

            // Создаём файл конфигурации db.php
            $configContent = "<?php\n\n\$host = '" . addslashes($host) . "';\n\$dbname = '" . addslashes($dbname) . "';\n\$username = '" . addslashes($username) . "';\n\$password = '" . addslashes($password) . "';\n\n\$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\";\n\n\$options = [\n    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n    PDO::ATTR_EMULATE_PREPARES => false,\n];\n\ntry {\n    \n    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);\n\n    \$pdo->exec(\"USE `\$dbname`\");\n\n    require_once __DIR__ . '/session_handler.php';\n    \$sessionHandler = new DatabaseSessionHandler(\$pdo);\n\n    if (session_status() === PHP_SESSION_NONE) {\n        session_set_save_handler(\$sessionHandler, true);\n        session_start();\n    }\n    \n} catch (PDOException \$e) {\n    error_log('Database connection failed: ' . \$e->getMessage());\n    die('Ошибка подключения к базе данных: ' . htmlspecialchars(\$e->getMessage()));\n}\n?>\n";
            
            $configFile = __DIR__ . '/includes/db.php';
            if (file_put_contents($configFile, $configContent)) {
                $success[] = 'Файл конфигурации БД создан';
            } else {
                $errors[] = 'Не удалось создать файл конфигурации БД. Создайте его вручную.';
            }

            $dirs = [
                __DIR__ . '/uploads/originals',
                __DIR__ . '/uploads/gallery',
                __DIR__ . '/cache/fonts',
                __DIR__ . '/cache/temp',
                __DIR__ . '/cache/certificates',
                __DIR__ . '/logs',
            ];
            
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            $success[] = 'Папки созданы';
            
            // Создаём файл-блокировку установки
            file_put_contents($installLockFile, 'installed');
            $success[] = 'Установка завершена успешно!';
            
            $installed = true;
            
        } catch (PDOException $e) {
            $errors[] = 'Ошибка БД: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Ошибка: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка сайта конкурса «Пирамида»</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #FF6B00;
            text-align: center;
            margin-bottom: 10px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #FF6B00;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .warning {
            background: #FFF3CD;
            border: 1px solid #FFC107;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background: #D4EDDA;
            border: 1px solid #C3E6CB;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            background: #F8D7DA;
            border: 1px solid #F5C6CB;
            color: #721C24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .btn {
            background: #FF6B00;
            color: #FFFFFF;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        .btn:hover {
            background: #E55E00;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Установка сайта конкурса «Пирамида»</h1>
        
        <?php if ($installed): ?>
            <div class="success">
                <h3>✅ Сайт успешно установлен!</h3>
                <p>Теперь вы можете войти в панель администратора.</p>
            </div>
            <a href="admin/login.php" class="btn">Перейти в панель администратора</a>
            
        <?php else: ?>
            <div class="warning">
                <h3>⚠️ Внимание!</h3>
                <p>Этот скрипт создаст таблицы в базе данных и настроит сайт.</p>
                <p>После установки удалите файл <code>install.php</code> в целях безопасности.</p>
            </div>
            
            <?php foreach ($success as $message): ?>
                <div class="success">✅ <?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $message): ?>
                <div class="error">❌ <?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
            
            <form method="POST">
                <h2>📦 Настройки базы данных</h2>
                
                <div class="form-group">
                    <label for="db_host">Хост БД:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Имя базы данных:</label>
                    <input type="text" id="db_name" name="db_name" value="pyramida" required>
                </div>
                
                <div class="form-group">
                    <label for="db_username">Пользователь БД:</label>
                    <input type="text" id="db_username" name="db_username" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_password">Пароль БД:</label>
                    <input type="password" id="db_password" name="db_password" value="">
                </div>
                
                <h2>👤 Учётная запись администратора</h2>
                
                <div class="form-group">
                    <label for="admin_login">Логин администратора:</label>
                    <input type="text" id="admin_login" name="admin_login" value="admin" required minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Пароль администратора:</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="6">
                </div>
                
                <button type="submit" name="install" class="btn">🚀 Установить сайт</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>