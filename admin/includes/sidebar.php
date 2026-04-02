<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Проверяем авторизацию
$currentUser = checkAuth();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}
?>
<aside class="admin-sidebar">
    <h2>🏛️ Пирамида</h2>
    
    <div class="admin-user-info">
        <span class="admin-user-icon">👤</span>
        <span class="admin-user-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
    </div>
    
    <nav>
        <a href="applications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'applications.php' ? 'active' : ''; ?>">Заявки</a>
        <a href="moderation.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'moderation.php' ? 'active' : ''; ?>">Модерация</a>
        <a href="export.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'export.php' ? 'active' : ''; ?>">Экспорт данных</a>
        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">Настройки</a>
        
        <a href="<?php echo BASE_URL; ?>" target="_blank">На сайт</a>
        <a href="logout.php" style="margin-top: 20px; border-top: 1px solid #E0E0E0; padding-top: 15px;">Выход</a>
    </nav>
</aside>
