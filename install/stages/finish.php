<?php
/**
 * Stage 5: Finish Installation
 */

// Create admin user and .env file
if (!isset($_SESSION['installation_complete'])) {
    try {
        // Connect to database
        $driver = $_SESSION['db_driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . BASE_DIR . '/' . $_SESSION['db_name'];
            $pdo = new PDO($dsn);
        } else {
            $config = [
                'host' => $_SESSION['db_host'],
                'port' => $_SESSION['db_port'],
                'database' => $_SESSION['db_name']
            ];
            $dsn = \ISER\Core\Database\DatabaseDriverDetector::buildDSN($driver, $config);
            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Hash password
        $passwordHash = password_hash($_SESSION['admin_password'], PASSWORD_ARGON2ID);
        $now = time();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO {$_SESSION['db_prefix']}users
            (username, email, password, first_name, last_name, status, email_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', 1, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_username'],
            $_SESSION['admin_email'],
            $passwordHash,
            $_SESSION['admin_firstname'],
            $_SESSION['admin_lastname'],
            $now,
            $now
        ]);
        $userId = $pdo->lastInsertId();

        // Assign admin role
        $stmt = $pdo->prepare("INSERT INTO {$_SESSION['db_prefix']}user_roles (user_id, role_id, assigned_at) VALUES (?, 1, ?)");
        $stmt->execute([$userId, $now]);

        // ====================================================================
        // CREAR ARCHIVO .env CON LA CONFIGURACIÓN
        // ====================================================================

        $envContent = <<<ENV
# ============================================================
# CONFIGURACIÓN DE NEXOSUPPORT
# Generado automáticamente: {$_SERVER['REQUEST_TIME']}
# ============================================================

# Base de Datos
DB_DRIVER={$_SESSION['db_driver']}
DB_HOST={$_SESSION['db_host']}
DB_PORT={$_SESSION['db_port']}
DB_NAME={$_SESSION['db_name']}
DB_USER={$_SESSION['db_user']}
DB_PASS={$_SESSION['db_pass']}
DB_PREFIX={$_SESSION['db_prefix']}
DB_CHARSET=utf8mb4

# Sistema
DEBUG_MODE=false
BASE_URL={$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}
TIMEZONE=America/Mexico_City
DEFAULT_LANG=es

# Seguridad (Cambiar SECRET_KEY en producción)
SECRET_KEY=

# Instalación
INSTALLED=true
INSTALL_DATE={$_SERVER['REQUEST_TIME']}

ENV;

        // Generar SECRET_KEY único
        $secretKey = bin2hex(random_bytes(32));
        $envContent = str_replace('SECRET_KEY=', "SECRET_KEY={$secretKey}", $envContent);

        // Escribir archivo .env
        $envPath = BASE_DIR . '/.env';
        if (file_put_contents($envPath, $envContent) === false) {
            throw new Exception("No se pudo crear el archivo .env. Verifique los permisos de escritura.");
        }

        // Proteger .env con permisos restrictivos (solo en Unix)
        if (function_exists('chmod')) {
            @chmod($envPath, 0600);
        }

        $_SESSION['installation_complete'] = true;
        $_SESSION['env_created'] = true;

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
}
?>

<div class="text-center">
    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
    <h3 class="mt-3">¡Instalación Completada!</h3>
    <p class="text-muted">NexoSupport ha sido instalado correctamente.</p>
</div>

<div class="alert alert-success mt-4">
    <h5><i class="bi bi-check-circle"></i> Archivos Creados</h5>
    <ul class="mb-0">
        <li><strong>.env</strong> - Archivo de configuración generado</li>
        <li><strong><?= count($pdo->query("SHOW TABLES LIKE '{$_SESSION['db_prefix']}%'")->fetchAll()) ?> tablas</strong> en la base de datos</li>
        <li><strong>Usuario administrador</strong> creado exitosamente</li>
    </ul>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="bi bi-key"></i> Credenciales de Acceso</h5>
    <p class="mb-0">
        <strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['admin_username']) ?><br>
        <strong>Email:</strong> <?= htmlspecialchars($_SESSION['admin_email']) ?>
    </p>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Importante - Seguridad:</strong>
    <ul class="mb-0 mt-2">
        <li>Elimine el archivo <code>public_html/install.php</code> para mayor seguridad</li>
        <li>El archivo <code>.env</code> debe tener permisos restrictivos (600)</li>
        <li>Asegúrese de que <code>.env</code> esté en su <code>.gitignore</code></li>
    </ul>
</div>

<div class="text-center mt-4">
    <a href="/" class="btn btn-primary btn-lg">
        <i class="bi bi-house"></i> Ir al Sistema
    </a>
</div>

<?php
// Clear session
session_destroy();
?>
