<?php
/**
 * Stage: Welcome
 */

$progress = 0;
?>

<div class="stage-indicator">
    <i class="fas fa-rocket icon"></i>
    <div class="text">
        <div class="step-number">Paso 1 de 6</div>
        <strong>Bienvenida</strong>
    </div>
</div>

<h1><i class="fas fa-cogs icon"></i>NexoSupport</h1>
<h2>Instalación del Sistema v1.1.9</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>Bienvenido a NexoSupport</strong><br>
    Este asistente le guiará a través del proceso de instalación.
</div>

<p>NexoSupport es un sistema de gestión construido con arquitectura Frankenstyle de Moodle.</p>

<h3 style="margin-top: 30px; margin-bottom: 15px;"><i class="fas fa-star"></i> Características:</h3>
<ul class="feature-list">
    <li>Sistema de plugins extensible</li>
    <li>Control de acceso basado en roles (RBAC)</li>
    <li>Navegación moderna con Font Awesome 6</li>
    <li>Sistema de caché avanzado (OPcache, i18n, Mustache)</li>
    <li>Templates Mustache para personalización</li>
    <li>Sistema de temas personalizable</li>
</ul>

<div class="actions">
    <a href="/install?stage=requirements" class="btn"><i class="fas fa-arrow-right icon"></i>Comenzar instalación</a>
</div>
