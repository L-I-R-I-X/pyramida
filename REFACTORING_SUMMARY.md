# Рефакторинг: Гибкая настройка путей записи

## Обзор изменений

Все операции записи (сессии, загрузки, кэш) теперь используют конфигурируемые пути внутри проекта.

## Изменённые файлы

### 1. `includes/config.php` — Центральный конфиг путей

**Добавленные константы:**
```php
UPLOAD_DIR_ORIGINALS  // ./uploads/originals/
UPLOAD_DIR_GALLERY    // ./uploads/gallery/
CACHE_DIR             // ./cache/
CACHE_SESSIONS_DIR    // ./cache/sessions/
CACHE_FONTS_DIR       // ./cache/fonts/
CACHE_TEMP_DIR        // ./cache/temp/
CACHE_CERTIFICATES_DIR// ./cache/certificates/
LOGS_DIR              // ./logs/
ERROR_LOG_FILE        // ./logs/php_errors.log
```

**Автоматическое создание директорий:**
При подключении config.php все необходимые папки создаются автоматически с правами 0755.

### 2. `includes/certificate.php` — Генерация PDF

**Изменения:**
- Использует `CACHE_FONTS_DIR` и `CACHE_TEMP_DIR` вместо жёстко закодированных путей

### 3. `admin/moderation.php` — Модерация работ

**Изменения:**
- Удаление файлов: использует `UPLOAD_DIR_GALLERY` и `UPLOAD_DIR_ORIGINALS`
- Предпросмотр: использует те же константы

### 4. `admin/export.php` — Экспорт данных

**Изменения:**
- Экспорт ZIP: использует `UPLOAD_DIR_ORIGINALS` для доступа к файлам работ

### 5. `gallery.php` — Галерея работ

**Изменения:**
- Проверка существования файла: `UPLOAD_DIR_GALLERY`
- URL для `<img>`: относительный путь `'uploads/gallery/'`

### 6. `winners.php` — Таблица победителей

**Изменения:**
- Аналогично gallery.php: разделение пути к файлу и URL

### 7. `install.php` — Скрипт установки

**Изменения:**
- Добавлен `require_once __DIR__ . '/includes/config.php'` в начало
- Создание директорий использует новые константы

## Структура директорий

```
/workspace/
├── uploads/
│   ├── originals/      # Оригиналы загруженных работ
│   └── gallery/        # Обработанные изображения (thumbnails)
├── cache/
│   ├── sessions/       # (резерв) для файловых сессий
│   ├── fonts/          # Кэш шрифтов dompdf
│   ├── temp/           # Временные файлы dompdf
│   └── certificates/   # (резерв) для сгенерированных сертификатов
└── logs/
    └── php_errors.log  # Лог ошибок PHP
```

## Настройка для разных окружений

### Local (разработка)
Пути по умолчанию уже настроены относительно корня проекта:
```php
$basePath = __DIR__ . '/..';
define('UPLOAD_DIR_ORIGINALS', $basePath . '/uploads/originals/');
```

### Production (хостинг)
Для изменения путей достаточно переопределить константы **до** подключения config.php:

```php
// В начале index.php или в отдельном env-файле
define('UPLOAD_DIR_ORIGINALS', '/home/pyramida/web/pyramida.sibadi.org/uploads/originals/');
define('UPLOAD_DIR_GALLERY', '/home/pyramida/web/pyramida.sibadi.org/uploads/gallery/');
define('CACHE_DIR', '/home/pyramida/web/pyramida.sibadi.org/cache/');
define('LOGS_DIR', '/home/pyramida/web/pyramida.sibadi.org/logs/');

require_once 'includes/config.php';
```

## Сессии

Проект использует **DatabaseSessionHandler** — хранение сессий в БД MySQL.

**Преимущества:**
- Не зависит от `session.save_path` в php.ini
- Работает даже если у www-data нет прав на `/tmp` или системные директории
- Сессии сохраняются при балансировке нагрузки (несколько серверов → одна БД)

**Таблица `sessions` создаётся автоматически** при установке или первом подключении к БД.

## Права доступа

Рекомендуемые права для production:
```bash
chown -R www-data:www-data /path/to/site/uploads
chown -R www-data:www-data /path/to/site/cache
chown -R www-data:www-data /path/to/site/logs
chmod -R 755 /path/to/site/uploads
chmod -R 755 /path/to/site/cache
chmod -R 755 /path/to/site/logs
```

## Чеклист после деплоя

- [ ] Все директории (`uploads/`, `cache/`, `logs/`) существуют
- [ ] Права доступа установлены (755, владелец www-data)
- [ ] `includes/config.php` подключается во всех точках входа
- [ ] Тестовая загрузка файла работает
- [ ] Авторизация администратора работает
- [ ] Генерация PDF-сертификатов работает
- [ ] Логи пишутся в `logs/php_errors.log`

## Обратная совместимость

Все изменения обратно совместимы:
- Константы определены через `if (!defined(...))` — можно переопределить заранее
- Старые пути в БД не изменились (хранятся только имена файлов)
- DatabaseSessionHandler уже использовался ранее
