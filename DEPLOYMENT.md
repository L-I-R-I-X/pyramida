# 📦 Руководство по развёртыванию проекта «Пирамида»

## ✅ Предварительные требования

- PHP 8.2+
- MySQL/MariaDB 5.7+
- Apache 2.4+ с mod_rewrite
- Composer (для установки зависимостей)
- Права на запись для www-data в директории проекта

---

## 🚀 Быстрый старт (Local)

### 1. Установка зависимостей
```bash
cd /path/to/project
composer install --no-dev
```

### 2. Настройка базы данных
Создайте базу данных и пользователя:
```sql
CREATE DATABASE pyramida_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pyramida_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON pyramida_db.* TO 'pyramida_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Запуск установщика
Откройте в браузере: `http://localhost/pyramida/install.php`

### 4. Проверка работы
- Главная страница: `http://localhost/pyramida/`
- Админ-панель: `http://localhost/pyramida/admin/login.php`

---

## 🌐 Развёртывание на хостинге (Production)

### 1. Подготовка окружения

#### Вариант A: Переопределение констант в начале index.php
```php
<?php
// Переопределить пути до подключения config.php
define('APP_ENV', 'production');
define('BASE_URL', 'https://pyramida.sibadi.org/');
define('SITE_URL', 'https://pyramida.sibadi.org/');

// Опционально: абсолютные пути для записи
define('UPLOAD_DIR_ORIGINALS', '/home/pyramida/web/pyramida.sibadi.org/uploads/originals/');
define('UPLOAD_DIR_GALLERY', '/home/pyramida/web/pyramida.sibadi.org/uploads/gallery/');
define('CACHE_DIR', '/home/pyramida/web/pyramida.sibadi.org/cache/');
define('LOGS_DIR', '/home/pyramida/web/pyramida.sibadi.org/logs/');

require_once 'includes/config.php';
require_once 'includes/db.php';
// ... остальной код
```

#### Вариант B: Через переменные окружения (рекомендуется)
В `.htaccess` или конфиге Apache:
```apache
SetEnv APP_ENV production
SetEnv BASE_URL https://pyramida.sibadi.org/
SetEnv SITE_URL https://pyramida.sibadi.org/
```

### 2. Настройка прав доступа

```bash
# Владелец файлов (ваш пользователь)
chown -R pyramida:pyramida /home/pyramida/web/pyramida.sibadi.org/

# Права на запись для www-data
chmod 755 /home/pyramida/web/pyramida.sibadi.org/uploads/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/uploads/originals/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/uploads/gallery/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/cache/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/cache/fonts/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/cache/temp/
chmod 755 /home/pyramida/web/pyramida.sibadi.org/logs/

# Или добавить www-data в группу владельца
usermod -aG pyramida www-data
```

### 3. Обновление учётных данных БД

Отредактируйте `/workspace/includes/db.php`:
```php
$host = 'localhost';
$dbname = 'pyramida_db';
$username = 'pyramida_user';
$password = 'your_secure_password';
```

### 4. Запуск установщика
Откройте: `https://pyramida.sibadi.org/install.php`

После успешной установки **удалите файл install.php**:
```bash
rm /home/pyramida/web/pyramida.sibadi.org/install.php
```

---

## 🔧 Конфигурация путей

### Константы для переопределения

| Константа | Описание | Значение по умолчанию |
|-----------|----------|----------------------|
| `APP_ENV` | Окружение (`local`/`production`) | `production` |
| `BASE_URL` | Базовый URL сайта | Автоопределение |
| `SITE_URL` | URL для редиректов | `BASE_URL` |
| `UPLOAD_DIR_ORIGINALS` | Путь к оригиналам работ | `./uploads/originals/` |
| `UPLOAD_DIR_GALLERY` | Путь к галерее | `./uploads/gallery/` |
| `CACHE_DIR` | Корневая папка кэша | `./cache/` |
| `CACHE_FONTS_DIR` | Кэш шрифтов dompdf | `./cache/fonts/` |
| `CACHE_TEMP_DIR` | Временные файлы dompdf | `./cache/temp/` |
| `LOGS_DIR` | Папка для логов | `./logs/` |

### Пример для production

```php
// В начале каждого entry-point файла (index.php, admin/*.php, etc.)
<?php
define('APP_ENV', 'production');
define('BASE_URL', 'https://pyramida.sibadi.org/');

// Если пути абсолютные на хостинге
define('UPLOAD_DIR_ORIGINALS', '/home/pyramida/web/pyramida.sibadi.org/uploads/originals/');
define('UPLOAD_DIR_GALLERY', '/home/pyramida/web/pyramida.sibadi.org/uploads/gallery/');
define('CACHE_DIR', '/home/pyramida/web/pyramida.sibadi.org/cache/');
define('LOGS_DIR', '/home/pyramida/web/pyramida.sibadi.org/logs/');

require_once 'includes/config.php';
```

---

## 🔐 Безопасность

### 1. Удаление тестовых файлов
```bash
rm test-all.php
```

### 2. Защита директоров
Создайте `.htaccess` в чувствительных директориях:

**uploads/.htaccess:**
```apache
# Запрет выполнения PHP
<FilesMatch "\.(php|phtml|php3|php4|php5)$">
    Deny from all
</FilesMatch>
```

**cache/.htaccess:**
```apache
# Полный запрет доступа
Deny from all
```

**logs/.htaccess:**
```apache
# Полный запрет доступа
Deny from all
```

### 3. HTTPS (рекомендуется)
В `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📋 Чеклист миграции на хостинг

- [ ] Создать базу данных и пользователя
- [ ] Обновить учётные данные в `includes/db.php`
- [ ] Переопределить константы путей (если нужны абсолютные)
- [ ] Установить права 755 на `uploads/`, `cache/`, `logs/`
- [ ] Запустить `install.php`
- [ ] Удалить `install.php` после установки
- [ ] Удалить `test-all.php`
- [ ] Проверить загрузку файлов (register.php)
- [ ] Проверить вход в админ-панель
- [ ] Проверить генерацию сертификатов
- [ ] Проверить экспорт ZIP/CSV
- [ ] Включить HTTPS (если доступен SSL)

---

## 🐛 Отладка

### Режим отладки (local)
```bash
export APP_ENV=local
```
Ошибки будут выводиться в браузер.

### Production логирование
Ошибки записываются в `logs/php_errors.log`.

Для просмотра в реальном времени:
```bash
tail -f /path/to/logs/php_errors.log
```

### Проверка путей
Создайте тестовый скрипт для проверки:
```php
<?php
require_once 'includes/config.php';
echo "UPLOAD_DIR_ORIGINALS: " . UPLOAD_DIR_ORIGINALS . "<br>";
echo "Проверка записи: " . (is_writable(UPLOAD_DIR_ORIGINALS) ? 'OK' : 'FAIL') . "<br>";
```

---

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи: `logs/php_errors.log`
2. Убедитесь, что все директории доступны на запись
3. Проверьте подключение к БД
4. Убедитесь, что `session.save_path` не требуется (сессии хранятся в БД)
