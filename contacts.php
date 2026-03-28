<?php
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $errorMessage = 'Заполните все обязательные поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Укажите корректный email';
    } else {
        $to = 'shipilina.ev@sibadi.org';
        $subject = 'Сообщение с сайта конкурса «Пирамида»';
        $body = "Имя: $name\nEmail: $email\n\nСообщение:\n$message";
        $headers = "From: no-reply@vuz.ru\r\nReply-To: $email\r\n";
        
        if (@mail($to, $subject, $body, $headers)) {
            $successMessage = 'Сообщение отправлено! Мы свяжемся с вами в ближайшее время.';
        } else {
            $errorMessage = 'Ошибка отправки. Попробуйте связаться по телефону.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты — Конкурс «Пирамида»</title>
    <meta name="description" content="Контактная информация организаторов 1 Международного конкурса архитектурной графики">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Контакты</h1>
                <p>Свяжитесь с организаторами конкурса</p>
            </div>
        </section>
        
        <section class="section contacts-section">
            <div class="container">
                <div class="contacts-grid">
                    <div class="contacts-info">
                        <h2>Контактная информация</h2>
                        
                        <div class="contact-item">
                            <h3>Организатор</h3>
                            <p>Кафедра архитектурно-конструктивного проектирования ФГБОУ ВО «СибАДИ»</p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Адрес</h3>
                            <p>644050, Сибирский федеральный округ, Омская область, г. Омск, ул. Петра Некрасова 10, каб. 317</p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Контактные лица</h3>
                            <p>Минеева Зоя Владимировна</p>
                            <p>Шипилина Евгения Владимировна</p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Телефон</h3>
                            <p><a href="tel:+79136234834">+7 (913) 623-48-34</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Email</h3>
                            <p><a href="mailto:shipilina.ev@sibadi.org">shipilina.ev@sibadi.org</a></p>
                        </div>
                        
                        <div class="contact-item">
                            <h3>Время работы</h3>
                            <p>Пн–Пт: 9:00 – 17:00 (по местному времени)</p>
                        </div>
                    </div>
                    
                    <div class="contacts-form-container">
                        <h2>Написать сообщение</h2>
                        
                        <?php if ($successMessage): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="contact-form">
                            <div class="form-group">
                                <label for="name">Ваше имя *</label>
                                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Сообщение *</label>
                                <textarea id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-large">Отправить</button>
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