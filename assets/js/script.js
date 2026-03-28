document.addEventListener('DOMContentLoaded', function() {
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