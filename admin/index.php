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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - NexoSupport</title>
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
        <a href="/">Inicio</a>
        <a href="/admin">Administración</a>
        <a href="/user/profile">Mi Perfil</a>
        <a href="/logout">Cerrar sesión</a>
    </div>

    <h1>Panel de Administración</h1>
    <p>Bienvenido, <?php echo htmlspecialchars($USER->firstname); ?></p>

    <div class="admin-grid">
        <a href="/admin/users" class="admin-card">
            <h3>Usuarios</h3>
            <p>Gestionar usuarios del sistema</p>
        </a>

        <a href="/admin/roles" class="admin-card">
            <h3>Roles y Permisos</h3>
            <p>Configurar roles y capabilities</p>
        </a>

        <a href="/admin/settings" class="admin-card">
            <h3>Configuración</h3>
            <p>Configuración general del sistema</p>
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
