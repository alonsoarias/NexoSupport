<?php
/**
 * Stage 8: Email Configuration
 */

// Obtener valores guardados o por defecto
$mail_driver = $_SESSION['mail_driver'] ?? 'smtp';
$smtp_host = $_SESSION['mail_smtp_host'] ?? '';
$smtp_port = $_SESSION['mail_smtp_port'] ?? 587;
$smtp_encryption = $_SESSION['mail_smtp_encryption'] ?? 'tls';
$smtp_user = $_SESSION['mail_smtp_user'] ?? '';
$smtp_pass = $_SESSION['mail_smtp_pass'] ?? '';
$mail_from_address = $_SESSION['mail_from_address'] ?? '';
$mail_from_name = $_SESSION['mail_from_name'] ?? 'NexoSupport';
$mailgun_domain = $_SESSION['mail_mailgun_domain'] ?? '';
$mailgun_secret = $_SESSION['mail_mailgun_secret'] ?? '';
$postmark_token = $_SESSION['mail_postmark_token'] ?? '';
?>

<h3 class="mb-4"><i class="bi bi-envelope"></i> Configuración de Correo Electrónico</h3>

<p class="text-muted">Configure el servicio de envío de correos electrónicos del sistema.</p>

<form method="POST" id="emailForm">
    <input type="hidden" name="stage" value="<?= STAGE_EMAIL ?>">

    <!-- Mail Driver Selection -->
    <div class="mb-4">
        <label class="form-label"><i class="bi bi-mailbox"></i> Proveedor de Correo *</label>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card h-100 mail-driver-card <?= $mail_driver === 'smtp' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mail_driver"
                                   id="driver_smtp" value="smtp" <?= $mail_driver === 'smtp' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="driver_smtp">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-gear text-primary fs-4 me-2"></i>
                                    <strong>SMTP</strong>
                                </div>
                                <small class="text-muted">Servidor SMTP personalizado</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 mail-driver-card <?= $mail_driver === 'sendmail' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mail_driver"
                                   id="driver_sendmail" value="sendmail" <?= $mail_driver === 'sendmail' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="driver_sendmail">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-server text-success fs-4 me-2"></i>
                                    <strong>Sendmail</strong>
                                </div>
                                <small class="text-muted">Sendmail local del servidor</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 mail-driver-card <?= $mail_driver === 'mailgun' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mail_driver"
                                   id="driver_mailgun" value="mailgun" <?= $mail_driver === 'mailgun' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="driver_mailgun">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-envelope-paper text-danger fs-4 me-2"></i>
                                    <strong>Mailgun</strong>
                                </div>
                                <small class="text-muted">API de Mailgun</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 mail-driver-card <?= $mail_driver === 'postmark' ? 'border-primary' : '' ?>">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mail_driver"
                                   id="driver_postmark" value="postmark" <?= $mail_driver === 'postmark' ? 'checked' : '' ?> required>
                            <label class="form-check-label w-100" for="driver_postmark">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-send text-warning fs-4 me-2"></i>
                                    <strong>Postmark</strong>
                                </div>
                                <small class="text-muted">API de Postmark</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Settings -->
    <div id="smtpSettings" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-gear"></i> Configuración SMTP</h5>

        <div class="mb-3">
            <label class="form-label">Servidor SMTP *</label>
            <input type="text" name="mail_smtp_host" id="smtp_host" class="form-control"
                   value="<?= htmlspecialchars($smtp_host) ?>" placeholder="smtp.gmail.com">
            <small class="text-muted">Dirección del servidor SMTP</small>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Puerto *</label>
                <input type="number" name="mail_smtp_port" id="smtp_port" class="form-control"
                       value="<?= $smtp_port ?>" min="1" max="65535">
                <small class="text-muted">Común: 587 (TLS), 465 (SSL), 25</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Encriptación *</label>
                <select name="mail_smtp_encryption" id="smtp_encryption" class="form-select">
                    <option value="tls" <?= $smtp_encryption === 'tls' ? 'selected' : '' ?>>TLS (recomendado)</option>
                    <option value="ssl" <?= $smtp_encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="" <?= $smtp_encryption === '' ? 'selected' : '' ?>>Ninguna</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Usuario SMTP *</label>
            <input type="text" name="mail_smtp_user" id="smtp_user" class="form-control"
                   value="<?= htmlspecialchars($smtp_user) ?>" placeholder="usuario@ejemplo.com">
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña SMTP *</label>
            <input type="password" name="mail_smtp_pass" id="smtp_pass" class="form-control"
                   value="<?= htmlspecialchars($smtp_pass) ?>">
        </div>
    </div>

    <!-- Mailgun Settings -->
    <div id="mailgunSettings" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-envelope-paper"></i> Configuración Mailgun</h5>

        <div class="mb-3">
            <label class="form-label">Dominio de Mailgun *</label>
            <input type="text" name="mail_mailgun_domain" id="mailgun_domain" class="form-control"
                   value="<?= htmlspecialchars($mailgun_domain) ?>" placeholder="mg.ejemplo.com">
        </div>

        <div class="mb-3">
            <label class="form-label">Clave API de Mailgun *</label>
            <input type="password" name="mail_mailgun_secret" id="mailgun_secret" class="form-control"
                   value="<?= htmlspecialchars($mailgun_secret) ?>">
        </div>
    </div>

    <!-- Postmark Settings -->
    <div id="postmarkSettings" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-send"></i> Configuración Postmark</h5>

        <div class="mb-3">
            <label class="form-label">Token de Servidor de Postmark *</label>
            <input type="password" name="mail_postmark_token" id="postmark_token" class="form-control"
                   value="<?= htmlspecialchars($postmark_token) ?>">
        </div>
    </div>

    <!-- From Settings (all drivers) -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-person"></i> Remitente</h5>

        <div class="mb-3">
            <label class="form-label">Correo del Remitente *</label>
            <input type="email" name="mail_from_address" class="form-control"
                   value="<?= htmlspecialchars($mail_from_address) ?>" placeholder="soporte@iser.edu.co" required>
            <small class="text-muted">Correo que aparecerá como remitente</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre del Remitente *</label>
            <input type="text" name="mail_from_name" class="form-control"
                   value="<?= htmlspecialchars($mail_from_name) ?>" required>
            <small class="text-muted">Nombre que aparecerá como remitente</small>
        </div>
    </div>

    <!-- Test Email -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-envelope-check"></i> Probar Configuración</h5>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Puede probar la configuración de correo después de completar la instalación desde el panel de administración.
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
// Actualizar campos según el driver seleccionado
document.querySelectorAll('input[name="mail_driver"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const driver = this.value;

        // Ocultar todos los campos específicos
        document.getElementById('smtpSettings').style.display = 'none';
        document.getElementById('mailgunSettings').style.display = 'none';
        document.getElementById('postmarkSettings').style.display = 'none';

        // Remover required de todos los campos específicos
        document.querySelectorAll('#smtpSettings input, #mailgunSettings input, #postmarkSettings input').forEach(input => {
            input.removeAttribute('required');
        });

        // Mostrar campos según el driver
        if (driver === 'smtp') {
            document.getElementById('smtpSettings').style.display = 'block';
            document.getElementById('smtp_host').setAttribute('required', 'required');
            document.getElementById('smtp_port').setAttribute('required', 'required');
            document.getElementById('smtp_user').setAttribute('required', 'required');
            document.getElementById('smtp_pass').setAttribute('required', 'required');
        } else if (driver === 'mailgun') {
            document.getElementById('mailgunSettings').style.display = 'block';
            document.getElementById('mailgun_domain').setAttribute('required', 'required');
            document.getElementById('mailgun_secret').setAttribute('required', 'required');
        } else if (driver === 'postmark') {
            document.getElementById('postmarkSettings').style.display = 'block';
            document.getElementById('postmark_token').setAttribute('required', 'required');
        }
        // sendmail no necesita campos adicionales

        // Actualizar border de las cards
        document.querySelectorAll('.mail-driver-card').forEach(card => {
            card.classList.remove('border-primary');
        });
        this.closest('.mail-driver-card').classList.add('border-primary');
    });
});

// Inicializar estado al cargar
const selectedDriver = document.querySelector('input[name="mail_driver"]:checked');
if (selectedDriver) {
    selectedDriver.dispatchEvent(new Event('change'));
}
</script>

<style>
.mail-driver-card {
    cursor: pointer;
    transition: all 0.2s;
}

.mail-driver-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.mail-driver-card.border-primary {
    box-shadow: 0 0 0 0.2rem rgba(27, 158, 136, 0.25);
    border-color: var(--iser-green) !important;
}

.alert-info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.border-bottom {
    border-color: var(--border-color) !important;
}
</style>
