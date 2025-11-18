<?php
/**
 * Stage: Database Configuration
 */

$progress = 50;
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
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $app_url = $protocol . '://' . $host;

        $envContent = "# NexoSupport Environment Configuration\n";
        $envContent .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $envContent .= "# Application Settings\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_URL=$app_url\n";
        $envContent .= "\n";
        $envContent .= "# Database Configuration\n";
        $envContent .= "DB_DRIVER=$dbdriver\n";
        $envContent .= "DB_HOST=$dbhost\n";
        $envContent .= "DB_DATABASE=$dbname\n";
        $envContent .= "DB_USERNAME=$dbuser\n";
        $envContent .= "DB_PASSWORD=$dbpass\n";
        $envContent .= "DB_PREFIX=$dbprefix\n";
        $envContent .= "\n";
        $envContent .= "# Installation Status\n";
        $envContent .= "INSTALLED=false\n";
        $envContent .= "\n";
        $envContent .= "# Cache Settings\n";
        $envContent .= "CACHE_DRIVER=file\n";
        $envContent .= "\n";
        $envContent .= "# Session Settings\n";
        $envContent .= "SESSION_LIFETIME=120\n";
        $envContent .= "SESSION_NAME=nexosupport_session\n";

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
    <div class="progress-bar" style="width: <?php echo $progress . '%'; ?>"></div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>Información</strong><br>
    El archivo .env se generará automáticamente con la configuración proporcionada.
</div>

<form method="POST">
    <div class="form-group">
        <label for="dbdriver"><i class="fas fa-server icon"></i>Driver de Base de Datos</label>
        <select name="dbdriver" id="dbdriver">
            <option value="mysql" <?php echo $dbdriver === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
            <option value="pgsql" <?php echo $dbdriver === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
        </select>
    </div>

    <div class="form-group">
        <label for="dbhost"><i class="fas fa-network-wired icon"></i>Host</label>
        <input type="text" name="dbhost" id="dbhost" value="<?php echo htmlspecialchars($dbhost); ?>" required placeholder="localhost">
    </div>

    <div class="form-group">
        <label for="dbname"><i class="fas fa-database icon"></i>Nombre de la Base de Datos</label>
        <input type="text" name="dbname" id="dbname" value="<?php echo htmlspecialchars($dbname); ?>" required placeholder="nexosupport">
    </div>

    <div class="form-group">
        <label for="dbuser"><i class="fas fa-user icon"></i>Usuario</label>
        <input type="text" name="dbuser" id="dbuser" value="<?php echo htmlspecialchars($dbuser); ?>" required placeholder="root">
    </div>

    <div class="form-group">
        <label for="dbpass"><i class="fas fa-lock icon"></i>Contraseña</label>
        <input type="password" name="dbpass" id="dbpass" value="<?php echo htmlspecialchars($dbpass); ?>" placeholder="••••••••">
    </div>

    <div class="form-group">
        <label for="dbprefix"><i class="fas fa-tag icon"></i>Prefijo de Tablas</label>
        <input type="text" name="dbprefix" id="dbprefix" value="<?php echo htmlspecialchars($dbprefix); ?>" required placeholder="nxs_">
        <small style="color: #666;"><i class="fas fa-info-circle"></i> Prefijo para todas las tablas (ej: nxs_)</small>
    </div>

    <div class="actions">
        <a href="/install?stage=requirements" class="btn btn-secondary"><i class="fas fa-arrow-left icon"></i>Atrás</a>
        <button type="submit" class="btn"><i class="fas fa-plug icon"></i>Probar Conexión y Continuar</button>
    </div>
</form>
