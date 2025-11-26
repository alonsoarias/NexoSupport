/**
 * Mobile Drawer JavaScript
 *
 * Handles the mobile navigation drawer:
 * - Open/close animations
 * - Swipe to close gesture
 * - Body scroll lock
 * - Focus trap
 * - Accessibility
 *
 * @package core
 */

(function() {
    'use strict';

    /**
     * Mobile Drawer Controller
     */
    class MobileDrawer {
        constructor() {
            this.drawer = document.getElementById('nexoMobileDrawer');
            this.overlay = document.getElementById('nexoMobileOverlay');
            this.hamburger = document.getElementById('nexoHamburger');
            this.closeBtn = document.getElementById('nexoDrawerClose');

            if (!this.drawer) return;

            this.isOpen = false;
            this.startX = 0;
            this.currentX = 0;
            this.focusableElements = null;
            this.firstFocusable = null;
            this.lastFocusable = null;
            this.previousActiveElement = null;

            this.init();
        }

        /**
         * Initialize drawer functionality
         */
        init() {
            this.initTriggers();
            this.initSwipeGesture();
            this.initKeyboardNavigation();
            this.initFocusTrap();
        }

        /**
         * Initialize open/close triggers
         */
        initTriggers() {
            // Hamburger button
            if (this.hamburger) {
                this.hamburger.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            }

            // Close button
            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.close();
                });
            }

            // Overlay click
            if (this.overlay) {
                this.overlay.addEventListener('click', () => {
                    this.close();
                });
            }
        }

        /**
         * Initialize swipe gesture for closing
         */
        initSwipeGesture() {
            let isDragging = false;

            this.drawer.addEventListener('touchstart', (e) => {
                if (e.touches.length === 1) {
                    this.startX = e.touches[0].clientX;
                    isDragging = true;
                }
            }, { passive: true });

            this.drawer.addEventListener('touchmove', (e) => {
                if (!isDragging) return;

                this.currentX = e.touches[0].clientX;
                const diff = this.startX - this.currentX;

                // Only allow swipe to the left (to close)
                if (diff > 0) {
                    const translateX = Math.min(diff, this.drawer.offsetWidth);
                    this.drawer.style.transform = `translateX(-${translateX}px)`;
                    this.drawer.style.transition = 'none';
                }
            }, { passive: true });

            this.drawer.addEventListener('touchend', () => {
                if (!isDragging) return;
                isDragging = false;

                const diff = this.startX - this.currentX;
                this.drawer.style.transform = '';
                this.drawer.style.transition = '';

                // Close if swiped more than 100px
                if (diff > 100) {
                    this.close();
                }
            }, { passive: true });
        }

        /**
         * Initialize keyboard navigation
         */
        initKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                if (!this.isOpen) return;

                // Escape closes drawer
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.close();
                }

                // Tab trap
                if (e.key === 'Tab') {
                    this.handleTabKey(e);
                }
            });
        }

        /**
         * Initialize focus trap
         */
        initFocusTrap() {
            this.updateFocusableElements();
        }

        /**
         * Update list of focusable elements
         */
        updateFocusableElements() {
            const focusableSelectors = [
                'button:not([disabled])',
                'a[href]',
                'input:not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"])'
            ].join(', ');

            this.focusableElements = this.drawer.querySelectorAll(focusableSelectors);

            if (this.focusableElements.length > 0) {
                this.firstFocusable = this.focusableElements[0];
                this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];
            }
        }

        /**
         * Handle Tab key for focus trap
         *
         * @param {KeyboardEvent} e
         */
        handleTabKey(e) {
            if (!this.focusableElements || this.focusableElements.length === 0) return;

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === this.firstFocusable) {
                    e.preventDefault();
                    this.lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === this.lastFocusable) {
                    e.preventDefault();
                    this.firstFocusable.focus();
                }
            }
        }

        /**
         * Toggle drawer open/close
         */
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        /**
         * Open drawer
         */
        open() {
            if (this.isOpen) return;

            // Save current focus
            this.previousActiveElement = document.activeElement;

            // Open drawer
            this.isOpen = true;
            this.drawer.classList.add('open');

            if (this.overlay) {
                this.overlay.classList.add('show');
            }

            if (this.hamburger) {
                this.hamburger.classList.add('active');
                this.hamburger.setAttribute('aria-expanded', 'true');
            }

            // Lock body scroll
            document.body.classList.add('drawer-open');

            // Update ARIA
            this.drawer.setAttribute('aria-hidden', 'false');

            // Focus first element
            this.updateFocusableElements();
            if (this.closeBtn) {
                setTimeout(() => this.closeBtn.focus(), 100);
            }

            // Dispatch event
            this.drawer.dispatchEvent(new CustomEvent('drawer:open'));
        }

        /**
         * Close drawer
         */
        close() {
            if (!this.isOpen) return;

            this.isOpen = false;
            this.drawer.classList.remove('open');

            if (this.overlay) {
                this.overlay.classList.remove('show');
            }

            if (this.hamburger) {
                this.hamburger.classList.remove('active');
                this.hamburger.setAttribute('aria-expanded', 'false');
            }

            // Unlock body scroll
            document.body.classList.remove('drawer-open');

            // Update ARIA
            this.drawer.setAttribute('aria-hidden', 'true');

            // Restore focus
            if (this.previousActiveElement) {
                this.previousActiveElement.focus();
            }

            // Dispatch event
            this.drawer.dispatchEvent(new CustomEvent('drawer:close'));
        }

        /**
         * Check if drawer is open
         *
         * @return {boolean}
         */
        isDrawerOpen() {
            return this.isOpen;
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new MobileDrawer());
    } else {
        new MobileDrawer();
    }

    // Export for external use
    window.NexoMobileDrawer = MobileDrawer;

})();
