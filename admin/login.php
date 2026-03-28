<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Если уже авторизован — сразу в админку
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: applications.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($login, $password)) {
        // Успешный вход — редирект в админку
        header('Location: applications.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
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
        body {
            background: #F5F5F5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #FFFFFF;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #1A1A1A;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .login-header .logo {
            display: block;
            margin: 0 auto 15px;
            width: 60px;
            height: auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1A1A1A;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #888888;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #FF6B00;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #FF6B00;
            color: #FFFFFF;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
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
            </div>
        <?php endif; ?>
        
        <form method="POST">
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