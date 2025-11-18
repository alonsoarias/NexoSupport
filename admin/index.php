<?php
/**
 * Admin panel
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();

global $USER;
?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('administration'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .admin-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .admin-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .admin-card h3 {
            color: #667eea;
            margin-bottom: 10px;
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
    </style>
</head>
<body>
    <div class="nav">
        <a href="/"><?php echo get_string('home'); ?></a>
        <a href="/admin"><?php echo get_string('administration'); ?></a>
        <a href="/user/profile"><?php echo get_string('profile'); ?></a>
        <a href="/logout"><?php echo get_string('logout'); ?></a>
    </div>

    <h1><?php echo get_string('adminarea'); ?></h1>
    <p><?php echo get_string('welcome'); ?>, <?php echo htmlspecialchars($USER->firstname); ?></p>

    <div class="admin-grid">
        <a href="/admin/users" class="admin-card">
            <h3><?php echo get_string('users'); ?></h3>
            <p><?php echo get_string('manageusers_desc'); ?></p>
        </a>

        <a href="/admin/roles" class="admin-card">
            <h3><?php echo get_string('roles'); ?></h3>
            <p><?php echo get_string('manageroles_desc'); ?></p>
        </a>

        <a href="/admin/settings" class="admin-card">
            <h3><?php echo get_string('settings'); ?></h3>
            <p><?php echo get_string('managesettings_desc'); ?></p>
        </a>

        <div class="admin-card" style="opacity: 0.5;">
            <h3>Plugins</h3>
            <p>Gestionar plugins (Fase 2)</p>
        </div>

        <div class="admin-card" style="opacity: 0.5;">
            <h3>Temas</h3>
            <p>Personalizar apariencia (Fase 6)</p>
        </div>

        <div class="admin-card" style="opacity: 0.5;">
            <h3>Reportes</h3>
            <p>Ver reportes del sistema (Fase 5)</p>
        </div>
    </div>
</body>
</html>
