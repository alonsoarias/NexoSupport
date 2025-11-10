<?php
/**
 * Archivo de Configuración - NexoSupport
 *
 * Este es un archivo de ejemplo. Durante la instalación, se creará
 * automáticamente un archivo config.php con los valores correctos.
 *
 * Si necesita configurar manualmente, copie este archivo a config.php
 * y modifique los valores según su entorno.
 */

// ==============================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ==============================================================

// Driver de base de datos: 'mysql', 'pgsql', o 'sqlite'
define('DB_DRIVER', 'mysql');

// Host del servidor de base de datos
define('DB_HOST', 'localhost');

// Puerto del servidor de base de datos
define('DB_PORT', 3306);

// Nombre de la base de datos
define('DB_NAME', 'nexosupport');

// Usuario de la base de datos
define('DB_USER', 'root');

// Contraseña de la base de datos
define('DB_PASS', '');

// Prefijo de tablas (opcional)
define('DB_PREFIX', '');

// Charset de la base de datos
define('DB_CHARSET', 'utf8mb4');

// ==============================================================
// CONFIGURACIÓN DEL SISTEMA
// ==============================================================

// Modo debug (true = desarrollo, false = producción)
define('DEBUG_MODE', false);

// URL base del sistema (sin trailing slash)
define('BASE_URL', 'http://localhost');

// Zona horaria
define('TIMEZONE', 'America/Mexico_City');

// Idioma por defecto
define('DEFAULT_LANG', 'es');

// ==============================================================
// SEGURIDAD
// ==============================================================

// Clave secreta para sesiones y tokens (generar una única)
define('SECRET_KEY', 'CHANGE_THIS_TO_A_RANDOM_STRING');

// ==============================================================
// NO MODIFICAR DEBAJO DE ESTA LÍNEA
// ==============================================================

// Indicador de que la configuración está lista
define('CONFIG_LOADED', true);

// Rutas del sistema
define('ROOT_PATH', __DIR__);
define('CORE_PATH', ROOT_PATH . '/core');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('DATABASE_PATH', ROOT_PATH . '/database');
