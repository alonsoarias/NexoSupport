<?php
/**
 * NexoSupport - Theme Administration
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../../lib/setup.php';
require_once __DIR__ . '/../../lib/accesslib.php';
require_once __DIR__ . '/../../lib/classes/theme/theme_manager.php';

use ISER\Core\Theme\ThemeManager;

defined('NEXOSUPPORT_INTERNAL') || die();

// Require admin capability
require_capability('moodle/site:config');

$message = '';
$error = '';

// Handle theme change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_theme' && isset($_POST['theme'])) {
        if (ThemeManager::set_active_theme($_POST['theme'])) {
            $message = 'Theme changed successfully';
        } else {
            $error = 'Failed to change theme';
        }
    }
}

$active_theme = ThemeManager::get_active_theme();
$available_themes = ThemeManager::get_available_themes();
$active_info = ThemeManager::get_theme_info($active_theme);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Settings - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { margin-bottom: 30px; color: #333; }
        .message { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .section { background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section h2 { margin-bottom: 20px; color: #333; font-size: 18px; }
        .theme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .theme-card { border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; transition: all 0.2s; }
        .theme-card.active { border-color: #1e3a8a; background: #f0f4ff; }
        .theme-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .theme-card h3 { color: #333; margin-bottom: 10px; }
        .theme-card p { color: #666; font-size: 14px; margin-bottom: 15px; }
        .theme-card .meta { font-size: 12px; color: #999; margin-bottom: 15px; }
        .theme-card .badge { display: inline-block; padding: 4px 8px; background: #1e3a8a; color: white; border-radius: 3px; font-size: 12px; margin-bottom: 10px; }
        .btn { padding: 8px 16px; background: #1e3a8a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .features { list-style: none; padding: 0; margin: 15px 0; }
        .features li { padding: 4px 0; color: #666; font-size: 13px; }
        .features li::before { content: "✓ "; color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Theme Settings</h1>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Active Theme</h2>
            <?php if ($active_info): ?>
                <p><strong>Current:</strong> <?= htmlspecialchars($active_info['title']) ?> (v<?= htmlspecialchars($active_info['release']) ?>)</p>
                <p><?= htmlspecialchars($active_info['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Available Themes</h2>
            <div class="theme-grid">
                <?php foreach ($available_themes as $theme_name => $theme): ?>
                    <div class="theme-card <?= $theme_name === $active_theme ? 'active' : '' ?>">
                        <?php if ($theme_name === $active_theme): ?>
                            <span class="badge">ACTIVE</span>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($theme['title']) ?></h3>
                        <p><?= htmlspecialchars($theme['description']) ?></p>

                        <div class="meta">
                            Version: <?= htmlspecialchars($theme['release']) ?> |
                            Component: <?= htmlspecialchars($theme['component']) ?>
                        </div>

                        <?php
                        // Get theme config
                        $config = ThemeManager::get_theme_config($theme_name);
                        if (!empty($config['features'])):
                        ?>
                            <strong>Features:</strong>
                            <ul class="features">
                                <?php foreach ($config['features'] as $feature => $enabled): ?>
                                    <?php if ($enabled): ?>
                                        <li><?= ucwords(str_replace('_', ' ', $feature)) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($theme_name !== $active_theme): ?>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="set_theme">
                                <input type="hidden" name="theme" value="<?= htmlspecialchars($theme_name) ?>">
                                <button type="submit" class="btn">Activate</button>
                            </form>
                        <?php else: ?>
                            <button class="btn" disabled>Currently Active</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($active_theme === 'iser'): ?>
            <div class="section">
                <h2>ISER Theme Settings</h2>
                <p><strong>Dark Mode:</strong> Enabled - Use the toggle in the header or press <kbd>Ctrl+Shift+D</kbd></p>
                <p><strong>Color Schemes:</strong> 4 available (Default, Ocean, Forest, Sunset)</p>
                <p><strong>Customization:</strong> Logo upload, color picker, custom CSS available</p>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Theme Development</h2>
            <p>To create a new theme:</p>
            <ol style="margin-left: 20px; color: #666;">
                <li>Create directory in <code>theme/yourtheme/</code></li>
                <li>Add <code>version.php</code> with Frankenstyle metadata</li>
                <li>Add <code>lib.php</code> with capabilities and functions</li>
                <li>Add <code>config.php</code> with theme configuration</li>
                <li>Create <code>styles/</code>, <code>scripts/</code>, <code>templates/</code> directories</li>
                <li>Refresh this page to see your theme</li>
            </ol>
        </div>
    </div>
</body>
</html>
