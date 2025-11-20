<?php
/**
 * Stage: Create Admin User - Refactorizado
 */

$progress = 83;

// Valores por defecto
$username = $_POST['username'] ?? 'admin';
$email = $_POST['email'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';

// Mostrar error si existe
$error = null;
if (isset($action_result) && !$action_result['success']) {
    $error = $action_result['error'];
}
?>

<div class="stage-indicator">
    <i class="fas fa-user-shield icon"></i>
    <div class="text">
        <div class="step-number">Paso 5 de 6</div>
        <strong>Crear Usuario Administrador</strong>
    </div>
</div>

<h1><i class="fas fa-user-shield icon"></i>Crear Usuario Administrador</h1>
<h2>Configure la cuenta de administrador del sistema</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> <strong>Importante:</strong><br>
    Esta cuenta tendrá acceso completo al sistema. Asegúrese de usar una contraseña segura.
</div>

<form method="POST" action="/install?stage=admin">
    <input type="hidden" name="action" value="create_admin">

    <div class="form-group">
        <label for="username"><i class="fas fa-user icon"></i>Nombre de Usuario</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required placeholder="admin" pattern="[a-zA-Z0-9_.-]+">
        <small><i class="fas fa-info-circle"></i> Solo letras, números, guiones, puntos y guiones bajos</small>
    </div>

    <div class="form-group">
        <label for="email"><i class="fas fa-envelope icon"></i>Email</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="admin@example.com">
    </div>

    <div class="form-group">
        <label for="firstname"><i class="fas fa-id-card icon"></i>Nombre</label>
        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required placeholder="Juan">
    </div>

    <div class="form-group">
        <label for="lastname"><i class="fas fa-id-card icon"></i>Apellido</label>
        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required placeholder="Pérez">
    </div>

    <div class="form-group">
        <label for="password"><i class="fas fa-lock icon"></i>Contraseña</label>
        <input type="password" name="password" id="password" required minlength="8" placeholder="••••••••">
        <small><i class="fas fa-info-circle"></i> Mínimo 8 caracteres</small>
    </div>

    <div class="form-group">
        <label for="password2"><i class="fas fa-lock icon"></i>Confirmar Contraseña</label>
        <input type="password" name="password2" id="password2" required minlength="8" placeholder="••••••••">
    </div>

    <div class="actions">
        <a href="/install?stage=database" class="btn btn-secondary"><i class="fas fa-arrow-left icon"></i>Atrás</a>
        <button type="submit" class="btn"><i class="fas fa-user-plus icon"></i>Crear Administrador</button>
    </div>
</form>

<script>
// Validar que las contraseñas coincidan
document.querySelector('form').addEventListener('submit', function(e) {
    var pass1 = document.getElementById('password').value;
    var pass2 = document.getElementById('password2').value;

    if (pass1 !== pass2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
    }
});
</script>
