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

        // Hash password con bcrypt (cost 12)
        $passwordHash = password_hash($_SESSION['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
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

        // Generar claves de seguridad únicas
        $jwtSecret = $_SESSION['security_jwt_secret'] ?? bin2hex(random_bytes(32));
        $appKey = bin2hex(random_bytes(32));

        // Get values from all stages with defaults
        // === SECURITY (Essential only) ===
        $securityMaxAttempts = $_SESSION['security_max_login_attempts'] ?? 5;
        $securityLockoutTime = $_SESSION['security_lockout_time'] ?? 900;
        $securityPasswordMinLength = $_SESSION['security_password_min_length'] ?? 8;
        $recaptchaEnabled = $_SESSION['recaptcha_enabled'] ?? 'false';
        $rateLimitEnabled = $_SESSION['rate_limit_enabled'] ?? 'true';
        $jwtExpiration = $_SESSION['security_jwt_expiration'] ?? 3600;

        // === LOGGING (Essential only) ===
        $logChannel = $_SESSION['log_channel'] ?? 'daily';
        $logLevel = $_SESSION['log_level'] ?? 'info';
        $logPath = $_SESSION['log_path'] ?? 'var/logs/iser.log';

        // === EMAIL (Essential only) ===
        $mailDriver = $_SESSION['mail_driver'] ?? 'smtp';
        $mailHost = $_SESSION['mail_host'] ?? 'localhost';
        $mailFromAddress = $_SESSION['mail_from_address'] ?? 'noreply@localhost';

        // === CACHE (Essential only) ===
        $cacheDriver = $_SESSION['cache_driver'] ?? 'file';
        $cacheTtl = $_SESSION['cache_ttl'] ?? 3600;

        // === REGIONAL (Essential only) ===
        $appTimezone = $_SESSION['regional_timezone'] ?? 'America/Bogota';
        $defaultLocale = $_SESSION['regional_locale'] ?? 'es';

        // Current timestamp
        $installedAt = date('Y-m-d H:i:s');

        $envContent = <<<ENV
# ============================================================
# NEXOSUPPORT - ESSENTIAL CONFIGURATION
# Auto-generated: {$installedAt}
# ============================================================

# APPLICATION (6 variables)
APP_ENV=production
APP_DEBUG=false
APP_KEY={$appKey}
BASE_URL={$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}
APP_TIMEZONE={$appTimezone}
DEFAULT_LOCALE={$defaultLocale}

# DATABASE (9 variables)
DB_CONNECTION={$_SESSION['db_driver']}
DB_HOST={$_SESSION['db_host']}
DB_PORT={$_SESSION['db_port']}
DB_DATABASE={$_SESSION['db_name']}
DB_USERNAME={$_SESSION['db_user']}
DB_PASSWORD={$_SESSION['db_pass']}
DB_PREFIX={$_SESSION['db_prefix']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# JWT (3 variables)
JWT_SECRET={$jwtSecret}
JWT_ALGORITHM=HS256
JWT_EXPIRATION={$jwtExpiration}

# SESSION (4 variables)
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# LOGGING (3 variables)
LOG_CHANNEL={$logChannel}
LOG_LEVEL={$logLevel}
LOG_PATH={$logPath}

# EMAIL (3 variables)
MAIL_DRIVER={$mailDriver}
MAIL_HOST={$mailHost}
MAIL_FROM_ADDRESS={$mailFromAddress}

# CACHE (2 variables)
CACHE_DRIVER={$cacheDriver}
CACHE_TTL={$cacheTtl}

# SECURITY (5 variables)
SECURITY_MAX_LOGIN_ATTEMPTS={$securityMaxAttempts}
SECURITY_LOCKOUT_TIME={$securityLockoutTime}
SECURITY_PASSWORD_MIN_LENGTH={$securityPasswordMinLength}
RECAPTCHA_ENABLED={$recaptchaEnabled}
RATE_LIMIT_ENABLED={$rateLimitEnabled}

# INSTALLATION (2 variables)
INSTALLED=true
INSTALLED_AT={$installedAt}

ENV;

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
    <p class="text-muted">NexoSupport ha sido instalado correctamente con todas sus configuraciones.</p>
</div>

<div class="alert alert-success mt-4">
    <h5><i class="bi bi-check-circle"></i> Archivos Creados</h5>
    <ul class="mb-0">
        <li><strong>.env</strong> - Configuración esencial con 40 variables (simplificada)</li>
        <li><strong><?= count($pdo->query("SHOW TABLES LIKE '{$_SESSION['db_prefix']}%'")->fetchAll()) ?> tablas</strong> en la base de datos</li>
        <li><strong>Usuario administrador</strong> creado exitosamente</li>
    </ul>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="bi bi-gear"></i> Resumen de Configuración Esencial</h5>
    <div class="row small">
        <div class="col-md-6">
            <strong>Aplicación:</strong>
            <ul class="mb-2">
                <li>URL Base: <?= htmlspecialchars($_SERVER['REQUEST_SCHEME']) ?>://<?= htmlspecialchars($_SERVER['HTTP_HOST']) ?></li>
                <li>Zona horaria: <?= htmlspecialchars($appTimezone) ?></li>
                <li>Idioma: <?= htmlspecialchars($defaultLocale) ?></li>
            </ul>

            <strong>Base de Datos:</strong>
            <ul class="mb-2">
                <li>Driver: <?= htmlspecialchars($driver) ?></li>
                <li>Host: <?= htmlspecialchars($_SESSION['db_host']) ?></li>
                <li>Base de datos: <?= htmlspecialchars($_SESSION['db_name']) ?></li>
            </ul>

            <strong>Seguridad:</strong>
            <ul class="mb-0">
                <li>Intentos máx. login: <?= $securityMaxAttempts ?></li>
                <li>Tiempo bloqueo: <?= $securityLockoutTime ?>s</li>
                <li>Long. mín. contraseña: <?= $securityPasswordMinLength ?></li>
            </ul>
        </div>

        <div class="col-md-6">
            <strong>Email:</strong>
            <ul class="mb-2">
                <li>Driver: <?= htmlspecialchars($mailDriver) ?></li>
                <li>Host: <?= htmlspecialchars($mailHost) ?></li>
                <li>Desde: <?= htmlspecialchars($mailFromAddress) ?></li>
            </ul>

            <strong>Caché y Logging:</strong>
            <ul class="mb-2">
                <li>Caché: <?= htmlspecialchars($cacheDriver) ?> (TTL: <?= $cacheTtl ?>s)</li>
                <li>Logs: <?= htmlspecialchars($logLevel) ?> a <?= htmlspecialchars($logPath) ?></li>
            </ul>

            <strong>Sesiones:</strong>
            <ul class="mb-0">
                <li>Duración: 7200s</li>
                <li>JWT expira en: <?= $jwtExpiration ?>s</li>
            </ul>
        </div>
    </div>
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
