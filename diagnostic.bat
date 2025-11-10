@echo off
echo ============================================
echo   DIAGNOSTIC - Authentication Debug
echo ============================================
echo.
echo Step 1: Checking if login_attempts table exists...
echo.

php -r "require 'vendor/autoload.php'; define('BASE_DIR', __DIR__); $app = ISER\Core\Bootstrap::getInstance(BASE_DIR); $app->init(); $db = $app->getDatabase(); $pdo = $db->getConnection()->getPdo(); try { $result = $pdo->query('SHOW TABLES LIKE \'' . $db->table('login_attempts') . '\'')->fetch(); if ($result) { echo '✓ Table ' . $db->table('login_attempts') . ' EXISTS' . PHP_EOL; } else { echo '✗ Table ' . $db->table('login_attempts') . ' DOES NOT EXIST' . PHP_EOL; echo 'You need to reinstall the system or create the table manually.' . PHP_EOL; } } catch (Exception $e) { echo '✗ Error checking table: ' . $e->getMessage() . PHP_EOL; }"

echo.
echo Step 2: Checking admin user...
echo.

php -r "require 'vendor/autoload.php'; define('BASE_DIR', __DIR__); $app = ISER\Core\Bootstrap::getInstance(BASE_DIR); $app->init(); $db = $app->getDatabase(); $userMgr = new ISER\User\UserManager($db); $user = $userMgr->getUserByUsername('admin'); if ($user) { echo '✓ Admin user EXISTS' . PHP_EOL; echo '  ID: ' . $user['id'] . PHP_EOL; echo '  Username: ' . $user['username'] . PHP_EOL; echo '  Email: ' . $user['email'] . PHP_EOL; echo '  Status: ' . ($user['status'] ?? 'N/A') . PHP_EOL; echo '  Hash type: ' . password_get_info($user['password'])['algoName'] . PHP_EOL; echo '  Locked until: ' . ($user['locked_until'] ?? 0) . PHP_EOL; echo '  Failed attempts: ' . ($user['failed_login_attempts'] ?? 0) . PHP_EOL; } else { echo '✗ Admin user DOES NOT EXIST' . PHP_EOL; }"

echo.
echo Step 3: Setting password to 'Admin.123+'...
echo.

php tools\test-password.php "Admin.123+"

echo.
echo Step 4: Checking error log for recent authentication attempts...
echo.
echo Opening log file...
echo.

powershell -Command "Get-Content 'C:\MAMP\logs\php_error.log' -Tail 100 | Select-String -Pattern '\[Auth' | Select-Object -Last 20"

echo.
echo ============================================
echo   Please copy ALL output above and share
echo ============================================
pause
