<?php

/**
 * Update Admin Password to Admin.123+
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;

// Define BASE_DIR constant
define('BASE_DIR', dirname(__DIR__));

$password = $argv[1] ?? 'Admin.123+';

echo "\n";
echo "======================================================================\n";
echo "  UPDATE ADMIN PASSWORD\n";
echo "======================================================================\n\n";

echo "Setting password to: '{$password}'\n\n";

try {
    // Initialize application
    $app = Bootstrap::getInstance(BASE_DIR);
    $app->init();

    // Get database and user manager
    $database = $app->getDatabase();
    $userManager = new UserManager($database);

    // Get admin user
    $user = $userManager->getUserByUsername('admin');

    if (!$user) {
        echo "✗ Admin user not found!\n";
        exit(1);
    }

    echo "✓ Admin user found (ID: {$user['id']})\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n\n";

    // Show current hash
    $currentHashInfo = password_get_info($user['password']);
    echo "Current password hash:\n";
    echo "  Algorithm: {$currentHashInfo['algoName']}\n";
    echo "  Hash: " . substr($user['password'], 0, 30) . "...\n\n";

    // Test current password
    echo "Testing new password before update...\n";
    $testResult = Helpers::verifyPassword($password, $user['password']);

    if ($testResult) {
        echo "✓ Password already matches! No update needed.\n\n";
        echo "======================================================================\n";
        echo "  Login Credentials:\n";
        echo "======================================================================\n";
        echo "  Username: admin\n";
        echo "  Password: {$password}\n";
        echo "  URL: https://nexosupport.localhost.com/login\n";
        echo "======================================================================\n\n";
        exit(0);
    }

    echo "✗ Password doesn't match. Updating...\n\n";

    // Generate new bcrypt hash
    $newHash = Helpers::hashPassword($password);
    $newHashInfo = password_get_info($newHash);

    echo "New password hash:\n";
    echo "  Algorithm: {$newHashInfo['algoName']}\n";
    echo "  Hash: " . substr($newHash, 0, 30) . "...\n\n";

    // Update password in database
    $success = $userManager->update($user['id'], [
        'password' => $newHash
    ]);

    if (!$success) {
        echo "✗ Failed to update password!\n";
        exit(1);
    }

    echo "✓ Password updated successfully!\n\n";

    // Verify the update
    $updatedUser = $userManager->getUserByUsername('admin');
    $verifyResult = Helpers::verifyPassword($password, $updatedUser['password']);

    echo "Verifying updated password...\n";
    if ($verifyResult) {
        echo "✓ Password verification SUCCESSFUL!\n\n";
    } else {
        echo "✗ Password verification FAILED!\n\n";
        exit(1);
    }

    // Clear any account locks or failed attempts
    $database->getConnection()->execute(
        "UPDATE " . $database->table('users') . "
         SET failed_login_attempts = 0, locked_until = NULL
         WHERE id = :id",
        [':id' => $user['id']]
    );

    echo "✓ Account locks cleared\n";
    echo "✓ Failed login attempts reset\n\n";

    echo "======================================================================\n";
    echo "  SUCCESS! Login Credentials:\n";
    echo "======================================================================\n";
    echo "  Username: admin\n";
    echo "  Password: {$password}\n";
    echo "  URL: https://nexosupport.localhost.com/login\n";
    echo "======================================================================\n\n";

    echo "You can now login with these credentials.\n\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
