<?php
/**
 * System Upgrade Page
 *
 * Detecta y ejecuta actualizaciones del sistema.
 * Similar a admin/index.php?upgrade en Moodle.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once(__DIR__ . '/../lib/upgrade.php');

// Get versions
$dbversion = get_core_version_from_db();
$codeversion = get_core_version_from_code();

// Get version info
require(__DIR__ . '/../lib/version.php');
$release = $plugin->release;

// Check if upgrade is needed
$upgradeNeeded = core_upgrade_required();

// Process upgrade request
$upgradeExecuted = false;
$upgradeSuccess = false;
$upgradeErrors = [];

if (isset($_POST['upgrade']) && $_POST['upgrade'] === 'true') {
    // Verify sesskey for security
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        $upgradeErrors[] = get_string('invalidtoken');
    } else {
        // Execute upgrade
        $upgradeExecuted = true;

        try {
            // Run core upgrade
            $upgradeSuccess = xmldb_core_upgrade($dbversion ?? 0);

            if (!$upgradeSuccess) {
                $upgradeErrors[] = get_string('error');
            }
        } catch (Exception $e) {
            $upgradeErrors[] = get_string('error') . ': ' . $e->getMessage();
            $upgradeSuccess = false;
        }

        // Refresh version after upgrade
        if ($upgradeSuccess) {
            $dbversion = get_core_version_from_db();
            $upgradeNeeded = core_upgrade_required();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('upgrade'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: normal;
        }

        .version-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .version-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .version-row:last-child {
            border-bottom: none;
        }

        .version-label {
            font-weight: 600;
            color: #555;
        }

        .version-value {
            color: #667eea;
            font-family: monospace;
            font-weight: bold;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .alert-info {
            background: #e3f2fd;
            color: #0277bd;
            border-left: 4px solid #0277bd;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-warning {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #e65100;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .upgrade-list {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .upgrade-list ul {
            margin: 10px 0 10px 20px;
            line-height: 1.8;
        }

        .upgrade-list li {
            margin: 5px 0;
        }

        .error-list {
            margin: 10px 0;
            padding-left: 20px;
        }

        .checkmark {
            color: #2e7d32;
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo get_string('upgrade'); ?></h1>
        <h2><?php echo get_string('sitename'); ?></h2>

        <div class="version-info">
            <div class="version-row">
                <span class="version-label"><?php echo get_string('currentversion'); ?>:</span>
                <span class="version-value"><?php echo $dbversion ?? get_string('notfound'); ?></span>
            </div>
            <div class="version-row">
                <span class="version-label"><?php echo get_string('newversion'); ?>:</span>
                <span class="version-value"><?php echo $codeversion; ?></span>
            </div>
            <div class="version-row">
                <span class="version-label">Release:</span>
                <span class="version-value"><?php echo $release; ?></span>
            </div>
        </div>

        <?php if ($upgradeExecuted && $upgradeSuccess): ?>
            <div class="alert alert-success">
                <strong><span class="checkmark">✓</span><?php echo get_string('upgradecomplete'); ?>!</strong><br>
                <?php echo get_string('systemreadyupgrade'); ?>
            </div>

            <div class="actions">
                <a href="/admin" class="btn"><?php echo get_string('administration'); ?></a>
            </div>

        <?php elseif ($upgradeExecuted && !$upgradeSuccess): ?>
            <div class="alert alert-error">
                <strong><?php echo get_string('error'); ?></strong><br>
                <?php echo get_string('error'); ?>:
                <ul class="error-list">
                    <?php foreach ($upgradeErrors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="actions">
                <a href="/admin/upgrade.php" class="btn"><?php echo get_string('upgrade'); ?></a>
                <a href="/admin" class="btn btn-secondary"><?php echo get_string('back'); ?></a>
            </div>

        <?php elseif ($upgradeNeeded): ?>
            <div class="alert alert-warning">
                <strong><?php echo get_string('requiresupgrade'); ?></strong><br>
                <?php echo get_string('systemreadyupgrade'); ?>
            </div>

            <div class="upgrade-list">
                <h3 style="margin-top: 0; color: #333;">Cambios en esta Actualización:</h3>

                <?php if ($dbversion === null || $dbversion < 2025011800): ?>
                    <h4 style="color: #667eea;">Versión 1.1.0 - Sistema RBAC Completo</h4>
                    <ul>
                        <li>✅ Sistema completo de Roles y Permisos (RBAC)</li>
                        <li>✅ Contextos jerárquicos (System, User, Course, Module)</li>
                        <li>✅ 3 Roles predefinidos (Administrator, Manager, User)</li>
                        <li>✅ 13 Capabilities del sistema</li>
                        <li>✅ Verificación granular de permisos</li>
                        <li>✅ Interfaz de gestión de roles</li>
                        <li>✅ Asignación automática de rol Administrator al usuario inicial</li>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="alert alert-info">
                <strong>Importante:</strong><br>
                • Se recomienda hacer un backup de la base de datos antes de actualizar<br>
                • La actualización puede tardar unos segundos<br>
                • No cierre esta ventana durante el proceso
            </div>

            <form method="POST">
                <input type="hidden" name="upgrade" value="true">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="actions">
                    <a href="/admin" class="btn btn-secondary"><?php echo get_string('cancel'); ?></a>
                    <button type="submit" class="btn"><?php echo get_string('upgradenow'); ?></button>
                </div>
            </form>

        <?php else: ?>
            <div class="alert alert-success">
                <strong><span class="checkmark">✓</span><?php echo get_string('upgradecomplete'); ?></strong><br>
                <?php echo get_string('noupdaterequired'); ?>
            </div>

            <div class="actions">
                <a href="/admin" class="btn"><?php echo get_string('administration'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
