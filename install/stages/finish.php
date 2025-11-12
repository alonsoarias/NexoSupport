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
        // Stage 6: Security
        $securityMaxAttempts = $_SESSION['security_max_login_attempts'] ?? 5;
        $securityLockoutTime = $_SESSION['security_lockout_time'] ?? 900;
        $securityPasswordMinLength = $_SESSION['security_password_min_length'] ?? 8;
        $securityRequireUppercase = $_SESSION['security_require_uppercase'] ?? 'true';
        $securityRequireNumbers = $_SESSION['security_require_numbers'] ?? 'true';
        $securityRequireSpecialChars = $_SESSION['security_require_special_chars'] ?? 'false';
        $recaptchaEnabled = $_SESSION['recaptcha_enabled'] ?? 'false';
        $recaptchaSiteKey = $_SESSION['recaptcha_site_key'] ?? '';
        $recaptchaSecretKey = $_SESSION['recaptcha_secret_key'] ?? '';
        $jwtExpiration = $_SESSION['security_jwt_expiration'] ?? 3600;

        // Stage 7: Logging
        $logChannel = $_SESSION['log_channel'] ?? 'daily';
        $logLevel = $_SESSION['log_level'] ?? 'info';
        $logPath = $_SESSION['log_path'] ?? 'var/logs/iser.log';
        $logMaxFiles = $_SESSION['log_max_files'] ?? 14;
        $logMaxSize = $_SESSION['log_max_size'] ?? 10;
        $logQueryEnabled = $_SESSION['log_query_enabled'] ?? 'false';

        // Stage 8: Email
        $mailDriver = $_SESSION['mail_driver'] ?? 'smtp';
        $mailHost = $_SESSION['mail_host'] ?? 'localhost';
        $mailPort = $_SESSION['mail_port'] ?? 587;
        $mailUsername = $_SESSION['mail_username'] ?? '';
        $mailPassword = $_SESSION['mail_password'] ?? '';
        $mailEncryption = $_SESSION['mail_encryption'] ?? 'tls';
        $mailFromAddress = $_SESSION['mail_from_address'] ?? 'noreply@localhost';
        $mailFromName = $_SESSION['mail_from_name'] ?? 'NexoSupport';
        $mailgunDomain = $_SESSION['mailgun_domain'] ?? '';
        $mailgunSecret = $_SESSION['mailgun_secret'] ?? '';
        $postmarkToken = $_SESSION['postmark_token'] ?? '';

        // Stage 9: Cache/Storage
        $cacheDriver = $_SESSION['cache_driver'] ?? 'file';
        $cacheTtl = $_SESSION['cache_ttl'] ?? 3600;
        $cachePrefix = $_SESSION['cache_prefix'] ?? 'nexo_';
        $redisHost = $_SESSION['redis_host'] ?? '127.0.0.1';
        $redisPort = $_SESSION['redis_port'] ?? 6379;
        $redisPassword = $_SESSION['redis_password'] ?? '';
        $memcachedHost = $_SESSION['memcached_host'] ?? '127.0.0.1';
        $memcachedPort = $_SESSION['memcached_port'] ?? 11211;
        $storageDriver = $_SESSION['storage_driver'] ?? 'local';
        $avatarPath = $_SESSION['avatar_path'] ?? 'uploads/avatars';
        $avatarMaxSize = $_SESSION['avatar_max_size'] ?? 2;
        $avatarAllowedTypes = $_SESSION['avatar_allowed_types'] ?? 'jpg,jpeg,png,gif';
        $uploadMaxSize = $_SESSION['upload_max_size'] ?? 10;
        $uploadAllowedExtensions = $_SESSION['upload_allowed_extensions'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx';

        // Stage 10: Regional
        $appTimezone = $_SESSION['regional_timezone'] ?? 'America/Bogota';
        $defaultLocale = $_SESSION['regional_locale'] ?? 'es';
        $dateFormat = $_SESSION['regional_date_format'] ?? 'd/m/Y';
        $timeFormat = $_SESSION['regional_time_format'] ?? 'H:i';
        $numberDecimalPlaces = $_SESSION['regional_decimal_places'] ?? 2;
        $numberDecimalSeparator = $_SESSION['regional_decimal_sep'] ?? ',';
        $numberThousandsSeparator = $_SESSION['regional_thousands_sep'] ?? '.';
        $currency = $_SESSION['regional_currency'] ?? 'COP';

        // Current timestamp
        $installedAt = date('Y-m-d H:i:s');

        $envContent = <<<ENV
# ============================================================
# CONFIGURACIÓN DE NEXOSUPPORT
# Generado automáticamente: {$installedAt}
# ============================================================

# ============================================================
# APPLICATION SETTINGS
# ============================================================
APP_ENV=production
APP_DEBUG=false
APP_KEY={$appKey}
BASE_URL={$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}
APP_TIMEZONE={$appTimezone}
DEFAULT_LOCALE={$defaultLocale}

# ============================================================
# DATABASE CONFIGURATION
# ============================================================
DB_CONNECTION={$_SESSION['db_driver']}
DB_HOST={$_SESSION['db_host']}
DB_PORT={$_SESSION['db_port']}
DB_DATABASE={$_SESSION['db_name']}
DB_USERNAME={$_SESSION['db_user']}
DB_PASSWORD={$_SESSION['db_pass']}
DB_PREFIX={$_SESSION['db_prefix']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ============================================================
# SECURITY CONFIGURATION
# ============================================================
# Login Security
SECURITY_MAX_LOGIN_ATTEMPTS={$securityMaxAttempts}
SECURITY_LOCKOUT_TIME={$securityLockoutTime}

# Password Policy
SECURITY_PASSWORD_MIN_LENGTH={$securityPasswordMinLength}
SECURITY_REQUIRE_UPPERCASE={$securityRequireUppercase}
SECURITY_REQUIRE_NUMBERS={$securityRequireNumbers}
SECURITY_REQUIRE_SPECIAL_CHARS={$securityRequireSpecialChars}

# reCAPTCHA
RECAPTCHA_ENABLED={$recaptchaEnabled}
RECAPTCHA_SITE_KEY={$recaptchaSiteKey}
RECAPTCHA_SECRET_KEY={$recaptchaSecretKey}

# JWT (JSON Web Tokens)
JWT_SECRET={$jwtSecret}
JWT_ALGORITHM=HS256
JWT_EXPIRATION={$jwtExpiration}
JWT_REFRESH_EXPIRATION=604800

# ============================================================
# SESSION CONFIGURATION
# ============================================================
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# ============================================================
# LOGGING CONFIGURATION
# ============================================================
LOG_CHANNEL={$logChannel}
LOG_LEVEL={$logLevel}
LOG_PATH={$logPath}
LOG_MAX_FILES={$logMaxFiles}
LOG_MAX_SIZE={$logMaxSize}
LOG_QUERY_ENABLED={$logQueryEnabled}

# ============================================================
# EMAIL CONFIGURATION
# ============================================================
MAIL_DRIVER={$mailDriver}
MAIL_HOST={$mailHost}
MAIL_PORT={$mailPort}
MAIL_USERNAME={$mailUsername}
MAIL_PASSWORD={$mailPassword}
MAIL_ENCRYPTION={$mailEncryption}
MAIL_FROM_ADDRESS={$mailFromAddress}
MAIL_FROM_NAME={$mailFromName}

# Mailgun Configuration (if using mailgun driver)
MAILGUN_DOMAIN={$mailgunDomain}
MAILGUN_SECRET={$mailgunSecret}

# Postmark Configuration (if using postmark driver)
POSTMARK_TOKEN={$postmarkToken}

# ============================================================
# CACHE CONFIGURATION
# ============================================================
CACHE_DRIVER={$cacheDriver}
CACHE_TTL={$cacheTtl}
CACHE_PREFIX={$cachePrefix}

# Redis Configuration (if using redis driver)
REDIS_HOST={$redisHost}
REDIS_PORT={$redisPort}
REDIS_PASSWORD={$redisPassword}

# Memcached Configuration (if using memcached driver)
MEMCACHED_HOST={$memcachedHost}
MEMCACHED_PORT={$memcachedPort}

# ============================================================
# STORAGE & UPLOAD CONFIGURATION
# ============================================================
STORAGE_DRIVER={$storageDriver}

# Avatar Settings
AVATAR_PATH={$avatarPath}
AVATAR_MAX_SIZE={$avatarMaxSize}
AVATAR_ALLOWED_TYPES={$avatarAllowedTypes}

# Upload Settings
UPLOAD_MAX_SIZE={$uploadMaxSize}
UPLOAD_ALLOWED_EXTENSIONS={$uploadAllowedExtensions}

# ============================================================
# REGIONAL SETTINGS
# ============================================================
DATE_FORMAT={$dateFormat}
TIME_FORMAT={$timeFormat}
NUMBER_DECIMAL_PLACES={$numberDecimalPlaces}
NUMBER_DECIMAL_SEPARATOR={$numberDecimalSeparator}
NUMBER_THOUSANDS_SEPARATOR={$numberThousandsSeparator}
CURRENCY={$currency}

# ============================================================
# INSTALLATION STATUS
# ============================================================
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
        <li><strong>.env</strong> - Archivo de configuración completo generado con <?= substr_count($envContent, "\n") ?> líneas</li>
        <li><strong><?= count($pdo->query("SHOW TABLES LIKE '{$_SESSION['db_prefix']}%'")->fetchAll()) ?> tablas</strong> en la base de datos</li>
        <li><strong>Usuario administrador</strong> creado exitosamente</li>
    </ul>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="bi bi-gear"></i> Resumen de Configuración</h5>
    <div class="row small">
        <div class="col-md-6">
            <strong>Base de Datos:</strong>
            <ul class="mb-2">
                <li>Driver: <?= htmlspecialchars($driver) ?></li>
                <li>Host: <?= htmlspecialchars($_SESSION['db_host']) ?></li>
                <li>Base de datos: <?= htmlspecialchars($_SESSION['db_name']) ?></li>
            </ul>

            <strong>Seguridad:</strong>
            <ul class="mb-2">
                <li>Intentos máx. login: <?= $securityMaxAttempts ?></li>
                <li>Tiempo bloqueo: <?= $securityLockoutTime ?>s</li>
                <li>Long. mín. contraseña: <?= $securityPasswordMinLength ?></li>
                <li>reCAPTCHA: <?= $recaptchaEnabled === 'true' ? 'Habilitado' : 'Deshabilitado' ?></li>
            </ul>

            <strong>Regional:</strong>
            <ul class="mb-2">
                <li>Zona horaria: <?= htmlspecialchars($appTimezone) ?></li>
                <li>Idioma: <?= htmlspecialchars($defaultLocale) ?></li>
                <li>Moneda: <?= htmlspecialchars($currency) ?></li>
                <li>Formato fecha: <?= htmlspecialchars($dateFormat) ?></li>
            </ul>
        </div>

        <div class="col-md-6">
            <strong>Email:</strong>
            <ul class="mb-2">
                <li>Driver: <?= htmlspecialchars($mailDriver) ?></li>
                <li>Host: <?= htmlspecialchars($mailHost) ?>:<?= $mailPort ?></li>
                <li>Desde: <?= htmlspecialchars($mailFromAddress) ?></li>
            </ul>

            <strong>Caché:</strong>
            <ul class="mb-2">
                <li>Driver: <?= htmlspecialchars($cacheDriver) ?></li>
                <li>TTL: <?= $cacheTtl ?>s</li>
                <li>Prefijo: <?= htmlspecialchars($cachePrefix) ?></li>
            </ul>

            <strong>Logging:</strong>
            <ul class="mb-2">
                <li>Canal: <?= htmlspecialchars($logChannel) ?></li>
                <li>Nivel: <?= htmlspecialchars($logLevel) ?></li>
                <li>Archivos máx.: <?= $logMaxFiles ?></li>
            </ul>

            <strong>Almacenamiento:</strong>
            <ul class="mb-0">
                <li>Driver: <?= htmlspecialchars($storageDriver) ?></li>
                <li>Tamaño máx. upload: <?= $uploadMaxSize ?>MB</li>
                <li>Tamaño máx. avatar: <?= $avatarMaxSize ?>MB</li>
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
