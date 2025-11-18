<?php
/**
 * Stage: Create Admin User
 */

$progress = 83;
$error = null;

session_start();

if (!isset($_SESSION['install_db'])) {
    header('Location: /install?stage=database');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $email = $_POST['email'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';

    // Validaciones
    if (empty($username) || empty($password) || empty($email) || empty($firstname) || empty($lastname)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        // Conectar a BD y crear usuario
        $dbconfig = $_SESSION['install_db'];

        $dsn = $dbconfig['driver'] === 'mysql'
            ? "mysql:host={$dbconfig['host']};dbname={$dbconfig['name']};charset=utf8mb4"
            : "pgsql:host={$dbconfig['host']};dbname={$dbconfig['name']}";

        try {
            $pdo = new PDO($dsn, $dbconfig['user'], $dbconfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            require_once(BASE_DIR . '/lib/classes/db/database.php');
            $DB = new \core\db\database($pdo, $dbconfig['prefix'], $dbconfig['driver']);

            // Crear usuario admin
            $userid = $DB->insert_record('users', [
                'auth' => 'manual',
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'suspended' => 0,
                'deleted' => 0,
                'timecreated' => time(),
                'timemodified' => time()
            ]);

            // Guardar en sesión (RBAC se instalará en finish.php)
            $_SESSION['admin_created'] = true;
            $_SESSION['admin_userid'] = $userid;

            // Redirigir a finish
            header('Location: /install?stage=finish');
            exit;

        } catch (Exception $e) {
            $error = 'Error al crear usuario: ' . $e->getMessage();
        }
    }
}

// Valores por defecto
$username = $_POST['username'] ?? 'admin';
$email = $_POST['email'] ?? 'soporteplataformas@iser.edu.co';
$firstname = $_POST['firstname'] ?? 'Administrador';
$lastname = $_POST['lastname'] ?? 'Sistema';
?>

<h1>Crear Usuario Administrador</h1>
<h2>Configure la cuenta del administrador del sistema</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="username">Nombre de Usuario</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>

    <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" required>
        <small style="color: #666;">Mínimo 8 caracteres</small>
    </div>

    <div class="form-group">
        <label for="password2">Confirmar Contraseña</label>
        <input type="password" name="password2" id="password2" required>
    </div>

    <div class="form-group">
        <label for="email">Correo Electrónico</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>

    <div class="form-group">
        <label for="firstname">Nombre</label>
        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
    </div>

    <div class="form-group">
        <label for="lastname">Apellido</label>
        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
    </div>

    <div class="actions">
        <button type="submit" class="btn">Crear Administrador</button>
    </div>
</form>
