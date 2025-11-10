<?php
/**
 * Paso 4: Instalación de Base de Datos
 *
 * Este paso ejecuta el SchemaInstaller y muestra el progreso en tiempo real
 */

// Redirect if no database config or schema not analyzed
if (!isset($_SESSION['db_config'])) {
    header('Location: ?step=2');
    exit;
}

if (!isset($_SESSION['step_3_completed']) || !$_SESSION['step_3_completed']) {
    header('Location: ?step=3');
    exit;
}

// Check if already installed
$alreadyInstalled = isset($_SESSION['step_4_completed']) && $_SESSION['step_4_completed'];
?>

<div class="mb-4">
    <p class="lead">
        <i class="bi bi-database text-primary me-2"></i>
        Se instalará la estructura completa de la base de datos utilizando el Schema XML.
    </p>
</div>

<?php if ($alreadyInstalled): ?>
    <div class="alert alert-success">
        <h5 class="alert-heading">
            <i class="bi bi-check-circle-fill me-2"></i>Base de Datos Ya Instalada
        </h5>
        <p class="mb-0">
            La base de datos ya ha sido instalada exitosamente en un paso anterior.
        </p>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=3" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Anterior
        </a>
        <a href="?step=5" class="btn btn-primary btn-installer btn-lg">
            Continuar
            <i class="bi bi-arrow-right ms-2"></i>
        </a>
    </div>

<?php else: ?>

<div class="alert alert-info mb-4">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Este proceso incluye:
    </h6>
    <ul class="mb-0">
        <li>Creación de 12 tablas del sistema</li>
        <li>Configuración de índices y claves foráneas</li>
        <li>Inserción de datos iniciales (roles, permisos, configuraciones)</li>
        <li>Asignación automática de permisos al rol administrador</li>
    </ul>
</div>

<!-- Installation Status -->
<div id="installation-container" style="display: none;">
    <!-- Progress Overview -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-hourglass-split me-2"></i>Progreso de Instalación
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-2">
                <div class="d-flex justify-content-between mb-1">
                    <span id="progress-text">Iniciando...</span>
                    <span id="progress-percent">0%</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <div id="progress-bar"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width: 0%"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100">0%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Installation Log -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h6 class="mb-0">
                <i class="bi bi-terminal me-2"></i>Log de Instalación
            </h6>
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; font-family: 'Courier New', monospace; font-size: 0.85rem;">
            <div id="installation-log"></div>
        </div>
    </div>
</div>

<!-- Results Container -->
<div id="results-container" style="display: none;"></div>

<!-- Start Installation Button -->
<div id="start-container">
    <div class="text-center my-4">
        <button type="button" id="start-install-btn" class="btn btn-primary btn-lg btn-installer" style="min-width: 250px;">
            <i class="bi bi-download me-2"></i>
            Iniciar Instalación
        </button>
    </div>
</div>

<!-- Navigation -->
<div class="d-flex justify-content-between mt-4">
    <a href="?step=3" class="btn btn-outline-secondary btn-lg" id="back-btn">
        <i class="bi bi-arrow-left me-2"></i>Anterior
    </a>
    <a href="?step=5" class="btn btn-primary btn-installer btn-lg" id="continue-btn" style="display: none;">
        Continuar
        <i class="bi bi-arrow-right ms-2"></i>
    </a>
</div>

<script>
// Installation Script
const InstallationManager = {
    startBtn: null,
    backBtn: null,
    continueBtn: null,
    startContainer: null,
    installContainer: null,
    resultsContainer: null,
    progressBar: null,
    progressText: null,
    progressPercent: null,
    log: null,

    init() {
        this.startBtn = document.getElementById('start-install-btn');
        this.backBtn = document.getElementById('back-btn');
        this.continueBtn = document.getElementById('continue-btn');
        this.startContainer = document.getElementById('start-container');
        this.installContainer = document.getElementById('installation-container');
        this.resultsContainer = document.getElementById('results-container');
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        this.progressPercent = document.getElementById('progress-percent');
        this.log = document.getElementById('installation-log');

        this.startBtn.addEventListener('click', () => this.startInstallation());
    },

    addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const icons = {
            info: 'ℹ️',
            success: '✅',
            error: '❌',
            warning: '⚠️'
        };
        const colors = {
            info: '#0d6efd',
            success: '#198754',
            error: '#dc3545',
            warning: '#ffc107'
        };

        const logEntry = document.createElement('div');
        logEntry.style.marginBottom = '0.5rem';
        logEntry.innerHTML = `
            <span style="color: #6c757d;">[${timestamp}]</span>
            <span style="color: ${colors[type]};">${icons[type]}</span>
            ${message}
        `;
        this.log.appendChild(logEntry);

        // Auto-scroll to bottom
        this.log.parentElement.scrollTop = this.log.parentElement.scrollHeight;
    },

    updateProgress(percent, text) {
        this.progressBar.style.width = percent + '%';
        this.progressBar.setAttribute('aria-valuenow', percent);
        this.progressBar.textContent = Math.round(percent) + '%';
        this.progressText.textContent = text;
        this.progressPercent.textContent = Math.round(percent) + '%';
    },

    async startInstallation() {
        // Disable start button
        this.startBtn.disabled = true;
        this.startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando...';
        this.backBtn.classList.add('disabled');

        // Show installation container
        this.startContainer.style.display = 'none';
        this.installContainer.style.display = 'block';

        // Start installation
        this.addLog('Iniciando instalación de base de datos...', 'info');
        this.updateProgress(5, 'Conectando a la base de datos...');

        try {
            const response = await fetch('install-database.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.handleSuccess(result);
            } else {
                this.handleError(result.errors || ['Error desconocido']);
            }

        } catch (error) {
            this.handleError([error.message]);
        }
    },

    handleSuccess(result) {
        this.addLog('✅ Conexión establecida', 'success');
        this.updateProgress(20, 'Instalando tablas...');

        // Simulate table-by-table progress
        const tables = result.tables || [];
        const totalTables = tables.length;

        tables.forEach((table, index) => {
            setTimeout(() => {
                const progress = 20 + ((index + 1) / totalTables) * 60;
                this.updateProgress(progress, `Creando tabla ${index + 1}/${totalTables}: ${table}...`);
                this.addLog(`Tabla <strong>${table}</strong> creada exitosamente`, 'success');

                if (index === totalTables - 1) {
                    // Finish installation
                    setTimeout(() => {
                        this.updateProgress(90, 'Insertando datos iniciales...');
                        this.addLog('Insertando roles por defecto...', 'info');
                        this.addLog('Insertando permisos...', 'info');
                        this.addLog('Asignando permisos al rol administrador...', 'info');

                        setTimeout(() => {
                            this.updateProgress(100, '¡Instalación completada!');
                            this.progressBar.classList.remove('progress-bar-animated');
                            this.progressBar.classList.add('bg-success');
                            this.showSuccessMessage(result);
                        }, 1000);
                    }, 500);
                }
            }, index * 200);
        });
    },

    handleError(errors) {
        this.progressBar.classList.remove('progress-bar-animated');
        this.progressBar.classList.add('bg-danger');
        this.updateProgress(100, 'Error en la instalación');

        errors.forEach(error => {
            this.addLog(error, 'error');
        });

        this.resultsContainer.innerHTML = `
            <div class="alert alert-danger">
                <h5 class="alert-heading">
                    <i class="bi bi-x-circle-fill me-2"></i>Error en la Instalación
                </h5>
                <p>Se encontraron los siguientes errores:</p>
                <ul class="mb-0">
                    ${errors.map(e => `<li>${e}</li>`).join('')}
                </ul>
            </div>
        `;
        this.resultsContainer.style.display = 'block';

        this.backBtn.classList.remove('disabled');
    },

    showSuccessMessage(result) {
        this.addLog('🎉 Instalación completada exitosamente!', 'success');

        const tablesCount = result.tables?.length || 0;
        const dataRows = result.initial_data_rows || 0;

        this.resultsContainer.innerHTML = `
            <div class="alert alert-success mb-4">
                <h5 class="alert-heading">
                    <i class="bi bi-check-circle-fill me-2"></i>¡Instalación Exitosa!
                </h5>
                <p class="mb-0">La base de datos ha sido instalada correctamente.</p>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card border-success text-center">
                        <div class="card-body">
                            <div class="display-4 text-success">${tablesCount}</div>
                            <p class="mb-0">Tablas Creadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info text-center">
                        <div class="card-body">
                            <div class="display-4 text-info">${dataRows}</div>
                            <p class="mb-0">Registros Iniciales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-primary text-center">
                        <div class="card-body">
                            <div class="display-4 text-primary">✓</div>
                            <p class="mb-0">Sistema Listo</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        this.resultsContainer.style.display = 'block';

        this.continueBtn.style.display = 'inline-block';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    InstallationManager.init();
});
</script>

<?php endif; ?>
