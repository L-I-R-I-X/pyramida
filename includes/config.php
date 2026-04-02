<?php

// ============================================================================
// КОНФИГУРАЦИЯ ПУТЕЙ (можно изменять для разных окружений)
// ============================================================================

// Базовый путь проекта (корень сайта)
$basePath = __DIR__ . '/..';

// Пути для загрузки файлов (должны быть доступны на запись www-data)
if (!defined('UPLOAD_DIR_ORIGINALS')) {
    define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
}
if (!defined('UPLOAD_DIR_GALLERY')) {
    define('UPLOAD_DIR_GALLERY', $basePath . '/uploads/gallery/');
}

// Пути для кэша и временных файлов
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', $basePath . '/cache/');
}
if (!defined('CACHE_SESSIONS_DIR')) {
    define('CACHE_SESSIONS_DIR', CACHE_DIR . 'sessions/');
}
if (!defined('CACHE_FONTS_DIR')) {
    define('CACHE_FONTS_DIR', CACHE_DIR . 'fonts/');
}
if (!defined('CACHE_TEMP_DIR')) {
    define('CACHE_TEMP_DIR', CACHE_DIR . 'temp/');
}
if (!defined('CACHE_CERTIFICATES_DIR')) {
    define('CACHE_CERTIFICATES_DIR', CACHE_DIR . 'certificates/');
}

// Путь для логов
if (!defined('LOGS_DIR')) {
    define('LOGS_DIR', $basePath . '/logs/');
}
if (!defined('ERROR_LOG_FILE')) {
    define('ERROR_LOG_FILE', LOGS_DIR . 'php_errors.log');
}

// ============================================================================
// НАСТРОЙКИ ПРИЛОЖЕНИЯ
// ============================================================================

if (!defined('BASE_URL')) {
    // Автоопределение BASE_URL если не задано
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePathUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    define('BASE_URL', getenv('BASE_URL') ?: ($protocol . '://' . $host . $basePathUrl . '/'));
}

if (!defined('SITE_URL')) {
    define('SITE_URL', getenv('SITE_URL') ?: BASE_URL);
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

// ============================================================================
// НАСТРОЙКИ PHP И ЛОГИРОВАНИЕ
// ============================================================================

// Окружение: 'local' или 'production' (можно переопределить через getenv)
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

// Режим отладки: включён только в local окружении
$debugMode = APP_ENV === 'local';

error_reporting(E_ALL);
ini_set('display_errors', $debugMode ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_FILE);

// Настройки загрузки файлов (должны быть также в php.ini сервера)
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

// Создаем необходимые директории при инициализации
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

// Настройка сессий для хранения в БД
// Будет активировано после подключения к БД в db.php
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

?>