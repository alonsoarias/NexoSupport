<?php
/**
 * ISER - Admin Reports Manager
 *
 * Generates system reports and statistics.
 * Provides data visualization and export capabilities.
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Modules\Admin;

use ISER\Core\Database\Database;

class AdminReports
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get available reports
     *
     * @return array Available reports
     */
    public function getReports(): array
    {
        return [
            'users' => [
                'name' => 'Reporte de Usuarios',
                'description' => 'Estadísticas y listado de usuarios del sistema',
                'icon' => 'bi-people',
            ],
            'logins' => [
                'name' => 'Reporte de Accesos',
                'description' => 'Historial de inicios de sesión',
                'icon' => 'bi-door-open',
            ],
            'roles' => [
                'name' => 'Reporte de Roles',
                'description' => 'Distribución de roles y permisos',
                'icon' => 'bi-shield-check',
            ],
            'mfa' => [
                'name' => 'Reporte de MFA',
                'description' => 'Uso de autenticación multi-factor',
                'icon' => 'bi-shield-lock',
            ],
            'admin_activity' => [
                'name' => 'Actividad Administrativa',
                'description' => 'Registro de acciones administrativas',
                'icon' => 'bi-activity',
            ],
        ];
    }

    /**
     * Generate user report
     *
     * @param int $days Number of days to include
     * @return array User report data
     */
    public function getUserReport(int $days = 30): array
    {
        $since = time() - ($days * 86400);

        return [
            'total_users' => $this->getTotalUsers(),
            'active_users' => $this->getActiveUsers(),
            'new_users' => $this->getNewUsers($since),
            'suspended_users' => $this->getSuspendedUsers(),
            'users_by_day' => $this->getUsersByDay($days),
        ];
    }

    /**
     * Generate login report
     *
     * @param int $days Number of days to include
     * @return array Login report data
     */
    public function getLoginReport(int $days = 30): array
    {
        $since = time() - ($days * 86400);

        $sql_total = "SELECT COUNT(*) as count
                      FROM {$this->db->table('login_tracking')}
                      WHERE timecreated >= :since";

        $sql_successful = "SELECT COUNT(*) as count
                          FROM {$this->db->table('login_tracking')}
                          WHERE timecreated >= :since AND success = 1";

        $sql_failed = "SELECT COUNT(*) as count
                      FROM {$this->db->table('login_tracking')}
                      WHERE timecreated >= :since AND success = 0";

        return [
            'total_attempts' => (int)$this->db->getConnection()->fetchColumn($sql_total, [':since' => $since]),
            'successful' => (int)$this->db->getConnection()->fetchColumn($sql_successful, [':since' => $since]),
            'failed' => (int)$this->db->getConnection()->fetchColumn($sql_failed, [':since' => $since]),
            'logins_by_day' => $this->getLoginsByDay($days),
        ];
    }

    /**
     * Generate role distribution report
     *
     * @return array Role report data
     */
    public function getRoleReport(): array
    {
        $sql = "SELECT r.name, r.shortname, COUNT(ra.id) as user_count
                FROM {$this->db->table('roles')} r
                LEFT JOIN {$this->db->table('role_assignments')} ra ON r.id = ra.roleid
                GROUP BY r.id, r.name, r.shortname
                ORDER BY user_count DESC";

        return $this->db->getConnection()->fetchAll($sql);
    }

    /**
     * Generate MFA usage report
     *
     * @return array MFA report data
     */
    public function getMfaReport(): array
    {
        try {
            $sql_total_users = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE deleted = 0";
            $sql_mfa_enabled = "SELECT COUNT(DISTINCT userid) as count
                               FROM {$this->db->table('mfa_user_config')}
                               WHERE enabled = 1";

            $total = (int)$this->db->getConnection()->fetchColumn($sql_total_users);
            $enabled = (int)$this->db->getConnection()->fetchColumn($sql_mfa_enabled);

            return [
                'total_users' => $total,
                'mfa_enabled' => $enabled,
                'mfa_disabled' => $total - $enabled,
                'adoption_rate' => $total > 0 ? round(($enabled / $total) * 100, 2) : 0,
                'factors_distribution' => $this->getMfaFactorsDistribution(),
            ];
        } catch (\Exception $e) {
            return [
                'total_users' => 0,
                'mfa_enabled' => 0,
                'mfa_disabled' => 0,
                'adoption_rate' => 0,
                'factors_distribution' => [],
            ];
        }
    }

    /**
     * Get total users
     *
     * @return int Total users
     */
    private function getTotalUsers(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE deleted = 0";
        return (int)$this->db->getConnection()->fetchColumn($sql);
    }

    /**
     * Get active users
     *
     * @return int Active users
     */
    private function getActiveUsers(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE status = 1 AND deleted = 0";
        return (int)$this->db->getConnection()->fetchColumn($sql);
    }

    /**
     * Get new users since date
     *
     * @param int $since Timestamp
     * @return int New users
     */
    private function getNewUsers(int $since): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')}
                WHERE timecreated >= :since AND deleted = 0";
        return (int)$this->db->getConnection()->fetchColumn($sql, [':since' => $since]);
    }

    /**
     * Get suspended users
     *
     * @return int Suspended users
     */
    private function getSuspendedUsers(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE suspended = 1 AND deleted = 0";
        return (int)$this->db->getConnection()->fetchColumn($sql);
    }

    /**
     * Get users grouped by day
     *
     * @param int $days Number of days
     * @return array Users by day
     */
    private function getUsersByDay(int $days): array
    {
        $since = time() - ($days * 86400);

        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date, COUNT(*) as count
                FROM {$this->db->table('users')}
                WHERE timecreated >= :since
                GROUP BY date
                ORDER BY date ASC";

        return $this->db->getConnection()->fetchAll($sql, [':since' => $since]);
    }

    /**
     * Get logins grouped by day
     *
     * @param int $days Number of days
     * @return array Logins by day
     */
    private function getLoginsByDay(int $days): array
    {
        $since = time() - ($days * 86400);

        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date,
                COUNT(*) as total,
                SUM(success) as successful,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
                FROM {$this->db->table('login_tracking')}
                WHERE timecreated >= :since
                GROUP BY date
                ORDER BY date ASC";

        return $this->db->getConnection()->fetchAll($sql, [':since' => $since]);
    }

    /**
     * Get MFA factors distribution
     *
     * @return array Factors distribution
     */
    private function getMfaFactorsDistribution(): array
    {
        try {
            $sql = "SELECT factor, COUNT(DISTINCT userid) as count
                    FROM {$this->db->table('mfa_user_config')}
                    WHERE enabled = 1
                    GROUP BY factor";

            return $this->db->getConnection()->fetchAll($sql);
        } catch (\Exception $e) {
            return [];
        }
    }
}
