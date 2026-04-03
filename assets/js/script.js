document.addEventListener('DOMContentLoaded', function() {
    // Данные о разделах для каждой номинации (из положения)
    const sectionsByNomination = {
        'arch_composition': [
            'Абстрактная',
            'Жанровая',
            'Шрифтовая'
        ],
        'art_graphics': [
            'Клаузура',
            'Рисунок к проекту',
            'Открытка',
            'Паттерн'
        ],
        'nature_drawing': [
            'Архитектурный пейзаж'
        ],
        'photography': [
            'Фотопроект',
            'Фотоколлаж'
        ]
    };

    const nominationSelect = document.getElementById('nomination');
    const sectionSelect = document.getElementById('section');
    
    // Функция обновления разделов в зависимости от выбранной номинации
    function updateSections() {
        const selectedNomination = nominationSelect.value;
        
        // Очищаем текущие опции
        sectionSelect.innerHTML = '<option value="">Выберите раздел</option>';
        
        if (!selectedNomination) {
            // Если номинация не выбрана - блокируем выбор разделов
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Сначала выберите номинацию</option>';
            return;
        }
        
        // Получаем разделы для выбранной номинации
        const sections = sectionsByNomination[selectedNomination] || [];
        
        // Добавляем опции разделов
        sections.forEach(function(section) {
            const option = document.createElement('option');
            option.value = section;
            option.textContent = section;
            sectionSelect.appendChild(option);
        });
        
        // Разблокируем выбор разделов
        sectionSelect.disabled = false;
    }
    
    // Слушаем изменения в выборе номинации
    if (nominationSelect) {
        nominationSelect.addEventListener('change', updateSections);
        
        // Восстанавливаем разделы при загрузке страницы (если номинация уже выбрана)
        // Это нужно для случая, когда форма показывается с ошибками валидации
        const savedNomination = nominationSelect.value;
        if (savedNomination) {
            updateSections();
            
            // Восстанавливаем выбранный раздел, если он был сохранён
            const sectionSelectCurrent = document.getElementById('section');
            const savedSection = '<?php echo htmlspecialchars($formData['section'] ?? '', ENT_QUOTES); ?>';
            if (savedSection && sectionSelectCurrent) {
                sectionSelectCurrent.value = savedSection;
            }
        }
    }
    
    const form = document.getElementById('registerForm');
    const fileInput = document.getElementById('work');
    const maxSize = 20 * 1024 * 1024;
    const minSize = 1 * 1024 * 1024;
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > maxSize) {
                    alert('Размер файла не должен превышать 20 Мб');
                    this.value = '';
                    return;
                }
                if (file.size < minSize) {
                    alert('Размер файла должен быть не менее 1 Мб');
                    this.value = '';
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Допустимы только файлы формата .jpeg, .jpg');
                    this.value = '';
                    return;
                }
            }
        });
    }
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const consent = form.querySelector('input[name="consent"]');
            if (consent && !consent.checked) {
                e.preventDefault();
                alert('Необходимо согласие на обработку персональных данных');
                return;
            }
            
            // Дополнительная проверка: выбрана ли номинация и раздел
            if (nominationSelect && !nominationSelect.value) {
                e.preventDefault();
                alert('Пожалуйста, выберите номинацию');
                return;
            }
            if (sectionSelect && !sectionSelect.value) {
                e.preventDefault();
                alert('Пожалуйста, выберите раздел');
                return;
            }
        });
    }
    
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            if (img) {
                openLightbox(img.src);
            }
        });
    });
    
    function openLightbox(src) {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <img src="${src}" alt="Просмотр работы">
                <button class="lightbox-close">&times;</button>
            </div>
        `;
        document.body.appendChild(lightbox);
        
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target.classList.contains('lightbox-close')) {
                lightbox.remove();
            }
        });
        
        document.addEventListener('keydown', function closeOnEsc(e) {
            if (e.key === 'Escape') {
                lightbox.remove();
                document.removeEventListener('keydown', closeOnEsc);
            }
        });
    }
});