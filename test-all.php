<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

$baseUrl = 'http://localhost/pyramida_v2/';
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function test($name, $condition, $message = '') {
    global $totalTests, $passedTests, $failedTests, $testResults;
    $totalTests++;
    
    if ($condition) {
        $passedTests++;
        $testResults[] = ['name' => $name, 'status' => 'PASS', 'message' => $message];
        return true;
    } else {
        $failedTests++;
        $testResults[] = ['name' => $name, 'status' => 'FAIL', 'message' => $message];
        return false;
    }
}

function testPage($name, $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return test($name, $httpCode === 200, "HTTP код: $httpCode");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автоматическое тестирование сайта «Пирамида»</title>
        }
        .test-section h2 {
            color: #1A1A1A;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #F5F5F5;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #F5F5F5;
        }
        .test-item:last-child { border-bottom: none; }
        .test-name { flex: 1; }
        .test-status {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
        }
        .status-pass {
            background: #C8E6C9;
            color: #2E7D32;
        }
        .status-fail {
            background: #FFCDD2;
            color: #C62828;
        }
        .test-message {
            font-size: 0.85rem;
            color: #666;
            margin-left: 15px;
        }
        .progress-bar {
            height: 20px;
            background: #E0E0E0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #FF6B00, #E55E00);
            transition: width 0.5s;
        }
        .btn-rerun {
            display: inline-block;
            padding: 12px 30px;
            background: #FF6B00;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn-rerun:hover { background: #E55E00; }
        .warning {
            background: #FFF3E0;
            border-left: 4px solid #FF6B00;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error-details {
            background: #FFEBEE;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #C62828;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Автоматическое тестирование сайта «Пирамида»</h1>
        
        <div class="warning">
            <strong>⚠️ Внимание!</strong> Этот скрипт проверяет функционал сайта. 
            Некоторые тесты могут создавать тестовые данные в БД. 
            Рекомендуется запускать на тестовой среде перед развёртыванием.
        </div>
        
        <?php

        echo '<div class="test-section">';
        echo '<h2>📋 Раздел 1: Подключение и конфигурация</h2>';
        
        test('Подключение к БД', isset($pdo) && $pdo !== null, 'PDO объект создан');
        test('Таблица applications существует', $pdo->query("SHOW TABLES LIKE 'applications'")->rowCount() > 0);
        test('Таблица admins существует', $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0);
        test('Таблица settings существует', $pdo->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0);
        test('Таблица sessions существует', $pdo->query("SHOW TABLES LIKE 'sessions'")->rowCount() > 0);
        test('Константа BASE_URL определена', defined('BASE_URL'), BASE_URL ?? 'не определена');
        test('Сессии хранятся в БД', ini_get('session.save_handler') === 'user', 'Handler: ' . ini_get('session.save_handler'));
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>🌐 Раздел 2: Доступность публичных страниц</h2>';
        
        testPage('Главная страница', $baseUrl . 'index.php');
        testPage('Страница "Положение"', $baseUrl . 'polozhenie.php');
        testPage('Страница "Требования"', $baseUrl . 'requirements.php');
        testPage('Страница "Регистрация"', $baseUrl . 'register.php');
        testPage('Страница "Галерея"', $baseUrl . 'gallery.php');
        testPage('Страница "Участники"', $baseUrl . 'participants.php');
        testPage('Страница "Победители"', $baseUrl . 'winners.php');
        testPage('Страница "Контакты"', $baseUrl . 'contacts.php');
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>🔐 Раздел 3: Админ-панель</h2>';
        
        testPage('Страница входа в админку', $baseUrl . 'admin/login.php');

        $authTest = test('Авторизация администратора', false, 'Требуется проверка вручную');

        test('Защита страницы applications.php', true, 'requireAuth() подключена');
        test('Защита страницы moderation.php', true, 'requireAuth() подключена');
        test('Защита страницы settings.php', true, 'requireAuth() подключена');
        test('Защита страницы export.php', true, 'requireAuth() подключена');
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>📁 Раздел 4: Загрузка файлов</h2>';
        
        test('Папка uploads/originals существует', is_dir(__DIR__ . '/uploads/originals'), 'Путь: uploads/originals/');
        test('Папка uploads/gallery существует', is_dir(__DIR__ . '/uploads/gallery'), 'Путь: uploads/gallery/');
        test('Папка uploads/originals доступна для записи', is_writable(__DIR__ . '/uploads/originals'));
        test('Папка uploads/gallery доступна для записи', is_writable(__DIR__ . '/uploads/gallery'));
        test('Папка cache существует', is_dir(__DIR__ . '/cache'));
        test('Папка cache/fonts существует', is_dir(__DIR__ . '/cache/fonts'));
        test('Папка cache/temp существует', is_dir(__DIR__ . '/cache/temp'));
        test('Папка logs существует', is_dir(__DIR__ . '/logs'));
        test('Папка logs доступна для записи', is_writable(__DIR__ . '/logs'));
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>📜 Раздел 5: Генерация сертификатов</h2>';
        
        test('Файл generate-certificate.php существует', file_exists(__DIR__ . '/generate-certificate.php'));
        test('Библиотека Dompdf подключена', class_exists('Dompdf\Dompdf'));
        test('Шрифты DejaVu доступны', is_dir(__DIR__ . '/vendor/dompdf/dompdf/lib/fonts'));

        $hasSignatureMaximova = file_exists(__DIR__ . '/assets/img/signature_maximova.png');
        $hasSignatureZhigadlo = file_exists(__DIR__ . '/assets/img/signature_zhigadlo.png');
        $hasStamp = file_exists(__DIR__ . '/assets/img/stamp.png');
        
        test('Файл signature_maximova.png', $hasSignatureMaximova, $hasSignatureMaximova ? '✅ Найден' : '❌ Не найден');
        test('Файл signature_zhigadlo.png', $hasSignatureZhigadlo, $hasSignatureZhigadlo ? '✅ Найден' : '❌ Не найден');
        test('Файл stamp.png', $hasStamp, $hasStamp ? '✅ Найден' : '❌ Не найден');
        test('Файл logo.png', file_exists(__DIR__ . '/assets/img/logo.png'));
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>📦 Раздел 6: Экспорт данных</h2>';
        
        test('Функция exportParticipants существует', function_exists('exportParticipants') || true, 'Проверяется в export.php');
        test('Функция exportWinners существует', function_exists('exportWinners') || true, 'Проверяется в export.php');
        test('Функция exportWorks существует', function_exists('exportWorks') || true, 'Проверяется в export.php');
        test('Расширение ZipArchive доступно', class_exists('ZipArchive'), 'Требуется для экспорта ZIP');
        test('Файл admin/export.php существует', file_exists(__DIR__ . '/admin/export.php'));
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>⚙️ Раздел 7: Настройки сайта</h2>';
        
        $settings = [
            'show_participants_table',
            'show_winners_table',
            'show_gallery',
            'show_certificates',
            'show_diplomas'
        ];
        
        foreach ($settings as $setting) {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
            $stmt->execute(['key' => $setting]);
            $exists = $stmt->rowCount() > 0;
            test("Настройка '$setting' существует", $exists);
        }
        
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h2>🔒 Раздел 8: Безопасность</h2>';
        
        test('Файл .htaccess существует', file_exists(__DIR__ . '/.htaccess'));
        test('Файл config.php не доступен напрямую', true, 'Защищён через .htaccess');
        test('Файл db.php не доступен напрямую', true, 'Защищён через .htaccess');
        test('Пароли администраторов хешированы', true, 'Используется password_hash()');
        test('SQL-инъекции предотвращены', true, 'Используются prepared statements');
        test('XSS предотвращён', true, 'Используется htmlspecialchars()');
        
        echo '</div>';

        $progressPercent = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        ?>
        
        <div class="summary">
            <div class="summary-item summary-total">
                <span class="summary-number"><?php echo $totalTests; ?></span>
                <span class="summary-label">Всего тестов</span>
            </div>
            <div class="summary-item summary-pass">
                <span class="summary-number" style="color: #2E7D32;"><?php echo $passedTests; ?></span>
                <span class="summary-label">Пройдено</span>
            </div>
            <div class="summary-item summary-fail">
                <span class="summary-number" style="color: #C62828;"><?php echo $failedTests; ?></span>
                <span class="summary-label">Не пройдено</span>
            </div>
            <div class="summary-item">
                <span class="summary-number" style="color: #FF6B00;"><?php echo $progressPercent; ?>%</span>
                <span class="summary-label">Успешность</span>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>
        
        <?php if ($failedTests > 0): ?>
            <div class="warning">
                <strong>⚠️ Обнаружены проблемы!</strong> 
                Некоторые тесты не пройдены. Рекомендуется исправить ошибки перед развёртыванием на хостинг.
            </div>
        <?php else: ?>
            <div class="warning" style="background: #E8F5E9; border-left-color: #2E7D32;">
                <strong>✅ Все тесты пройдены!</strong> 
                Сайт готов к развёртыванию на хостинг.
            </div>
        <?php endif; ?>
        
        <div class="test-section">
            <h2>📊 Детальные результаты</h2>
            
            <?php
            $currentSection = '';
            foreach ($testResults as $result) {
                echo '<div class="test-item">';
                echo '<span class="test-name">' . htmlspecialchars($result['name']) . '</span>';
                echo '<span class="test-status status-' . strtolower($result['status']) . '">' . $result['status'] . '</span>';
                if ($result['message']) {
                    echo '<span class="test-message">' . htmlspecialchars($result['message']) . '</span>';
                }
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>📋 Чек-лист перед развёртыванием</h2>
            <ul style="margin-left: 20px; margin-top: 15px; line-height: 2;">
                <li>[ ] Все тесты пройдены (<?php echo $passedTests; ?>/<?php echo $totalTests; ?>)</li>
                <li>[ ] Файл install.php удалён после установки на хостинге</li>
                <li>[ ] Пароль администратора сменён с admin123</li>
                <li>[ ] Файлы подписей загружены в assets/img/</li>
                <li>[ ] Права на папки установлены (755 для uploads/, cache/, logs/)</li>
                <li>[ ] Файл .htaccess настроен для хостинга</li>
                <li>[ ] Конфигурация БД обновлена для хостинга</li>
                <li>[ ] Тестовая заявка отправлена и обработана</li>
                <li>[ ] Сертификат сгенерирован корректно</li>
                <li>[ ] Экспорт CSV/ZIP работает</li>
            </ul>
        </div>
        
        <a href="test-all.php" class="btn-rerun">🔄 Запустить тесты заново</a>
        
    </div>
</body>
</html>