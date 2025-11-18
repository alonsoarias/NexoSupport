<?php
/**
 * Role Edit Form
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER;

$roleid = optional_param('id', 0, 'int');
$action = optional_param('action', '', 'text');
$isNew = $roleid === 0;

$errors = [];
$success = null;

// Cargar rol existente
if (!$isNew) {
    $role = \core\rbac\role::get_by_id($roleid);
    if (!$role) {
        redirect('/admin/roles', get_string('rolenotfound'));
    }
} else {
    $role = new stdClass();
    $role->id = 0;
    $role->name = '';
    $role->shortname = '';
    $role->description = '';
    $role->archetype = 0;
}

// Manejar eliminación
if ($action === 'delete' && !$isNew) {
    require_sesskey();

    // Verificar que no sea un rol del sistema
    if (in_array($role->shortname, ['administrator', 'manager', 'user'])) {
        $errors[] = get_string('systemrolewarning');
    } else {
        try {
            \core\rbac\role::delete_role($roleid);
            redirect('/admin/roles', get_string('roledeleted'));
        } catch (\Exception $e) {
            $errors[] = get_string('error') . ': ' . $e->getMessage();
        }
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
    require_sesskey();

    $data = new stdClass();
    $data->name = required_param('name', 'text');
    $data->shortname = required_param('shortname', 'alphanumext');
    $data->description = optional_param('description', '', 'text');
    $data->archetype = optional_param('archetype', 0, 'int');

    if (empty($errors)) {
        try {
            if ($isNew) {
                // Crear rol
                $newrole = \core\rbac\role::create(
                    $data->shortname,
                    $data->name,
                    $data->description,
                    $data->archetype
                );
                $success = get_string('rolecreated');
                redirect('/admin/roles/define?roleid=' . $newrole->id, $success);
            } else {
                // Actualizar rol
                $role->name = $data->name;
                $role->description = $data->description;
                $role->archetype = $data->archetype;

                if ($role->shortname !== $data->shortname) {
                    // Solo permitir cambiar shortname si no es rol del sistema
                    if (!in_array($role->shortname, ['administrator', 'manager', 'user'])) {
                        $role->shortname = $data->shortname;
                    } else {
                        $errors[] = get_string('cannotrename');
                    }
                }

                if (empty($errors)) {
                    \core\rbac\role::update_role($role);
                    $success = get_string('roleupdated');
                    $role = \core\rbac\role::get_by_id($roleid);
                }
            }
        } catch (\coding_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$isSystemRole = !$isNew && in_array($role->shortname, ['administrator', 'manager', 'user']);

// Prepare template context
$context = [
    'lang' => \core\string_manager::get_language(),
    'title_key' => $isNew ? 'createrole' : 'editrole',
    'submit_button_key' => $isNew ? 'createrole' : 'save',
    'is_system_role' => $isSystemRole,
    'success' => $success,
    'has_errors' => !empty($errors),
    'errors' => $errors,
    'sesskey' => sesskey(),
    'role_id' => $role->id,
    'role_name' => htmlspecialchars($role->name),
    'role_shortname' => htmlspecialchars($role->shortname),
    'role_description' => htmlspecialchars($role->description),
    'show_define_button' => !$isNew,
    'show_delete_button' => !$isNew && !$isSystemRole,
];

echo render_template('admin/role_edit', $context);
