/**
 * Boost theme loader module.
 *
 * This module initializes the Boost theme components.
 * Follows Moodle AMD module patterns.
 *
 * @module     theme_boost/loader
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

define(['jquery', 'theme_boost/drawers'], function($, Drawers) {

    /**
     * Initialize the theme.
     *
     * @param {Object} config Configuration options
     * @return {void}
     */
    var init = function(config) {
        config = config || {};

        // Initialize drawers
        Drawers.init();

        // Initialize dropdowns
        initDropdowns();

        // Initialize tooltips if available
        initTooltips();

        // Handle page visibility changes
        handleVisibilityChanges();

        // Log initialization
        if (config.debug) {
            console.log('Boost theme initialized');
        }
    };

    /**
     * Initialize dropdown menus.
     *
     * @return {void}
     */
    var initDropdowns = function() {
        // User menu dropdown
        var userMenuToggle = document.getElementById('user-menu-toggle');
        var userMenu = document.getElementById('user-menu');

        if (userMenuToggle && userMenu) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
                userMenuToggle.setAttribute('aria-expanded', userMenu.classList.contains('show'));
            });

            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && !userMenuToggle.contains(e.target)) {
                    userMenu.classList.remove('show');
                    userMenuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Generic dropdown support
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            if (toggle.id === 'user-menu-toggle') {
                return; // Already handled
            }

            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                var menu = toggle.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.classList.toggle('show');
                    toggle.setAttribute('aria-expanded', menu.classList.contains('show'));
                }
            });
        });
    };

    /**
     * Initialize tooltips using title attributes.
     *
     * @return {void}
     */
    var initTooltips = function() {
        // Simple tooltip implementation
        var tooltipElements = document.querySelectorAll('[data-toggle="tooltip"], [title]');

        tooltipElements.forEach(function(el) {
            var title = el.getAttribute('title') || el.getAttribute('data-original-title');
            if (!title) {
                return;
            }

            // Store and remove title to prevent default browser tooltip
            el.setAttribute('data-original-title', title);
            el.removeAttribute('title');

            el.addEventListener('mouseenter', function() {
                showTooltip(el, title);
            });

            el.addEventListener('mouseleave', function() {
                hideTooltip(el);
            });
        });
    };

    /**
     * Show tooltip for an element.
     *
     * @param {HTMLElement} el Element
     * @param {string} text Tooltip text
     * @return {void}
     */
    var showTooltip = function(el, text) {
        var tooltip = document.createElement('div');
        tooltip.className = 'boost-tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = 'position:fixed;background:#333;color:#fff;padding:5px 10px;' +
            'border-radius:4px;font-size:12px;z-index:10000;pointer-events:none;';

        document.body.appendChild(tooltip);

        var rect = el.getBoundingClientRect();
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';

        el._tooltip = tooltip;
    };

    /**
     * Hide tooltip for an element.
     *
     * @param {HTMLElement} el Element
     * @return {void}
     */
    var hideTooltip = function(el) {
        if (el._tooltip) {
            el._tooltip.remove();
            delete el._tooltip;
        }
    };

    /**
     * Handle page visibility changes.
     *
     * @return {void}
     */
    var handleVisibilityChanges = function() {
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, pause animations
                document.body.classList.add('paused');
            } else {
                // Page is visible, resume
                document.body.classList.remove('paused');
            }
        });
    };

    return {
        init: init
    };
});
