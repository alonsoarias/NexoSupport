<?php
/**
 * Script de prueba para environment_checker
 *
 * Muestra el estado completo del sistema
 *
 * Uso: php check_environment.php
 */

define('BASE_DIR', __DIR__);
define('NEXOSUPPORT_INTERNAL', true);

// Cargar autoloader
require_once(__DIR__ . '/vendor/autoload.php');

// Crear environment checker
echo "=== NexoSupport Environment Checker ===\n\n";

$checker = new \core\install\environment_checker();

echo "Estado del Sistema:\n";
echo str_repeat('-', 50) . "\n";

// Estado de instalación
echo "¿Instalado?: " . ($checker->is_installed() ? "✓ SÍ" : "✗ NO") . "\n";
echo "¿Necesita instalación?: " . ($checker->needs_install() ? "✓ SÍ" : "✗ NO") . "\n";
echo "¿Necesita actualización?: " . ($checker->needs_upgrade() ? "✓ SÍ" : "✗ NO") . "\n";
echo "\n";

// Versiones
echo "Versiones:\n";
echo str_repeat('-', 50) . "\n";
$dbVersion = $checker->get_db_version();
$codeVersion = $checker->get_code_version();
echo "Versión en BD: " . ($dbVersion !== null ? $dbVersion : "N/A") . "\n";
echo "Versión en código: " . ($codeVersion !== null ? $codeVersion : "N/A") . "\n";
echo "Release: " . $checker->get_release() . "\n";
echo "\n";

// Estado detallado
echo "Estado Detallado:\n";
echo str_repeat('-', 50) . "\n";
$state = $checker->get_state();
foreach ($state as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    } elseif (is_array($value)) {
        $value = json_encode($value);
    } elseif ($value === null) {
        $value = 'null';
    }
    echo "  $key: $value\n";
}
echo "\n";

// Errores
if ($checker->has_errors()) {
    echo "⚠ ERRORES ENCONTRADOS:\n";
    echo str_repeat('-', 50) . "\n";
    foreach ($checker->get_errors() as $error) {
        echo "  • $error\n";
    }
    echo "\n";
}

// Configuración de BD
echo "Configuración de BD:\n";
echo str_repeat('-', 50) . "\n";
$dbConfig = $checker->get_db_config();
foreach ($dbConfig as $key => $value) {
    // Ocultar contraseña
    if ($key === 'password') {
        $value = str_repeat('*', strlen($value));
    }
    echo "  $key: $value\n";
}
echo "\n";

echo "=== Fin del Reporte ===\n";
