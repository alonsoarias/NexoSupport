<?php
/**
 * Paso 5: Configuración del Sistema
 */

// Redirect if database not installed
if (!isset($_SESSION['step_4_completed']) || !$_SESSION['step_4_completed']) {
    // Check if database actually exists and was installed
    if (!isset($_SESSION['db_config'])) {
        header('Location: ?step=2');
        exit;
    }
}

$defaultValues = $_SESSION['system_config'] ?? [
    'app_name' => 'ISER Authentication System',
    'app_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'app_env' => 'production',
    'app_debug' => 'false',
    'app_timezone' => 'America/Mexico_City',
    'app_locale' => 'es',
    'session_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'true' : 'false'
];

$timezones = timezone_identifiers_list();
?>

<div class="mb-4">
    <p class="lead">
        <i class="bi bi-gear text-primary me-2"></i>
        Configure los parámetros generales del sistema y genere el archivo de configuración.
    </p>
</div>

<form method="POST" action="?step=5" id="system-config-form">
    <!-- General Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-sliders me-2"></i>Configuración General
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="app_name" class="form-label">
                    <i class="bi bi-tag me-1"></i> Nombre de la Aplicación
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="app_name"
                       name="app_name"
                       value="<?= htmlspecialchars($defaultValues['app_name']) ?>"
                       required>
                <div class="form-text">Este nombre aparecerá en la interfaz, emails y notificaciones</div>
            </div>

            <div class="mb-3">
                <label for="app_url" class="form-label">
                    <i class="bi bi-link-45deg me-1"></i> URL de la Aplicación
                    <span class="text-danger">*</span>
                </label>
                <input type="url"
                       class="form-control"
                       id="app_url"
                       name="app_url"
                       value="<?= htmlspecialchars($defaultValues['app_url']) ?>"
                       placeholder="https://ejemplo.com"
                       required>
                <div class="form-text">URL completa sin la barra final (ej: https://ejemplo.com)</div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_timezone" class="form-label">
                            <i class="bi bi-clock me-1"></i> Zona Horaria
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="app_timezone" name="app_timezone" required>
                            <optgroup label="América">
                                <option value="America/Mexico_City" <?= $defaultValues['app_timezone'] === 'America/Mexico_City' ? 'selected' : '' ?>>
                                    México (GMT-6)
                                </option>
                                <option value="America/New_York" <?= $defaultValues['app_timezone'] === 'America/New_York' ? 'selected' : '' ?>>
                                    New York (GMT-5)
                                </option>
                                <option value="America/Los_Angeles" <?= $defaultValues['app_timezone'] === 'America/Los_Angeles' ? 'selected' : '' ?>>
                                    Los Angeles (GMT-8)
                                </option>
                                <option value="America/Chicago" <?= $defaultValues['app_timezone'] === 'America/Chicago' ? 'selected' : '' ?>>
                                    Chicago (GMT-6)
                                </option>
                                <option value="America/Bogota" <?= $defaultValues['app_timezone'] === 'America/Bogota' ? 'selected' : '' ?>>
                                    Bogotá (GMT-5)
                                </option>
                                <option value="America/Argentina/Buenos_Aires" <?= $defaultValues['app_timezone'] === 'America/Argentina/Buenos_Aires' ? 'selected' : '' ?>>
                                    Buenos Aires (GMT-3)
                                </option>
                            </optgroup>
                            <optgroup label="Europa">
                                <option value="Europe/Madrid" <?= $defaultValues['app_timezone'] === 'Europe/Madrid' ? 'selected' : '' ?>>
                                    Madrid (GMT+1)
                                </option>
                                <option value="Europe/London" <?= $defaultValues['app_timezone'] === 'Europe/London' ? 'selected' : '' ?>>
                                    Londres (GMT+0)
                                </option>
                            </optgroup>
                            <optgroup label="Otras">
                                <?php foreach ($timezones as $tz): ?>
                                    <?php if (!in_array($tz, ['America/Mexico_City', 'America/New_York', 'America/Los_Angeles', 'America/Chicago', 'America/Bogota', 'America/Argentina/Buenos_Aires', 'Europe/Madrid', 'Europe/London'])): ?>
                                        <option value="<?= $tz ?>" <?= $defaultValues['app_timezone'] === $tz ? 'selected' : '' ?>>
                                            <?= $tz ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_locale" class="form-label">
                            <i class="bi bi-translate me-1"></i> Idioma
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="app_locale" name="app_locale" required>
                            <option value="es" <?= $defaultValues['app_locale'] === 'es' ? 'selected' : '' ?>>
                                Español
                            </option>
                            <option value="en" <?= $defaultValues['app_locale'] === 'en' ? 'selected' : '' ?>>
                                English
                            </option>
                            <option value="pt" <?= $defaultValues['app_locale'] === 'pt' ? 'selected' : '' ?>>
                                Português
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Environment Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="bi bi-server me-2"></i>Configuración de Entorno
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_env" class="form-label">
                            Entorno de Ejecución
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="app_env" name="app_env" required>
                            <option value="production" <?= $defaultValues['app_env'] === 'production' ? 'selected' : '' ?>>
                                🟢 Producción (Recomendado)
                            </option>
                            <option value="development" <?= $defaultValues['app_env'] === 'development' ? 'selected' : '' ?>>
                                🔵 Desarrollo
                            </option>
                            <option value="testing" <?= $defaultValues['app_env'] === 'testing' ? 'selected' : '' ?>>
                                🟡 Pruebas
                            </option>
                        </select>
                        <div class="form-text">
                            <strong>Producción:</strong> Para sitios en vivo<br>
                            <strong>Desarrollo:</strong> Para entorno de desarrollo local
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_debug" class="form-label">
                            Modo Debug
                        </label>
                        <select class="form-select" id="app_debug" name="app_debug">
                            <option value="false" <?= $defaultValues['app_debug'] === 'false' ? 'selected' : '' ?>>
                                🔒 Desactivado (Recomendado para producción)
                            </option>
                            <option value="true" <?= $defaultValues['app_debug'] === 'true' ? 'selected' : '' ?>>
                                🔓 Activado (Solo para desarrollo)
                            </option>
                        </select>
                        <div class="form-text">
                            Active solo en desarrollo. Muestra errores detallados.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-shield-lock me-2"></i>Configuración de Seguridad
            </h5>
        </div>
        <div class="card-body">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       id="session_secure"
                       name="session_secure"
                       value="true"
                       <?= $defaultValues['session_secure'] === 'true' ? 'checked' : '' ?>>
                <label class="form-check-label" for="session_secure">
                    <i class="bi bi-lock me-1"></i>
                    <strong>Usar cookies seguras (HTTPS)</strong>
                </label>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>
                    Estado actual: <strong class="<?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'text-success' : 'text-warning' ?>">
                        <?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS Activo ✓' : 'HTTP (sin cifrado)' ?>
                    </strong><br>
                    Active solo si su sitio usa HTTPS. Las cookies seguras solo se envían por conexiones cifradas.
                </div>
            </div>

            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="bi bi-shield-check me-2"></i>Configuración de Seguridad Adicional
                </h6>
                <p class="mb-0">
                    Las siguientes opciones de seguridad ya están preconfiguradas en el sistema:
                </p>
                <ul class="mb-0 mt-2">
                    <li>Longitud mínima de contraseña: <strong>8 caracteres</strong></li>
                    <li>Intentos máximos de inicio de sesión: <strong>5</strong></li>
                    <li>Duración de bloqueo: <strong>15 minutos</strong></li>
                    <li>Expiración de JWT: <strong>1 hora</strong></li>
                    <li>Expiración de Refresh Token: <strong>7 días</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Information Alert -->
    <div class="alert alert-success">
        <h6 class="alert-heading">
            <i class="bi bi-check-circle me-2"></i>Archivo .env
        </h6>
        <p class="mb-0">
            Al continuar, se generará el archivo <code>.env</code> con toda la configuración del sistema.
            Puede modificar estos valores más tarde editando directamente el archivo.
        </p>
    </div>

    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-between mt-4">
        <a href="?step=4" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-installer btn-lg">
            Generar Configuración
            <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<script>
// Form validation feedback
document.getElementById('system-config-form').addEventListener('submit', function(e) {
    const appUrl = document.getElementById('app_url').value;

    // Validate URL format
    try {
        new URL(appUrl);
    } catch {
        e.preventDefault();
        InstallerApp.showError('La URL de la aplicación no es válida. Debe incluir el protocolo (http:// o https://)');
        return false;
    }

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando configuración...';
});

// Real-time URL validation
document.getElementById('app_url').addEventListener('blur', function() {
    try {
        new URL(this.value);
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } catch {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});
</script>
