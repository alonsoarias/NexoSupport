<?php
/**
 * Stage: Finish Installation
 */

$progress = 100;

session_start();

if (!isset($_SESSION['admin_created'])) {
    header('Location: /install?stage=admin');
    exit;
}

// Marcar como instalado
$envPath = BASE_DIR . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $envContent = str_replace('INSTALLED=false', 'INSTALLED=true', $envContent);
    file_put_contents($envPath, $envContent);
}

// Crear archivo .installed
file_put_contents(BASE_DIR . '/.installed', date('Y-m-d H:i:s'));

// Limpiar sesión
session_destroy();
?>

<h1>¡Instalación Completada!</h1>
<h2>NexoSupport está listo para usar</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<div class="alert alert-success">
    <strong>¡Felicidades!</strong> NexoSupport ha sido instalado exitosamente.
</div>

<div style="background: #f5f5f5; padding: 20px; border-radius: 6px; margin: 20px 0;">
    <h3 style="margin-top: 0;">Información importante:</h3>
    <ul style="line-height: 1.8; margin-left: 20px;">
        <li>El sistema ha sido configurado correctamente</li>
        <li>Se ha creado la cuenta de administrador</li>
        <li>Las tablas de la base de datos están instaladas</li>
        <li>Ya puede iniciar sesión y comenzar a usar NexoSupport</li>
    </ul>
</div>

<div class="alert alert-info">
    <strong>Próximos pasos:</strong><br>
    1. Inicie sesión con su cuenta de administrador<br>
    2. Configure el sistema desde el panel de administración<br>
    3. Cree usuarios y asigne roles<br>
    4. Personalice el tema y la apariencia
</div>

<div class="actions">
    <a href="/" class="btn">Ir al sistema</a>
</div>
