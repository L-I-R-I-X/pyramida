<?php

// ============================================================================
// КОНФИГУРАЦИЯ ПУТЕЙ
// ============================================================================

// Базовый URL сайта (измените на ваш домен при деплое)
define('BASE_URL', 'http://localhost/pyramida/');
define('SITE_URL', BASE_URL);

// Базовый путь проекта (корень сайта)
$basePath = __DIR__ . '/..';

// Пути для загрузки файлов
define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
define('UPLOAD_DIR_GALLERY', $basePath . '/uploads/gallery/');

// Пути для кэша и временных файлов
define('CACHE_DIR', $basePath . '/cache/');
define('CACHE_SESSIONS_DIR', CACHE_DIR . 'sessions/');
define('CACHE_FONTS_DIR', CACHE_DIR . 'fonts/');
define('CACHE_TEMP_DIR', CACHE_DIR . 'temp/');
define('CACHE_CERTIFICATES_DIR', CACHE_DIR . 'certificates/');

// Путь для логов
define('LOGS_DIR', $basePath . '/logs/');
define('ERROR_LOG_FILE', LOGS_DIR . 'php_errors.log');

// ============================================================================
// НАСТРОЙКИ ПРИЛОЖЕНИЯ
// ============================================================================

define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); 
define('UPLOAD_MIN_SIZE', 0);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
define('ALLOWED_MIME_TYPES', ['image/jpeg']);

// ============================================================================
// НАСТРОЙКИ PHP И ЛОГИРОВАНИЕ
// ============================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);

// Настройки загрузки файлов
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

// Создаем необходимые директории
$requiredDirs = [
    LOGS_DIR,
    UPLOAD_DIR_ORIGINALS,
    UPLOAD_DIR_GALLERY,
    CACHE_DIR,
    CACHE_SESSIONS_DIR,
    CACHE_FONTS_DIR,
    CACHE_TEMP_DIR,
    CACHE_CERTIFICATES_DIR,
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================================================
// НАСТРОЙКИ СЕССИЙ
// ============================================================================

ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

if (!defined('SESSION_INITIALIZED')) {
    define('SESSION_INITIALIZED', false);
}

?>
