<?php
/**
 * Paso 4: Instalación de Base de Datos
 *
 * Instalación directa sin AJAX, al estilo Moodle
 */

// Redirect if no database config or schema not analyzed
if (!isset($_SESSION['db_config'])) {
    header('Location: ?step=2');
    exit;
}

if (!isset($_SESSION['step_3_completed']) || !$_SESSION['step_3_completed']) {
    header('Location: ?step=3');
    exit;
}

// Check if already installed
$alreadyInstalled = isset($_SESSION['step_4_completed']) && $_SESSION['step_4_completed'];
?>

<div class="mb-4">
    <p class="lead">
        <i class="bi bi-database text-primary me-2"></i>
        El sistema instalará ahora la estructura completa de la base de datos.
    </p>
</div>

<?php if ($alreadyInstalled): ?>
    <div class="alert alert-success">
        <h5 class="alert-heading">
            <i class="bi bi-check-circle-fill me-2"></i>Base de Datos Ya Instalada
        </h5>
        <p class="mb-2">
            La base de datos ya ha sido instalada exitosamente.
        </p>
        <?php if (isset($_SESSION['created_tables'])): ?>
            <p class="mb-0">
                <strong>Tablas creadas:</strong> <?= count($_SESSION['created_tables']) ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=3" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Anterior
        </a>
        <a href="?step=5" class="btn btn-primary btn-installer btn-lg">
            Continuar
            <i class="bi bi-arrow-right ms-2"></i>
        </a>
    </div>

<?php else: ?>

<div class="alert alert-info mb-4">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Se instalarán los siguientes componentes:
    </h6>
    <ul class="mb-0">
        <li>12 tablas del sistema (usuarios, roles, permisos, sesiones, etc.)</li>
        <li>Índices y claves foráneas para integridad referencial</li>
        <li>Datos iniciales: roles por defecto, permisos y configuraciones</li>
        <li>Asignación automática de permisos al rol administrador</li>
    </ul>
</div>

<div class="alert alert-warning">
    <h6 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2"></i>Importante
    </h6>
    <p class="mb-0">
        Este proceso puede tardar varios segundos. No cierre ni actualice esta página hasta que se complete la instalación.
    </p>
</div>

<!-- Installation Form -->
<form method="POST" action="?step=4">
    <input type="hidden" name="install_database" value="1">

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=3" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-installer btn-lg">
            <i class="bi bi-download me-2"></i>
            Instalar Base de Datos
        </button>
    </div>
</form>

<?php endif; ?>

<?php
// Process installation if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_database'])) {
    echo '<hr class="my-4">';
    echo '<h4 class="mb-3"><i class="bi bi-gear-fill me-2 text-primary"></i>Proceso de Instalación</h4>';

    echo '<div class="card">';
    echo '<div class="card-body" style="font-family: monospace; font-size: 0.9rem; background: #f8f9fa;">';

    try {
        $db = $_SESSION['db_config'];

        // Connect to database
        echo '<p class="mb-2"><i class="bi bi-arrow-right text-primary me-2"></i>Conectando a la base de datos...</p>';
        flush();
        ob_flush();

        $dsn = "mysql:host={$db['db_host']};port={$db['db_port']};dbname={$db['db_name']}";
        $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<p class="mb-2 text-success"><i class="bi bi-check-circle-fill me-2"></i>Conexión establecida exitosamente</p>';
        flush();
        ob_flush();

        // Check schema file
        if (!file_exists(SCHEMA_FILE)) {
            throw new Exception('Archivo de esquema XML no encontrado: ' . SCHEMA_FILE);
        }

        echo '<p class="mb-2"><i class="bi bi-arrow-right text-primary me-2"></i>Leyendo archivo de esquema XML...</p>';
        flush();
        ob_flush();

        // Initialize SchemaInstaller
        $prefix = $db['db_prefix'] ?? '';
        $installer = new \ISER\Core\Database\SchemaInstaller($pdo, $prefix);

        echo '<p class="mb-2 text-success"><i class="bi bi-check-circle-fill me-2"></i>SchemaInstaller inicializado</p>';
        echo '<p class="mb-2"><i class="bi bi-arrow-right text-primary me-2"></i>Instalando tablas desde XML...</p>';
        flush();
        ob_flush();

        // Install from XML
        $installer->installFromXML(SCHEMA_FILE);

        // Get created tables
        $tables = $installer->getCreatedTables();

        echo '<p class="mb-2 text-success"><i class="bi bi-check-circle-fill me-2"></i><strong>'.count($tables).' tablas creadas exitosamente:</strong></p>';
        echo '<ul class="mb-3">';
        foreach ($tables as $table) {
            echo '<li class="text-success">'.$table.'</li>';
            flush();
            ob_flush();
        }
        echo '</ul>';

        // Mark as completed
        $_SESSION['step_4_completed'] = true;
        $_SESSION['created_tables'] = $tables;

        echo '<div class="alert alert-success mb-0">';
        echo '<h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>¡Instalación Completada!</h5>';
        echo '<p class="mb-0">La base de datos ha sido instalada correctamente con todas las tablas, índices y datos iniciales.</p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '<div class="d-flex justify-content-end mt-4">';
        echo '<a href="?step=5" class="btn btn-primary btn-installer btn-lg">';
        echo 'Continuar <i class="bi bi-arrow-right ms-2"></i>';
        echo '</a>';
        echo '</div>';

    } catch (PDOException $e) {
        echo '<p class="mb-2 text-danger"><i class="bi bi-x-circle-fill me-2"></i><strong>Error de base de datos:</strong></p>';
        echo '<div class="alert alert-danger">'.$e->getMessage().'</div>';
        echo '</div>';
        echo '</div>';

    } catch (Exception $e) {
        echo '<p class="mb-2 text-danger"><i class="bi bi-x-circle-fill me-2"></i><strong>Error:</strong></p>';
        echo '<div class="alert alert-danger">'.$e->getMessage().'</div>';
        echo '</div>';
        echo '</div>';
    }

    exit;
}
?>
