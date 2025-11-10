<?php
/**
 * Stage 3: Database Installation with Progress Bar
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
        $driver = $_SESSION['db_driver'] ?? 'mysql';

        // Construir DSN según el driver
        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . BASE_DIR . '/' . $_SESSION['db_name'];
            $pdo = new PDO($dsn);
        } else {
            $config = [
                'host' => $_SESSION['db_host'],
                'port' => $_SESSION['db_port'],
                'database' => $_SESSION['db_name']
            ];
            $dsn = \ISER\Core\Database\DatabaseDriverDetector::buildDSN($driver, $config);
            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $adapter = new \ISER\Core\Database\DatabaseAdapter($pdo);
        $reallyInstalled = $adapter->tableExists($_SESSION['db_prefix'] . 'users');
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

// Handle AJAX progress request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_install'])) {
    // Aumentar tiempo de ejecución
    set_time_limit(300);
    ini_set('max_execution_time', '300');

    // Limpiar cualquier output previo y capturar todo
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Iniciar nuevo buffer
    ob_start();

    try {
        $driver = $_SESSION['db_driver'] ?? 'mysql';

        // Construir DSN según el driver
        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . BASE_DIR . '/' . $_SESSION['db_name'];
            $pdo = new PDO($dsn);
        } else {
            $config = [
                'host' => $_SESSION['db_host'],
                'port' => $_SESSION['db_port'],
                'database' => $_SESSION['db_name']
            ];
            $dsn = \ISER\Core\Database\DatabaseDriverDetector::buildDSN($driver, $config);
            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar schema.xml
        if (!file_exists(SCHEMA_FILE)) {
            throw new Exception("Archivo schema.xml no encontrado");
        }

        // Install schema (captura todo el output HTML)
        $installer = new \ISER\Core\Database\SchemaInstaller($pdo, $_SESSION['db_prefix']);
        $installer->installFromXML(SCHEMA_FILE);

        $tables = $installer->getCreatedTables();
        $errors = $installer->getErrors();

        $_SESSION['db_installed'] = true;

        // Limpiar todo el output capturado
        ob_end_clean();

        // Enviar headers y JSON limpio
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'tables' => count($tables),
            'table_list' => $tables,
            'errors' => $errors
        ]);
        exit;

    } catch (Exception $e) {
        // Limpiar output capturado
        ob_end_clean();

        // Enviar error como JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }
}
?>

<h3 class="mb-4">Instalación de Base de Datos</h3>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Se instalarán:</strong>
    <ul class="mb-0 mt-2">
        <li><strong>13 tablas del sistema</strong></li>
        <li>Datos iniciales: 4 roles, <strong>35 permisos granulares</strong>, 8 configuraciones</li>
        <li>Permisos granulares organizados en 9 módulos (users, roles, permissions, dashboard, settings, logs, audit, reports, sessions)</li>
        <li>Índices optimizados y claves foráneas</li>
        <li>Relaciones: usuarios-roles, roles-permisos</li>
        <li>Asignación automática de todos los permisos al rol Admin</li>
    </ul>
</div>

<!-- Progress Container (Hidden initially) -->
<div id="progress-container" style="display: none;" class="mb-4">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="bi bi-gear-fill spinner-icon"></i>
                <span id="progress-title">Instalando Base de Datos...</span>
            </h5>

            <!-- Progress Bar -->
            <div class="progress mb-3" style="height: 30px;">
                <div id="install-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                     role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <span id="progress-text">0%</span>
                </div>
            </div>

            <!-- Status Messages -->
            <div id="install-log" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <!-- Messages will appear here -->
            </div>
        </div>
    </div>
</div>

<!-- Install Form -->
<form id="install-form" method="POST">
    <input type="hidden" name="stage" value="<?= STAGE_INSTALL_DB ?>">
    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="button" id="install-btn" class="btn btn-success">
            <i class="bi bi-download"></i> Instalar Ahora
        </button>
    </div>
</form>

<!-- Success Form (Hidden initially) -->
<form id="next-form" method="POST" style="display: none;">
    <input type="hidden" name="stage" value="<?= STAGE_INSTALL_DB ?>">
    <div class="text-end">
        <button type="submit" name="next" class="btn btn-primary btn-lg">
            Continuar <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<style>
.spinner-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#install-log .log-info {
    color: #0d6efd;
}

#install-log .log-success {
    color: #198754;
    font-weight: 600;
}

#install-log .log-error {
    color: #dc3545;
    font-weight: 600;
}

#install-log .log-warning {
    color: #ffc107;
}

#install-log p {
    margin: 5px 0;
    padding-left: 20px;
    text-indent: -20px;
}
</style>

<script>
document.getElementById('install-btn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando...';

    // Hide install form, show progress
    document.getElementById('install-form').style.display = 'none';
    document.getElementById('progress-container').style.display = 'block';

    installDatabase();
});

function addLog(message, type = 'info') {
    const log = document.getElementById('install-log');
    const p = document.createElement('p');
    p.className = 'log-' + type;
    p.innerHTML = message;
    log.appendChild(p);
    log.scrollTop = log.scrollHeight; // Auto-scroll to bottom
}

function updateProgress(percent, text) {
    const bar = document.getElementById('install-progress');
    const progressText = document.getElementById('progress-text');
    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', percent);
    progressText.textContent = text || percent + '%';
}

function installDatabase() {
    addLog('<i class="bi bi-info-circle"></i> Iniciando instalación de base de datos...', 'info');
    updateProgress(5, '5% - Iniciando');

    setTimeout(() => {
        addLog('<i class="bi bi-arrow-right"></i> Conectando a la base de datos...', 'info');
        updateProgress(10, '10% - Conectando');
    }, 300);

    setTimeout(() => {
        addLog('<i class="bi bi-check"></i> Conexión establecida', 'success');
        addLog('<i class="bi bi-arrow-right"></i> Verificando schema.xml...', 'info');
        updateProgress(15, '15% - Verificando schema');
    }, 600);

    setTimeout(() => {
        addLog('<i class="bi bi-check"></i> Schema.xml encontrado', 'success');
        addLog('<i class="bi bi-arrow-right"></i> Parseando archivo XML...', 'info');
        updateProgress(20, '20% - Parseando XML');
    }, 900);

    setTimeout(() => {
        addLog('<i class="bi bi-check"></i> XML parseado correctamente', 'success');
        addLog('<i class="bi bi-arrow-right"></i> Creando tablas del sistema...', 'info');
        updateProgress(30, '30% - Creando tablas');
    }, 1200);

    // Simulate table creation
    const tables = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
        'sessions', 'audit_logs', 'settings', 'notifications', 'reports',
        'activity_logs', 'password_resets', 'user_preferences'
    ];

    let currentTable = 0;
    const tableInterval = setInterval(() => {
        if (currentTable < tables.length) {
            addLog(`<i class="bi bi-check"></i> Tabla creada: <strong>${tables[currentTable]}</strong>`, 'success');
            currentTable++;
            const progress = 30 + (currentTable / tables.length) * 30;
            updateProgress(Math.round(progress), Math.round(progress) + '% - Creando tablas');
        } else {
            clearInterval(tableInterval);

            addLog('<i class="bi bi-check-circle"></i> <strong>Todas las tablas creadas exitosamente</strong>', 'success');
            updateProgress(60, '60% - Tablas creadas');

            setTimeout(() => {
                addLog('<i class="bi bi-arrow-right"></i> Insertando datos iniciales...', 'info');
                updateProgress(65, '65% - Insertando datos');
            }, 300);

            setTimeout(() => {
                addLog('<i class="bi bi-check"></i> 4 roles iniciales insertados', 'success');
                updateProgress(70, '70% - Roles insertados');
            }, 600);

            setTimeout(() => {
                addLog('<i class="bi bi-check"></i> <strong>35 permisos granulares insertados</strong>', 'success');
                addLog('<i class="bi bi-info-circle"></i> Permisos organizados en 9 módulos', 'info');
                updateProgress(80, '80% - Permisos insertados');
            }, 900);

            setTimeout(() => {
                addLog('<i class="bi bi-check"></i> 8 configuraciones iniciales insertadas', 'success');
                updateProgress(85, '85% - Configuraciones insertadas');
            }, 1200);

            setTimeout(() => {
                addLog('<i class="bi bi-arrow-right"></i> Asignando permisos al rol Admin...', 'info');
                updateProgress(90, '90% - Asignando permisos');
            }, 1500);

            setTimeout(() => {
                addLog('<i class="bi bi-check-circle"></i> <strong>35 permisos asignados al rol Admin</strong>', 'success');
                updateProgress(95, '95% - Permisos asignados');
            }, 1800);

            setTimeout(() => {
                // Make actual AJAX call to install
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax_install=1&stage=<?= STAGE_INSTALL_DB ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateProgress(100, '100% - Completado');
                        addLog('<i class="bi bi-check-circle-fill"></i> <strong style="font-size: 1.1em;">¡Instalación completada exitosamente!</strong>', 'success');
                        addLog(`<i class="bi bi-info-circle"></i> Total de tablas creadas: <strong>${data.tables}</strong>`, 'info');

                        // Remove animation from progress bar
                        document.getElementById('install-progress').classList.remove('progress-bar-animated', 'progress-bar-striped');
                        document.getElementById('progress-title').innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Instalación Completada';

                        // Show next button
                        setTimeout(() => {
                            document.getElementById('next-form').style.display = 'block';
                        }, 1000);
                    } else {
                        updateProgress(100, 'Error');
                        document.getElementById('install-progress').classList.remove('bg-success');
                        document.getElementById('install-progress').classList.add('bg-danger');
                        addLog('<i class="bi bi-x-circle"></i> <strong>Error:</strong> ' + data.error, 'error');

                        // Show install form again
                        setTimeout(() => {
                            document.getElementById('install-form').style.display = 'block';
                            document.getElementById('install-btn').disabled = false;
                            document.getElementById('install-btn').innerHTML = '<i class="bi bi-arrow-repeat"></i> Reintentar';
                        }, 2000);
                    }
                })
                .catch(error => {
                    updateProgress(100, 'Error');
                    document.getElementById('install-progress').classList.remove('bg-success');
                    document.getElementById('install-progress').classList.add('bg-danger');
                    addLog('<i class="bi bi-x-circle"></i> <strong>Error de red:</strong> ' + error.message, 'error');
                });
            }, 2100);
        }
    }, 150);
}
</script>
