<?php
/**
 * Clear Opcache - Limpiar caché de PHP
 *
 * Ejecutar este archivo desde el navegador para limpiar el cache de Opcache
 * URL: https://nexosupport.localhost.com/clear-cache.php
 */

echo "<h1>Clear Opcache</h1>";

// Limpiar Opcache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green;'>✓ Opcache cleared successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to clear opcache</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Opcache is not enabled</p>";
}

// Información de Opcache
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "<h2>Opcache Status:</h2>";
    echo "<pre>";
    echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo "Cache Full: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
    echo "Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "Misses: " . $status['opcache_statistics']['misses'] . "\n";
    echo "Memory Used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
    echo "</pre>";
}

// Verificar archivo Router.php
echo "<h2>Router.php Status:</h2>";
$routerFile = dirname(__DIR__) . '/core/Routing/Router.php';
echo "<p>File: $routerFile</p>";
echo "<p>Last Modified: " . date('Y-m-d H:i:s', filemtime($routerFile)) . "</p>";

// Leer líneas específicas del Router.php
$lines = file($routerFile);
echo "<h3>Line 287-288:</h3>";
echo "<pre>";
echo htmlspecialchars($lines[286] ?? ''); // Línea 287 (índice 286)
echo htmlspecialchars($lines[287] ?? ''); // Línea 288 (índice 287)
echo "</pre>";

echo "<hr>";
echo "<p><a href='/admin/users'>← Volver a gestión de usuarios</a></p>";
