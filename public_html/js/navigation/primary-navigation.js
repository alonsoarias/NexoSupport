/**
 * Primary Navigation JavaScript
 *
 * Handles interactions for the primary navigation header:
 * - Mobile drawer toggle
 * - User dropdown menu
 * - Keyboard navigation
 * - Close on outside click
 *
 * @package core
 */

(function() {
    'use strict';

    /**
     * Primary Navigation Controller
     */
    class PrimaryNavigation {
        constructor() {
            this.hamburger = document.getElementById('nexoHamburger');
            this.primaryNav = document.getElementById('nexoPrimaryNav');
            this.userDropdown = document.getElementById('nexoUserDropdown');
            this.userDropdownMenu = document.getElementById('nexoUserDropdownMenu');
            this.mobileDrawer = document.getElementById('nexoMobileDrawer');
            this.mobileOverlay = document.getElementById('nexoMobileOverlay');

            this.init();
        }

        /**
         * Initialize all event listeners
         */
        init() {
            this.initHamburger();
            this.initUserDropdown();
            this.initKeyboardNavigation();
            this.initOutsideClick();
        }

        /**
         * Initialize hamburger menu for mobile
         */
        initHamburger() {
            if (!this.hamburger) return;

            this.hamburger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleMobileDrawer();
            });
        }

        /**
         * Toggle mobile drawer
         */
        toggleMobileDrawer() {
            const isOpen = this.hamburger.classList.toggle('active');
            this.hamburger.setAttribute('aria-expanded', isOpen);

            if (this.mobileDrawer) {
                this.mobileDrawer.classList.toggle('open', isOpen);
            }

            if (this.mobileOverlay) {
                this.mobileOverlay.classList.toggle('show', isOpen);
            }

            // Lock body scroll when drawer is open
            document.body.classList.toggle('drawer-open', isOpen);
        }

        /**
         * Close mobile drawer
         */
        closeMobileDrawer() {
            if (this.hamburger) {
                this.hamburger.classList.remove('active');
                this.hamburger.setAttribute('aria-expanded', 'false');
            }

            if (this.mobileDrawer) {
                this.mobileDrawer.classList.remove('open');
            }

            if (this.mobileOverlay) {
                this.mobileOverlay.classList.remove('show');
            }

            document.body.classList.remove('drawer-open');
        }

        /**
         * Initialize user dropdown
         */
        initUserDropdown() {
            if (!this.userDropdown) return;

            const trigger = this.userDropdown.querySelector('.nexo-user-trigger');
            if (!trigger) return;

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleUserDropdown();
            });
        }

        /**
         * Toggle user dropdown menu
         */
        toggleUserDropdown() {
            const trigger = this.userDropdown.querySelector('.nexo-user-trigger');
            const isOpen = this.userDropdown.classList.toggle('open');

            trigger.setAttribute('aria-expanded', isOpen);

            if (this.userDropdownMenu) {
                this.userDropdownMenu.classList.toggle('show', isOpen);
            }

            // Focus first item when opening
            if (isOpen && this.userDropdownMenu) {
                const firstItem = this.userDropdownMenu.querySelector('.nexo-dropdown-item');
                if (firstItem) {
                    setTimeout(() => firstItem.focus(), 100);
                }
            }
        }

        /**
         * Close user dropdown
         */
        closeUserDropdown() {
            if (!this.userDropdown) return;

            const trigger = this.userDropdown.querySelector('.nexo-user-trigger');
            this.userDropdown.classList.remove('open');

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }

            if (this.userDropdownMenu) {
                this.userDropdownMenu.classList.remove('show');
            }
        }

        /**
         * Initialize keyboard navigation
         */
        initKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                // Escape key closes dropdowns and drawer
                if (e.key === 'Escape') {
                    this.closeUserDropdown();
                    this.closeMobileDrawer();
                }

                // Tab key navigation within dropdown
                if (e.key === 'Tab' && this.userDropdown && this.userDropdown.classList.contains('open')) {
                    this.handleTabNavigation(e);
                }

                // Arrow key navigation in dropdown
                if (this.userDropdownMenu && this.userDropdownMenu.classList.contains('show')) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.handleArrowNavigation(e.key === 'ArrowDown' ? 1 : -1);
                    }
                }
            });

            // Enter key on dropdown items
            if (this.userDropdownMenu) {
                this.userDropdownMenu.querySelectorAll('.nexo-dropdown-item').forEach(item => {
                    item.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            item.click();
                        }
                    });
                });
            }
        }

        /**
         * Handle tab navigation within dropdown
         *
         * @param {KeyboardEvent} e
         */
        handleTabNavigation(e) {
            const items = this.userDropdownMenu.querySelectorAll('.nexo-dropdown-item');
            const lastItem = items[items.length - 1];
            const firstItem = items[0];

            if (e.shiftKey && document.activeElement === firstItem) {
                // Shift+Tab on first item - close dropdown
                this.closeUserDropdown();
            } else if (!e.shiftKey && document.activeElement === lastItem) {
                // Tab on last item - close dropdown
                this.closeUserDropdown();
            }
        }

        /**
         * Handle arrow key navigation in dropdown
         *
         * @param {number} direction 1 for down, -1 for up
         */
        handleArrowNavigation(direction) {
            const items = Array.from(this.userDropdownMenu.querySelectorAll('.nexo-dropdown-item'));
            const currentIndex = items.indexOf(document.activeElement);
            let nextIndex;

            if (currentIndex === -1) {
                nextIndex = direction === 1 ? 0 : items.length - 1;
            } else {
                nextIndex = currentIndex + direction;
                if (nextIndex < 0) nextIndex = items.length - 1;
                if (nextIndex >= items.length) nextIndex = 0;
            }

            items[nextIndex].focus();
        }

        /**
         * Initialize outside click handler
         */
        initOutsideClick() {
            document.addEventListener('click', (e) => {
                // Close user dropdown if clicking outside
                if (this.userDropdown && !this.userDropdown.contains(e.target)) {
                    this.closeUserDropdown();
                }

                // Close mobile drawer if clicking overlay
                if (this.mobileOverlay && e.target === this.mobileOverlay) {
                    this.closeMobileDrawer();
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new PrimaryNavigation());
    } else {
        new PrimaryNavigation();
    }

    // Export for external use
    window.NexoPrimaryNavigation = PrimaryNavigation;

})();
