<?php

/**
 * Migration Runner
 * Run this script to create the missing login_attempts table
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;

// Define BASE_DIR constant
define('BASE_DIR', dirname(__DIR__, 2));

echo "=============================================================\n";
echo "  MIGRATION: Create login_attempts Table\n";
echo "=============================================================\n\n";

try {
    // Initialize application
    $app = Bootstrap::getInstance(BASE_DIR);
    $app->init();

    // Get database instance
    $database = $app->getDatabase();

    // Read SQL migration file
    $sqlFile = __DIR__ . '/001_create_login_attempts_table.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);

    echo "Reading migration file: 001_create_login_attempts_table.sql\n";
    echo "Creating table: login_attempts\n\n";

    // Get table name with prefix
    $tableName = $database->table('login_attempts');

    echo "Full table name: {$tableName}\n\n";

    // Replace placeholder with actual table name
    $sql = str_replace('ndgf_login_attempts', $tableName, $sql);

    // Execute migration
    $pdo = $database->getConnection()->getPdo();
    $pdo->exec($sql);

    echo "✓ Migration completed successfully!\n";
    echo "\nTable '{$tableName}' has been created.\n";

    // Verify table exists
    $result = $pdo->query("SHOW TABLES LIKE '{$tableName}'")->fetch();

    if ($result) {
        echo "\n✓ Table verified in database.\n";

        // Show table structure
        echo "\nTable structure:\n";
        echo str_repeat("-", 60) . "\n";
        $columns = $pdo->query("DESCRIBE {$tableName}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo sprintf("  %-20s %-30s %s\n",
                $column['Field'],
                $column['Type'],
                $column['Key'] ? "({$column['Key']})" : ''
            );
        }
        echo str_repeat("-", 60) . "\n";
    } else {
        echo "\n✗ Warning: Could not verify table creation.\n";
    }

} catch (Exception $e) {
    echo "\n✗ Error running migration:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=============================================================\n";
echo "  Migration completed. You can now try logging in again.\n";
echo "=============================================================\n";
