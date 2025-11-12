<?php
/**
 * Stage 7: Logging Configuration
 */

// Obtener valores guardados o por defecto
$log_channel = $_SESSION['log_channel'] ?? 'daily';
$log_level = $_SESSION['log_level'] ?? 'info';
$log_path = $_SESSION['log_path'] ?? 'var/logs/iser.log';
$log_max_files = $_SESSION['log_max_files'] ?? 14;
$log_max_size = $_SESSION['log_max_size'] ?? 10;
$log_query_enabled = $_SESSION['log_query_enabled'] ?? false;
?>

<h3 class="mb-4"><i class="bi bi-file-text"></i> Configuración de Logs</h3>

<p class="text-muted">Configure cómo el sistema registrará eventos, errores y actividad.</p>

<form method="POST" id="loggingForm">
    <input type="hidden" name="stage" value="<?= STAGE_LOGGING ?>">

    <!-- Log Channel -->
    <div class="mb-4">
        <label class="form-label"><i class="bi bi-hdd"></i> Canal de Logs *</label>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100 log-channel-card <?= $log_channel === 'single' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="log_channel"
                                   id="channel_single" value="single" <?= $log_channel === 'single' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="channel_single">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark text-primary fs-4 me-2"></i>
                                    <strong>Archivo Único</strong>
                                </div>
                                <small class="text-muted">Todos los logs en un solo archivo</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 log-channel-card <?= $log_channel === 'daily' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="log_channel"
                                   id="channel_daily" value="daily" <?= $log_channel === 'daily' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="channel_daily">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calendar3 text-success fs-4 me-2"></i>
                                    <strong>Diario</strong>
                                </div>
                                <small class="text-muted">Un archivo por día (recomendado)</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 log-channel-card <?= $log_channel === 'syslog' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="log_channel"
                                   id="channel_syslog" value="syslog" <?= $log_channel === 'syslog' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="channel_syslog">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-server text-warning fs-4 me-2"></i>
                                    <strong>Syslog</strong>
                                </div>
                                <small class="text-muted">Logs del sistema operativo</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Level -->
    <div class="mb-4">
        <label class="form-label"><i class="bi bi-bar-chart"></i> Nivel de Logs *</label>
        <select name="log_level" class="form-select" required>
            <option value="debug" <?= $log_level === 'debug' ? 'selected' : '' ?>>
                Debug - Todo (desarrollo)
            </option>
            <option value="info" <?= $log_level === 'info' ? 'selected' : '' ?>>
                Info - Información general (recomendado)
            </option>
            <option value="warning" <?= $log_level === 'warning' ? 'selected' : '' ?>>
                Warning - Advertencias y errores
            </option>
            <option value="error" <?= $log_level === 'error' ? 'selected' : '' ?>>
                Error - Solo errores críticos
            </option>
        </select>
        <small class="text-muted">Nivel mínimo de severidad para registrar eventos</small>
    </div>

    <!-- Log Path -->
    <div class="mb-4" id="logPathField">
        <label class="form-label"><i class="bi bi-folder"></i> Ruta de Logs *</label>
        <input type="text" name="log_path" class="form-control" value="<?= htmlspecialchars($log_path) ?>" required>
        <small class="text-muted">Ruta relativa al directorio raíz del sistema</small>
    </div>

    <!-- Log Rotation -->
    <div class="mb-4" id="rotationFields">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-arrow-repeat"></i> Rotación de Logs</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Máximo de Archivos *</label>
                <input type="number" name="log_max_files" class="form-control"
                       value="<?= $log_max_files ?>" min="1" max="365" required>
                <small class="text-muted">Cantidad de archivos históricos a mantener</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Tamaño Máximo (MB) *</label>
                <input type="number" name="log_max_size" class="form-control"
                       value="<?= $log_max_size ?>" min="1" max="100" required>
                <small class="text-muted">Tamaño máximo por archivo de log</small>
            </div>
        </div>
    </div>

    <!-- Query Logging -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-database"></i> Logs de Base de Datos</h5>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="log_query_enabled"
                   id="query_logging" value="1" <?= $log_query_enabled ? 'checked' : '' ?>>
            <label class="form-check-label" for="query_logging">
                <strong>Registrar consultas SQL</strong>
            </label>
        </div>
        <small class="text-muted">
            Registra todas las consultas ejecutadas. Útil para debugging pero puede generar archivos grandes.
        </small>

        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Advertencia:</strong> Habilitar el logging de consultas puede afectar el rendimiento en producción.
            Se recomienda solo para ambientes de desarrollo.
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="submit" name="next" class="btn btn-primary">
            Siguiente <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<script>
// Actualizar visibilidad de campos según el canal seleccionado
document.querySelectorAll('input[name="log_channel"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const channel = this.value;
        const logPathField = document.getElementById('logPathField');
        const rotationFields = document.getElementById('rotationFields');

        if (channel === 'syslog') {
            // Syslog no necesita path ni rotación
            logPathField.style.display = 'none';
            rotationFields.style.display = 'none';
            document.querySelector('input[name="log_path"]').removeAttribute('required');
        } else {
            // Single y Daily necesitan path y rotación
            logPathField.style.display = 'block';
            rotationFields.style.display = 'block';
            document.querySelector('input[name="log_path"]').setAttribute('required', 'required');
        }

        // Actualizar border de las cards
        document.querySelectorAll('.log-channel-card').forEach(card => {
            card.classList.remove('border-primary');
        });
        this.closest('.log-channel-card').classList.add('border-primary');
    });
});

// Inicializar estado al cargar
const selectedChannel = document.querySelector('input[name="log_channel"]:checked');
if (selectedChannel) {
    selectedChannel.dispatchEvent(new Event('change'));
}
</script>

<style>
.log-channel-card {
    cursor: pointer;
    transition: all 0.2s;
}

.log-channel-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.log-channel-card.border-primary {
    box-shadow: 0 0 0 0.2rem rgba(27, 158, 136, 0.25);
    border-color: var(--iser-green) !important;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
}

.form-switch .form-check-input:checked {
    background-color: var(--iser-green);
    border-color: var(--iser-green);
}

.alert-warning {
    background-color: #fff8e1;
    border-left: 4px solid var(--iser-yellow);
}

.border-bottom {
    border-color: var(--border-color) !important;
}
</style>
