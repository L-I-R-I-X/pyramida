<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

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
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Заявки участников</h1>   
            </div>
            
            <!-- ✅ Карточки статистики в едином стиле -->
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
                                <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
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
                                    <!-- ✅ Изменено: vuz → educational_institution -->
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