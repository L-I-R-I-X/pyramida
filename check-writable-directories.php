<?php
/**
 * Скрипт проверки прав на запись в директории проекта
 * Использует те же функции для записи, которые используются в основных скриптах
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Директории для проверки
$directoriesToCheck = [
    'UPLOAD_DIR_ORIGINALS' => UPLOAD_DIR_ORIGINALS,
    'UPLOAD_DIR_GALLERY' => UPLOAD_DIR_GALLERY,
    'CACHE_DIR' => CACHE_DIR,
    'CACHE_CERTIFICATES_DIR' => CACHE_CERTIFICATES_DIR,
    'LOGS_DIR' => LOGS_DIR,
];

$results = [];
$overallSuccess = true;

/**
 * Функция проверки возможности записи в директорию
 * Использует подход из upload.php (создание поддиректорий и запись файлов)
 */
function checkDirectoryWrite($dirPath, $dirName) {
    $result = [
        'name' => $dirName,
        'path' => $dirPath,
        'exists' => false,
        'writable' => false,
        'canCreateSubdir' => false,
        'canWriteFile' => false,
        'errors' => []
    ];
    
    // Проверяем существование директории
    if (!is_dir($dirPath)) {
        $result['exists'] = false;
        $result['errors'][] = 'Директория не существует';
        
        // Пытаемся создать директорию (как в upload.php)
        if (@mkdir($dirPath, 0755, true)) {
            $result['exists'] = true;
            $result['errors'] = []; // Очищаем ошибку, если удалось создать
        } else {
            $result['errors'][] = 'Не удалось создать директорию';
            return $result;
        }
    } else {
        $result['exists'] = true;
    }
    
    // Проверяем возможность записи в существующую директорию
    if (is_writable($dirPath)) {
        $result['writable'] = true;
    } else {
        $result['errors'][] = 'Директория недоступна для записи (is_writable = false)';
    }
    
    // Тестируем создание поддиректории (как в upload.php: mkdir с рекурсией)
    $testSubdir = $dirPath . 'test_subdir_' . uniqid();
    if (@mkdir($testSubdir, 0755, true)) {
        $result['canCreateSubdir'] = true;
        @rmdir($testSubdir);
    } else {
        $result['errors'][] = 'Не удалось создать поддиректорию';
    }
    
    // Тестируем запись файла (используем подход из upload.php)
    $testFilename = generateUniqueFilename('txt');
    $testFilePath = $dirPath . $testFilename;
    $testContent = "Test file created by check-writable-directories.php\n" . date('Y-m-d H:i:s');
    
    // Пробуем записать файл (аналог move_uploaded_file из upload.php)
    if (@file_put_contents($testFilePath, $testContent) !== false) {
        $result['canWriteFile'] = true;
        
        // Проверяем чтение записанного файла
        $readContent = @file_get_contents($testFilePath);
        if ($readContent !== $testContent) {
            $result['errors'][] = 'Файл записан, но не читается корректно';
        }
        
        // Удаляем тестовый файл
        @unlink($testFilePath);
    } else {
        $result['errors'][] = 'Не удалось записать тестовый файл';
    }
    
    return $result;
}

// Проверяем все директории
foreach ($directoriesToCheck as $name => $path) {
    $results[$name] = checkDirectoryWrite($path, $name);
    if (!$results[$name]['canWriteFile']) {
        $overallSuccess = false;
    }
}

// Дополнительно тестируем функции из functions.php
$thumbnailTestResult = null;
if ($results['UPLOAD_DIR_ORIGINALS']['canWriteFile'] && $results['UPLOAD_DIR_GALLERY']['canWriteFile']) {
    // Создаем тестовое изображение и пробуем создать thumbnail (как в upload.php)
    $testImageWidth = 100;
    $testImageHeight = 100;
    $testImage = imagecreatetruecolor($testImageWidth, $testImageHeight);
    imagefill($testImage, 0, 0, imagecolorallocate($testImage, 255, 0, 0));
    
    $testSourcePath = UPLOAD_DIR_ORIGINALS . 'test_image_' . uniqid() . '.jpg';
    $testDestPath = UPLOAD_DIR_GALLERY . 'test_thumb_' . uniqid() . '.jpg';
    
    if (@imagejpeg($testImage, $testSourcePath, 85)) {
        imagedestroy($testImage);
        
        // Вызываем функцию createThumbnail из functions.php
        $thumbnailResult = createThumbnail($testSourcePath, $testDestPath, 80);
        
        $thumbnailTestResult = [
            'sourceCreated' => file_exists($testSourcePath),
            'thumbnailCreated' => $thumbnailResult && file_exists($testDestPath),
            'functionWorks' => $thumbnailResult
        ];
        
        // Очищаем тестовые файлы
        @unlink($testSourcePath);
        @unlink($testDestPath);
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка прав на запись в директории</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status-overall {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 18px;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .directory-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .directory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .directory-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .directory-path {
            font-size: 12px;
            color: #666;
            font-family: monospace;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .check-item {
            display: flex;
            align-items: center;
            margin: 8px 0;
            padding: 5px 0;
        }
        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .check-success {
            background-color: #d4edda;
            color: #155724;
        }
        .check-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .error-list {
            margin-top: 10px;
            padding-left: 30px;
        }
        .error-list li {
            color: #721c24;
            margin: 5px 0;
        }
        .test-result {
            margin-top: 15px;
            padding: 10px;
            background: #e7f3ff;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>🔍 Проверка прав на запись в директории проекта</h1>
    
    <div class="status-overall <?= $overallSuccess ? 'status-success' : 'status-error' ?>">
        <?= $overallSuccess ? '✓ Все директории доступны для записи' : '✗ Обнаружены проблемы с правами доступа' ?>
    </div>
    
    <?php foreach ($results as $constName => $result): ?>
    <div class="directory-card">
        <div class="directory-header">
            <span class="directory-name"><?= htmlspecialchars($constName) ?></span>
            <span class="directory-path"><?= htmlspecialchars($result['path']) ?></span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $result['exists'] ? 'check-success' : 'check-error' ?>">
                <?= $result['exists'] ? '✓' : '✗' ?>
            </div>
            <span>Директория существует: <?= $result['exists'] ? 'Да' : 'Нет (попытка создания: ' . ($result['writable'] ? 'успешна' : 'неудачна') . ')' ?></span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $result['writable'] ? 'check-success' : 'check-error' ?>">
                <?= $result['writable'] ? '✓' : '✗' ?>
            </div>
            <span>Директория доступна для записи (is_writable)</span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $result['canCreateSubdir'] ? 'check-success' : 'check-error' ?>">
                <?= $result['canCreateSubdir'] ? '✓' : '✗' ?>
            </div>
            <span>Возможно создание поддиректорий (mkdir с рекурсией)</span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $result['canWriteFile'] ? 'check-success' : 'check-error' ?>">
                <?= $result['canWriteFile'] ? '✓' : '✗' ?>
            </div>
            <span>Возможна запись файлов (file_put_contents)</span>
        </div>
        
        <?php if (!empty($result['errors'])): ?>
        <ul class="error-list">
            <?php foreach ($result['errors'] as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <?php if ($thumbnailTestResult): ?>
    <div class="directory-card">
        <div class="directory-header">
            <span class="directory-name">Тест функций обработки изображений</span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $thumbnailTestResult['sourceCreated'] ? 'check-success' : 'check-error' ?>">
                <?= $thumbnailTestResult['sourceCreated'] ? '✓' : '✗' ?>
            </div>
            <span>Создание тестового изображения</span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $thumbnailTestResult['functionWorks'] ? 'check-success' : 'check-error' ?>">
                <?= $thumbnailTestResult['functionWorks'] ? '✓' : '✗' ?>
            </div>
            <span>Функция createThumbnail() работает корректно</span>
        </div>
        
        <div class="check-item">
            <div class="check-icon <?= $thumbnailTestResult['thumbnailCreated'] ? 'check-success' : 'check-error' ?>">
                <?= $thumbnailTestResult['thumbnailCreated'] ? '✓' : '✗' ?>
            </div>
            <span>Создание thumbnail успешно</span>
        </div>
    </div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Константа</th>
                <th>Путь</th>
                <th>Существует</th>
                <th>Запись</th>
                <th>Поддиректории</th>
                <th>Файлы</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $constName => $result): ?>
            <tr>
                <td><code><?= htmlspecialchars($constName) ?></code></td>
                <td style="font-family: monospace; font-size: 11px;"><?= htmlspecialchars($result['path']) ?></td>
                <td><?= $result['exists'] ? '✓' : '✗' ?></td>
                <td><?= $result['writable'] ? '✓' : '✗' ?></td>
                <td><?= $result['canCreateSubdir'] ? '✓' : '✗' ?></td>
                <td><?= $result['canWriteFile'] ? '✓' : '✗' ?></td>
                <td><?= $result['canWriteFile'] ? '<span style="color: green;">OK</span>' : '<span style="color: red;">ERROR</span>' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <a href="<?= BASE_URL ?>" class="back-link">← На главную</a>
    
    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
        <strong>ℹ️ Информация:</strong>
        <p style="margin: 10px 0 0 0; font-size: 14px;">
            Этот скрипт использует те же методы для записи в директории, которые используются в основных скриптах проекта:
        </p>
        <ul style="margin: 10px 0 0 20px; font-size: 14px;">
            <li><code>mkdir()</code> с рекурсией — как в <code>upload.php</code> при создании UPLOAD_DIR_ORIGINALS и UPLOAD_DIR_GALLERY</li>
            <li><code>move_uploaded_file()</code> / <code>file_put_contents()</code> — для записи файлов</li>
            <li><code>generateUniqueFilename()</code> — функция из <code>functions.php</code> для генерации имен файлов</li>
            <li><code>createThumbnail()</code> — функция из <code>functions.php</code> для обработки изображений</li>
        </ul>
    </div>
</body>
</html>
