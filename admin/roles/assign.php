<?php
/**
 * Assign Roles to Users
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:assignroles');

global $USER, $DB;

$userid = optional_param('userid', 0, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

$errors = [];
$success = null;

// Si se especifica userid, mostrar roles del usuario
if ($userid > 0) {
    $user = \core\user\manager::get_user($userid);
    if (!$user) {
        redirect('/admin/users', get_string('usernotfound'));
    }

    $syscontext = \core\rbac\context::system();

    // Obtener roles asignados al usuario
    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);

    // Obtener todos los roles disponibles
    $allroles = \core\rbac\role::get_all();

    // Procesar asignación/desasignación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_sesskey();

        $assignroleid = required_param('assignroleid', PARAM_INT);
        $assignaction = required_param('assignaction', PARAM_TEXT);

        try {
            if ($assignaction === 'assign') {
                $role = \core\rbac\role::get_by_id($assignroleid);
                if ($role) {
                    \core\rbac\access::assign_role($assignroleid, $userid, $syscontext);
                    $success = get_string('roleassigned', 'core', $role->name);
                    // Recargar roles
                    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);
                }
            } elseif ($assignaction === 'unassign') {
                $role = \core\rbac\role::get_by_id($assignroleid);
                if ($role) {
                    \core\rbac\access::unassign_role($assignroleid, $userid, $syscontext);
                    $success = get_string('roleunassigned', 'core', $role->name);
                    // Recargar roles
                    $userroles = \core\rbac\access::get_user_roles($userid, $syscontext);
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }

    // IDs de roles ya asignados
    $assignedRoleIds = array_column($userroles, 'id');

    // Prepare roles for template
    $roles = [];
    foreach ($allroles as $role) {
        $isAssigned = in_array($role->id, $assignedRoleIds);
        $roles[] = [
            'role_id' => $role->id,
            'role_name' => htmlspecialchars($role->name),
            'role_description' => htmlspecialchars($role->description),
            'is_assigned' => $isAssigned,
            'sesskey' => sesskey(),
        ];
    }

    // Prepare template context
    $context = [
        'lang' => \core\string_manager::get_language(),
        'user_view' => true,
        'role_view' => false,
        'user_username' => htmlspecialchars($user->username),
        'user_fullname' => htmlspecialchars($user->firstname . ' ' . $user->lastname),
        'user_email' => htmlspecialchars($user->email),
        'success' => $success,
        'has_errors' => !empty($errors),
        'errors' => $errors,
        'roles' => $roles,
        'pagetitle' => get_string('assignroles', 'core'),
    'has_navigation' => true,
        'navigation_html' => get_navigation_html(),
    ];

} elseif ($roleid > 0) {
    // Si se especifica roleid, mostrar usuarios con ese rol
    $role = \core\rbac\role::get_by_id($roleid);
    if (!$role) {
        redirect('/admin/roles', get_string('rolenotfound'));
    }

    $syscontext = \core\rbac\context::system();
    $roleusers = $role->get_users($syscontext);

    // Prepare users for template
    $users = [];
    foreach ($roleusers as $roleuser) {
        $users[] = [
            'user_id' => $roleuser->id,
            'user_username' => htmlspecialchars($roleuser->username),
            'user_fullname' => htmlspecialchars($roleuser->firstname . ' ' . $roleuser->lastname),
            'user_email' => htmlspecialchars($roleuser->email),
        ];
    }

    // Prepare template context
    $context = [
        'lang' => \core\string_manager::get_language(),
        'user_view' => false,
        'role_view' => true,
        'role_name' => htmlspecialchars($role->name),
        'role_shortname' => htmlspecialchars($role->shortname),
        'role_description' => $role->description ? htmlspecialchars($role->description) : null,
        'has_no_users' => empty($roleusers),
        'has_users' => !empty($roleusers),
        'users' => $users,
        'pagetitle' => get_string('assignroles', 'core'),
    'has_navigation' => true,
        'navigation_html' => get_navigation_html(),
    ];

} else {
    redirect('/admin/users', get_string('mustselectuserrole'));
}

echo render_template('admin/role_assign', $context);
