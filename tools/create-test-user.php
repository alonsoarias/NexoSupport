<?php
/**
 * Create Test User Script
 *
 * This script creates a test user for authentication testing
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));

// Load Composer autoloader
require_once BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\Core\Utils\Helpers;

try {
    // Initialize the system
    echo "Initializing system...\n";
    $app = new Bootstrap(BASE_DIR);
    $app->init();

    $db = $app->getDatabase();
    if (!$db) {
        die("ERROR: Could not get database instance\n");
    }

    $userManager = new UserManager($db);

    // Check if user already exists
    $username = 'admin';
    $email = 'admin@example.com';

    echo "Checking if user '{$username}' exists...\n";
    $existingUser = $userManager->getUserByUsername($username);

    if ($existingUser) {
        echo "User '{$username}' already exists (ID: {$existingUser['id']})\n";
        echo "Username: {$existingUser['username']}\n";
        echo "Email: {$existingUser['email']}\n";
        echo "Status: {$existingUser['status']}\n";
        echo "Created: " . date('Y-m-d H:i:s', $existingUser['created_at']) . "\n";

        // Update password
        echo "\nUpdating password to 'admin123'...\n";
        $userManager->update($existingUser['id'], [
            'password' => 'admin123',  // Will be hashed by UserManager
            'status' => 'active'
        ]);
        echo "Password updated successfully!\n";
    } else {
        echo "User not found. Creating new user...\n";

        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => 'admin123',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'status' => 'active'
        ];

        $userId = $userManager->create($userData);

        if ($userId) {
            echo "✓ User created successfully!\n";
            echo "  ID: {$userId}\n";
            echo "  Username: {$username}\n";
            echo "  Password: admin123\n";
            echo "  Email: {$email}\n";
        } else {
            echo "✗ Failed to create user\n";
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "TEST CREDENTIALS:\n";
    echo "  Username: {$username}\n";
    echo "  Password: admin123\n";
    echo str_repeat("=", 50) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
