<?php
/**
 * Stage 3: Database Installation
 */

// Check if already installed
if (isset($_SESSION['db_installed']) && $_SESSION['db_installed']) {
    ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <strong>Base de datos ya instalada</strong>
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
        <li>12 tablas del sistema</li>
        <li>Datos iniciales (roles, permisos, configuraciones)</li>
        <li>Índices y claves foráneas</li>
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
