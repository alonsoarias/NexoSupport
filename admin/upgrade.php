<?php
/**
 * System Upgrade Page
 *
 * Handles detection and execution of system upgrades.
 * Similar to Moodle's admin/index.php upgrade handling.
 *
 * This page:
 * - Checks if upgrade is needed by comparing versions
 * - Validates system requirements
 * - Executes core and plugin upgrades
 * - Displays upgrade progress and results
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/lib/upgradelib.php');
require_once($CFG->dirroot . '/lib/environmentlib.php');

// Require login and admin permission
require_login();

global $USER, $DB, $OUTPUT, $PAGE, $CFG;

// Verify site administrator
if (!is_siteadmin($USER->id)) {
    print_error('accessdenied', 'admin', '', null,
        get_string('upgrade_admin_required', 'admin'));
}

// Get version information
$currentversion = upgrade_get_current_version();
$targetversion = upgrade_get_target_version();

// Get release info from version.php
$plugin = new stdClass();
require_once($CFG->dirroot . '/lib/version.php');
$targetrelease = $plugin->release ?? '';
$targetmaturity = $plugin->maturity ?? MATURITY_STABLE;

// Check if upgrade is needed
$needsupgrade = upgrade_is_needed();

// Check if upgrade is already running
$upgraderunning = upgrade_is_running();

// Process upgrade request
$upgradeexecuted = false;
$upgraderesult = null;
$upgradelog = [];
$upgradeerrors = [];

if (isset($_POST['upgrade']) && ($_POST['upgrade'] === 'start' || $_POST['upgrade'] === 'true')) {
    // Verify sesskey for security
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        print_error('invalidsesskey');
    }

    // Check if already running
    if ($upgraderunning) {
        print_error('upgradealreadyrunning', 'admin');
    }

    // Execute upgrade
    $upgradeexecuted = true;

    try {
        // Mark upgrade as started
        upgrade_started(600); // 10 minute timeout

        // Disable caches during upgrade
        if (!defined('CACHE_DISABLE_ALL')) {
            define('CACHE_DISABLE_ALL', true);
        }

        $upgradelog[] = ['message' => 'Iniciando actualización...', 'type' => 'info'];

        // Step 1: Check environment
        $upgradelog[] = ['message' => 'Verificando requisitos del sistema...', 'type' => 'info'];
        list($envstatus, $envresults) = check_nexosupport_environment(normalize_version($targetrelease));

        if (!$envstatus) {
            $hasfail = false;
            foreach ($envresults as $result) {
                if ($result['status'] === ENVIRONMENT_FAIL) {
                    $hasfail = true;
                    $upgradeerrors[] = $result['name'] . ': ' . $result['message'];
                }
            }
            if ($hasfail) {
                throw new Exception('Requisitos del sistema no cumplidos');
            }
        }
        $upgradelog[] = ['message' => 'Requisitos del sistema OK', 'type' => 'success'];

        // Step 2: Purge caches
        $upgradelog[] = ['message' => 'Limpiando cachés...', 'type' => 'info'];
        purge_all_caches();
        $upgradelog[] = ['message' => 'Cachés limpiadas', 'type' => 'success'];

        // Step 3: Run pre-upgrade script if exists
        $preupgradefile = $CFG->dirroot . '/local/preupgrade.php';
        if (file_exists($preupgradefile)) {
            $upgradelog[] = ['message' => 'Ejecutando script pre-actualización...', 'type' => 'info'];
            require($preupgradefile);
            $upgradelog[] = ['message' => 'Script pre-actualización completado', 'type' => 'success'];
        }

        // Step 4: Execute core upgrade
        $upgradelog[] = ['message' => "Actualizando core de $currentversion a $targetversion...", 'type' => 'info'];

        require_once($CFG->dirroot . '/lib/db/upgrade.php');

        // Execute upgrade function
        ob_start();
        $result = xmldb_core_upgrade($currentversion);
        $upgradeoutput = ob_get_clean();

        if (!$result) {
            throw new Exception('La función de actualización del core falló');
        }

        // Save new version
        upgrade_main_savepoint(true, $targetversion);

        $upgradelog[] = ['message' => "Core actualizado a versión $targetversion", 'type' => 'success'];

        // Step 5: Upgrade plugins
        $upgradelog[] = ['message' => 'Verificando plugins...', 'type' => 'info'];

        try {
            if (class_exists('\core\plugin\plugin_manager')) {
                $pluginman = \core\plugin\plugin_manager::instance();
                $plugins = $pluginman->get_all_plugins();
                $plugincount = 0;

                foreach ($plugins as $type => $typeplugins) {
                    foreach ($typeplugins as $name => $plugininfo) {
                        $status = $pluginman->get_plugin_status($type, $name);

                        if ($status === \core\plugin\plugin_manager::STATUS_NEW) {
                            $upgradelog[] = ['message' => "Instalando plugin: {$type}_{$name}...", 'type' => 'info'];
                            $pluginman->install_plugin($type, $name);
                            $plugincount++;
                        } elseif ($status === \core\plugin\plugin_manager::STATUS_UPGRADE) {
                            $upgradelog[] = ['message' => "Actualizando plugin: {$type}_{$name}...", 'type' => 'info'];
                            $pluginman->upgrade_plugin($type, $name);
                            $plugincount++;
                        }
                    }
                }

                if ($plugincount > 0) {
                    $upgradelog[] = ['message' => "$plugincount plugins procesados", 'type' => 'success'];
                } else {
                    $upgradelog[] = ['message' => 'Todos los plugins están actualizados', 'type' => 'success'];
                }
            }
        } catch (Exception $e) {
            $upgradelog[] = ['message' => 'Advertencia de plugins: ' . $e->getMessage(), 'type' => 'warning'];
        }

        // Step 6: Final cleanup
        $upgradelog[] = ['message' => 'Finalizando actualización...', 'type' => 'info'];
        purge_all_caches();

        // Mark upgrade as finished
        upgrade_finished();

        $upgradelog[] = ['message' => '¡Actualización completada exitosamente!', 'type' => 'success'];
        $upgraderesult = ['success' => true];

    } catch (Exception $e) {
        // Mark upgrade as finished (so it can be retried)
        upgrade_finished();

        $upgradeerrors[] = $e->getMessage();
        $upgradelog[] = ['message' => 'ERROR: ' . $e->getMessage(), 'type' => 'error'];
        $upgraderesult = ['success' => false, 'error' => $e->getMessage()];
    }
}

// Build context for template
$context = [
    'pagetitle' => get_string('upgrade_title', 'admin'),
    'sesskey' => sesskey(),

    // Version info
    'current_version' => $currentversion ?: get_string('notinstalled', 'admin'),
    'db_version' => $currentversion ?: get_string('notinstalled', 'admin'),
    'target_version' => $targetversion,
    'code_version' => $targetversion,
    'target_release' => $targetrelease,
    'release' => $targetrelease,

    // Status flags
    'needs_upgrade' => $needsupgrade,
    'upgrade_running' => $upgraderunning,
    'upgrade_executed' => $upgradeexecuted,

    // Results
    'success' => isset($upgraderesult['success']) ? $upgraderesult['success'] : false,
    'upgrade_success' => isset($upgraderesult['success']) ? $upgraderesult['success'] : false,
    'log' => $upgradelog,
    'upgrade_log' => $upgradelog,
    'has_log' => !empty($upgradelog),
    'errors' => $upgradeerrors,
    'upgrade_errors' => $upgradeerrors,
    'has_errors' => !empty($upgradeerrors),

    // Navigation
    'showadmin' => true,
    'has_navigation' => true,

    // Maturity
    'is_stable' => $targetmaturity >= MATURITY_STABLE,
    'maturity_warning' => $targetmaturity < MATURITY_STABLE,
    'maturity_level' => get_maturity_string($targetmaturity),

    // Compatibility with old template variables
    'requirements_ok' => true,
    'requirement_issues' => [],
    'has_upgrades' => $needsupgrade,
    'upgrades_to_execute' => [],
];

// Get navigation HTML if function exists
if (function_exists('get_navigation_html')) {
    $context['navigation_html'] = get_navigation_html();
}

// Check environment for display
if (!$upgradeexecuted && $needsupgrade) {
    list($envstatus, $envresults) = check_nexosupport_environment(normalize_version($targetrelease));
    $context['environment_ok'] = $envstatus;
    $context['requirements_ok'] = $envstatus;
    $context['environment_results'] = $envresults;

    // Convert to requirement_issues for template compatibility
    if (!$envstatus) {
        foreach ($envresults as $result) {
            if ($result['status'] !== ENVIRONMENT_PASS) {
                $context['requirement_issues'][] = [
                    'name' => $result['name'],
                    'message' => $result['message']
                ];
            }
        }
    }
}

// Try to render with template, fallback to inline HTML
if (function_exists('render_template')) {
    echo render_template('admin/upgrade', $context);
} else {
    render_upgrade_page($context);
}

/**
 * Get maturity level string
 */
function get_maturity_string($maturity) {
    $strings = [
        MATURITY_ALPHA => 'Alpha',
        MATURITY_BETA => 'Beta',
        MATURITY_RC => 'Release Candidate',
        MATURITY_STABLE => 'Stable'
    ];
    return $strings[$maturity] ?? 'Unknown';
}

/**
 * Render upgrade page (fallback when template not available)
 */
function render_upgrade_page($context) {
    global $CFG;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($context['pagetitle']); ?> - NexoSupport</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f6fa; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #1B9E88 0%, #167a6a 100%); color: white; padding: 30px; }
        .card-header h1 { font-size: 28px; margin-bottom: 5px; }
        .card-header p { opacity: 0.9; }
        .card-body { padding: 30px; }
        .version-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .version-box { padding: 20px; border-radius: 8px; background: #f8f9fa; }
        .version-box label { display: block; font-size: 12px; text-transform: uppercase; color: #6c757d; margin-bottom: 5px; }
        .version-box .value { font-size: 24px; font-weight: bold; color: #333; }
        .version-box.target { background: #e8f5e9; }
        .version-box.target .value { color: #1B9E88; }
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #4caf50; color: #2e7d32; }
        .alert-warning { background: #fff8e1; border-left: 4px solid #ff9800; color: #f57c00; }
        .alert-danger { background: #ffebee; border-left: 4px solid #f44336; color: #c62828; }
        .alert-info { background: #e3f2fd; border-left: 4px solid #2196f3; color: #1565c0; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 28px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #1B9E88 0%, #167a6a 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(27, 158, 136, 0.4); }
        .btn-secondary { background: #f8f9fa; color: #333; border: 2px solid #e9ecef; }
        .upgrade-log { background: #1a1a2e; border-radius: 8px; padding: 20px; margin: 20px 0; max-height: 400px; overflow-y: auto; }
        .log-item { padding: 8px 0; font-family: 'Monaco', 'Menlo', monospace; font-size: 13px; color: #eee; }
        .log-item.success { color: #68d391; }
        .log-item.error { color: #fc8181; }
        .log-item.warning { color: #fbd38d; }
        .log-item.info { color: #63b3ed; }
        .log-item::before { margin-right: 10px; }
        .log-item.success::before { content: '✓'; }
        .log-item.error::before { content: '✗'; }
        .log-item.warning::before { content: '⚠'; }
        .log-item.info::before { content: '→'; }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>NexoSupport Upgrade</h1>
                <p>Sistema de Actualización</p>
            </div>
            <div class="card-body">
                <?php if ($context['upgrade_executed']): ?>
                    <?php if ($context['upgrade_success']): ?>
                        <div class="alert alert-success">
                            <strong>Actualización completada</strong><br>
                            NexoSupport ha sido actualizado exitosamente a la versión <?php echo htmlspecialchars($context['target_release']); ?>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>Error en la actualización</strong><br>
                            La actualización encontró errores.
                        </div>
                    <?php endif; ?>
                    <h3>Log de Actualización</h3>
                    <div class="upgrade-log">
                        <?php foreach ($context['upgrade_log'] as $log): ?>
                            <div class="log-item <?php echo htmlspecialchars($log['type']); ?>"><?php echo htmlspecialchars($log['message']); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo htmlspecialchars($CFG->wwwroot); ?>" class="btn btn-primary">Ir al Inicio</a>
                    </div>
                <?php elseif ($context['needs_upgrade']): ?>
                    <div class="version-info">
                        <div class="version-box"><label>Versión Actual</label><div class="value"><?php echo htmlspecialchars($context['current_version']); ?></div></div>
                        <div class="version-box target"><label>Nueva Versión</label><div class="value"><?php echo htmlspecialchars($context['target_version']); ?></div><small><?php echo htmlspecialchars($context['target_release']); ?></small></div>
                    </div>
                    <div class="alert alert-info">
                        <strong>Listo para actualizar</strong><br>
                        Asegúrese de tener un respaldo de la base de datos antes de continuar.
                    </div>
                    <form method="post" action="">
                        <input type="hidden" name="sesskey" value="<?php echo htmlspecialchars($context['sesskey']); ?>">
                        <input type="hidden" name="upgrade" value="start">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Iniciar Actualización</button>
                            <a href="<?php echo htmlspecialchars($CFG->wwwroot); ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>Sistema actualizado</strong><br>
                        NexoSupport está ejecutando la última versión disponible (<?php echo htmlspecialchars($context['target_release']); ?>).
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo htmlspecialchars($CFG->wwwroot); ?>" class="btn btn-primary">Ir al Inicio</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
    <?php
}
