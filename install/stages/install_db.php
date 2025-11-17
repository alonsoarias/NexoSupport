<?php
/**
 * Stage: Install Database
 */

$progress = 50;

session_start();

if (!isset($_SESSION['install_db'])) {
    header('Location: /install?stage=database');
    exit;
}

$dbconfig = $_SESSION['install_db'];

// Conectar a base de datos
$dsn = $dbconfig['driver'] === 'mysql'
    ? "mysql:host={$dbconfig['host']};dbname={$dbconfig['name']};charset=utf8mb4"
    : "pgsql:host={$dbconfig['host']};dbname={$dbconfig['name']}";

try {
    $pdo = new PDO($dsn, $dbconfig['user'], $dbconfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cargar clases necesarias manualmente (Composer no está disponible aún)
    require_once(BASE_DIR . '/lib/classes/db/xmldb_table.php');
    require_once(BASE_DIR . '/lib/classes/db/xmldb_field.php');
    require_once(BASE_DIR . '/lib/classes/db/xmldb_key.php');
    require_once(BASE_DIR . '/lib/classes/db/xmldb_index.php');
    require_once(BASE_DIR . '/lib/classes/db/database.php');
    require_once(BASE_DIR . '/lib/classes/db/ddl_manager.php');
    require_once(BASE_DIR . '/lib/classes/db/schema_installer.php');

    $DB = new \core\db\database($pdo, $dbconfig['prefix'], $dbconfig['driver']);
    $installer = new \core\db\schema_installer($DB);

    // Instalar schema del core
    $installer->install_from_xmlfile(BASE_DIR . '/lib/db/install.xml');

    // Instalar datos iniciales
    // Crear contexto raíz
    $DB->insert_record('contexts', [
        'contextlevel' => 10,
        'instanceid' => 0,
        'path' => '/1',
        'depth' => 1
    ]);

    // Redirigir al siguiente stage
    header('Location: /install?stage=admin');
    exit;

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<h1>Instalación de Base de Datos</h1>
<h2>Creando tablas del sistema</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <div class="actions">
        <a href="/install?stage=database" class="btn">Volver</a>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <strong>Instalando...</strong> Por favor espere mientras se crean las tablas.
    </div>
    <script>
        // Auto-redirigir si todo fue bien
        setTimeout(function() {
            window.location.href = '/install?stage=admin';
        }, 2000);
    </script>
<?php endif; ?>
