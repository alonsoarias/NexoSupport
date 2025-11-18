<?php
/**
 * Stage: Database Configuration
 */

$progress = 33;
$error = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbdriver = $_POST['dbdriver'] ?? 'mysql';
    $dbhost = $_POST['dbhost'] ?? '';
    $dbname = $_POST['dbname'] ?? '';
    $dbuser = $_POST['dbuser'] ?? '';
    $dbpass = $_POST['dbpass'] ?? '';
    $dbprefix = $_POST['dbprefix'] ?? 'nxs_';

    // Intentar conectar
    try {
        $dsn = $dbdriver === 'mysql'
            ? "mysql:host=$dbhost;charset=utf8mb4"
            : "pgsql:host=$dbhost";

        $pdo = new PDO($dsn, $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si la BD existe, si no, crearla
        if ($dbdriver === 'mysql') {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");
        }

        // Guardar configuración en .env
        $envContent = "# NexoSupport Environment Configuration\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_URL=http://localhost\n";
        $envContent .= "\n";
        $envContent .= "DB_DRIVER=$dbdriver\n";
        $envContent .= "DB_HOST=$dbhost\n";
        $envContent .= "DB_DATABASE=$dbname\n";
        $envContent .= "DB_USERNAME=$dbuser\n";
        $envContent .= "DB_PASSWORD=$dbpass\n";
        $envContent .= "DB_PREFIX=$dbprefix\n";
        $envContent .= "\n";
        $envContent .= "INSTALLED=false\n";

        file_put_contents(BASE_DIR . '/.env', $envContent);

        // Guardar en sesión para siguiente stage
        session_start();
        $_SESSION['install_db'] = [
            'driver' => $dbdriver,
            'host' => $dbhost,
            'name' => $dbname,
            'user' => $dbuser,
            'pass' => $dbpass,
            'prefix' => $dbprefix
        ];

        // Redirigir al siguiente paso
        header('Location: /install?stage=install_db');
        exit;

    } catch (PDOException $e) {
        $error = 'Error de conexión: ' . $e->getMessage();
    }
}

// Valores por defecto
$dbdriver = $_POST['dbdriver'] ?? 'mysql';
$dbhost = $_POST['dbhost'] ?? 'localhost';
$dbname = $_POST['dbname'] ?? 'nexosupport';
$dbuser = $_POST['dbuser'] ?? 'root';
$dbpass = $_POST['dbpass'] ?? '';
$dbprefix = $_POST['dbprefix'] ?? 'nxs_';
?>

<h1>Configuración de Base de Datos</h1>
<h2>Configure la conexión a la base de datos</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress . '%'; ?>"></div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="dbdriver">Driver de Base de Datos</label>
        <select name="dbdriver" id="dbdriver">
            <option value="mysql" <?php echo $dbdriver === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
            <option value="pgsql" <?php echo $dbdriver === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
        </select>
    </div>

    <div class="form-group">
        <label for="dbhost">Host</label>
        <input type="text" name="dbhost" id="dbhost" value="<?php echo htmlspecialchars($dbhost); ?>" required>
    </div>

    <div class="form-group">
        <label for="dbname">Nombre de la Base de Datos</label>
        <input type="text" name="dbname" id="dbname" value="<?php echo htmlspecialchars($dbname); ?>" required>
    </div>

    <div class="form-group">
        <label for="dbuser">Usuario</label>
        <input type="text" name="dbuser" id="dbuser" value="<?php echo htmlspecialchars($dbuser); ?>" required>
    </div>

    <div class="form-group">
        <label for="dbpass">Contraseña</label>
        <input type="password" name="dbpass" id="dbpass" value="<?php echo htmlspecialchars($dbpass); ?>">
    </div>

    <div class="form-group">
        <label for="dbprefix">Prefijo de Tablas</label>
        <input type="text" name="dbprefix" id="dbprefix" value="<?php echo htmlspecialchars($dbprefix); ?>" required>
        <small style="color: #666;">Prefijo para todas las tablas (ej: nxs_)</small>
    </div>

    <div class="actions">
        <a href="/install?stage=requirements" class="btn btn-secondary">Atrás</a>
        <button type="submit" class="btn">Probar Conexión y Continuar</button>
    </div>
</form>
