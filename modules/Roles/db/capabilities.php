<?php
/**
 * ISER Roles System - Base Capabilities Definition
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    4.0.0
 * @since      Phase 4
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

// Risk levels
define('RISK_SPAM', 1);
define('RISK_PERSONAL', 2);
define('RISK_XSS', 4);
define('RISK_CONFIG', 8);
define('RISK_MANAGEMENT', 16);
define('RISK_DATALOSS', 32);

// Context levels
define('CONTEXT_SYSTEM', 10);
define('CONTEXT_USER', 30);
define('CONTEXT_MODULE', 70);
define('CONTEXT_BLOCK', 80);

/**
 * Get all base system capabilities
 *
 * @return array Array of capability definitions
 */
function get_base_capabilities(): array
{
    return [
        // Site administration capabilities
        'moodle/site:config' => [
            'component' => 'core',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
            'description' => 'Configurar el sitio y cambiar ajustes globales',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/site:accessallgroups' => [
            'component' => 'core',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Acceder a todos los grupos',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        // User management capabilities
        'moodle/user:create' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_SPAM,
            'description' => 'Crear nuevos usuarios',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        'moodle/user:update' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Editar información de usuarios',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        'moodle/user:delete' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_DATALOSS,
            'description' => 'Eliminar usuarios',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/user:viewalldetails' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_USER,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Ver todos los detalles de usuario',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        'moodle/user:viewdetails' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_USER,
            'riskbitmask' => 0,
            'description' => 'Ver detalles básicos de usuario',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW, 'user' => CAP_ALLOW]
        ],

        'moodle/user:manageownprofile' => [
            'component' => 'user',
            'contextlevel' => CONTEXT_USER,
            'riskbitmask' => 0,
            'description' => 'Gestionar su propio perfil',
            'archetypes' => ['user' => CAP_ALLOW]
        ],

        // Role management capabilities
        'moodle/role:assign' => [
            'component' => 'role',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_MANAGEMENT,
            'description' => 'Asignar roles a usuarios',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        'moodle/role:manage' => [
            'component' => 'role',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_MANAGEMENT | RISK_CONFIG,
            'description' => 'Crear, editar y eliminar roles',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/role:override' => [
            'component' => 'role',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_MANAGEMENT,
            'description' => 'Sobrescribir permisos de roles',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/role:review' => [
            'component' => 'role',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => 0,
            'description' => 'Ver definiciones de roles y permisos',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        // Authentication capabilities
        'moodle/auth:changeownpassword' => [
            'component' => 'auth',
            'contextlevel' => CONTEXT_USER,
            'riskbitmask' => 0,
            'description' => 'Cambiar su propia contraseña',
            'archetypes' => ['user' => CAP_ALLOW]
        ],

        'moodle/auth:resetpassword' => [
            'component' => 'auth',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Resetear contraseñas de otros usuarios',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        // Backup and restore
        'moodle/backup:backupsite' => [
            'component' => 'backup',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
            'description' => 'Realizar backup del sitio completo',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/restore:restoresite' => [
            'component' => 'restore',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
            'description' => 'Restaurar backup del sitio',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        // Logging and reporting
        'moodle/site:viewreports' => [
            'component' => 'core',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Ver reportes del sitio',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        'moodle/site:viewlogs' => [
            'component' => 'core',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Ver logs del sistema',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ],

        // MFA (Multi-Factor Authentication)
        'moodle/mfa:manage' => [
            'component' => 'mfa',
            'contextlevel' => CONTEXT_USER,
            'riskbitmask' => 0,
            'description' => 'Gestionar su propia autenticación multifactor',
            'archetypes' => ['user' => CAP_ALLOW]
        ],

        'moodle/mfa:enforce' => [
            'component' => 'mfa',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_CONFIG,
            'description' => 'Forzar MFA para otros usuarios',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        // Session management
        'moodle/session:manage' => [
            'component' => 'session',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Gestionar sesiones de usuarios',
            'archetypes' => ['admin' => CAP_ALLOW]
        ],

        'moodle/session:viewactive' => [
            'component' => 'session',
            'contextlevel' => CONTEXT_SYSTEM,
            'riskbitmask' => RISK_PERSONAL,
            'description' => 'Ver sesiones activas',
            'archetypes' => ['admin' => CAP_ALLOW, 'manager' => CAP_ALLOW]
        ]
    ];
}

// Permission levels
define('CAP_INHERIT', 0);
define('CAP_ALLOW', 1);
define('CAP_PREVENT', -1);
define('CAP_PROHIBIT', -1000);

/**
 * Install base capabilities in database
 *
 * @param \ISER\Core\Database\Database $db Database instance
 * @return bool True on success
 */
function install_base_capabilities(\ISER\Core\Database\Database $db): bool
{
    $capabilities = get_base_capabilities();
    $now = time();

    foreach ($capabilities as $name => $capability) {
        $data = [
            'name' => $name,
            'component' => $capability['component'],
            'contextlevel' => $capability['contextlevel'],
            'riskbitmask' => $capability['riskbitmask'],
            'description' => $capability['description'],
            'timecreated' => $now,
            'timemodified' => $now
        ];

        try {
            $db->insert('capabilities', $data);
        } catch (\Exception $e) {
            error_log('Failed to insert capability ' . $name . ': ' . $e->getMessage());
        }
    }

    // Assign capabilities to default roles
    assign_default_capabilities($db);

    return true;
}

/**
 * Assign capabilities to default roles based on archetypes
 *
 * @param \ISER\Core\Database\Database $db Database instance
 * @return bool True on success
 */
function assign_default_capabilities(\ISER\Core\Database\Database $db): bool
{
    $capabilities = get_base_capabilities();
    $now = time();

    // Get roles
    $roles = $db->select('roles');
    $rolesByShortname = [];
    foreach ($roles as $role) {
        $rolesByShortname[$role['shortname']] = $role;
    }

    // Get system context
    $systemContext = $db->selectOne('context', ['contextlevel' => CONTEXT_SYSTEM]);

    foreach ($capabilities as $capName => $capDef) {
        // Get capability ID
        $capability = $db->selectOne('capabilities', ['name' => $capName]);
        if (!$capability) continue;

        // Assign to roles based on archetype
        if (isset($capDef['archetypes'])) {
            foreach ($capDef['archetypes'] as $archetype => $permission) {
                if (!isset($rolesByShortname[$archetype])) continue;

                $role = $rolesByShortname[$archetype];

                $assignment = [
                    'roleid' => $role['id'],
                    'capabilityid' => $capability['id'],
                    'permission' => $permission,
                    'contextid' => $systemContext['id'],
                    'timecreated' => $now,
                    'timemodified' => $now
                ];

                try {
                    $db->insert('role_capabilities', $assignment);
                } catch (\Exception $e) {
                    // Might already exist
                }
            }
        }
    }

    return true;
}
