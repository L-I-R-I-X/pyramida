<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

function exportParticipants($pdo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="participants_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');

    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        '№',
        'ФИО',
        'Учебное заведение',  
        'Курс',
        'Номинация',
        'Раздел',
        'Название работы',
        'Email',
        'Телефон',
        'Статус',
        'Дата заявки'
    ], ';');

    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, work_title, email, phone, is_published, created_at 
        FROM applications 
        ORDER BY created_at DESC
    ");
    $applications = $stmt->fetchAll();
    
    $nominationNames = [
        'arch_composition' => 'Архитектурная композиция',
        'art_graphics' => 'Художественно-проектная графика',
        'nature_drawing' => 'Рисунок с натуры',
        'photography' => 'Фотография'
    ];
    
    $rank = 1;
    foreach ($applications as $app) {
        fputcsv($output, [
            $rank++,
            $app['fio'],
            $app['educational_institution'],  
            $app['course'],
            $nominationNames[$app['nomination']] ?? $app['nomination'],
            $app['section'],
            $app['work_title'],
            $app['email'],
            $app['phone'],
            $app['is_published'] ? 'Опубликовано' : 'На модерации',
            date('d.m.Y H:i', strtotime($app['created_at']))
        ], ';');
    }
    
    fclose($output);
    exit;
}

function exportWinners($pdo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="winners_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');

    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        '№',
        'ФИО',
        'Учебное заведение',  
        'Курс',
        'Номинация',
        'Раздел',
        'Название работы',
        'Email',
        'Телефон',
        'Оценка жюри',
        'Дата заявки'
    ], ';');

    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, work_title, email, phone, jury_score, created_at 
        FROM applications 
        WHERE is_published = 1 
        ORDER BY nomination, section, created_at DESC
    ");
    $applications = $stmt->fetchAll();
    
    $nominationNames = [
        'arch_composition' => 'Архитектурная композиция',
        'art_graphics' => 'Художественно-проектная графика',
        'nature_drawing' => 'Рисунок с натуры',
        'photography' => 'Фотография'
    ];
    
    $currentNomination = '';
    $currentSection = '';
    $rank = 1;
    
    foreach ($applications as $app) {
        
        $nominationKey = $app['nomination'] . '_' . $app['section'];
        if ($nominationKey !== $currentNomination . '_' . $currentSection) {
            $rank = 1;
            $currentNomination = $app['nomination'];
            $currentSection = $app['section'];
        }
        
        fputcsv($output, [
            $rank++,
            $app['fio'],
            $app['educational_institution'],  
            $app['course'],
            $nominationNames[$app['nomination']] ?? $app['nomination'],
            $app['section'],
            $app['work_title'],
            $app['email'],
            $app['phone'],
            $app['jury_score'] !== null ? $app['jury_score'] . '/10' : '—',
            date('d.m.Y H:i', strtotime($app['created_at']))
        ], ';');
    }
    
    fclose($output);
    exit;
}

function exportWorks($pdo, $type = 'all') {
    if (!class_exists('ZipArchive')) {
        die('Ошибка: ZipArchive не доступен на этом сервере');
    }
    
    $zip = new ZipArchive();
    $filename = $type === 'all' ? 'works_all_' : 'works_winners_';
    $filename .= date('Y-m-d_H-i-s') . '.zip';
    
    if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
        die('Ошибка: не удалось создать ZIP-архив');
    }
    
    if ($type === 'all') {
        
        $stmt = $pdo->query("
            SELECT id, fio, educational_institution, nomination, section, work_title, work_file 
            FROM applications 
            ORDER BY nomination, section, created_at DESC
        ");
    } else {
        
        $stmt = $pdo->query("
            SELECT id, fio, educational_institution, nomination, section, work_title, work_file 
            FROM applications 
            WHERE is_published = 1 
            ORDER BY nomination, section, created_at DESC
        ");
    }
    
    $applications = $stmt->fetchAll();
    
    $nominationNames = [
        'arch_composition' => 'Архитектурная_композиция',
        'art_graphics' => 'Художественно-проектная_графика',
        'nature_drawing' => 'Рисунок_с_натуры',
        'photography' => 'Фотография'
    ];
    
    $manifest = "СПИСОК РАБОТ\n";
    $manifest .= "================\n\n";
    
    $currentNomination = '';
    $currentSection = '';
    $rank = 1;
    
    foreach ($applications as $app) {
        $originalPath = __DIR__ . '/../uploads/originals/' . $app['work_file'];
        
        if (file_exists($originalPath)) {
            $nomination = $nominationNames[$app['nomination']] ?? $app['nomination'];
            $section = $app['section'] ?? 'Без_раздела';
            $fio = preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $app['fio']);
            $workTitle = preg_replace('/[^a-zA-Zа-яА-Я0-9]/u', '_', $app['work_title'] ?? 'Без_названия');
            
            $zipName = "{$nomination}/{$section}/{$fio}_{$workTitle}.jpg";
            
            $zip->addFile($originalPath, $zipName);
            
            $nominationKey = $app['nomination'] . '_' . $app['section'];
            if ($nominationKey !== $currentNomination . '_' . $currentSection) {
                $rank = 1;
                $currentNomination = $app['nomination'];
                $currentSection = $app['section'];
            }

            $manifest .= "{$rank}. {$app['fio']} | {$app['educational_institution']} | {$app['nomination']} | {$app['section']}\n";
            $manifest .= "   Файл: {$zipName}\n";
            $manifest .= "   Название работы: {$app['work_title']}\n\n";
            
            $rank++;
        }
    }
    
    $zip->addFromString('MANIFEST.txt', $manifest);
    
    $zip->close();
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filename));
    
    readfile($filename);
    
    unlink($filename);
    exit;
}

$exportType = $_GET['type'] ?? '';

if ($exportType === 'participants') {
    exportParticipants($pdo);
} elseif ($exportType === 'winners') {
    exportWinners($pdo);
} elseif ($exportType === 'works_all') {
    exportWorks($pdo, 'all');
} elseif ($exportType === 'works_published') {
    exportWorks($pdo, 'published');
}

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
    <title>Экспорт данных — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
        .warning-box p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Экспорт данных</h1>
            </div>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalParticipants; ?></div>
                    <div class="stat-label">Всего участников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $publishedWorks; ?></div>
                    <div class="stat-label">Опубликованных работ</div>
                </div>
            </div>
            
            <div class="export-section">
                <h2>📋 Экспорт таблиц участников</h2>
                <div class="export-cards">
                    <div class="export-card">
                        <h3>Все участники</h3>
                        <p>Полная выгрузка всех заявок в CSV-файл</p>
                        <ul>
                            <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                            <li>ФИО, учебное заведение, курс</li>
                            <li>Номинация и раздел</li>
                            <li>Название работы</li>
                            <li>Email, телефон</li>
                            <li>Статус публикации</li>
                            <li>Дата заявки</li>
                        </ul>
                        <a href="?type=participants" class="export-btn">Скачать CSV</a>
                        <div class="export-info">
                            <p>📁 Файл откроется в Excel автоматически</p>
                        </div>
                    </div>
                    
                    <div class="export-card">
                        <h3>🏆 Победители</h3>
                        <p>Выгрузка только опубликованных работ</p>
                        <ul>
                            <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                            <li>ФИО, учебное заведение, курс</li>
                            <li>Номинация и раздел</li>
                            <li>Название работы</li>
                            <li>Email, телефон</li>
                            <li>Оценка жюри</li>
                            <li>Дата заявки</li>
                        </ul>
                        <a href="?type=winners" class="export-btn">Скачать CSV</a>
                        <div class="export-info">
                            <p>📁 Файл откроется в Excel автоматически</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="export-section">
                <h2>📦 Экспорт работ (ZIP-архивы)</h2>
                <div class="export-cards">
                    <div class="export-card">
                        <h3>Все работы</h3>
                        <p>Архив со всеми работами участников (оригиналы)</p>
                        <ul>
                            <li>Структура: Номинация/Раздел/Файл.jpg</li>
                            <li>Имя файла: ФИО_НазваниеРаботы.jpg</li>
                            <li>Включает MANIFEST.txt со списком</li>
                            <li>Оригинальное качество</li>
                        </ul>
                        <a href="?type=works_all" class="export-btn secondary">Скачать ZIP</a>
                        <div class="warning-box">
                            <p>⚠️ Размер архива может быть большим (до нескольких ГБ)</p>
                        </div>
                    </div>
                    
                    <div class="export-card">
                        <h3>🏆 Работы победителей</h3>
                        <p>Архив только с опубликованными работами</p>
                        <ul>
                            <li>Структура: Номинация/Раздел/Файл.jpg</li>
                            <li>Имя файла: ФИО_НазваниеРаботы.jpg</li>
                            <li>Включает MANIFEST.txt со списком</li>
                            <li>Оригинальное качество</li>
                        </ul>
                        <a href="?type=works_published" class="export-btn secondary">Скачать ZIP</a>
                        <div class="export-info">
                            <p>📁 Рекомендуется для передачи жюри или публикации</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>