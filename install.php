<?php

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

        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO admins (login, password_hash) VALUES
            ('admin', '$passwordHash')
            ON DUPLICATE KEY UPDATE login = login
        ");
        $success[] = 'Администратор создан (логин: admin, пароль: admin123)';

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