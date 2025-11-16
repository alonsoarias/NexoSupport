/**
 * ISER Theme - JavaScript
 * Instituto Superior de Educación Rural
 */

(function() {
    'use strict';

    // Colores corporativos ISER
    const ISER_COLORS = {
        green: '#1B9E88',
        greenLight: '#2AC9B0',
        greenDark: '#157562',
        yellow: '#F4C430',
        yellowLight: '#FFD65C',
        yellowDark: '#D4A820',
        red: '#EB4335',
        redLight: '#FF5D4F',
        redDark: '#C42E21'
    };

    // Paleta para gráficos (alternando colores)
    const CHART_COLORS = [
        ISER_COLORS.green,
        ISER_COLORS.yellow,
        ISER_COLORS.red,
        ISER_COLORS.greenLight,
        ISER_COLORS.yellowDark,
        ISER_COLORS.redDark
    ];

    const CHART_BORDER_COLORS = [
        ISER_COLORS.greenDark,
        ISER_COLORS.yellowDark,
        ISER_COLORS.redDark,
        ISER_COLORS.green,
        '#B88A10',
        '#A02515'
    ];

    /**
     * Inicializar cuando el DOM esté listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        initProgressBars();
        initAnimations();
        initLogoFallback();
    });

    /**
     * Inicializar animaciones de barras de progreso
     */
    function initProgressBars() {
        const progressBars = document.querySelectorAll('.progress-fill');

        setTimeout(() => {
            progressBars.forEach(bar => {
                const width = bar.getAttribute('data-width');
                if (width) {
                    bar.style.width = width + '%';
                }
            });
        }, 300);
    }

    /**
     * Inicializar animaciones de entrada
     */
    function initAnimations() {
        const cards = document.querySelectorAll('.card, .stat-card');

        cards.forEach((card, index) => {
            card.classList.add('fade-in');
            if (index < 6) {
                card.classList.add(`fade-in-delay-${index + 1}`);
            }
        });
    }

    /**
     * Inicializar fallback del logo
     */
    function initLogoFallback() {
        const logoImg = document.querySelector('.iser-header-logo');
        const logoFallback = document.getElementById('logo-fallback');

        if (logoImg && logoFallback) {
            logoImg.addEventListener('error', function() {
                this.style.display = 'none';
                logoFallback.style.display = 'block';
            });
        }
    }

    /**
     * Crear gráfico de barras con colores ISER
     */
    window.createBarChart = function(canvasId, labels, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(2) + (options.suffix || '%');
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, family: 'Arial' },
                    bodyFont: { size: 12, family: 'Arial' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: options.maxValue || 100,
                    ticks: {
                        callback: function(value) {
                            return value + (options.suffix || '%');
                        },
                        font: { size: 11, family: 'Arial' },
                        color: '#646363'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: { size: 11, family: 'Arial' },
                        color: '#646363'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        };

        return new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: options.label || 'Datos',
                    data: data,
                    backgroundColor: CHART_COLORS,
                    borderColor: CHART_BORDER_COLORS,
                    borderWidth: 2
                }]
            },
            options: defaultOptions
        });
    };

    /**
     * Crear gráfico de líneas con colores ISER
     */
    window.createLineChart = function(canvasId, labels, datasets, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 12, family: 'Arial', weight: 'bold' },
                        color: '#646363',
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                   context.parsed.y.toFixed(2) +
                                   (options.suffix || ' pts');
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, family: 'Arial' },
                    bodyFont: { size: 12, family: 'Arial' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 11, family: 'Arial' },
                        color: '#646363'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: { size: 11, family: 'Arial' },
                        color: '#646363'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        };

        // Formatear datasets con colores ISER
        const formattedDatasets = datasets.map((dataset, index) => {
            const color = index === 0 ? ISER_COLORS.green : ISER_COLORS.red;
            return {
                ...dataset,
                borderColor: color,
                backgroundColor: color.replace(')', ', 0.1)').replace('rgb', 'rgba'),
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            };
        });

        return new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: formattedDatasets
            },
            options: defaultOptions
        });
    };

    /**
     * Formatear número con separador de miles
     */
    window.formatNumber = function(num, decimals = 2) {
        return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };

    /**
     * Calcular porcentaje
     */
    window.calculatePercentage = function(obtained, max) {
        if (max === 0) return 0;
        return ((obtained / max) * 100).toFixed(2);
    };

    /**
     * Animar contador de números
     */
    window.animateCounter = function(element, target, duration = 2000) {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 16);
    };

    /**
     * Mostrar notificación
     */
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const colors = {
            success: ISER_COLORS.green,
            error: ISER_COLORS.red,
            warning: ISER_COLORS.yellow,
            info: ISER_COLORS.green
        };

        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || colors.info};
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            font-family: Arial, sans-serif;
            animation: fadeIn 0.3s ease-out;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    };

    /**
     * Exportar colores ISER para uso externo
     */
    window.ISER_COLORS = ISER_COLORS;
    window.CHART_COLORS = CHART_COLORS;
    window.CHART_BORDER_COLORS = CHART_BORDER_COLORS;

})();

// Agregar estilos para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);
