<?php
/**
 * Stage: Database Configuration - Refactorizado
 */

$progress = 50;

// Valores por defecto
$dbdriver = $_POST['dbdriver'] ?? 'mysql';
$dbhost = $_POST['dbhost'] ?? 'localhost';
$dbname = $_POST['dbname'] ?? 'nexosupport';
$dbuser = $_POST['dbuser'] ?? 'root';
$dbpass = $_POST['dbpass'] ?? '';
$dbprefix = $_POST['dbprefix'] ?? 'nxs_';

// Mostrar error si existe (desde action_result)
$error = null;
if (isset($action_result) && !$action_result['success']) {
    $error = $action_result['error'];
}
?>

<div class="stage-indicator">
    <i class="fas fa-database icon"></i>
    <div class="text">
        <div class="step-number">Paso 3 de 6</div>
        <strong>Configuración de Base de Datos</strong>
    </div>
</div>

<h1><i class="fas fa-database icon"></i>Configuración de Base de Datos</h1>
<h2>Configure la conexión a la base de datos</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>Información</strong><br>
    El archivo .env se generará automáticamente con la configuración proporcionada.
    La base de datos se creará si no existe (solo MySQL).
</div>

<form method="POST" action="/install?stage=database">
    <input type="hidden" name="action" value="save_database">

    <div class="form-group">
        <label for="dbdriver"><i class="fas fa-server icon"></i>Driver de Base de Datos</label>
        <select name="dbdriver" id="dbdriver" required>
            <option value="mysql" <?php echo $dbdriver === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
            <option value="pgsql" <?php echo $dbdriver === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
        </select>
    </div>

    <div class="form-group">
        <label for="dbhost"><i class="fas fa-network-wired icon"></i>Host</label>
        <input type="text" name="dbhost" id="dbhost" value="<?php echo htmlspecialchars($dbhost); ?>" required placeholder="localhost">
        <small><i class="fas fa-info-circle"></i> Generalmente es "localhost" o "127.0.0.1"</small>
    </div>

    <div class="form-group">
        <label for="dbname"><i class="fas fa-database icon"></i>Nombre de la Base de Datos</label>
        <input type="text" name="dbname" id="dbname" value="<?php echo htmlspecialchars($dbname); ?>" required placeholder="nexosupport" pattern="[a-zA-Z0-9_]+">
        <small><i class="fas fa-info-circle"></i> Solo letras, números y guiones bajos</small>
    </div>

    <div class="form-group">
        <label for="dbuser"><i class="fas fa-user icon"></i>Usuario</label>
        <input type="text" name="dbuser" id="dbuser" value="<?php echo htmlspecialchars($dbuser); ?>" required placeholder="root">
    </div>

    <div class="form-group">
        <label for="dbpass"><i class="fas fa-lock icon"></i>Contraseña</label>
        <input type="password" name="dbpass" id="dbpass" value="<?php echo htmlspecialchars($dbpass); ?>" placeholder="••••••••">
        <small><i class="fas fa-info-circle"></i> Dejar en blanco si no hay contraseña</small>
    </div>

    <div class="form-group">
        <label for="dbprefix"><i class="fas fa-tag icon"></i>Prefijo de Tablas</label>
        <input type="text" name="dbprefix" id="dbprefix" value="<?php echo htmlspecialchars($dbprefix); ?>" required placeholder="nxs_" pattern="[a-zA-Z0-9_]*">
        <small><i class="fas fa-info-circle"></i> Prefijo para todas las tablas (ej: nxs_). Solo letras, números y guiones bajos.</small>
    </div>

    <div class="actions">
        <a href="/install?stage=requirements" class="btn btn-secondary"><i class="fas fa-arrow-left icon"></i>Atrás</a>
        <button type="submit" class="btn"><i class="fas fa-plug icon"></i>Probar Conexión y Continuar</button>
    </div>
</form>
