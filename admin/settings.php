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

// Получаем полную информацию о пользователе включая роль
$fullUserInfo = getUserById($currentUser['id']);
$isMainAdmin = $fullUserInfo && $fullUserInfo['role'] === 'main';

// Если не главный администратор - перенаправляем
if (!$isMainAdmin) {
    header('Location: applications.php');
    exit;
}

$message = '';
$messageType = '';

// Обработка сообщений из URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = 'success';
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = createAdminUser($username, $password);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
    } elseif ($action === 'delete_admin') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        if ($adminId > 0 && $adminId !== $currentUser['id']) {
            $result = deleteAdminUser($adminId);
            if ($result['success']) {
                // Перезагружаем страницу после успешного удаления
                header('Location: settings.php?msg=' . urlencode($result['message']));
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
        
    } elseif ($action === 'change_role') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $newRole = $_POST['role'] ?? 'regular';
        // Только главный администратор может менять роли и только на regular
        if ($isMainAdmin && $adminId > 0 && $adminId !== $currentUser['id']) {
            // Нельзя понизить роль главного администратора
            $targetUser = getUserById($adminId);
            if ($targetUser && $targetUser['role'] === 'main') {
                $message = 'Нельзя изменить роль главного администратора';
                $messageType = 'error';
            } else {
                $result = changeAdminRole($adminId, 'regular');
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
        }
        
    } elseif ($action === 'change_username') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $newUsername = trim($_POST['new_username'] ?? '');
        if ($adminId > 0 && ($adminId === $currentUser['id'] || $isMainAdmin)) {
            $result = changeUsername($adminId, $newUsername);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
        
    } elseif ($action === 'change_password') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        if ($adminId > 0 && ($adminId === $currentUser['id'] || $isMainAdmin)) {
            $result = changePassword($adminId, $newPassword);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
}

// Получаем список всех администраторов
$admins = getAllAdmins();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администраторы — Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admins-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .admins-table th, .admins-table td { padding: 12px; text-align: left; border-bottom: 1px solid #E0E0E0; }
        .admins-table th { background: #F5F5F5; font-weight: 600; color: #1A1A1A; }
        .admins-table tr:hover { background: #F9F9F9; }
        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .role-badge.main { background: #FF6B00; color: #FFFFFF; }
        .role-badge.regular { background: #E0E0E0; color: #666666; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-badge.active { background: #4CAF50; color: #FFFFFF; }
        .status-badge.inactive { background: #9E9E9E; color: #FFFFFF; }
        .card { background: #FFFFFF; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; margin-bottom: 20px; color: #1A1A1A; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 8px; color: #1A1A1A; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #E0E0E0; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #FFFFFF; margin: 10% auto; padding: 30px; border-radius: 8px; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; }
        .close { cursor: pointer; font-size: 1.5rem; color: #999; }
        .close:hover { color: #333; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>👥 Управление администраторами</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Список администраторов -->
            <div class="card">
                <h3>Все администраторы</h3>
                <table class="admins-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $admin['role']; ?>">
                                        <?php echo $admin['role'] === 'main' ? 'Главный' : 'Обычный'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Активен' : 'Заблокирован'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <?php if ($admin['id'] !== $currentUser['id']): ?>
                                        <button class="btn-sm btn-secondary" onclick="openEditModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">Изменить</button>
                                        <?php if ($isMainAdmin): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены?');">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn-sm btn-danger">Удалить</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Текущий пользователь</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Создание нового администратора -->
            <div class="card">
                <h3>➕ Создать нового администратора</h3>
                <form method="POST" id="createAdminForm">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_username">Логин *</label>
                            <input type="text" id="new_username" name="username" required minlength="3" placeholder="Минимум 3 символа">
                        </div>
                        <div class="form-group">
                            <label for="new_password">Пароль *</label>
                            <input type="password" id="new_password" name="password" required placeholder="Введите пароль">
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
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_role">Роль *</label>
                            <select id="new_role" name="role">
                                <option value="regular">Обычный администратор</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-sm btn-primary" id="submitCreateBtn" disabled>Создать администратора</button>
                </form>
            </div>
        </main>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Редактирование администратора</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Текущий логин</label>
                    <input type="text" id="edit_current_username" disabled>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_new_username">Новый логин</label>
                    <input type="text" id="edit_new_username" name="new_username" minlength="3" placeholder="Оставьте пустым, чтобы не менять">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_new_password">Новый пароль</label>
                    <input type="password" id="edit_new_password" name="new_password" placeholder="Оставьте пустым, чтобы не менять">
                    <div id="edit_password_requirements" style="margin-top: 10px; padding: 10px; background: #F9F9F9; border-radius: 4px; font-size: 0.85rem; display: none;">
                        <strong>Требования к паролю:</strong>
                        <ul style="margin: 5px 0 0 20px; padding: 0;" id="edit_requirements_list">
                            <li id="edit_req_length" style="color: #666;">Минимум 12 символов</li>
                            <li id="edit_req_uppercase" style="color: #666;">Хотя бы одна заглавная буква (A-Z, А-Я)</li>
                            <li id="edit_req_lowercase" style="color: #666;">Хотя бы одна строчная буква (a-z, а-я)</li>
                            <li id="edit_req_digit" style="color: #666;">Хотя бы одна цифра</li>
                            <li id="edit_req_special" style="color: #666;">Хотя бы один специальный символ (!@#$%^&* и т.д.)</li>
                        </ul>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="action" value="change_username" class="btn-sm btn-primary">Изменить логин</button>
                    <button type="submit" name="action" value="change_password" class="btn-sm btn-primary" id="editSubmitPasswordBtn" disabled>Изменить пароль</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(id, username) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_current_username').value = username;
            document.getElementById('edit_new_username').value = '';
            document.getElementById('edit_new_password').value = '';
            document.getElementById('edit_password_requirements').style.display = 'none';
            document.getElementById('editSubmitPasswordBtn').disabled = true;
            // Сброс стилей требований
            resetEditRequirements();
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Закрытие по клику вне модального окна
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        
        // Функция сброса стилей требований для модального окна
        function resetEditRequirements() {
            const reqLength = document.getElementById('edit_req_length');
            const reqUppercase = document.getElementById('edit_req_uppercase');
            const reqLowercase = document.getElementById('edit_req_lowercase');
            const reqDigit = document.getElementById('edit_req_digit');
            const reqSpecial = document.getElementById('edit_req_special');
            
            [reqLength, reqUppercase, reqLowercase, reqDigit, reqSpecial].forEach(el => {
                if (el) {
                    el.style.color = '#666';
                    el.innerHTML = el.textContent.replace('✓ ', '');
                }
            });
        }
        
        // Валидация пароля в реальном времени для формы создания администратора
        const newPasswordInput = document.getElementById('new_password');
        const submitCreateBtn = document.getElementById('submitCreateBtn');
        
        if (newPasswordInput && submitCreateBtn) {
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
                if (!element) return;
                if (isValid) {
                    element.style.color = '#4CAF50';
                    element.innerHTML = '✓ ' + element.textContent.replace('✓ ', '');
                } else {
                    element.style.color = '#666';
                    element.innerHTML = element.textContent.replace('✓ ', '');
                }
            }
            
            newPasswordInput.addEventListener('input', function() {
                const isPasswordValid = validatePassword(newPasswordInput.value);
                submitCreateBtn.disabled = !isPasswordValid;
            });
            
            // Предотвращаем отправку формы если пароль не валиден
            document.getElementById('createAdminForm').addEventListener('submit', function(e) {
                const password = newPasswordInput.value;
                if (!validatePassword(password)) {
                    e.preventDefault();
                    alert('Пароль не соответствует требованиям безопасности');
                }
            });
        }
        
        // Валидация пароля в модальном окне редактирования
        const editPasswordInput = document.getElementById('edit_new_password');
        const editSubmitPasswordBtn = document.getElementById('editSubmitPasswordBtn');
        const editPasswordRequirements = document.getElementById('edit_password_requirements');
        
        if (editPasswordInput && editSubmitPasswordBtn) {
            const editReqLength = document.getElementById('edit_req_length');
            const editReqUppercase = document.getElementById('edit_req_uppercase');
            const editReqLowercase = document.getElementById('edit_req_lowercase');
            const editReqDigit = document.getElementById('edit_req_digit');
            const editReqSpecial = document.getElementById('edit_req_special');
            
            function validateEditPassword(password) {
                // Если поле пустое - показываем кнопку активной (пароль менять не обязательно)
                if (!password || password.trim() === '') {
                    editPasswordRequirements.style.display = 'none';
                    editSubmitPasswordBtn.disabled = false;
                    return true;
                }
                
                // Показываем требования только если начали вводить пароль
                editPasswordRequirements.style.display = 'block';
                
                const validation = {
                    length: password.length >= 12,
                    uppercase: /[A-ZА-ЯЁ]/u.test(password),
                    lowercase: /[a-zа-яё]/u.test(password),
                    digit: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9А-Яа-яЁё]/.test(password)
                };
                
                // Обновляем визуальное состояние требований
                updateEditRequirement(editReqLength, validation.length);
                updateEditRequirement(editReqUppercase, validation.uppercase);
                updateEditRequirement(editReqLowercase, validation.lowercase);
                updateEditRequirement(editReqDigit, validation.digit);
                updateEditRequirement(editReqSpecial, validation.special);
                
                return Object.values(validation).every(v => v);
            }
            
            function updateEditRequirement(element, isValid) {
                if (!element) return;
                if (isValid) {
                    element.style.color = '#4CAF50';
                    element.innerHTML = '✓ ' + element.textContent.replace('✓ ', '');
                } else {
                    element.style.color = '#666';
                    element.innerHTML = element.textContent.replace('✓ ', '');
                }
            }
            
            editPasswordInput.addEventListener('input', function() {
                const isPasswordValid = validateEditPassword(editPasswordInput.value);
                editSubmitPasswordBtn.disabled = !isPasswordValid;
            });
            
            // Предотвращаем отправку формы если пароль не валиден
            document.getElementById('editForm').addEventListener('submit', function(e) {
                const action = this.querySelector('button[name="action"]:focus')?.value;
                if (action === 'change_password' && editPasswordInput.value.trim() !== '') {
                    const password = editPasswordInput.value;
                    if (!validateEditPassword(password)) {
                        e.preventDefault();
                        alert('Пароль не соответствует требованиям безопасности');
                    }
                }
            });
        }
    </script>
</body>
</html>
