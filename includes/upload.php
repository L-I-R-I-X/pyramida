<?php
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die('Ошибка: Файл config.php не найден. Переименуйте config.example.php в config.php и настройте параметры подключения.');
}
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . 'register.php', 'Неверный метод запроса', 'error');
}

$fio = sanitizeInput($_POST['fio'] ?? '');

$educational_institution = sanitizeInput($_POST['educational_institution'] ?? '');
$course = sanitizeInput($_POST['course'] ?? '');
$nomination = sanitizeInput($_POST['nomination'] ?? '');
$section = sanitizeInput($_POST['section'] ?? '');
$work_title = sanitizeInput($_POST['work_title'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = sanitizeInput($_POST['phone'] ?? '');
$consent = isset($_POST['consent']);

$errors = [];

if (empty($fio)) {
    $errors[] = 'Укажите ФИО';
}

if (empty($educational_institution)) {
    $errors[] = 'Укажите учебное заведение';
}

if (empty($course)) {
    $errors[] = 'Укажите курс';
}

if (empty($nomination)) {
    $errors[] = 'Выберите номинацию';
}

if (empty($section)) {
    $errors[] = 'Выберите раздел';
}

if (empty($work_title)) {
    $errors[] = 'Укажите название работы';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Укажите корректный email';
}

if (!$consent) {
    $errors[] = 'Требуется согласие на обработку персональных данных';
}

if (!isset($_FILES['work']) || $_FILES['work']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Ошибка загрузки файла';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    redirect(SITE_URL . 'register.php', 'Исправьте ошибки в форме', 'error');
}

$file = $_FILES['work'];
$fileSize = $file['size'];
$fileType = $file['type'];
$fileTmpName = $file['tmp_name'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
    redirect(SITE_URL . 'register.php', 'Недопустимый формат файла', 'error');
}

if (!in_array($fileType, ALLOWED_MIME_TYPES)) {
    redirect(SITE_URL . 'register.php', 'Недопустимый тип файла', 'error');
}

if ($fileSize < UPLOAD_MIN_SIZE && UPLOAD_MIN_SIZE > 0) {
    redirect(SITE_URL . 'register.php', 'Файл слишком маленький (минимум ' . (UPLOAD_MIN_SIZE / 1024 / 1024) . ' Мб)', 'error');
}

if ($fileSize > UPLOAD_MAX_SIZE) {
    redirect(SITE_URL . 'register.php', 'Файл слишком большой (максимум 10 Мб)', 'error');
}

if (!validateFileSignature($fileTmpName)) {
    redirect(SITE_URL . 'register.php', 'Файл не является изображением', 'error');
}

if (!validateImageDimensions($fileTmpName)) {
    redirect(SITE_URL . 'register.php', 'Изображение слишком большое (макс. 5000×5000 px)', 'error');
}

if (!is_dir(UPLOAD_DIR_ORIGINALS)) {
    mkdir(UPLOAD_DIR_ORIGINALS, 0755, true);
}

if (!is_dir(UPLOAD_DIR_GALLERY)) {
    mkdir(UPLOAD_DIR_GALLERY, 0755, true);
}

$filename = generateUniqueFilename($fileExt);
$originalPath = UPLOAD_DIR_ORIGINALS . $filename;
$galleryPath = UPLOAD_DIR_GALLERY . $filename;

if (!move_uploaded_file($fileTmpName, $originalPath)) {
    redirect(SITE_URL . 'register.php', 'Ошибка сохранения файла', 'error');
}

if (!createThumbnail($originalPath, $galleryPath, 800)) {
    unlink($originalPath);
    redirect(SITE_URL . 'register.php', 'Ошибка обработки изображения', 'error');
}

try {
    
    $stmt = $pdo->prepare("
        INSERT INTO applications (fio, educational_institution, course, nomination, section, work_title, email, phone, work_file, created_at) 
        VALUES (:fio, :educational_institution, :course, :nomination, :section, :work_title, :email, :phone, :work, NOW())
    ");
    
    $stmt->execute([
        'fio' => $fio,
        
        'educational_institution' => $educational_institution,
        'course' => $course,
        'nomination' => $nomination,
        'section' => $section,
        'work_title' => $work_title,
        'email' => $email,
        'phone' => $phone,
        'work' => $filename,
    ]);
    
} catch (PDOException $e) {
    unlink($originalPath);
    unlink($galleryPath);
    redirect(SITE_URL . 'register.php', 'Ошибка сохранения данных', 'error');
}

redirect(SITE_URL . 'register.php?success=1', 'Заявка успешно отправлена', 'success');