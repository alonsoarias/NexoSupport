<?php
/**
 * Stage 4: Admin User Creation
 */

$username = $_SESSION['admin_username'] ?? '';
$email = $_SESSION['admin_email'] ?? '';
$firstname = $_SESSION['admin_firstname'] ?? 'Admin';
$lastname = $_SESSION['admin_lastname'] ?? 'User';
?>

<h3 class="mb-4">Usuario Administrador</h3>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Importante:</strong> Guarde estas credenciales en un lugar seguro.
</div>

<form method="POST">
    <input type="hidden" name="stage" value="<?= STAGE_ADMIN ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nombre de usuario *</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required pattern="[a-zA-Z0-9_]+" minlength="4">
            <small class="text-muted">Solo letras, números y guiones bajos</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($firstname) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($lastname) ?>">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Contraseña *</label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <small class="text-muted">Mínimo 8 caracteres</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Confirmar contraseña *</label>
            <input type="password" name="password_confirm" class="form-control" required>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="submit" name="next" class="btn btn-primary">
            Crear Administrador <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>
