<?php
/**
 * System Settings
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

$errors = [];
$success = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $sitename = required_param('sitename', PARAM_TEXT);
    $debug = optional_param('debug', 0, PARAM_BOOL);
    $sessiontimeout = required_param('sessiontimeout', PARAM_INT);

    try {
        set_config('sitename', $sitename, 'core');
        set_config('debug', $debug ? 'true' : 'false', 'core');
        set_config('sessiontimeout', $sessiontimeout, 'core');

        $success = get_string('configsaved');
    } catch (Exception $e) {
        $errors[] = get_string('errorconfig', 'core', $e->getMessage());
    }
}

// Cargar configuración actual
$sitename = get_config('core', 'sitename') ?? 'NexoSupport';
$debug = get_config('core', 'debug') === 'true';
$sessiontimeout = get_config('core', 'sessiontimeout') ?? 7200;

?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('systemsettings'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .nav {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .nav a {
            margin-right: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        h1 {
            margin-top: 0;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .settings-group {
            margin-bottom: 30px;
        }

        .settings-group h2 {
            color: #667eea;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/"><?php echo get_string('home'); ?></a>
        <a href="/admin"><?php echo get_string('administration'); ?></a>
        <a href="/admin/settings"><?php echo get_string('settings'); ?></a>
        <a href="/logout"><?php echo get_string('logout'); ?></a>
    </div>

    <div class="card">
        <h1><?php echo get_string('systemsettings'); ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong><?php echo get_string('error'); ?>:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <div class="settings-group">
                <h2><?php echo get_string('generalsettings'); ?></h2>

                <div class="form-group">
                    <label for="sitename"><?php echo get_string('sitename'); ?></label>
                    <input type="text"
                           id="sitename"
                           name="sitename"
                           value="<?php echo htmlspecialchars($sitename); ?>"
                           required>
                    <div class="help-text"><?php echo get_string('sitenamehelp'); ?></div>
                </div>
            </div>

            <div class="settings-group">
                <h2><?php echo get_string('sessions'); ?></h2>

                <div class="form-group">
                    <label for="sessiontimeout"><?php echo get_string('sessiontimeout'); ?></label>
                    <input type="number"
                           id="sessiontimeout"
                           name="sessiontimeout"
                           value="<?php echo $sessiontimeout; ?>"
                           min="600"
                           max="86400"
                           required>
                    <div class="help-text"><?php echo get_string('sessiontimeouthelp'); ?></div>
                </div>
            </div>

            <div class="settings-group">
                <h2><?php echo get_string('developmentsettings'); ?></h2>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox"
                               id="debug"
                               name="debug"
                               value="1"
                               <?php echo $debug ? 'checked' : ''; ?>>
                        <label for="debug" style="margin: 0;"><?php echo get_string('debugmode'); ?></label>
                    </div>
                    <div class="help-text"><?php echo get_string('debughelp'); ?></div>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <button type="submit" class="btn"><?php echo get_string('save'); ?></button>
                <a href="/admin" class="btn btn-secondary"><?php echo get_string('back'); ?></a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><?php echo get_string('systeminfo'); ?></h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;"><?php echo get_string('systemversion'); ?></td>
                <td style="padding: 10px;"><?php echo get_config('core', 'version') ?? get_string('unknown'); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;"><?php echo get_string('phpversion'); ?></td>
                <td style="padding: 10px;"><?php echo phpversion(); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;"><?php echo get_string('database'); ?></td>
                <td style="padding: 10px;"><?php echo $CFG->dbtype; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;"><?php echo get_string('tableprefix'); ?></td>
                <td style="padding: 10px;"><?php echo $CFG->dbprefix; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;"><?php echo get_string('currentuser'); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($USER->username ?? 'Guest'); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
