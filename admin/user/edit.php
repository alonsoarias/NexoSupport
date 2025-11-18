<?php
/**
 * User Edit Form
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER;

$userid = optional_param('id', 0, 'int');
$isNew = $userid === 0;

$errors = [];
$success = null;

// Cargar usuario existente
if (!$isNew) {
    $edituser = \core\user\manager::get_user($userid);
    if (!$edituser) {
        redirect('/admin/users', get_string('usernotfound'));
    }
} else {
    $edituser = new stdClass();
    $edituser->id = 0;
    $edituser->username = '';
    $edituser->email = '';
    $edituser->firstname = '';
    $edituser->lastname = '';
    $edituser->phone = '';
    $edituser->auth = 'manual';
    $edituser->suspended = 0;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = new stdClass();
    $data->username = required_param('username', 'alphanumext');
    $data->email = required_param('email', 'email');
    $data->firstname = required_param('firstname', 'text');
    $data->lastname = required_param('lastname', 'text');
    $data->phone = optional_param('phone', '', 'text');
    $data->auth = optional_param('auth', 'manual', 'text');
    $data->suspended = optional_param('suspended', 0, 'int');
    $password = optional_param('password', '', 'raw');
    $password2 = optional_param('password2', '', 'raw');

    // Validar passwords si se proporcionan
    if (!empty($password) || $isNew) {
        if ($password !== $password2) {
            $errors[] = get_string('passwordmismatch');
        } elseif (strlen($password) < 8) {
            $errors[] = get_string('passwordtooshort');
        } else {
            $data->password = $password;
        }
    }

    if (empty($errors)) {
        try {
            if ($isNew) {
                // Crear usuario
                $newid = \core\user\manager::create_user($data);
                $success = get_string('usercreated');
                redirect('/admin/user/edit?id=' . $newid, $success);
            } else {
                // Actualizar usuario
                $data->id = $userid;
                \core\user\manager::update_user($data);
                $success = get_string('userupdated');
                $edituser = \core\user\manager::get_user($userid);
            }
        } catch (\coding_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isNew ? get_string('newuser') : get_string('edituser'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .nav {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .nav a {
            margin-right: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            margin-top: 0;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .required::after {
            content: ' *';
            color: #dc3545;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-danger {
            background: #dc3545;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/"><?php echo get_string('home'); ?></a>
        <a href="/admin"><?php echo get_string('administration'); ?></a>
        <a href="/admin/users"><?php echo get_string('users'); ?></a>
        <a href="/logout"><?php echo get_string('logout'); ?></a>
    </div>

    <div class="card">
        <h1><?php echo $isNew ? get_string('newuser') : get_string('edituser'); ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong><?php echo get_string('error'); ?>:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <div class="form-group">
                <label for="username" class="required"><?php echo get_string('username'); ?></label>
                <input type="text"
                       id="username"
                       name="username"
                       value="<?php echo htmlspecialchars($edituser->username); ?>"
                       required
                       <?php echo $isNew ? '' : 'readonly'; ?>>
                <?php if (!$isNew): ?>
                    <div class="help-text"><?php echo get_string('cannotrename'); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email" class="required"><?php echo get_string('email'); ?></label>
                <input type="email"
                       id="email"
                       name="email"
                       value="<?php echo htmlspecialchars($edituser->email); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="firstname" class="required"><?php echo get_string('firstname'); ?></label>
                <input type="text"
                       id="firstname"
                       name="firstname"
                       value="<?php echo htmlspecialchars($edituser->firstname); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="lastname" class="required"><?php echo get_string('lastname'); ?></label>
                <input type="text"
                       id="lastname"
                       name="lastname"
                       value="<?php echo htmlspecialchars($edituser->lastname); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="phone">Teléfono</label>
                <input type="text"
                       id="phone"
                       name="phone"
                       value="<?php echo htmlspecialchars($edituser->phone ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password"><?php echo get_string('password'); ?></label>
                <input type="password"
                       id="password"
                       name="password"
                       <?php echo $isNew ? 'required' : ''; ?>>
                <div class="help-text">
                    <?php if ($isNew): ?>
                        <?php echo get_string('passwordtooshort'); ?>
                    <?php else: ?>
                        <?php echo get_string('passwordtooshort'); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="password2"><?php echo get_string('password'); ?></label>
                <input type="password"
                       id="password2"
                       name="password2"
                       <?php echo $isNew ? 'required' : ''; ?>>
            </div>

            <div class="form-group">
                <label for="auth"><?php echo get_string('name'); ?></label>
                <select id="auth" name="auth">
                    <option value="manual" <?php echo $edituser->auth === 'manual' ? 'selected' : ''; ?>><?php echo get_string('name'); ?></option>
                </select>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox"
                           id="suspended"
                           name="suspended"
                           value="1"
                           <?php echo $edituser->suspended ? 'checked' : ''; ?>>
                    <label for="suspended" style="margin: 0;"><?php echo get_string('suspenduser'); ?></label>
                </div>
                <div class="help-text"><?php echo get_string('userissuspended'); ?></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">
                    <?php echo $isNew ? get_string('createuser') : get_string('save'); ?>
                </button>
                <a href="/admin/users" class="btn btn-secondary"><?php echo get_string('cancel'); ?></a>

                <?php if (!$isNew): ?>
                    <a href="/admin/roles/assign?userid=<?php echo $userid; ?>" class="btn btn-secondary">
                        <?php echo get_string('assignroles'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>
