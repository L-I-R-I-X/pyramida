<?php
// ШАБЛОН конфигурации — скопируйте в config.php и заполните свои данные

// Базовый URL сайта (с слэшем в конце!)
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pyramida/');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/pyramida/');
}

// Настройки загрузки файлов
if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10 МБ
}
if (!defined('UPLOAD_MIN_SIZE')) {
    define('UPLOAD_MIN_SIZE', 1 * 1024 * 1024);  // 1 МБ
}
if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
}
if (!defined('ALLOWED_MIME_TYPES')) {
    define('ALLOWED_MIME_TYPES', ['image/jpeg']);
}

// Пути к файлам
$basePath = __DIR__ . '/..';
if (!defined('UPLOAD_DIR_ORIGINALS')) {
    define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
}
if (!defined('UPLOAD_DIR_GALLERY')) {
    define('UPLOAD_DIR_GALLERY', $basePath . '/uploads/gallery/');
}

// Отладка (в продакшене: error_reporting(0))
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $basePath . '/logs/php_errors.log');

// Создаём папку для логов, если нет
if (!is_dir($basePath . '/logs')) {
    mkdir($basePath . '/logs', 0755, true);
}
?>