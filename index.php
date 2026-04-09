<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Международный конкурс архитектурной графики «Пирамида»</title>
    <meta name="description" content="I Международный конкурс архитектурной графики и онлайн-выставка творческих работ и проектов">
    <meta name="keywords" content="конкурс, архитектурная графика, архитектурный рисунок, СибАДИ, Пирамида">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="hero">
            <div class="container">
                <h1>I Международный конкурс архитектурной графики и онлайн-выставка «Пирамида»</h1>
                <p class="hero-subtitle">Для студентов ВУЗов, техникумов и колледжей</p>
                <p class="hero-dates">📅 10 апреля – 18 мая 2026 г.</p>
                <a href="register.php" class="btn btn-large">Подать заявку</a>
                <a href="requirements.php" class="btn btn-outline">Требования к работам</a>
            </div>
        </section>
        
        <section class="section about">
            <div class="container">
                <h2>О конкурсе</h2>
                <p>Конкурс «Пирамида» направлен на выявление, поощрение и продвижение талантливых авторов в области архитектурной графики через организацию конкурсных и выставочных мероприятий.</p>
                
                <div class="goals-grid">
                    <div class="goal-card">
                        <h3>🎯 Цель</h3>
                        <p>Поддержка практики рисунка в профессии архитектора и популяризация архитектурного рисунка как вида искусства</p>
                    </div>
                    <div class="goal-card">
                        <h3>🤝 Задачи</h3>
                        <p>Развитие творческого сообщества, платформы для самопрезентации и профессионального обмена</p>
                    </div>
                    <div class="goal-card">
                        <h3>🔍 Исследование</h3>
                        <p>Изучение роли изображения архитектуры в современной художественной и визуальной культуре</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="section timeline">
            <div class="container">
                <h2>Этапы проведения</h2>
                <div class="timeline-grid">
                    <div class="timeline-item">
                        <div class="timeline-date">10 апреля – 10 мая</div>
                        <div class="timeline-title">1 этап: Приём заявок и работ</div>
                        <div class="timeline-desc">Отправка заявок и работ на сайт Конкурса ПИРАМИДА</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">11 мая – 14 мая</div>
                        <div class="timeline-title">2 этап: Работа жюри</div>
                        <div class="timeline-desc">Работа жюри Конкурса</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">15 мая – 18 мая</div>
                        <div class="timeline-title">3 этап: Онлайн-выставка</div>
                        <div class="timeline-desc">Проведение онлайн выставки на сайте Конкурса ПИРАМИДА по номинациям и разделам</div>
                    </div>
                </div>
                <p class="note">* Даты могут быть изменены. Следите за обновлениями на сайте.</p>
            </div>
        </section>
        
        <section class="section nominations">
            <div class="container">
                <h2>Номинации конкурса</h2>
                <div class="nominations-grid">
                    <div class="nomination-card">
                        <h3>Архитектурная композиция</h3>
                        <ul>
                            <li>Абстрактная</li>
                            <li>Жанровая</li>
                            <li>Шрифтовая</li>
                        </ul>
                    </div>
                    <div class="nomination-card">
                        <h3>Художественно-проектная графика</h3>
                        <ul>
                            <li>Клаузура</li>
                            <li>Рисунок к проекту</li>
                            <li>Открытка</li>
                            <li>Паттерн</li>
                        </ul>
                    </div>
                    <div class="nomination-card">
                        <h3>Рисунок с натуры</h3>
                        <ul>
                            <li>Архитектурный пейзаж</li>
                        </ul>
                    </div>
                    <div class="nomination-card">
                        <h3>Фотография</h3>
                        <ul>
                            <li>Фотопроект</li>
                            <li>Фотоколлаж</li>
                        </ul>
                    </div>
                </div>
                <div class="nominations-actions">
                    <a href="requirements.php" class="btn">Подробнее о требованиях</a>
                </div>
            </div>
        </section>
        
        <section class="section cta">
            <div class="container">
                <h2>Готовы участвовать?</h2>
                <p>Зарегистрируйтесь сейчас и получите возможность представить свои работы широкой аудитории</p>
                <a href="register.php" class="btn">Подать заявку</a>
                <a href="polozhenie.php" class="btn btn-outline">Положение конкурса</a>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>