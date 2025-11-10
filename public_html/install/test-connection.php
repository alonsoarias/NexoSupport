<?php
/**
 * Test Database Connection
 * AJAX endpoint para probar la conexión a la base de datos
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'test_connection') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$host = $_POST['db_host'] ?? '';
$port = $_POST['db_port'] ?? '3306';
$dbname = $_POST['db_name'] ?? '';
$user = $_POST['db_user'] ?? '';
$pass = $_POST['db_pass'] ?? '';

if (empty($host) || empty($dbname) || empty($user)) {
    echo json_encode([
        'success' => false,
        'message' => 'Host, nombre de base de datos y usuario son requeridos'
    ]);
    exit;
}

try {
    // Intentar conexión sin especificar la base de datos
    $dsn = "mysql:host={$host};port={$port}";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbname}'");
    $dbExists = $stmt->rowCount() > 0;

    if ($dbExists) {
        $message = "Conexión exitosa. La base de datos '{$dbname}' existe.";
    } else {
        $message = "Conexión exitosa. La base de datos '{$dbname}' será creada durante la instalación.";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'database_exists' => $dbExists
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
