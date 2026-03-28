<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

// Генерируем CSRF токен при загрузке страницы
generateCsrfToken();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: applications.php');
    exit;
}

$error = '';
$remainingAttempts = null;
$waitTime = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности: недействительный токен';
    } else {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Валидация входных данных
        if (empty($login) || empty($password)) {
            $error = 'Введите логин и пароль';
        } else {
            $result = login($login, $password);
            
            if ($result['success']) {
                header('Location: applications.php');
                exit;
            } else {
                if ($result['blocked']) {
                    $waitTime = $result['wait_time'];
                    $error = "Слишком много неудачных попыток. Попробуйте через {$waitTime} сек.";
                    $remainingAttempts = 0;
                } else {
                    $error = 'Неверный логин или пароль';
                    $remainingAttempts = $result['attempts_left_before_block'] ?? null;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель — Пирамида</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-login {
            background: #FF6B00;
            color: #FFFFFF;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #E55E00;
        }
        .error-message {
            background: #FFEBEE;
            border: 1px solid #FFCDD2;
            color: #C62828;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        .login-footer a {
            color: #FF6B00;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/img/logo.png" alt="Логотип" class="logo">
            <h1>Админ-панель</h1>
            <p style="color: #666;">Конкурс «Пирамида»</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
                <?php if ($remainingAttempts !== null && $remainingAttempts > 0): ?>
                    <br><small>Осталось попыток: <?php echo $remainingAttempts; ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php csrfField(); ?>
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="login-footer">
            <a href="../index.php">← Вернуться на сайт</a>
        </div>
    </div>
</body>
</html>