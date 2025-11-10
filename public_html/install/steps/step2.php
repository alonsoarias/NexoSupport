<?php
/**
 * Paso 2: Configuración de Base de Datos
 */

$defaultValues = $_SESSION['db_config'] ?? [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'iser_auth',
    'db_user' => 'root',
    'db_pass' => '',
    'db_prefix' => 'iser_'
];
?>

<div class="mb-4">
    <p class="lead">
        Configure la conexión a la base de datos MySQL/MariaDB.
    </p>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Nota:</strong> Asegúrese de que el usuario de base de datos tenga permisos para crear bases de datos,
        o cree la base de datos manualmente antes de continuar.
    </div>
</div>

<form method="POST" action="?step=2" id="db-form">
    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="db_host" class="form-label">
                    <i class="bi bi-hdd-network me-1"></i> Host de Base de Datos
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="db_host"
                       name="db_host"
                       value="<?= htmlspecialchars($defaultValues['db_host']) ?>"
                       required>
                <div class="form-text">
                    Generalmente "localhost" o "127.0.0.1"
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <label for="db_port" class="form-label">
                    Puerto <span class="text-danger">*</span>
                </label>
                <input type="number"
                       class="form-control"
                       id="db_port"
                       name="db_port"
                       value="<?= htmlspecialchars($defaultValues['db_port']) ?>"
                       required>
                <div class="form-text">Por defecto: 3306</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="db_name" class="form-label">
            <i class="bi bi-database me-1"></i> Nombre de Base de Datos
            <span class="text-danger">*</span>
        </label>
        <input type="text"
               class="form-control"
               id="db_name"
               name="db_name"
               value="<?= htmlspecialchars($defaultValues['db_name']) ?>"
               pattern="[a-zA-Z0-9_]+"
               required>
        <div class="form-text">
            Solo letras, números y guiones bajos. Se creará si no existe.
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="db_user" class="form-label">
                    <i class="bi bi-person me-1"></i> Usuario de Base de Datos
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="db_user"
                       name="db_user"
                       value="<?= htmlspecialchars($defaultValues['db_user']) ?>"
                       required>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="db_pass" class="form-label">
                    <i class="bi bi-key me-1"></i> Contraseña de Base de Datos
                </label>
                <input type="password"
                       class="form-control"
                       id="db_pass"
                       name="db_pass"
                       value="<?= htmlspecialchars($defaultValues['db_pass']) ?>">
                <div class="form-text">Dejar en blanco si no tiene contraseña</div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label for="db_prefix" class="form-label">
            <i class="bi bi-tag me-1"></i> Prefijo de Tablas
        </label>
        <input type="text"
               class="form-control"
               id="db_prefix"
               name="db_prefix"
               value="<?= htmlspecialchars($defaultValues['db_prefix']) ?>"
               pattern="[a-z0-9_]*">
        <div class="form-text">
            Prefijo opcional para todas las tablas (ej: "iser_"). Útil si comparte la base de datos con otras aplicaciones.
        </div>
    </div>

    <div class="card bg-light border-0 mb-4">
        <div class="card-body">
            <h6 class="card-title">
                <i class="bi bi-shield-check me-2"></i>Prueba de Conexión
            </h6>
            <p class="card-text text-muted mb-3">
                El instalador intentará conectarse a la base de datos con las credenciales proporcionadas.
            </p>
            <button type="button" class="btn btn-outline-primary" id="test-connection">
                <i class="bi bi-lightning me-2"></i>Probar Conexión
            </button>
            <div id="connection-result" class="mt-3"></div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=1" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i> Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            Siguiente <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<script>
document.getElementById('test-connection').addEventListener('click', async function() {
    const btn = this;
    const resultDiv = document.getElementById('connection-result');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Probando...';

    const formData = new FormData(document.getElementById('db-form'));
    formData.append('action', 'test_connection');

    try {
        const response = await fetch('test-connection.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>¡Conexión exitosa!</strong>
                    ${result.message || 'Se pudo conectar a la base de datos correctamente.'}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong>Error de conexión:</strong> ${result.message}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning me-2"></i>Probar Conexión';
    }
});
</script>
