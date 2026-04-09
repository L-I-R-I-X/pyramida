<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';


$success = isset($_GET['success']);
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];


unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подать заявку — Конкурс «Пирамида»</title>
    <meta name="description" content="Регистрация участника конкурса архитектурной графики">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Подать заявку</h1>
                <p>Заполните форму для участия в конкурсе</p>
            </div>
        </section>
        
        <section class="section register-section">
            <div class="container">
                <div class="register-wrapper">
                    <div class="register-info">
                        <h2>📋 Информация об участии</h2>
                        
                        <ul class="requirements-list">
                            <li><strong>Участники:</strong> студенты архитектурных и художественных учебных заведений</li>
                            <li><strong>Взнос:</strong> участие бесплатное</li>
                            <li><strong>Формат:</strong> JPG, JPEG (от 150 до 300 dpi)</li>
                            <li><strong>Размер:</strong> от 1 Мб до 10 Мб</li>
                            <li><strong>Срок подачи:</strong> до 31 марта 2026</li>
                        </ul>
                        
                        <div class="info-block">
                            <h3>📧 Контакты для вопросов</h3>
                            <p>Email: <a href="mailto:shipilina.ev@sibadi.org">shipilina.ev@sibadi.org</a></p>
                            <p>Телефон: +7 (913) 623-48-34</p>
                        </div>
                        
                        <div class="info-block">
                            <h3>⚠️ Важно</h3>
                            <p>После подведения итогов дипломы и сертификаты участников будут доступны для скачивания на сайте во вкладках «Победители» и Участники»</p>
                        </div>
                    </div>
                    
                    <div class="register-form-container">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h3>✅ Заявка успешно отправлена!</h3>
                                <p>Ваша работа принята на рассмотрение жюри.</p>
                                <p>ID заявки: <strong><?php echo htmlspecialchars($_GET['id'] ?? ''); ?></strong></p>
                                <p>Файл работы: <strong><?php echo htmlspecialchars($_GET['file'] ?? ''); ?></strong></p>
                                <p>После подведения итогов дипломы и сертификаты участников будут доступны для скачивания на сайте во вкладках «Победители» и «Участники»</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <h3>❌ Ошибки в форме</h3>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="includes/upload.php" id="registerForm" class="register-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="fio">ФИО *</label>
                                <input type="text" id="fio" name="fio" required 
                                       value="<?php echo htmlspecialchars($formData['fio'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="educational_institution">Учебное заведение *</label>
                                <input type="text" id="educational_institution" name="educational_institution" required 
                                       value="<?php echo htmlspecialchars($formData['educational_institution'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="course">Курс *</label>
                                    <select id="course" name="course" required>
                                        <option value="">Выберите курс</option>
                                        <option value="1" <?php echo ($formData['course'] ?? '') === '1' ? 'selected' : ''; ?>>1 курс</option>
                                        <option value="2" <?php echo ($formData['course'] ?? '') === '2' ? 'selected' : ''; ?>>2 курс</option>
                                        <option value="3" <?php echo ($formData['course'] ?? '') === '3' ? 'selected' : ''; ?>>3 курс</option>
                                        <option value="4" <?php echo ($formData['course'] ?? '') === '4' ? 'selected' : ''; ?>>4 курс</option>
                                        <option value="5" <?php echo ($formData['course'] ?? '') === '5' ? 'selected' : ''; ?>>5 курс</option>
                                        <option value="6" <?php echo ($formData['course'] ?? '') === '6' ? 'selected' : ''; ?>>6 курс</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Телефон</label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="nomination_section">Номинация и раздел *</label>
                                <select id="nomination_section" name="nomination_section" required>
                                    <option value="">Выберите номинацию и раздел</option>
                                    <?php
                                    $savedNominationSection = $formData['nomination_section'] ?? '';
                                    
                                    $options = [
                                        'arch_composition_abstract' => 'Архитектурная композиция: Абстрактная',
                                        'arch_composition_genre' => 'Архитектурная композиция: Жанровая',
                                        'arch_composition_typographic' => 'Архитектурная композиция: Шрифтовая',
                                        'art_graphics_clausura' => 'Художественно-проектная графика: Клаузура',
                                        'art_graphics_project_drawing' => 'Художественно-проектная графика: Рисунок к проекту',
                                        'art_graphics_postcard' => 'Художественно-проектная графика: Открытка',
                                        'art_graphics_pattern' => 'Художественно-проектная графика: Паттерн',
                                        'nature_drawing_landscape' => 'Рисунок с натуры: Архитектурный пейзаж',
                                        'photography_photo_project' => 'Фотография: Фотопроект',
                                        'photography_photo_collage' => 'Фотография: Фотоколлаж'
                                    ];
                                    
                                    foreach ($options as $value => $label):
                                        $selected = ($value === $savedNominationSection) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                            
                            <input type="hidden" id="nomination" name="nomination" value="">
                            <input type="hidden" id="section" name="section" value="">
                            
                            <div class="form-group">
                                <label for="work_title">Название работы *</label>
                                <input type="text" id="work_title" name="work_title" required 
                                       value="<?php echo htmlspecialchars($formData['work_title'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="work">Файл работы *</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" id="work" name="work" accept=".jpg,.jpeg" required>
                                    <div class="file-upload-hint">
                                        <span>JPG, JPEG</span>
                                        <span>1–10 Мб</span>
                                        <span>150–300 dpi</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="consent" required>
                                    <span>Я согласен(на) на обработку персональных данных и подтверждаю, что работа выполнена мной самостоятельно</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-large">Отправить заявку</button>
                                <a href="index.php" class="btn btn-outline btn-large">На главную</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>