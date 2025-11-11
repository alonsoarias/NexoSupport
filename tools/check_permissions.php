<?php
/**
 * Script de diagnóstico: Verificar permisos en BD
 */

define('BASE_DIR', dirname(__DIR__));

require_once BASE_DIR . '/vendor/autoload.php';

// Cargar .env
$envFile = BASE_DIR . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: Archivo .env no encontrado\n");
}

$envContent = file_get_contents($envFile);
$lines = explode("\n", $envContent);
$config = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') {
        continue;
    }
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
}

// Configurar conexión
$host = $config['DB_HOST'] ?? 'localhost';
$port = $config['DB_PORT'] ?? '3306';
$database = $config['DB_DATABASE'] ?? '';
$username = $config['DB_USERNAME'] ?? '';
$password = $config['DB_PASSWORD'] ?? '';
$prefix = $config['DB_PREFIX'] ?? 'iser_';

echo "=== DIAGNÓSTICO DE PERMISOS ===\n\n";
echo "Configuración de BD:\n";
echo "  Host: $host:$port\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
echo "  Prefix: $prefix\n\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Conexión a BD exitosa\n\n";

    // Verificar si existe la tabla
    $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}permissions'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "❌ ERROR: La tabla {$prefix}permissions NO EXISTE\n";
        echo "\nTablas disponibles:\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
        exit(1);
    }

    echo "✓ Tabla {$prefix}permissions existe\n\n";

    // Contar permisos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$prefix}permissions");
    $count = $stmt->fetch();

    echo "Total de permisos: " . $count['total'] . "\n\n";

    if ($count['total'] == 0) {
        echo "⚠️  ADVERTENCIA: No hay permisos en la base de datos\n";
        echo "   Los permisos deberían haberse insertado durante la instalación.\n";
        exit(1);
    }

    // Mostrar permisos agrupados por módulo
    $stmt = $pdo->query("SELECT module, COUNT(*) as count FROM {$prefix}permissions GROUP BY module ORDER BY module");
    $modules = $stmt->fetchAll();

    echo "Permisos por módulo:\n";
    foreach ($modules as $module) {
        echo "  - {$module['module']}: {$module['count']} permisos\n";
    }
    echo "\n";

    // Mostrar primeros 10 permisos
    echo "Primeros 10 permisos:\n";
    $stmt = $pdo->query("SELECT id, name, slug, module FROM {$prefix}permissions ORDER BY module, name LIMIT 10");
    $permissions = $stmt->fetchAll();

    foreach ($permissions as $perm) {
        echo sprintf("  [%3d] %-30s %-25s (%s)\n",
            $perm['id'],
            $perm['name'],
            $perm['slug'],
            $perm['module']
        );
    }

    echo "\n✓ Todo parece estar correcto en la base de datos\n";

} catch (PDOException $e) {
    echo "❌ ERROR DE BD: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
