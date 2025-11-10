<?php
/**
 * ISER - Admin Panel Manager
 *
 * Main controller for the administration panel.
 * Provides dashboard statistics, system information, and admin navigation.
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Modules\Admin;

use ISER\Core\Database\Database;
use ISER\Core\Config\SettingsManager;
use ISER\Core\Utils\Logger;

class AdminManager
{
    private Database $db;
    private SettingsManager $settings;

    public function __construct(Database $db, SettingsManager $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    /**
     * Get admin dashboard statistics
     *
     * @return array Dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'users' => $this->getUserStats(),
            'auth' => $this->getAuthStats(),
            'roles' => $this->getRoleStats(),
            'mfa' => $this->getMfaStats(),
            'system' => $this->getSystemStats(),
        ];
    }

    /**
     * Get user statistics
     *
     * @return array User statistics
     */
    private function getUserStats(): array
    {
        $sql_total = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE deleted = 0";
        $sql_active = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE status = 1 AND deleted = 0";
        $sql_suspended = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE suspended = 1 AND deleted = 0";
        $sql_new_today = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE timecreated >= :today";

        $today = strtotime('today');

        return [
            'total' => (int)$this->db->getConnection()->fetchColumn($sql_total),
            'active' => (int)$this->db->getConnection()->fetchColumn($sql_active),
            'suspended' => (int)$this->db->getConnection()->fetchColumn($sql_suspended),
            'new_today' => (int)$this->db->getConnection()->fetchColumn($sql_new_today, [':today' => $today]),
        ];
    }

    /**
     * Get authentication statistics
     *
     * @return array Authentication statistics
     */
    private function getAuthStats(): array
    {
        $sql_total_logins = "SELECT COUNT(*) as count FROM {$this->db->table('login_tracking')}";
        $sql_today_logins = "SELECT COUNT(*) as count FROM {$this->db->table('login_tracking')}
                             WHERE success = 1 AND timecreated >= :today";
        $sql_failed_today = "SELECT COUNT(*) as count FROM {$this->db->table('login_tracking')}
                             WHERE success = 0 AND timecreated >= :today";

        $today = strtotime('today');

        return [
            'total_logins' => (int)$this->db->getConnection()->fetchColumn($sql_total_logins),
            'logins_today' => (int)$this->db->getConnection()->fetchColumn($sql_today_logins, [':today' => $today]),
            'failed_today' => (int)$this->db->getConnection()->fetchColumn($sql_failed_today, [':today' => $today]),
        ];
    }

    /**
     * Get role statistics
     *
     * @return array Role statistics
     */
    private function getRoleStats(): array
    {
        $sql_total_roles = "SELECT COUNT(*) as count FROM {$this->db->table('roles')}";
        $sql_total_assignments = "SELECT COUNT(*) as count FROM {$this->db->table('role_assignments')}";

        return [
            'total_roles' => (int)$this->db->getConnection()->fetchColumn($sql_total_roles),
            'total_assignments' => (int)$this->db->getConnection()->fetchColumn($sql_total_assignments),
        ];
    }

    /**
     * Get MFA statistics
     *
     * @return array MFA statistics
     */
    private function getMfaStats(): array
    {
        // Check if MFA tables exist
        try {
            $sql_enabled_users = "SELECT COUNT(DISTINCT userid) as count
                                  FROM {$this->db->table('mfa_user_config')}
                                  WHERE enabled = 1";
            $sql_totp_users = "SELECT COUNT(DISTINCT userid) as count
                               FROM {$this->db->table('mfa_user_config')}
                               WHERE factor = 'totp' AND enabled = 1";

            return [
                'enabled_users' => (int)$this->db->getConnection()->fetchColumn($sql_enabled_users),
                'totp_users' => (int)$this->db->getConnection()->fetchColumn($sql_totp_users),
                'mfa_enabled' => $this->settings->getBool('enabled', 'tool_mfa', false),
            ];
        } catch (\Exception $e) {
            return [
                'enabled_users' => 0,
                'totp_users' => 0,
                'mfa_enabled' => false,
            ];
        }
    }

    /**
     * Get system statistics
     *
     * @return array System statistics
     */
    private function getSystemStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $this->getDatabaseVersion(),
            'iser_version' => '6.0.0',
        ];
    }

    /**
     * Get database version
     *
     * @return string Database version
     */
    private function getDatabaseVersion(): string
    {
        try {
            $version = $this->db->getConnection()->fetchColumn('SELECT VERSION()');
            return $version ?: 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get admin sections for navigation
     *
     * @return array Admin sections
     */
    public function getAdminSections(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard',
                'url' => '/admin/index.php',
                'icon' => 'bi-speedometer2',
                'order' => 1,
            ],
            'users' => [
                'name' => 'Usuarios',
                'url' => '/admin/user.php',
                'icon' => 'bi-people',
                'order' => 2,
            ],
            'roles' => [
                'name' => 'Roles y Permisos',
                'url' => '/admin/roles/manage.php',
                'icon' => 'bi-shield-check',
                'order' => 3,
                'subsections' => [
                    ['name' => 'Gestionar Roles', 'url' => '/admin/roles/manage.php'],
                    ['name' => 'Administradores', 'url' => '/admin/roles/admins.php'],
                ],
            ],
            'settings' => [
                'name' => 'Configuración',
                'url' => '/admin/settings.php',
                'icon' => 'bi-gear',
                'order' => 4,
                'subsections' => [
                    ['name' => 'General', 'url' => '/admin/settings.php'],
                    ['name' => 'Autenticación', 'url' => '/admin/settings.php?section=manageauths'],
                    ['name' => 'Correo Saliente', 'url' => '/admin/settings.php?section=outgoingmailconfig'],
                    ['name' => 'MFA', 'url' => '/admin/settings.php?section=mfa'],
                    ['name' => 'Políticas del Sitio', 'url' => '/admin/settings.php?section=sitepolicies'],
                    ['name' => 'Tema', 'url' => '/admin/settings.php?section=themesettingiser'],
                ],
            ],
            'plugins' => [
                'name' => 'Plugins',
                'url' => '/admin/plugins.php',
                'icon' => 'bi-plugin',
                'order' => 5,
            ],
            'tools' => [
                'name' => 'Herramientas',
                'url' => '/admin/tools.php',
                'icon' => 'bi-tools',
                'order' => 6,
                'subsections' => [
                    ['name' => 'Instalar Addon', 'url' => '/admin/tool/installaddon/index.php'],
                    ['name' => 'Cargar Usuarios', 'url' => '/admin/tool/uploaduser/index.php'],
                ],
            ],
            'reports' => [
                'name' => 'Reportes',
                'url' => '/admin/reports.php',
                'icon' => 'bi-graph-up',
                'order' => 7,
            ],
        ];
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public function getSystemInfo(): array
    {
        return [
            'iser_version' => '6.0.0',
            'php_version' => PHP_VERSION,
            'php_extensions' => get_loaded_extensions(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => [
                'type' => 'MySQL',
                'version' => $this->getDatabaseVersion(),
                'prefix' => $this->db->getPrefix(),
            ],
            'paths' => [
                'base' => ISER_BASE_DIR,
                'public' => ISER_BASE_DIR . '/public_html',
                'var' => ISER_BASE_DIR . '/var',
            ],
            'settings' => [
                'sitename' => $this->settings->get('sitename', 'core', 'ISER'),
                'timezone' => $this->settings->get('timezone', 'core', 'UTC'),
                'language' => $this->settings->get('defaultlanguage', 'core', 'es'),
            ],
        ];
    }

    /**
     * Get recent admin activity
     *
     * @param int $limit Number of records to return
     * @return array Recent admin activities
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $sql = "SELECT al.*, u.username
                FROM {$this->db->table('admin_log')} al
                JOIN {$this->db->table('users')} u ON al.userid = u.id
                ORDER BY al.timecreated DESC
                LIMIT :limit";

        try {
            return $this->db->getConnection()->fetchAll($sql, [':limit' => $limit]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Log admin action
     *
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $component Component affected
     * @param int|null $objectId Object ID
     * @param string|null $objectTable Object table
     * @param array $data Additional data
     * @return bool True on success
     */
    public function logAction(
        int $userId,
        string $action,
        string $component,
        ?int $objectId = null,
        ?string $objectTable = null,
        array $data = []
    ): bool {
        try {
            $this->db->insert('admin_log', [
                'userid' => $userId,
                'action' => $action,
                'component' => $component,
                'objectid' => $objectId,
                'objecttable' => $objectTable,
                'data' => json_encode($data),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'timecreated' => time(),
            ]);

            Logger::auth('Admin action logged', [
                'userid' => $userId,
                'action' => $action,
                'component' => $component,
            ]);

            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to log admin action', [
                'error' => $e->getMessage(),
                'action' => $action,
            ]);
            return false;
        }
    }

    /**
     * Check if user is administrator
     *
     * @param int $userId User ID
     * @return bool True if administrator
     */
    public function isAdmin(int $userId): bool
    {
        // Check via PermissionManager (Phase 4)
        $sql = "SELECT COUNT(*) as count
                FROM {$this->db->table('role_assignments')} ra
                JOIN {$this->db->table('roles')} r ON ra.roleid = r.id
                WHERE ra.userid = :userid AND r.shortname = 'admin'";

        try {
            $count = $this->db->getConnection()->fetchColumn($sql, [':userid' => $userId]);
            return $count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get admin count
     *
     * @return int Number of administrators
     */
    public function getAdminCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT ra.userid) as count
                FROM {$this->db->table('role_assignments')} ra
                JOIN {$this->db->table('roles')} r ON ra.roleid = r.id
                WHERE r.shortname = 'admin'";

        try {
            return (int)$this->db->getConnection()->fetchColumn($sql);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
