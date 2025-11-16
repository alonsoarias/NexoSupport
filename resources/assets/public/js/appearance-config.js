/**
 * Appearance Configuration Manager - Phase 9
 *
 * Handles all theme configuration interactions:
 * - Real-time color updates
 * - WCAG contrast validation
 * - Export/Import configuration
 * - Backup management
 * - CSS regeneration
 *
 * @package NexoSupport
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Configuration state
    const state = {
        isDirty: false,
        currentConfig: {},
        backups: []
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initColorPickers();
        initTypography();
        initActions();
        loadBackups();
        attachEventListeners();
    });

    /**
     * Initialize color pickers with real-time sync
     */
    function initColorPickers() {
        const colorPickers = document.querySelectorAll('.color-picker');
        const hexInputs = document.querySelectorAll('.hex-input');

        // Sync color picker -> hex input
        colorPickers.forEach(picker => {
            picker.addEventListener('input', function(e) {
                const colorName = this.dataset.colorName;
                const hexInput = document.getElementById('hex-' + colorName);
                if (hexInput) {
                    hexInput.value = this.value.toUpperCase();
                    updateColorPreview(colorName, this.value);
                    validateContrast(colorName, this.value);
                    markDirty();
                }
            });
        });

        // Sync hex input -> color picker
        hexInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                const colorName = this.id.replace('hex-', '');
                const hexValue = this.value.trim();

                if (isValidHex(hexValue)) {
                    const picker = document.getElementById('color-' + colorName);
                    if (picker) {
                        picker.value = hexValue;
                        updateColorPreview(colorName, hexValue);
                        validateContrast(colorName, hexValue);
                        markDirty();
                    }
                }
            });

            // Force uppercase and # prefix
            input.addEventListener('blur', function(e) {
                let value = this.value.trim();
                if (value && !value.startsWith('#')) {
                    value = '#' + value;
                }
                this.value = value.toUpperCase();
            });
        });
    }

    /**
     * Initialize typography selectors
     */
    function initTypography() {
        const fontSelects = document.querySelectorAll('#font-heading, #font-body, #font-mono');

        fontSelects.forEach(select => {
            select.addEventListener('change', function() {
                markDirty();
                // Update preview immediately
                const previewElement = this.parentElement.querySelector('.font-preview');
                if (previewElement) {
                    previewElement.style.fontFamily = this.value;
                }
            });
        });
    }

    /**
     * Initialize action buttons
     */
    function initActions() {
        // Save configuration
        const saveBtn = document.getElementById('save-config-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveConfiguration);
        }

        // Reset colors
        const resetColorsBtn = document.getElementById('reset-colors-btn');
        if (resetColorsBtn) {
            resetColorsBtn.addEventListener('click', resetColors);
        }

        // Export configuration
        const exportBtn = document.getElementById('export-config-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', exportConfiguration);
        }

        // Import configuration
        const importBtn = document.getElementById('import-config-btn');
        const importInput = document.getElementById('import-file-input');
        if (importBtn && importInput) {
            importBtn.addEventListener('click', () => importInput.click());
            importInput.addEventListener('change', importConfiguration);
        }

        // Create backup
        const createBackupBtn = document.getElementById('create-backup-btn');
        if (createBackupBtn) {
            createBackupBtn.addEventListener('click', createBackup);
        }

        // Regenerate CSS
        const regenerateCssBtn = document.getElementById('regenerate-css-btn');
        if (regenerateCssBtn) {
            regenerateCssBtn.addEventListener('click', regenerateCSS);
        }

        // Reset all
        const resetAllBtn = document.getElementById('reset-all-btn');
        if (resetAllBtn) {
            resetAllBtn.addEventListener('click', resetAll);
        }

        // Dark mode toggle
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('change', function() {
                document.documentElement.setAttribute('data-theme', this.checked ? 'dark' : 'light');
                markDirty();
            });
        }
    }

    /**
     * Attach global event listeners
     */
    function attachEventListeners() {
        // Warn before leaving if unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (state.isDirty) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });

        // Form inputs mark dirty
        const formInputs = document.querySelectorAll('#site-name, #site-tagline, #logo-url, #logo-dark-url, #favicon-url, #default-layout, #sidebar-width, #navbar-height, #container-width');
        formInputs.forEach(input => {
            input.addEventListener('input', markDirty);
        });
    }

    /**
     * Update color preview box
     */
    function updateColorPreview(colorName, hexValue) {
        const preview = document.querySelector(`#color-${colorName}`).closest('.color-item').querySelector('.color-preview');
        if (preview) {
            preview.style.backgroundColor = hexValue;
        }
    }

    /**
     * Validate color contrast (WCAG)
     */
    async function validateContrast(colorName, hexValue) {
        const contrastElement = document.getElementById('contrast-' + colorName);
        if (!contrastElement) return;

        try {
            // Use white as default background for contrast check
            const background = '#FFFFFF';

            const response = await fetch('/admin/appearance/validate-contrast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    foreground: hexValue,
                    background: background
                })
            });

            const result = await response.json();

            if (result.success && result.data) {
                const ratio = result.data.ratio;
                const meetsAA = result.data.meets_aa;
                const rating = result.data.rating;

                const ratioSpan = contrastElement.querySelector('.contrast-ratio');
                if (ratioSpan) {
                    ratioSpan.textContent = `${ratio}:1`;

                    // Color code the ratio based on WCAG compliance
                    if (meetsAA) {
                        ratioSpan.style.color = '#28a745'; // Green
                    } else {
                        ratioSpan.style.color = '#dc3545'; // Red
                    }
                }
            }
        } catch (error) {
            console.error('Contrast validation failed:', error);
        }
    }

    /**
     * Validate HEX color format
     */
    function isValidHex(hex) {
        return /^#?[0-9A-Fa-f]{6}$/.test(hex) || /^#?[0-9A-Fa-f]{3}$/.test(hex);
    }

    /**
     * Mark configuration as dirty (unsaved changes)
     */
    function markDirty() {
        state.isDirty = true;
        const saveBtn = document.getElementById('save-config-btn');
        if (saveBtn && !saveBtn.classList.contains('btn-warning')) {
            saveBtn.classList.remove('btn-success');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Guardar Cambios';
        }
    }

    /**
     * Mark configuration as saved
     */
    function markClean() {
        state.isDirty = false;
        const saveBtn = document.getElementById('save-config-btn');
        if (saveBtn) {
            saveBtn.classList.remove('btn-warning');
            saveBtn.classList.add('btn-success');
            saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Guardado';

            setTimeout(() => {
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-success');
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Guardar Cambios';
            }, 3000);
        }
    }

    /**
     * Save configuration to server
     */
    async function saveConfiguration() {
        showLoading('Guardando configuración...');

        const config = {
            colors: {},
            fonts: {}
        };

        // Collect colors
        document.querySelectorAll('.color-picker').forEach(picker => {
            const colorName = picker.dataset.colorName;
            config.colors[colorName] = picker.value;
        });

        // Collect fonts
        const fontHeading = document.getElementById('font-heading');
        const fontBody = document.getElementById('font-body');
        const fontMono = document.getElementById('font-mono');

        if (fontHeading) config.fonts.font_heading = fontHeading.value;
        if (fontBody) config.fonts.font_body = fontBody.value;
        if (fontMono) config.fonts.font_mono = fontMono.value;

        // Collect branding
        // TODO: Add branding fields when implemented

        // Collect layout
        // TODO: Add layout fields when implemented

        try {
            const response = await fetch('/admin/appearance/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(config)
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message || 'Configuración guardada exitosamente');
                markClean();

                // Regenerate CSS automatically after save
                await regenerateCSS(true);
            } else {
                showAlert('danger', result.message || 'Error al guardar la configuración');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    /**
     * Reset colors to defaults
     */
    async function resetColors() {
        if (!confirm('¿Estás seguro de que deseas restaurar todos los colores a sus valores predeterminados?')) {
            return;
        }

        showLoading('Restaurando colores predeterminados...');

        try {
            const response = await fetch('/admin/appearance/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Colores restaurados exitosamente');
                // Reload page to show new values
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('danger', result.message || 'Error al restaurar colores');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    /**
     * Export configuration as JSON file
     */
    async function exportConfiguration() {
        showLoading('Exportando configuración...');

        try {
            const response = await fetch('/admin/appearance/export');

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `theme-export-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                showAlert('success', 'Configuración exportada exitosamente');
            } else {
                showAlert('danger', 'Error al exportar configuración');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    /**
     * Import configuration from JSON file
     */
    async function importConfiguration(e) {
        const file = e.target.files[0];
        if (!file) return;

        showLoading('Importando configuración...');

        const reader = new FileReader();
        reader.onload = async function(event) {
            try {
                const json = event.target.result;

                // Validate JSON
                JSON.parse(json);

                const response = await fetch('/admin/appearance/import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ json: json })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', 'Configuración importada exitosamente');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', result.message || 'Error al importar configuración');
                }
            } catch (error) {
                showAlert('danger', 'Archivo JSON inválido');
            } finally {
                hideLoading();
                e.target.value = ''; // Reset input
            }
        };
        reader.readAsText(file);
    }

    /**
     * Create backup of current configuration
     */
    async function createBackup() {
        const nameInput = document.getElementById('backup-name-input');
        const backupName = nameInput.value.trim() || `Respaldo ${new Date().toLocaleString()}`;

        showLoading('Creando respaldo...');

        try {
            const response = await fetch('/admin/appearance/backup/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name: backupName })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Respaldo creado exitosamente');
                nameInput.value = '';
                loadBackups(); // Refresh list
            } else {
                showAlert('danger', result.message || 'Error al crear respaldo');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    /**
     * Load backups list
     */
    async function loadBackups() {
        try {
            const response = await fetch('/admin/appearance/backups');
            const result = await response.json();

            if (result.success && result.data) {
                state.backups = result.data;
                renderBackupsList(result.data);
            }
        } catch (error) {
            console.error('Failed to load backups:', error);
        }
    }

    /**
     * Render backups list
     */
    function renderBackupsList(backups) {
        const container = document.getElementById('backups-list');
        if (!container) return;

        if (!backups || backups.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p>No hay respaldos guardados</p>
                </div>
            `;
            return;
        }

        const html = backups.map(backup => `
            <div class="backup-item">
                <div class="backup-info">
                    <div class="backup-name">${escapeHtml(backup.backup_name)}</div>
                    <div class="backup-date">
                        <i class="bi bi-clock"></i> ${new Date(backup.created_at * 1000).toLocaleString()}
                    </div>
                </div>
                <div class="backup-actions">
                    <button class="btn btn-sm btn-primary" onclick="restoreBackup(${backup.id})">
                        <i class="bi bi-arrow-clockwise"></i> Restaurar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteBackup(${backup.id})">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    /**
     * Restore backup
     */
    window.restoreBackup = async function(backupId) {
        if (!confirm('¿Estás seguro de que deseas restaurar este respaldo? Se sobrescribirá la configuración actual.')) {
            return;
        }

        showLoading('Restaurando respaldo...');

        try {
            const response = await fetch('/admin/appearance/backup/restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ backup_id: backupId })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Respaldo restaurado exitosamente');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('danger', result.message || 'Error al restaurar respaldo');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    };

    /**
     * Delete backup
     */
    window.deleteBackup = async function(backupId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este respaldo? Esta acción no se puede deshacer.')) {
            return;
        }

        showLoading('Eliminando respaldo...');

        try {
            const response = await fetch(`/admin/appearance/backup/delete/${backupId}`, {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Respaldo eliminado exitosamente');
                loadBackups(); // Refresh list
            } else {
                showAlert('danger', result.message || 'Error al eliminar respaldo');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    };

    /**
     * Regenerate CSS file
     */
    async function regenerateCSS(silent = false) {
        if (!silent) {
            showLoading('Regenerando archivos CSS...');
        }

        try {
            const response = await fetch('/admin/appearance/regenerate-css', {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                if (!silent) {
                    showAlert('success', 'CSS regenerado exitosamente');
                }
                // Reload page to apply new CSS
                setTimeout(() => window.location.reload(), silent ? 500 : 1500);
            } else {
                if (!silent) {
                    showAlert('danger', result.message || 'Error al regenerar CSS');
                }
            }
        } catch (error) {
            if (!silent) {
                showAlert('danger', 'Error de red: ' + error.message);
            }
        } finally {
            if (!silent) {
                hideLoading();
            }
        }
    }

    /**
     * Reset all configuration
     */
    async function resetAll() {
        if (!confirm('⚠️ ADVERTENCIA: Esto restaurará TODA la configuración del tema a los valores predeterminados de fábrica.\n\n¿Estás absolutamente seguro?')) {
            return;
        }

        if (!confirm('Esta acción NO se puede deshacer. ¿Deseas continuar?')) {
            return;
        }

        showLoading('Restaurando configuración...');

        try {
            const response = await fetch('/admin/appearance/reset', {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Configuración restaurada exitosamente');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('danger', result.message || 'Error al restaurar configuración');
            }
        } catch (error) {
            showAlert('danger', 'Error de red: ' + error.message);
        } finally {
            hideLoading();
        }
    }

    /**
     * Show alert message
     */
    function showAlert(type, message) {
        const container = document.getElementById('alert-container');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'x-circle' : 'info-circle'}"></i>
            <span>${escapeHtml(message)}</span>
        `;

        container.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    /**
     * Show loading overlay
     */
    function showLoading(message = 'Cargando...') {
        let overlay = document.getElementById('loading-overlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                color: white;
                font-size: 1.2rem;
            `;
            document.body.appendChild(overlay);
        }

        overlay.innerHTML = `
            <div style="text-align: center;">
                <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; margin-bottom: 1rem;"></div>
                <div>${escapeHtml(message)}</div>
            </div>
        `;
        overlay.style.display = 'flex';
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
