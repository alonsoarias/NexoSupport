<?php
/**
 * Test Core System Loading
 *
 * Verifica que el core del sistema cargue sin errores
 */

// Define constants
define('BASE_DIR', __DIR__);
define('NEXOSUPPORT_INTERNAL', true);

echo "=== TEST: CARGA DEL CORE DEL SISTEMA ===\n\n";

// Test 1: Composer Autoloader
echo "Test 1: Cargando Composer autoloader...\n";
if (!file_exists('vendor/autoload.php')) {
    die("❌ ERROR: vendor/autoload.php no existe\n");
}
require_once('vendor/autoload.php');
echo "  ✅ Autoloader cargado\n\n";

// Test 2: Load environment
echo "Test 2: Cargando archivo .env...\n";
if (!file_exists('.env')) {
    echo "  ⚠️  WARNING: Archivo .env no existe\n";
} else {
    echo "  ✅ Archivo .env existe\n";
}
echo "\n";

// Test 3: Load lib/setup.php
echo "Test 3: Cargando lib/setup.php...\n";
try {
    ob_start();
    require_once('lib/setup.php');
    $output = ob_get_clean();

    if (!empty($output)) {
        echo "  ⚠️  Output durante carga:\n";
        echo "  " . str_replace("\n", "\n  ", trim($output)) . "\n";
    }
    echo "  ✅ lib/setup.php cargado sin errores fatales\n\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "  ❌ ERROR al cargar lib/setup.php:\n";
    echo "  Tipo: " . get_class($e) . "\n";
    echo "  Mensaje: " . $e->getMessage() . "\n";
    echo "  Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "  Stack trace:\n";
    echo "  " . str_replace("\n", "\n  ", $e->getTraceAsString()) . "\n";
    exit(1);
}

// Test 4: Verify global variables
echo "Test 4: Verificando variables globales...\n";
$required_globals = ['CFG', 'DB', 'USER'];
foreach ($required_globals as $global) {
    if (isset($GLOBALS[$global])) {
        $type = is_object($GLOBALS[$global]) ? get_class($GLOBALS[$global]) : gettype($GLOBALS[$global]);
        echo "  ✅ \${$global} = {$type}\n";
    } else {
        echo "  ❌ \${$global} no definido\n";
    }
}
echo "\n";

// Test 5: Check CFG properties
echo "Test 5: Verificando propiedades de \$CFG...\n";
if (isset($CFG)) {
    $required_props = ['dirroot', 'wwwroot', 'dbtype', 'dbhost', 'dbname', 'dbprefix'];
    foreach ($required_props as $prop) {
        if (isset($CFG->$prop)) {
            $value = is_string($CFG->$prop) ? $CFG->$prop : gettype($CFG->$prop);
            echo "  ✅ CFG->{$prop} = {$value}\n";
        } else {
            echo "  ❌ CFG->{$prop} no definido\n";
        }
    }
} else {
    echo "  ❌ \$CFG no está definido\n";
}
echo "\n";

// Test 6: Check DB connection
echo "Test 6: Verificando conexión a base de datos...\n";
if (isset($DB) && $DB !== null) {
    if ($DB instanceof \core\db\database) {
        echo "  ✅ \$DB es instancia de \\core\\db\\database\n";
        echo "  ✅ Prefijo de tablas: " . $DB->get_prefix() . "\n";

        // Try a simple query
        try {
            $pdo = $DB->get_pdo();
            echo "  ✅ PDO connection obtenida\n";
        } catch (Exception $e) {
            echo "  ⚠️  No se pudo obtener conexión PDO: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ❌ \$DB no es instancia de \\core\\db\\database\n";
    }
} else {
    echo "  ⚠️  \$DB es null (BD no conectada, normal si no está instalado)\n";
}
echo "\n";

// Test 7: Check USER
echo "Test 7: Verificando objeto \$USER...\n";
if (isset($USER)) {
    echo "  ✅ \$USER definido\n";
    if (isset($USER->id)) {
        echo "  ✅ USER->id = " . $USER->id . " (0 = no logueado)\n";
    } else {
        echo "  ❌ USER->id no definido\n";
    }
} else {
    echo "  ❌ \$USER no está definido\n";
}
echo "\n";

// Test 8: Check core functions
echo "Test 8: Verificando funciones core disponibles...\n";
$required_functions = [
    'get_string',
    'get_config',
    'set_config',
    'redirect',
    'require_login',
    'has_capability',
    'get_auth_plugin',
    'debugging',
    'clean_param',
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "  ✅ {$func}()\n";
    } else {
        echo "  ❌ {$func}() no existe\n";
    }
}
echo "\n";

// Test 9: Check core classes
echo "Test 9: Verificando clases core disponibles...\n";
$required_classes = [
    'core\db\database',
    'core\string_manager',
    'core\auth\auth_plugin_base',
    'core\rbac\access',
    'core\rbac\context',
    'core\rbac\role',
    'core\routing\router',
    'core\session\manager',
];

foreach ($required_classes as $class) {
    if (class_exists($class)) {
        echo "  ✅ {$class}\n";
    } else {
        echo "  ❌ {$class} no existe\n";
    }
}
echo "\n";

// Summary
echo "════════════════════════════════════════════\n";
echo "✅ CORE LOADING TEST COMPLETADO\n";
echo "════════════════════════════════════════════\n";
