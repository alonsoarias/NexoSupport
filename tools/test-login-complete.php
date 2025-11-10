<?php

/**
 * Test Login Completo - Simula el proceso de login paso a paso
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\User\UserManager;

define('BASE_DIR', dirname(__DIR__));

echo "\n";
echo "================================================================\n";
echo "  TEST DE LOGIN COMPLETO - SIMULACIÓN PASO A PASO\n";
echo "================================================================\n\n";

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

echo "Probando login con:\n";
echo "  Username: {$username}\n";
echo "  Password: {$password}\n\n";

try {
    // PASO 1: Inicializar sistema
    echo "[PASO 1] Inicializando sistema...\n";
    $app = Bootstrap::getInstance(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    $userManager = new UserManager($database);
    echo "  ✓ Sistema inicializado\n\n";

    // PASO 2: Buscar usuario
    echo "[PASO 2] Buscando usuario en base de datos...\n";
    $user = $userManager->getUserByUsername($username);

    if (!$user) {
        echo "  Intentando buscar por email...\n";
        $user = $userManager->getUserByEmail($username);
    }

    if (!$user) {
        echo "  ✗ FALLO: Usuario no existe\n";
        echo "  >> El username/email '{$username}' no se encuentra en la DB\n\n";
        exit(1);
    }

    echo "  ✓ Usuario encontrado\n";
    echo "     - ID: {$user['id']}\n";
    echo "     - Username: {$user['username']}\n";
    echo "     - Email: {$user['email']}\n";
    echo "     - Status: {$user['status']}\n";
    echo "     - Deleted: " . (empty($user['deleted_at']) ? 'No' : 'Sí') . "\n";
    echo "     - Locked until: " . ($user['locked_until'] ?? 'NULL') . "\n";
    echo "     - Failed attempts: " . ($user['failed_login_attempts'] ?? 0) . "\n\n";

    // PASO 3: Verificar status
    echo "[PASO 3] Verificando status del usuario...\n";

    if (($user['status'] ?? 'active') !== 'active') {
        echo "  ✗ FALLO: Usuario no está activo\n";
        echo "  >> Status actual: " . ($user['status'] ?? 'NULL') . "\n";
        echo "  >> Debe ser: 'active'\n\n";

        echo "  Corrección SQL:\n";
        echo "  UPDATE " . $database->table('users') . " SET status = 'active' WHERE id = {$user['id']};\n\n";
        exit(1);
    }

    echo "  ✓ Status es 'active'\n\n";

    // PASO 4: Verificar deleted_at
    echo "[PASO 4] Verificando que no esté eliminado...\n";

    if (!empty($user['deleted_at'])) {
        echo "  ✗ FALLO: Usuario está eliminado\n";
        echo "  >> deleted_at: {$user['deleted_at']}\n\n";

        echo "  Corrección SQL:\n";
        echo "  UPDATE " . $database->table('users') . " SET deleted_at = NULL WHERE id = {$user['id']};\n\n";
        exit(1);
    }

    echo "  ✓ Usuario no está eliminado\n\n";

    // PASO 5: Verificar bloqueo
    echo "[PASO 5] Verificando bloqueos de cuenta...\n";
    $lockedUntil = $user['locked_until'] ?? 0;

    if ($lockedUntil > time()) {
        $remainingMinutes = ceil(($lockedUntil - time()) / 60);
        echo "  ✗ FALLO: Cuenta bloqueada\n";
        echo "  >> Bloqueada por {$remainingMinutes} minutos más\n\n";

        echo "  Corrección SQL:\n";
        echo "  UPDATE " . $database->table('users') . " SET locked_until = NULL, failed_login_attempts = 0 WHERE id = {$user['id']};\n\n";
        exit(1);
    }

    echo "  ✓ Cuenta no está bloqueada\n\n";

    // PASO 6: Verificar contraseña
    echo "[PASO 6] Verificando contraseña...\n";
    $passwordHash = $user['password'];
    $hashInfo = password_get_info($passwordHash);

    echo "     - Hash algorithm: {$hashInfo['algoName']}\n";
    echo "     - Hash: " . substr($passwordHash, 0, 50) . "...\n";
    echo "     - Password to verify: '{$password}'\n";

    $passwordValid = password_verify($password, $passwordHash);

    if (!$passwordValid) {
        echo "  ✗ FALLO: Contraseña incorrecta\n";
        echo "  >> La contraseña '{$password}' NO coincide con el hash en la DB\n\n";

        echo "  Posibles soluciones:\n";
        echo "  1. Usa la contraseña correcta que pusiste en la instalación\n";
        echo "  2. O actualiza la contraseña: php tools/fix-admin-user.php \"TuPassword\"\n\n";
        exit(1);
    }

    echo "  ✓ Contraseña verificada correctamente\n\n";

    // PASO 7: Simular creación de sesión
    echo "[PASO 7] Simulando creación de sesión...\n";

    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerar ID de sesión
    session_regenerate_id(true);

    // Establecer variables de sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['authenticated'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    echo "  ✓ Sesión creada exitosamente\n";
    echo "     - session_id: " . session_id() . "\n";
    echo "     - user_id: " . $_SESSION['user_id'] . "\n";
    echo "     - username: " . $_SESSION['username'] . "\n";
    echo "     - authenticated: " . ($_SESSION['authenticated'] ? 'true' : 'false') . "\n\n";

    // PASO 8: Verificar autenticación
    echo "[PASO 8] Verificando estado de autenticación...\n";

    $isAuthenticated = isset($_SESSION['user_id'])
        && isset($_SESSION['authenticated'])
        && $_SESSION['authenticated'] === true;

    if ($isAuthenticated) {
        echo "  ✓ Usuario está autenticado correctamente\n\n";
    } else {
        echo "  ✗ FALLO: Sesión no válida\n\n";
        exit(1);
    }

    // Destruir sesión de prueba
    session_destroy();

    echo "================================================================\n";
    echo "  ✓✓✓ TODOS LOS PASOS PASARON EXITOSAMENTE ✓✓✓\n";
    echo "================================================================\n\n";

    echo "El flujo de autenticación funciona correctamente desde el backend.\n\n";

    echo "Si el login desde el navegador aún falla, el problema está en:\n";
    echo "  1. El formulario HTML no está enviando los datos\n";
    echo "  2. El router no está capturando POST /login\n";
    echo "  3. Hay algún middleware bloqueando la request\n\n";

    echo "Revisa el log de PHP:\n";
    echo "  tail -50 C:\\MAMP\\logs\\php_error.log | grep \"\\[LOGIN\"\n\n";

    echo "Credenciales que funcionan:\n";
    echo "  Username: {$username}\n";
    echo "  Password: {$password}\n";
    echo "  URL: https://nexosupport.localhost.com/login\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
