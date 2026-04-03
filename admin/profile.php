<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Проверяем авторизацию
$currentUser = checkAuth();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_username') {
        $newUsername = trim($_POST['new_username'] ?? '');
        $result = changeUsername($currentUser['id'], $newUsername);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Если логин успешно изменён, обновляем данные текущего пользователя
        if ($result['success']) {
            $currentUser = getUserById($currentUser['id']);
        }
        
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Проверка текущего пароля
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = :id");
            $stmt->execute(['id' => $currentUser['id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $message = 'Неверный текущий пароль';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Новые пароли не совпадают';
                $messageType = 'error';
            } else {
                $result = changePassword($currentUser['id'], $newPassword);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
        } catch (PDOException $e) {
            $message = 'Ошибка базы данных';
            $messageType = 'error';
        }
    }
}

// Получаем полную информацию о пользователе
$fullUserInfo = getUserById($currentUser['id']);
$isMainAdmin = $fullUserInfo && $fullUserInfo['role'] === 'main';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой аккаунт — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card { background: #FFFFFF; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; margin-bottom: 20px; color: #1A1A1A; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #1A1A1A; font-weight: 500; }
        .form-group input { width: 100%; max-width: 400px; padding: 10px; border: 1px solid #E0E0E0; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; }
        .form-group input[disabled] { background: #F5F5F5; cursor: not-allowed; }
        .user-info-row { display: flex; gap: 30px; margin-bottom: 20px; padding: 15px; background: #F9F9F9; border-radius: 8px; }
        .user-info-item { flex: 1; }
        .user-info-label { font-size: 0.85rem; color: #666; margin-bottom: 5px; }
        .user-info-value { font-size: 1.1rem; font-weight: 600; color: #1A1A1A; }
        .divider { height: 1px; background: #E0E0E0; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>⚙️ Мой аккаунт</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Информация о пользователе -->
            <div class="card">
                <h3>Информация об аккаунте</h3>
                <div class="user-info-row">
                    <div class="user-info-item">
                        <div class="user-info-label">Логин</div>
                        <div class="user-info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                    </div>
                    <div class="user-info-item">
                        <div class="user-info-label">Роль</div>
                        <div class="user-info-value">
                            <?php if ($isMainAdmin): ?>
                                <span style="display: inline-block; padding: 4px 12px; background: #FF6B00; color: #FFFFFF; border-radius: 20px; font-size: 0.85rem;">Главный администратор</span>
                            <?php else: ?>
                                <span style="display: inline-block; padding: 4px 12px; background: #E0E0E0; color: #666666; border-radius: 20px; font-size: 0.85rem;">Администратор</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-info-item">
                        <div class="user-info-label">Дата регистрации</div>
                        <div class="user-info-value"><?php echo date('d.m.Y', strtotime($fullUserInfo['created_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Изменение логина -->
            <div class="card">
                <h3>✏️ Изменить логин</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_username">
                    <div class="form-group">
                        <label for="current_username_display">Текущий логин</label>
                        <input type="text" id="current_username_display" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="new_username">Новый логин</label>
                        <input type="text" id="new_username" name="new_username" required minlength="3" placeholder="Введите новый логин (минимум 3 символа)">
                    </div>
                    <button type="submit" class="btn-primary">Изменить логин</button>
                </form>
            </div>
            
            <div class="divider"></div>
            
            <!-- Изменение пароля -->
            <div class="card">
                <h3>🔒 Изменить пароль</h3>
                <form method="POST" id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password">Текущий пароль</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Введите текущий пароль">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Новый пароль</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Введите новый пароль">
                        <div id="password_requirements" style="margin-top: 10px; padding: 10px; background: #F9F9F9; border-radius: 4px; font-size: 0.85rem;">
                            <strong>Требования к паролю:</strong>
                            <ul style="margin: 5px 0 0 20px; padding: 0;" id="requirements_list">
                                <li id="req_length" style="color: #666;">Минимум 12 символов</li>
                                <li id="req_uppercase" style="color: #666;">Хотя бы одна заглавная буква (A-Z, А-Я)</li>
                                <li id="req_lowercase" style="color: #666;">Хотя бы одна строчная буква (a-z, а-я)</li>
                                <li id="req_digit" style="color: #666;">Хотя бы одна цифра</li>
                                <li id="req_special" style="color: #666;">Хотя бы один специальный символ (!@#$%^&* и т.д.)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Подтверждение нового пароля</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Повторите новый пароль">
                    </div>
                    <button type="submit" class="btn-primary" id="submitPasswordBtn" disabled>Изменить пароль</button>
                </form>
            </div>
        </main>
    </div>
    <script>
        // Валидация пароля в реальном времени для формы смены пароля
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const submitPasswordBtn = document.getElementById('submitPasswordBtn');
        
        const reqLength = document.getElementById('req_length');
        const reqUppercase = document.getElementById('req_uppercase');
        const reqLowercase = document.getElementById('req_lowercase');
        const reqDigit = document.getElementById('req_digit');
        const reqSpecial = document.getElementById('req_special');
        
        function validatePassword(password) {
            const validation = {
                length: password.length >= 12,
                uppercase: /[A-ZА-ЯЁ]/u.test(password),
                lowercase: /[a-zа-яё]/u.test(password),
                digit: /[0-9]/.test(password),
                special: /[^A-Za-z0-9А-Яа-яЁё]/.test(password)
            };
            
            // Обновляем визуальное состояние требований
            updateRequirement(reqLength, validation.length);
            updateRequirement(reqUppercase, validation.uppercase);
            updateRequirement(reqLowercase, validation.lowercase);
            updateRequirement(reqDigit, validation.digit);
            updateRequirement(reqSpecial, validation.special);
            
            return Object.values(validation).every(v => v);
        }
        
        function updateRequirement(element, isValid) {
            if (isValid) {
                element.style.color = '#4CAF50';
                element.innerHTML = '✓ ' + element.textContent.replace('✓ ', '');
            } else {
                element.style.color = '#666';
                element.innerHTML = element.textContent.replace('✓ ', '');
            }
        }
        
        function checkPasswordForm() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            const isPasswordValid = validatePassword(newPassword);
            const passwordsMatch = newPassword && confirmPassword && newPassword === confirmPassword;
            
            submitPasswordBtn.disabled = !(isPasswordValid && passwordsMatch);
        }
        
        newPasswordInput.addEventListener('input', checkPasswordForm);
        confirmPasswordInput.addEventListener('input', checkPasswordForm);
        
        // Предотвращаем отправку формы если пароль не валиден
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = newPasswordInput.value;
            if (!validatePassword(newPassword)) {
                e.preventDefault();
                alert('Пароль не соответствует требованиям безопасности');
            }
        });
    </script>
</body>
</html>
