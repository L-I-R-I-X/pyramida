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
define('CACHE_CERTIFICATES_DIR', CACHE_DIR . 'certificates/');

// Путь для логов
define('LOGS_DIR', $basePath . '/logs/');

// ============================================================================
// НАСТРОЙКИ ПРИЛОЖЕНИЯ
// ============================================================================

define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); 
define('UPLOAD_MIN_SIZE', 0);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
define('ALLOWED_MIME_TYPES', ['image/jpeg']);

// Настройки загрузки файлов
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

?>
