<?php
/**
 * Stage 3: Database Installation
 */

// Función para verificar si las tablas existen realmente
function tablesExist($pdo, $prefix) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}users'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Verificar si realmente las tablas existen
$reallyInstalled = false;
if (isset($_SESSION['db_installed']) && $_SESSION['db_installed']) {
    try {
        $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']}";
        $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $reallyInstalled = tablesExist($pdo, $_SESSION['db_prefix']);
    } catch (Exception $e) {
        $reallyInstalled = false;
    }
}

// Si la sesión dice que está instalado pero las tablas no existen, limpiar flag
if (isset($_SESSION['db_installed']) && !$reallyInstalled) {
    unset($_SESSION['db_installed']);
}

// Check if already installed (con verificación real)
if ($reallyInstalled) {
    ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <strong>Base de datos ya instalada</strong>
        <p class="mb-0 mt-2 small">Las tablas del sistema ya existen en la base de datos.</p>
    </div>

    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        Si desea reinstalar, debe eliminar las tablas existentes manualmente primero.
    </div>

    <form method="POST">
        <input type="hidden" name="stage" value="<?= STAGE_INSTALL_DB ?>">
        <div class="d-flex justify-content-between">
            <button type="submit" name="previous" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Anterior
            </button>
            <button type="submit" name="next" class="btn btn-primary">
                Siguiente <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </form>
    <?php
    return;
}

// If POST install, do installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    echo '<h3 class="mb-4">Instalando Base de Datos...</h3>';
    echo '<div style="font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 5px;">';

    try {
        // Connect
        $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']}";
        $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<p class="text-success"><i class="bi bi-check"></i> Conectado a la base de datos</p>';
        flush(); ob_flush();

        // Install schema
        $installer = new \ISER\Core\Database\SchemaInstaller($pdo, $_SESSION['db_prefix']);
        echo '<p class="text-primary"><i class="bi bi-arrow-right"></i> Instalando tablas...</p>';
        flush(); ob_flush();

        $installer->installFromXML(SCHEMA_FILE);
        $tables = $installer->getCreatedTables();

        echo '<p class="text-success"><i class="bi bi-check"></i> <strong>' . count($tables) . ' tablas creadas</strong></p>';
        echo '<ul>';
        foreach ($tables as $table) {
            echo '<li class="text-success">' . htmlspecialchars($table) . '</li>';
            flush(); ob_flush();
        }
        echo '</ul>';

        $_SESSION['db_installed'] = true;

        echo '<div class="alert alert-success mt-3"><strong>¡Instalación completada!</strong></div>';
        echo '</div>';

        echo '<form method="POST" class="mt-3">';
        echo '<input type="hidden" name="stage" value="' . STAGE_INSTALL_DB . '">';
        echo '<div class="text-end"><button type="submit" name="next" class="btn btn-primary">Siguiente <i class="bi bi-arrow-right"></i></button></div>';
        echo '</form>';

    } catch (Exception $e) {
        echo '<p class="text-danger"><i class="bi bi-x"></i> <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        echo '<form method="POST" class="mt-3">';
        echo '<input type="hidden" name="stage" value="' . STAGE_INSTALL_DB . '">';
        echo '<div class="text-end"><button type="submit" name="previous" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Anterior</button></div>';
        echo '</form>';
    }

    return;
}
?>

<h3 class="mb-4">Instalación de Base de Datos</h3>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Se instalarán:</strong>
    <ul class="mb-0 mt-2">
        <li><strong>13 tablas del sistema</strong></li>
        <li>Datos iniciales: 4 roles, 9 permisos, 8 configuraciones</li>
        <li>Índices optimizados y claves foráneas</li>
        <li>Relaciones: usuarios-roles, roles-permisos</li>
    </ul>
</div>

<form method="POST">
    <input type="hidden" name="stage" value="<?= STAGE_INSTALL_DB ?>">
    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="submit" name="install" class="btn btn-success">
            <i class="bi bi-download"></i> Instalar Ahora
        </button>
    </div>
</form>
