<?php
/**
 * NexoSupport - Install Plugin Interface
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../../../lib/setup.php';
require_once __DIR__ . '/../../../lib/accesslib.php';

defined('NEXOSUPPORT_INTERNAL') || die();

require_capability('tool/installaddon:install');

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'ISER\\Tools\\InstallAddon\\';
    $base_dir = __DIR__ . '/classes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use ISER\Tools\InstallAddon\AddonInstaller;
use ISER\Tools\InstallAddon\AddonValidator;
use ISER\Tools\InstallAddon\ZipExtractor;

$message = '';
$error = '';
$log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip'])) {
    $upload = $_FILES['plugin_zip'];

    if ($upload['error'] === UPLOAD_ERR_OK) {
        $validator = new AddonValidator();
        $valid_result = $validator->validate_zip($upload['tmp_name']);

        if ($valid_result['success']) {
            $installer = new AddonInstaller();
            $install_result = $installer->install_from_zip($upload['tmp_name']);

            if ($install_result['success']) {
                $message = "Plugin installed successfully: {$install_result['component']}";
                $log = $install_result['log'];
            } else {
                $error = $install_result['error'];
            }
        } else {
            $error = $valid_result['error'];
        }
    } else {
        $error = 'File upload failed';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Plugin - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { margin-bottom: 30px; color: #333; }
        .section { background: white; padding: 30px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .message { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .upload-area { border: 2px dashed #ddd; border-radius: 8px; padding: 40px; text-align: center; background: #fafafa; margin-bottom: 20px; }
        .upload-area:hover { border-color: #1e3a8a; background: #f0f4ff; }
        .btn { padding: 12px 24px; background: #1e3a8a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #1e40af; }
        .log { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 13px; }
        .log-item { margin-bottom: 5px; color: #666; }
        ul { margin-left: 20px; margin-top: 10px; }
        li { margin-bottom: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Install Plugin</h1>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php if (!empty($log)): ?>
                <div class="section">
                    <h3 style="margin-bottom: 15px;">Installation Log</h3>
                    <div class="log">
                        <?php foreach ($log as $entry): ?>
                            <div class="log-item">▸ <?= htmlspecialchars($entry) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="section">
            <h2 style="margin-bottom: 20px;">Upload Plugin Package</h2>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <p style="margin-bottom: 15px; font-size: 18px;">📦 Select ZIP file to install</p>
                    <input type="file" name="plugin_zip" accept=".zip" required style="margin-bottom: 15px;">
                    <p style="color: #666; font-size: 14px;">Maximum file size: 50MB</p>
                </div>

                <button type="submit" class="btn">Install Plugin</button>
            </form>
        </div>

        <div class="section">
            <h3 style="margin-bottom: 15px;">Requirements</h3>
            <p style="margin-bottom: 15px; color: #666;">Plugin packages must follow Frankenstyle naming and structure:</p>
            <ul>
                <li><strong>Required files:</strong> version.php and lib.php</li>
                <li><strong>Component naming:</strong> type_name (e.g., tool_example, auth_example)</li>
                <li><strong>Valid types:</strong> tool, auth, theme, report, factor</li>
                <li><strong>ZIP structure:</strong> All files must be in the root of the ZIP (no subdirectory)</li>
                <li><strong>Security:</strong> No dangerous functions (eval, exec, system, etc.)</li>
            </ul>
        </div>
    </div>
</body>
</html>
