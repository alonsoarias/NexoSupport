<?php
/**
 * RBAC Installation Script
 *
 * Instala roles y capabilities iniciales del sistema.
 * Este script se ejecuta automáticamente al finalizar la instalación.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use core\rbac\context;
use core\rbac\role;
use core\rbac\access;

/**
 * Install RBAC system
 *
 * @return bool
 */
function install_rbac_system(): bool {
    global $DB;

    try {
        // ========================================
        // 1. Create system context
        // ========================================
        $syscontext = context::system();

        // ========================================
        // 2. Define system capabilities
        // ========================================
        $capabilities = get_system_capabilities();

        foreach ($capabilities as $cap) {
            // Insert capability definition
            $existing = $DB->get_record('capabilities', ['name' => $cap['name']]);

            if (!$existing) {
                $record = new stdClass();
                $record->name = $cap['name'];
                $record->captype = $cap['captype'];
                $record->contextlevel = $cap['contextlevel'];
                $record->component = $cap['component'];
                $record->riskbitmask = $cap['riskbitmask'];

                $DB->insert_record('capabilities', $record);
            }
        }

        // ========================================
        // 3. Create roles
        // ========================================

        // Administrator role
        $admin = role::get_by_shortname('administrator');
        if (!$admin) {
            $admin = role::create(
                'administrator',
                'Administrador',
                'Acceso completo al sistema',
                1
            );
        }

        // Manager role
        $manager = role::get_by_shortname('manager');
        if (!$manager) {
            $manager = role::create(
                'manager',
                'Gestor',
                'Puede gestionar usuarios y configuración',
                2
            );
        }

        // User role
        $user = role::get_by_shortname('user');
        if (!$user) {
            $user = role::create(
                'user',
                'Usuario',
                'Usuario estándar del sistema',
                3
            );
        }

        // ========================================
        // 4. Assign capabilities to roles
        // ========================================

        // Administrator: ALL capabilities
        foreach ($capabilities as $cap) {
            $admin->assign_capability(
                $cap['name'],
                access::PERMISSION_ALLOW,
                $syscontext
            );
        }

        // Manager: Management capabilities
        $manager_caps = [
            'nexosupport/admin:viewdashboard',
            'nexosupport/user:view',
            'nexosupport/user:create',
            'nexosupport/user:update',
            'nexosupport/role:view',
            'nexosupport/role:assign',
            'nexosupport/log:view',
        ];

        foreach ($manager_caps as $capname) {
            $manager->assign_capability(
                $capname,
                access::PERMISSION_ALLOW,
                $syscontext
            );
        }

        // User: Basic capabilities
        $user_caps = [
            'nexosupport/user:viewown',
            'nexosupport/user:updateown',
        ];

        foreach ($user_caps as $capname) {
            $user->assign_capability(
                $capname,
                access::PERMISSION_ALLOW,
                $syscontext
            );
        }

        return true;

    } catch (Exception $e) {
        debugging('Error installing RBAC: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get system capabilities
 *
 * @return array
 */
function get_system_capabilities(): array {
    return [
        // Admin capabilities
        [
            'name' => 'nexosupport/admin:viewdashboard',
            'captype' => 'read',
            'contextlevel' => 10, // CONTEXT_SYSTEM
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'nexosupport/admin:manageconfig',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8 // RISK_CONFIG
        ],
        [
            'name' => 'nexosupport/admin:manageusers',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8 // RISK_CONFIG
        ],
        [
            'name' => 'nexosupport/admin:manageroles',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8 // RISK_CONFIG
        ],
        [
            'name' => 'nexosupport/admin:assignroles',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8 // RISK_CONFIG
        ],

        // User capabilities
        [
            'name' => 'nexosupport/user:view',
            'captype' => 'read',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'nexosupport/user:viewown',
            'captype' => 'read',
            'contextlevel' => 30, // CONTEXT_USER
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'nexosupport/user:create',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 4 // RISK_SPAM
        ],
        [
            'name' => 'nexosupport/user:update',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 4
        ],
        [
            'name' => 'nexosupport/user:updateown',
            'captype' => 'write',
            'contextlevel' => 30,
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'nexosupport/user:delete',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 16 // RISK_DATALOSS
        ],

        // Role capabilities
        [
            'name' => 'nexosupport/role:view',
            'captype' => 'read',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'nexosupport/role:manage',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8
        ],
        [
            'name' => 'nexosupport/role:assign',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8
        ],

        // Log capabilities
        [
            'name' => 'nexosupport/log:view',
            'captype' => 'read',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 0
        ],

        // System capabilities
        [
            'name' => 'nexosupport/system:manage',
            'captype' => 'write',
            'contextlevel' => 10,
            'component' => 'core',
            'riskbitmask' => 8
        ],
    ];
}

/**
 * Upgrade RBAC system
 *
 * @param int $oldversion
 * @return bool
 */
function upgrade_rbac_system(int $oldversion): bool {
    // Future upgrades will go here
    return true;
}
