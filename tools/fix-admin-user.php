<?php

/**
 * Fix Admin User - Corregir usuario admin después de instalación
 *
 * Este script:
 * 1. Verifica que el usuario admin existe
 * 2. Actualiza su status a 'active'
 * 3. Actualiza la contraseña a bcrypt
 * 4. Limpia bloqueos y intentos fallidos
 * 5. Asegura que esté listo para login
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;
use ISER\User\UserManager;

define('BASE_DIR', dirname(__DIR__));

$password = $argv[1] ?? 'Admin.123+';

echo "\n";
echo "================================================================\n";
echo "  FIX ADMIN USER - POST INSTALACIÓN\n";
echo "================================================================\n\n";

try {
    // Inicializar sistema
    echo "[PASO 1/6] Inicializando sistema...\n";
    $app = Bootstrap::getInstance(BASE_DIR);
    $app->init();
    $database = $app->getDatabase();
    $userManager = new UserManager($database);
    echo "  ✓ Sistema inicializado\n\n";

    // Buscar usuario admin
    echo "[PASO 2/6] Buscando usuario admin...\n";
    $admin = $userManager->getUserByUsername('admin');

    if (!$admin) {
        echo "  ✗ Usuario admin NO EXISTE\n";
        echo "  >> Verifica que la instalación se completó correctamente\n\n";
        exit(1);
    }

    echo "  ✓ Usuario admin encontrado\n";
    echo "     - ID: {$admin['id']}\n";
    echo "     - Username: {$admin['username']}\n";
    echo "     - Email: {$admin['email']}\n";
    echo "     - Status actual: " . ($admin['status'] ?? 'NULL') . "\n";
    echo "     - Deleted: " . (empty($admin['deleted_at']) ? 'No' : 'Sí') . "\n";
    echo "     - Locked until: " . ($admin['locked_until'] ?? 0) . "\n";
    echo "     - Failed attempts: " . ($admin['failed_login_attempts'] ?? 0) . "\n\n";

    // Verificar hash actual
    echo "[PASO 3/6] Verificando hash de contraseña...\n";
    $currentHashInfo = password_get_info($admin['password']);
    echo "     - Algoritmo actual: {$currentHashInfo['algoName']}\n";
    echo "     - Hash: " . substr($admin['password'], 0, 40) . "...\n\n";

    // Generar nuevo hash con bcrypt
    echo "[PASO 4/6] Generando nuevo hash bcrypt...\n";
    $newPasswordHash = Helpers::hashPassword($password);
    $newHashInfo = password_get_info($newPasswordHash);
    echo "     - Algoritmo nuevo: {$newHashInfo['algoName']}\n";
    echo "     - Hash: " . substr($newPasswordHash, 0, 40) . "...\n\n";

    // Actualizar usuario completamente
    echo "[PASO 5/6] Actualizando usuario admin...\n";

    $updateData = [
        'status' => 'active',                    // Asegurar que esté activo
        'password' => $newPasswordHash,          // Nueva contraseña bcrypt
        'failed_login_attempts' => 0,            // Resetear intentos
        'locked_until' => null,                  // Quitar bloqueo
        'deleted_at' => null,                    // Asegurar que no esté eliminado
        'email_verified' => 1,                   // Marcar como verificado
    ];

    $success = $userManager->update($admin['id'], $updateData);

    if (!$success) {
        echo "  ✗ Error al actualizar usuario\n\n";
        exit(1);
    }

    echo "  ✓ Usuario actualizado correctamente\n\n";

    // Verificar actualización
    echo "[PASO 6/6] Verificando cambios...\n";
    $updatedAdmin = $userManager->getUserByUsername('admin');

    echo "     - Status: {$updatedAdmin['status']}\n";
    echo "     - Hash algorithm: " . password_get_info($updatedAdmin['password'])['algoName'] . "\n";
    echo "     - Deleted: " . (empty($updatedAdmin['deleted_at']) ? 'No' : 'Sí') . "\n";
    echo "     - Locked until: " . ($updatedAdmin['locked_until'] ?? 0) . "\n";
    echo "     - Failed attempts: " . ($updatedAdmin['failed_login_attempts'] ?? 0) . "\n";

    // Probar contraseña
    $passwordTest = password_verify($password, $updatedAdmin['password']);
    echo "     - Password test: " . ($passwordTest ? '✓ VÁLIDA' : '✗ INVÁLIDA') . "\n\n";

    if ($passwordTest && $updatedAdmin['status'] === 'active' && empty($updatedAdmin['deleted_at'])) {
        echo "  ✓✓✓ USUARIO ADMIN CORREGIDO EXITOSAMENTE ✓✓✓\n\n";
    } else {
        echo "  ⚠ Usuario actualizado pero verificación falló\n\n";
        exit(1);
    }

    // Limpiar intentos previos en login_attempts
    echo "[EXTRA] Limpiando intentos de login previos...\n";
    try {
        $tableName = $database->table('login_attempts');
        $pdo = $database->getConnection()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM {$tableName} WHERE username = :username");
        $stmt->execute([':username' => 'admin']);
        echo "  ✓ Intentos de login limpiados\n\n";
    } catch (\Exception $e) {
        echo "  ⚠ No se pudieron limpiar intentos: " . $e->getMessage() . "\n\n";
    }

    echo "================================================================\n";
    echo "  CREDENCIALES LISTAS PARA LOGIN\n";
    echo "================================================================\n\n";
    echo "  URL:       https://nexosupport.localhost.com/login\n";
    echo "  Usuario:   admin\n";
    echo "  Password:  {$password}\n\n";
    echo "  Status:    ACTIVE ✓\n";
    echo "  Hash:      bcrypt ✓\n";
    echo "  Deleted:   No ✓\n";
    echo "  Locked:    No ✓\n\n";
    echo "================================================================\n\n";
    echo "Ahora puedes hacer login sin problemas.\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
