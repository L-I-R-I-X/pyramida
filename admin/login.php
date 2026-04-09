<?php


require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';


$currentUser = checkAuth();
if ($currentUser) {
    header('Location: applications.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $result = authenticate($username, $password);
        
        if ($result['success']) {
            
            $redirect = $_GET['redirect'] ?? 'applications.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
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
            margin: 0 0 10px 0;
        }
        .login-header p {
            color: #888888;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1A1A1A;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #E0E0E0;
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
            padding: 14px;
            background: #FF6B00;
            color: #FFFFFF;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #E55E00;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #888888;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link a:hover {
            color: #FF6B00;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏛️ Пирамида</h1>
            <p>Вход в админ-панель</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="back-link">
            <a href="<?php echo BASE_URL; ?>">← Вернуться на сайт</a>
        </div>
    </div>
</body>
</html>
