<?php
/**
 * Paso 4: Instalación de Base de Datos
 */
?>

<div class="mb-4">
    <p class="lead">
        Se instalarán todas las tablas necesarias en la base de datos.
    </p>
</div>

<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Este paso:</strong>
    <ul class="mb-0 mt-2">
        <li>Creará las tablas del sistema de autenticación</li>
        <li>Creará las tablas de roles y permisos</li>
        <li>Creará las tablas de sesiones y MFA</li>
        <li>Creará las tablas de logs y reportes</li>
        <li>Insertará datos iniciales (roles por defecto)</li>
    </ul>
</div>

<div id="installation-status" class="mb-4">
    <!-- Se llenará dinámicamente -->
</div>

<form method="POST" action="?step=4" id="install-form">
    <div class="d-flex justify-content-between mt-4">
        <a href="?step=3" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i> Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-lg" id="install-btn">
            <i class="bi bi-download me-2"></i> Instalar Base de Datos
        </button>
    </div>
</form>

<script>
document.getElementById('install-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('install-btn');
    const statusDiv = document.getElementById('installation-status');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Instalando...';

    statusDiv.innerHTML = `
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-primary me-3" role="status"></div>
                    <div>
                        <h5 class="mb-1">Instalando base de datos...</h5>
                        <p class="mb-0 text-muted">Por favor espere, esto puede tomar unos momentos.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
});
</script>
