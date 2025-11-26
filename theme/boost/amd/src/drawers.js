/**
 * Drawers module for Boost theme.
 *
 * Handles the navigation drawer functionality.
 * Follows Moodle Boost theme drawer patterns.
 *
 * @module     theme_boost/drawers
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

define(['jquery'], function($) {

    /** @var {string} DRAWER_LEFT Left drawer selector */
    var DRAWER_LEFT = '#nav-drawer';

    /** @var {string} DRAWER_RIGHT Right drawer selector */
    var DRAWER_RIGHT = '#block-drawer';

    /** @var {string} DRAWER_TOGGLE Toggle button selector */
    var DRAWER_TOGGLE = '#drawer-toggle';

    /** @var {string} PAGE Page container selector */
    var PAGE = '#page';

    /** @var {string} USER_PREF_NAV User preference key for nav drawer */
    var USER_PREF_NAV = 'drawer-open-nav';

    /** @var {string} USER_PREF_BLOCK User preference key for block drawer */
    var USER_PREF_BLOCK = 'drawer-open-block';

    /**
     * Initialize drawer functionality.
     *
     * @return {void}
     */
    var init = function() {
        initLeftDrawer();
        initRightDrawer();
        initSwipeGestures();
        initKeyboardNavigation();
    };

    /**
     * Initialize the left navigation drawer.
     *
     * @return {void}
     */
    var initLeftDrawer = function() {
        var toggle = document.querySelector(DRAWER_TOGGLE);
        var drawer = document.querySelector(DRAWER_LEFT);
        var page = document.querySelector(PAGE);

        if (!toggle || !drawer) {
            return;
        }

        toggle.addEventListener('click', function() {
            var isOpen = drawer.classList.toggle('show');
            if (page) {
                page.classList.toggle('show-drawer-left', isOpen);
            }
            toggle.setAttribute('aria-expanded', isOpen);

            // Save preference
            saveUserPreference(USER_PREF_NAV, isOpen);
        });

        // Close drawer when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!drawer.contains(e.target) && !toggle.contains(e.target)) {
                    closeDrawer(drawer, page, toggle);
                }
            }
        });
    };

    /**
     * Initialize the right block drawer.
     *
     * @return {void}
     */
    var initRightDrawer = function() {
        var drawer = document.querySelector(DRAWER_RIGHT);

        if (!drawer) {
            return;
        }

        // Find toggle button for right drawer
        var toggle = document.querySelector('[data-toggle="block-drawer"]');

        if (toggle) {
            toggle.addEventListener('click', function() {
                var isOpen = drawer.classList.toggle('show');
                toggle.setAttribute('aria-expanded', isOpen);
                saveUserPreference(USER_PREF_BLOCK, isOpen);
            });
        }
    };

    /**
     * Close a drawer.
     *
     * @param {HTMLElement} drawer Drawer element
     * @param {HTMLElement} page Page element
     * @param {HTMLElement} toggle Toggle button
     * @return {void}
     */
    var closeDrawer = function(drawer, page, toggle) {
        drawer.classList.remove('show');
        if (page) {
            page.classList.remove('show-drawer-left');
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    };

    /**
     * Initialize swipe gestures for mobile.
     *
     * @return {void}
     */
    var initSwipeGestures = function() {
        var touchStartX = 0;
        var touchEndX = 0;
        var minSwipeDistance = 50;

        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        var handleSwipe = function() {
            var drawer = document.querySelector(DRAWER_LEFT);
            var page = document.querySelector(PAGE);
            var toggle = document.querySelector(DRAWER_TOGGLE);

            if (!drawer) {
                return;
            }

            var swipeDistance = touchEndX - touchStartX;

            // Swipe right - open drawer
            if (swipeDistance > minSwipeDistance && touchStartX < 30) {
                drawer.classList.add('show');
                if (page) {
                    page.classList.add('show-drawer-left');
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }

            // Swipe left - close drawer
            if (swipeDistance < -minSwipeDistance && drawer.classList.contains('show')) {
                closeDrawer(drawer, page, toggle);
            }
        };
    };

    /**
     * Initialize keyboard navigation for drawers.
     *
     * @return {void}
     */
    var initKeyboardNavigation = function() {
        document.addEventListener('keydown', function(e) {
            // ESC to close drawers
            if (e.key === 'Escape') {
                var leftDrawer = document.querySelector(DRAWER_LEFT + '.show');
                var rightDrawer = document.querySelector(DRAWER_RIGHT + '.show');

                if (leftDrawer) {
                    closeDrawer(
                        leftDrawer,
                        document.querySelector(PAGE),
                        document.querySelector(DRAWER_TOGGLE)
                    );
                }

                if (rightDrawer) {
                    rightDrawer.classList.remove('show');
                }
            }
        });

        // Focus trap within open drawer
        var drawer = document.querySelector(DRAWER_LEFT);
        if (drawer) {
            drawer.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' && drawer.classList.contains('show')) {
                    trapFocus(e, drawer);
                }
            });
        }
    };

    /**
     * Trap focus within an element.
     *
     * @param {Event} e Keyboard event
     * @param {HTMLElement} container Container element
     * @return {void}
     */
    var trapFocus = function(e, container) {
        var focusableElements = container.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), ' +
            'input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        var firstFocusable = focusableElements[0];
        var lastFocusable = focusableElements[focusableElements.length - 1];

        if (e.shiftKey && document.activeElement === firstFocusable) {
            lastFocusable.focus();
            e.preventDefault();
        } else if (!e.shiftKey && document.activeElement === lastFocusable) {
            firstFocusable.focus();
            e.preventDefault();
        }
    };

    /**
     * Save user preference via AJAX.
     *
     * @param {string} name Preference name
     * @param {*} value Preference value
     * @return {void}
     */
    var saveUserPreference = function(name, value) {
        // Try to save preference via AJAX
        if (window.M && M.cfg && M.cfg.wwwroot) {
            $.ajax({
                url: M.cfg.wwwroot + '/lib/ajax/setuserpref.php',
                method: 'POST',
                data: {
                    sesskey: M.cfg.sesskey,
                    pref: name,
                    value: value ? 1 : 0
                }
            });
        }
    };

    /**
     * Toggle drawer visibility.
     *
     * @param {string} drawerId Drawer ID
     * @return {void}
     */
    var toggle = function(drawerId) {
        var drawer = document.querySelector(drawerId);
        if (drawer) {
            drawer.classList.toggle('show');
        }
    };

    /**
     * Open a drawer.
     *
     * @param {string} drawerId Drawer ID
     * @return {void}
     */
    var open = function(drawerId) {
        var drawer = document.querySelector(drawerId);
        if (drawer) {
            drawer.classList.add('show');
        }
    };

    /**
     * Close a specific drawer.
     *
     * @param {string} drawerId Drawer ID
     * @return {void}
     */
    var close = function(drawerId) {
        var drawer = document.querySelector(drawerId);
        if (drawer) {
            drawer.classList.remove('show');
        }
    };

    return {
        init: init,
        toggle: toggle,
        open: open,
        close: close
    };
});
