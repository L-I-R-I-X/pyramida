<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

logout();

// Редирект на страницу входа
header('Location: login.php');
exit;
?>