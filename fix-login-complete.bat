@echo off
setlocal enabledelayedexpansion

echo.
echo ============================================================================
echo                   NEXOSUPPORT - FIX LOGIN COMPLETE
echo ============================================================================
echo.
echo Este script realizara los siguientes pasos:
echo   1. Verificar que la tabla login_attempts existe
echo   2. Verificar el usuario admin
echo   3. Actualizar la contrasena a 'Admin.123+' con bcrypt
echo   4. Limpiar bloqueos de cuenta
echo   5. Mostrar credenciales finales
echo.
echo ============================================================================
echo.

pause

echo.
echo [PASO 1/5] Verificando tabla login_attempts...
echo ----------------------------------------------------------------------------

php -r "require 'vendor/autoload.php'; define('BASE_DIR', __DIR__); try { $app = ISER\Core\Bootstrap::getInstance(BASE_DIR); $app->init(); $db = $app->getDatabase(); $tableName = $db->table('login_attempts'); $pdo = $db->getConnection()->getPdo(); $result = $pdo->query('SHOW TABLES LIKE \"' . $tableName . '\"')->fetch(); if ($result) { echo PHP_EOL . '  [OK] Tabla ' . $tableName . ' EXISTE' . PHP_EOL . PHP_EOL; } else { echo PHP_EOL . '  [ERROR] Tabla ' . $tableName . ' NO EXISTE' . PHP_EOL; echo '  Por favor reinstala el sistema o crea la tabla manualmente' . PHP_EOL . PHP_EOL; exit(1); } } catch (Exception $e) { echo PHP_EOL . '  [ERROR] ' . $e->getMessage() . PHP_EOL . PHP_EOL; exit(1); }"

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo verificar la tabla. Revisa el error anterior.
    pause
    exit /b 1
)

echo [PASO 2/5] Verificando usuario admin...
echo ----------------------------------------------------------------------------

php -r "require 'vendor/autoload.php'; define('BASE_DIR', __DIR__); $app = ISER\Core\Bootstrap::getInstance(BASE_DIR); $app->init(); $db = $app->getDatabase(); $userMgr = new ISER\User\UserManager($db); $user = $userMgr->getUserByUsername('admin'); if ($user) { echo PHP_EOL . '  [OK] Usuario admin EXISTE' . PHP_EOL; echo '  - ID: ' . $user['id'] . PHP_EOL; echo '  - Username: ' . $user['username'] . PHP_EOL; echo '  - Email: ' . $user['email'] . PHP_EOL; echo '  - Status: ' . ($user['status'] ?? 'N/A') . PHP_EOL; $hashInfo = password_get_info($user['password']); echo '  - Hash algorithm: ' . $hashInfo['algoName'] . PHP_EOL; echo '  - Failed attempts: ' . ($user['failed_login_attempts'] ?? 0) . PHP_EOL; echo '  - Locked until: ' . ($user['locked_until'] ?? 0) . PHP_EOL . PHP_EOL; } else { echo PHP_EOL . '  [ERROR] Usuario admin NO EXISTE' . PHP_EOL . PHP_EOL; exit(1); }"

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo verificar el usuario. Revisa el error anterior.
    pause
    exit /b 1
)

echo [PASO 3/5] Actualizando contrasena a 'Admin.123+'...
echo ----------------------------------------------------------------------------
echo.

php tools\update-admin-password.php "Admin.123+"

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo actualizar la contrasena. Revisa el error anterior.
    pause
    exit /b 1
)

echo.
echo [PASO 4/5] Limpiando tabla login_attempts...
echo ----------------------------------------------------------------------------

php -r "require 'vendor/autoload.php'; define('BASE_DIR', __DIR__); $app = ISER\Core\Bootstrap::getInstance(BASE_DIR); $app->init(); $db = $app->getDatabase(); $tableName = $db->table('login_attempts'); $pdo = $db->getConnection()->getPdo(); $stmt = $pdo->prepare('DELETE FROM ' . $tableName . ' WHERE username = :username'); $stmt->execute([':username' => 'admin']); echo PHP_EOL . '  [OK] Intentos de login previos eliminados' . PHP_EOL . PHP_EOL;"

echo [PASO 5/5] Todo listo!
echo ----------------------------------------------------------------------------
echo.
echo   ╔══════════════════════════════════════════════════════════════╗
echo   ║                  CREDENCIALES DE ACCESO                      ║
echo   ╠══════════════════════════════════════════════════════════════╣
echo   ║                                                              ║
echo   ║  URL:       https://nexosupport.localhost.com/login          ║
echo   ║  Usuario:   admin                                            ║
echo   ║  Password:  Admin.123+                                       ║
echo   ║                                                              ║
echo   ╚══════════════════════════════════════════════════════════════╝
echo.
echo.
echo ============================================================================
echo                         PASOS SIGUIENTES
echo ============================================================================
echo.
echo 1. Abre tu navegador
echo 2. Ve a: https://nexosupport.localhost.com/login
echo 3. Ingresa las credenciales mostradas arriba
echo 4. Click en "Iniciar Sesion"
echo.
echo Si el login falla, ejecuta: diagnostic.bat
echo Luego copia el contenido del log para analisis
echo.
echo ============================================================================
echo.
pause
