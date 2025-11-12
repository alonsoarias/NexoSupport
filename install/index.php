<?php
/**
 * NexoSupport Sistema de Soporte - Web Installer
 *
 * Instalador con tema ISER corporativo
 *
 * @package NexoSupport
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
        <title>Sistema Ya Instalado - NexoSupport</title>
        <link rel="stylesheet" href="/assets/css/iser-theme.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    </head>
    <body>
        <div class="container">
            <!-- Header ISER -->
            <div class="iser-header">
                <div class="iser-header-logo-fallback">
                    Instituto Superior de<br>
                    Educación Rural
                    <div class="iser">ISER</div>
                </div>
                <div class="iser-header-info">
                    <h1>NexoSupport</h1>
                    <p>Sistema de Soporte y Gestión</p>
                    <p class="vigilado">Vigilado por el Ministerio de Educación Nacional</p>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="card" style="text-align: center; padding: 60px 40px;">
                    <i class="bi bi-check-circle" style="font-size: 5rem; color: var(--iser-green);"></i>
                    <h2 class="section-title" style="margin-top: 30px;">Sistema Ya Instalado</h2>
                    <p style="font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 40px;">
                        NexoSupport ya está instalado y configurado correctamente.
                    </p>

                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="/" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem;">
                            <i class="bi bi-house"></i> Ir al Sistema
                        </a>
                        <a href="/install.php?reinstall=1" class="btn btn-danger" style="padding: 15px 30px; font-size: 1.1rem;">
                            <i class="bi bi-exclamation-triangle"></i> Reinstalar (Peligroso)
                        </a>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: var(--bg-light); border-left: 4px solid var(--iser-yellow);">
                        <i class="bi bi-exclamation-triangle" style="color: var(--iser-yellow); font-size: 1.5rem;"></i>
                        <p style="margin: 10px 0 0 0; font-size: 0.95rem; color: var(--text-secondary);">
                            <strong>Advertencia:</strong> La reinstalación eliminará todos los datos existentes del sistema.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="iser-footer">
                <p>Instituto Superior de Educación Rural - ISER &copy; <?= date('Y') ?></p>
                <p>Vigilado por el Ministerio de Educación Nacional</p>
            </div>
        </div>

        <style>
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--iser-green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--iser-green-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(27, 158, 136, 0.3);
        }

        .btn-danger {
            background: var(--iser-red);
            color: white;
        }

        .btn-danger:hover {
            background: var(--iser-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(235, 67, 53, 0.3);
        }

        .iser-footer {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 2px solid var(--border-color);
        }

        .iser-footer p {
            margin: 5px 0;
        }
        </style>
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
define('STAGE_WELCOME', 1);
define('STAGE_REQUIREMENTS', 2);
define('STAGE_DATABASE', 3);
define('STAGE_INSTALL_DB', 4);
define('STAGE_ADMIN', 5);
define('STAGE_SECURITY', 6);
define('STAGE_LOGGING', 7);
define('STAGE_EMAIL', 8);
define('STAGE_STORAGE', 9);
define('STAGE_REGIONAL', 10);
define('STAGE_FINISH', 11);

// Get current stage
if (!empty($_POST)) {
    $stage = (int)$_POST['stage'];

    if (isset($_POST['next'])) {
        $stage++;
    } else if (isset($_POST['previous'])) {
        $stage--;
    }
} else {
    $stage = STAGE_WELCOME;
}

// Keep stage in bounds
if ($stage < STAGE_WELCOME) $stage = STAGE_WELCOME;
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
                    $dsn = "sqlite:" . BASE_DIR . '/' . $_SESSION['db_name'];
                    $pdo = new PDO($dsn);
                } else {
                    $config = [
                        'host' => $_SESSION['db_host'],
                        'port' => $_SESSION['db_port'],
                        'database' => ''
                    ];
                    $dsn = \ISER\Core\Database\DatabaseDriverDetector::buildDSN($driver, $config);
                    $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Crear base de datos
                if ($driver !== 'sqlite') {
                    $adapter = new \ISER\Core\Database\DatabaseAdapter($pdo);
                    $dbName = preg_replace('/[^a-zA-Z0-9_-]/', '', $_SESSION['db_name']);
                    $adapter->createDatabase($dbName);
                }

            } catch (PDOException $e) {
                $errors[] = "Error de conexión: " . $e->getMessage();
                $stage--;
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
                $stage--;
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

        case STAGE_SECURITY:
            // Save security configuration
            $_SESSION['security_session_lifetime'] = (int)trim($_POST['security_session_lifetime'] ?? 3600);
            $_SESSION['security_max_login_attempts'] = (int)trim($_POST['security_max_login_attempts'] ?? 5);
            $_SESSION['security_lockout_time'] = (int)trim($_POST['security_lockout_time'] ?? 900);
            $_SESSION['security_password_min_length'] = (int)trim($_POST['security_password_min_length'] ?? 8);
            $_SESSION['security_require_special_chars'] = isset($_POST['security_require_special_chars']) ? 1 : 0;
            $_SESSION['security_enable_2fa'] = isset($_POST['security_enable_2fa']) ? 1 : 0;
            $_SESSION['security_jwt_secret'] = trim($_POST['security_jwt_secret'] ?? bin2hex(random_bytes(32)));
            break;

        case STAGE_LOGGING:
            // Save logging configuration
            $_SESSION['log_level'] = trim($_POST['log_level'] ?? 'info');
            $_SESSION['log_path'] = trim($_POST['log_path'] ?? 'storage/logs');
            $_SESSION['log_channel'] = trim($_POST['log_channel'] ?? 'daily');
            $_SESSION['log_max_files'] = (int)trim($_POST['log_max_files'] ?? 14);
            $_SESSION['log_enable_query_log'] = isset($_POST['log_enable_query_log']) ? 1 : 0;
            $_SESSION['log_enable_error_reporting'] = isset($_POST['log_enable_error_reporting']) ? 1 : 0;
            break;

        case STAGE_EMAIL:
            // Save email configuration
            $_SESSION['mail_driver'] = trim($_POST['mail_driver'] ?? 'smtp');
            $_SESSION['mail_host'] = trim($_POST['mail_host'] ?? '');
            $_SESSION['mail_port'] = (int)trim($_POST['mail_port'] ?? 587);
            $_SESSION['mail_username'] = trim($_POST['mail_username'] ?? '');
            $_SESSION['mail_password'] = trim($_POST['mail_password'] ?? '');
            $_SESSION['mail_encryption'] = trim($_POST['mail_encryption'] ?? 'tls');
            $_SESSION['mail_from_address'] = trim($_POST['mail_from_address'] ?? '');
            $_SESSION['mail_from_name'] = trim($_POST['mail_from_name'] ?? 'NexoSupport');
            break;

        case STAGE_STORAGE:
            // Save storage and cache configuration
            $_SESSION['cache_driver'] = trim($_POST['cache_driver'] ?? 'file');
            $_SESSION['cache_prefix'] = trim($_POST['cache_prefix'] ?? 'nexo_');
            $_SESSION['cache_ttl'] = (int)trim($_POST['cache_ttl'] ?? 3600);
            $_SESSION['storage_driver'] = trim($_POST['storage_driver'] ?? 'local');
            $_SESSION['storage_path'] = trim($_POST['storage_path'] ?? 'storage/uploads');
            $_SESSION['storage_max_size'] = (int)trim($_POST['storage_max_size'] ?? 10485760);
            break;

        case STAGE_REGIONAL:
            // Save regional and localization configuration
            $_SESSION['regional_timezone'] = trim($_POST['regional_timezone'] ?? 'America/Bogota');
            $_SESSION['regional_locale'] = trim($_POST['regional_locale'] ?? 'es_CO');
            $_SESSION['regional_currency'] = trim($_POST['regional_currency'] ?? 'COP');
            $_SESSION['regional_date_format'] = trim($_POST['regional_date_format'] ?? 'Y-m-d');
            $_SESSION['regional_time_format'] = trim($_POST['regional_time_format'] ?? 'H:i:s');
            break;
    }
}

// Stage titles
$stages = [
    STAGE_WELCOME => 'Bienvenida',
    STAGE_REQUIREMENTS => 'Requisitos del Sistema',
    STAGE_DATABASE => 'Configuración de Base de Datos',
    STAGE_INSTALL_DB => 'Instalación de Base de Datos',
    STAGE_ADMIN => 'Usuario Administrador',
    STAGE_SECURITY => 'Seguridad',
    STAGE_LOGGING => 'Sistema de Logs',
    STAGE_EMAIL => 'Configuración de Email',
    STAGE_STORAGE => 'Almacenamiento y Cache',
    STAGE_REGIONAL => 'Configuración Regional',
    STAGE_FINISH => 'Instalación Completada'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador NexoSupport - <?= $stages[$stage] ?></title>
    <link rel="stylesheet" href="/assets/css/iser-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/installer.css">
</head>
<body>
    <div class="container">
        <!-- Header ISER -->
        <div class="iser-header">
            <div class="iser-header-logo-fallback">
                Instituto Superior de<br>
                Educación Rural
                <div class="iser">ISER</div>
            </div>
            <div class="iser-header-info">
                <h1>NexoSupport</h1>
                <p>Instalador del Sistema de Soporte</p>
                <p class="vigilado">Vigilado por el Ministerio de Educación Nacional</p>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div style="background: var(--bg-light); padding: 15px 30px; margin-bottom: 20px; border-radius: 8px; text-align: center; border: 2px solid var(--iser-green);">
            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 5px;">Progreso de Instalación</div>
            <div style="font-size: 1.3rem; font-weight: 600; color: var(--iser-green);">
                Etapa <?= $stage ?> de 11: <?= $stages[$stage] ?>
            </div>
            <div style="margin-top: 10px; background: var(--border-color); height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: var(--iser-green); height: 100%; width: <?= round(($stage / 11) * 100) ?>%; transition: width 0.3s ease;"></div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="steps-container">
            <div class="steps-progress">
                <?php for ($i = STAGE_WELCOME; $i <= STAGE_FINISH; $i++): ?>
                <div class="step <?= $i < $stage ? 'completed' : ($i == $stage ? 'active' : '') ?>">
                    <div class="step-number">
                        <?php if ($i < $stage): ?>
                            <i class="bi bi-check"></i>
                        <?php else: ?>
                            <?= $i ?>
                        <?php endif; ?>
                    </div>
                    <div class="step-name"><?= $stages[$i] ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i>
                <strong>Error:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="content">
            <div class="card">
                <?php
                // Include stage file
                switch ($stage) {
                    case STAGE_WELCOME:
                        include __DIR__ . '/stages/welcome.php';
                        break;
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
                    case STAGE_SECURITY:
                        include __DIR__ . '/stages/security.php';
                        break;
                    case STAGE_LOGGING:
                        include __DIR__ . '/stages/logging.php';
                        break;
                    case STAGE_EMAIL:
                        include __DIR__ . '/stages/email.php';
                        break;
                    case STAGE_STORAGE:
                        include __DIR__ . '/stages/storage.php';
                        break;
                    case STAGE_REGIONAL:
                        include __DIR__ . '/stages/regional.php';
                        break;
                    case STAGE_FINISH:
                        include __DIR__ . '/stages/finish.php';
                        break;
                }
                ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="iser-footer">
            <p>Instituto Superior de Educación Rural - ISER &copy; <?= date('Y') ?></p>
            <p>Vigilado por el Ministerio de Educación Nacional</p>
        </div>
    </div>

    <script src="assets/js/installer.js"></script>
</body>
</html>
