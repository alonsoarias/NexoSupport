<?php
/**
 * User profile
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();

global $USER;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - NexoSupport</title>
</head>
<body>
    <h1>Mi Perfil</h1>

    <p><strong>Usuario:</strong> <?php echo htmlspecialchars($USER->username); ?></p>
    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($USER->email); ?></p>

    <p><a href="/">Volver al inicio</a></p>
    <p><a href="/logout">Cerrar sesión</a></p>
</body>
</html>
