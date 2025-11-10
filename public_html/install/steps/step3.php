<?php
/**
 * Paso 3: Configuración del Sistema
 */

$defaultValues = $_SESSION['system_config'] ?? [
    'app_name' => 'ISER Authentication System',
    'app_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'app_env' => 'production',
    'app_debug' => 'false',
    'app_timezone' => 'America/Mexico_City',
    'session_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'true' : 'false'
];

$timezones = timezone_identifiers_list();
?>

<div class="mb-4">
    <p class="lead">
        Configure los parámetros generales del sistema.
    </p>
</div>

<form method="POST" action="?step=3">
    <h5 class="mb-3"><i class="bi bi-gear me-2"></i>Configuración General</h5>

    <div class="mb-3">
        <label for="app_name" class="form-label">
            Nombre de la Aplicación <span class="text-danger">*</span>
        </label>
        <input type="text"
               class="form-control"
               id="app_name"
               name="app_name"
               value="<?= htmlspecialchars($defaultValues['app_name']) ?>"
               required>
        <div class="form-text">Este nombre aparecerá en la interfaz y notificaciones</div>
    </div>

    <div class="mb-3">
        <label for="app_url" class="form-label">
            URL de la Aplicación <span class="text-danger">*</span>
        </label>
        <input type="url"
               class="form-control"
               id="app_url"
               name="app_url"
               value="<?= htmlspecialchars($defaultValues['app_url']) ?>"
               required>
        <div class="form-text">URL completa sin la barra final (ej: https://ejemplo.com)</div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="app_env" class="form-label">
                    Entorno <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="app_env" name="app_env" required>
                    <option value="production" <?= $defaultValues['app_env'] === 'production' ? 'selected' : '' ?>>
                        Producción
                    </option>
                    <option value="development" <?= $defaultValues['app_env'] === 'development' ? 'selected' : '' ?>>
                        Desarrollo
                    </option>
                    <option value="testing" <?= $defaultValues['app_env'] === 'testing' ? 'selected' : '' ?>>
                        Pruebas
                    </option>
                </select>
                <div class="form-text">Seleccione "Producción" para sitios en vivo</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="app_debug" class="form-label">
                    Modo Debug
                </label>
                <select class="form-select" id="app_debug" name="app_debug">
                    <option value="false" <?= $defaultValues['app_debug'] === 'false' ? 'selected' : '' ?>>
                        Desactivado (Recomendado)
                    </option>
                    <option value="true" <?= $defaultValues['app_debug'] === 'true' ? 'selected' : '' ?>>
                        Activado
                    </option>
                </select>
                <div class="form-text">Desactive en producción</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="app_timezone" class="form-label">
            Zona Horaria <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="app_timezone" name="app_timezone" required>
            <?php foreach ($timezones as $tz): ?>
                <option value="<?= $tz ?>" <?= $defaultValues['app_timezone'] === $tz ? 'selected' : '' ?>>
                    <?= $tz ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <h5 class="mb-3 mt-4"><i class="bi bi-shield-lock me-2"></i>Configuración de Seguridad</h5>

    <div class="mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   id="session_secure"
                   name="session_secure"
                   value="true"
                   <?= $defaultValues['session_secure'] === 'true' ? 'checked' : '' ?>>
            <label class="form-check-label" for="session_secure">
                Usar cookies seguras (HTTPS)
            </label>
            <div class="form-text">
                Active solo si su sitio usa HTTPS. Actualmente:
                <strong><?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS activo' : 'HTTP' ?></strong>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Nota:</strong> Puede modificar estas opciones más tarde editando el archivo <code>.env</code>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=2" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i> Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            Siguiente <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>
