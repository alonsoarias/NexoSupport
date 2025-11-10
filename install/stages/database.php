<?php
/**
 * Stage 2: Database Configuration
 */

use ISER\Core\Database\DatabaseDriverDetector;

// Obtener drivers disponibles
$availableDrivers = DatabaseDriverDetector::getAvailableDrivers();

// Si no hay drivers disponibles, mostrar error
if (empty($availableDrivers)) {
    ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Error: No hay drivers de base de datos disponibles</strong>
        <p class="mb-0 mt-2">Su instalación de PHP no tiene extensiones PDO habilitadas. Por favor instale al menos una de las siguientes extensiones:</p>
        <ul class="mb-0 mt-2">
            <li>pdo_mysql (para MySQL/MariaDB)</li>
            <li>pdo_pgsql (para PostgreSQL)</li>
            <li>pdo_sqlite (para SQLite)</li>
        </ul>
    </div>
    <form method="POST">
        <input type="hidden" name="stage" value="<?= STAGE_DATABASE ?>">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
    </form>
    <?php
    return;
}

// Obtener valores guardados o por defecto
$db_driver = $_SESSION['db_driver'] ?? DatabaseDriverDetector::getRecommendedDriver();
$db_host = $_SESSION['db_host'] ?? 'localhost';
$db_port = $_SESSION['db_port'] ?? ($availableDrivers[$db_driver]['default_port'] ?? 3306);
$db_name = $_SESSION['db_name'] ?? 'nexosupport';
$db_user = $_SESSION['db_user'] ?? 'root';
$db_pass = $_SESSION['db_pass'] ?? '';
$db_prefix = $_SESSION['db_prefix'] ?? '';
?>

<h3 class="mb-4">Configuración de Base de Datos</h3>

<p class="text-muted">Seleccione el motor de base de datos e ingrese los datos de conexión.</p>

<form method="POST" id="dbForm">
    <input type="hidden" name="stage" value="<?= STAGE_DATABASE ?>">

    <!-- Selección de Motor de Base de Datos -->
    <div class="mb-4">
        <label class="form-label"><i class="bi bi-hdd-stack"></i> Motor de Base de Datos</label>
        <div class="row g-3">
            <?php foreach ($availableDrivers as $driverKey => $driverInfo): ?>
                <div class="col-md-4">
                    <div class="card h-100 driver-card <?= $driverKey === $db_driver ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="db_driver"
                                    id="driver_<?= $driverKey ?>"
                                    value="<?= $driverKey ?>"
                                    data-port="<?= $driverInfo['default_port'] ?>"
                                    <?= $driverKey === $db_driver ? 'checked' : '' ?>
                                    required>
                                <label class="form-check-label w-100" for="driver_<?= $driverKey ?>">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-<?= $driverInfo['icon'] ?> text-<?= $driverInfo['color'] ?> fs-4 me-2"></i>
                                        <strong><?= htmlspecialchars($driverInfo['name']) ?></strong>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($driverInfo['description']) ?></small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Campos de conexión (dinámicos según el driver) -->
    <div id="connectionFields">
        <div class="mb-3" id="field_host">
            <label class="form-label">Host</label>
            <input type="text" name="db_host" id="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3" id="field_port">
                <label class="form-label">Puerto</label>
                <input type="number" name="db_port" id="db_port" class="form-control" value="<?= $db_port ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de BD</label>
                <input type="text" name="db_name" id="db_name" class="form-control" value="<?= htmlspecialchars($db_name) ?>" required>
            </div>
        </div>

        <div class="mb-3" id="field_user">
            <label class="form-label">Usuario</label>
            <input type="text" name="db_user" id="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>">
        </div>

        <div class="mb-3" id="field_pass">
            <label class="form-label">Contraseña</label>
            <input type="password" name="db_pass" id="db_pass" class="form-control" value="<?= htmlspecialchars($db_pass) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Prefijo de tablas (opcional)</label>
            <input type="text" name="db_prefix" class="form-control" value="<?= htmlspecialchars($db_prefix) ?>" placeholder="ej: ns_">
            <small class="text-muted">Dejar vacío si no desea prefijo</small>
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
// Actualizar campos según el driver seleccionado
document.querySelectorAll('input[name="db_driver"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const driver = this.value;
        const port = this.dataset.port;

        // Actualizar puerto por defecto
        if (port) {
            document.getElementById('db_port').value = port;
        }

        // Mostrar/ocultar campos según el driver
        if (driver === 'sqlite') {
            // SQLite: ocultar host, port, user, pass
            document.getElementById('field_host').style.display = 'none';
            document.getElementById('field_port').style.display = 'none';
            document.getElementById('field_user').style.display = 'none';
            document.getElementById('field_pass').style.display = 'none';

            // SQLite: cambiar label de nombre de BD
            document.querySelector('label[for="db_name"]').textContent = 'Ruta del archivo SQLite';
            document.getElementById('db_name').placeholder = 'database/nexosupport.sqlite';

            // Quitar required de campos ocultos
            document.getElementById('db_host').removeAttribute('required');
            document.getElementById('db_port').removeAttribute('required');
            document.getElementById('db_user').removeAttribute('required');
        } else {
            // MySQL/PostgreSQL: mostrar todos los campos
            document.getElementById('field_host').style.display = 'block';
            document.getElementById('field_port').style.display = 'block';
            document.getElementById('field_user').style.display = 'block';
            document.getElementById('field_pass').style.display = 'block';

            document.querySelector('label[for="db_name"]').textContent = 'Nombre de BD';
            document.getElementById('db_name').placeholder = '';

            // Agregar required a campos visibles
            document.getElementById('db_host').setAttribute('required', 'required');
            document.getElementById('db_port').setAttribute('required', 'required');
            document.getElementById('db_user').setAttribute('required', 'required');
        }

        // Actualizar border de las cards
        document.querySelectorAll('.driver-card').forEach(card => {
            card.classList.remove('border-primary');
        });
        this.closest('.driver-card').classList.add('border-primary');
    });
});

// Inicializar estado al cargar
const selectedDriver = document.querySelector('input[name="db_driver"]:checked');
if (selectedDriver) {
    selectedDriver.dispatchEvent(new Event('change'));
}
</script>

<style>
.driver-card {
    cursor: pointer;
    transition: all 0.2s;
}
.driver-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
.driver-card.border-primary {
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}
</style>
