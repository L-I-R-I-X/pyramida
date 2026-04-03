<?php
require_once 'config.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$showParticipants = '0';
$showWinners = '0';
$showGallery = '0';

if (file_exists(__DIR__ . '/db.php')) {
    try {
        require_once __DIR__ . '/db.php';
        if (file_exists(__DIR__ . '/functions.php') && isset($pdo)) {
            require_once __DIR__ . '/functions.php';
            $showParticipants = getSetting('show_participants_table', '0');
            $showWinners = getSetting('show_winners_table', '0');
            $showGallery = getSetting('show_gallery', '0');
        }
    } catch (Exception $e) {
    }
}
?>
<header class="site-header">
    <div class="container header-content">
        <a href="<?php echo BASE_URL; ?>index.php" class="logo">
            <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Пирамида">
            <span>Конкурс «Пирамида»</span>
        </a>
        <nav class="main-nav">
            <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">Главная</a>
            <a href="<?php echo BASE_URL; ?>polozhenie.php" class="<?php echo $currentPage === 'polozhenie' ? 'active' : ''; ?>">Положение</a>
            <a href="<?php echo BASE_URL; ?>register.php" class="<?php echo $currentPage === 'register' ? 'active' : ''; ?>">Подать заявку</a>
            <?php if ($showGallery == '1'): ?>
                <a href="<?php echo BASE_URL; ?>gallery.php" class="<?php echo $currentPage === 'gallery' ? 'active' : ''; ?>">Галерея</a>
            <?php endif; ?>
            <?php if ($showWinners == '1'): ?>
                <a href="<?php echo BASE_URL; ?>winners.php" class="<?php echo $currentPage === 'winners' ? 'active' : ''; ?>">Победители</a>
            <?php endif; ?>
            <?php if ($showParticipants == '1'): ?>
                <a href="<?php echo BASE_URL; ?>participants.php" class="<?php echo $currentPage === 'participants' ? 'active' : ''; ?>">Участники</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>contacts.php" class="<?php echo $currentPage === 'contacts' ? 'active' : ''; ?>">Контакты</a>
        </nav>
    </div>
</header>
