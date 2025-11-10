<?php
/**
 * Paso 6: Finalización
 */
?>

<div class="text-center mb-4">
    <div class="mb-4">
        <i class="bi bi-check-circle text-success" style="font-size: 5rem;"></i>
    </div>
    <h2 class="text-success mb-3">¡Instalación Completada!</h2>
    <p class="lead">
        ISER Authentication System ha sido instalado exitosamente.
    </p>
</div>

<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="bi bi-info-circle me-2"></i>Información Importante
        </h5>

        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Seguridad:</strong> Por favor, elimine o restrinja el acceso al directorio
            <code>/public_html/install/</code> para prevenir reinstalaciones no autorizadas.
        </div>

        <h6 class="mb-2">Próximos Pasos:</h6>
        <ol class="mb-3">
            <li>Inicie sesión con su cuenta de administrador</li>
            <li>Configure las opciones de email en el archivo <code>.env</code></li>
            <li>Revise la configuración de seguridad</li>
            <li>Cree usuarios y roles adicionales según sea necesario</li>
            <li>Configure MFA (autenticación de dos factores) si lo desea</li>
        </ol>

        <h6 class="mb-2">Recursos del Sistema:</h6>
        <ul class="mb-0">
            <li><strong>Panel de Administración:</strong> <code>/admin/</code></li>
            <li><strong>Sistema de Reportes:</strong> <code>/report/</code></li>
            <li><strong>API REST:</strong> <code>/api/v1/</code></li>
            <li><strong>Logs:</strong> <code>/var/logs/</code></li>
        </ul>
    </div>
</div>

<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-key me-2"></i>Credenciales de Acceso
    </div>
    <div class="card-body">
        <p class="mb-2">Utilice estas credenciales para iniciar sesión:</p>
        <ul class="mb-0">
            <li><strong>Usuario:</strong> <code><?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?></code></li>
            <li><strong>Email:</strong> <code><?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></code></li>
            <li><strong>URL de Login:</strong> <a href="../login.php">../login.php</a></li>
        </ul>
    </div>
</div>

<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h6 class="mb-2">Configuración Generada:</h6>
        <ul class="mb-0">
            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Archivo <code>.env</code> creado</li>
            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Base de datos instalada</li>
            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Roles por defecto creados</li>
            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Usuario administrador creado</li>
            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Archivo <code>.installed</code> creado</li>
        </ul>
    </div>
</div>

<div class="text-center">
    <a href="../login.php" class="btn btn-primary btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i> Ir a Iniciar Sesión
    </a>
    <a href="../index.php" class="btn btn-outline-secondary btn-lg ms-2">
        <i class="bi bi-house me-2"></i> Ir al Inicio
    </a>
</div>

<div class="text-center mt-4">
    <small class="text-muted">
        Gracias por usar ISER Authentication System
    </small>
</div>
