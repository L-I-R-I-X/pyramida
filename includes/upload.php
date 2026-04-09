<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

session_start();

// Функция для логирования на страницу и в файл
function logStep($step, $message, $status = 'info', $logFile = null) {
    $timestamp = date('Y-m-d H:i:s');
    $statusClass = $status === 'error' ? 'error' : ($status === 'success' ? 'success' : 'info');
    
    // Вывод на страницу
    echo "<div class=\"log-entry log-$statusClass\">";
    echo "<strong>[$timestamp] $step:</strong> " . htmlspecialchars($message);
    echo "</div>\n";
    
    // Логирование в файл (если указан)
    if ($logFile) {
        $logMessage = "[$timestamp] [$status] $step: $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    // Флеш для вывода
    flush();
    ob_flush();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . 'register.php', 'Неверный метод запроса', 'error');
}

// Отключаем буферизацию для пошагового вывода
ob_end_clean();
header('Content-Type: text/html; charset=utf-8');

// Начинаем вывод HTML для логирования
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лог подачи заявки — Конкурс «Пирамида»</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .log-container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .log-entry { padding: 10px; margin: 10px 0; border-left: 4px solid #ccc; background: #f9f9f9; }
        .log-info { border-left-color: #2196F3; }
        .log-success { border-left-color: #4CAF50; background: #e8f5e9; }
        .log-error { border-left-color: #f44336; background: #ffebee; }
        h1 { color: #333; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="log-container">
        <h1>📋 Лог подачи заявки</h1>
        <?php
        
        $logFile = LOGS_DIR . 'register_' . date('Y-m-d') . '.log';
        
        // Этап 1: Начало обработки
        logStep('Инициализация', 'Начало обработки заявки', 'info', $logFile);
        
        $fio = sanitizeInput($_POST['fio'] ?? '');
        $educational_institution = sanitizeInput($_POST['educational_institution'] ?? '');
        $course = sanitizeInput($_POST['course'] ?? '');
        $nominationSection = sanitizeInput($_POST['nomination_section'] ?? '');
        $nomination = sanitizeInput($_POST['nomination'] ?? '');
        $section = sanitizeInput($_POST['section'] ?? '');
        $work_title = sanitizeInput($_POST['work_title'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $consent = isset($_POST['consent']);
        
        logStep('Данные формы', "ФИО: $fio, Email: $email, Номинация: $nominationSection", 'info', $logFile);
        
        $errors = [];
        
        // Этап 2: Валидация данных
        logStep('Валидация', 'Начало проверки данных формы', 'info', $logFile);
        
        if (empty($fio)) {
            $errors[] = 'Укажите ФИО';
            logStep('Валидация', 'Ошибка: не указано ФИО', 'error', $logFile);
        }
        
        if (empty($educational_institution)) {
            $errors[] = 'Укажите учебное заведение';
            logStep('Валидация', 'Ошибка: не указано учебное заведение', 'error', $logFile);
        }
        
        if (empty($course)) {
            $errors[] = 'Укажите курс';
            logStep('Валидация', 'Ошибка: не указан курс', 'error', $logFile);
        }
        
        if (empty($nominationSection)) {
            $errors[] = 'Выберите номинацию и раздел';
            logStep('Валидация', 'Ошибка: не выбрана номинация', 'error', $logFile);
        }
        
        if (empty($work_title)) {
            $errors[] = 'Укажите название работы';
            logStep('Валидация', 'Ошибка: не указано название работы', 'error', $logFile);
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Укажите корректный email';
            logStep('Валидация', 'Ошибка: некорректный email', 'error', $logFile);
        }
        
        if (!$consent) {
            $errors[] = 'Требуется согласие на обработку персональных данных';
            logStep('Валидация', 'Ошибка: нет согласия на обработку данных', 'error', $logFile);
        }
        
        if (!isset($_FILES['work']) || $_FILES['work']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Ошибка загрузки файла';
            $fileError = $_FILES['work']['error'] ?? 'unknown';
            logStep('Валидация', "Ошибка файла: код $fileError", 'error', $logFile);
        } else {
            logStep('Валидация', 'Файл загружен успешно', 'success', $logFile);
        }
        
        if (!empty($errors)) {
            logStep('Валидация', 'Обнаружено ошибок: ' . count($errors), 'error', $logFile);
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            if (!empty($nominationSection)) {
                $_SESSION['form_data']['nomination_section'] = $nominationSection;
            }
            echo '<p><a href="' . SITE_URL . 'register.php" class="back-link">← Вернуться к форме</a></p>';
            echo '</div></body></html>';
            exit;
        }
        
        logStep('Валидация', 'Все проверки пройдены успешно', 'success', $logFile);
        
        // Этап 3: Проверка файла
        $file = $_FILES['work'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileTmpName = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        logStep('Файл', "Имя: {$file['name']}, Размер: " . round($fileSize / 1024 / 1024, 2) . " Мб, Тип: $fileType", 'info', $logFile);
        
        if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
            logStep('Файл', "Недопустимое расширение: $fileExt", 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Недопустимый формат файла', 'error');
        }
        logStep('Файл', 'Расширение файла проверено: ' . $fileExt, 'success', $logFile);
        
        if (!in_array($fileType, ALLOWED_MIME_TYPES)) {
            logStep('Файл', "Недопустимый MIME-тип: $fileType", 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Недопустимый тип файла', 'error');
        }
        logStep('Файл', 'MIME-тип проверен: ' . $fileType, 'success', $logFile);
        
        if ($fileSize < UPLOAD_MIN_SIZE && UPLOAD_MIN_SIZE > 0) {
            logStep('Файл', 'Файл слишком маленький', 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Файл слишком маленький (минимум ' . (UPLOAD_MIN_SIZE / 1024 / 1024) . ' Мб)', 'error');
        }
        
        if ($fileSize > UPLOAD_MAX_SIZE) {
            logStep('Файл', 'Файл слишком большой', 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Файл слишком большой (максимум 10 Мб)', 'error');
        }
        logStep('Файл', 'Размер файла в пределах нормы', 'success', $logFile);
        
        if (!validateFileSignature($fileTmpName)) {
            logStep('Файл', 'Файл не является изображением (проверка сигнатуры)', 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Файл не является изображением', 'error');
        }
        logStep('Файл', 'Сигнатура файла проверена (JPEG)', 'success', $logFile);
        
        if (!validateImageDimensions($fileTmpName)) {
            logStep('Файл', 'Изображение слишком большое', 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Изображение слишком большое (макс. 5000×5000 px)', 'error');
        }
        logStep('Файл', 'Размеры изображения проверены', 'success', $logFile);
        
        // Этап 4: Создание директорий
        logStep('Директории', 'Проверка существования папок для загрузки', 'info', $logFile);
        
        if (!is_dir(UPLOAD_DIR_ORIGINALS)) {
            logStep('Директории', "Создание папки: " . UPLOAD_DIR_ORIGINALS, 'info', $logFile);
            if (!mkdir(UPLOAD_DIR_ORIGINALS, 0755, true)) {
                logStep('Директории', 'Ошибка создания папки originals', 'error', $logFile);
                redirect(SITE_URL . 'register.php', 'Ошибка создания директории', 'error');
            }
        }
        logStep('Директории', 'Папка originals готова: ' . UPLOAD_DIR_ORIGINALS, 'success', $logFile);
        
        if (!is_dir(UPLOAD_DIR_GALLERY)) {
            logStep('Директории', "Создание папки: " . UPLOAD_DIR_GALLERY, 'info', $logFile);
            if (!mkdir(UPLOAD_DIR_GALLERY, 0755, true)) {
                logStep('Директории', 'Ошибка создания папки gallery', 'error', $logFile);
                redirect(SITE_URL . 'register.php', 'Ошибка создания директории', 'error');
            }
        }
        logStep('Директории', 'Папка gallery готова: ' . UPLOAD_DIR_GALLERY, 'success', $logFile);
        
        // Проверка прав на запись
        if (!is_writable(UPLOAD_DIR_ORIGINALS)) {
            logStep('Директории', 'Нет прав на запись в папку originals: ' . UPLOAD_DIR_ORIGINALS, 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Нет прав на запись в директорию', 'error');
        }
        logStep('Директории', 'Права на запись проверены', 'success', $logFile);
        
        // Этап 5: Генерация имени и сохранение файла
        $filename = generateUniqueFilename($fileExt);
        $originalPath = UPLOAD_DIR_ORIGINALS . $filename;
        $galleryPath = UPLOAD_DIR_GALLERY . $filename;
        
        logStep('Файл', "Генерация имени: $filename", 'info', $logFile);
        logStep('Файл', "Сохранение оригинала: $originalPath", 'info', $logFile);
        
        if (!move_uploaded_file($fileTmpName, $originalPath)) {
            logStep('Файл', 'Ошибка перемещения загруженного файла в ' . $originalPath, 'error', $logFile);
            redirect(SITE_URL . 'register.php', 'Ошибка сохранения файла', 'error');
        }
        logStep('Файл', 'Оригинал сохранён успешно: ' . $originalPath, 'success', $logFile);
        
        // Этап 6: Создание миниатюры
        logStep('Файл', "Создание миниатюры: $galleryPath", 'info', $logFile);
        
        if (!createThumbnail($originalPath, $galleryPath, 800)) {
            logStep('Файл', 'Ошибка создания миниатюры', 'error', $logFile);
            unlink($originalPath);
            redirect(SITE_URL . 'register.php', 'Ошибка обработки изображения', 'error');
        }
        logStep('Файл', 'Миниатюра создана успешно: ' . $galleryPath, 'success', $logFile);
        
        // Этап 7: Сохранение в БД
        logStep('БД', 'Начало сохранения данных в базу', 'info', $logFile);
        
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
            
            $applicationId = $pdo->lastInsertId();
            logStep('БД', "Заявка сохранена с ID: $applicationId", 'success', $logFile);
            
        } catch (PDOException $e) {
            logStep('БД', 'Ошибка сохранения в БД: ' . $e->getMessage(), 'error', $logFile);
            unlink($originalPath);
            unlink($galleryPath);
            redirect(SITE_URL . 'register.php', 'Ошибка сохранения данных', 'error');
        }
        
        // Этап 8: Завершение
        logStep('Завершение', 'Заявка успешно обработана', 'success', $logFile);
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 8px;">
            <h2 style="color: #4CAF50;">✅ Заявка успешно отправлена!</h2>
            <p>Ваша работа принята на рассмотрение жюри.</p>
            <p>ID заявки: <strong><?php echo $applicationId; ?></strong></p>
            <p>Файл работы: <strong><?php echo htmlspecialchars($filename); ?></strong></p>
            <p>После подведения итогов дипломы и сертификаты участников будут доступны для скачивания на сайте во вкладках «Победители» и «Участники»</p>
        </div>
        
        <p><a href="<?php echo SITE_URL; ?>register.php" class="back-link">← Вернуться к форме</a></p>
        <p><a href="<?php echo SITE_URL; ?>" class="back-link" style="background: #666;">← На главную</a></p>
    </div>
</body>
</html>