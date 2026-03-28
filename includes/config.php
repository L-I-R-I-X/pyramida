<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pyramida/');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/pyramida/');
}

if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); 
}
if (!defined('UPLOAD_MIN_SIZE')) {
    define('UPLOAD_MIN_SIZE', 0);  // Без нижнего лимита
}
if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
}
if (!defined('ALLOWED_MIME_TYPES')) {
    define('ALLOWED_MIME_TYPES', ['image/jpeg']);
}

$basePath = __DIR__ . '/..';
if (!defined('UPLOAD_DIR_ORIGINALS')) {
    define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
}
if (!defined('UPLOAD_DIR_GALLERY')) {
    define('UPLOAD_DIR_GALLERY', $basePath . '/uploads/gallery/');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $basePath . '/logs/php_errors.log');

// Настройки загрузки файлов (должны быть также в php.ini сервера)
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

if (!is_dir($basePath . '/logs')) {
    mkdir($basePath . '/logs', 0755, true);
}
?>