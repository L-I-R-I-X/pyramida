<?php
$configFile = __DIR__ . '/../includes/config.php';
if (!file_exists($configFile)) {
    die('Ошибка: Файл config.php не найден.');
}
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';


$currentUser = checkAuth();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Статус работы обновлён';
    $messageType = 'success';
}

if (isset($_GET['deleted'])) {
    $message = 'Работа успешно удалена';
    $messageType = 'success';
}

try {
    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, email, phone, work_file, is_published, created_at 
        FROM applications 
        ORDER BY created_at DESC
    ");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}

$publishedCount = 0;
foreach ($applications as $app) {
    if ($app['is_published']) {
        $publishedCount++;
    }
}

$filter = $_GET['filter'] ?? 'all';
$published = $_GET['published'] ?? '';

if ($published !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT id, fio, educational_institution, course, nomination, section, email, phone, work_file, is_published, created_at 
            FROM applications 
            WHERE is_published = :published
            ORDER BY created_at DESC
        ");
        $stmt->execute(['published' => (int)$published]);
        $applications = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .admin-filters a { padding: 8px 16px; background: #FFFFFF; border: 1px solid #888888; border-radius: 4px; text-decoration: none; color: #1A1A1A; font-size: 0.9rem; white-space: nowrap; }
        .admin-filters a.active { background: #FF6B00; border-color: #FF6B00; color: #FFFFFF; }
        .applications-table { background: #FFFFFF; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; width: 100%; }
        .applications-table table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .applications-table th, .applications-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #F5F5F5; white-space: nowrap; }
        .applications-table th { background: #1A1A1A; color: #FFFFFF; font-weight: 500; }
        .applications-table tr:hover { background: #F5F5F5; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; white-space: nowrap; }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #f8d7da; color: #721c24; }
        .action-btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; margin-right: 5px; white-space: nowrap; }
        .action-btn-moderate { background: #1A1A1A; color: #FFFFFF; }
        .empty-state { text-align: center; padding: 60px 20px; color: #888888; }
        
        @media (max-width: 1200px) {
            .applications-table th, .applications-table td { padding: 10px 12px; font-size: 0.9rem; }
            .action-btn { padding: 5px 10px; font-size: 0.8rem; }
        }
        
        @media (max-width: 992px) {
            .admin-content { padding: 20px; }
            .applications-table table { min-width: 800px; }
            .applications-table th, .applications-table td { padding: 8px 10px; font-size: 0.85rem; }
        }
        
        @media (max-width: 768px) {
            .applications-table table { min-width: 700px; }
            .applications-table th, .applications-table td { padding: 6px 8px; font-size: 0.8rem; }
            .action-btn { padding: 4px 8px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Заявки участников</h1>   
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($applications); ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $publishedCount; ?></div>
                    <div class="stat-label">Опубликовано</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($applications) - $publishedCount; ?></div>
                    <div class="stat-label">На модерации</div>
                </div>
            </div>
            
            <div class="admin-filters">
                <a href="?filter=all&published=" class="<?php echo $published === '' ? 'active' : ''; ?>">Все</a>
                <a href="?filter=published&published=1" class="<?php echo $published === '1' ? 'active' : ''; ?>">Опубликованные</a>
                <a href="?filter=draft&published=0" class="<?php echo $published === '0' ? 'active' : ''; ?>">Не опубликованные</a>
            </div>
            
            <div class="applications-table">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <p>Заявок пока нет</p>
                    </div>
                <?php else: ?>
                    <table>
                       <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Учебное заведение</th>
                                <th>Номинация</th>
                                <th>Файл</th>
                                <th>Email</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo $app['id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['fio']); ?></td>
                                    <td><?php echo htmlspecialchars($app['educational_institution']); ?></td>
                                    <td><?php echo htmlspecialchars(getNominationName($app['nomination'])); ?></td>
                                    <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($app['work_file']); ?></code></td>
                                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <?php if ($app['is_published']): ?>
                                            <span class="status-badge status-published">Опубликовано</span>
                                        <?php else: ?>
                                            <span class="status-badge status-draft">На модерации</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="moderation.php?id=<?php echo $app['id']; ?>" class="action-btn action-btn-moderate">Модерация</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
