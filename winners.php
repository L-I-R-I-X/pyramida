<?php
$configFile = __DIR__ . '/includes/config.php';
if (!file_exists($configFile)) {
    die('Ошибка: Файл config.php не найден. Переименуйте config.example.php в config.php и настройте параметры подключения.');
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$showTable = getSetting('show_winners_table', '0');
$showDiplomas = getSetting('show_diplomas', '1');

if ($showTable !== '1') {
    header('Location: index.php');
    exit;
}

try {
    
    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, work_file, work_title, is_published, jury_score, created_at 
        FROM applications 
        WHERE is_published = 1 
        ORDER BY nomination, section, created_at DESC
    ");
    $winners = $stmt->fetchAll();
} catch (PDOException $e) {
    $winners = [];
}

$groupedWinners = [];
foreach ($winners as $winner) {
    $nomination = $winner['nomination'] ?? 'Без номинации';
    $section = $winner['section'] ?? 'Без раздела';
    
    if (!isset($groupedWinners[$nomination])) {
        $groupedWinners[$nomination] = [];
    }
    
    if (!isset($groupedWinners[$nomination][$section])) {
        $groupedWinners[$nomination][$section] = [];
    }
    
    $groupedWinners[$nomination][$section][] = $winner;
}

$nominationNames = [
    'arch_composition' => 'Архитектурная композиция',
    'art_graphics' => 'Художественно-проектная графика',
    'nature_drawing' => 'Рисунок с натуры',
    'photography' => 'Фотография'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Победители конкурса — Конкурс «Пирамида»</title>
    <meta name="description" content="Таблица победителей конкурса архитектурной графики по номинациям">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Победители конкурса</h1>
                <p>Лучшие работы по версии жюри, сгруппированные по номинациям</p>
            </div>
        </section>
        
        <section class="section winners-section">
            <div class="container">
                <div class="table-info">
                    <p>В таблице представлены работы, отобранные жюри для публикации в галерее, сгруппированные по номинациям и разделам.</p>
                </div>
                
                <?php if (empty($groupedWinners)): ?>
                    <div class="empty-state">
                        <h2>Победители ещё не определены</h2>
                        <p>Таблица победителей будет опубликована после завершения работы жюри</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedWinners as $nomination => $sections): ?>
                        <div class="nomination-group">
                            <h2 class="nomination-title"><?php echo htmlspecialchars($nominationNames[$nomination] ?? $nomination); ?></h2>
                            
                            <?php foreach ($sections as $section => $winnersInSection): ?>
                                <div class="section-group">
                                    <h3 class="section-title"><?php echo htmlspecialchars($section); ?></h3>
                                    
                                    <div class="winners-table-wrapper">
                                        <table class="winners-table">
                                            <thead>
                                                <tr>
                                                    <th>№</th>
                                                    <th>Работа</th>
                                                    <th>Автор</th>
                                                    <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                                                    <th>Учебное заведение</th>
                                                    <th>Курс</th>
                                                    <th>Диплом</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($winnersInSection as $index => $winner): 
                                                    
                                                    $place = 0;
                                                    if ($winner['jury_score'] !== null) {
                                                        $place = getPlaceInNomination(
                                                            $pdo, 
                                                            $winner['nomination'], 
                                                            $winner['section'], 
                                                            $winner['jury_score'], 
                                                            $winner['id']
                                                        );
                                                    }
                                                    $placeText = $place > 0 ? getPlaceText($place) : '';
                                                    
                                                    $medal = $index < 3 ? ['🥇', '🥈', '🥉'][$index] : ($index + 1) . '.';
                                                    $galleryPath = 'uploads/gallery/' . $winner['work_file'];
                                                ?>
                                                    <tr class="<?php echo $index < 3 ? 'winner-row winner-' . ($index + 1) : ''; ?>">
                                                        <td class="medal-cell"><?php echo $medal; ?></td>
                                                        <td class="work-preview-cell">
                                                            <?php if (file_exists($galleryPath)): ?>
                                                                <img src="<?php echo $galleryPath; ?>" alt="Работа <?php echo htmlspecialchars($winner['fio']); ?>">
                                                            <?php else: ?>
                                                                <span class="no-image">Нет изображения</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($winner['fio']); ?></td>
                                                        <!-- ✅ Изменено: vuz → educational_institution -->
                                                        <td><?php echo htmlspecialchars($winner['educational_institution']); ?></td>
                                                        <td><?php echo $winner['course']; ?></td>
                                                        <td>
                                                            <?php if ($showDiplomas == '1'): ?>
                                                                <a href="generate-certificate.php?<?= http_build_query([
                                                                    'fio' => $winner['fio'],
                                                                    'nomination' => $winner['nomination'],
                                                                    'section' => $winner['section'],
                                                                    'type' => 'diploma',
                                                                    'place' => $placeText
                                                                ]); ?>" 
                                                                   class="btn-download" 
                                                                   target="_blank">
                                                                    🏆 Диплом
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="table-actions">
                    <a href="gallery.php" class="btn">Перейти в галерею</a>
                    <a href="participants.php" class="btn btn-outline">Все участники</a>
                    <a href="index.php" class="btn btn-outline">На главную</a>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>