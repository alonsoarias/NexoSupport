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

        $success = 'Configuración guardada exitosamente';
    } catch (Exception $e) {
        $errors[] = 'Error guardando configuración: ' . $e->getMessage();
    }
}

// Cargar configuración actual
$sitename = get_config('core', 'sitename') ?? 'NexoSupport';
$debug = get_config('core', 'debug') === 'true';
$sessiontimeout = get_config('core', 'sessiontimeout') ?? 7200;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - NexoSupport</title>
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
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/admin/settings">Configuración</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <div class="card">
        <h1>Configuración del Sistema</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong>
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
                <h2>General</h2>

                <div class="form-group">
                    <label for="sitename">Nombre del Sitio</label>
                    <input type="text"
                           id="sitename"
                           name="sitename"
                           value="<?php echo htmlspecialchars($sitename); ?>"
                           required>
                    <div class="help-text">Nombre que aparece en el encabezado y correos</div>
                </div>
            </div>

            <div class="settings-group">
                <h2>Sesiones</h2>

                <div class="form-group">
                    <label for="sessiontimeout">Timeout de Sesión (segundos)</label>
                    <input type="number"
                           id="sessiontimeout"
                           name="sessiontimeout"
                           value="<?php echo $sessiontimeout; ?>"
                           min="600"
                           max="86400"
                           required>
                    <div class="help-text">Tiempo de inactividad antes de cerrar sesión. Rango: 10 min (600) - 24 hrs (86400). Valor recomendado: 7200 (2 horas)</div>
                </div>
            </div>

            <div class="settings-group">
                <h2>Desarrollo</h2>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox"
                               id="debug"
                               name="debug"
                               value="1"
                               <?php echo $debug ? 'checked' : ''; ?>>
                        <label for="debug" style="margin: 0;">Modo Debug</label>
                    </div>
                    <div class="help-text">Habilita mensajes de debug en logs. Solo para desarrollo.</div>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <button type="submit" class="btn">Guardar Configuración</button>
                <a href="/admin" class="btn btn-secondary">Volver a Administración</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Información del Sistema</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;">Versión del Sistema</td>
                <td style="padding: 10px;"><?php echo get_config('core', 'version') ?? 'Unknown'; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;">Versión de PHP</td>
                <td style="padding: 10px;"><?php echo phpversion(); ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;">Base de Datos</td>
                <td style="padding: 10px;"><?php echo $CFG->dbtype; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;">Prefijo de Tablas</td>
                <td style="padding: 10px;"><?php echo $CFG->dbprefix; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 10px; font-weight: 600;">Usuario Actual</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($USER->username ?? 'Guest'); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
