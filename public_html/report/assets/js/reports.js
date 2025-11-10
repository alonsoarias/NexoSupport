/**
 * Sistema de Reportes - JavaScript Interactivo
 * @package report
 * @author ISER Desarrollo
 */

class ReportManager {
    constructor() {
        this.charts = {};
        this.autoRefreshInterval = null;
        this.init();
    }

    /**
     * Inicializar gestor de reportes
     */
    init() {
        this.setupFilters();
        this.setupCharts();
        this.setupExportButtons();
        this.setupAutoRefresh();
        this.setupSearch();
        this.setupAlertHandlers();
    }

    /**
     * Configurar filtros
     */
    setupFilters() {
        const filterForm = document.getElementById('filter-form');
        if (!filterForm) return;

        // Auto-submit cuando cambian los filtros
        filterForm.querySelectorAll('select, input[type="date"]').forEach(input => {
            input.addEventListener('change', () => {
                this.applyFilters();
            });
        });

        // Reset button
        const resetBtn = document.getElementById('reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                filterForm.reset();
                this.applyFilters();
            });
        }
    }

    /**
     * Aplicar filtros
     */
    applyFilters() {
        const form = document.getElementById('filter-form');
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        // Recargar página con nuevos filtros
        window.location.href = `?${params.toString()}`;
    }

    /**
     * Configurar gráficos
     */
    setupCharts() {
        this.createSeverityChart();
        this.createComponentChart();
        this.createActivityChart();
    }

    /**
     * Crear gráfico de severidad
     */
    createSeverityChart() {
        const canvas = document.getElementById('severity-chart');
        if (!canvas) return;

        const data = JSON.parse(canvas.dataset.chartData || '[]');

        this.charts.severity = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    data: data.map(item => item.value),
                    backgroundColor: [
                        '#17a2b8', // Info
                        '#ffc107', // Warning
                        '#fd7e14', // Error
                        '#dc3545'  // Critical
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Distribución por Severidad'
                    }
                }
            }
        });
    }

    /**
     * Crear gráfico de componentes
     */
    createComponentChart() {
        const canvas = document.getElementById('component-chart');
        if (!canvas) return;

        const data = JSON.parse(canvas.dataset.chartData || '[]');

        this.charts.component = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    label: 'Eventos',
                    data: data.map(item => item.value),
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Eventos por Componente'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Crear gráfico de actividad diaria
     */
    createActivityChart() {
        const canvas = document.getElementById('activity-chart');
        if (!canvas) return;

        const data = JSON.parse(canvas.dataset.chartData || '[]');

        this.charts.activity = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Eventos',
                    data: data.map(item => item.value),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Actividad Diaria (últimos 30 días)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Configurar botones de exportación
     */
    setupExportButtons() {
        const exportButtons = document.querySelectorAll('[data-export]');

        exportButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const format = btn.dataset.export;
                const type = btn.dataset.type || 'logs';
                this.exportData(format, type);
            });
        });
    }

    /**
     * Exportar datos
     */
    exportData(format, type = 'logs') {
        // Obtener filtros actuales
        const params = new URLSearchParams(window.location.search);
        params.set('action', 'export');
        params.set('format', format);
        params.set('type', type);

        // Abrir en nueva ventana
        window.open(`?${params.toString()}`, '_blank');

        // Notificación
        this.showNotification(`Exportando datos en formato ${format.toUpperCase()}...`, 'info');
    }

    /**
     * Configurar auto-refresh
     */
    setupAutoRefresh() {
        const autoRefreshToggle = document.getElementById('auto-refresh');
        if (!autoRefreshToggle) return;

        autoRefreshToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        });

        // Si está activo al cargar, iniciar
        if (autoRefreshToggle.checked) {
            this.startAutoRefresh();
        }
    }

    /**
     * Iniciar auto-refresh
     */
    startAutoRefresh() {
        const interval = parseInt(
            document.getElementById('refresh-interval')?.value || '30'
        );

        this.autoRefreshInterval = setInterval(() => {
            this.refreshData();
        }, interval * 1000);

        this.showNotification(`Auto-refresh activado (cada ${interval}s)`, 'success');
    }

    /**
     * Detener auto-refresh
     */
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
            this.showNotification('Auto-refresh desactivado', 'info');
        }
    }

    /**
     * Refrescar datos
     */
    async refreshData() {
        try {
            const params = new URLSearchParams(window.location.search);
            params.set('ajax', '1');

            const response = await fetch(`?${params.toString()}`);
            const data = await response.json();

            this.updateDashboard(data);
            this.updateLastRefresh();

        } catch (error) {
            console.error('Error al refrescar datos:', error);
        }
    }

    /**
     * Actualizar dashboard
     */
    updateDashboard(data) {
        // Actualizar estadísticas
        if (data.stats) {
            document.querySelectorAll('[data-stat]').forEach(elem => {
                const stat = elem.dataset.stat;
                if (data.stats[stat] !== undefined) {
                    elem.textContent = this.formatNumber(data.stats[stat]);
                }
            });
        }

        // Actualizar gráficos
        if (data.charts) {
            Object.keys(data.charts).forEach(chartName => {
                if (this.charts[chartName]) {
                    this.updateChart(chartName, data.charts[chartName]);
                }
            });
        }
    }

    /**
     * Actualizar gráfico
     */
    updateChart(chartName, data) {
        const chart = this.charts[chartName];
        if (!chart) return;

        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data.values;
        chart.update();
    }

    /**
     * Actualizar timestamp de última actualización
     */
    updateLastRefresh() {
        const elem = document.getElementById('last-refresh');
        if (elem) {
            elem.textContent = new Date().toLocaleTimeString();
        }
    }

    /**
     * Configurar búsqueda
     */
    setupSearch() {
        const searchInput = document.getElementById('search-logs');
        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 500);
        });
    }

    /**
     * Realizar búsqueda
     */
    performSearch(query) {
        const params = new URLSearchParams(window.location.search);
        params.set('search', query);
        params.set('page', '1');

        window.location.href = `?${params.toString()}`;
    }

    /**
     * Configurar manejadores de alertas
     */
    setupAlertHandlers() {
        // Botones de resolución de alertas
        document.querySelectorAll('[data-resolve-alert]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const alertId = btn.dataset.resolveAlert;
                await this.resolveAlert(alertId);
            });
        });

        // Filtros de estado de alertas
        const statusFilter = document.getElementById('alert-status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                const params = new URLSearchParams(window.location.search);
                params.set('status', e.target.value);
                params.set('page', '1');
                window.location.href = `?${params.toString()}`;
            });
        }
    }

    /**
     * Resolver alerta
     */
    async resolveAlert(alertId) {
        try {
            const response = await fetch('/api/v1/security-alerts/resolve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    alert_id: alertId,
                    status: 'resolved',
                    notes: 'Resuelto desde interfaz web'
                })
            });

            if (response.ok) {
                this.showNotification('Alerta resuelta exitosamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error('Error al resolver alerta');
            }

        } catch (error) {
            this.showNotification('Error al resolver alerta', 'danger');
            console.error(error);
        }
    }

    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Formatear número
     */
    formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.reportManager = new ReportManager();
    });
} else {
    window.reportManager = new ReportManager();
}
