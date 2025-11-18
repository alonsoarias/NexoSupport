<?php
/**
 * Manual Authentication Settings
 *
 * @package auth_manual
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageconfig');

global $CFG;

$errors = [];
$success = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $minpasswordlength = required_param('minpasswordlength', PARAM_INT);
    $requireuppercase = optional_param('requireuppercase', 0, PARAM_BOOL);
    $requirelowercase = optional_param('requirelowercase', 0, PARAM_BOOL);
    $requirenumbers = optional_param('requirenumbers', 0, PARAM_BOOL);
    $requirespecialchars = optional_param('requirespecialchars', 0, PARAM_BOOL);

    // Validate minimum password length
    if ($minpasswordlength < 6) {
        $errors[] = get_string('minpasswordlengtherror', 'auth_manual');
    } elseif ($minpasswordlength > 64) {
        $errors[] = get_string('minpasswordlengthmaxerror', 'auth_manual');
    }

    if (empty($errors)) {
        try {
            set_config('minpasswordlength', $minpasswordlength, 'auth_manual');
            set_config('requireuppercase', $requireuppercase, 'auth_manual');
            set_config('requirelowercase', $requirelowercase, 'auth_manual');
            set_config('requirenumbers', $requirenumbers, 'auth_manual');
            set_config('requirespecialchars', $requirespecialchars, 'auth_manual');

            $success = get_string('configsaved');
        } catch (Exception $e) {
            $errors[] = get_string('errorconfig', 'core', $e->getMessage());
        }
    }
}

// Load current configuration values
$minpasswordlength = get_config('auth_manual', 'minpasswordlength') ?? 8;
$requireuppercase = get_config('auth_manual', 'requireuppercase') ?? 0;
$requirelowercase = get_config('auth_manual', 'requirelowercase') ?? 0;
$requirenumbers = get_config('auth_manual', 'requirenumbers') ?? 0;
$requirespecialchars = get_config('auth_manual', 'requirespecialchars') ?? 0;

$currentlang = \core\string_manager::get_language();

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentlang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('auth_manual_settings', 'auth_manual'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            gap: 20px;
            list-style: none;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .navbar-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .settings-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input[type="number"] {
            width: 100%;
            max-width: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input[type="checkbox"] {
            margin-right: 8px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
        }

        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
            margin-left: 24px;
        }

        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="navbar-brand"><?php echo get_string('sitename'); ?></a>
            <ul class="navbar-nav">
                <li><a href="/"><?php echo get_string('dashboard'); ?></a></li>
                <li><a href="/admin"><?php echo get_string('administration'); ?></a></li>
                <li><a href="/logout"><?php echo get_string('logout'); ?></a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="page-header">
            <h1><?php echo get_string('auth_manual_settings', 'auth_manual'); ?></h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="settings-form">
            <form method="POST" action="/auth/manual/settings">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="form-section">
                    <h2><?php echo get_string('passwordpolicy', 'auth_manual'); ?></h2>

                    <div class="form-group">
                        <label for="minpasswordlength">
                            <?php echo get_string('minpasswordlength', 'auth_manual'); ?>
                        </label>
                        <input type="number"
                               id="minpasswordlength"
                               name="minpasswordlength"
                               value="<?php echo intval($minpasswordlength); ?>"
                               min="6"
                               max="64"
                               required>
                        <div class="help-text">
                            <?php echo get_string('minpasswordlength_help', 'auth_manual'); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox"
                                   id="requireuppercase"
                                   name="requireuppercase"
                                   value="1"
                                   <?php echo $requireuppercase ? 'checked' : ''; ?>>
                            <label for="requireuppercase">
                                <?php echo get_string('requireuppercase', 'auth_manual'); ?>
                            </label>
                        </div>
                        <div class="help-text">
                            <?php echo get_string('requireuppercase_help', 'auth_manual'); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox"
                                   id="requirelowercase"
                                   name="requirelowercase"
                                   value="1"
                                   <?php echo $requirelowercase ? 'checked' : ''; ?>>
                            <label for="requirelowercase">
                                <?php echo get_string('requirelowercase', 'auth_manual'); ?>
                            </label>
                        </div>
                        <div class="help-text">
                            <?php echo get_string('requirelowercase_help', 'auth_manual'); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox"
                                   id="requirenumbers"
                                   name="requirenumbers"
                                   value="1"
                                   <?php echo $requirenumbers ? 'checked' : ''; ?>>
                            <label for="requirenumbers">
                                <?php echo get_string('requirenumbers', 'auth_manual'); ?>
                            </label>
                        </div>
                        <div class="help-text">
                            <?php echo get_string('requirenumbers_help', 'auth_manual'); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox"
                                   id="requirespecialchars"
                                   name="requirespecialchars"
                                   value="1"
                                   <?php echo $requirespecialchars ? 'checked' : ''; ?>>
                            <label for="requirespecialchars">
                                <?php echo get_string('requirespecialchars', 'auth_manual'); ?>
                            </label>
                        </div>
                        <div class="help-text">
                            <?php echo get_string('requirespecialchars_help', 'auth_manual'); ?>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo get_string('savechanges'); ?>
                    </button>
                    <a href="/admin" class="btn btn-secondary">
                        <?php echo get_string('cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
