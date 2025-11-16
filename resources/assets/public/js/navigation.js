/**
 * Navigation JavaScript - NexoSupport
 * Handles sidebar toggle, user menu, and navigation interactions
 */

(function() {
    'use strict';

    // ========================================
    // SIDEBAR TOGGLE
    // ========================================

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;

    if (sidebarToggle && sidebar) {
        // Toggle sidebar on mobile
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                body.classList.remove('sidebar-open');
            });
        }

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
                body.classList.remove('sidebar-open');
            }
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Close mobile sidebar on desktop resize
                if (window.innerWidth > 768) {
                    body.classList.remove('sidebar-open');
                }
            }, 250);
        });
    }

    // ========================================
    // USER MENU DROPDOWN
    // ========================================

    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuToggle && userMenuDropdown) {
        const userMenu = userMenuToggle.closest('.user-menu');

        userMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userMenu.classList.contains('active')) {
                userMenu.classList.remove('active');
            }
        });

        // Prevent dropdown from closing when clicking inside
        userMenuDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // ========================================
    // ACTIVE NAVIGATION HIGHLIGHTING
    // ========================================

    function setActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');

        navLinks.forEach(function(link) {
            const linkPath = link.getAttribute('href');

            // Exact match
            if (linkPath === currentPath) {
                link.classList.add('active');
            }
            // Partial match for nested routes
            else if (currentPath.startsWith(linkPath) && linkPath !== '/') {
                link.classList.add('active');
            }
            else {
                link.classList.remove('active');
            }
        });
    }

    // Set active navigation on page load
    setActiveNavigation();

    // ========================================
    // SMOOTH SCROLLING FOR NAVIGATION
    // ========================================

    const navLinksAll = document.querySelectorAll('.nav-link');
    navLinksAll.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Close mobile sidebar when clicking a link
            if (window.innerWidth <= 768) {
                body.classList.remove('sidebar-open');
            }
        });
    });

    // ========================================
    // NOTIFICATIONS (Placeholder for future)
    // ========================================

    const notificationBtn = document.querySelector('.notifications-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // TODO: Implement notifications dropdown
            console.log('Notifications clicked');
        });
    }

    // ========================================
    // SEARCH (Placeholder for future)
    // ========================================

    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query) {
                    // TODO: Implement search functionality
                    console.log('Search query:', query);
                }
            }
        });

        // Clear search on escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.blur();
            }
        });
    }

    // ========================================
    // ACCESSIBILITY ENHANCEMENTS
    // ========================================

    // Trap focus in mobile sidebar when open
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        });
    }

    if (sidebar && window.innerWidth <= 768) {
        trapFocus(sidebar);
    }

    // ========================================
    // DARK MODE TOGGLE
    // ========================================

    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');

    // Función para aplicar dark mode
    function applyDarkMode(isDark) {
        if (isDark) {
            body.classList.add('dark-mode');
            if (darkModeIcon) {
                darkModeIcon.classList.remove('bi-toggle-off');
                darkModeIcon.classList.add('bi-toggle-on');
            }
        } else {
            body.classList.remove('dark-mode');
            if (darkModeIcon) {
                darkModeIcon.classList.remove('bi-toggle-on');
                darkModeIcon.classList.add('bi-toggle-off');
            }
        }
    }

    // Cargar preferencia de dark mode desde localStorage
    const savedDarkMode = localStorage.getItem('darkMode') === 'true';
    applyDarkMode(savedDarkMode);

    // Toggle dark mode al hacer click
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isDark = body.classList.contains('dark-mode');
            const newMode = !isDark;

            applyDarkMode(newMode);
            localStorage.setItem('darkMode', newMode);
        });
    }

    // Detectar preferencia del sistema
    if (window.matchMedia && !localStorage.getItem('darkMode')) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyDarkMode(prefersDark);
    }

    // Escuchar cambios en la preferencia del sistema
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (!localStorage.getItem('darkMode')) {
                applyDarkMode(e.matches);
            }
        });
    }

    // ========================================
    // CONSOLE INFO
    // ========================================

    console.log('✅ NexoSupport Navigation initialized');
    console.log('📱 Window width:', window.innerWidth);
    console.log('🎨 Theme: ISER');
    console.log('🌙 Dark Mode:', body.classList.contains('dark-mode'));

})();
