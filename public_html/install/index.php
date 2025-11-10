<?php
/**
 * ISER Authentication System - Web Installer
 * @package Installer
 * @author ISER Desarrollo
 * @license Propietario
 */

session_start();

// Define base directory
define('INSTALL_DIR', __DIR__);
define('BASE_DIR', dirname(__DIR__, 2));
define('ENV_FILE', BASE_DIR . '/.env');
define('INSTALL_LOCK', BASE_DIR . '/.installed');

// Verificar si ya está instalado
if (file_exists(INSTALL_LOCK) && !isset($_GET['reinstall'])) {
    header('Location: ../index.php');
    exit('Sistema ya instalado. Si necesita reinstalar, elimine el archivo .installed');
}

// Cargar autoloader de Composer
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    die('Error: Composer dependencies not installed. Run: composer install');
}

require_once BASE_DIR . '/vendor/autoload.php';

// Pasos del instalador
$steps = [
    1 => 'Verificación de Requisitos',
    2 => 'Configuración de Base de Datos',
    3 => 'Configuración del Sistema',
    4 => 'Instalación de Base de Datos',
    5 => 'Crear Administrador',
    6 => 'Finalización'
];

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($currentStep < 1 || $currentStep > 6) {
    $currentStep = 1;
}

// Procesar formularios
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 2:
            // Procesar configuración de base de datos
            $result = processDatabaseConfig($_POST);
            if ($result['success']) {
                $_SESSION['db_config'] = $_POST;
                header('Location: ?step=3');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;

        case 3:
            // Procesar configuración del sistema
            $result = processSystemConfig($_POST);
            if ($result['success']) {
                $_SESSION['system_config'] = $_POST;
                // Crear archivo .env
                if (createEnvFile()) {
                    header('Location: ?step=4');
                    exit;
                } else {
                    $errors[] = 'No se pudo crear el archivo .env';
                }
            } else {
                $errors = $result['errors'];
            }
            break;

        case 4:
            // Instalar base de datos
            $result = installDatabase();
            if ($result['success']) {
                $_SESSION['db_installed'] = true;
                header('Location: ?step=5');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;

        case 5:
            // Crear administrador
            $result = createAdminUser($_POST);
            if ($result['success']) {
                $_SESSION['admin_created'] = true;
                header('Location: ?step=6');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - ISER Authentication System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/installer.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-4 mb-3">ISER Authentication System</h1>
                    <p class="lead text-muted">Asistente de Instalación</p>
                </div>

                <!-- Progress Steps -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="steps-progress">
                            <?php foreach ($steps as $num => $name): ?>
                                <div class="step <?= $num < $currentStep ? 'completed' : ($num === $currentStep ? 'active' : '') ?>">
                                    <div class="step-number"><?= $num ?></div>
                                    <div class="step-name"><?= $name ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php foreach ($success as $msg): ?>
                            <div><?= htmlspecialchars($msg) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Step Content -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-<?= getStepIcon($currentStep) ?> me-2"></i>
                            Paso <?= $currentStep ?>: <?= $steps[$currentStep] ?>
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php include __DIR__ . "/steps/step{$currentStep}.php"; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4 text-muted">
                    <small>ISER Authentication System v1.0 &copy; <?= date('Y') ?></small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/installer.js"></script>
</body>
</html>

<?php

/**
 * Funciones auxiliares del instalador
 */

function getStepIcon($step) {
    $icons = [
        1 => 'clipboard-check',
        2 => 'database',
        3 => 'gear',
        4 => 'download',
        5 => 'person-badge',
        6 => 'check-circle'
    ];
    return $icons[$step] ?? 'circle';
}

function processDatabaseConfig($data) {
    $errors = [];

    // Validar campos requeridos
    $required = ['db_host', 'db_port', 'db_name', 'db_user'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Intentar conexión
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']}";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'] ?? '');

        // Verificar si la base de datos existe, si no, crearla
        $dbName = $data['db_name'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ["Error de conexión: " . $e->getMessage()]];
    }
}

function processSystemConfig($data) {
    $errors = [];

    // Validar campos requeridos
    $required = ['app_name', 'app_url', 'app_timezone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    return ['success' => true];
}

function createEnvFile() {
    if (!isset($_SESSION['db_config']) || !isset($_SESSION['system_config'])) {
        return false;
    }

    $db = $_SESSION['db_config'];
    $sys = $_SESSION['system_config'];

    // Generar JWT secret aleatorio
    $jwtSecret = bin2hex(random_bytes(32));

    $envContent = <<<ENV
# ISER Authentication System - Environment Configuration
# Generated on: {DATE}

# Application
APP_ENV={$sys['app_env']}
APP_DEBUG={$sys['app_debug']}
APP_NAME="{$sys['app_name']}"
APP_URL={$sys['app_url']}
APP_TIMEZONE={$sys['app_timezone']}

# Database
DB_CONNECTION=mysql
DB_HOST={$db['db_host']}
DB_PORT={$db['db_port']}
DB_DATABASE={$db['db_name']}
DB_USERNAME={$db['db_user']}
DB_PASSWORD={$db['db_pass']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX={$db['db_prefix']}

# JWT Configuration
JWT_SECRET={$jwtSecret}
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

# Session
SESSION_LIFETIME=120
SESSION_SECURE={$sys['session_secure']}
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# Security
SECURITY_PASSWORD_MIN_LENGTH=8
SECURITY_PASSWORD_REQUIRE_UPPERCASE=true
SECURITY_PASSWORD_REQUIRE_LOWERCASE=true
SECURITY_PASSWORD_REQUIRE_NUMBERS=true
SECURITY_PASSWORD_REQUIRE_SPECIAL=true
SECURITY_MAX_LOGIN_ATTEMPTS=5
SECURITY_LOCKOUT_DURATION=900

# Logging
LOG_LEVEL=info
LOG_CHANNEL=daily
LOG_MAX_FILES=30

# Email (configurar después)
MAIL_DRIVER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="{$sys['app_name']}"

# MFA
MFA_ENABLED=true
MFA_ISSUER="{$sys['app_name']}"

ENV;

    $envContent = str_replace('{DATE}', date('Y-m-d H:i:s'), $envContent);

    return file_put_contents(ENV_FILE, $envContent) !== false;
}

function installDatabase() {
    try {
        // Cargar configuración
        if (!file_exists(ENV_FILE)) {
            return ['success' => false, 'errors' => ['Archivo .env no encontrado']];
        }

        // Leer .env
        $env = parse_ini_file(ENV_FILE);

        // Conectar a base de datos
        $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
        $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Leer y ejecutar archivos SQL
        $sqlFiles = [
            BASE_DIR . '/database/schema/core.sql',
            BASE_DIR . '/database/schema/auth.sql',
            BASE_DIR . '/database/schema/roles.sql',
            BASE_DIR . '/database/schema/sessions.sql',
            BASE_DIR . '/database/schema/mfa.sql',
            BASE_DIR . '/database/schema/logs.sql',
            BASE_DIR . '/database/schema/reports.sql',
        ];

        foreach ($sqlFiles as $file) {
            if (file_exists($file)) {
                $sql = file_get_contents($file);
                // Reemplazar prefijo si existe
                if (!empty($env['DB_PREFIX'])) {
                    $sql = str_replace('iser_', $env['DB_PREFIX'], $sql);
                }
                $pdo->exec($sql);
            }
        }

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'errors' => [$e->getMessage()]];
    }
}

function createAdminUser($data) {
    $errors = [];

    // Validar campos
    $required = ['username', 'email', 'password', 'password_confirm'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    if ($data['password'] !== $data['password_confirm']) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (strlen($data['password']) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        // Conectar a base de datos
        $env = parse_ini_file(ENV_FILE);
        $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
        $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $prefix = $env['DB_PREFIX'] ?? 'iser_';

        // Crear usuario administrador
        $passwordHash = password_hash($data['password'], PASSWORD_ARGON2ID);
        $now = time();

        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}users
            (username, email, password, first_name, last_name, status, email_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', 1, ?, ?)
        ");

        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['first_name'] ?? 'Admin',
            $data['last_name'] ?? 'User',
            $now,
            $now
        ]);

        $userId = $pdo->lastInsertId();

        // Asignar rol de administrador (ID 1)
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}user_roles (user_id, role_id, assigned_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$userId, $now]);

        // Crear archivo .installed
        file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'errors' => [$e->getMessage()]];
    }
}
