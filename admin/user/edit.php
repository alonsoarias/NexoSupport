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
        } else {
            // Validate against password policy
            $error = '';
            if (!check_password_policy($password, $data->auth, $error)) {
                $errors[] = $error;
            } else {
                $data->password = $password;
            }
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

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'isnew' => $isNew,
    'edituser' => [
        'id' => $edituser->id,
        'username' => htmlspecialchars($edituser->username),
        'email' => htmlspecialchars($edituser->email),
        'firstname' => htmlspecialchars($edituser->firstname),
        'lastname' => htmlspecialchars($edituser->lastname),
        'phone' => htmlspecialchars($edituser->phone ?? ''),
        'suspended' => (bool)$edituser->suspended,
        'ismanual' => ($edituser->auth === 'manual'),
    ],
    'success' => $success ? htmlspecialchars($success) : null,
    'errors' => array_map('htmlspecialchars', $errors),
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

// Render and output
echo render_template('admin/user_edit', $context);
