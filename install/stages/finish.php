<?php
/**
 * Stage 5: Finish Installation
 */

// Create admin user
if (!isset($_SESSION['installation_complete'])) {
    try {
        // Connect
        $dsn = "mysql:host={$_SESSION['db_host']};port={$_SESSION['db_port']};dbname={$_SESSION['db_name']}";
        $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Hash password
        $passwordHash = password_hash($_SESSION['admin_password'], PASSWORD_ARGON2ID);
        $now = time();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO {$_SESSION['db_prefix']}users
            (username, email, password, first_name, last_name, status, email_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', 1, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_username'],
            $_SESSION['admin_email'],
            $passwordHash,
            $_SESSION['admin_firstname'],
            $_SESSION['admin_lastname'],
            $now,
            $now
        ]);
        $userId = $pdo->lastInsertId();

        // Assign admin role
        $stmt = $pdo->prepare("INSERT INTO {$_SESSION['db_prefix']}user_roles (user_id, role_id, assigned_at) VALUES (?, 1, ?)");
        $stmt->execute([$userId, $now]);

        // Create .installed file
        file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s'));

        $_SESSION['installation_complete'] = true;

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return;
    }
}
?>

<div class="text-center">
    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
    <h3 class="mt-3">¡Instalación Completada!</h3>
    <p class="text-muted">ISER Authentication System ha sido instalado correctamente.</p>
</div>

<div class="alert alert-info mt-4">
    <h5><i class="bi bi-key"></i> Credenciales de Acceso</h5>
    <p class="mb-0">
        <strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['admin_username']) ?><br>
        <strong>Email:</strong> <?= htmlspecialchars($_SESSION['admin_email']) ?>
    </p>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Importante:</strong> Por seguridad, elimine o restrinja el acceso al directorio <code>/install/</code>
</div>

<div class="text-center mt-4">
    <a href="../" class="btn btn-primary btn-lg">
        <i class="bi bi-house"></i> Ir al Sistema
    </a>
</div>

<?php
// Clear session
session_destroy();
?>
