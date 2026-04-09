<?php
// Тестовый файл для проверки настроек загрузки файлов
require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>Проверка настроек загрузки файлов</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Проверка настроек загрузки файлов</h1>
        
        <table>
            <tr>
                <th>Параметр</th>
                <th>Значение</th>
                <th>Статус</th>
            </tr>
            <tr>
                <td><strong>upload_max_filesize</strong></td>
                <td>" . ini_get('upload_max_filesize') . "</td>
                <td>" . (filesizeToBytes(ini_get('upload_max_filesize')) >= UPLOAD_MAX_SIZE ? "<span class='ok'>✓ OK</span>" : "<span class='error'>✗ Мало (нужно минимум 10M)</span>") . "</td>
            </tr>
            <tr>
                <td><strong>post_max_size</strong></td>
                <td>" . ini_get('post_max_size') . "</td>
                <td>" . (filesizeToBytes(ini_get('post_max_size')) >= (UPLOAD_MAX_SIZE + 2 * 1024 * 1024) ? "<span class='ok'>✓ OK</span>" : "<span class='error'>✗ Мало (нужно минимум 12M)</span>") . "</td>
            </tr>
            <tr>
                <td><strong>max_file_uploads</strong></td>
                <td>" . ini_get('max_file_uploads') . "</td>
                <td>" . (ini_get('max_file_uploads') >= 5 ? "<span class='ok'>✓ OK</span>" : "<span class='warning'>⚠ Рекомендуется увеличить</span>") . "</td>
            </tr>
            <tr>
                <td><strong>memory_limit</strong></td>
                <td>" . ini_get('memory_limit') . "</td>
                <td>" . (filesizeToBytes(ini_get('memory_limit')) >= 128 * 1024 * 1024 ? "<span class='ok'>✓ OK</span>" : "<span class='warning'>⚠ Может быть недостаточно для обработки изображений</span>") . "</td>
            </tr>
        </table>
        
        <div class='info'>
            <h3>📋 Коды ошибок загрузки файлов:</h3>
            <ul>
                <li><strong>0 (UPLOAD_ERR_OK)</strong> — Файл загружен успешно</li>
                <li><strong>1 (UPLOAD_ERR_INI_SIZE)</strong> — Размер файла превышает upload_max_filesize в php.ini</li>
                <li><strong>2 (UPLOAD_ERR_FORM_SIZE)</strong> — Размер файла превышает MAX_FILE_SIZE в форме</li>
                <li><strong>3 (UPLOAD_ERR_PARTIAL)</strong> — Файл загружен частично</li>
                <li><strong>4 (UPLOAD_ERR_NO_FILE)</strong> — Файл не был загружен</li>
                <li><strong>6 (UPLOAD_ERR_NO_TMP_DIR)</strong> — Отсутствует временная папка</li>
                <li><strong>7 (UPLOAD_ERR_CANT_WRITE)</strong> — Не удалось записать файл на диск</li>
                <li><strong>8 (UPLOAD_ERR_EXTENSION)</strong> — Загрузка прервана расширением PHP</li>
            </ul>
        </div>
        
        <div class='info'>
            <h3>🔧 Как исправить:</h3>
            <p><strong>Вариант 1:</strong> Измените <code>php.ini</code> на сервере:</p>
            <pre>upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 10
memory_limit = 256M</pre>
            
            <p><strong>Вариант 2:</strong> Добавьте в <code>.htaccess</code> (если работает Apache):</p>
            <pre>php_value upload_max_filesize 10M
php_value post_max_size 12M
php_value max_file_uploads 10
php_value memory_limit 256M</pre>
            
            <p><strong>Вариант 3:</strong> Создайте файл <code>.user.ini</code> в корне сайта:</p>
            <pre>upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 10
memory_limit = 256M</pre>
            
            <p>После изменений <strong>перезапустите веб-сервер</strong> или подождите несколько минут.</p>
        </div>
        
        <h2>📁 Проверка директорий</h2>
        <table>
            <tr>
                <th>Директория</th>
                <th>Существует</th>
                <th>Доступна для записи</th>
            </tr>
            <tr>
                <td>" . UPLOAD_DIR_ORIGINALS . "</td>
                <td>" . (is_dir(UPLOAD_DIR_ORIGINALS) ? "<span class='ok'>✓ Да</span>" : "<span class='error'>✗ Нет</span>") . "</td>
                <td>" . (is_writable(UPLOAD_DIR_ORIGINALS) ? "<span class='ok'>✓ Да</span>" : "<span class='error'>✗ Нет</span>") . "</td>
            </tr>
            <tr>
                <td>" . UPLOAD_DIR_GALLERY . "</td>
                <td>" . (is_dir(UPLOAD_DIR_GALLERY) ? "<span class='ok'>✓ Да</span>" : "<span class='error'>✗ Нет</span>") . "</td>
                <td>" . (is_writable(UPLOAD_DIR_GALLERY) ? "<span class='ok'>✓ Да</span>" : "<span class='error'>✗ Нет</span>") . "</td>
            </tr>
        </table>
        
        <p style='margin-top: 30px;'>
            <a href='register.php' style='display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>← Вернуться к форме регистрации</a>
        </p>
    </div>
</body>
</html>";

function filesizeToBytes($sizeStr) {
    $units = ['K' => 1024, 'M' => 1024*1024, 'G' => 1024*1024*1024];
    $number = floatval($sizeStr);
    $unit = strtoupper(substr($sizeStr, -1));
    return isset($units[$unit]) ? $number * $units[$unit] : $number;
}
?>
