<?php
/**
 * Страница выхода из админ-панели
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Уничтожаем сессию
destroySession();

// Перенаправляем на страницу входа
header('Location: login.php');
exit;
