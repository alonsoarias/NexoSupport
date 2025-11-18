<?php
/**
 * Sistema de Recuperación de Instalación
 *
 * Esta página se muestra cuando se detecta que existe una base de datos
 * con datos del sistema, pero faltan archivos de configuración (.env, .installed)
 *
 * Similar al sistema de recuperación de Moodle.
 *
 * @package NexoSupport
 */

if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
    define('BASE_DIR', dirname(__DIR__));
}

$error = '';
$success = false;

// Procesar formulario de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_prefix = $_POST['db_prefix'] ?? 'nxs_';
    $app_url = $_POST['app_url'] ?? 'http://localhost';

    try {
        // Intentar conectar a la base de datos
        $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar que existe la tabla de configuración
        $stmt = $pdo->query("SHOW TABLES LIKE '{$db_prefix}config'");
        $configTableExists = $stmt->rowCount() > 0;

        if (!$configTableExists) {
            throw new Exception('La base de datos no contiene una instalación válida de NexoSupport. Por favor, ejecute una instalación nueva.');
        }

        // Verificar que existe al menos un usuario
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$db_prefix}users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($userCount == 0) {
            throw new Exception('La base de datos no contiene usuarios. Por favor, ejecute una instalación nueva.');
        }

        // Regenerar archivo .env
        $envContent = <<<ENV
# =========================================
# NEXOSUPPORT - CONFIGURACIÓN REGENERADA
# =========================================
# Este archivo fue regenerado automáticamente por el sistema de recuperación

# -----------------------------------------
# ENVIRONMENT CONFIGURATION
# -----------------------------------------
APP_ENV=production
APP_DEBUG=false
APP_NAME="NexoSupport"
APP_URL={$app_url}
APP_TIMEZONE=America/Bogota

# -----------------------------------------
# DATABASE CONFIGURATION
# -----------------------------------------
DB_DRIVER=mysql
DB_HOST={$db_host}
DB_PORT=3306
DB_DATABASE={$db_name}
DB_USERNAME={$db_user}
DB_PASSWORD={$db_pass}
DB_PREFIX={$db_prefix}

# -----------------------------------------
# INSTALLATION
# -----------------------------------------
INSTALLED=true

ENV;

        // Guardar archivo .env
        $envPath = BASE_DIR . '/.env';
        if (file_put_contents($envPath, $envContent) === false) {
            throw new Exception('No se pudo crear el archivo .env. Verifique los permisos de escritura.');
        }

        // Crear archivo .installed
        $installedPath = BASE_DIR . '/.installed';
        $installedContent = date('Y-m-d H:i:s') . " - Recuperado automáticamente\n";
        if (file_put_contents($installedPath, $installedContent) === false) {
            throw new Exception('No se pudo crear el archivo .installed. Verifique los permisos de escritura.');
        }

        $success = true;

    } catch (PDOException $e) {
        $error = 'Error de conexión a la base de datos: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Intentar detectar configuración desde .env.example o valores previos
$default_host = 'localhost';
$default_name = 'nexosupport';
$default_user = 'root';
$default_prefix = 'nxs_';
$default_url = 'http://localhost';

// Intentar leer desde .env.example
if (file_exists(BASE_DIR . '/.env.example')) {
    $envExample = file_get_contents(BASE_DIR . '/.env.example');
    if (preg_match('/DB_HOST=(.+)/', $envExample, $matches)) {
        $default_host = trim($matches[1]);
    }
    if (preg_match('/DB_DATABASE=(.+)/', $envExample, $matches)) {
        $default_name = trim($matches[1]);
    }
    if (preg_match('/DB_USERNAME=(.+)/', $envExample, $matches)) {
        $default_user = trim($matches[1]);
    }
    if (preg_match('/DB_PREFIX=(.+)/', $envExample, $matches)) {
        $default_prefix = trim($matches[1]);
    }
    if (preg_match('/APP_URL=(.+)/', $envExample, $matches)) {
        $default_url = trim($matches[1]);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Instalación - NexoSupport</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #2d5016 0%, #4a7c23 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #2d5016;
            margin-bottom: 10px;
            font-size: 28px;
        }

        h2 {
            color: #4a7c23;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: normal;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #2d5016;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #2d5016 0%, #4a7c23 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box h3 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-box p {
            color: #0d47a1;
            line-height: 1.6;
        }

        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <h1>✓ Recuperación Exitosa</h1>
            <h2>Los archivos de configuración han sido regenerados</h2>

            <div class="alert alert-success">
                <strong>¡Éxito!</strong> Su instalación ha sido recuperada correctamente.
                Los archivos <code>.env</code> y <code>.installed</code> han sido regenerados.
            </div>

            <div class="info-box">
                <h3>Próximos pasos:</h3>
                <p>
                    1. Verifique que puede acceder al sistema<br>
                    2. Si se solicitó, ejecute las actualizaciones pendientes<br>
                    3. Revise la configuración del sistema en el panel de administración
                </p>
            </div>

            <div class="actions">
                <a href="/" class="btn">Ir al Sistema</a>
            </div>

        <?php else: ?>
            <h1>🔧 Recuperación de Instalación</h1>
            <h2>Se detectó una instalación existente en la base de datos</h2>

            <div class="alert alert-warning">
                <strong>Atención:</strong> El sistema detectó que faltan archivos de configuración,
                pero existe una instalación válida en la base de datos. Complete el formulario
                para regenerar los archivos necesarios.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>¿Qué hace esta herramienta?</h3>
                <p>
                    Esta herramienta regenera los archivos <code>.env</code> y <code>.installed</code>
                    que son necesarios para que el sistema funcione, sin modificar los datos existentes
                    en la base de datos. Es similar al sistema de recuperación de Moodle.
                </p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Host de Base de Datos:</label>
                    <input type="text" id="db_host" name="db_host"
                           value="<?php echo htmlspecialchars($default_host); ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_name">Nombre de Base de Datos:</label>
                    <input type="text" id="db_name" name="db_name"
                           value="<?php echo htmlspecialchars($default_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_user">Usuario de Base de Datos:</label>
                    <input type="text" id="db_user" name="db_user"
                           value="<?php echo htmlspecialchars($default_user); ?>" required>
                </div>

                <div class="form-group">
                    <label for="db_pass">Contraseña de Base de Datos:</label>
                    <input type="password" id="db_pass" name="db_pass" required>
                </div>

                <div class="form-group">
                    <label for="db_prefix">Prefijo de Tablas:</label>
                    <input type="text" id="db_prefix" name="db_prefix"
                           value="<?php echo htmlspecialchars($default_prefix); ?>" required>
                </div>

                <div class="form-group">
                    <label for="app_url">URL de la Aplicación:</label>
                    <input type="text" id="app_url" name="app_url"
                           value="<?php echo htmlspecialchars($default_url); ?>" required>
                </div>

                <div class="actions">
                    <a href="/install" class="btn btn-secondary">Instalar desde Cero</a>
                    <button type="submit" class="btn">Recuperar Instalación</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
