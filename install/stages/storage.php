<?php
/**
 * Stage 9: Cache and Storage Configuration
 */

// Obtener valores guardados o por defecto
$cache_driver = $_SESSION['cache_driver'] ?? 'file';
$cache_ttl = $_SESSION['cache_ttl'] ?? 3600;
$redis_host = $_SESSION['cache_redis_host'] ?? '127.0.0.1';
$redis_port = $_SESSION['cache_redis_port'] ?? 6379;
$redis_password = $_SESSION['cache_redis_password'] ?? '';
$memcached_host = $_SESSION['cache_memcached_host'] ?? '127.0.0.1';
$memcached_port = $_SESSION['cache_memcached_port'] ?? 11211;
$avatar_storage_path = $_SESSION['storage_avatar_path'] ?? 'public/uploads/avatars';
$avatar_max_size = $_SESSION['storage_avatar_max_size'] ?? 2;
$avatar_allowed_types = $_SESSION['storage_avatar_allowed_types'] ?? 'jpg,jpeg,png,gif';
$upload_max_size = $_SESSION['storage_upload_max_size'] ?? 10;
$upload_allowed_extensions = $_SESSION['storage_upload_extensions'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip';
?>

<h3 class="mb-4"><i class="bi bi-hdd-stack"></i> Configuración de Caché y Almacenamiento</h3>

<p class="text-muted">Configure el sistema de caché y las opciones de almacenamiento de archivos.</p>

<form method="POST" id="storageForm">
    <input type="hidden" name="stage" value="<?= STAGE_STORAGE ?>">

    <!-- Cache Driver Selection -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-lightning"></i> Sistema de Caché</h5>

        <label class="form-label">Driver de Caché *</label>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100 cache-driver-card <?= $cache_driver === 'file' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cache_driver"
                                   id="cache_file" value="file" <?= $cache_driver === 'file' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="cache_file">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark text-primary fs-4 me-2"></i>
                                    <strong>Archivo</strong>
                                </div>
                                <small class="text-muted">Caché en archivos locales (predeterminado)</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 cache-driver-card <?= $cache_driver === 'redis' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cache_driver"
                                   id="cache_redis" value="redis" <?= $cache_driver === 'redis' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="cache_redis">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-server text-danger fs-4 me-2"></i>
                                    <strong>Redis</strong>
                                </div>
                                <small class="text-muted">Alto rendimiento, recomendado para producción</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 cache-driver-card <?= $cache_driver === 'memcached' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cache_driver"
                                   id="cache_memcached" value="memcached" <?= $cache_driver === 'memcached' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="cache_memcached">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-memory text-success fs-4 me-2"></i>
                                    <strong>Memcached</strong>
                                </div>
                                <small class="text-muted">Sistema de caché distribuido</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label">TTL de Caché (segundos) *</label>
            <input type="number" name="cache_ttl" class="form-control"
                   value="<?= $cache_ttl ?>" min="60" max="86400" required>
            <small class="text-muted">Tiempo de vida predeterminado de los elementos en caché</small>
        </div>
    </div>

    <!-- Redis Settings -->
    <div id="redisSettings" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-server"></i> Configuración Redis</h5>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Host de Redis *</label>
                <input type="text" name="cache_redis_host" id="redis_host" class="form-control"
                       value="<?= htmlspecialchars($redis_host) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Puerto *</label>
                <input type="number" name="cache_redis_port" id="redis_port" class="form-control"
                       value="<?= $redis_port ?>" min="1" max="65535">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña (opcional)</label>
            <input type="password" name="cache_redis_password" class="form-control"
                   value="<?= htmlspecialchars($redis_password) ?>">
            <small class="text-muted">Dejar vacío si Redis no requiere autenticación</small>
        </div>
    </div>

    <!-- Memcached Settings -->
    <div id="memcachedSettings" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-memory"></i> Configuración Memcached</h5>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Host de Memcached *</label>
                <input type="text" name="cache_memcached_host" id="memcached_host" class="form-control"
                       value="<?= htmlspecialchars($memcached_host) ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Puerto *</label>
                <input type="number" name="cache_memcached_port" id="memcached_port" class="form-control"
                       value="<?= $memcached_port ?>" min="1" max="65535">
            </div>
        </div>
    </div>

    <!-- Avatar Storage -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-person-circle"></i> Almacenamiento de Avatares</h5>

        <div class="mb-3">
            <label class="form-label">Ruta de Almacenamiento *</label>
            <input type="text" name="storage_avatar_path" class="form-control"
                   value="<?= htmlspecialchars($avatar_storage_path) ?>" required>
            <small class="text-muted">Ruta relativa al directorio raíz del sistema</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Tamaño Máximo (MB) *</label>
            <input type="number" name="storage_avatar_max_size" class="form-control"
                   value="<?= $avatar_max_size ?>" min="0.5" max="10" step="0.5" required>
            <small class="text-muted">Tamaño máximo permitido para avatares</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipos de Archivo Permitidos *</label>
            <input type="text" name="storage_avatar_allowed_types" class="form-control"
                   value="<?= htmlspecialchars($avatar_allowed_types) ?>" required>
            <small class="text-muted">Extensiones separadas por comas (ej: jpg,png,gif)</small>
        </div>
    </div>

    <!-- File Upload Storage -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-cloud-upload"></i> Carga de Archivos</h5>

        <div class="mb-3">
            <label class="form-label">Tamaño Máximo de Carga (MB) *</label>
            <input type="number" name="storage_upload_max_size" class="form-control"
                   value="<?= $upload_max_size ?>" min="1" max="100" required>
            <small class="text-muted">Tamaño máximo para archivos adjuntos en tickets</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Extensiones Permitidas *</label>
            <textarea name="storage_upload_extensions" class="form-control" rows="2" required><?= htmlspecialchars($upload_allowed_extensions) ?></textarea>
            <small class="text-muted">Extensiones de archivo permitidas, separadas por comas</small>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Nota:</strong> Asegúrese de que el límite de carga de PHP (php.ini) sea igual o mayor al configurado aquí.
            <ul class="mb-0 mt-2">
                <li>upload_max_filesize</li>
                <li>post_max_size</li>
            </ul>
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
// Actualizar campos según el driver de caché seleccionado
document.querySelectorAll('input[name="cache_driver"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const driver = this.value;

        // Ocultar todos los campos específicos
        document.getElementById('redisSettings').style.display = 'none';
        document.getElementById('memcachedSettings').style.display = 'none';

        // Remover required de todos los campos específicos
        document.querySelectorAll('#redisSettings input, #memcachedSettings input').forEach(input => {
            if (input.type !== 'password') {
                input.removeAttribute('required');
            }
        });

        // Mostrar campos según el driver
        if (driver === 'redis') {
            document.getElementById('redisSettings').style.display = 'block';
            document.getElementById('redis_host').setAttribute('required', 'required');
            document.getElementById('redis_port').setAttribute('required', 'required');
        } else if (driver === 'memcached') {
            document.getElementById('memcachedSettings').style.display = 'block';
            document.getElementById('memcached_host').setAttribute('required', 'required');
            document.getElementById('memcached_port').setAttribute('required', 'required');
        }

        // Actualizar border de las cards
        document.querySelectorAll('.cache-driver-card').forEach(card => {
            card.classList.remove('border-primary');
        });
        this.closest('.cache-driver-card').classList.add('border-primary');
    });
});

// Inicializar estado al cargar
const selectedDriver = document.querySelector('input[name="cache_driver"]:checked');
if (selectedDriver) {
    selectedDriver.dispatchEvent(new Event('change'));
}
</script>

<style>
.cache-driver-card {
    cursor: pointer;
    transition: all 0.2s;
}

.cache-driver-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.cache-driver-card.border-primary {
    box-shadow: 0 0 0 0.2rem rgba(27, 158, 136, 0.25);
    border-color: var(--iser-green) !important;
}

.alert-info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.border-bottom {
    border-color: var(--border-color) !important;
}
</style>
