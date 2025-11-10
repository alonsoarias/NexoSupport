<?php
/**
 * ISER Authentication System - Database Installation Endpoint
 *
 * AJAX endpoint para instalar la base de datos usando SchemaInstaller
 *
 * @package Installer
 * @author ISER Desarrollo
 * @license Propietario
 */

session_start();

// Set JSON response headers
header('Content-Type: application/json');

// Define constants
define('BASE_DIR', dirname(__DIR__));
define('SCHEMA_FILE', BASE_DIR . '/database/schema/schema.xml');

// Load Composer autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    echo json_encode([
        'success' => false,
        'errors' => ['Composer dependencies not installed']
    ]);
    exit;
}

require_once BASE_DIR . '/vendor/autoload.php';

// Import required classes
use ISER\Core\Database\SchemaInstaller;
use ISER\Core\Utils\XMLParser;

/**
 * Main installation function
 */
function installDatabase(): array
{
    try {
        // Check session config
        if (!isset($_SESSION['db_config'])) {
            return [
                'success' => false,
                'errors' => ['Configuración de base de datos no encontrada en sesión']
            ];
        }

        $db = $_SESSION['db_config'];

        // Connect to database
        $dsn = "mysql:host={$db['db_host']};port={$db['db_port']};dbname={$db['db_name']}";
        $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check schema file exists
        if (!file_exists(SCHEMA_FILE)) {
            return [
                'success' => false,
                'errors' => ['Archivo de esquema XML no encontrado: ' . SCHEMA_FILE]
            ];
        }

        // Initialize SchemaInstaller
        $prefix = $db['db_prefix'] ?? '';
        $installer = new SchemaInstaller($pdo, $prefix);

        // Install from XML
        $installer->installFromXML(SCHEMA_FILE);

        // Get created tables
        $tables = $installer->getCreatedTables();

        // Count initial data
        $xmlParser = new XMLParser();
        $xmlParser->parseFile(SCHEMA_FILE);
        $schemaData = $xmlParser->toArray();

        $tablesData = $schemaData['table'] ?? [];
        if (isset($tablesData['name'])) {
            $tablesData = [$tablesData];
        }

        $totalInitialRows = 0;
        foreach ($tablesData as $table) {
            if (isset($table['data']['row'])) {
                $rows = $table['data']['row'];
                if (!isset($rows[0])) {
                    $rows = [$rows];
                }
                $totalInitialRows += count($rows);
            }
        }

        // Mark as completed in session
        $_SESSION['step_4_completed'] = true;
        $_SESSION['created_tables'] = $tables;

        return [
            'success' => true,
            'tables' => $tables,
            'initial_data_rows' => $totalInitialRows,
            'message' => 'Base de datos instalada correctamente'
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'errors' => ['Error de base de datos: ' . $e->getMessage()]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => ['Error: ' . $e->getMessage()]
        ];
    }
}

// Execute installation
$result = installDatabase();

// Return JSON response
echo json_encode($result);
