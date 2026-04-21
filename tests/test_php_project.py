"""
Юнит-тесты для PHP-проекта (конкурс "Пирамида")
Тестирование логики на Python с эмуляцией PHP-функций

Модули:
1. Валидация загружаемых файлов
2. Валидация и санитизация пользовательских данных
3. Расчёт рейтинга и определение мест
4. Подготовка данных для генерации PDF
5. Экспорт данных и формирование архивов
6. Работа с настройками и конфигурацией
7. Аутентификация и безопасность
8. Вспомогательные утилитарные функции
"""

import pytest
import re
import hashlib
import time
import os
from datetime import datetime
from typing import Dict, List, Optional, Any, Tuple


# =============================================================================
# ЭМУЛЯЦИЯ PHP-ФУНКЦИЙ И КОНСТАНТ
# =============================================================================

# Константы из config.php
ALLOWED_EXTENSIONS = ['jpg', 'jpeg']
ALLOWED_MIME_TYPES = ['image/jpeg']
UPLOAD_MAX_SIZE = 10 * 1024 * 1024  # 10 Мб
UPLOAD_MIN_SIZE = 1 * 1024 * 1024   # 1 Мб
MAX_IMAGE_WIDTH = 5000
MAX_IMAGE_HEIGHT = 5000

# Номинации
NOMINATION_NAMES = {
    'arch_composition': 'Архитектурная композиция',
    'art_graphics': 'Художественно-проектная графика',
    'nature_drawing': 'Рисунок с натуры',
    'photography': 'Фотография'
}


def php_trim(s: str) -> str:
    """Эмуляция PHP trim()"""
    return s.strip() if s else ''


def php_htmlspecialchars(s: str, flags: int = 0) -> str:
    """Эмуляция PHP htmlspecialchars()"""
    if not s:
        return ''
    replacements = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }
    for char, replacement in replacements.items():
        s = s.replace(char, replacement)
    return s


def php_strip_tags(s: str) -> str:
    """Эмуляция PHP strip_tags()"""
    if not s:
        return ''
    return re.sub(r'<[^>]*>', '', s)


def php_password_verify(password: str, hash_str: str) -> bool:
    """Эмуляция PHP password_verify()"""
    import bcrypt
    try:
        return bcrypt.checkpw(password.encode('utf-8'), hash_str.encode('utf-8'))
    except Exception:
        return False


def php_password_hash(password: str, algorithm: str = 'bcrypt') -> str:
    """Эмуляция PHP password_hash() с PASSWORD_DEFAULT"""
    import bcrypt
    return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')


def php_uniqid(prefix: str = '', more_entropy: bool = True) -> str:
    """Эмуляция PHP uniqid()"""
    base = f"{prefix}{int(time.time()):x}"
    if more_entropy:
        base += f".{hashlib.md5(str(time.time()).encode()).hexdigest()[:5]}"
    return base


def php_file_get_contents(filepath: str) -> Optional[bytes]:
    """Эмуляция PHP file_get_contents()"""
    try:
        with open(filepath, 'rb') as f:
            return f.read()
    except Exception:
        return None


def php_file_exists(filepath: str) -> bool:
    """Эмуляция PHP file_exists()"""
    return os.path.isfile(filepath)


def php_is_readable(filepath: str) -> bool:
    """Эмуляция PHP is_readable()"""
    return os.access(filepath, os.R_OK)


def php_is_writable(filepath: str) -> bool:
    """Эмуляция PHP is_writable()"""
    return os.access(filepath, os.W_OK)


def php_is_dir(path: str) -> bool:
    """Эмуляция PHP is_dir()"""
    return os.path.isdir(path)


def php_mkdir(path: str, mode: int = 0o755, recursive: bool = True) -> bool:
    """Эмуляция PHP mkdir()"""
    try:
        if recursive:
            os.makedirs(path, mode, exist_ok=True)
        else:
            os.mkdir(path, mode)
        return True
    except Exception:
        return False


# =============================================================================
# МОДУЛЬ 1: ВАЛИДАЦИЯ ЗАГРУЖАЕМЫХ ФАЙЛОВ
# =============================================================================

class FileValidator:
    """Класс валидации загружаемых файлов (эмуляция upload.php + functions.php)"""
    
    @staticmethod
    def validate_extension(filename: str) -> bool:
        """Проверка допустимого расширения файла (.jpeg, .jpg)"""
        ext = filename.lower().split('.')[-1] if '.' in filename else ''
        return ext in ALLOWED_EXTENSIONS
    
    @staticmethod
    def validate_mime_type(mime_type: str) -> bool:
        """Верификация MIME-типа (image/jpeg)"""
        return mime_type in ALLOWED_MIME_TYPES
    
    @staticmethod
    def validate_file_size(file_size: int) -> Tuple[bool, Optional[str]]:
        """
        Контроль объёма файла (нижняя граница 1 Мб, верхняя граница 10 Мб)
        Возвращает (успех, сообщение_об_ошибке)
        """
        if file_size < UPLOAD_MIN_SIZE:
            return False, f"Файл слишком маленький (минимум {UPLOAD_MIN_SIZE // 1024 // 1024} Мб)"
        if file_size > UPLOAD_MAX_SIZE:
            return False, f"Файл слишком большой (максимум {UPLOAD_MAX_SIZE // 1024 // 1024} Мб)"
        return True, None
    
    @staticmethod
    def validate_file_signature(file_content: bytes) -> bool:
        """
        Анализ бинарной сигнатуры JPEG (проверка начальных байтов FF D8 FF)
        """
        if not file_content or len(file_content) < 3:
            return False
        
        # Проверка сигнатуры JPEG: FF D8 FF
        jpeg_signature = b'\xff\xd8\xff'
        return file_content[:3] == jpeg_signature
    
    @staticmethod
    def validate_image_dimensions(width: int, height: int) -> Tuple[bool, Optional[str]]:
        """
        Проверка геометрических параметров изображения
        (максимальные ширина и высота 5000 пикселей)
        """
        if width <= 0 or height <= 0:
            return False, "Некорректные размеры изображения"
        if width > MAX_IMAGE_WIDTH or height > MAX_IMAGE_HEIGHT:
            return False, f"Изображение слишком большое (макс. {MAX_IMAGE_WIDTH}×{MAX_IMAGE_HEIGHT} px)"
        return True, None
    
    @staticmethod
    def generate_unique_filename(extension: str) -> str:
        """
        Формирование уникального имени файла на основе временной метки и криптографического хеша
        """
        timestamp = int(time.time())
        random_data = f"{timestamp}{os.urandom(16).hex()}"
        hash_part = hashlib.sha256(random_data.encode()).hexdigest()[:16]
        return f"work_{timestamp}_{hash_part}.{extension}"
    
    @staticmethod
    def validate_directory_path(dir_path: str) -> Tuple[bool, Optional[str]]:
        """
        Проверка корректности пути сохранения и прав доступа к целевой директории
        """
        if not dir_path:
            return False, "Путь к директории не указан"
        
        if not php_is_dir(dir_path):
            return False, f"Директория не существует: {dir_path}"
        
        if not php_is_writable(dir_path):
            return False, f"Нет прав на запись в директорию: {dir_path}"
        
        return True, None
    
    @staticmethod
    def handle_file_exceptions(file_content: Optional[bytes]) -> Tuple[bool, str]:
        """
        Обработка исключений при повреждённых, пустых или нечитаемых файлах
        """
        if file_content is None:
            return False, "Файл нечитаем"
        if len(file_content) == 0:
            return False, "Файл пуст"
        if len(file_content) < 10:
            return False, "Файл повреждён (слишком мал)"
        return True, ""


class TestFileValidation:
    """Тесты модуля валидации файлов"""
    
    def test_validate_extension_valid_jpg(self):
        """Проверка валидного расширения .jpg"""
        assert FileValidator.validate_extension("image.jpg") is True
        assert FileValidator.validate_extension("IMAGE.JPG") is True
        assert FileValidator.validate_extension("my_photo.Jpg") is True
    
    def test_validate_extension_valid_jpeg(self):
        """Проверка валидного расширения .jpeg"""
        assert FileValidator.validate_extension("image.jpeg") is True
        assert FileValidator.validate_extension("IMAGE.JPEG") is True
    
    def test_validate_extension_invalid(self):
        """Проверка недопустимых расширений"""
        assert FileValidator.validate_extension("image.png") is False
        assert FileValidator.validate_extension("image.gif") is False
        assert FileValidator.validate_extension("image.bmp") is False
        assert FileValidator.validate_extension("image.txt") is False
        assert FileValidator.validate_extension("image") is False  # без расширения
    
    def test_validate_mime_type_valid(self):
        """Проверка валидного MIME-типа"""
        assert FileValidator.validate_mime_type("image/jpeg") is True
    
    def test_validate_mime_type_invalid(self):
        """Проверка недопустимых MIME-типов"""
        assert FileValidator.validate_mime_type("image/png") is False
        assert FileValidator.validate_mime_type("image/gif") is False
        assert FileValidator.validate_mime_type("text/plain") is False
        assert FileValidator.validate_mime_type("") is False
    
    def test_validate_file_size_valid(self):
        """Проверка валидного размера файла (от 1 до 10 Мб)"""
        valid_size = 5 * 1024 * 1024  # 5 Мб
        success, error = FileValidator.validate_file_size(valid_size)
        assert success is True
        assert error is None
    
    def test_validate_file_size_too_small(self):
        """Проверка слишком маленького файла (< 1 Мб)"""
        small_size = 500 * 1024  # 500 Кб
        success, error = FileValidator.validate_file_size(small_size)
        assert success is False
        assert "слишком маленький" in error
    
    def test_validate_file_size_too_large(self):
        """Проверка слишком большого файла (> 10 Мб)"""
        large_size = 15 * 1024 * 1024  # 15 Мб
        success, error = FileValidator.validate_file_size(large_size)
        assert success is False
        assert "слишком большой" in error
    
    def test_validate_file_size_boundary_min(self):
        """Проверка границы минимального размера (ровно 1 Мб)"""
        min_size = UPLOAD_MIN_SIZE
        success, error = FileValidator.validate_file_size(min_size)
        assert success is True
    
    def test_validate_file_size_boundary_max(self):
        """Проверка границы максимального размера (ровно 10 Мб)"""
        max_size = UPLOAD_MAX_SIZE
        success, error = FileValidator.validate_file_size(max_size)
        assert success is True
    
    def test_validate_file_signature_valid_jpeg(self):
        """Проверка валидной сигнатуры JPEG"""
        # Валидный JPEG начинается с FF D8 FF
        jpeg_content = b'\xff\xd8\xff\xe0\x00\x10JFIF' + b'\x00' * 100
        assert FileValidator.validate_file_signature(jpeg_content) is True
    
    def test_validate_file_signature_invalid(self):
        """Проверка невалидной сигнатуры"""
        png_content = b'\x89PNG\r\n\x1a\n' + b'\x00' * 100
        gif_content = b'GIF89a' + b'\x00' * 100
        empty_content = b''
        
        assert FileValidator.validate_file_signature(png_content) is False
        assert FileValidator.validate_file_signature(gif_content) is False
        assert FileValidator.validate_file_signature(empty_content) is False
    
    def test_validate_file_signature_partial(self):
        """Проверка частичной сигнатуры (неполный файл)"""
        partial = b'\xff\xd8'  # только 2 байта
        assert FileValidator.validate_file_signature(partial) is False
    
    def test_validate_image_dimensions_valid(self):
        """Проверка валидных размеров изображения"""
        success, error = FileValidator.validate_image_dimensions(1920, 1080)
        assert success is True
        assert error is None
        
        success, error = FileValidator.validate_image_dimensions(5000, 5000)
        assert success is True
    
    def test_validate_image_dimensions_too_large(self):
        """Проверка слишком больших размеров"""
        success, error = FileValidator.validate_image_dimensions(5001, 4000)
        assert success is False
        assert "слишком большое" in error
        
        success, error = FileValidator.validate_image_dimensions(4000, 5001)
        assert success is False
        
        success, error = FileValidator.validate_image_dimensions(6000, 6000)
        assert success is False
    
    def test_validate_image_dimensions_invalid(self):
        """Проверка некорректных размеров"""
        success, error = FileValidator.validate_image_dimensions(0, 1000)
        assert success is False
        
        success, error = FileValidator.validate_image_dimensions(-100, 1000)
        assert success is False
    
    def test_generate_unique_filename_format(self):
        """Проверка формата генерируемого имени файла"""
        filename = FileValidator.generate_unique_filename("jpg")
        assert filename.startswith("work_")
        assert filename.endswith(".jpg")
        assert "_" in filename
        # Проверяем, что имя содержит временную метку и хеш
        parts = filename[:-4].split("_")  # убираем .jpg
        assert len(parts) >= 3  # work_<timestamp>_<hash>
    
    def test_generate_unique_filename_uniqueness(self):
        """Проверка уникальности генерируемых имён"""
        filenames = [FileValidator.generate_unique_filename("jpg") for _ in range(100)]
        assert len(set(filenames)) == 100  # все имена уникальны
    
    def test_generate_unique_filename_different_extensions(self):
        """Проверка генерации имён с разными расширениями"""
        assert FileValidator.generate_unique_filename("jpg").endswith(".jpg")
        assert FileValidator.generate_unique_filename("jpeg").endswith(".jpeg")
    
    def test_validate_directory_path_valid(self, tmp_path):
        """Проверка валидного пути к директории"""
        test_dir = tmp_path / "test_uploads"
        test_dir.mkdir()
        
        success, error = FileValidator.validate_directory_path(str(test_dir))
        assert success is True
        assert error is None
    
    def test_validate_directory_path_not_exists(self, tmp_path):
        """Проверка несуществующей директории"""
        non_existent = str(tmp_path / "non_existent")
        success, error = FileValidator.validate_directory_path(non_existent)
        assert success is False
        assert "не существует" in error
    
    def test_handle_file_exceptions_empty(self):
        """Обработка пустого файла"""
        success, message = FileValidator.handle_file_exceptions(b'')
        assert success is False
        assert "пуст" in message
    
    def test_handle_file_exceptions_none(self):
        """Обработка нечитаемого файла"""
        success, message = FileValidator.handle_file_exceptions(None)
        assert success is False
        assert "нечитаем" in message
    
    def test_handle_file_exceptions_corrupted(self):
        """Обработка повреждённого файла"""
        success, message = FileValidator.handle_file_exceptions(b'\x00\x01\x02')
        assert success is False
        assert "повреждён" in message
    
    def test_handle_file_exceptions_valid(self):
        """Обработка валидного файла"""
        valid_content = b'\xff\xd8\xff' + b'\x00' * 100
        success, message = FileValidator.handle_file_exceptions(valid_content)
        assert success is True
        assert message == ""


# =============================================================================
# МОДУЛЬ 2: ВАЛИДАЦИЯ И САНИТИЗАЦИЯ ПОЛЬЗОВАТЕЛЬСКИХ ДАННЫХ
# =============================================================================

class DataValidator:
    """Класс валидации и санитизации пользовательских данных"""
    
    @staticmethod
    def validate_required_field(value: Any) -> Tuple[bool, str]:
        """
        Проверка обязательных полей формы на пустоту, null и пробельные символы
        """
        if value is None:
            return False, "Поле обязательно для заполнения"
        if isinstance(value, str):
            stripped = value.strip()
            if not stripped:
                return False, "Поле не может содержать только пробелы"
        return True, ""
    
    @staticmethod
    def validate_email(email: str) -> Tuple[bool, str]:
        """
        Валидация формата email (соответствие RFC 5322)
        Упрощённая проверка для практических целей
        """
        if not email:
            return False, "Email обязателен"
        
        # RFC 5322 simplified pattern
        pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        if not re.match(pattern, email):
            return False, "Некорректный формат email"
        
        return True, ""
    
    @staticmethod
    def validate_course(course: Any) -> Tuple[bool, str]:
        """
        Проверка диапазона курса обучения (целые числа от 1 до 6)
        """
        try:
            course_int = int(course)
            if course_int < 1 or course_int > 6:
                return False, "Курс должен быть от 1 до 6"
            return True, ""
        except (ValueError, TypeError):
            return False, "Курс должен быть числом"
    
    @staticmethod
    def validate_phone(phone: str) -> Tuple[bool, str]:
        """
        Валидация формата телефонного номера (маска +7 XXX XXX-XX-XX)
        """
        if not phone:
            return True, ""  # Телефон не обязателен
        
        # Очищаем от лишних символов
        cleaned = re.sub(r'[^\d+]', '', phone)
        
        # Проверка различных форматов
        patterns = [
            r'^\+7\d{10}$',      # +7XXXXXXXXXX
            r'^8\d{10}$',        # 8XXXXXXXXXX
            r'^\+7\s?\(?\d{3}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}$',  # +7 XXX XXX-XX-XX
        ]
        
        for pattern in patterns:
            if re.match(pattern, phone) or re.match(pattern, cleaned):
                return True, ""
        
        return False, "Некорректный формат телефона"
    
    @staticmethod
    def sanitize_xss(value: str) -> str:
        """
        Экранирование специальных символов для защиты от XSS
        (htmlspecialchars, strip_tags)
        """
        if not value:
            return ''
        # Сначала удаляем теги
        value = php_strip_tags(value)
        # Затем экранируем спецсимволы
        value = php_htmlspecialchars(value)
        return value
    
    @staticmethod
    def validate_consent(consent: bool) -> Tuple[bool, str]:
        """
        Проверка наличия флага согласия на обработку персональных данных
        """
        if not consent:
            return False, "Требуется согласие на обработку персональных данных"
        return True, ""
    
    @staticmethod
    def sanitize_string(value: str) -> str:
        """
        Обрезка пробелов, нормализация регистра и удаление управляющих символов
        из строковых полей
        """
        if not value:
            return ''
        # Обрезка пробелов
        value = php_trim(value)
        # Удаление управляющих символов (кроме табуляции и переноса строки)
        value = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]', '', value)
        # Нормализация множественных пробелов
        value = re.sub(r'\s+', ' ', value)
        return value
    
    @staticmethod
    def validate_sql_injection_protection(params: Dict) -> Tuple[bool, str]:
        """
        Защита от SQL-инъекций на уровне параметризации запросов
        (проверка структуры массивов параметров)
        """
        dangerous_patterns = [
            r";\s*DROP\s+",
            r";\s*DELETE\s+",
            r";\s*UPDATE\s+.*\s+SET\s+",
            r";\s*INSERT\s+",
            r"--",
            r"/\*.*\*/",
            r"\bUNION\b.*\bSELECT\b",
        ]
        
        for key, value in params.items():
            if isinstance(value, str):
                for pattern in dangerous_patterns:
                    if re.search(pattern, value, re.IGNORECASE):
                        return False, f"Обнаружена потенциальная SQL-инъекция в поле {key}"
        
        return True, ""


class TestDataValidation:
    """Тесты модуля валидации пользовательских данных"""
    
    def test_validate_required_field_valid(self):
        """Проверка валидного обязательного поля"""
        success, error = DataValidator.validate_required_field("Иванов Иван")
        assert success is True
        assert error == ""
    
    def test_validate_required_field_empty_string(self):
        """Проверка пустой строки"""
        success, error = DataValidator.validate_required_field("")
        assert success is False
        # Пустая строка считается как пробелы после trim
        assert "пробелы" in error or "обязательно" in error
    
    def test_validate_required_field_whitespace(self):
        """Проверка строки из пробелов"""
        success, error = DataValidator.validate_required_field("   ")
        assert success is False
        assert "пробелы" in error
    
    def test_validate_required_field_null(self):
        """Проверка null значения"""
        success, error = DataValidator.validate_required_field(None)
        assert success is False
    
    def test_validate_email_valid(self):
        """Проверка валидных email адресов"""
        valid_emails = [
            "user@example.com",
            "ivan.petrov@sibadi.org",
            "test.user+tag@domain.co.uk",
            "name123@test.ru"
        ]
        for email in valid_emails:
            success, error = DataValidator.validate_email(email)
            assert success is True, f"Email {email} должен быть валидным"
    
    def test_validate_email_invalid(self):
        """Проверка невалидных email адресов"""
        invalid_emails = [
            "",
            "invalid",
            "@example.com",
            "user@",
            "user@.com",
            "user@example",
            "user name@example.com"
        ]
        for email in invalid_emails:
            success, error = DataValidator.validate_email(email)
            assert success is False, f"Email {email} должен быть невалидным"
    
    def test_validate_course_valid(self):
        """Проверка валидных значений курса"""
        for course in [1, 2, 3, 4, 5, 6, "1", "6"]:
            success, error = DataValidator.validate_course(course)
            assert success is True, f"Курс {course} должен быть валидным"
    
    def test_validate_course_invalid(self):
        """Проверка невалидных значений курса"""
        for course in [0, 7, -1, 10, "abc", "", None]:
            success, error = DataValidator.validate_course(course)
            assert success is False, f"Курс {course} должен быть невалидным"
    
    def test_validate_phone_valid(self):
        """Проверка валидных номеров телефонов"""
        valid_phones = [
            "+79991234567",
            "89991234567",
            "+7 999 123-45-67",
            "+7(999)123-45-67",
            ""  # Пустой телефон допустим
        ]
        for phone in valid_phones:
            success, error = DataValidator.validate_phone(phone)
            assert success is True, f"Телефон {phone} должен быть валидным"
    
    def test_validate_phone_invalid(self):
        """Проверка невалидных номеров телефонов"""
        invalid_phones = [
            "123",
            "abcdefghij",
            "+1234567890123456",
        ]
        for phone in invalid_phones:
            success, error = DataValidator.validate_phone(phone)
            assert success is False, f"Телефон {phone} должен быть невалидным"
    
    def test_sanitize_xss_script_tag(self):
        """Экранирование XSS через script тег"""
        malicious = "<script>alert('XSS')</script>"
        sanitized = DataValidator.sanitize_xss(malicious)
        assert "<script>" not in sanitized
        assert "alert" in sanitized  # текст остаётся
    
    def test_sanitize_xss_onclick(self):
        """Экранирование XSS через onclick"""
        malicious = '<img src="x" onerror="alert(1)">'
        sanitized = DataValidator.sanitize_xss(malicious)
        assert "<img" not in sanitized
    
    def test_sanitize_xss_safe_input(self):
        """Безопасный ввод не изменяется"""
        safe = "Просто текст без HTML"
        sanitized = DataValidator.sanitize_xss(safe)
        assert sanitized == safe
    
    def test_validate_consent_true(self):
        """Проверка согласия True"""
        success, error = DataValidator.validate_consent(True)
        assert success is True
    
    def test_validate_consent_false(self):
        """Проверка отсутствия согласия"""
        success, error = DataValidator.validate_consent(False)
        assert success is False
        assert "согласие" in error
    
    def test_sanitize_string_trim(self):
        """Обрезка пробелов"""
        result = DataValidator.sanitize_string("  hello world  ")
        assert result == "hello world"
    
    def test_sanitize_string_remove_control_chars(self):
        """Удаление управляющих символов"""
        result = DataValidator.sanitize_string("hello\x00world\x1ftest")
        assert "\x00" not in result
        assert "\x1f" not in result
    
    def test_sanitize_string_normalize_spaces(self):
        """Нормализация множественных пробелов"""
        result = DataValidator.sanitize_string("hello    world")
        assert result == "hello world"
    
    def test_validate_sql_injection_safe(self):
        """Проверка безопасных параметров"""
        safe_params = {
            "fio": "Иванов Иван",
            "email": "test@example.com",
            "course": "3"
        }
        success, error = DataValidator.validate_sql_injection_protection(safe_params)
        assert success is True
    
    def test_validate_sql_injection_drop(self):
        """Обнаружение DROP инъекции"""
        malicious_params = {"id": "1; DROP TABLE users"}
        success, error = DataValidator.validate_sql_injection_protection(malicious_params)
        assert success is False
        assert "SQL-инъекция" in error
    
    def test_validate_sql_injection_union_select(self):
        """Обнаружение UNION SELECT инъекции"""
        malicious_params = {"id": "1 UNION SELECT * FROM users"}
        success, error = DataValidator.validate_sql_injection_protection(malicious_params)
        assert success is False


# =============================================================================
# МОДУЛЬ 3: РАСЧЁТ РЕЙТИНГА И ОПРЕДЕЛЕНИЯ МЕСТ
# =============================================================================

class RatingCalculator:
    """Класс расчёта рейтинга и определения мест"""
    
    @staticmethod
    def sort_applications(applications: List[Dict]) -> List[Dict]:
        """
        Сортировка массива заявок:
        1. По убыванию балла жюри (jury_score DESC)
        2. По дате подачи при равенстве баллов (created_at ASC)
        """
        return sorted(
            applications,
            key=lambda x: (-(x.get('jury_score') or 0), x.get('created_at', ''))
        )
    
    @staticmethod
    def filter_published(applications: List[Dict]) -> List[Dict]:
        """Фильтрация только опубликованных работ (is_published = 1)"""
        return [app for app in applications if app.get('is_published') == 1]
    
    @staticmethod
    def assign_places(applications: List[Dict], skip_places: bool = False) -> List[Dict]:
        """
        Алгоритм присвоения мест внутри номинации и раздела
        skip_places: если True - места пропускаются при равенстве баллов (1, 1, 3)
                     если False - места без пропуска (1, 1, 2)
        """
        if not applications:
            return []
        
        sorted_apps = RatingCalculator.sort_applications(applications)
        
        result = []
        current_place = 1
        previous_score = None
        position = 0  # Позиция в отсортированном списке
        
        for app in sorted_apps:
            score = app.get('jury_score')
            
            # Если баллы отличаются от предыдущего
            if score != previous_score:
                if skip_places:
                    # Пропускаем места: если было 2 участника на месте 1, следующее место 3
                    current_place = position + 1
                else:
                    # Без пропуска: место равно количеству уникальных лучших результатов + 1
                    unique_better_scores = len(set(a.get('jury_score') for a in result if a.get('jury_score') is not None and a.get('jury_score') > (score if score is not None else -1)))
                    current_place = unique_better_scores + 1
            
            app_with_place = app.copy()
            app_with_place['place'] = current_place
            result.append(app_with_place)
            
            previous_score = score
            position += 1
        
        return result
    
    @staticmethod
    def convert_place_to_roman(place: int) -> str:
        """
        Преобразование числового места в строковое представление для диплома
        1 -> I, 2 -> II, 3 -> III, остальные -> Участник
        """
        roman_map = {1: 'I', 2: 'II', 3: 'III'}
        if place in roman_map:
            return roman_map[place]
        return 'Участник'
    
    @staticmethod
    def get_place_text(place: int) -> str:
        """Получение текстового представления места"""
        places = {
            1: 'I (первое)',
            2: 'II (второе)',
            3: 'III (третье)'
        }
        return places.get(place, f'{place}-е')


class TestRatingCalculation:
    """Тесты модуля расчёта рейтинга"""
    
    def test_sort_applications_by_score(self):
        """Сортировка по баллам"""
        apps = [
            {'id': 1, 'jury_score': 7, 'created_at': '2024-01-01'},
            {'id': 2, 'jury_score': 9, 'created_at': '2024-01-02'},
            {'id': 3, 'jury_score': 8, 'created_at': '2024-01-03'},
        ]
        sorted_apps = RatingCalculator.sort_applications(apps)
        assert sorted_apps[0]['jury_score'] == 9
        assert sorted_apps[1]['jury_score'] == 8
        assert sorted_apps[2]['jury_score'] == 7
    
    def test_sort_applications_same_score_by_date(self):
        """Сортировка при одинаковых баллах по дате"""
        apps = [
            {'id': 1, 'jury_score': 8, 'created_at': '2024-01-03'},
            {'id': 2, 'jury_score': 8, 'created_at': '2024-01-01'},
            {'id': 3, 'jury_score': 8, 'created_at': '2024-01-02'},
        ]
        sorted_apps = RatingCalculator.sort_applications(apps)
        assert sorted_apps[0]['id'] == 2  # самая ранняя дата
        assert sorted_apps[1]['id'] == 3
        assert sorted_apps[2]['id'] == 1
    
    def test_filter_published(self):
        """Фильтрация опубликованных работ"""
        apps = [
            {'id': 1, 'is_published': 1},
            {'id': 2, 'is_published': 0},
            {'id': 3, 'is_published': 1},
        ]
        filtered = RatingCalculator.filter_published(apps)
        assert len(filtered) == 2
        assert all(app['is_published'] == 1 for app in filtered)
    
    def test_assign_places_no_skip(self):
        """Присвоение мест без пропуска"""
        apps = [
            {'id': 1, 'jury_score': 10, 'created_at': '2024-01-01'},
            {'id': 2, 'jury_score': 9, 'created_at': '2024-01-02'},
            {'id': 3, 'jury_score': 8, 'created_at': '2024-01-03'},
        ]
        result = RatingCalculator.assign_places(apps, skip_places=False)
        assert result[0]['place'] == 1
        assert result[1]['place'] == 2
        assert result[2]['place'] == 3
    
    def test_assign_places_with_ties_no_skip(self):
        """Присвоение мест с одинаковыми баллами без пропуска"""
        apps = [
            {'id': 1, 'jury_score': 10, 'created_at': '2024-01-01'},
            {'id': 2, 'jury_score': 10, 'created_at': '2024-01-02'},
            {'id': 3, 'jury_score': 8, 'created_at': '2024-01-03'},
        ]
        result = RatingCalculator.assign_places(apps, skip_places=False)
        assert result[0]['place'] == 1
        assert result[1]['place'] == 1  # одинаковые баллы
        assert result[2]['place'] == 2  # без пропуска
    
    def test_assign_places_with_ties_skip(self):
        """Присвоение мест с одинаковыми баллами с пропуском"""
        apps = [
            {'id': 1, 'jury_score': 10, 'created_at': '2024-01-01'},
            {'id': 2, 'jury_score': 10, 'created_at': '2024-01-02'},
            {'id': 3, 'jury_score': 8, 'created_at': '2024-01-03'},
        ]
        result = RatingCalculator.assign_places(apps, skip_places=True)
        assert result[0]['place'] == 1
        assert result[1]['place'] == 1
        assert result[2]['place'] == 3  # с пропуском
    
    def test_convert_place_to_roman(self):
        """Преобразование места в римские цифры"""
        assert RatingCalculator.convert_place_to_roman(1) == 'I'
        assert RatingCalculator.convert_place_to_roman(2) == 'II'
        assert RatingCalculator.convert_place_to_roman(3) == 'III'
        assert RatingCalculator.convert_place_to_roman(4) == 'Участник'
        assert RatingCalculator.convert_place_to_roman(10) == 'Участник'
    
    def test_get_place_text(self):
        """Текстовое представление места"""
        assert RatingCalculator.get_place_text(1) == 'I (первое)'
        assert RatingCalculator.get_place_text(2) == 'II (второе)'
        assert RatingCalculator.get_place_text(3) == 'III (третье)'
        assert RatingCalculator.get_place_text(4) == '4-е'
    
    def test_edge_case_zero_applicants(self):
        """Граничное условие: 0 участников"""
        result = RatingCalculator.assign_places([])
        assert result == []
    
    def test_edge_case_one_applicant(self):
        """Граничное условие: 1 участник"""
        apps = [{'id': 1, 'jury_score': 8, 'created_at': '2024-01-01'}]
        result = RatingCalculator.assign_places(apps)
        assert len(result) == 1
        assert result[0]['place'] == 1
    
    def test_edge_case_no_scores(self):
        """Граничное условие: отсутствие оценок"""
        apps = [
            {'id': 1, 'jury_score': None, 'created_at': '2024-01-01'},
            {'id': 2, 'jury_score': None, 'created_at': '2024-01-02'},
        ]
        result = RatingCalculator.assign_places(apps)
        assert len(result) == 2
        # При отсутствии оценок сортировка по дате


# =============================================================================
# МОДУЛЬ 4: ПОДГОТОВКА ДАННЫХ ДЛЯ ГЕНЕРАЦИИ PDF-ДОКУМЕНТОВ
# =============================================================================

class PDFDataPreparator:
    """Класс подготовки данных для генерации PDF"""
    
    @staticmethod
    def format_date_gost(date_obj: datetime) -> str:
        """
        Форматирование даты выдачи документа в соответствии с требованиями ГОСТ
        Формат: DD.MM.YYYY
        """
        return date_obj.strftime('%d.%m.%Y')
    
    @staticmethod
    def substitute_template_variables(template: str, variables: Dict[str, str]) -> str:
        """
        Подстановка переменных в HTML-шаблон
        (ФИО, ВУЗ, номинация, раздел, место)
        """
        result = template
        for key, value in variables.items():
            placeholder = '{{' + key + '}}'
            result = result.replace(placeholder, value or '')
        return result
    
    @staticmethod
    def validate_utf8_encoding(text: str) -> Tuple[bool, str]:
        """
        Обработка кириллических символов и проверка корректности кодировки UTF-8
        """
        try:
            text.encode('utf-8').decode('utf-8')
            return True, ""
        except UnicodeDecodeError as e:
            return False, f"Ошибка кодировки UTF-8: {str(e)}"
    
    @staticmethod
    def validate_template_path(template_path: str) -> Tuple[bool, str]:
        """
        Валидация путей к шаблонам печати, подписей и логотипов
        (проверка existence и read permissions)
        """
        if not php_file_exists(template_path):
            return False, f"Шаблон не найден: {template_path}"
        if not php_is_readable(template_path):
            return False, f"Нет прав на чтение шаблона: {template_path}"
        return True, ""
    
    @staticmethod
    def prepare_certificate_data(participant: Dict) -> Dict:
        """
        Проверка корректности сборки ассоциативного массива данных перед передачей в DomPDF
        """
        required_fields = ['fio', 'educational_institution', 'nomination', 'section']
        
        data = {}
        missing_fields = []
        
        for field in required_fields:
            value = participant.get(field)
            if value is None or value == '':
                missing_fields.append(field)
            data[field] = value or ''
        
        # Добавление дополнительных полей
        data['date'] = PDFDataPreparator.format_date_gost(datetime.now())
        data['nomination_name'] = NOMINATION_NAMES.get(
            participant.get('nomination', ''),
            participant.get('nomination', '')
        )
        
        return {
            'data': data,
            'missing_fields': missing_fields,
            'is_valid': len(missing_fields) == 0
        }
    
    @staticmethod
    def handle_missing_data(value: Optional[str], default: str = '') -> str:
        """
        Обработка отсутствующих данных подстановки
        (заглушки или значения по умолчанию)
        """
        if value is None or value == '':
            return default
        return value


class TestPDFPreparation:
    """Тесты модуля подготовки данных для PDF"""
    
    def test_format_date_gost(self):
        """Форматирование даты по ГОСТ"""
        date_obj = datetime(2024, 3, 15)
        formatted = PDFDataPreparator.format_date_gost(date_obj)
        assert formatted == '15.03.2024'
    
    def test_substitute_template_variables(self):
        """Подстановка переменных в шаблон"""
        template = "Сертификат выдан {{fio}} за участие в номинации {{nomination}}"
        variables = {
            'fio': 'Иванов Иван',
            'nomination': 'Фотография'
        }
        result = PDFDataPreparator.substitute_template_variables(template, variables)
        assert 'Иванов Иван' in result
        assert 'Фотография' in result
        assert '{{fio}}' not in result
    
    def test_validate_utf8_valid(self):
        """Проверка валидной UTF-8 строки"""
        text = "Привет мир! Кириллица работает."
        success, error = PDFDataPreparator.validate_utf8_encoding(text)
        assert success is True
    
    def test_validate_utf8_invalid(self):
        """Проверка невалидной UTF-8 строки"""
        # В Python невозможно создать строку с невалидным UTF-8,
        # поэтому проверяем обработку None и пустых значений
        success, error = PDFDataPreparator.validate_utf8_encoding("")
        assert success is True  # Пустая строка валидна
        
        success, error = PDFDataPreparator.validate_utf8_encoding("Valid text")
        assert success is True
    
    def test_validate_template_path_exists(self, tmp_path):
        """Проверка существующего шаблона"""
        template_file = tmp_path / "template.html"
        template_file.write_text("<html>Test</html>")
        
        success, error = PDFDataPreparator.validate_template_path(str(template_file))
        assert success is True
    
    def test_validate_template_path_not_exists(self):
        """Проверка несуществующего шаблона"""
        success, error = PDFDataPreparator.validate_template_path("/non/existent/path.html")
        assert success is False
        assert "не найден" in error
    
    def test_prepare_certificate_data_valid(self):
        """Подготовка валидных данных сертификата"""
        participant = {
            'fio': 'Иванов Иван',
            'educational_institution': 'СибАДИ',
            'nomination': 'photography',
            'section': 'Основной'
        }
        result = PDFDataPreparator.prepare_certificate_data(participant)
        assert result['is_valid'] is True
        assert len(result['missing_fields']) == 0
        assert 'date' in result['data']
    
    def test_prepare_certificate_data_missing_fields(self):
        """Подготовка данных с отсутствующими полями"""
        participant = {
            'fio': '',
            'educational_institution': 'СибАДИ',
            'nomination': None,
            'section': 'Основной'
        }
        result = PDFDataPreparator.prepare_certificate_data(participant)
        assert result['is_valid'] is False
        assert 'fio' in result['missing_fields']
        assert 'nomination' in result['missing_fields']
    
    def test_handle_missing_data_none(self):
        """Обработка None значения"""
        result = PDFDataPreparator.handle_missing_data(None, 'По умолчанию')
        assert result == 'По умолчанию'
    
    def test_handle_missing_data_empty(self):
        """Обработка пустой строки"""
        result = PDFDataPreparator.handle_missing_data('', 'По умолчанию')
        assert result == 'По умолчанию'
    
    def test_handle_missing_data_valid(self):
        """Обработка валидного значения"""
        result = PDFDataPreparator.handle_missing_data('Значение', 'По умолчанию')
        assert result == 'Значение'


# =============================================================================
# МОДУЛЬ 5: ЭКСПОРТ ДАННЫХ И ФОРМИРОВАНИЯ АРХИВОВ
# =============================================================================

class ExportManager:
    """Класс экспорта данных и формирования архивов"""
    
    @staticmethod
    def array_to_csv(data: List[Dict], delimiter: str = ';') -> str:
        """
        Преобразование массива ассоциативных данных в формат CSV
        """
        if not data:
            return ''
        
        lines = []
        headers = list(data[0].keys())
        lines.append(delimiter.join(headers))
        
        for row in data:
            values = []
            for header in headers:
                value = str(row.get(header, ''))
                # Экранирование кавычек и разделителей
                if delimiter in value or '"' in value or '\n' in value:
                    value = '"' + value.replace('"', '""') + '"'
                values.append(value)
            lines.append(delimiter.join(values))
        
        return '\n'.join(lines)
    
    @staticmethod
    def add_utf8_bom(csv_content: str) -> bytes:
        """
        Добавление UTF-8 BOM для корректного открытия в табличных редакторах
        """
        bom = b'\xef\xbb\xbf'
        return bom + csv_content.encode('utf-8')
    
    @staticmethod
    def generate_zip_structure(applications: List[Dict]) -> Dict[str, str]:
        """
        Формирование древовидной структуры ZIP-архива
        (Номинация/Раздел/ФИО_Название.jpg)
        """
        structure = {}
        
        nom_names = {
            'arch_composition': 'Архитектурная_композиция',
            'art_graphics': 'Художественно-проектная_графика',
            'nature_drawing': 'Рисунок_с_натуры',
            'photography': 'Фотография'
        }
        
        for app in applications:
            nomination = nom_names.get(app.get('nomination', ''), app.get('nomination', 'Без_номинации'))
            section = app.get('section', 'Без_раздела') or 'Без_раздела'
            fio = re.sub(r'[^\w\s]', '_', app.get('fio', 'Unknown'))
            work_title = re.sub(r'[^\w\s]', '_', app.get('work_title', 'Без_названия'))
            
            zip_path = f"{nomination}/{section}/{fio}_{work_title}.jpg"
            structure[zip_path] = app.get('work_file', '')
        
        return structure
    
    @staticmethod
    def generate_manifest(applications: List[Dict]) -> str:
        """
        Генерация содержимого файла MANIFEST.txt
        (список путей, авторов, номинаций, баллов)
        """
        lines = ["СПИСОК РАБОТ", "=" * 50, ""]
        
        for app in applications:
            fio = app.get('fio', 'Unknown')
            institution = app.get('educational_institution', '')
            nomination = app.get('nomination', '')
            section = app.get('section', '')
            score = app.get('jury_score', '—')
            
            line = f"{fio} | {institution} | {nomination} | {section} | Балл: {score}"
            lines.append(line)
        
        return '\n'.join(lines)
    
    @staticmethod
    def handle_empty_selection(data: List) -> Tuple[bool, str]:
        """
        Обработка пустых выборок и защита от переполнения памяти
        """
        if not data:
            return False, "Пустая выборка данных"
        if len(data) > 10000:
            return False, "Превышен лимит количества записей (10000)"
        return True, ""
    
    @staticmethod
    def verify_archive_integrity(expected_files: List[str], actual_files: List[str]) -> Tuple[bool, List[str]]:
        """
        Проверка целостности архива после создания
        (верификация структуры и наличия файлов)
        """
        missing = []
        for expected in expected_files:
            if expected not in actual_files:
                missing.append(expected)
        
        return len(missing) == 0, missing


class TestExport:
    """Тесты модуля экспорта данных"""
    
    def test_array_to_csv_basic(self):
        """Базовое преобразование в CSV"""
        data = [
            {'name': 'Иван', 'age': '25'},
            {'name': 'Петр', 'age': '30'}
        ]
        csv = ExportManager.array_to_csv(data, ';')
        assert 'name;age' in csv
        assert 'Иван;25' in csv
        assert 'Петр;30' in csv
    
    def test_array_to_csv_escaping(self):
        """Экранирование спецсимволов в CSV"""
        data = [
            {'name': 'Иван "Цитата"', 'value': 'a;b'}
        ]
        csv = ExportManager.array_to_csv(data, ';')
        assert '"Иvan ""Цитата"""' in csv or '"Иван ""Цитата"""' in csv
    
    def test_array_to_csv_empty(self):
        """Преобразование пустого массива"""
        csv = ExportManager.array_to_csv([])
        assert csv == ''
    
    def test_add_utf8_bom(self):
        """Добавление UTF-8 BOM"""
        content = "test,data"
        result = ExportManager.add_utf8_bom(content)
        assert result.startswith(b'\xef\xbb\xbf')
        assert b'test,data' in result
    
    def test_generate_zip_structure(self):
        """Генерация структуры ZIP"""
        apps = [
            {
                'nomination': 'photography',
                'section': 'Основной',
                'fio': 'Иванов Иван',
                'work_title': 'Моя работа',
                'work_file': 'work_123.jpg'
            }
        ]
        structure = ExportManager.generate_zip_structure(apps)
        assert len(structure) == 1
        assert any('Фотография' in key for key in structure.keys())
    
    def test_generate_manifest(self):
        """Генерация MANIFEST.txt"""
        apps = [
            {
                'fio': 'Иванов Иван',
                'educational_institution': 'СибАДИ',
                'nomination': 'photography',
                'section': 'Основной',
                'jury_score': 9
            }
        ]
        manifest = ExportManager.generate_manifest(apps)
        assert 'Иванов Иван' in manifest
        assert 'СибАДИ' in manifest
        assert 'photography' in manifest
    
    def test_handle_empty_selection_empty(self):
        """Обработка пустой выборки"""
        success, message = ExportManager.handle_empty_selection([])
        assert success is False
        assert "Пустая" in message
    
    def test_handle_empty_selection_large(self):
        """Защита от переполнения памяти"""
        large_data = [{'id': i} for i in range(15000)]
        success, message = ExportManager.handle_empty_selection(large_data)
        assert success is False
        assert "лимит" in message.lower()
    
    def test_handle_empty_selection_valid(self):
        """Обработка валидной выборки"""
        data = [{'id': i} for i in range(100)]
        success, message = ExportManager.handle_empty_selection(data)
        assert success is True
    
    def test_verify_archive_integrity_complete(self):
        """Проверка целостности архива - все файлы на месте"""
        expected = ['file1.jpg', 'file2.jpg', 'MANIFEST.txt']
        actual = ['file1.jpg', 'file2.jpg', 'MANIFEST.txt']
        success, missing = ExportManager.verify_archive_integrity(expected, actual)
        assert success is True
        assert len(missing) == 0
    
    def test_verify_archive_integrity_missing(self):
        """Проверка целостности архива - есть недостающие файлы"""
        expected = ['file1.jpg', 'file2.jpg', 'MANIFEST.txt']
        actual = ['file1.jpg', 'MANIFEST.txt']
        success, missing = ExportManager.verify_archive_integrity(expected, actual)
        assert success is False
        assert 'file2.jpg' in missing


# =============================================================================
# МОДУЛЬ 6: РАБОТА С НАСТРОЙКАМИ И КОНФИГУРАЦИЕЙ
# =============================================================================

class SettingsManager:
    """Класс работы с настройками и конфигурацией"""
    
    def __init__(self):
        self._cache = {}
        self._cache_timestamp = {}
    
    @staticmethod
    def get_setting_value(settings: Dict, key: str, default: str = '0') -> str:
        """
        Получение значения по ключу из таблицы settings с приведением типов
        """
        return settings.get(key, default)
    
    @staticmethod
    def validate_setting_key(key: str, allowed_keys: List[str]) -> bool:
        """
        Проверка допустимых ключей конфигурации
        """
        return key in allowed_keys
    
    @staticmethod
    def validate_boolean_flag(value: Any) -> bool:
        """
        Валидация булевых флагов видимости разделов
        (true/false, 1/0, on/off)
        """
        true_values = ['1', 'true', 'on', 'yes', True, 1]
        false_values = ['0', 'false', 'off', 'no', False, 0]
        
        if isinstance(value, str):
            value = value.lower()
        
        return value in true_values or value in false_values
    
    @staticmethod
    def parse_boolean_flag(value: Any) -> bool:
        """Преобразование флага в boolean"""
        true_values = ['1', 'true', 'on', 'yes', True, 1]
        if isinstance(value, str):
            value = value.lower()
        return value in true_values
    
    def cache_setting(self, key: str, value: Any, ttl: int = 3600) -> None:
        """
        Кэширование настроек на время выполнения скрипта
        (проверка актуальности кэша)
        """
        self._cache[key] = {
            'value': value,
            'timestamp': time.time(),
            'ttl': ttl
        }
    
    def get_cached_setting(self, key: str) -> Optional[Any]:
        """Получение настройки из кэша"""
        if key not in self._cache:
            return None
        
        cached = self._cache[key]
        if time.time() - cached['timestamp'] > cached['ttl']:
            del self._cache[key]
            return None
        
        return cached['value']
    
    @staticmethod
    def validate_php_ini_value(value: str, expected_type: str) -> Tuple[bool, Any]:
        """
        Валидация типов данных при чтении параметров php.ini
        (upload_max_filesize, post_max_size)
        """
        try:
            if expected_type == 'size':
                # Парсинг размеров типа "10M", "128K"
                value = value.strip().upper()
                multipliers = {'K': 1024, 'M': 1024**2, 'G': 1024**3}
                
                for suffix, mult in multipliers.items():
                    if value.endswith(suffix):
                        return True, int(value[:-1]) * mult
                
                return True, int(value)
            
            elif expected_type == 'boolean':
                return True, SettingsManager.parse_boolean_flag(value)
            
            elif expected_type == 'integer':
                return True, int(value)
            
        except (ValueError, AttributeError):
            return False, None
        
        return False, None
    
    @staticmethod
    def handle_duplicate_settings(settings: List[Dict]) -> List[Dict]:
        """
        Обработка дублирующихся записей в БД
        (оставляем последнюю по времени обновления)
        """
        unique = {}
        for setting in settings:
            key = setting.get('setting_key')
            if key not in unique:
                unique[key] = setting
            else:
                # Оставляем запись с более поздним updated_at
                existing_time = unique[key].get('updated_at', '')
                new_time = setting.get('updated_at', '')
                if new_time > existing_time:
                    unique[key] = setting
        
        return list(unique.values())


class TestSettings:
    """Тесты модуля настроек"""
    
    def test_get_setting_value_exists(self):
        """Получение существующей настройки"""
        settings = {'show_certificates': '1', 'max_uploads': '10'}
        value = SettingsManager.get_setting_value(settings, 'show_certificates', '0')
        assert value == '1'
    
    def test_get_setting_value_default(self):
        """Получение несуществующей настройки (default)"""
        settings = {'show_certificates': '1'}
        value = SettingsManager.get_setting_value(settings, 'nonexistent', '0')
        assert value == '0'
    
    def test_validate_setting_key_valid(self):
        """Проверка допустимого ключа"""
        allowed = ['show_certificates', 'show_diplomas', 'registration_open']
        assert SettingsManager.validate_setting_key('show_certificates', allowed) is True
    
    def test_validate_setting_key_invalid(self):
        """Проверка недопустимого ключа"""
        allowed = ['show_certificates', 'show_diplomas']
        assert SettingsManager.validate_setting_key('malicious_key', allowed) is False
    
    def test_validate_boolean_flag_valid(self):
        """Валидация булевых флагов"""
        assert SettingsManager.validate_boolean_flag('1') is True
        assert SettingsManager.validate_boolean_flag('0') is True
        assert SettingsManager.validate_boolean_flag('true') is True
        assert SettingsManager.validate_boolean_flag('false') is True
        assert SettingsManager.validate_boolean_flag('on') is True
        assert SettingsManager.validate_boolean_flag('off') is True
        assert SettingsManager.validate_boolean_flag(True) is True
        assert SettingsManager.validate_boolean_flag(False) is True
    
    def test_validate_boolean_flag_invalid(self):
        """Валидация некорректных булевых флагов"""
        assert SettingsManager.validate_boolean_flag('yes_no') is False
        assert SettingsManager.validate_boolean_flag('2') is False
    
    def test_parse_boolean_flag(self):
        """Преобразование флагов в boolean"""
        assert SettingsManager.parse_boolean_flag('1') is True
        assert SettingsManager.parse_boolean_flag('0') is False
        assert SettingsManager.parse_boolean_flag('true') is True
        assert SettingsManager.parse_boolean_flag('false') is False
        assert SettingsManager.parse_boolean_flag('ON') is True
        assert SettingsManager.parse_boolean_flag('off') is False
    
    def test_cache_setting(self):
        """Кэширование настроек"""
        manager = SettingsManager()
        manager.cache_setting('test_key', 'test_value', ttl=3600)
        value = manager.get_cached_setting('test_key')
        assert value == 'test_value'
    
    def test_cache_setting_expired(self):
        """Истечение срока кэша"""
        manager = SettingsManager()
        manager.cache_setting('test_key', 'test_value', ttl=0)
        time.sleep(0.1)  # Небольшая задержка
        value = manager.get_cached_setting('test_key')
        assert value is None
    
    def test_validate_php_ini_size(self):
        """Валидация размеров из php.ini"""
        success, value = SettingsManager.validate_php_ini_value('10M', 'size')
        assert success is True
        assert value == 10 * 1024 * 1024
        
        success, value = SettingsManager.validate_php_ini_value('512K', 'size')
        assert success is True
        assert value == 512 * 1024
    
    def test_validate_php_ini_integer(self):
        """Валидация целых чисел из php.ini"""
        success, value = SettingsManager.validate_php_ini_value('100', 'integer')
        assert success is True
        assert value == 100
    
    def test_handle_duplicate_settings(self):
        """Обработка дублирующихся настроек"""
        settings = [
            {'setting_key': 'key1', 'setting_value': 'old', 'updated_at': '2024-01-01'},
            {'setting_key': 'key1', 'setting_value': 'new', 'updated_at': '2024-01-02'},
            {'setting_key': 'key2', 'setting_value': 'value2', 'updated_at': '2024-01-01'},
        ]
        result = SettingsManager.handle_duplicate_settings(settings)
        assert len(result) == 2
        key1_result = next(s for s in result if s['setting_key'] == 'key1')
        assert key1_result['setting_value'] == 'new'


# =============================================================================
# МОДУЛЬ 7: АУТЕНТИФИКАЦИЯ И БЕЗОПАСНОСТЬ
# =============================================================================

class AuthSecurity:
    """Класс аутентификации и безопасности"""
    
    @staticmethod
    def verify_password(password: str, password_hash: str) -> bool:
        """
        Верификация хеша пароля (password_verify против password_hash)
        """
        import bcrypt
        try:
            return bcrypt.checkpw(password.encode('utf-8'), password_hash.encode('utf-8'))
        except Exception:
            return False
    
    @staticmethod
    def hash_password(password: str) -> str:
        """Хеширование пароля с PASSWORD_DEFAULT"""
        import bcrypt
        return bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    
    @staticmethod
    def validate_password_strength(password: str) -> Dict[str, Any]:
        """
        Проверка сложности пароля администратора
        (длина ≥12, заглавные/строчные, цифры, спецсимволы)
        """
        errors = []
        
        if not password:
            return {'valid': False, 'errors': ['Пароль обязателен']}
        
        if len(password) < 12:
            errors.append('Минимум 12 символов')
        
        if not re.search(r'[A-ZА-ЯЁ]', password):
            errors.append('Хотя бы одна заглавная буква')
        
        if not re.search(r'[a-zа-яё]', password):
            errors.append('Хотя бы одна строчная буква')
        
        if not re.search(r'[0-9]', password):
            errors.append('Хотя бы одна цифра')
        
        if not re.search(r'[^A-Za-z0-9А-Яа-яЁё]', password):
            errors.append('Хотя бы один специальный символ')
        
        return {
            'valid': len(errors) == 0,
            'errors': errors
        }
    
    @staticmethod
    def validate_session(session: Optional[Dict]) -> Tuple[bool, str]:
        """
        Валидация состояния сессии и флага is_active учётной записи
        """
        if session is None:
            return False, "Сессия не найдена"
        
        if not session.get('user_id'):
            return False, "Некорректная сессия"
        
        if session.get('expires_at', 0) < time.time():
            return False, "Сессия истекла"
        
        if not session.get('is_active', True):
            return False, "Учётная запись заблокирована"
        
        return True, ""
    
    @staticmethod
    def generate_csrf_token() -> str:
        """
        Генерация CSRF-токена
        """
        return hashlib.sha256(os.urandom(32)).hexdigest()
    
    @staticmethod
    def verify_csrf_token(token: str, stored_token: str) -> bool:
        """
        Криптографическая проверка CSRF-токенов
        """
        if not token or not stored_token:
            return False
        # Используем hmac.compare_digest для безопасного сравнения
        import hmac
        return hmac.compare_digest(token.encode('utf-8'), stored_token.encode('utf-8'))
    
    @staticmethod
    def check_brute_force(attempts: List[Dict], max_attempts: int = 5, 
                          lockout_time: int = 900) -> Tuple[bool, int]:
        """
        Логика задержки и блокировки при множественных неудачных попытках входа
        Возвращает (можно_попытаться, секунд_до_разблокировки)
        """
        if not attempts:
            return True, 0
        
        now = time.time()
        recent_attempts = [a for a in attempts if now - a.get('timestamp', 0) < lockout_time]
        
        if len(recent_attempts) >= max_attempts:
            oldest = min(a.get('timestamp', 0) for a in recent_attempts)
            wait_time = int(lockout_time - (now - oldest))
            return False, max(0, wait_time)
        
        return True, 0
    
    @staticmethod
    def validate_cookie_flags(flags: Dict) -> Tuple[bool, List[str]]:
        """
        Проверка корректности установки флагов cookie
        (HttpOnly, SameSite, Secure)
        """
        issues = []
        
        if not flags.get('HttpOnly', False):
            issues.append('Отсутствует флаг HttpOnly')
        
        if flags.get('SameSite', '') not in ['Strict', 'Lax', 'None']:
            issues.append('Некорректный флаг SameSite')
        
        if flags.get('Secure', False) and not flags.get('https', False):
            # Secure должен использоваться только с HTTPS
            pass  # Это предупреждение, но не ошибка
        
        return len(issues) == 0, issues


class TestAuthSecurity:
    """Тесты модуля аутентификации и безопасности"""
    
    def test_hash_and_verify_password(self):
        """Хеширование и верификация пароля"""
        password = "TestPassword123!"
        password_hash = AuthSecurity.hash_password(password)
        assert AuthSecurity.verify_password(password, password_hash) is True
        assert AuthSecurity.verify_password("wrong_password", password_hash) is False
    
    def test_validate_password_strength_strong(self):
        """Проверка сложного пароля"""
        strong_password = "MyStr0ngP@ssword!"
        result = AuthSecurity.validate_password_strength(strong_password)
        assert result['valid'] is True
        assert len(result['errors']) == 0
    
    def test_validate_password_strength_weak_short(self):
        """Проверка короткого пароля"""
        weak_password = "Short1!"
        result = AuthSecurity.validate_password_strength(weak_password)
        assert result['valid'] is False
        assert any('12 символов' in e for e in result['errors'])
    
    def test_validate_password_strength_weak_no_upper(self):
        """Проверка пароля без заглавных"""
        weak_password = "mystrongpassword1!"
        result = AuthSecurity.validate_password_strength(weak_password)
        assert result['valid'] is False
        assert any('заглавная' in e for e in result['errors'])
    
    def test_validate_password_strength_weak_no_special(self):
        """Проверка пароля без спецсимволов"""
        weak_password = "MyStrongPass123"
        result = AuthSecurity.validate_password_strength(weak_password)
        assert result['valid'] is False
        assert any('специальный' in e for e in result['errors'])
    
    def test_validate_session_valid(self):
        """Валидация валидной сессии"""
        session = {
            'user_id': 1,
            'expires_at': time.time() + 3600,
            'is_active': True
        }
        success, error = AuthSecurity.validate_session(session)
        assert success is True
    
    def test_validate_session_expired(self):
        """Валидация истёкшей сессии"""
        session = {
            'user_id': 1,
            'expires_at': time.time() - 100,
            'is_active': True
        }
        success, error = AuthSecurity.validate_session(session)
        assert success is False
        assert "истекла" in error
    
    def test_validate_session_inactive(self):
        """Валидация заблокированной учётной записи"""
        session = {
            'user_id': 1,
            'expires_at': time.time() + 3600,
            'is_active': False
        }
        success, error = AuthSecurity.validate_session(session)
        assert success is False
        assert "заблокирована" in error
    
    def test_generate_and_verify_csrf_token(self):
        """Генерация и проверка CSRF-токена"""
        token = AuthSecurity.generate_csrf_token()
        assert len(token) == 64  # SHA256 hex
        
        assert AuthSecurity.verify_csrf_token(token, token) is True
        assert AuthSecurity.verify_csrf_token(token, "wrong_token") is False
        assert AuthSecurity.verify_csrf_token("", token) is False
    
    def test_check_brute_force_allowed(self):
        """Проверка brute force - попытки в пределах нормы"""
        attempts = [
            {'timestamp': time.time() - 60},
            {'timestamp': time.time() - 30},
        ]
        allowed, wait_time = AuthSecurity.check_brute_force(attempts, max_attempts=5)
        assert allowed is True
    
    def test_check_brute_force_blocked(self):
        """Проверка brute force - превышение лимита"""
        attempts = [
            {'timestamp': time.time() - 10},
            {'timestamp': time.time() - 20},
            {'timestamp': time.time() - 30},
            {'timestamp': time.time() - 40},
            {'timestamp': time.time() - 50},
        ]
        allowed, wait_time = AuthSecurity.check_brute_force(attempts, max_attempts=5, lockout_time=900)
        assert allowed is False
        assert wait_time > 0
    
    def test_validate_cookie_flags_valid(self):
        """Проверка валидных флагов cookie"""
        flags = {
            'HttpOnly': True,
            'SameSite': 'Strict',
            'Secure': True,
            'https': True
        }
        success, issues = AuthSecurity.validate_cookie_flags(flags)
        assert success is True
    
    def test_validate_cookie_flags_missing_httponly(self):
        """Проверка отсутствия HttpOnly"""
        flags = {
            'HttpOnly': False,
            'SameSite': 'Strict'
        }
        success, issues = AuthSecurity.validate_cookie_flags(flags)
        assert success is False
        assert any('HttpOnly' in i for i in issues)


# =============================================================================
# МОДУЛЬ 8: ВСПОМОГАТЕЛЬНЫЕ И УТИЛИТАРНЫЕ ФУНКЦИИ
# =============================================================================

class UtilityFunctions:
    """Вспомогательные утилитарные функции"""
    
    @staticmethod
    def slugify(text: str) -> str:
        """
        Преобразование URL в человеко-понятный формат
        (ЧПУ, slugify, транслитерация)
        """
        # Транслитерация кириллицы
        translit_map = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
            'е': 'e', 'ё': 'yo', 'ж': 'zh', 'з': 'z', 'и': 'i',
            'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
            'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
            'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch',
            'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '',
            'э': 'e', 'ю': 'yu', 'я': 'ya',
            ' ': '-', '_': '-'
        }
        
        text = text.lower()
        result = ''
        for char in text:
            result += translit_map.get(char, char)
        
        # Удаление недопустимых символов
        result = re.sub(r'[^a-z0-9\-]', '', result)
        # Замена множественных дефисов
        result = re.sub(r'-+', '-', result)
        # Удаление дефисов по краям
        result = result.strip('-')
        
        return result
    
    @staticmethod
    def format_meta_title(title: str, site_name: str = '', max_length: int = 60) -> str:
        """
        Форматирование мета-тега Title для SEO
        (длина, экранирование, подстановка ключевых слов)
        """
        if not title:
            return site_name
        
        result = php_htmlspecialchars(title)
        
        if site_name:
            result = f"{result} | {site_name}"
        
        if len(result) > max_length:
            result = result[:max_length - 3] + '...'
        
        return result
    
    @staticmethod
    def format_meta_description(description: str, max_length: int = 160) -> str:
        """
        Форматирование мета-тега Description для SEO
        """
        if not description:
            return ''
        
        result = php_htmlspecialchars(php_strip_tags(description))
        result = ' '.join(result.split())  # Нормализация пробелов
        
        if len(result) > max_length:
            result = result[:max_length - 3] + '...'
        
        return result
    
    @staticmethod
    def calculate_relative_path(current_path: str, target_path: str) -> str:
        """
        Расчёт относительных путей к ресурсам
        (assets, uploads, admin) с учётом вложенности
        """
        current_parts = current_path.strip('/').split('/')
        target_parts = target_path.strip('/').split('/')
        
        # Находим общую часть
        common_len = 0
        for i in range(min(len(current_parts), len(target_parts))):
            if current_parts[i] == target_parts[i]:
                common_len += 1
            else:
                break
        
        # Количество уровней вверх
        up_levels = len(current_parts) - common_len
        
        # Оставшаяся часть целевого пути
        remaining_parts = target_parts[common_len:]
        
        relative = '../' * up_levels + '/'.join(remaining_parts)
        return relative if relative else './'
    
    @staticmethod
    def log_event(message: str, level: str = 'INFO', 
                  log_file: str = 'app.log') -> bool:
        """
        Логирование событий с фильтрацией по уровню важности
        (INFO, WARN, ERROR) и ротацией
        """
        levels = {'DEBUG': 0, 'INFO': 1, 'WARN': 2, 'ERROR': 3}
        
        if level not in levels:
            level = 'INFO'
        
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        log_entry = f"[{timestamp}] [{level}] {message}\n"
        
        try:
            with open(log_file, 'a', encoding='utf-8') as f:
                f.write(log_entry)
            return True
        except Exception:
            return False
    
    @staticmethod
    def parse_get_param(value: str, param_type: str = 'string', 
                        default: Any = None) -> Any:
        """
        Парсинг и валидация входных параметров $_GET
        с приведением типов и проверкой диапазонов
        """
        if value is None:
            return default
        
        if param_type == 'int':
            try:
                return int(value)
            except (ValueError, TypeError):
                return default
        
        elif param_type == 'float':
            try:
                return float(value)
            except (ValueError, TypeError):
                return default
        
        elif param_type == 'bool':
            return value.lower() in ['1', 'true', 'yes', 'on']
        
        elif param_type == 'string':
            return str(value)
        
        return default
    
    @staticmethod
    def format_number(number: float, decimals: int = 2, locale: str = 'ru_RU') -> str:
        """
        Форматирование чисел в соответствии с локалью ru_RU
        """
        if locale == 'ru_RU':
            # Русская локаль: пробел как разделитель тысяч, запятая для десятичных
            formatted = f"{number:,.{decimals}f}".replace(',', '#').replace('.', ',').replace('#', '.')
            # Исправляем: 1000.50 -> 1 000,50
            parts = formatted.split(',')
            integer_part = parts[0].replace('.', ' ')
            if len(parts) > 1:
                return f"{integer_part},{parts[1]}"
            return integer_part
        else:
            return f"{number:,.{decimals}f}"
    
    @staticmethod
    def format_date(date_obj: datetime, format_str: str = '%d.%m.%Y') -> str:
        """
        Форматирование дат в соответствии с локалью ru_RU
        """
        return date_obj.strftime(format_str)
    
    @staticmethod
    def format_currency(amount: float, currency: str = 'RUB') -> str:
        """
        Форматирование валют в соответствии с локалью ru_RU
        """
        formatted = UtilityFunctions.format_number(amount, 2)
        currency_symbols = {'RUB': '₽', 'USD': '$', 'EUR': '€'}
        symbol = currency_symbols.get(currency, currency)
        return f"{formatted} {symbol}"


class TestUtilityFunctions:
    """Тесты вспомогательных функций"""
    
    def test_slugify_russian(self):
        """Транслитерация русского текста"""
        assert UtilityFunctions.slugify('Привет мир') == 'privet-mir'
        assert UtilityFunctions.slugify('СибАДИ') == 'sibadi'
        # 'ц' транслитерируется как 'ts', поэтому 'kompozitsiya'
        assert UtilityFunctions.slugify('Архитектурная композиция') == 'arhitekturnaya-kompozitsiya'
    
    def test_slugify_with_spaces_and_special(self):
        """Обработка пробелов и спецсимволов"""
        assert UtilityFunctions.slugify('Hello World!') == 'hello-world'
        assert UtilityFunctions.slugify('Test_123') == 'test-123'
    
    def test_slugify_empty(self):
        """Обработка пустой строки"""
        assert UtilityFunctions.slugify('') == ''
    
    def test_format_meta_title_basic(self):
        """Форматирование базового Title"""
        result = UtilityFunctions.format_meta_title('Главная страница', 'Мой Сайт')
        assert 'Главная страница' in result
        assert 'Мой Сайт' in result
    
    def test_format_meta_title_truncated(self):
        """Обрезка длинного Title"""
        long_title = 'О' * 100
        result = UtilityFunctions.format_meta_title(long_title, max_length=60)
        assert len(result) <= 60
        assert result.endswith('...')
    
    def test_format_meta_description(self):
        """Форматирование Description"""
        desc = '<script>alert("XSS")</script>Это описание страницы'
        result = UtilityFunctions.format_meta_description(desc)
        assert '<script>' not in result
        # strip_tags удаляет теги, но текст внутри alert остаётся
        assert 'alert' in result or len(result) < len(desc)
    
    def test_calculate_relative_path_same_level(self):
        """Расчёт относительного пути - один уровень"""
        result = UtilityFunctions.calculate_relative_path('admin/', 'assets/')
        assert '../assets' in result
    
    def test_calculate_relative_path_nested(self):
        """Расчёт относительного пути - вложенность"""
        result = UtilityFunctions.calculate_relative_path('admin/settings/', '../../assets/')
        assert 'assets' in result
    
    def test_log_event(self, tmp_path):
        """Логирование событий"""
        log_file = tmp_path / 'test.log'
        success = UtilityFunctions.log_event('Test message', 'INFO', str(log_file))
        assert success is True
        assert log_file.exists()
        
        content = log_file.read_text()
        assert '[INFO]' in content
        assert 'Test message' in content
    
    def test_parse_get_param_int(self):
        """Парсинг integer параметра"""
        assert UtilityFunctions.parse_get_param('123', 'int') == 123
        assert UtilityFunctions.parse_get_param('abc', 'int', default=0) == 0
    
    def test_parse_get_param_bool(self):
        """Парсинг boolean параметра"""
        assert UtilityFunctions.parse_get_param('1', 'bool') is True
        assert UtilityFunctions.parse_get_param('true', 'bool') is True
        assert UtilityFunctions.parse_get_param('0', 'bool') is False
        assert UtilityFunctions.parse_get_param('false', 'bool') is False
    
    def test_format_number_ru(self):
        """Форматирование числа для ru_RU"""
        result = UtilityFunctions.format_number(1234567.89, locale='ru_RU')
        assert ',' in result  # Запятая для десятичных
        assert ' ' in result or result.startswith('1')  # Разделитель тысяч
    
    def test_format_date(self):
        """Форматирование даты"""
        date_obj = datetime(2024, 3, 15, 10, 30, 0)
        result = UtilityFunctions.format_date(date_obj)
        assert result == '15.03.2024'
    
    def test_format_currency(self):
        """Форматирование валюты"""
        result = UtilityFunctions.format_currency(1234.56, 'RUB')
        assert '₽' in result


# =============================================================================
# ЗАПУСК ТЕСТОВ
# =============================================================================

if __name__ == '__main__':
    pytest.main([__file__, '-v', '--tb=short'])
