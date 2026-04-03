document.addEventListener('DOMContentLoaded', function() {
    // Маппинг значений объединённого поля на номинацию и раздел
    const nominationSectionMap = {
        'arch_composition_abstract': { nomination: 'arch_composition', section: 'Абстрактная' },
        'arch_composition_genre': { nomination: 'arch_composition', section: 'Жанровая' },
        'arch_composition_typographic': { nomination: 'arch_composition', section: 'Шрифтовая' },
        'art_graphics_clausura': { nomination: 'art_graphics', section: 'Клаузура' },
        'art_graphics_project_drawing': { nomination: 'art_graphics', section: 'Рисунок к проекту' },
        'art_graphics_postcard': { nomination: 'art_graphics', section: 'Открытка' },
        'art_graphics_pattern': { nomination: 'art_graphics', section: 'Паттерн' },
        'nature_drawing_landscape': { nomination: 'nature_drawing', section: 'Архитектурный пейзаж' },
        'photography_photo_project': { nomination: 'photography', section: 'Фотопроект' },
        'photography_photo_collage': { nomination: 'photography', section: 'Фотоколлаж' }
    };

    const nominationSectionSelect = document.getElementById('nomination_section');
    const nominationInput = document.getElementById('nomination');
    const sectionInput = document.getElementById('section');
    
    // Функция обновления скрытых полей при выборе номинации и раздела
    function updateHiddenFields() {
        const selectedValue = nominationSectionSelect.value;
        
        if (!selectedValue || !nominationSectionMap[selectedValue]) {
            if (nominationInput) nominationInput.value = '';
            if (sectionInput) sectionInput.value = '';
            return;
        }
        
        const data = nominationSectionMap[selectedValue];
        if (nominationInput) nominationInput.value = data.nomination;
        if (sectionInput) sectionInput.value = data.section;
    }
    
    // Слушаем изменения в выборе номинации и раздела
    if (nominationSectionSelect) {
        nominationSectionSelect.addEventListener('change', updateHiddenFields);
        
        // Инициализируем скрытые поля при загрузке страницы
        updateHiddenFields();
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
            if (nominationSectionSelect && !nominationSectionSelect.value) {
                e.preventDefault();
                alert('Пожалуйста, выберите номинацию и раздел');
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