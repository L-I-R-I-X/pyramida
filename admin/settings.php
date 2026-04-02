<?php
$configFile = __DIR__ . '/../includes/config.php';
if (!file_exists($configFile)) {
    die('Ошибка: Файл config.php не найден. Переименуйте config.example.php в config.php и настройте параметры подключения.');
}
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Явно инициализируем сессию
initSession();

requireAuth();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showParticipants = isset($_POST['show_participants_table']) ? '1' : '0';
    $showWinners = isset($_POST['show_winners_table']) ? '1' : '0';
    
    $showGallery = isset($_POST['show_gallery']) ? '1' : '0';
    
    $showCertificates = isset($_POST['show_certificates']) ? '1' : '0';
    $showDiplomas = isset($_POST['show_diplomas']) ? '1' : '0';
    
    updateSetting('show_participants_table', $showParticipants);
    updateSetting('show_winners_table', $showWinners);
    updateSetting('show_gallery', $showGallery);
    updateSetting('show_certificates', $showCertificates);
    updateSetting('show_diplomas', $showDiplomas);
    
    $message = 'Настройки сохранены';
    $messageType = 'success';
}

$showParticipants = getSetting('show_participants_table', '0');
$showWinners = getSetting('show_winners_table', '0');
$showGallery = getSetting('show_gallery', '1');
$showCertificates = getSetting('show_certificates', '1');
$showDiplomas = getSetting('show_diplomas', '1');

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM applications");
    $totalParticipants = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM applications WHERE is_published = 1");
    $publishedWorks = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $totalParticipants = 0;
    $publishedWorks = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .setting-control {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            margin: 0;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cccccc;
            transition: 0.4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        input:checked + .toggle-slider {
            background-color: #FF6B00;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        input:focus + .toggle-slider {
            box-shadow: 0 0 1px #FF6B00;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .btn-save {
            background: #FF6B00;
            color: #FFFFFF;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn-save:hover {
            background: #E55E00;
        }
        .db-check {
            background: #FFF3CD;
            border-left: 4px solid #FFC107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .db-check p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
        }
        .db-check code {
            background: #FFFFFF;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Настройки сайта</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalParticipants; ?></div>
                    <div class="stat-label">Всего участников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $publishedWorks; ?></div>
                    <div class="stat-label">Опубликованных работ</div>
                </div>
            </div>
            
            <form method="POST">
                <div class="settings-card">
                    <h2>📋 Публичные таблицы</h2>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <h3>Таблица участников</h3>
                            <p>Показать сводную таблицу всех участников (без изображений работ)</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_participants_table" value="1" <?php echo $showParticipants == '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <h3>Таблица победителей</h3>
                            <p>Показать таблицу победителей (только опубликованные работы)</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_winners_table" value="1" <?php echo $showWinners == '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h2>🖼️ Настройки галереи</h2>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <h3>Показывать галерею</h3>
                            <p>Полностью скрыть или показать раздел галереи на сайте</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_gallery" value="1" <?php echo $showGallery == '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h2>📜 Сертификаты и дипломы</h2>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <h3>Показывать сертификаты</h3>
                            <p>Разрешить скачивание сертификатов участника на странице участников</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_certificates" value="1" <?php echo $showCertificates == '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-label">
                            <h3>Показывать дипломы</h3>
                            <p>Разрешить скачивание дипломов победителей на странице победителей</p>
                        </div>
                        <div class="setting-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="show_diplomas" value="1" <?php echo $showDiplomas == '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">Сохранить настройки</button>
            </form>
        </main>
    </div>
</body>
</html>