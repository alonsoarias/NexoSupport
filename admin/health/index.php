<?php
/**
 * NexoSupport - System Health Dashboard
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../../lib/setup.php';
require_once __DIR__ . '/../../lib/accesslib.php';
require_once __DIR__ . '/../../lib/classes/health/health_checker.php';

use ISER\Core\Health\HealthChecker;

defined('NEXOSUPPORT_INTERNAL') || die();

// Require admin capability
require_capability('moodle/site:config');

$checks = HealthChecker::run_all_checks();
$system_info = HealthChecker::get_system_info();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { margin-bottom: 10px; color: #333; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .health-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .health-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #ddd; }
        .health-card.ok { border-left-color: #28a745; }
        .health-card.warning { border-left-color: #ffc107; }
        .health-card.error { border-left-color: #dc3545; }
        .health-card h3 { color: #333; margin-bottom: 10px; font-size: 16px; }
        .health-card .status { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .health-card .status .icon { font-size: 24px; }
        .health-card .status .message { font-size: 14px; color: #666; }
        .health-card .details { font-size: 12px; color: #999; margin-top: 10px; }
        .overall-status { background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .overall-status .icon { font-size: 48px; margin-bottom: 10px; }
        .overall-status .message { font-size: 24px; font-weight: 600; color: #333; }
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { margin-bottom: 20px; color: #333; font-size: 18px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-item { padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .info-item .label { font-size: 12px; color: #666; margin-bottom: 4px; }
        .info-item .value { font-size: 14px; color: #333; font-weight: 500; }
        .refresh-btn { padding: 10px 20px; background: #1e3a8a; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; }
        .refresh-btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 System Health</h1>
        <p class="subtitle">Monitor system health and diagnostics</p>

        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>

        <!-- Overall Status -->
        <div class="overall-status">
            <div class="icon"><?= $checks['overall']['icon'] ?></div>
            <div class="message"><?= htmlspecialchars($checks['overall']['message']) ?></div>
        </div>

        <!-- Health Checks -->
        <div class="health-grid">
            <!-- Database -->
            <div class="health-card <?= $checks['database']['status'] ?>">
                <h3>💾 Database</h3>
                <div class="status">
                    <span class="icon"><?= $checks['database']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['database']['message']) ?></span>
                </div>
                <?php if (isset($checks['database']['tables_count'])): ?>
                    <div class="details">Tables: <?= $checks['database']['tables_count'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Filesystem -->
            <div class="health-card <?= $checks['filesystem']['status'] ?>">
                <h3>📁 Filesystem</h3>
                <div class="status">
                    <span class="icon"><?= $checks['filesystem']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['filesystem']['message']) ?></span>
                </div>
            </div>

            <!-- PHP Extensions -->
            <div class="health-card <?= $checks['php']['status'] ?>">
                <h3>🔧 PHP Extensions</h3>
                <div class="status">
                    <span class="icon"><?= $checks['php']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['php']['message']) ?></span>
                </div>
                <?php if (isset($checks['php']['count'])): ?>
                    <div class="details">Required: <?= $checks['php']['count'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Disk Space -->
            <div class="health-card <?= $checks['disk']['status'] ?>">
                <h3>💿 Disk Space</h3>
                <div class="status">
                    <span class="icon"><?= $checks['disk']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['disk']['message']) ?></span>
                </div>
                <?php if (isset($checks['disk']['used_percent'])): ?>
                    <div class="details">Used: <?= $checks['disk']['used_percent'] ?>%</div>
                <?php endif; ?>
            </div>

            <!-- Cache -->
            <div class="health-card <?= $checks['cache']['status'] ?>">
                <h3>⚡ Cache</h3>
                <div class="status">
                    <span class="icon"><?= $checks['cache']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['cache']['message']) ?></span>
                </div>
                <?php if (isset($checks['cache']['stats'])): ?>
                    <div class="details">
                        Memory: <?= $checks['cache']['stats']['memory_items'] ?> |
                        Files: <?= $checks['cache']['stats']['file_cache_items'] ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Themes -->
            <div class="health-card <?= $checks['themes']['status'] ?>">
                <h3>🎨 Themes</h3>
                <div class="status">
                    <span class="icon"><?= $checks['themes']['icon'] ?></span>
                    <span class="message"><?= htmlspecialchars($checks['themes']['message']) ?></span>
                </div>
                <?php if (isset($checks['themes']['active'])): ?>
                    <div class="details">Active: <?= htmlspecialchars($checks['themes']['active']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Information -->
        <div class="section">
            <h2>📊 System Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">PHP Version</div>
                    <div class="value"><?= htmlspecialchars($system_info['php_version']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Server Software</div>
                    <div class="value"><?= htmlspecialchars($system_info['server_software']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Operating System</div>
                    <div class="value"><?= htmlspecialchars($system_info['operating_system']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Memory Limit</div>
                    <div class="value"><?= htmlspecialchars($system_info['memory_limit']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Max Execution Time</div>
                    <div class="value"><?= htmlspecialchars($system_info['max_execution_time']) ?>s</div>
                </div>
                <div class="info-item">
                    <div class="label">Upload Max Size</div>
                    <div class="value"><?= htmlspecialchars($system_info['upload_max_filesize']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Post Max Size</div>
                    <div class="value"><?= htmlspecialchars($system_info['post_max_size']) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Server Name</div>
                    <div class="value"><?= htmlspecialchars($system_info['server_name']) ?></div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="section">
            <h2>💡 Recommendations</h2>
            <ul style="margin-left: 20px; color: #666;">
                <li>Monitor disk space regularly (recommended free: >25%)</li>
                <li>Keep cache directory writable for optimal performance</li>
                <li>Update PHP to latest stable version for security</li>
                <li>Regular database backups (daily recommended)</li>
                <li>Monitor error logs for issues</li>
            </ul>
        </div>
    </div>
</body>
</html>
