<?php
/**
 * Stage 6: Security Configuration
 */

// Generar JWT secret si no existe
if (!isset($_SESSION['security_jwt_secret'])) {
    $_SESSION['security_jwt_secret'] = bin2hex(random_bytes(32));
}

// Obtener valores guardados o por defecto
$jwt_secret = $_SESSION['security_jwt_secret'] ?? '';
$jwt_expiration = $_SESSION['security_jwt_expiration'] ?? 3600;
$password_min_chars = $_SESSION['security_password_min_chars'] ?? 8;
$password_uppercase = $_SESSION['security_password_uppercase'] ?? true;
$password_numbers = $_SESSION['security_password_numbers'] ?? true;
$password_special = $_SESSION['security_password_special'] ?? true;
$recaptcha_enabled = $_SESSION['security_recaptcha_enabled'] ?? false;
$recaptcha_site_key = $_SESSION['security_recaptcha_site_key'] ?? '';
$recaptcha_secret_key = $_SESSION['security_recaptcha_secret_key'] ?? '';
$rate_limit_attempts = $_SESSION['security_rate_limit_attempts'] ?? 5;
$rate_limit_lockout = $_SESSION['security_rate_limit_lockout'] ?? 15;
?>

<h3 class="mb-4"><i class="bi bi-shield-lock"></i> Configuración de Seguridad</h3>

<p class="text-muted">Configure las opciones de seguridad y autenticación del sistema.</p>

<form method="POST" id="securityForm">
    <input type="hidden" name="stage" value="<?= STAGE_SECURITY ?>">

    <!-- JWT Configuration -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-key"></i> JWT (JSON Web Token)</h5>

        <div class="mb-3">
            <label class="form-label">Clave Secreta JWT *</label>
            <div class="input-group">
                <input type="text" name="security_jwt_secret" id="jwt_secret" class="form-control font-monospace"
                       value="<?= htmlspecialchars($jwt_secret) ?>" required readonly>
                <button type="button" class="btn btn-outline-secondary" id="regenerateJWT">
                    <i class="bi bi-arrow-clockwise"></i> Regenerar
                </button>
            </div>
            <small class="text-muted">Clave para firmar los tokens de autenticación. Se genera automáticamente.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Expiración del Token (segundos) *</label>
            <input type="number" name="security_jwt_expiration" class="form-control"
                   value="<?= $jwt_expiration ?>" min="300" max="86400" required>
            <small class="text-muted">Tiempo de validez del token. Predeterminado: 3600 (1 hora)</small>
        </div>
    </div>

    <!-- Password Policy -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-lock"></i> Política de Contraseñas</h5>

        <div class="mb-3">
            <label class="form-label">Mínimo de Caracteres *</label>
            <input type="number" name="security_password_min_chars" class="form-control"
                   value="<?= $password_min_chars ?>" min="6" max="32" required>
            <small class="text-muted">Longitud mínima requerida para las contraseñas</small>
        </div>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="security_password_uppercase"
                       id="pass_uppercase" value="1" <?= $password_uppercase ? 'checked' : '' ?>>
                <label class="form-check-label" for="pass_uppercase">
                    Requerir al menos una letra mayúscula
                </label>
            </div>
        </div>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="security_password_numbers"
                       id="pass_numbers" value="1" <?= $password_numbers ? 'checked' : '' ?>>
                <label class="form-check-label" for="pass_numbers">
                    Requerir al menos un número
                </label>
            </div>
        </div>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="security_password_special"
                       id="pass_special" value="1" <?= $password_special ? 'checked' : '' ?>>
                <label class="form-check-label" for="pass_special">
                    Requerir al menos un carácter especial
                </label>
            </div>
        </div>
    </div>

    <!-- reCAPTCHA -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-robot"></i> reCAPTCHA (Opcional)</h5>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="security_recaptcha_enabled"
                       id="recaptcha_enabled" value="1" <?= $recaptcha_enabled ? 'checked' : '' ?>>
                <label class="form-check-label" for="recaptcha_enabled">
                    <strong>Habilitar reCAPTCHA</strong>
                </label>
            </div>
            <small class="text-muted">Protección contra bots en formularios de inicio de sesión</small>
        </div>

        <div id="recaptchaFields" style="display: <?= $recaptcha_enabled ? 'block' : 'none' ?>;">
            <div class="mb-3">
                <label class="form-label">Site Key</label>
                <input type="text" name="security_recaptcha_site_key" class="form-control"
                       value="<?= htmlspecialchars($recaptcha_site_key) ?>">
                <small class="text-muted">Clave pública de Google reCAPTCHA v2</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Secret Key</label>
                <input type="password" name="security_recaptcha_secret_key" class="form-control"
                       value="<?= htmlspecialchars($recaptcha_secret_key) ?>">
                <small class="text-muted">Clave secreta de Google reCAPTCHA v2</small>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Obtenga las claves de reCAPTCHA en:
                <a href="https://www.google.com/recaptcha/admin" target="_blank">
                    https://www.google.com/recaptcha/admin
                </a>
            </div>
        </div>
    </div>

    <!-- Rate Limiting -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-speedometer"></i> Limitación de Intentos</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Máximo de Intentos Fallidos *</label>
                <input type="number" name="security_rate_limit_attempts" class="form-control"
                       value="<?= $rate_limit_attempts ?>" min="3" max="20" required>
                <small class="text-muted">Intentos antes de bloquear la cuenta</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Tiempo de Bloqueo (minutos) *</label>
                <input type="number" name="security_rate_limit_lockout" class="form-control"
                       value="<?= $rate_limit_lockout ?>" min="5" max="60" required>
                <small class="text-muted">Duración del bloqueo temporal</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="submit" name="next" class="btn btn-primary">
            Siguiente <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<script>
// Regenerar JWT Secret
document.getElementById('regenerateJWT').addEventListener('click', function() {
    if (confirm('¿Está seguro de regenerar la clave JWT? Esta acción invalidará todos los tokens existentes.')) {
        // Generar nuevo token aleatorio
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        const newSecret = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        document.getElementById('jwt_secret').value = newSecret;
    }
});

// Mostrar/ocultar campos de reCAPTCHA
document.getElementById('recaptcha_enabled').addEventListener('change', function() {
    document.getElementById('recaptchaFields').style.display = this.checked ? 'block' : 'none';
});
</script>

<style>
.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
}

.form-switch .form-check-input:checked {
    background-color: var(--iser-green);
    border-color: var(--iser-green);
}

.alert-info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.border-bottom {
    border-color: var(--border-color) !important;
}
</style>
