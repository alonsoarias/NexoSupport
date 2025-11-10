<?php
/**
 * Paso 6: Crear Usuario Administrador
 */
?>

<div class="mb-4">
    <p class="lead">
        Cree la cuenta de administrador principal del sistema.
    </p>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Importante:</strong> Guarde estas credenciales en un lugar seguro. Este usuario tendrá acceso
        completo al sistema.
    </div>
</div>

<form method="POST" action="?step=6" id="admin-form">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-1"></i> Nombre de Usuario
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="username"
                       name="username"
                       pattern="[a-zA-Z0-9_]+"
                       minlength="4"
                       maxlength="50"
                       required>
                <div class="form-text">Solo letras, números y guiones bajos (4-50 caracteres)</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i> Correo Electrónico
                    <span class="text-danger">*</span>
                </label>
                <input type="email"
                       class="form-control"
                       id="email"
                       name="email"
                       required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="first_name" class="form-label">
                    Nombre
                </label>
                <input type="text"
                       class="form-control"
                       id="first_name"
                       name="first_name"
                       value="Admin">
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="last_name" class="form-label">
                    Apellido
                </label>
                <input type="text"
                       class="form-control"
                       id="last_name"
                       name="last_name"
                       value="User">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-key me-1"></i> Contraseña
                    <span class="text-danger">*</span>
                </label>
                <input type="password"
                       class="form-control"
                       id="password"
                       name="password"
                       minlength="8"
                       required>
                <div class="form-text">Mínimo 8 caracteres</div>
                <div id="password-strength" class="mt-2"></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="password_confirm" class="form-label">
                    Confirmar Contraseña
                    <span class="text-danger">*</span>
                </label>
                <input type="password"
                       class="form-control"
                       id="password_confirm"
                       name="password_confirm"
                       minlength="8"
                       required>
                <div id="password-match" class="mt-2"></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="?step=5" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left me-2"></i> Anterior
        </a>
        <button type="submit" class="btn btn-primary btn-installer btn-lg">
            Crear Administrador <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<script>
// Validación de contraseña en tiempo real
const password = document.getElementById('password');
const passwordConfirm = document.getElementById('password_confirm');
const strengthDiv = document.getElementById('password-strength');
const matchDiv = document.getElementById('password-match');

password.addEventListener('input', function() {
    const value = this.value;
    let strength = 0;
    let messages = [];

    if (value.length >= 8) strength++;
    else messages.push('mínimo 8 caracteres');

    if (/[A-Z]/.test(value)) strength++;
    else messages.push('mayúsculas');

    if (/[a-z]/.test(value)) strength++;
    else messages.push('minúsculas');

    if (/[0-9]/.test(value)) strength++;
    else messages.push('números');

    if (/[^A-Za-z0-9]/.test(value)) strength++;
    else messages.push('caracteres especiales');

    const colors = ['danger', 'danger', 'warning', 'info', 'success', 'success'];
    const labels = ['Muy débil', 'Débil', 'Regular', 'Buena', 'Fuerte', 'Muy fuerte'];

    strengthDiv.innerHTML = `
        <div class="progress" style="height: 5px;">
            <div class="progress-bar bg-${colors[strength]}" style="width: ${strength * 20}%"></div>
        </div>
        <small class="text-${colors[strength]}">
            ${labels[strength]}${messages.length > 0 ? ' - Faltan: ' + messages.join(', ') : ''}
        </small>
    `;
});

passwordConfirm.addEventListener('input', function() {
    if (this.value && this.value === password.value) {
        matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Las contraseñas coinciden</small>';
    } else if (this.value) {
        matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Las contraseñas no coinciden</small>';
    } else {
        matchDiv.innerHTML = '';
    }
});
</script>
