#!/usr/bin/env php
<?php
/**
 * Script de Verificación de NexoSupport
 *
 * Este script verifica que todos los requisitos estén cumplidos
 * y que la instalación esté configurada correctamente.
 *
 * Ejecutar: php check.php
 *
 * @package NexoSupport
 */

echo "================================================\n";
echo "  NexoSupport - Verificación del Sistema\n";
echo "================================================\n\n";

$errors = [];
$warnings = [];
$ok = [];

// ============================================
// 1. Versión de PHP
// ============================================
echo "[1/10] Verificando versión de PHP...\n";
$phpversion = phpversion();
if (version_compare($phpversion, '8.1.0', '>=')) {
    $ok[] = "PHP $phpversion ✓";
} else {
    $errors[] = "PHP >= 8.1 requerido (encontrado: $phpversion)";
}

// ============================================
// 2. Extensiones PHP requeridas
// ============================================
echo "[2/10] Verificando extensiones PHP...\n";
$required_extensions = ['PDO', 'pdo_mysql', 'json', 'mbstring', 'session'];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $ok[] = "Extensión $ext ✓";
    } else {
        $errors[] = "Extensión PHP '$ext' no está instalada";
    }
}

// ============================================
// 3. Composer Autoloader
// ============================================
echo "[3/10] Verificando Composer autoloader...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $ok[] = "Composer autoloader encontrado ✓";
} else {
    $errors[] = "Composer autoloader no encontrado. Ejecutar: composer install";
}

// ============================================
// 4. Estructura de directorios
// ============================================
echo "[4/10] Verificando estructura de directorios...\n";
$required_dirs = [
    'public_html',
    'lib',
    'lib/classes',
    'lib/db',
    'lib/lang',
    'auth',
    'auth/manual',
    'install',
    'install/stages',
    'var',
    'var/cache',
    'var/logs',
    'var/sessions',
];

foreach ($required_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $ok[] = "Directorio $dir ✓";
    } else {
        $errors[] = "Directorio '$dir' no encontrado";
    }
}

// ============================================
// 5. Permisos de escritura
// ============================================
echo "[5/10] Verificando permisos de escritura...\n";
$writable_dirs = [
    'var',
    'var/cache',
    'var/logs',
    'var/sessions',
];

foreach ($writable_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_writable($path)) {
        $ok[] = "Directorio $dir es escribible ✓";
    } else {
        $errors[] = "Directorio '$dir' no es escribible. Ejecutar: chmod -R 777 var/";
    }
}

// ============================================
// 6. Archivos críticos
// ============================================
echo "[6/10] Verificando archivos críticos...\n";
$required_files = [
    'public_html/index.php',
    'public_html/.htaccess',
    'lib/setup.php',
    'lib/functions.php',
    'lib/components.json',
    'install/index.php',
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $ok[] = "Archivo $file ✓";
    } else {
        $errors[] = "Archivo '$file' no encontrado";
    }
}

// ============================================
// 7. Clases del core
// ============================================
echo "[7/10] Verificando clases del core...\n";
$core_classes = [
    'lib/classes/db/database.php',
    'lib/classes/db/ddl_manager.php',
    'lib/classes/plugin/manager.php',
    'lib/classes/plugininfo/base.php',
    'lib/classes/plugininfo/auth.php',
    'lib/classes/routing/router.php',
];

foreach ($core_classes as $class) {
    $path = __DIR__ . '/' . $class;
    if (file_exists($path)) {
        // Verificar sintaxis
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($path), $output, $return);
        if ($return === 0) {
            $ok[] = "Clase $class ✓";
        } else {
            $errors[] = "Error de sintaxis en '$class'";
        }
    } else {
        $errors[] = "Clase '$class' no encontrada";
    }
}

// ============================================
// 8. Plugin auth_manual
// ============================================
echo "[8/10] Verificando plugin auth_manual...\n";
$plugin_files = [
    'auth/manual/classes/plugin.php',
    'auth/manual/version.php',
    'auth/manual/lang/es/auth_manual.php',
];

foreach ($plugin_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $ok[] = "Plugin file $file ✓";
    } else {
        $errors[] = "Archivo de plugin '$file' no encontrado";
    }
}

// ============================================
// 9. Archivos de idioma
// ============================================
echo "[9/10] Verificando archivos de idioma...\n";
$lang_files = [
    'lib/lang/es/core.php',
    'lib/lang/en/core.php',
];

foreach ($lang_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $ok[] = "Archivo de idioma $file ✓";
    } else {
        $warnings[] = "Archivo de idioma '$file' no encontrado";
    }
}

// ============================================
// 10. Estado de instalación
// ============================================
echo "[10/10] Verificando estado de instalación...\n";
if (file_exists(__DIR__ . '/.installed')) {
    $ok[] = "Sistema instalado ✓";
} else {
    $warnings[] = "Sistema no instalado (es normal si no has ejecutado el instalador aún)";
}

// ============================================
// RESUMEN
// ============================================
echo "\n================================================\n";
echo "  RESUMEN\n";
echo "================================================\n\n";

if (count($ok) > 0) {
    echo "✓ ÉXITOS (" . count($ok) . "):\n";
    foreach ($ok as $item) {
        echo "  ✓ $item\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $item) {
        echo "  ⚠ $item\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "✗ ERRORES (" . count($errors) . "):\n";
    foreach ($errors as $item) {
        echo "  ✗ $item\n";
    }
    echo "\n";
    echo "ACCIÓN REQUERIDA:\n";
    echo "Por favor, corrige los errores antes de continuar.\n\n";
    exit(1);
} else {
    echo "================================================\n";
    echo "✓ Todos los requisitos están cumplidos!\n";
    echo "================================================\n\n";

    if (!file_exists(__DIR__ . '/.installed')) {
        echo "Siguiente paso: Accede al instalador web:\n";
        echo "  1. Configura tu servidor web (Apache/Nginx)\n";
        echo "  2. Asegúrate de que el document root apunta a public_html/\n";
        echo "  3. Accede a http://tu-dominio/install\n\n";
        echo "Consulta INSTALL.md para instrucciones detalladas.\n\n";
    } else {
        echo "El sistema ya está instalado.\n";
        echo "Accede a: http://tu-dominio/\n\n";
    }

    exit(0);
}
