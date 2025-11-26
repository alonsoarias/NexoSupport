<?php
/**
 * NexoSupport Installation Script
 *
 * Web-based installation wizard for NexoSupport.
 * Similar to Moodle's install.php
 *
 * Installation Phases:
 * - INSTALL_WELCOME (0): Welcome page and language selection
 * - INSTALL_ENVIRONMENT (1): System requirements check
 * - INSTALL_PATHS (2): Configure paths (wwwroot, dataroot)
 * - INSTALL_DATABASE (3): Database configuration
 * - INSTALL_ADMIN (4): Admin account setup
 * - INSTALL_SAVE (5): Execute installation
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

// Prevent direct access after installation
if (file_exists(__DIR__ . '/.env') && !isset($_GET['force'])) {
    // Check if installation is complete
    require_once(__DIR__ . '/config.php');
    if (!empty($CFG->version)) {
        header('Location: ' . $CFG->wwwroot);
        exit;
    }
}

// Minimal bootstrap for installation
define('NEXOSUPPORT_INTERNAL', true);
define('NEXOSUPPORT_INSTALLING', true);
define('BASE_DIR', __DIR__);

// Error display during installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Installation phases
define('INSTALL_WELCOME', 0);
define('INSTALL_ENVIRONMENT', 1);
define('INSTALL_PATHS', 2);
define('INSTALL_DATABASE', 3);
define('INSTALL_ADMIN', 4);
define('INSTALL_SAVE', 5);
define('INSTALL_COMPLETE', 6);

// Memory constants
define('MEMORY_STANDARD', '128M');
define('MEMORY_EXTRA', '256M');
define('MEMORY_HUGE', '512M');

// Context levels for RBAC
define('CONTEXT_SYSTEM', 10);
define('CONTEXT_USER', 30);
define('CONTEXT_COURSE', 50);
define('CONTEXT_MODULE', 70);

// Risk levels
define('RISK_CONFIG', 1);
define('RISK_DATALOSS', 2);
define('RISK_PERSONAL', 4);
define('RISK_XSS', 8);
define('RISK_SPAM', 16);

// Load installation library
require_once(__DIR__ . '/lib/installlib.php');

// Session for installation data
session_start();

// Get current phase
$phase = isset($_GET['phase']) ? (int)$_GET['phase'] : INSTALL_WELCOME;
if (isset($_POST['phase'])) {
    $phase = (int)$_POST['phase'];
}

// Installation data from session
$installdata = $_SESSION['nexosupport_install'] ?? [
    'lang' => 'es',
    'wwwroot' => '',
    'dataroot' => '',
    'dbtype' => 'mysqli',
    'dbhost' => 'localhost',
    'dbport' => '3306',
    'dbname' => 'nexosupport',
    'dbuser' => '',
    'dbpass' => '',
    'prefix' => 'ns_',
    'adminuser' => 'admin',
    'adminpass' => '',
    'adminemail' => '',
    'sitename' => 'NexoSupport'
];

// Process form submissions
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($phase) {
        case INSTALL_PATHS:
            $installdata['wwwroot'] = trim($_POST['wwwroot'] ?? '');
            $installdata['dataroot'] = trim($_POST['dataroot'] ?? '');

            // Validate
            if (empty($installdata['wwwroot'])) {
                $errors[] = 'URL del sitio es requerida';
            }
            if (empty($installdata['dataroot'])) {
                $errors[] = 'Directorio de datos es requerido';
            } elseif (!is_writable(dirname($installdata['dataroot']))) {
                $errors[] = 'El directorio padre de dataroot no es escribible';
            }

            if (empty($errors)) {
                $phase = INSTALL_DATABASE;
            }
            break;

        case INSTALL_DATABASE:
            $installdata['dbtype'] = $_POST['dbtype'] ?? 'mysqli';
            $installdata['dbhost'] = trim($_POST['dbhost'] ?? 'localhost');
            $installdata['dbport'] = trim($_POST['dbport'] ?? '3306');
            $installdata['dbname'] = trim($_POST['dbname'] ?? '');
            $installdata['dbuser'] = trim($_POST['dbuser'] ?? '');
            $installdata['dbpass'] = $_POST['dbpass'] ?? '';
            $installdata['prefix'] = trim($_POST['prefix'] ?? 'ns_');

            // Validate database configuration
            $validation = install_validate_database_config($installdata);
            if (!$validation['valid']) {
                $errors = $validation['errors'];
            } else {
                // Test connection
                $test = install_test_database_connection($installdata);
                if (!$test['success']) {
                    $errors[] = $test['message'];
                }
            }

            if (empty($errors)) {
                $phase = INSTALL_ADMIN;
            }
            break;

        case INSTALL_ADMIN:
            $installdata['adminuser'] = trim($_POST['adminuser'] ?? 'admin');
            $installdata['adminpass'] = $_POST['adminpass'] ?? '';
            $installdata['adminpassconfirm'] = $_POST['adminpassconfirm'] ?? '';
            $installdata['adminemail'] = trim($_POST['adminemail'] ?? '');
            $installdata['sitename'] = trim($_POST['sitename'] ?? 'NexoSupport');

            // Validate
            if (empty($installdata['adminuser'])) {
                $errors[] = 'Usuario administrador es requerido';
            }
            if (strlen($installdata['adminpass']) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres';
            }
            if ($installdata['adminpass'] !== $installdata['adminpassconfirm']) {
                $errors[] = 'Las contraseñas no coinciden';
            }
            if (empty($installdata['adminemail']) || !filter_var($installdata['adminemail'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email válido es requerido';
            }

            if (empty($errors)) {
                $phase = INSTALL_SAVE;
            }
            break;
    }

    // Save to session
    $_SESSION['nexosupport_install'] = $installdata;
}

// Auto-detect wwwroot if not set
if (empty($installdata['wwwroot'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $installdata['wwwroot'] = $protocol . '://' . $host . ($path !== '/' ? $path : '');
}

// Auto-detect dataroot suggestion
if (empty($installdata['dataroot'])) {
    $installdata['dataroot'] = dirname(__DIR__) . '/nexodata';
}

// Render installation page
install_render_page($phase, $installdata, $errors);

/**
 * Render the installation page
 *
 * @param int $phase Current installation phase
 * @param array $data Installation data
 * @param array $errors Error messages
 */
function install_render_page($phase, $data, $errors = []) {
    $phases = [
        INSTALL_WELCOME => 'Bienvenida',
        INSTALL_ENVIRONMENT => 'Requisitos',
        INSTALL_PATHS => 'Configuración',
        INSTALL_DATABASE => 'Base de Datos',
        INSTALL_ADMIN => 'Administrador',
        INSTALL_SAVE => 'Instalación'
    ];

    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - NexoSupport</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1B9E88 0%, #0d5c4f 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .installer {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #1B9E88 0%, #167a6a 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .installer-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .installer-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .progress-bar {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            font-size: 12px;
            color: #6c757d;
        }
        .progress-step::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
        }
        .progress-step:last-child::before { display: none; }
        .progress-step .number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            font-weight: bold;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        .progress-step.active .number {
            background: #FCBD05;
            color: #333;
        }
        .progress-step.completed .number {
            background: #1B9E88;
        }
        .progress-step.active, .progress-step.completed { color: #333; }
        .installer-content {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #1B9E88;
        }
        select.form-control {
            appearance: none;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
            background-color: white;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1B9E88 0%, #167a6a 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(27, 158, 136, 0.4);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fff5f5;
            border: 1px solid #fc8181;
            color: #c53030;
        }
        .alert-success {
            background: #f0fff4;
            border: 1px solid #68d391;
            color: #276749;
        }
        .alert-info {
            background: #ebf8ff;
            border: 1px solid #63b3ed;
            color: #2b6cb0;
        }
        .requirements-list {
            list-style: none;
        }
        .requirements-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .requirements-list li:last-child { border-bottom: none; }
        .status-ok { color: #1B9E88; font-weight: bold; }
        .status-fail { color: #e53e3e; font-weight: bold; }
        .install-log {
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .install-log .log-line {
            padding: 4px 0;
        }
        .install-log .log-success { color: #68d391; }
        .install-log .log-error { color: #fc8181; }
        .install-log .log-info { color: #63b3ed; }
        .welcome-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .feature-card {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .feature-card h4 {
            color: #1B9E88;
            margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <h1>NexoSupport</h1>
            <p>Sistema de Soporte y Gestión Educativa</p>
        </div>

        <div class="progress-bar">
            <?php foreach ($phases as $p => $name): ?>
            <div class="progress-step <?php echo $p < $phase ? 'completed' : ($p === $phase ? 'active' : ''); ?>">
                <div class="number"><?php echo $p + 1; ?></div>
                <div><?php echo $name; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="installer-content">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Errores encontrados:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php
            switch ($phase) {
                case INSTALL_WELCOME:
                    install_render_welcome();
                    break;
                case INSTALL_ENVIRONMENT:
                    install_render_environment();
                    break;
                case INSTALL_PATHS:
                    install_render_paths($data);
                    break;
                case INSTALL_DATABASE:
                    install_render_database($data);
                    break;
                case INSTALL_ADMIN:
                    install_render_admin($data);
                    break;
                case INSTALL_SAVE:
                    install_execute_installation($data);
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>
    <?php
}

/**
 * Render welcome phase
 */
function install_render_welcome() {
    ?>
    <h2 style="margin-bottom: 20px;">Bienvenido a NexoSupport</h2>
    <p style="color: #6c757d; margin-bottom: 20px;">
        Este asistente le guiará a través del proceso de instalación de NexoSupport,
        un sistema de soporte y gestión educativa inspirado en la arquitectura de Moodle.
    </p>

    <div class="welcome-features">
        <div class="feature-card">
            <h4>Sistema RBAC</h4>
            <p>Control de acceso basado en roles con contextos jerárquicos.</p>
        </div>
        <div class="feature-card">
            <h4>Sistema de Plugins</h4>
            <p>Arquitectura extensible con plugins de autenticación, temas y herramientas.</p>
        </div>
        <div class="feature-card">
            <h4>Navegación Moderna</h4>
            <p>Interfaz responsive con navegación tipo Moodle 4.x e ISER branding.</p>
        </div>
        <div class="feature-card">
            <h4>Actualizaciones Incrementales</h4>
            <p>Sistema de upgrade con savepoints y migraciones de base de datos.</p>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Antes de continuar, asegúrese de tener:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <li>PHP 8.1 o superior</li>
            <li>Base de datos MySQL/MariaDB o PostgreSQL</li>
            <li>Credenciales de acceso a la base de datos</li>
            <li>Un directorio para almacenar datos fuera del webroot</li>
        </ul>
    </div>

    <div class="btn-group">
        <div></div>
        <a href="?phase=<?php echo INSTALL_ENVIRONMENT; ?>" class="btn btn-primary">
            Comenzar Instalación &rarr;
        </a>
    </div>
    <?php
}

/**
 * Render environment check phase
 */
function install_render_environment() {
    $requirements = install_check_requirements();
    ?>
    <h2 style="margin-bottom: 20px;">Verificación de Requisitos</h2>
    <p style="color: #6c757d; margin-bottom: 20px;">
        Verificando que el servidor cumpla con los requisitos mínimos del sistema.
    </p>

    <ul class="requirements-list">
        <?php foreach ($requirements['results'] as $req): ?>
        <li>
            <div>
                <strong><?php echo htmlspecialchars($req['name']); ?></strong>
                <small style="display: block; color: #6c757d;">
                    Requerido: <?php echo htmlspecialchars($req['required']); ?> |
                    Actual: <?php echo htmlspecialchars($req['current']); ?>
                </small>
            </div>
            <span class="<?php echo $req['status'] ? 'status-ok' : 'status-fail'; ?>">
                <?php echo $req['status'] ? '✓ OK' : '✗ Fallo'; ?>
            </span>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="btn-group">
        <a href="?phase=<?php echo INSTALL_WELCOME; ?>" class="btn btn-secondary">
            &larr; Atrás
        </a>
        <?php if ($requirements['status']): ?>
        <a href="?phase=<?php echo INSTALL_PATHS; ?>" class="btn btn-primary">
            Continuar &rarr;
        </a>
        <?php else: ?>
        <button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
            Requisitos no cumplidos
        </button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render paths configuration phase
 */
function install_render_paths($data) {
    ?>
    <h2 style="margin-bottom: 20px;">Configuración de Rutas</h2>
    <p style="color: #6c757d; margin-bottom: 20px;">
        Configure las rutas del sistema.
    </p>

    <form method="post" action="?phase=<?php echo INSTALL_PATHS; ?>">
        <input type="hidden" name="phase" value="<?php echo INSTALL_PATHS; ?>">

        <div class="form-group">
            <label for="wwwroot">URL del Sitio (wwwroot)</label>
            <input type="url" id="wwwroot" name="wwwroot" class="form-control"
                   value="<?php echo htmlspecialchars($data['wwwroot']); ?>" required>
            <small>La URL completa donde estará accesible NexoSupport (ej: https://midominio.com/nexosupport)</small>
        </div>

        <div class="form-group">
            <label for="dataroot">Directorio de Datos (dataroot)</label>
            <input type="text" id="dataroot" name="dataroot" class="form-control"
                   value="<?php echo htmlspecialchars($data['dataroot']); ?>" required>
            <small>Directorio para almacenar archivos del sistema. Debe estar FUERA del directorio web por seguridad.</small>
        </div>

        <div class="btn-group">
            <a href="?phase=<?php echo INSTALL_ENVIRONMENT; ?>" class="btn btn-secondary">
                &larr; Atrás
            </a>
            <button type="submit" class="btn btn-primary">Continuar &rarr;</button>
        </div>
    </form>
    <?php
}

/**
 * Render database configuration phase
 */
function install_render_database($data) {
    ?>
    <h2 style="margin-bottom: 20px;">Configuración de Base de Datos</h2>
    <p style="color: #6c757d; margin-bottom: 20px;">
        Configure la conexión a la base de datos.
    </p>

    <form method="post" action="?phase=<?php echo INSTALL_DATABASE; ?>">
        <input type="hidden" name="phase" value="<?php echo INSTALL_DATABASE; ?>">

        <div class="form-group">
            <label for="dbtype">Tipo de Base de Datos</label>
            <select id="dbtype" name="dbtype" class="form-control">
                <option value="mysqli" <?php echo $data['dbtype'] === 'mysqli' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
                <option value="pgsql" <?php echo $data['dbtype'] === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="dbhost">Servidor</label>
                <input type="text" id="dbhost" name="dbhost" class="form-control"
                       value="<?php echo htmlspecialchars($data['dbhost']); ?>" required>
            </div>
            <div class="form-group">
                <label for="dbport">Puerto</label>
                <input type="text" id="dbport" name="dbport" class="form-control"
                       value="<?php echo htmlspecialchars($data['dbport']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="dbname">Nombre de Base de Datos</label>
            <input type="text" id="dbname" name="dbname" class="form-control"
                   value="<?php echo htmlspecialchars($data['dbname']); ?>" required>
            <small>La base de datos debe existir previamente</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="dbuser">Usuario</label>
                <input type="text" id="dbuser" name="dbuser" class="form-control"
                       value="<?php echo htmlspecialchars($data['dbuser']); ?>" required>
            </div>
            <div class="form-group">
                <label for="dbpass">Contraseña</label>
                <input type="password" id="dbpass" name="dbpass" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="prefix">Prefijo de Tablas</label>
            <input type="text" id="prefix" name="prefix" class="form-control"
                   value="<?php echo htmlspecialchars($data['prefix']); ?>">
            <small>Prefijo para todas las tablas (ej: ns_). Útil si comparte la base de datos.</small>
        </div>

        <div class="btn-group">
            <a href="?phase=<?php echo INSTALL_PATHS; ?>" class="btn btn-secondary">
                &larr; Atrás
            </a>
            <button type="submit" class="btn btn-primary">Continuar &rarr;</button>
        </div>
    </form>
    <?php
}

/**
 * Render admin account setup phase
 */
function install_render_admin($data) {
    ?>
    <h2 style="margin-bottom: 20px;">Cuenta de Administrador</h2>
    <p style="color: #6c757d; margin-bottom: 20px;">
        Configure la cuenta del administrador principal y el nombre del sitio.
    </p>

    <form method="post" action="?phase=<?php echo INSTALL_ADMIN; ?>">
        <input type="hidden" name="phase" value="<?php echo INSTALL_ADMIN; ?>">

        <div class="form-group">
            <label for="sitename">Nombre del Sitio</label>
            <input type="text" id="sitename" name="sitename" class="form-control"
                   value="<?php echo htmlspecialchars($data['sitename']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="adminuser">Usuario Administrador</label>
                <input type="text" id="adminuser" name="adminuser" class="form-control"
                       value="<?php echo htmlspecialchars($data['adminuser']); ?>" required>
            </div>
            <div class="form-group">
                <label for="adminemail">Email Administrador</label>
                <input type="email" id="adminemail" name="adminemail" class="form-control"
                       value="<?php echo htmlspecialchars($data['adminemail']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="adminpass">Contraseña</label>
                <input type="password" id="adminpass" name="adminpass" class="form-control" required>
                <small>Mínimo 8 caracteres</small>
            </div>
            <div class="form-group">
                <label for="adminpassconfirm">Confirmar Contraseña</label>
                <input type="password" id="adminpassconfirm" name="adminpassconfirm" class="form-control" required>
            </div>
        </div>

        <div class="btn-group">
            <a href="?phase=<?php echo INSTALL_DATABASE; ?>" class="btn btn-secondary">
                &larr; Atrás
            </a>
            <button type="submit" class="btn btn-primary">Instalar NexoSupport &rarr;</button>
        </div>
    </form>
    <?php
}

/**
 * Execute the installation
 */
function install_execute_installation($data) {
    global $CFG, $DB;

    echo '<h2 style="margin-bottom: 20px;">Instalando NexoSupport...</h2>';
    echo '<div class="install-log" id="installLog">';

    $success = true;

    try {
        // Step 1: Create dataroot directory
        install_log_message('Creando directorio de datos...', 'info');
        if (!file_exists($data['dataroot'])) {
            if (!@mkdir($data['dataroot'], 0755, true)) {
                throw new Exception('No se pudo crear el directorio de datos');
            }
        }
        install_log_message('Directorio de datos creado', 'success');

        // Step 2: Create subdirectories
        install_log_message('Creando subdirectorios...', 'info');
        $subdirs = ['cache', 'temp', 'sessions', 'filedir', 'localcache', 'trashdir'];
        foreach ($subdirs as $dir) {
            $path = $data['dataroot'] . '/' . $dir;
            if (!file_exists($path)) {
                @mkdir($path, 0755, true);
            }
        }
        install_log_message('Subdirectorios creados', 'success');

        // Step 3: Generate .env file
        install_log_message('Generando archivo de configuración...', 'info');
        $envfile = BASE_DIR . '/.env';
        install_save_env_file($envfile, $data);
        install_log_message('Archivo .env creado', 'success');

        // Step 4: Initialize database connection
        install_log_message('Conectando a la base de datos...', 'info');

        // Set up CFG for database connection
        $CFG = new stdClass();
        $CFG->dirroot = BASE_DIR;
        $CFG->wwwroot = $data['wwwroot'];
        $CFG->dataroot = $data['dataroot'];
        $CFG->dbtype = $data['dbtype'];
        $CFG->dbhost = $data['dbhost'];
        $CFG->dbport = $data['dbport'];
        $CFG->dbname = $data['dbname'];
        $CFG->dbuser = $data['dbuser'];
        $CFG->dbpass = $data['dbpass'];
        $CFG->prefix = $data['prefix'];
        $CFG->debug = E_ALL;

        // Load database class
        require_once(BASE_DIR . '/lib/classes/db/database.php');
        $DB = \core\db\database::get_instance($CFG);

        install_log_message('Conexión a base de datos establecida', 'success');

        // Step 5: Install database schema
        install_log_message('Instalando esquema de base de datos...', 'info');
        $schemafile = BASE_DIR . '/lib/db/install.xml';
        if (file_exists($schemafile)) {
            $dbman = $DB->get_manager();
            $dbman->install_from_xmldb_file($schemafile);
            install_log_message('Esquema de base de datos instalado', 'success');
        } else {
            install_log_message('Archivo install.xml no encontrado, omitiendo...', 'info');
        }

        // Step 6: Get version info
        $plugin = new stdClass();
        require_once(BASE_DIR . '/lib/version.php');
        $CFG->version = $plugin->version;
        $CFG->release = $plugin->release;

        // Step 7: Insert initial configuration
        install_log_message('Insertando configuración inicial...', 'info');

        // Check if config table exists and has version
        try {
            $existingVersion = $DB->get_field('config', 'value', ['name' => 'version']);
        } catch (Exception $e) {
            $existingVersion = null;
        }

        if (!$existingVersion) {
            // Insert version
            $DB->insert_record('config', [
                'name' => 'version',
                'value' => $plugin->version
            ]);
            $DB->insert_record('config', [
                'name' => 'release',
                'value' => $plugin->release
            ]);
            $DB->insert_record('config', [
                'name' => 'sitename',
                'value' => $data['sitename']
            ]);
            $DB->insert_record('config', [
                'name' => 'installed',
                'value' => time()
            ]);
        }
        install_log_message('Configuración inicial guardada', 'success');

        // Step 8: Run post-installation
        install_log_message('Ejecutando post-instalación...', 'info');
        $installfile = BASE_DIR . '/lib/db/install.php';
        if (file_exists($installfile)) {
            require_once($installfile);
            if (function_exists('xmldb_main_install')) {
                xmldb_main_install();
            }
        }
        install_log_message('Post-instalación completada', 'success');

        // Step 9: Create admin user
        install_log_message('Creando usuario administrador...', 'info');
        $adminid = install_create_admin_user($data, $DB);
        install_log_message('Usuario administrador creado (ID: ' . $adminid . ')', 'success');

        // Success
        install_log_message('', '');
        install_log_message('==========================================', 'success');
        install_log_message('  INSTALACIÓN COMPLETADA EXITOSAMENTE!', 'success');
        install_log_message('==========================================', 'success');

        // Clear session
        unset($_SESSION['nexosupport_install']);

    } catch (Exception $e) {
        $success = false;
        install_log_message('ERROR: ' . $e->getMessage(), 'error');
    }

    echo '</div>';

    if ($success) {
        echo '<div class="alert alert-success" style="margin-top: 20px;">';
        echo '<strong>Instalación completada!</strong><br>';
        echo 'NexoSupport ha sido instalado correctamente. Puede acceder al sistema.';
        echo '</div>';
        echo '<div class="btn-group">';
        echo '<div></div>';
        echo '<a href="' . htmlspecialchars($data['wwwroot']) . '/login" class="btn btn-primary">Ir al Login &rarr;</a>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger" style="margin-top: 20px;">';
        echo '<strong>Error de instalación</strong><br>';
        echo 'Hubo un problema durante la instalación. Revise el log anterior.';
        echo '</div>';
        echo '<div class="btn-group">';
        echo '<a href="?phase=' . INSTALL_ADMIN . '" class="btn btn-secondary">&larr; Atrás</a>';
        echo '<div></div>';
        echo '</div>';
    }
}

/**
 * Create admin user
 */
function install_create_admin_user($data, $DB) {
    // Check if admin user already exists
    try {
        $existing = $DB->get_record('users', ['username' => $data['adminuser']]);
        if ($existing) {
            return $existing->id;
        }
    } catch (Exception $e) {
        // Table might not exist
    }

    // Create admin user
    $user = new stdClass();
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->username = $data['adminuser'];
    $user->password = password_hash($data['adminpass'], PASSWORD_DEFAULT);
    $user->firstname = 'Admin';
    $user->lastname = 'User';
    $user->email = $data['adminemail'];
    $user->lang = 'es';
    $user->timezone = 'America/Mexico_City';
    $user->timecreated = time();
    $user->timemodified = time();

    try {
        $id = $DB->insert_record('users', $user);

        // Assign site admin role if roles table exists
        try {
            // Get admin role
            $adminrole = $DB->get_record('roles', ['shortname' => 'admin']);
            if ($adminrole) {
                // Get system context
                $syscontext = $DB->get_record('contexts', [
                    'contextlevel' => CONTEXT_SYSTEM,
                    'instanceid' => 0
                ]);
                if ($syscontext) {
                    $DB->insert_record('role_assignments', [
                        'roleid' => $adminrole->id,
                        'contextid' => $syscontext->id,
                        'userid' => $id,
                        'timemodified' => time(),
                        'modifierid' => $id
                    ]);
                }
            }
        } catch (Exception $e) {
            // Roles system might not be fully set up
        }

        return $id;
    } catch (Exception $e) {
        throw new Exception('No se pudo crear el usuario administrador: ' . $e->getMessage());
    }
}

/**
 * Log installation message
 */
function install_log_message($message, $type = 'info') {
    $class = 'log-' . $type;
    $prefix = '';

    switch ($type) {
        case 'success':
            $prefix = '✓ ';
            break;
        case 'error':
            $prefix = '✗ ';
            break;
        case 'info':
            $prefix = '→ ';
            break;
    }

    echo '<div class="log-line ' . $class . '">' . $prefix . htmlspecialchars($message) . '</div>';

    // Flush output
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}
