<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

try {
    // ✅ Изменено: vuz → educational_institution
    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, email, phone, work_file, is_published, created_at 
        FROM applications 
        ORDER BY created_at DESC
    ");
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}

// ✅ Рассчитываем количество опубликованных работ
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
        // ✅ Изменено: vuz → educational_institution
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
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            background: #1A1A1A;
            color: #FFFFFF;
            padding: 20px;
            flex-shrink: 0;
        }
        .admin-sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #FF6B00;
            white-space: nowrap;
        }
        .admin-user-info {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-user-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .admin-user-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #FFFFFF;
            word-break: break-word;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #FF6B00;
        }
        .admin-content {
            flex: 1;
            padding: 30px;
            background: #F5F5F5;
            min-width: 0;
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
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: #FFFFFF;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #FF6B00;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .stat-card .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #FF6B00;
            margin-bottom: 8px;
            line-height: 1;
        }
        .stat-card .stat-label {
            color: #1A1A1A;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .admin-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .admin-filters a {
            padding: 8px 16px;
            background: #FFFFFF;
            border: 1px solid #888888;
            border-radius: 4px;
            text-decoration: none;
            color: #1A1A1A;
            font-size: 0.9rem;
        }
        .admin-filters a.active {
            background: #FF6B00;
            border-color: #FF6B00;
            color: #FFFFFF;
        }
        .applications-table {
            background: #FFFFFF;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .applications-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .applications-table th,
        .applications-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #F5F5F5;
        }
        .applications-table th {
            background: #1A1A1A;
            color: #FFFFFF;
            font-weight: 500;
        }
        .applications-table tr:hover {
            background: #F5F5F5;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-right: 5px;
        }
        .action-btn-view {
            background: #FF6B00;
            color: #FFFFFF;
        }
        .action-btn-moderate {
            background: #1A1A1A;
            color: #FFFFFF;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888888;
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