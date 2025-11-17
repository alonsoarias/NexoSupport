<?php
/**
 * Stage: Welcome
 */

$progress = 0;
?>

<h1>NexoSupport</h1>
<h2>Instalación del Sistema</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<div class="alert alert-info">
    <strong>Bienvenido a NexoSupport</strong><br>
    Este asistente le guiará a través del proceso de instalación.
</div>

<p>NexoSupport es un sistema de gestión construido con arquitectura Frankenstyle de Moodle.</p>

<h3 style="margin-top: 30px; margin-bottom: 15px;">Características:</h3>
<ul style="margin-left: 20px; line-height: 1.8;">
    <li>Sistema de plugins extensible</li>
    <li>Control de acceso basado en roles (RBAC)</li>
    <li>Autenticación multi-factor (MFA)</li>
    <li>Sistema de temas personalizable</li>
    <li>Reportes configurables</li>
</ul>

<div class="actions">
    <a href="/install?stage=requirements" class="btn">Comenzar instalación</a>
</div>
