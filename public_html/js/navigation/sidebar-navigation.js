/**
 * Sidebar Navigation JavaScript
 *
 * Handles interactions for the sidebar navigation:
 * - Category collapse/expand
 * - State persistence via localStorage
 * - Auto-expand active category
 * - Smooth animations
 * - Sidebar width toggle
 *
 * @package core
 */

(function() {
    'use strict';

    /**
     * Sidebar Navigation Controller
     */
    class SidebarNavigation {
        constructor() {
            this.sidebar = document.getElementById('nexoSidebar');
            this.mobileDrawer = document.getElementById('nexoMobileDrawer');
            this.storageKey = 'nexo_nav_collapsed_categories';
            this.collapsedCategories = this.loadState();

            this.init();
        }

        /**
         * Initialize sidebar functionality
         */
        init() {
            this.initCategories(this.sidebar);
            this.initCategories(this.mobileDrawer);
            this.initSidebarToggle();
            this.autoExpandActiveCategory();
        }

        /**
         * Initialize category collapse/expand for a container
         *
         * @param {HTMLElement} container
         */
        initCategories(container) {
            if (!container) return;

            const categories = container.querySelectorAll('.nexo-sidebar-category');
            categories.forEach(category => {
                const header = category.querySelector('.nexo-sidebar-category-header');
                const key = category.dataset.key;

                if (!header) return;

                // Restore state from localStorage
                if (this.collapsedCategories.includes(key)) {
                    category.classList.remove('expanded');
                }

                // Click handler
                header.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleCategory(category);
                });

                // Keyboard handler
                header.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggleCategory(category);
                    }
                });
            });
        }

        /**
         * Toggle category expanded state
         *
         * @param {HTMLElement} category
         */
        toggleCategory(category) {
            const isExpanded = category.classList.toggle('expanded');
            const key = category.dataset.key;
            const header = category.querySelector('.nexo-sidebar-category-header');

            // Update ARIA
            if (header) {
                header.setAttribute('aria-expanded', isExpanded);
            }

            // Animate items
            const items = category.querySelector('.nexo-sidebar-category-items');
            if (items) {
                if (isExpanded) {
                    this.slideDown(items);
                } else {
                    this.slideUp(items);
                }
            }

            // Update state
            if (isExpanded) {
                this.collapsedCategories = this.collapsedCategories.filter(k => k !== key);
            } else {
                if (!this.collapsedCategories.includes(key)) {
                    this.collapsedCategories.push(key);
                }
            }

            this.saveState();

            // Sync state between sidebar and drawer
            this.syncCategoryState(key, isExpanded);
        }

        /**
         * Sync category state between sidebar and mobile drawer
         *
         * @param {string} key Category key
         * @param {boolean} isExpanded
         */
        syncCategoryState(key, isExpanded) {
            const containers = [this.sidebar, this.mobileDrawer];
            containers.forEach(container => {
                if (!container) return;

                const category = container.querySelector(`.nexo-sidebar-category[data-key="${key}"]`);
                if (category) {
                    if (isExpanded) {
                        category.classList.add('expanded');
                    } else {
                        category.classList.remove('expanded');
                    }

                    const header = category.querySelector('.nexo-sidebar-category-header');
                    if (header) {
                        header.setAttribute('aria-expanded', isExpanded);
                    }
                }
            });
        }

        /**
         * Slide down animation
         *
         * @param {HTMLElement} element
         */
        slideDown(element) {
            element.style.display = 'block';
            element.style.height = '0px';
            element.style.overflow = 'hidden';
            element.style.transition = 'height 0.3s ease';

            const height = element.scrollHeight;
            element.style.height = height + 'px';

            setTimeout(() => {
                element.style.height = '';
                element.style.overflow = '';
                element.style.transition = '';
            }, 300);
        }

        /**
         * Slide up animation
         *
         * @param {HTMLElement} element
         */
        slideUp(element) {
            element.style.height = element.scrollHeight + 'px';
            element.style.overflow = 'hidden';
            element.style.transition = 'height 0.3s ease';

            setTimeout(() => {
                element.style.height = '0px';
            }, 10);

            setTimeout(() => {
                element.style.display = 'none';
                element.style.height = '';
                element.style.overflow = '';
                element.style.transition = '';
            }, 300);
        }

        /**
         * Auto-expand category containing active item
         */
        autoExpandActiveCategory() {
            const containers = [this.sidebar, this.mobileDrawer];
            containers.forEach(container => {
                if (!container) return;

                const activeItem = container.querySelector('.nexo-sidebar-item.active');
                if (!activeItem) return;

                // Find parent category
                let parent = activeItem.parentElement;
                while (parent && !parent.classList.contains('nexo-sidebar-category')) {
                    parent = parent.parentElement;
                }

                if (parent && parent.classList.contains('nexo-sidebar-category')) {
                    parent.classList.add('expanded');
                    const key = parent.dataset.key;

                    // Remove from collapsed state
                    this.collapsedCategories = this.collapsedCategories.filter(k => k !== key);
                    this.saveState();

                    // Expand all parent categories
                    let grandParent = parent.parentElement;
                    while (grandParent) {
                        const gpCategory = grandParent.closest('.nexo-sidebar-category');
                        if (gpCategory) {
                            gpCategory.classList.add('expanded');
                            const gpKey = gpCategory.dataset.key;
                            this.collapsedCategories = this.collapsedCategories.filter(k => k !== gpKey);
                        }
                        grandParent = grandParent.parentElement;
                    }
                }
            });
        }

        /**
         * Initialize sidebar toggle (full width ↔ collapsed)
         */
        initSidebarToggle() {
            const toggleBtn = document.getElementById('nexoSidebarToggle');
            if (!toggleBtn || !this.sidebar) return;

            toggleBtn.addEventListener('click', () => {
                this.sidebar.classList.toggle('collapsed');

                const isCollapsed = this.sidebar.classList.contains('collapsed');
                localStorage.setItem('nexo_sidebar_collapsed', isCollapsed ? '1' : '0');
            });

            // Restore collapsed state
            if (localStorage.getItem('nexo_sidebar_collapsed') === '1') {
                this.sidebar.classList.add('collapsed');
            }
        }

        /**
         * Load collapsed categories from localStorage
         *
         * @return {Array}
         */
        loadState() {
            try {
                const stored = localStorage.getItem(this.storageKey);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        }

        /**
         * Save collapsed categories to localStorage
         */
        saveState() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.collapsedCategories));
            } catch (e) {
                // Storage not available
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new SidebarNavigation());
    } else {
        new SidebarNavigation();
    }

    // Export for external use
    window.NexoSidebarNavigation = SidebarNavigation;

})();
