/**
 * NexoSupport - ISER Dark Mode Toggle
 *
 * Features:
 * - Manual toggle
 * - Auto-detect system preference
 * - Persistent storage (localStorage)
 * - Smooth transitions
 */

class DarkModeToggle {
    constructor() {
        this.storageKey = 'nexosupport-theme';
        this.dataAttribute = 'data-theme';
        this.init();
    }

    /**
     * Initialize dark mode
     */
    init() {
        // Load saved preference or detect system preference
        const savedTheme = localStorage.getItem(this.storageKey);
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        const theme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
        this.setTheme(theme, false);

        // Attach event listeners
        this.attachListeners();

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(this.storageKey)) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    /**
     * Set theme
     *
     * @param {string} theme - 'light' or 'dark'
     * @param {boolean} save - Save to localStorage
     */
    setTheme(theme, save = true) {
        document.documentElement.setAttribute(this.dataAttribute, theme);

        if (save) {
            localStorage.setItem(this.storageKey, theme);
        }

        // Update toggle button if exists
        this.updateToggleButton(theme);

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    /**
     * Toggle theme
     */
    toggle() {
        const current = document.documentElement.getAttribute(this.dataAttribute);
        const newTheme = current === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    /**
     * Get current theme
     *
     * @returns {string} Current theme
     */
    getCurrentTheme() {
        return document.documentElement.getAttribute(this.dataAttribute) || 'light';
    }

    /**
     * Attach event listeners
     */
    attachListeners() {
        // Find all toggle buttons
        const toggleButtons = document.querySelectorAll('[data-dark-mode-toggle]');

        toggleButtons.forEach(button => {
            button.addEventListener('click', () => this.toggle());
        });

        // Keyboard shortcut: Ctrl/Cmd + Shift + D
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    /**
     * Update toggle button state
     *
     * @param {string} theme - Current theme
     */
    updateToggleButton(theme) {
        const toggleButtons = document.querySelectorAll('[data-dark-mode-toggle]');

        toggleButtons.forEach(button => {
            const icon = button.querySelector('.icon');
            if (icon) {
                icon.textContent = theme === 'dark' ? '☀️' : '🌙';
            }

            button.setAttribute('aria-label',
                theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
            );
        });
    }

    /**
     * Reset to system preference
     */
    resetToSystem() {
        localStorage.removeItem(this.storageKey);
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.setTheme(systemPrefersDark ? 'dark' : 'light', false);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.darkMode = new DarkModeToggle();
    });
} else {
    window.darkMode = new DarkModeToggle();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DarkModeToggle;
}
