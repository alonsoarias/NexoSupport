<?php
/**
 * Define Role Capabilities
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageroles');

global $USER, $DB;

$roleid = required_param('roleid', 'int');

$role = \core\rbac\role::get_by_id($roleid);
if (!$role) {
    redirect('/admin/roles', get_string('rolenotfound'));
}

$errors = [];
$success = null;

// Obtener todas las capabilities del sistema
$allcaps = $DB->get_records('capabilities', [], 'component, name');

// Obtener capabilities actuales del rol
$syscontext = \core\rbac\context::system();
$rolecaps = $role->get_capabilities($syscontext);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $updated = 0;

    foreach ($allcaps as $cap) {
        $permission = optional_param('cap_' . str_replace('/', '_', $cap->name), null, 'int');

        if ($permission !== null) {
            $currentperm = $rolecaps[$cap->name] ?? 0;

            if ($permission == 0 && isset($rolecaps[$cap->name])) {
                // Remover capability
                $role->remove_capability($cap->name, $syscontext);
                $updated++;
            } elseif ($permission != 0 && $permission != $currentperm) {
                // Asignar o actualizar capability
                $role->assign_capability($cap->name, $permission, $syscontext);
                $updated++;
            }
        }
    }

    if ($updated > 0) {
        $success = get_string('capabilitiesupdated');
        // Recargar capabilities
        $rolecaps = $role->get_capabilities($syscontext);
    } else {
        $success = get_string('capabilitiesupdated');
    }
}

// Agrupar capabilities por componente
$capsByComponent = [];
foreach ($allcaps as $cap) {
    if (!isset($capsByComponent[$cap->component])) {
        $capsByComponent[$cap->component] = [];
    }
    $capsByComponent[$cap->component][] = $cap;
}

// Constantes de permisos
define('CAP_INHERIT', 0);
define('CAP_ALLOW', 1);
define('CAP_PREVENT', -1);
define('CAP_PROHIBIT', -1000);

// Prepare components for template
$components = [];
foreach ($capsByComponent as $component => $caps) {
    $capabilities = [];
    foreach ($caps as $cap) {
        $currentPerm = $rolecaps[$cap->name] ?? CAP_INHERIT;
        $fieldName = 'cap_' . str_replace('/', '_', $cap->name);

        $capabilities[] = [
            'cap_name' => htmlspecialchars($cap->name),
            'cap_type' => htmlspecialchars($cap->captype),
            'field_name' => $fieldName,
            'current_perm' => $currentPerm,
            'is_inherit' => $currentPerm == CAP_INHERIT,
            'is_allow' => $currentPerm == CAP_ALLOW,
            'is_prevent' => $currentPerm == CAP_PREVENT,
            'is_prohibit' => $currentPerm == CAP_PROHIBIT,
        ];
    }

    $components[] = [
        'component_name' => htmlspecialchars($component),
        'capability_count' => count($caps),
        'capabilities' => $capabilities,
    ];
}

// Prepare template context
$context = [
    'lang' => \core\string_manager::get_language(),
    'role_id' => $roleid,
    'role_name' => htmlspecialchars($role->name),
    'role_shortname' => htmlspecialchars($role->shortname),
    'role_description' => $role->description ? htmlspecialchars($role->description) : null,
    'success' => $success,
    'sesskey' => sesskey(),
    'components' => $components,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

echo render_template('admin/role_define', $context);
