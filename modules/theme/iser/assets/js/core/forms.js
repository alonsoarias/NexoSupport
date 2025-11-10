/**
 * Componente de formularios del tema ISER
 * @version 1.0.0
 */

class ISERForms {
    constructor() {
        this.init();
    }

    init() {
        this.setupValidation();
        this.setupFileUploads();
        this.setupDatePickers();
        this.setupPasswordToggles();
        this.setupCharCounters();
        this.setupAutoSave();
    }

    /**
     * Configurar validación de formularios
     */
    setupValidation() {
        const forms = document.querySelectorAll('.needs-validation');

        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Scroll al primer error
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstInvalid.focus();
                    }
                }

                form.classList.add('was-validated');
            }, false);

            // Validación en tiempo real
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    if (form.classList.contains('was-validated')) {
                        this.validateField(input);
                    }
                });

                input.addEventListener('input', () => {
                    if (form.classList.contains('was-validated')) {
                        this.validateField(input);
                    }
                });
            });
        });
    }

    /**
     * Validar campo individual
     */
    validateField(field) {
        if (field.checkValidity()) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }

    /**
     * Configurar file uploads
     */
    setupFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"]');

        fileInputs.forEach(input => {
            const label = input.nextElementSibling;
            const labelText = label ? label.textContent : 'Seleccionar archivo';

            input.addEventListener('change', () => {
                const files = input.files;
                if (files.length > 0) {
                    const fileNames = Array.from(files).map(f => f.name).join(', ');
                    if (label) {
                        label.textContent = fileNames;
                    }

                    // Previsualización de imágenes
                    if (input.accept && input.accept.includes('image')) {
                        this.previewImage(input, files[0]);
                    }
                } else {
                    if (label) {
                        label.textContent = labelText;
                    }
                }
            });
        });
    }

    /**
     * Previsualizar imagen
     */
    previewImage(input, file) {
        const previewId = input.dataset.preview;
        if (!previewId) return;

        const preview = document.getElementById(previewId);
        if (!preview) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    /**
     * Configurar date pickers (si hay librería)
     */
    setupDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]');

        dateInputs.forEach(input => {
            // Agregar icono de calendario
            const wrapper = document.createElement('div');
            wrapper.className = 'input-group';

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const icon = document.createElement('span');
            icon.className = 'input-group-text';
            icon.innerHTML = '<i class="fas fa-calendar"></i>';
            wrapper.appendChild(icon);

            icon.addEventListener('click', () => {
                input.showPicker && input.showPicker();
            });
        });
    }

    /**
     * Configurar toggles de contraseña
     */
    setupPasswordToggles() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(input => {
            // Si ya tiene toggle, saltar
            if (input.dataset.hasToggle) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'input-group';

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const button = document.createElement('button');
            button.className = 'btn btn-outline-secondary';
            button.type = 'button';
            button.innerHTML = '<i class="fas fa-eye"></i>';
            button.setAttribute('title', 'Mostrar/ocultar contraseña');
            wrapper.appendChild(button);

            button.addEventListener('click', () => {
                if (input.type === 'password') {
                    input.type = 'text';
                    button.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    button.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });

            input.dataset.hasToggle = 'true';
        });
    }

    /**
     * Configurar contadores de caracteres
     */
    setupCharCounters() {
        const textareas = document.querySelectorAll('textarea[maxlength]');

        textareas.forEach(textarea => {
            // Si ya tiene contador, saltar
            if (textarea.dataset.hasCounter) return;

            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end char-counter';

            const updateCounter = () => {
                const length = textarea.value.length;
                counter.textContent = `${length} / ${maxLength}`;

                if (length >= maxLength * 0.9) {
                    counter.classList.add('text-warning');
                } else {
                    counter.classList.remove('text-warning');
                }

                if (length >= maxLength) {
                    counter.classList.add('text-danger');
                    counter.classList.remove('text-warning');
                } else {
                    counter.classList.remove('text-danger');
                }
            };

            textarea.parentNode.appendChild(counter);
            updateCounter();

            textarea.addEventListener('input', updateCounter);
            textarea.dataset.hasCounter = 'true';
        });
    }

    /**
     * Configurar auto-guardado
     */
    setupAutoSave() {
        const forms = document.querySelectorAll('[data-autosave]');

        forms.forEach(form => {
            const interval = parseInt(form.dataset.autosave) || 30000; // 30 segundos por defecto
            const inputs = form.querySelectorAll('input, select, textarea');

            let timeout;
            const autoSave = () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.saveFormData(form);
                }, interval);
            };

            inputs.forEach(input => {
                input.addEventListener('input', autoSave);
            });

            // Restaurar datos guardados
            this.restoreFormData(form);
        });
    }

    /**
     * Guardar datos del formulario
     */
    saveFormData(form) {
        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }

        const formId = form.id || form.getAttribute('name');
        if (formId) {
            localStorage.setItem(`iser_form_${formId}`, JSON.stringify(data));

            // Mostrar indicador de guardado
            this.showSaveIndicator(form);
        }
    }

    /**
     * Restaurar datos del formulario
     */
    restoreFormData(form) {
        const formId = form.id || form.getAttribute('name');
        if (!formId) return;

        const savedData = localStorage.getItem(`iser_form_${formId}`);
        if (!savedData) return;

        try {
            const data = JSON.parse(savedData);

            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && !input.value) {
                    input.value = data[key];
                }
            });
        } catch (error) {
            console.error('Error restaurando datos del formulario:', error);
        }
    }

    /**
     * Mostrar indicador de guardado
     */
    showSaveIndicator(form) {
        let indicator = form.querySelector('.autosave-indicator');

        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator';
            indicator.style.cssText = 'position: absolute; top: 10px; right: 10px; background: #00d97e; color: white; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.875rem;';
            form.style.position = 'relative';
            form.appendChild(indicator);
        }

        indicator.textContent = 'Guardado';
        indicator.style.display = 'block';

        setTimeout(() => {
            indicator.style.display = 'none';
        }, 2000);
    }

    /**
     * Limpiar datos guardados del formulario
     */
    clearSavedFormData(formId) {
        localStorage.removeItem(`iser_form_${formId}`);
    }
}

// Auto-inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ISERForms = new ISERForms();
    });
} else {
    window.ISERForms = new ISERForms();
}
