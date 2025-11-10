<?php
/**
 * Debug Authentication Script
 *
 * This script helps debug authentication issues by:
 * 1. Checking if the database connection works
 * 2. Listing all users in the database
 * 3. Showing password hashes
 * 4. Testing password verification
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
echo "  NEXOSUPPORT - AUTHENTICATION DEBUG SCRIPT\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Initialize the system
    echo "1. Initializing system...\n";
    $app = new Bootstrap(BASE_DIR);
    $app->init();
    echo "   ✓ System initialized\n\n";

    $db = $app->getDatabase();
    if (!$db) {
        die("   ✗ ERROR: Could not get database instance\n");
    }
    echo "   ✓ Database connection established\n\n";

    $userManager = new UserManager($db);

    // List all users
    echo "2. Listing all users in database:\n";
    echo str_repeat("-", 70) . "\n";

    $sql = "SELECT id, username, email, status, created_at, deleted_at FROM {$db->table('users')} ORDER BY id";
    $users = $db->getConnection()->fetchAll($sql);

    if (empty($users)) {
        echo "   ⚠ No users found in database!\n\n";
        echo "   Creating default admin user...\n";

        $userData = [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'status' => 'active'
        ];

        $userId = $userManager->create($userData);
        if ($userId) {
            echo "   ✓ Admin user created with ID: {$userId}\n";
            // Fetch the user again
            $users = $db->getConnection()->fetchAll($sql);
        } else {
            die("   ✗ Failed to create admin user\n");
        }
    }

    foreach ($users as $user) {
        echo "\n   User ID: {$user['id']}\n";
        echo "   Username: {$user['username']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Status: {$user['status']}\n";
        echo "   Created: " . date('Y-m-d H:i:s', $user['created_at']) . "\n";
        echo "   Deleted: " . ($user['deleted_at'] ? date('Y-m-d H:i:s', $user['deleted_at']) : 'No') . "\n";
        echo "   " . str_repeat("-", 66) . "\n";
    }

    // Test password for admin user
    echo "\n3. Testing password verification for 'admin' user:\n";
    echo str_repeat("-", 70) . "\n";

    $adminUser = $userManager->getUserByUsername('admin');
    if (!$adminUser) {
        echo "   ✗ Admin user not found\n";
    } else {
        echo "   ✓ Admin user found (ID: {$adminUser['id']})\n";
        echo "   Password hash in DB: " . substr($adminUser['password'], 0, 30) . "...\n";

        // Test with password 'admin123'
        $testPassword = 'admin123';
        $isValid = Helpers::verifyPassword($testPassword, $adminUser['password']);

        echo "   Testing password '{$testPassword}': " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n";

        if (!$isValid) {
            echo "\n   ⚠ Password is invalid! Updating password to 'admin123'...\n";
            $userManager->update($adminUser['id'], [
                'password' => $testPassword,
                'status' => 'active'
            ]);
            echo "   ✓ Password updated!\n";

            // Verify again
            $adminUser = $userManager->getUserByUsername('admin');
            $isValid = Helpers::verifyPassword($testPassword, $adminUser['password']);
            echo "   Re-testing password: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n";
        }
    }

    // Summary
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "  SUMMARY & RECOMMENDATIONS\n";
    echo str_repeat("=", 70) . "\n\n";

    echo "Login Credentials to Use:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n";
    echo "  URL: https://nexosupport.localhost.com/login\n\n";

    echo "Next Steps:\n";
    echo "1. Try logging in with the credentials above\n";
    echo "2. If it still fails, check PHP error logs at:\n";
    echo "   C:\\MAMP\\logs\\php_error.log (Windows)\n";
    echo "3. Look for lines starting with [AuthController] or [AuthService]\n";
    echo "4. Share those log lines for further debugging\n\n";

    echo str_repeat("=", 70) . "\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
