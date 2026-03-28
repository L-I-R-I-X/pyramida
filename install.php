<?php
/**
 * Скрипт инициализации базы данных
 * Запустить один раз: http://localhost/pyramida/install.php
 * Для локального тестирования перед хостингом
 */

// Данные для подключения к БД (локальные)
$host = 'localhost';
$dbname = 'pyramida';
$username = 'root';
$password = '';

$installed = false;
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Таблица заявок
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fio VARCHAR(255) NOT NULL,
                vuz VARCHAR(255) NOT NULL,
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
        
        // Таблица настроек
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = 'Таблица settings создана';
        
        // Таблица администраторов
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                login VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = 'Таблица admins создана';
        
        // Таблица сессий
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $success[] = 'Таблица sessions создана';
        
        // Начальные настройки
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
        
        // Администратор по умолчанию (пароль: admin123)
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO admins (login, password_hash) VALUES
            ('admin', '$passwordHash')
            ON DUPLICATE KEY UPDATE login = login
        ");
        $success[] = 'Администратор создан (логин: admin, пароль: admin123)';
        
        // Создаём папки
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
        
        $installed = true;
        
    } catch (PDOException $e) {
        $errors[] = 'Ошибка БД: ' . $e->getMessage();
    } catch (Exception $e) {
        $errors[] = 'Ошибка: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка сайта конкурса «Пирамида»</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #F5F5F5;
        }
        .container {
            background: #FFFFFF;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1A1A1A;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .btn {
            background: #FF6B00;
            color: #FFFFFF;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #E55E00;
        }
        .warning {
            background: #FFF3CD;
            border: 1px solid #FFC107;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        code {
            background: #F5F5F5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏛️ Установка сайта конкурса «Пирамида»</h1>
        
        <?php if ($installed): ?>
            <div class="success">
                <h3>✅ Установка завершена успешно!</h3>
                <p>База данных инициализирована, таблицы созданы.</p>
            </div>
            
            <div class="warning">
                <h3>⚠️ Важно!</h3>
                <p>1. Удалите файл <code>install.php</code> после установки</p>
                <p>2. Смените пароль администратора в панели управления</p>
                <p>3. Проверьте права на папки <code>uploads/</code> и <code>cache/</code></p>
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
                <button type="submit" name="install" class="btn">🚀 Установить сайт</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>