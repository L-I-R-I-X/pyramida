<?php


require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';


destroySession();


header('Location: login.php');
exit;
