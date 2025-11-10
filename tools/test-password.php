<?php
/**
 * Test Password Verification
 *
 * This script tests password verification with different passwords
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));

// Load Composer autoloader
require_once BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\Core\Utils\Helpers;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "  PASSWORD VERIFICATION TEST\n";
echo str_repeat("=", 70) . "\n\n";

// Get password from command line or use default
$testPassword = $argv[1] ?? 'Admin.123+';

echo "Testing password: '{$testPassword}'\n\n";

try {
    // Initialize the system
    $app = new Bootstrap(BASE_DIR);
    $app->init();

    $db = $app->getDatabase();
    $userManager = new UserManager($db);

    // Get admin user
    $adminUser = $userManager->getUserByUsername('admin');
    if (!$adminUser) {
        die("ERROR: Admin user not found\n");
    }

    echo "User found:\n";
    echo "  ID: {$adminUser['id']}\n";
    echo "  Username: {$adminUser['username']}\n";
    echo "  Email: {$adminUser['email']}\n";
    echo "  Status: {$adminUser['status']}\n\n";

    echo "Current password hash in DB:\n";
    echo "  " . $adminUser['password'] . "\n\n";

    // Test current password
    echo "Testing password '{$testPassword}' against current hash:\n";
    $isValid = Helpers::verifyPassword($testPassword, $adminUser['password']);
    echo "  Result: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n\n";

    if (!$isValid) {
        echo "Password doesn't match. Updating to '{$testPassword}'...\n";
        $newHash = Helpers::hashPassword($testPassword);
        echo "New hash: {$newHash}\n\n";

        $userManager->update($adminUser['id'], [
            'password' => $testPassword,  // Will be hashed by UserManager
            'status' => 'active'
        ]);

        // Verify again
        $adminUser = $userManager->getUserByUsername('admin');
        echo "Password updated. New hash in DB:\n";
        echo "  " . $adminUser['password'] . "\n\n";

        echo "Re-testing password '{$testPassword}':\n";
        $isValid = Helpers::verifyPassword($testPassword, $adminUser['password']);
        echo "  Result: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n\n";
    }

    echo str_repeat("=", 70) . "\n";
    echo "NOW TRY TO LOGIN WITH:\n";
    echo "  Username: admin\n";
    echo "  Password: {$testPassword}\n";
    echo str_repeat("=", 70) . "\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
