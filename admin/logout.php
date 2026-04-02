<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Инициализируем сессию перед выходом
initSession($pdo);

logout();

header('Location: login.php');
exit;
?>
