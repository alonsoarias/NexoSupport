<?php
/**
 * Login page
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $USER, $CFG;

// Si ya está logueado, redirigir al home
if (isset($USER->id) && $USER->id > 0) {
    redirect('/');
}

$error = null;

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = required_param('username', PARAM_ALPHANUMEXT);
    $password = required_param('password', PARAM_RAW);

    // Obtener plugin de autenticación configurado
    $authmethod = get_config('core', 'auth') ?? 'manual';
    $authplugin = \core\plugin\manager::get_auth_plugin($authmethod);

    if (!$authplugin) {
        $error = get_string('authpluginnotfound');
    } else {
        // Intentar autenticar
        $user = $authplugin->authenticate($username, $password);

        if ($user) {
            // Login exitoso
            $_SESSION['USER'] = $user;
            $USER = $user;

            // Hook post-login del plugin
            $authplugin->post_login_hook($user);

            // Redirigir al home
            redirect('/');
        } else {
            $error = get_string('invalidlogin', 'core');
        }
    }
}

// Prepare context for template
$context = [
    'error' => $error ? htmlspecialchars($error) : null,
];

// Render and output
echo render_template('core/login', $context);
