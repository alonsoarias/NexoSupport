<?php
/**
 * ISER Authentication System - Web Installer
 *
 * Instalador simple inspirado en Moodle
 *
 * @package Installer
 * @author ISER Desarrollo
 * @license Propietario
 */

session_start();

// Constants
define('INSTALL_DIR', __DIR__);
define('BASE_DIR', dirname(__DIR__));
define('ENV_FILE', BASE_DIR . '/.env');
define('SCHEMA_FILE', BASE_DIR . '/database/schema/schema.xml');

/**
 * Verificar si el sistema ya está instalado
 * Usa el mismo mecanismo que public_html/index.php
 */
function isAlreadyInstalled(): bool {
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    $envContent = file_get_contents(ENV_FILE);
    if ($envContent === false) {
        return false;
    }

    // Buscar INSTALLED=true en .env
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'INSTALLED=') === 0) {
            $value = trim(str_replace('INSTALLED=', '', $line));
            return ($value === 'true');
        }
    }

    return false;
}

// Check if already installed
if (isAlreadyInstalled() && !isset($_GET['reinstall'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ya Instalado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Sistema Ya Instalado</h2>
                            <p class="text-muted">NexoSupport ya está instalado y configurado.</p>
                            <hr>
                            <div class="d-grid gap-2">
                                <a href="../" class="btn btn-primary">
                                    <i class="bi bi-house"></i> Ir al Sistema
                                </a>
                                <a href="?reinstall=1" class="btn btn-outline-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Reinstalar (Peligroso)
                                </a>
                            </div>
                            <small class="text-muted d-block mt-3">
                                La reinstalación eliminará todos los datos existentes.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load Composer autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    die('Error: Run composer install first');
}
require_once BASE_DIR . '/vendor/autoload.php';

// Installation stages
define('STAGE_REQUIREMENTS', 1);
define('STAGE_DATABASE', 2);
define('STAGE_INSTALL_DB', 3);
define('STAGE_ADMIN', 4);
define('STAGE_FINISH', 5);

// Get current stage
if (!empty($_POST)) {
    $stage = (int)$_POST['stage'];

    if (isset($_POST['next'])) {
        $stage++;
    } else if (isset($_POST['previous'])) {
        $stage--;
    }
} else {
    $stage = STAGE_REQUIREMENTS;
}

// Keep stage in bounds
if ($stage < STAGE_REQUIREMENTS) $stage = STAGE_REQUIREMENTS;
if ($stage > STAGE_FINISH) $stage = STAGE_FINISH;

// Process form data
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next'])) {

    switch ($stage - 1) { // Process previous stage before advancing

        case STAGE_DATABASE:
            // Save database config
            $_SESSION['db_driver'] = trim($_POST['db_driver'] ?? 'mysql');
            $_SESSION['db_host'] = trim($_POST['db_host'] ?? 'localhost');
            $_SESSION['db_port'] = (int)trim($_POST['db_port'] ?? 3306);
            $_SESSION['db_name'] = trim($_POST['db_name'] ?? '');
            $_SESSION['db_user'] = trim($_POST['db_user'] ?? '');
            $_SESSION['db_pass'] = trim($_POST['db_pass'] ?? '');
            $_SESSION['db_prefix'] = trim($_POST['db_prefix'] ?? '');

            // Validate connection
            try {
                $driver = $_SESSION['db_driver'];

                // Construir DSN según el driver
                if ($driver === 'sqlite') {
                    // SQLite: conectar directamente al archivo
                    $dsn = "sqlite:" . BASE_DIR . '/' . $_SESSION['db_name'];
                    $pdo = new PDO($dsn);
                } else {
                    // MySQL/PostgreSQL: conectar al servidor sin especificar BD
                    $config = [
                        'host' => $_SESSION['db_host'],
                        'port' => $_SESSION['db_port'],
                        'database' => ''
                    ];
                    $dsn = \ISER\Core\Database\DatabaseDriverDetector::buildDSN($driver, $config);
                    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Crear base de datos (no necesario para SQLite)
                if ($driver !== 'sqlite') {
                    $adapter = new \ISER\Core\Database\DatabaseAdapter($pdo);
                    $dbName = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['db_name']);
                    $adapter->createDatabase($dbName);
                }

            } catch (PDOException $e) {
                $errors[] = "Error de conexión: " . $e->getMessage();
                $stage--; // Stay on this stage
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
                $stage--; // Stay on this stage
            }
            break;

        case STAGE_ADMIN:
            // Validate admin data
            if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                $errors[] = "Todos los campos son requeridos";
                $stage--;
            } else if ($_POST['password'] !== $_POST['password_confirm']) {
                $errors[] = "Las contraseñas no coinciden";
                $stage--;
            } else {
                $_SESSION['admin_username'] = trim($_POST['username']);
                $_SESSION['admin_email'] = trim($_POST['email']);
                $_SESSION['admin_password'] = $_POST['password'];
                $_SESSION['admin_firstname'] = trim($_POST['first_name'] ?? 'Admin');
                $_SESSION['admin_lastname'] = trim($_POST['last_name'] ?? 'User');
            }
            break;
    }
}

// Stage titles
$stages = [
    STAGE_REQUIREMENTS => 'Requisitos del Sistema',
    STAGE_DATABASE => 'Configuración de Base de Datos',
    STAGE_INSTALL_DB => 'Instalación de Base de Datos',
    STAGE_ADMIN => 'Usuario Administrador',
    STAGE_FINISH => 'Instalación Completada'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador ISER - <?= $stages[$stage] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f5f5; padding: 20px 0; }
        .container { max-width: 800px; }
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .stage-indicator { text-align: center; margin-bottom: 30px; }
        .stage-indicator .badge { font-size: 1rem; padding: 10px 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h1>ISER Authentication System</h1>
            <p class="text-muted">Instalador Web</p>
        </div>

        <div class="stage-indicator">
            <span class="badge bg-primary">Paso <?= $stage ?> de <?= STAGE_FINISH ?> - <?= $stages[$stage] ?></span>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php
                // Include stage file
                switch ($stage) {
                    case STAGE_REQUIREMENTS:
                        include __DIR__ . '/stages/requirements.php';
                        break;
                    case STAGE_DATABASE:
                        include __DIR__ . '/stages/database.php';
                        break;
                    case STAGE_INSTALL_DB:
                        include __DIR__ . '/stages/install_db.php';
                        break;
                    case STAGE_ADMIN:
                        include __DIR__ . '/stages/admin.php';
                        break;
                    case STAGE_FINISH:
                        include __DIR__ . '/stages/finish.php';
                        break;
                }
                ?>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">ISER Authentication System &copy; <?= date('Y') ?></small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
