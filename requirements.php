<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Требования к работам — Конкурс «Пирамида»</title>
    <meta name="description" content="Технические требования к оформлению конкурсных работ">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Требования к работам</h1>
                <p>Технические требования к оформлению файлов и подаче заявки</p>
            </div>
        </section>
        
        <section class="section requirements-section">
            <div class="container">
                <div class="requirements-content">
                    <div class="requirement-card">
                        <h2>📁 Формат файлов</h2>
                        <div class="requirement-detail">
                            <span class="label">Формат:</span>
                            <span class="value">JPG, JPEG</span>
                        </div>
                        <div class="requirement-detail">
                            <span class="label">Разрешение:</span>
                            <span class="value">от 150 dpi до 300 dpi</span>
                        </div>
                        <div class="requirement-detail">
                            <span class="label">Размер файла:</span>
                            <span class="value">от 1 Мб до 10 Мб</span>
                        </div>
                    </div>
                    
                    <div class="requirement-card">
                        <h2>📝 Наименование файлов</h2>
                        <div class="file-naming-example">
                            <p><strong>Система присваивает имя автоматически:</strong></p>
                            <code>work_УникальныйИдентификатор.jpg</code>
                            <p style="margin-top: 10px; font-size: 0.9rem; color: var(--color-gray);">
                                Вам не нужно переименовывать файл — система сделает это автоматически при загрузке
                            </p>
                        </div>
                    </div>
                    
                    <div class="requirement-card">
                        <h2>📋 Подача заявки</h2>
                        <ul>
                            <li>Заявка заполняется <strong>онлайн через форму на сайте</strong></li>
                            <li>Необходимо указать: ФИО, учебное заведение, курс, номинацию, раздел, название работы, email, телефон</li>
                            <li>Дать согласие на обработку персональных данных</li>
                            <li>Прикрепить файл работы в формате .jpeg</li>
                        </ul>
                    </div>
                    
                    <div class="requirement-card">
                        <h2>🖼️ Оформление работы</h2>
                        <ul>
                            <li>Работа должна быть выполнена в области архитектурной графики</li>
                            <li>Если работа состоит из нескольких листов — скомпоновать на одном листе</li>
                            <li>Одна работа в одном разделе номинации от одного участника</li>
                            <li>Групповые заявки и архивы не принимаются</li>
                        </ul>
                    </div>
                    
                    <div class="requirement-card highlight">
                        <h2>⚠️ Важно</h2>
                        <ul>
                            <li>Файл работы загружается <strong>напрямую в форму заявки</strong></li>
                            <li>Ссылка на работу в облаке <strong>не допускается</strong></li>
                            <li>Подача работы означает согласие с условиями конкурса</li>
                            <li>За авторство работы ответственность несёт лицо, подавшее заявку</li>
                            <li>Оргкомитет вправе не допускать работы низкого качества или не соответствующие тематике</li>
                        </ul>
                        <p class="note" style="margin-top: 15px; font-weight: 500;">
                            ПО ПРИНЯТЫМ РЕШЕНИЯМ ОРГАНИЗАЦИОННЫЙ КОМИТЕТ КОММЕНТАРИИ НЕ ПРЕДОСТАВЛЯЕТ!
                        </p>
                    </div>
                    
                    <div class="requirement-card">
                        <h2>🏆 Номинации и разделы</h2>
                        <table class="nominations-table">
                            <thead>
                                <tr>
                                    <th>Номинация</th>
                                    <th>Разделы</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="3"><strong>Архитектурная композиция</strong></td>
                                    <td>Абстрактная</td>
                                </tr>
                                <tr>
                                    <td>Жанровая</td>
                                </tr>
                                <tr>
                                    <td>Шрифтовая</td>
                                </tr>
                                <tr>
                                    <td rowspan="4"><strong>Художественно-проектная графика</strong></td>
                                    <td>Клаузура</td>
                                </tr>
                                <tr>
                                    <td>Рисунок к проекту</td>
                                </tr>
                                <tr>
                                    <td>Открытка</td>
                                </tr>
                                <tr>
                                    <td>Паттерн</td>
                                </tr>
                                <tr>
                                    <td rowspan="1"><strong>Рисунок с натуры</strong></td>
                                    <td>Архитектурный пейзаж</td>
                                </tr>
                                <tr>
                                    <td rowspan="2"><strong>Фотография</strong></td>
                                    <td>Фотопроект</td>
                                </tr>
                                <tr>
                                    <td>Фотоколлаж</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="requirements-actions">
                        <a href="register.php" class="btn btn-large">Подать заявку</a>
                        <a href="polozhenie.php" class="btn btn-outline">Положение конкурса</a>
                        <a href="index.php" class="btn btn-outline">На главную</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>