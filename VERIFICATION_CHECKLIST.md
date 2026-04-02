# ✅ Чеклист верификации рефакторинга

## 1. Структурные проверки

### Пути и константы
- [x] Все пути к файлам/директориям берутся из констант `config.php`
  - `UPLOAD_DIR_ORIGINALS`, `UPLOAD_DIR_GALLERY`
  - `CACHE_FONTS_DIR`, `CACHE_TEMP_DIR`
  - `LOGS_DIR`, `ERROR_LOG_FILE`
- [x] Нет абсолютных путей вида `/home/...` в коде
- [x] Константы можно переопределить до подключения `config.php`

### Повторное использование компонентов
- [x] `includes/header.php` / `includes/footer.php` подключаются, а не копируются
- [x] `admin/includes/sidebar.php` содержит всё меню

### Удаление временных фиксов
- [ ] Удалить `test-all.php` (тестовый файл)
- [x] Нет `error_reporting(E_ALL)` с `display_errors=1` в production

---

## 2. Функциональные проверки

### Загрузка файлов (`register.php`)
```bash
# Проверка после отправки формы
ls -la uploads/originals/  # Должен появиться новый файл
ls -la uploads/gallery/    # Должно появиться превью
```

### Генерация сертификатов (`generate-certificate.php`)
```bash
# Проверка кэша dompdf
ls -la cache/fonts/   # Кэш шрифтов
ls -la cache/temp/    # Временные файлы
```

### Модерация (`admin/moderation.php`)
```bash
# Проверка удаления работы
# Файлы должны удалиться из uploads/originals/ и uploads/gallery/
```

### Экспорт (`admin/export.php`)
```bash
# Проверка ZIP-экспорта
# Архив должен создаваться во временной директории и отправляться пользователю
```

---

## 3. Проверки безопасности

### SQL-инъекции
- [x] Все запросы используют prepared statements (PDO)
- [x] Нет прямой подстановки `$_GET`/`$_POST` в SQL

### XSS защита
- [x] Все пользовательские данные при выводе обернуты в `htmlspecialchars()`

### Загрузка файлов
- [x] Проверка расширения + MIME + размера
- [x] Имя файла генерируется случайно (`uniqid()`)

### Сессии
- [x] Сессии хранятся в БД (DatabaseSessionHandler)
- [x] `session.use_only_cookies = 1`
- [x] `session.cookie_httponly = 1`
- [x] `session.cookie_samesite = 'Strict'`

---

## 4. Проверки для production

### Ошибки и логирование
```php
// В config.php
APP_ENV = 'production'  // display_errors = 0
APP_ENV = 'local'       // display_errors = 1
```

- [x] При `APP_ENV=production` ошибки не выводятся в браузер
- [x] Ошибки записываются в `logs/php_errors.log`

### Сессии без session.save_path
- [x] Используется DatabaseSessionHandler
- [x] Нет зависимости от системного `/tmp`

### Dompdf
- [x] Временные файлы пишутся в `cache/temp/`
- [x] Кэш шрифтов в `cache/fonts/`

---

## 5. Документация

- [x] `DEPLOYMENT.md` — руководство по развёртыванию
- [x] `REFACTORING_SUMMARY.md` — описание изменений
- [x] `VERIFICATION_CHECKLIST.md` — этот файл

---

## 6. Автоматическая проверка (скрипт)

```bash
#!/bin/bash
# verify.sh

echo "=== Проверка структуры ==="
grep -r "UPLOAD_DIR_ORIGINALS" includes/ admin/ *.php | wc -l
grep -r "CACHE_FONTS_DIR" includes/ | wc -l

echo "=== Проверка на хардкод путей ==="
grep -rn "/home/" --include="*.php" . | grep -v vendor | grep -v DEPLOYMENT

echo "=== Проверка директоров ==="
ls -la uploads/originals/ uploads/gallery/ cache/ logs/

echo "=== Проверка прав ==="
find uploads cache logs -type d -exec ls -ld {} \;
```

---

## 7. Итоговый статус

| Категория | Статус |
|-----------|--------|
| Конфигурация путей | ✅ Готово |
| Переиспользование компонентов | ✅ Готово |
| Безопасность (SQL, XSS, файлы) | ✅ Готово |
| Сессии через БД | ✅ Готово |
| Логирование | ✅ Готово |
| Поддержка local/production | ✅ Готово |
| Документация | ✅ Готово |
| Удаление тестовых файлов | ⏳ Требуется (test-all.php) |

---

## 8. Действия перед деплоем

1. Удалить тестовые файлы:
   ```bash
   rm test-all.php
   ```

2. Создать `.htaccess` для защиты директорий:
   ```bash
   echo "Deny from all" > cache/.htaccess
   echo "Deny from all" > logs/.htaccess
   ```

3. Обновить `includes/db.php` с учётными данными production БД

4. Установить права:
   ```bash
   chmod 755 uploads/ cache/ logs/
   chown www-data:www-data uploads/ cache/ logs/
   ```
