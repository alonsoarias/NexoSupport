<?php
/**
 * Debug User Data - Verificar qué devuelve getUserById()
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ISER\Core\Database\PDOConnection;
use ISER\Core\Database\Database;
use ISER\Modules\User\UserManager;

// Configuración de la base de datos
$dbConfig = require dirname(__DIR__) . '/config/database.php';

// Inicializar conexión
$pdoConnection = PDOConnection::getInstance($dbConfig);
$database = new Database($pdoConnection);

// Crear UserManager
$userManager = new UserManager($database);

// Obtener usuario con ID 1 (admin)
$userId = 1;
$user = $userManager->getUserById($userId);

echo "<h1>Debug: getUserById($userId)</h1>";

echo "<h2>Resultado completo:</h2>";
echo "<pre>";
var_dump($user);
echo "</pre>";

if ($user) {
    echo "<h2>Estructura del array:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    echo "<h2>Verificación de 'id':</h2>";
    echo "<ul>";
    echo "<li>¿Existe 'id'? " . (isset($user['id']) ? 'Sí' : 'No') . "</li>";
    echo "<li>Valor de 'id': " . var_export($user['id'] ?? 'NO EXISTE', true) . "</li>";
    echo "<li>Tipo de 'id': " . gettype($user['id'] ?? null) . "</li>";
    echo "<li>¿Es vacío?: " . (empty($user['id'] ?? null) ? 'Sí' : 'No') . "</li>";
    echo "</ul>";

    echo "<h2>Todas las claves del array:</h2>";
    echo "<pre>";
    print_r(array_keys($user));
    echo "</pre>";

    // Simular cómo Mustache vería esto
    echo "<h2>Simulación de Mustache:</h2>";
    echo "<p>{{user.id}} renderizaría como: <strong>" . ($user['id'] ?? '') . "</strong></p>";
    echo "<p>Longitud del string: " . strlen((string)($user['id'] ?? '')) . "</p>";

} else {
    echo "<p style='color: red;'>getUserById() devolvió FALSE - usuario no encontrado</p>";
}

// Consulta SQL directa para comparar
echo "<hr>";
echo "<h2>Consulta SQL directa:</h2>";
$result = $database->query("SELECT * FROM iser_users WHERE id = :id", [':id' => $userId]);
echo "<pre>";
print_r($result);
echo "</pre>";
