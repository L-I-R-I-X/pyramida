<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pyramida/');
}

requireAuth();

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Статус работы обновлён';
    $messageType = 'success';
}

$applicationId = $_GET['id'] ?? 0;

try {
    // ✅ Изменено: vuz → educational_institution
    $stmt = $pdo->prepare("
        SELECT id, fio, educational_institution, course, nomination, section, email, phone, work_file, work_title, is_published, jury_score, created_at 
        FROM applications 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $applicationId]);
    $application = $stmt->fetch();
} catch (PDOException $e) {
    $application = null;
}

if (!$application) {
    header('Location: applications.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $publish = isset($_POST['publish']) ? (int)$_POST['publish'] : 0;
    $score = isset($_POST['jury_score']) && $_POST['jury_score'] !== '' ? (int)$_POST['jury_score'] : null;
    
    if ($score !== null) {
        $score = max(0, min(10, $score));
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET is_published = :published, jury_score = :score 
            WHERE id = :id
        ");
        $stmt->execute([
            'published' => $publish,
            'score' => $score,
            'id' => $applicationId
        ]);
        
        header('Location: moderation.php?id=' . $applicationId . '&success=1');
        exit;
    } catch (PDOException $e) {
        $message = 'Ошибка обновления статуса';
        $messageType = 'error';
    }
}

$galleryPath = '../uploads/gallery/' . $application['work_file'];
$originalPath = '../uploads/originals/' . $application['work_file'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация работы — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: #1A1A1A;
            color: #FFFFFF;
            padding: 20px;
        }
        .admin-sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #FF6B00;
        }
        .admin-sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .admin-sidebar a {
            color: #FFFFFF;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #FF6B00;
        }
        .admin-content {
            flex: 1;
            padding: 30px;
            background: #F5F5F5;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .admin-header h1 {
            font-size: 1.8rem;
            color: #1A1A1A;
        }
        .moderation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .work-preview {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .work-preview img {
            width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .work-info {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #F5F5F5;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 150px; /* ✅ Чуть шире для "Учебное заведение" */
            color: #888888;
            font-size: 0.9rem;
        }
        .info-value {
            color: #1A1A1A;
            font-weight: 500;
        }
        .status-indicator {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #f8d7da;
            color: #721c24;
        }
        .moderation-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #F5F5F5;
        }
        .moderation-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-publish {
            background: #FF6B00;
            color: #FFFFFF;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-unpublish {
            background: #1A1A1A;
            color: #FFFFFF;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            background: #F5F5F5;
            color: #1A1A1A;
            padding: 12px 24px;
            border: 1px solid #888888;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .file-download {
            margin-top: 15px;
        }
        .file-download a {
            color: #FF6B00;
            text-decoration: none;
        }
        .moderation-score {
            margin: 25px 0;
            padding: 20px;
            background: #F5F5F5;
            border-radius: 4px;
        }
        .moderation-score h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #1A1A1A;
        }
        .score-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .score-input-group input {
            width: 80px;
            padding: 10px;
            border: 1px solid #888888;
            border-radius: 4px;
            font-size: 1rem;
            text-align: center;
        }
        .score-input-group input:focus {
            outline: none;
            border-color: #FF6B00;
        }
        .score-hint {
            color: #888888;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Модерация работы</h1>
                <a href="applications.php" class="btn-back">← Назад к списку</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="moderation-grid">
                <div class="work-preview">
                    <h3>Предпросмотр работы</h3>
                    <?php if (file_exists($galleryPath)): ?>
                        <img src="<?php echo $galleryPath; ?>" alt="Работа участника">
                        <div class="file-download">
                            <a href="<?php echo $originalPath; ?>" download>Скачать оригинал</a>
                        </div>
                    <?php else: ?>
                        <p style="color: #888888; text-align: center; padding: 40px;">Изображение не найдено</p>
                    <?php endif; ?>
                </div>
                
                <div class="work-info">
                    <h3>Информация об участнике</h3>
                    
                    <div class="info-row">
                        <span class="info-label">ФИО:</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['fio']); ?></span>
                    </div>
                    
                    <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                    <div class="info-row">
                        <span class="info-label">Учебное заведение:</span>
                        <!-- ✅ Изменено: vuz → educational_institution -->
                        <span class="info-value"><?php echo htmlspecialchars($application['educational_institution']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Курс:</span>
                        <span class="info-value"><?php echo $application['course']; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Номинация:</span>
                        <span class="info-value"><?php echo htmlspecialchars(getNominationName($application['nomination'])); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Раздел:</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['section']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Название работы:</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['work_title'] ?? '-'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Имя файла:</span>
                        <span class="info-value"><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($application['work_file']); ?></code></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['email']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Телефон:</span>
                        <span class="info-value"><?php echo htmlspecialchars($application['phone'] ?? 'Не указан'); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Дата заявки:</span>
                        <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($application['created_at'])); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Статус:</span>
                        <span class="info-value">
                            <?php if ($application['is_published']): ?>
                                <span class="status-indicator status-published">Опубликовано в галерее</span>
                            <?php else: ?>
                                <span class="status-indicator status-draft">Не опубликовано</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <form method="POST" class="moderation-form">
                        <h3>Действия модератора</h3>
                        <p style="color: #888888; margin-bottom: 20px;">
                            Отметьте работу для публикации в галерее на сайте
                        </p>
                        
                        <div class="moderation-score">
                            <h3>Оценка жюри</h3>
                            <p style="color: #888888; margin-bottom: 15px;">
                                Укажите балл от 0 до 10 (необязательно)
                            </p>
                            
                            <div class="score-input-group">
                                <input type="number" 
                                       id="jury_score" 
                                       name="jury_score" 
                                       min="0" 
                                       max="10" 
                                       value="<?php echo $application['jury_score'] !== null ? htmlspecialchars($application['jury_score']) : ''; ?>"
                                       placeholder="0-10">
                                <span class="score-hint">баллов</span>
                            </div>
                            
                            <?php if ($application['jury_score'] !== null): ?>
                                <p style="margin-top: 10px; font-size: 0.9rem; color: #FF6B00;">
                                    Текущая оценка: <strong><?php echo htmlspecialchars($application['jury_score']); ?>/10</strong>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="moderation-actions">
                            <?php if ($application['is_published']): ?>
                                <button type="submit" name="unpublish" class="btn-unpublish">Снять с публикации</button>
                            <?php else: ?>
                                <button type="submit" name="publish" class="btn-publish">Опубликовать в галерее</button>
                            <?php endif; ?>
                            <input type="hidden" name="publish" value="<?php echo $application['is_published'] ? 0 : 1; ?>">
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>