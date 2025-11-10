<?php
/**
 * Paso 3: Análisis de Schema XML
 *
 * Este paso parsea el archivo schema.xml y muestra la estructura
 * completa de la base de datos antes de instalarla
 */

// Redirect if no database config
if (!isset($_SESSION['db_config'])) {
    header('Location: ?step=2');
    exit;
}

// Parse schema.xml
$schemaFile = SCHEMA_FILE;
$schemaData = null;
$parseError = null;

try {
    $xmlParser = new \ISER\Core\Utils\XMLParser();
    $xmlParser->parseFile($schemaFile);
    $schemaData = $xmlParser->toArray();
} catch (Exception $e) {
    $parseError = $e->getMessage();
}

// Extract schema information
$metadata = $schemaData['metadata'] ?? [];
$tables = $schemaData['table'] ?? [];

// Normalize tables array
if (isset($tables['@attributes']) || isset($tables['name'])) {
    $tables = [$tables];
}

// Count initial data
$totalInitialRows = 0;
foreach ($tables as $table) {
    if (isset($table['data']['row'])) {
        $rows = $table['data']['row'];
        if (!isset($rows[0])) {
            $rows = [$rows];
        }
        $totalInitialRows += count($rows);
    }
}
?>

<div class="mb-4">
    <p class="lead">
        <i class="bi bi-file-earmark-code text-primary me-2"></i>
        A continuación se muestra la estructura de la base de datos que se instalará.
        Revise cuidadosamente antes de continuar.
    </p>
</div>

<?php if ($parseError): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error al parsear schema.xml:</strong> <?= htmlspecialchars($parseError) ?>
    </div>
<?php else: ?>

<!-- Schema Metadata -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-info-circle me-2"></i>Metadata del Schema
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th class="text-muted" style="width: 40%">Nombre:</th>
                        <td><?= htmlspecialchars($metadata['name'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Versión:</th>
                        <td><span class="badge bg-info"><?= htmlspecialchars($metadata['version'] ?? 'N/A') ?></span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Motor:</th>
                        <td><?= htmlspecialchars($metadata['engine'] ?? 'N/A') ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th class="text-muted" style="width: 40%">Charset:</th>
                        <td><?= htmlspecialchars($metadata['charset'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Collation:</th>
                        <td><?= htmlspecialchars($metadata['collation'] ?? 'N/A') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <div class="display-4"><?= count($tables) ?></div>
                <div class="mt-2">Tablas</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <div class="display-4"><?= $totalInitialRows ?></div>
                <div class="mt-2">Registros Iniciales</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <div class="display-4">
                    <?php
                    $totalColumns = 0;
                    foreach ($tables as $table) {
                        $columns = $table['columns']['column'] ?? [];
                        if (isset($columns['name'])) $columns = [$columns];
                        $totalColumns += count($columns);
                    }
                    echo $totalColumns;
                    ?>
                </div>
                <div class="mt-2">Columnas Totales</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <div class="display-4">
                    <?php
                    $totalFKs = 0;
                    foreach ($tables as $table) {
                        if (isset($table['foreignKeys']['foreignKey'])) {
                            $fks = $table['foreignKeys']['foreignKey'];
                            if (isset($fks['column'])) $fks = [$fks];
                            $totalFKs += count($fks);
                        }
                    }
                    echo $totalFKs;
                    ?>
                </div>
                <div class="mt-2">Foreign Keys</div>
            </div>
        </div>
    </div>
</div>

<!-- Tables List -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">
            <i class="bi bi-table me-2"></i>Tablas a Crear (<?= count($tables) ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="accordion" id="tablesAccordion">
            <?php foreach ($tables as $index => $table): ?>
                <?php
                $tableName = $table['name'] ?? 'Unknown';
                $description = $table['description'] ?? '';
                $columns = $table['columns']['column'] ?? [];
                if (isset($columns['name'])) $columns = [$columns];

                $indexes = [];
                if (isset($table['indexes']['index'])) {
                    $indexes = $table['indexes']['index'];
                    if (isset($indexes['name'])) $indexes = [$indexes];
                }

                $foreignKeys = [];
                if (isset($table['foreignKeys']['foreignKey'])) {
                    $foreignKeys = $table['foreignKeys']['foreignKey'];
                    if (isset($foreignKeys['column'])) $foreignKeys = [$foreignKeys];
                }

                $initialData = [];
                if (isset($table['data']['row'])) {
                    $initialData = $table['data']['row'];
                    if (!isset($initialData[0])) $initialData = [$initialData];
                }
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#table-<?= $index ?>">
                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                            <strong><?= htmlspecialchars($tableName) ?></strong>
                            <span class="text-muted ms-2">
                                (<?= count($columns) ?> columnas<?= count($initialData) > 0 ? ', ' . count($initialData) . ' registros iniciales' : '' ?>)
                            </span>
                        </button>
                    </h2>
                    <div id="table-<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                         data-bs-parent="#tablesAccordion">
                        <div class="accordion-body">
                            <?php if ($description): ?>
                                <p class="text-muted mb-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?= htmlspecialchars($description) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Columns -->
                            <h6 class="mb-2">
                                <i class="bi bi-list-columns-reverse me-1"></i>Columnas (<?= count($columns) ?>)
                            </h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%">Nombre</th>
                                            <th style="width: 20%">Tipo</th>
                                            <th style="width: 15%">Null</th>
                                            <th style="width: 20%">Default</th>
                                            <th style="width: 20%">Extras</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($columns as $column): ?>
                                            <tr>
                                                <td>
                                                    <code><?= htmlspecialchars($column['name'] ?? '') ?></code>
                                                    <?php if (isset($column['primary']) && $column['primary'] === 'true'): ?>
                                                        <span class="badge bg-warning text-dark ms-1">PK</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($column['type'] ?? '') ?></td>
                                                <td>
                                                    <?php if (isset($column['null']) && $column['null'] === 'false'): ?>
                                                        <span class="text-danger">NOT NULL</span>
                                                    <?php else: ?>
                                                        <span class="text-success">NULL</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($column['default'])): ?>
                                                        <code><?= htmlspecialchars($column['default']) ?></code>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($column['autoincrement']) && $column['autoincrement'] === 'true'): ?>
                                                        <span class="badge bg-info">AUTO_INCREMENT</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($column['unique']) && $column['unique'] === 'true'): ?>
                                                        <span class="badge bg-secondary">UNIQUE</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Indexes -->
                            <?php if (count($indexes) > 0): ?>
                                <h6 class="mb-2">
                                    <i class="bi bi-lightning me-1"></i>Índices (<?= count($indexes) ?>)
                                </h6>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($indexes as $index_item): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($index_item['name'] ?? '') ?></strong>
                                            <?php if (isset($index_item['unique']) && $index_item['unique'] === 'true'): ?>
                                                <span class="badge bg-warning text-dark ms-1">UNIQUE</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">Columnas: <?= htmlspecialchars($index_item['columns'] ?? '') ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Foreign Keys -->
                            <?php if (count($foreignKeys) > 0): ?>
                                <h6 class="mb-2">
                                    <i class="bi bi-link-45deg me-1"></i>Foreign Keys (<?= count($foreignKeys) ?>)
                                </h6>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($foreignKeys as $fk): ?>
                                        <li class="list-group-item">
                                            <code><?= htmlspecialchars($fk['column'] ?? '') ?></code>
                                            →
                                            <code><?= htmlspecialchars($fk['references'] ?? '') ?></code>
                                            <br>
                                            <small class="text-muted">
                                                ON DELETE: <?= htmlspecialchars($fk['onDelete'] ?? 'RESTRICT') ?>
                                            </small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Initial Data -->
                            <?php if (count($initialData) > 0): ?>
                                <h6 class="mb-2">
                                    <i class="bi bi-database-fill-add me-1"></i>Datos Iniciales (<?= count($initialData) ?> registros)
                                </h6>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Esta tabla incluye <?= count($initialData) ?> registro(s) de datos iniciales que se insertarán automáticamente.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Warnings -->
<div class="alert alert-warning">
    <h5 class="alert-heading">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>Importante
    </h5>
    <ul class="mb-0">
        <li>Se crearán <strong><?= count($tables) ?> tablas</strong> en la base de datos</li>
        <li>Se insertarán <strong><?= $totalInitialRows ?> registros</strong> de datos iniciales</li>
        <li>El proceso puede tardar varios segundos dependiendo del servidor</li>
        <li>Asegúrese de tener los permisos necesarios en la base de datos</li>
    </ul>
</div>

<!-- Continue Button -->
<form method="POST" action="?step=3">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="?step=2" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
        <button type="submit" class="btn btn-primary btn-installer btn-lg">
            Continuar con la Instalación
            <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<?php endif; ?>

<script>
// Highlight first table
document.addEventListener('DOMContentLoaded', () => {
    console.log('Schema analysis loaded - <?= count($tables) ?> tables found');
});
</script>
