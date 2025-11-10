/**
 * ISER Authentication System - Installer JavaScript
 * @author ISER Desarrollo
 */

(function() {
    'use strict';

    /**
     * Inicializar cuando el DOM esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        initFormValidation();
        initTooltips();
        initPasswordToggle();
    });

    /**
     * Inicializar validación de formularios
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form');

        forms.forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    /**
     * Inicializar tooltips de Bootstrap
     */
    function initTooltips() {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );

        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Añadir funcionalidad para mostrar/ocultar contraseña
     */
    function initPasswordToggle() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(function(input) {
            const wrapper = input.parentElement;
            if (wrapper.classList.contains('input-group')) return;

            // Crear contenedor
            const container = document.createElement('div');
            container.className = 'position-relative';

            // Mover input al contenedor
            input.parentNode.insertBefore(container, input);
            container.appendChild(input);

            // Crear botón toggle
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'btn btn-sm btn-outline-secondary position-absolute';
            toggleBtn.style.cssText = 'right: 10px; top: 50%; transform: translateY(-50%); z-index: 10;';
            toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';

            toggleBtn.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    toggleBtn.innerHTML = '<i class="bi bi-eye"></i>';
                }
            });

            container.appendChild(toggleBtn);
        });
    }

    /**
     * Utilidad para mostrar notificaciones
     */
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        if (duration > 0) {
            setTimeout(function() {
                notification.classList.remove('show');
                setTimeout(function() {
                    notification.remove();
                }, 150);
            }, duration);
        }
    };

    /**
     * Utilidad para validar email
     */
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    /**
     * Utilidad para validar contraseña
     */
    window.validatePassword = function(password) {
        return {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
            isValid: function() {
                return this.length && this.uppercase && this.lowercase && this.number;
            }
        };
    };

    /**
     * Utilidad para capitalizar texto
     */
    window.capitalize = function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    };

    /**
     * Prevenir envío duplicado de formularios
     */
    window.preventDoubleSubmit = function(form) {
        let submitted = false;

        form.addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
                return false;
            }

            if (form.checkValidity()) {
                submitted = true;

                // Deshabilitar botones de envío
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(function(btn) {
                    btn.disabled = true;
                    if (btn.tagName === 'BUTTON') {
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
                    }
                });
            }
        });
    };

    // Aplicar prevención de doble envío a todos los formularios
    document.querySelectorAll('form').forEach(function(form) {
        preventDoubleSubmit(form);
    });

})();
