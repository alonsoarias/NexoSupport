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
?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('title_login'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
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
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: normal;
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
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><?php echo get_string('sitename'); ?></h1>
        <h2><?php echo get_string('title_login'); ?></h2>

        <?php if ($error): ?>
            <div class="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username"><?php echo get_string('username', 'core'); ?></label>
                <input type="text" name="username" id="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?php echo get_string('password', 'core'); ?></label>
                <input type="password" name="password" id="password" required>
            </div>

            <button type="submit" class="btn"><?php echo get_string('login', 'core'); ?></button>
        </form>
    </div>
</body>
</html>
