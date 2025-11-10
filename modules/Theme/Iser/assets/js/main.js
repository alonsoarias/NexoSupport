/**
 * JavaScript principal del tema ISER
 * @version 1.0.0
 * @author ISER Desarrollo
 */

class ISERTheme {
    constructor() {
        this.config = window.ISER_THEME || {};
        this.init();
    }

    /**
     * Inicializar el tema
     */
    init() {
        console.log('ISER Theme v1.0.0 inicializado');

        this.initializeComponents();
        this.setupEventListeners();
        this.setupThemeMode();
        this.initializeSidebar();
    }

    /**
     * Inicializar componentes de Bootstrap
     */
    initializeComponents() {
        // Inicializar tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Inicializar popovers
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        [...popoverTriggerList].forEach(popoverTriggerEl => {
            new bootstrap.Popover(popoverTriggerEl);
        });

        // Inicializar modales
        this.initializeModals();

        // Inicializar formularios
        this.initializeForms();

        // Inicializar tablas
        this.initializeDataTables();

        // Inicializar dropdowns
        this.initializeDropdowns();
    }

    /**
     * Inicializar modales
     */
    initializeModals() {
        // Configuración global de modales
        document.addEventListener('show.bs.modal', (e) => {
            const modal = e.target;
            this.loadModalContent(modal);
        });

        // Modal de confirmación genérico
        window.showConfirmModal = (message, onConfirm) => {
            const modal = document.getElementById('confirmModal');
            const messageEl = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalConfirm');

            if (modal && messageEl && confirmBtn) {
                messageEl.textContent = message;

                // Remover listeners previos
                const newConfirmBtn = confirmBtn.cloneNode(true);
                confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

                // Agregar nuevo listener
                newConfirmBtn.addEventListener('click', () => {
                    onConfirm();
                    bootstrap.Modal.getInstance(modal).hide();
                });

                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        };

        // Modal de alerta genérico
        window.showAlertModal = (message, title = 'Información') => {
            const modal = document.getElementById('alertModal');
            const titleEl = document.getElementById('alertModalLabel');
            const messageEl = document.getElementById('alertModalMessage');

            if (modal && titleEl && messageEl) {
                titleEl.textContent = title;
                messageEl.textContent = message;

                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        };
    }

    /**
     * Cargar contenido dinámico del modal
     */
    loadModalContent(modal) {
        const url = modal.dataset.loadUrl;
        if (url) {
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const body = modal.querySelector('.modal-body');
                    if (body) {
                        body.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error cargando contenido del modal:', error);
                });
        }
    }

    /**
     * Inicializar formularios
     */
    initializeForms() {
        // Validación de formularios
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Inputs dinámicos
        this.setupDynamicInputs();

        // Auto-submit en selects
        const autoSubmitSelects = document.querySelectorAll('select[data-auto-submit]');
        autoSubmitSelects.forEach(select => {
            select.addEventListener('change', () => {
                select.form.submit();
            });
        });
    }

    /**
     * Configurar inputs dinámicos
     */
    setupDynamicInputs() {
        // Contador de caracteres
        const textareas = document.querySelectorAll('textarea[maxlength]');
        textareas.forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.textContent = `0 / ${maxLength}`;
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', () => {
                const length = textarea.value.length;
                counter.textContent = `${length} / ${maxLength}`;

                if (length >= maxLength * 0.9) {
                    counter.classList.add('text-warning');
                } else {
                    counter.classList.remove('text-warning');
                }
            });
        });

        // Toggle de password
        const passwordToggles = document.querySelectorAll('[data-toggle-password]');
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.dataset.togglePassword;
                const input = document.getElementById(targetId);

                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        input.type = 'password';
                        toggle.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                }
            });
        });
    }

    /**
     * Inicializar tablas de datos
     */
    initializeDataTables() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            this.enhanceTable(table);
        });
    }

    /**
     * Mejorar tabla con funcionalidad de ordenamiento
     */
    enhanceTable(table) {
        const headers = table.querySelectorAll('th.sortable');

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const field = header.dataset.sortBy;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));

                // Determinar dirección del ordenamiento
                const currentSort = header.dataset.sortDirection || 'asc';
                const newSort = currentSort === 'asc' ? 'desc' : 'asc';

                // Resetear iconos de todas las cabeceras
                headers.forEach(h => {
                    h.querySelector('i').className = 'fas fa-sort ms-1 text-muted';
                    delete h.dataset.sortDirection;
                });

                // Actualizar icono de la cabecera actual
                const icon = header.querySelector('i');
                icon.className = `fas fa-sort-${newSort === 'asc' ? 'up' : 'down'} ms-1`;
                header.dataset.sortDirection = newSort;

                // Ordenar filas
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                rows.sort((a, b) => {
                    const aValue = a.children[columnIndex].textContent.trim();
                    const bValue = b.children[columnIndex].textContent.trim();

                    // Intentar comparar como números
                    const aNum = parseFloat(aValue);
                    const bNum = parseFloat(bValue);

                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return newSort === 'asc' ? aNum - bNum : bNum - aNum;
                    }

                    // Comparar como texto
                    return newSort === 'asc'
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                });

                // Reordenar en el DOM
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    /**
     * Inicializar dropdowns
     */
    initializeDropdowns() {
        // Auto-cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                const dropdowns = document.querySelectorAll('.dropdown-menu.show');
                dropdowns.forEach(dropdown => {
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                });
            }
        });
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Toggle del sidebar
        document.addEventListener('click', (e) => {
            if (e.target.closest('.sidebar-toggle')) {
                e.preventDefault();
                this.toggleSidebar();
            }
        });

        // Cambio de modo oscuro/claro
        document.addEventListener('click', (e) => {
            if (e.target.closest('.theme-toggle')) {
                e.preventDefault();
                this.toggleThemeMode();
            }
        });

        // Cerrar alerts
        document.addEventListener('click', (e) => {
            if (e.target.closest('.alert .btn-close')) {
                const alert = e.target.closest('.alert');
                this.hideAlert(alert);
            }
        });

        // Acciones de tablas
        document.addEventListener('click', (e) => {
            const actionButton = e.target.closest('[data-action]');
            if (actionButton) {
                e.preventDefault();
                this.handleTableAction(actionButton);
            }
        });

        // Confirmación antes de submit
        const confirmForms = document.querySelectorAll('form[data-confirm]');
        confirmForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Inicializar sidebar
     */
    initializeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Restaurar estado del sidebar
        const collapsed = localStorage.getItem('iser_sidebar_collapsed') === 'true';
        if (collapsed) {
            sidebar.classList.add('collapsed');
            const mainContent = document.querySelector('.main-content-area');
            if (mainContent) {
                mainContent.classList.add('expanded');
            }
        }

        // Cerrar sidebar en móviles al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('.sidebar') && !e.target.closest('.sidebar-toggle')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    /**
     * Toggle del sidebar
     */
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content-area');

        if (!sidebar) return;

        if (window.innerWidth <= 768) {
            // En móviles, toggle show/hide
            sidebar.classList.toggle('show');
        } else {
            // En desktop, toggle collapsed
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }

            // Guardar preferencia
            const isCollapsed = sidebar.classList.contains('collapsed');
            this.savePreference('sidebar_collapsed', isCollapsed);
        }
    }

    /**
     * Configurar modo de tema (oscuro/claro)
     */
    setupThemeMode() {
        // Cargar preferencia guardada
        const savedTheme = localStorage.getItem('iser_theme_mode') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        this.updateThemeIcon(savedTheme);
    }

    /**
     * Toggle del modo de tema
     */
    toggleThemeMode() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-bs-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        html.setAttribute('data-bs-theme', newTheme);
        this.savePreference('theme_mode', newTheme);
        this.updateThemeIcon(newTheme);

        // Guardar en servidor si hay usuario autenticado
        if (this.config.user && this.config.user.authenticated) {
            this.savePreferenceToServer('theme_mode', newTheme);
        }
    }

    /**
     * Actualizar icono del tema
     */
    updateThemeIcon(theme) {
        const icons = document.querySelectorAll('.theme-toggle i');
        icons.forEach(icon => {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }

    /**
     * Guardar preferencia en localStorage
     */
    savePreference(key, value) {
        localStorage.setItem(`iser_${key}`, value);
    }

    /**
     * Guardar preferencia en servidor
     */
    async savePreferenceToServer(key, value) {
        try {
            const response = await fetch(`${this.config.baseUrl}/api/v1/user/preferences`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.config.csrfToken
                },
                body: JSON.stringify({
                    key: `theme_${key}`,
                    value: value
                })
            });

            if (!response.ok) {
                throw new Error('Error guardando preferencia');
            }
        } catch (error) {
            console.warn('No se pudo guardar la preferencia en el servidor:', error);
        }
    }

    /**
     * Ocultar alert con animación
     */
    hideAlert(alert) {
        alert.classList.remove('show');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }

    /**
     * Manejar acciones de tabla
     */
    handleTableAction(button) {
        const action = button.dataset.action;
        const id = button.dataset.id || button.dataset.userid;

        switch (action) {
            case 'view-profile':
                window.location.href = `/profile?id=${id}`;
                break;

            case 'edit-user':
                window.location.href = `/admin/user/edit.php?id=${id}`;
                break;

            case 'delete-user':
                showConfirmModal(
                    '¿Está seguro de que desea eliminar este usuario?',
                    () => {
                        this.deleteUser(id);
                    }
                );
                break;

            default:
                console.warn(`Acción no manejada: ${action}`);
        }
    }

    /**
     * Eliminar usuario (ejemplo)
     */
    async deleteUser(userId) {
        try {
            const response = await fetch(`${this.config.baseUrl}/api/v1/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.config.csrfToken
                }
            });

            if (response.ok) {
                this.showNotification('Usuario eliminado correctamente', 'success');
                // Recargar la tabla o remover la fila
                const row = document.querySelector(`tr[data-id="${userId}"]`);
                if (row) {
                    row.remove();
                }
            } else {
                throw new Error('Error eliminando usuario');
            }
        } catch (error) {
            this.showNotification('Error al eliminar el usuario', 'danger');
            console.error(error);
        }
    }

    /**
     * Mostrar notificación (toast)
     */
    showNotification(message, type = 'info', duration = 5000) {
        this.createToast(message, type, duration);
    }

    /**
     * Crear toast
     */
    createToast(message, type, duration) {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
        const toast = this.buildToastElement(message, type);

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    /**
     * Crear contenedor de toasts
     */
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    /**
     * Construir elemento de toast
     */
    buildToastElement(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        return toast;
    }

    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Realizar petición AJAX
     */
    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.config.csrfToken
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en petición AJAX:', error);
            throw error;
        }
    }

    /**
     * Debounce para optimizar eventos
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle para optimizar eventos
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Inicializar el tema cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ISERTheme = new ISERTheme();
    });
} else {
    window.ISERTheme = new ISERTheme();
}

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ISERTheme;
}
