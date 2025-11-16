<?php
/**
 * NexoSupport - Plugin Manager Tool
 *
 * Frankenstyle admin tool for managing plugins/components
 *
 * @package    tool_pluginmanager
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Require system bootstrap
require_once __DIR__ . '/../../../bootstrap.php';

// Define as internal access
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Require login and admin capability
require_login();
require_capability('tool/pluginmanager:manage');

// Load tool library
require_once __DIR__ . '/lib.php';

use tool_pluginmanager\plugin_manager;

$pluginManager = new plugin_manager();

// Get installed plugins
$plugins = $pluginManager->get_installed_plugins();
$pluginTypes = $pluginManager->get_plugin_types();

// Render page
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo tool_pluginmanager_get_title(); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .plugin-type {
            margin-bottom: 30px;
        }
        .plugin-type h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .plugin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        .plugin-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        .plugin-card h3 {
            margin-bottom: 5px;
            color: #333;
        }
        .plugin-card .component {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .plugin-card .version {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .plugin-card .description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .badge-stable {
            background: #d4edda;
            color: #155724;
        }
        .badge-beta {
            background: #fff3cd;
            color: #856404;
        }
        .badge-alpha {
            background: #f8d7da;
            color: #721c24;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin" class="back-link">← Back to Admin</a>

        <h1><?php echo tool_pluginmanager_get_title(); ?></h1>
        <p class="subtitle">Manage installed plugins and components</p>

        <?php if (empty($plugins)): ?>
            <div class="empty-state">
                <p>No plugins found</p>
            </div>
        <?php else: ?>
            <?php foreach ($pluginTypes as $type => $typeName): ?>
                <?php if (isset($plugins[$type]) && !empty($plugins[$type])): ?>
                    <div class="plugin-type">
                        <h2><?php echo htmlspecialchars($typeName); ?> (<?php echo count($plugins[$type]); ?>)</h2>
                        <div class="plugin-grid">
                            <?php foreach ($plugins[$type] as $plugin): ?>
                                <div class="plugin-card">
                                    <h3><?php echo htmlspecialchars($plugin['name'] ?? 'Unknown'); ?></h3>
                                    <div class="component"><?php echo htmlspecialchars($plugin['component'] ?? ''); ?></div>
                                    <div class="version">Version: <?php echo htmlspecialchars($plugin['version'] ?? 'N/A'); ?></div>
                                    <?php if (!empty($plugin['description'])): ?>
                                        <div class="description"><?php echo htmlspecialchars($plugin['description']); ?></div>
                                    <?php endif; ?>
                                    <span class="badge badge-<?php echo htmlspecialchars($plugin['maturity'] ?? 'stable'); ?>">
                                        <?php echo strtoupper($plugin['maturity'] ?? 'STABLE'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
