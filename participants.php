<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pyramida/');
}

$showTable = getSetting('show_participants_table', '0');
$showCertificates = getSetting('show_certificates', '1');

if ($showTable !== '1') {
    header('Location: index.php');
    exit;
}

try {
    // ✅ Изменено: vuz → educational_institution
    $stmt = $pdo->query("
        SELECT id, fio, educational_institution, course, nomination, section, is_published, created_at 
        FROM applications 
        ORDER BY nomination, section, created_at DESC
    ");
    $participants = $stmt->fetchAll();
} catch (PDOException $e) {
    $participants = [];
}

// Группировка по номинациям и разделам
$groupedParticipants = [];
foreach ($participants as $participant) {
    $nomination = $participant['nomination'] ?? 'Без номинации';
    $section = $participant['section'] ?? 'Без раздела';
    
    if (!isset($groupedParticipants[$nomination])) {
        $groupedParticipants[$nomination] = [];
    }
    
    if (!isset($groupedParticipants[$nomination][$section])) {
        $groupedParticipants[$nomination][$section] = [];
    }
    
    $groupedParticipants[$nomination][$section][] = $participant;
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
    <title>Таблица участников — Конкурс «Пирамида»</title>
    <meta name="description" content="Сводная таблица участников конкурса">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Таблица участников</h1>
                <p>Сводная информация об участниках конкурса по номинациям</p>
            </div>
        </section>
        
        <section class="section participants-section">
            <div class="container">
                <div class="table-info">
                    <p>В таблице представлены все участники конкурса, сгруппированные по номинациям и разделам. Работы не отображаются для защиты авторских прав.</p>
                </div>
                
                <?php if (empty($groupedParticipants)): ?>
                    <div class="empty-state">
                        <h2>Данных пока нет</h2>
                        <p>Таблица участников будет опубликована после завершения приёма заявок</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedParticipants as $nomination => $sections): ?>
                        <div class="nomination-group">
                            <h2 class="nomination-title"><?php echo htmlspecialchars($nominationNames[$nomination] ?? $nomination); ?></h2>
                            
                            <?php foreach ($sections as $section => $participantsInSection): ?>
                                <div class="section-group">
                                    <h3 class="section-title"><?php echo htmlspecialchars($section); ?></h3>
                                    
                                    <div class="participants-table-wrapper">
                                        <table class="participants-table">
                                            <thead>
                                                <tr>
                                                    <th>№</th>
                                                    <th>ФИО участника</th>
                                                    <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                                                    <th>Учебное заведение</th>
                                                    <th>Курс</th>
                                                    <th>Статус</th>
                                                    <th>Сертификат</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $rank = 1;
                                                foreach ($participantsInSection as $participant): 
                                                ?>
                                                    <tr>
                                                        <td><?php echo $rank++; ?></td>
                                                        <td><?php echo htmlspecialchars($participant['fio']); ?></td>
                                                        <!-- ✅ Изменено: vuz → educational_institution -->
                                                        <td><?php echo htmlspecialchars($participant['educational_institution']); ?></td>
                                                        <td><?php echo $participant['course']; ?></td>
                                                        <td>
                                                            <?php if ($participant['is_published']): ?>
                                                                <span class="status-badge status-published">В галерее</span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-draft">Участник</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($showCertificates == '1'): ?>
                                                                <a href="generate-certificate.php?<?= http_build_query([
                                                                    'fio' => $participant['fio'],
                                                                    'nomination' => $participant['nomination'],
                                                                    'section' => $participant['section'],
                                                                    'type' => 'certificate'
                                                                ]); ?>" 
                                                                   class="btn-download" 
                                                                   target="_blank">
                                                                    📄 Сертификат
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
                    <a href="index.php" class="btn btn-outline">На главную</a>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>