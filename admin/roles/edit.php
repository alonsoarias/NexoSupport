<?php
/**
 * Role Edit Form
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$roleid = optional_param('id', 0, PARAM_INT);
$isNew = $roleid === 0;

$errors = [];
$success = null;

// Load existing role
if (!$isNew) {
    $role = $DB->get_record('roles', ['id' => $roleid]);
    if (!$role) {
        redirect('/admin/roles/', get_string('rolenotfound', 'core'));
    }
} else {
    $role = new stdClass();
    $role->id = 0;
    $role->name = '';
    $role->shortname = '';
    $role->description = '';
    $role->archetype = '';
    $role->sortorder = 0;
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = new stdClass();
    $data->name = required_param('name', PARAM_TEXT);
    $data->shortname = required_param('shortname', PARAM_ALPHANUMEXT);
    $data->description = optional_param('description', '', PARAM_TEXT);
    $data->archetype = optional_param('archetype', '', PARAM_ALPHANUMEXT);

    // Validate shortname uniqueness
    $existing = $DB->get_record('roles', ['shortname' => $data->shortname]);
    if ($existing && ($isNew || $existing->id != $roleid)) {
        $errors[] = get_string('roleshortnameexists', 'core');
    }

    if (empty($errors)) {
        try {
            if ($isNew) {
                $data->sortorder = $DB->count_records('roles');
                $newid = $DB->insert_record('roles', $data);
                $success = get_string('rolecreated', 'core');
                redirect('/admin/roles/edit?id=' . $newid, $success);
            } else {
                $data->id = $roleid;
                $DB->update_record('roles', $data);
                $success = get_string('roleupdated', 'core');
                $role = $DB->get_record('roles', ['id' => $roleid]);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// System roles
$systemroles = ['admin', 'manager', 'user', 'guest'];
$is_system_role = !$isNew && in_array($role->shortname, $systemroles);

// Prepare context
$context = [
    'pagetitle' => $isNew ? get_string('newrole', 'core') : get_string('editrole', 'core'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'title_key' => $isNew ? 'newrole' : 'editrole',
    'role_id' => $role->id,
    'role_name' => htmlspecialchars($role->name),
    'role_shortname' => htmlspecialchars($role->shortname),
    'role_description' => htmlspecialchars($role->description ?? ''),
    'is_system_role' => $is_system_role,
    'show_delete_button' => !$isNew && !$is_system_role,
    'show_define_button' => !$isNew,
    'submit_button_key' => $isNew ? 'create' : 'save',
    'success' => $success,
    'errors' => $errors,
    'has_errors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/role_edit', $context);
