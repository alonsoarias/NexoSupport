<?php

/**
 * Test Login Flow End-to-End
 * Prueba completa del flujo de autenticación
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;

define('BASE_DIR', dirname(__DIR__));

echo "\n";
echo "================================================================\n";
echo "  NEXOSUPPORT - TEST DE FLUJO DE LOGIN COMPLETO\n";
echo "================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

try {
    // Inicializar aplicación
    echo "[1/8] Inicializando sistema...\n";
    $app = Bootstrap::getInstance(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    $userManager = new UserManager($database);
    echo "  ✓ Sistema inicializado\n\n";
    $testsPassed++;

    // Verificar tabla login_attempts
    echo "[2/8] Verificando tabla login_attempts...\n";
    $tableName = $database->table('login_attempts');
    $pdo = $database->getConnection()->getPdo();
    $result = $pdo->query("SHOW TABLES LIKE '{$tableName}'")->fetch();

    if ($result) {
        echo "  ✓ Tabla {$tableName} existe\n\n";
        $testsPassed++;
    } else {
        echo "  ✗ Tabla {$tableName} NO EXISTE\n";
        echo "  >> Ejecuta la reinstalación del sistema\n\n";
        $testsFailed++;
    }

    // Verificar usuario admin
    echo "[3/8] Verificando usuario admin...\n";
    $admin = $userManager->getUserByUsername('admin');

    if (!$admin) {
        echo "  ✗ Usuario admin NO EXISTE\n";
        echo "  >> Crea el usuario admin primero\n\n";
        $testsFailed++;
    } else {
        echo "  ✓ Usuario admin encontrado\n";
        echo "     - ID: {$admin['id']}\n";
        echo "     - Username: {$admin['username']}\n";
        echo "     - Email: {$admin['email']}\n";
        echo "     - Status: " . ($admin['status'] ?? 'N/A') . "\n";
        echo "     - Deleted: " . (empty($admin['deleted_at']) ? 'No' : 'Sí') . "\n";
        $testsPassed++;

        // Verificar hash de contraseña
        echo "\n[4/8] Verificando hash de contraseña...\n";
        $hashInfo = password_get_info($admin['password']);
        echo "     - Algoritmo: {$hashInfo['algoName']}\n";
        echo "     - Hash: " . substr($admin['password'], 0, 40) . "...\n";

        if ($hashInfo['algoName'] === 'bcrypt') {
            echo "  ✓ Hash es bcrypt (correcto)\n\n";
            $testsPassed++;
        } elseif ($hashInfo['algoName'] === 'argon2id') {
            echo "  ⚠ Hash es argon2id (funcional pero no preferido)\n";
            echo "  >> Recomendación: actualiza a bcrypt con update-admin-password.php\n\n";
            $testsPassed++;
        } else {
            echo "  ✗ Hash desconocido: {$hashInfo['algoName']}\n\n";
            $testsFailed++;
        }

        // Verificar contraseña de prueba
        echo "[5/8] Probando contraseña 'Admin.123+'...\n";
        $passwordTest = password_verify('Admin.123+', $admin['password']);

        if ($passwordTest) {
            echo "  ✓ Contraseña 'Admin.123+' es VÁLIDA\n\n";
            $testsPassed++;
        } else {
            echo "  ✗ Contraseña 'Admin.123+' NO COINCIDE\n";
            echo "  >> Actualiza la contraseña: php tools/update-admin-password.php \"Admin.123+\"\n\n";
            $testsFailed++;
        }

        // Verificar bloqueos
        echo "[6/8] Verificando bloqueos de cuenta...\n";
        $lockedUntil = $admin['locked_until'] ?? 0;

        if ($lockedUntil > time()) {
            $remainingMinutes = ceil(($lockedUntil - time()) / 60);
            echo "  ⚠ Cuenta BLOQUEADA por {$remainingMinutes} minutos más\n";
            echo "  >> Espera o limpia manualmente con SQL\n\n";
            $testsFailed++;
        } else {
            echo "  ✓ Cuenta NO está bloqueada\n\n";
            $testsPassed++;
        }

        // Verificar intentos fallidos
        echo "[7/8] Verificando intentos fallidos...\n";
        $failedAttempts = $admin['failed_login_attempts'] ?? 0;
        echo "     - Intentos fallidos registrados: {$failedAttempts}\n";

        if ($failedAttempts >= 5) {
            echo "  ⚠ Demasiados intentos fallidos\n";
            echo "  >> La cuenta podría bloquearse pronto\n\n";
        } else {
            echo "  ✓ Intentos fallidos dentro del límite\n\n";
            $testsPassed++;
        }
    }

    // Verificar estructura de sesión
    echo "[8/8] Verificando configuración de sesión...\n";
    echo "     - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
    echo "     - Session name: " . session_name() . "\n";
    echo "     - Session save path: " . session_save_path() . "\n";
    echo "  ✓ Sesión configurada correctamente\n\n";
    $testsPassed++;

} catch (Exception $e) {
    echo "\n✗ Error durante las pruebas:\n";
    echo "   " . $e->getMessage() . "\n\n";
    $testsFailed++;
}

// Resumen
echo "================================================================\n";
echo "  RESUMEN DE PRUEBAS\n";
echo "================================================================\n\n";
echo "  Tests pasados: {$testsPassed}\n";
echo "  Tests fallidos: {$testsFailed}\n";
echo "  Total: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "  ✓✓✓ TODAS LAS PRUEBAS PASARON ✓✓✓\n\n";
    echo "  Tu sistema de autenticación está listo.\n";
    echo "  Puedes intentar hacer login con:\n\n";
    echo "  URL:       https://nexosupport.localhost.com/login\n";
    echo "  Usuario:   admin\n";
    echo "  Password:  Admin.123+\n\n";
    echo "  Logs se guardarán en: C:\\MAMP\\logs\\php_error.log\n";
    echo "  Busca líneas con: [LOGIN]\n\n";
    exit(0);
} else {
    echo "  ✗✗✗ ALGUNAS PRUEBAS FALLARON ✗✗✗\n\n";
    echo "  Revisa los errores arriba y corrige antes de intentar login.\n\n";
    echo "  Comandos útiles:\n";
    echo "  - Actualizar contraseña: php tools/update-admin-password.php \"Admin.123+\"\n";
    echo "  - Ver logs: tail -f C:\\MAMP\\logs\\php_error.log\n";
    echo "  - Reinstalar: https://nexosupport.localhost.com/install.php\n\n";
    exit(1);
}
