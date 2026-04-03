<?php

define('BASE_URL', 'http://localhost/pyramida/');
define('SITE_URL', BASE_URL);

$basePath = __DIR__ . '/..';

define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
define('UPLOAD_DIR_GALLERY', $basePath . '/uploads/gallery/');

define('CACHE_DIR', $basePath . '/cache/');
define('CACHE_CERTIFICATES_DIR', CACHE_DIR . 'certificates/');

define('LOGS_DIR', $basePath . '/logs/');

define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);
define('UPLOAD_MIN_SIZE', 1 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg']);
define('ALLOWED_MIME_TYPES', ['image/jpeg']);

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_file_uploads', 5);

?>
