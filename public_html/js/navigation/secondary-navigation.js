/**
 * Secondary Navigation JavaScript
 *
 * Handles interactions for the secondary navigation (context tabs):
 * - Overflow detection and "More" menu
 * - Responsive resize handling
 * - Active tab indicator
 * - Keyboard navigation
 *
 * @package core
 */

(function() {
    'use strict';

    /**
     * Secondary Navigation Controller
     */
    class SecondaryNavigation {
        constructor() {
            this.nav = document.querySelector('.nexo-nav-secondary');
            this.tabsList = document.querySelector('.nexo-nav-secondary-tabs');
            this.moreBtn = document.getElementById('nexoSecondaryMoreBtn');
            this.moreMenu = document.getElementById('nexoSecondaryMoreMenu');

            if (!this.nav) return;

            this.tabs = [];
            this.visibleCount = 5;
            this.resizeTimeout = null;

            this.init();
        }

        /**
         * Initialize secondary navigation
         */
        init() {
            this.collectTabs();
            this.initMoreMenu();
            this.initResizeHandler();
            this.initKeyboardNavigation();
            this.checkOverflow();
        }

        /**
         * Collect all tabs
         */
        collectTabs() {
            if (!this.tabsList) return;
            this.tabs = Array.from(this.tabsList.querySelectorAll('.nexo-nav-secondary-tab'));
        }

        /**
         * Initialize "More" menu dropdown
         */
        initMoreMenu() {
            if (!this.moreBtn || !this.moreMenu) return;

            // Click handler
            this.moreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleMoreMenu();
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!this.moreBtn.contains(e.target) && !this.moreMenu.contains(e.target)) {
                    this.closeMoreMenu();
                }
            });
        }

        /**
         * Toggle "More" menu visibility
         */
        toggleMoreMenu() {
            const isOpen = this.moreMenu.classList.toggle('show');
            this.moreBtn.setAttribute('aria-expanded', isOpen);

            if (isOpen) {
                const firstItem = this.moreMenu.querySelector('a');
                if (firstItem) {
                    setTimeout(() => firstItem.focus(), 100);
                }
            }
        }

        /**
         * Close "More" menu
         */
        closeMoreMenu() {
            if (this.moreMenu) {
                this.moreMenu.classList.remove('show');
            }
            if (this.moreBtn) {
                this.moreBtn.setAttribute('aria-expanded', 'false');
            }
        }

        /**
         * Initialize resize handler
         */
        initResizeHandler() {
            window.addEventListener('resize', () => {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    this.checkOverflow();
                }, 150);
            });
        }

        /**
         * Check for overflow and show/hide tabs accordingly
         */
        checkOverflow() {
            if (!this.nav || !this.tabsList) return;

            const navWidth = this.nav.offsetWidth;
            const moreWidth = this.moreBtn ? this.moreBtn.offsetWidth + 20 : 100;
            let usedWidth = 0;
            let visibleCount = 0;

            // First pass: calculate how many tabs can fit
            this.tabs.forEach((tab, index) => {
                tab.style.display = '';
                const tabWidth = tab.offsetWidth;

                if (usedWidth + tabWidth + moreWidth < navWidth || index === this.tabs.length - 1) {
                    usedWidth += tabWidth;
                    visibleCount++;
                }
            });

            // If all tabs fit, hide more menu
            if (visibleCount >= this.tabs.length) {
                this.hideMoreMenu();
                this.tabs.forEach(tab => tab.style.display = '');
                return;
            }

            // Show/hide tabs based on calculation
            this.tabs.forEach((tab, index) => {
                if (index < visibleCount - 1) {
                    tab.style.display = '';
                } else {
                    tab.style.display = 'none';
                }
            });

            // Show more menu and populate it
            this.showMoreMenu();
            this.populateMoreMenu(visibleCount - 1);
        }

        /**
         * Hide "More" menu button
         */
        hideMoreMenu() {
            const moreContainer = document.querySelector('.nexo-nav-secondary-more');
            if (moreContainer) {
                moreContainer.style.display = 'none';
            }
        }

        /**
         * Show "More" menu button
         */
        showMoreMenu() {
            const moreContainer = document.querySelector('.nexo-nav-secondary-more');
            if (moreContainer) {
                moreContainer.style.display = '';
            }
        }

        /**
         * Populate "More" menu with overflow tabs
         *
         * @param {number} startIndex Index to start from
         */
        populateMoreMenu(startIndex) {
            if (!this.moreMenu) return;

            // Clear existing items
            this.moreMenu.innerHTML = '';

            // Add overflow tabs
            for (let i = startIndex; i < this.tabs.length; i++) {
                const tab = this.tabs[i];
                const link = tab.querySelector('a');
                if (!link) continue;

                const menuItem = document.createElement('a');
                menuItem.href = link.href;
                menuItem.innerHTML = link.innerHTML;
                menuItem.setAttribute('role', 'menuitem');

                if (tab.classList.contains('active')) {
                    menuItem.classList.add('active');
                }

                this.moreMenu.appendChild(menuItem);
            }
        }

        /**
         * Initialize keyboard navigation
         */
        initKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                // Escape closes more menu
                if (e.key === 'Escape') {
                    this.closeMoreMenu();
                }

                // Arrow navigation in more menu
                if (this.moreMenu && this.moreMenu.classList.contains('show')) {
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.navigateMoreMenu(e.key === 'ArrowDown' ? 1 : -1);
                    }
                }
            });

            // Tab navigation
            if (this.tabsList) {
                this.tabsList.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                        e.preventDefault();
                        this.navigateTabs(e.key === 'ArrowRight' ? 1 : -1);
                    }
                });
            }
        }

        /**
         * Navigate within "More" menu
         *
         * @param {number} direction 1 for next, -1 for previous
         */
        navigateMoreMenu(direction) {
            const items = Array.from(this.moreMenu.querySelectorAll('a'));
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
         * Navigate between tabs
         *
         * @param {number} direction 1 for next, -1 for previous
         */
        navigateTabs(direction) {
            const visibleTabs = this.tabs.filter(tab => tab.style.display !== 'none');
            const links = visibleTabs.map(tab => tab.querySelector('a')).filter(Boolean);
            const currentIndex = links.indexOf(document.activeElement);
            let nextIndex;

            if (currentIndex === -1) {
                nextIndex = direction === 1 ? 0 : links.length - 1;
            } else {
                nextIndex = currentIndex + direction;
                if (nextIndex < 0) nextIndex = links.length - 1;
                if (nextIndex >= links.length) nextIndex = 0;
            }

            links[nextIndex].focus();
        }

        /**
         * Set active tab programmatically
         *
         * @param {string} key Tab key
         */
        setActive(key) {
            this.tabs.forEach(tab => {
                const isActive = tab.dataset.key === key;
                tab.classList.toggle('active', isActive);

                const link = tab.querySelector('a');
                if (link) {
                    if (isActive) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new SecondaryNavigation());
    } else {
        new SecondaryNavigation();
    }

    // Export for external use
    window.NexoSecondaryNavigation = SecondaryNavigation;

})();
