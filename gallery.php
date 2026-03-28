<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

require_once 'includes/functions.php';

$showGallery = getSetting('show_gallery', '1');
$gallerySortOrder = getSetting('gallery_sort_order', 'date_desc');

if ($showGallery !== '1') {
    header('Location: index.php');
    exit;
}

$sortOrders = [
    'date_desc' => 'created_at DESC',
    'date_asc' => 'created_at ASC',
];

$orderBy = $sortOrders[$gallerySortOrder] ?? 'created_at DESC';

try {
    $stmt = $pdo->query("
        SELECT id, fio, vuz, course, nomination, section, work_file, work_title, created_at 
        FROM applications 
        WHERE is_published = 1 
        ORDER BY nomination, section, $orderBy
    ");
    $works = $stmt->fetchAll();
} catch (PDOException $e) {
    $works = [];
}

// Группировка по номинациям и разделам
$groupedWorks = [];
foreach ($works as $work) {
    $nomination = $work['nomination'] ?? 'Без номинации';
    $section = $work['section'] ?? 'Без раздела';
    
    if (!isset($groupedWorks[$nomination])) {
        $groupedWorks[$nomination] = [];
    }
    
    if (!isset($groupedWorks[$nomination][$section])) {
        $groupedWorks[$nomination][$section] = [];
    }
    
    $groupedWorks[$nomination][$section][] = $work;
}

$filterNomination = $_GET['nomination'] ?? '';
$filterEducationalInstitution = $_GET['educational_institution'] ?? ''; // ✅ Переименовано
$filterCourse = $_GET['course'] ?? '';

if ($filterNomination || $filterEducationalInstitution || $filterCourse) { // ✅ Переименовано
    $conditions = ['is_published = 1'];
    $params = [];
    
    if ($filterNomination) {
        $conditions[] = 'nomination = :nomination';
        $params['nomination'] = $filterNomination;
    }
    
    if ($filterEducationalInstitution) { // ✅ Переименовано
        $conditions[] = 'vuz LIKE :vuz'; // ⚠️ Столбец в БД остаётся "vuz"
        $params['vuz'] = '%' . $filterEducationalInstitution . '%'; // ✅ Переименовано
    }
    
    if ($filterCourse) {
        $conditions[] = 'course = :course';
        $params['course'] = (int)$filterCourse;
    }
    
    $sql = "SELECT id, fio, vuz, course, nomination, section, work_file, work_title, created_at 
            FROM applications 
            WHERE " . implode(' AND ', $conditions) . " 
            ORDER BY nomination, section, $orderBy";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $works = $stmt->fetchAll();
        
        // Перегруппировываем
        $groupedWorks = [];
        foreach ($works as $work) {
            $nomination = $work['nomination'] ?? 'Без номинации';
            $section = $work['section'] ?? 'Без раздела';
            
            if (!isset($groupedWorks[$nomination])) {
                $groupedWorks[$nomination] = [];
            }
            
            if (!isset($groupedWorks[$nomination][$section])) {
                $groupedWorks[$nomination][$section] = [];
            }
            
            $groupedWorks[$nomination][$section][] = $work;
        }
    } catch (PDOException $e) {
    }
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
    <title>Галерея работ — Конкурс «Пирамида»</title>
    <meta name="description" content="Онлайн-выставка работ участников конкурса архитектурной графики">
    <meta name="keywords" content="конкурс, архитектурная графика, архитектурный рисунок, рисунок, выставка">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Галерея работ</h1>
                <p>Онлайн-выставка лучших работ участников конкурса</p>
            </div>
        </section>
        
        <section class="section gallery-section">
            <div class="container">
                <div class="gallery-filters">
                    <form method="GET" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="nomination">Номинация</label>
                                <select id="nomination" name="nomination">
                                    <option value="">Все номинации</option>
                                    <option value="arch_composition" <?php echo $filterNomination == 'arch_composition' ? 'selected' : ''; ?>>Архитектурная композиция</option>
                                    <option value="art_graphics" <?php echo $filterNomination == 'art_graphics' ? 'selected' : ''; ?>>Художественно-проектная графика</option>
                                    <option value="nature_drawing" <?php echo $filterNomination == 'nature_drawing' ? 'selected' : ''; ?>>Рисунок с натуры</option>
                                    <option value="photography" <?php echo $filterNomination == 'photography' ? 'selected' : ''; ?>>Фотография</option>
                                </select>
                            </div>
                            
                            <!-- ✅ Изменено: ВУЗ → Учебное заведение -->
                            <div class="filter-group">
                                <label for="educational_institution">Учебное заведение</label>
                                <input type="text" id="educational_institution" name="educational_institution" placeholder="Название учебного заведения" value="<?php echo htmlspecialchars($filterEducationalInstitution); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label for="course">Курс</label>
                                <select id="course" name="course">
                                    <option value="">Все курсы</option>
                                    <option value="1" <?php echo $filterCourse == '1' ? 'selected' : ''; ?>>1 курс</option>
                                    <option value="2" <?php echo $filterCourse == '2' ? 'selected' : ''; ?>>2 курс</option>
                                    <option value="3" <?php echo $filterCourse == '3' ? 'selected' : ''; ?>>3 курс</option>
                                    <option value="4" <?php echo $filterCourse == '4' ? 'selected' : ''; ?>>4 курс</option>
                                    <option value="5" <?php echo $filterCourse == '5' ? 'selected' : ''; ?>>5 курс</option>
                                    <option value="6" <?php echo $filterCourse == '6' ? 'selected' : ''; ?>>6 курс</option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn">Фильтровать</button>
                                <a href="gallery.php" class="btn btn-outline">Сбросить</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="gallery-stats">
                    <p>Найдено работ: <strong><?php echo count($works); ?></strong></p>
                </div>
                
                <?php if (empty($groupedWorks)): ?>
                    <div class="empty-state">
                        <h2>Работ пока нет</h2>
                        <p>Опубликованные работы появятся здесь после отбора жюри</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedWorks as $nomination => $sections): ?>
                        <div class="nomination-group">
                            <h2 class="nomination-title"><?php echo htmlspecialchars($nominationNames[$nomination] ?? $nomination); ?></h2>
                            
                            <?php foreach ($sections as $section => $worksInSection): ?>
                                <div class="section-group">
                                    <h3 class="section-title"><?php echo htmlspecialchars($section); ?></h3>
                                    
                                    <div class="gallery-grid">
                                        <?php foreach ($worksInSection as $work): ?>
                                            <div class="gallery-item" data-id="<?php echo $work['id']; ?>">
                                                <div class="gallery-item-image">
                                                    <?php 
                                                    $galleryPath = 'uploads/gallery/' . $work['work_file'];
                                                    if (file_exists($galleryPath)): 
                                                    ?>
                                                        <img src="<?php echo $galleryPath; ?>" alt="Работа <?php echo htmlspecialchars($work['fio']); ?>" loading="lazy">
                                                    <?php else: ?>
                                                        <div class="placeholder-image">Изображение не найдено</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="gallery-item-info">
                                                    <h3><?php echo htmlspecialchars($work['fio']); ?></h3>
                                                    <!-- ✅ Изменено: work-vuz → work-educational-institution -->
                                                    <p class="work-educational-institution"><?php echo htmlspecialchars($work['vuz']); ?></p>
                                                    <p class="work-course"><?php echo $work['course']; ?> курс</p>
                                                    <?php if (!empty($work['work_title'])): ?>
                                                        <p class="work-title"><?php echo htmlspecialchars($work['work_title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>