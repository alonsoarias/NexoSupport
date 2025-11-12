/**
 * Hello World Plugin JavaScript
 * Example JavaScript for the Hello World plugin
 */

(function() {
    'use strict';

    /**
     * HelloWorld Plugin Class
     */
    class HelloWorldPlugin {
        constructor() {
            this.initialized = false;
            this.init();
        }

        /**
         * Initialize plugin
         */
        init() {
            if (this.initialized) {
                return;
            }

            console.log('Hello World Plugin initialized');

            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
            } else {
                this.setupEventListeners();
            }

            this.initialized = true;
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Add click handler to hello world buttons
            const buttons = document.querySelectorAll('.hello-world-btn');
            buttons.forEach(button => {
                button.addEventListener('click', (e) => this.handleButtonClick(e));
            });

            // Add hover effects
            const containers = document.querySelectorAll('.hello-world-container');
            containers.forEach(container => {
                container.addEventListener('mouseenter', () => this.handleHover(container, true));
                container.addEventListener('mouseleave', () => this.handleHover(container, false));
            });
        }

        /**
         * Handle button click
         */
        handleButtonClick(event) {
            event.preventDefault();
            const button = event.target;

            // Show notification
            this.showNotification('Hello from the plugin!', 'success');

            // Animate button
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = '';
            }, 100);
        }

        /**
         * Handle hover effect
         */
        handleHover(element, isHovering) {
            if (isHovering) {
                element.style.transform = 'translateY(-2px)';
            } else {
                element.style.transform = '';
            }
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            // Check if notification system exists
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            } else {
                // Fallback to console
                console.log(`[${type.toUpperCase()}] ${message}`);

                // Or use browser alert for demo
                if (type === 'error') {
                    alert(message);
                }
            }
        }

        /**
         * Get plugin configuration
         */
        getConfig(key, defaultValue = null) {
            if (window.helloWorldConfig && window.helloWorldConfig[key]) {
                return window.helloWorldConfig[key];
            }
            return defaultValue;
        }

        /**
         * Log message
         */
        log(message, data = null) {
            if (this.getConfig('debug', false)) {
                console.log('[HelloWorld]', message, data || '');
            }
        }
    }

    // Initialize plugin when script loads
    window.HelloWorldPlugin = new HelloWorldPlugin();

    // Expose plugin methods for external use
    window.helloWorld = {
        /**
         * Say hello
         */
        sayHello: function(name = 'World') {
            const message = `Hello, ${name}!`;
            window.HelloWorldPlugin.showNotification(message, 'success');
            return message;
        },

        /**
         * Get plugin version
         */
        getVersion: function() {
            return '1.0.0';
        },

        /**
         * Check if plugin is ready
         */
        isReady: function() {
            return window.HelloWorldPlugin && window.HelloWorldPlugin.initialized;
        }
    };

})();
