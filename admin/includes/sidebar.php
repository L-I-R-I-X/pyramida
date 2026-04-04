<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Проверяем авторизацию
$currentUser = checkAuth();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Получаем полную информацию о пользователе включая роль
$fullUserInfo = getUserById($currentUser['id']);
$isMainAdmin = $fullUserInfo && $fullUserInfo['role'] === 'main';

// Определяем текущую страницу для подсветки активного пункта
$currentPage = basename($_SERVER['PHP_SELF']);
// Для модерации подсвечиваем "Заявки"
$isModerationPage = $currentPage === 'moderation.php';
$highlightApplications = $currentPage === 'applications.php' || $isModerationPage;
?>
<aside class="admin-sidebar">
    <h2>🏛️ Пирамида</h2>
    
    <a href="<?php echo BASE_URL; ?>" target="_blank" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">На сайт</a>
    
    <div class="sidebar-divider-white"></div>
    
    <div class="admin-user-info" style="cursor: pointer;" onclick="window.location.href='profile.php'">
        <span class="admin-user-icon">👤</span>
        <span class="admin-user-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
        <?php if ($isMainAdmin): ?>
            <span class="admin-role-badge">Главный</span>
        <?php else: ?>
            <span class="admin-role-badge regular">Админ</span>
        <?php endif; ?>
    </div>
    
    <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">Мой аккаунт</a>
    
    <div class="sidebar-divider-gray"></div>
    
    <a href="applications.php" class="<?php echo $highlightApplications ? 'active' : ''; ?>">Заявки</a>
    <a href="export.php" class="<?php echo $currentPage === 'export.php' ? 'active' : ''; ?>">Экспорт данных</a>
    <a href="site-settings.php" class="<?php echo $currentPage === 'site-settings.php' ? 'active' : ''; ?>">Настройки сайта</a>
    
    <?php if ($isMainAdmin): ?>
        <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">Администраторы</a>
    <?php endif; ?>
    
    <div class="sidebar-divider-gray"></div>
    
    <a href="logout.php">Выход</a>
</aside>
