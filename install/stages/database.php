<?php
/**
 * Stage 2: Database Configuration
 */

$db_host = $_SESSION['db_host'] ?? 'localhost';
$db_port = $_SESSION['db_port'] ?? 3306;
$db_name = $_SESSION['db_name'] ?? 'nexosupport';
$db_user = $_SESSION['db_user'] ?? 'root';
$db_pass = $_SESSION['db_pass'] ?? '';
$db_prefix = $_SESSION['db_prefix'] ?? '';
?>

<h3 class="mb-4">Configuración de Base de Datos</h3>

<p class="text-muted">Ingrese los datos de conexión a su base de datos MySQL/MariaDB.</p>

<form method="POST">
    <input type="hidden" name="stage" value="<?= STAGE_DATABASE ?>">

    <div class="mb-3">
        <label class="form-label">Host</label>
        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>" required>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Puerto</label>
            <input type="number" name="db_port" class="form-control" value="<?= $db_port ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Nombre de BD</label>
            <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($db_name) ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($db_pass) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Prefijo de tablas (opcional)</label>
        <input type="text" name="db_prefix" class="form-control" value="<?= htmlspecialchars($db_prefix) ?>" placeholder="ej: iser_">
        <small class="text-muted">Dejar vacío si no desea prefijo</small>
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
