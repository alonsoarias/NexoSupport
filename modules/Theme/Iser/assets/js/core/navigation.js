/**
 * Componente de navegación del tema ISER
 * @version 1.0.0
 */

class ISERNavigation {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileMenu();
        this.setupDropdowns();
        this.setupStickyNav();
        this.setupActiveLinks();
    }

    /**
     * Configurar menú móvil
     */
    setupMobileMenu() {
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');

        if (navbarToggler && navbarCollapse) {
            // Cerrar menú al hacer clic en un enlace
            const navLinks = navbarCollapse.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                        if (bsCollapse) {
                            bsCollapse.hide();
                        }
                    }
                });
            });
        }
    }

    /**
     * Configurar dropdowns de navegación
     */
    setupDropdowns() {
        const dropdowns = document.querySelectorAll('.navbar .dropdown');

        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
            const menu = dropdown.querySelector('.dropdown-menu');

            if (!toggle || !menu) return;

            // Hover en desktop
            if (window.innerWidth >= 992) {
                dropdown.addEventListener('mouseenter', () => {
                    const bsDropdown = new bootstrap.Dropdown(toggle);
                    bsDropdown.show();
                });

                dropdown.addEventListener('mouseleave', () => {
                    const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                });
            }
        });
    }

    /**
     * Configurar navegación sticky
     */
    setupStickyNav() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        let lastScroll = 0;
        const scrollThreshold = 100;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > scrollThreshold) {
                navbar.classList.add('navbar-scrolled');

                // Auto-hide en scroll down
                if (currentScroll > lastScroll && currentScroll > 300) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
            } else {
                navbar.classList.remove('navbar-scrolled');
                navbar.style.transform = 'translateY(0)';
            }

            lastScroll = currentScroll;
        });
    }

    /**
     * Marcar enlaces activos basados en URL
     */
    setupActiveLinks() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href === currentPath) {
                link.classList.add('active');

                // Si es un item de dropdown, activar también el padre
                const dropdown = link.closest('.dropdown');
                if (dropdown) {
                    const dropdownToggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                    if (dropdownToggle) {
                        dropdownToggle.classList.add('active');
                    }
                }
            }
        });
    }

    /**
     * Obtener breadcrumbs desde la URL
     */
    getBreadcrumbs() {
        const path = window.location.pathname;
        const segments = path.split('/').filter(s => s);

        const breadcrumbs = [{ text: 'Inicio', url: '/' }];

        let currentPath = '';
        segments.forEach((segment, index) => {
            currentPath += '/' + segment;
            const isLast = index === segments.length - 1;

            breadcrumbs.push({
                text: this.segmentToTitle(segment),
                url: isLast ? null : currentPath
            });
        });

        return breadcrumbs;
    }

    /**
     * Convertir segmento de URL a título
     */
    segmentToTitle(segment) {
        return segment
            .replace(/[-_]/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }

    /**
     * Renderizar breadcrumbs
     */
    renderBreadcrumbs(containerId = 'breadcrumb-container') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const breadcrumbs = this.getBreadcrumbs();
        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'breadcrumb');

        const ol = document.createElement('ol');
        ol.className = 'breadcrumb';

        breadcrumbs.forEach((crumb, index) => {
            const li = document.createElement('li');
            li.className = 'breadcrumb-item';

            if (index === breadcrumbs.length - 1) {
                li.classList.add('active');
                li.setAttribute('aria-current', 'page');
                li.textContent = crumb.text;
            } else {
                const a = document.createElement('a');
                a.href = crumb.url;
                a.textContent = crumb.text;
                li.appendChild(a);
            }

            ol.appendChild(li);
        });

        nav.appendChild(ol);
        container.innerHTML = '';
        container.appendChild(nav);
    }
}

// Auto-inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ISERNavigation = new ISERNavigation();
    });
} else {
    window.ISERNavigation = new ISERNavigation();
}
