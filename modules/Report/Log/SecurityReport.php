<?php
/**
 * Generador de reportes de seguridad
 * @package Report\Log
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;
use Monolog\Logger;

class SecurityReport
{
    private Database $db;
    private Logger $logger;
    private ReportLog $reportLog;
    private LogManager $logManager;

    public function __construct(Database $db, Logger $logger, ReportLog $reportLog, LogManager $logManager)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->reportLog = $reportLog;
        $this->logManager = $logManager;
    }

    /**
     * Generar reporte de seguridad completo
     */
    public function generateSecurityReport(int $days = 30): array
    {
        $cutoff = time() - ($days * 86400);

        return [
            'period' => "{$days} días",
            'generated_at' => date('Y-m-d H:i:s'),
            'failed_logins' => $this->getFailedLoginSummary($cutoff),
            'security_alerts' => $this->getAlertsSummary($cutoff),
            'suspicious_ips' => $this->getSuspiciousIPs($cutoff),
            'user_anomalies' => $this->getUserAnomalies($cutoff),
            'access_patterns' => $this->getAccessPatterns($cutoff),
            'privilege_changes' => $this->getPrivilegeChanges($cutoff),
            'data_access' => $this->getDataAccessSummary($cutoff)
        ];
    }

    /**
     * Resumen de intentos de login fallidos
     */
    private function getFailedLoginSummary(int $cutoff): array
    {
        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_login_attempts
             WHERE success = 0 AND timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        $byReason = $this->db->query(
            "SELECT failure_reason, COUNT(*) as count
             FROM iser_login_attempts
             WHERE success = 0 AND timecreated > :cutoff
             GROUP BY failure_reason
             ORDER BY count DESC",
            ['cutoff' => $cutoff]
        );

        $topIPs = $this->db->query(
            "SELECT ip_address, COUNT(*) as attempts, COUNT(DISTINCT username) as usernames
             FROM iser_login_attempts
             WHERE success = 0 AND timecreated > :cutoff
             GROUP BY ip_address
             ORDER BY attempts DESC
             LIMIT 10",
            ['cutoff' => $cutoff]
        );

        $topUsernames = $this->db->query(
            "SELECT username, COUNT(*) as attempts, COUNT(DISTINCT ip_address) as ips
             FROM iser_login_attempts
             WHERE success = 0 AND timecreated > :cutoff
             GROUP BY username
             ORDER BY attempts DESC
             LIMIT 10",
            ['cutoff' => $cutoff]
        );

        return [
            'total' => (int)$total,
            'by_reason' => $byReason,
            'top_ips' => $topIPs,
            'top_usernames' => $topUsernames
        ];
    }

    /**
     * Resumen de alertas de seguridad
     */
    private function getAlertsSummary(int $cutoff): array
    {
        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_security_alerts
             WHERE timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        $bySeverity = $this->db->query(
            "SELECT severity, COUNT(*) as count
             FROM iser_security_alerts
             WHERE timecreated > :cutoff
             GROUP BY severity
             ORDER BY severity DESC",
            ['cutoff' => $cutoff]
        );

        $byType = $this->db->query(
            "SELECT alert_type, COUNT(*) as count
             FROM iser_security_alerts
             WHERE timecreated > :cutoff
             GROUP BY alert_type
             ORDER BY count DESC",
            ['cutoff' => $cutoff]
        );

        $byStatus = $this->db->query(
            "SELECT status, COUNT(*) as count
             FROM iser_security_alerts
             WHERE timecreated > :cutoff
             GROUP BY status",
            ['cutoff' => $cutoff]
        );

        $unresolved = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_security_alerts
             WHERE status IN ('new', 'investigating') AND timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        return [
            'total' => (int)$total,
            'by_severity' => $bySeverity,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'unresolved' => (int)$unresolved
        ];
    }

    /**
     * IPs sospechosas
     */
    private function getSuspiciousIPs(int $cutoff): array
    {
        // IPs con múltiples intentos fallidos
        $failedLogins = $this->db->query(
            "SELECT ip_address, COUNT(*) as attempts,
                    COUNT(DISTINCT username) as usernames_tried
             FROM iser_login_attempts
             WHERE success = 0 AND timecreated > :cutoff
             GROUP BY ip_address
             HAVING attempts >= 5
             ORDER BY attempts DESC
             LIMIT 20",
            ['cutoff' => $cutoff]
        );

        // IPs con actividad inusual
        $unusualActivity = $this->db->query(
            "SELECT ip_address, COUNT(*) as actions,
                    COUNT(DISTINCT userid) as users,
                    COUNT(DISTINCT component) as components
             FROM iser_logs
             WHERE timecreated > :cutoff
             GROUP BY ip_address
             HAVING users >= 5 OR actions >= 100
             ORDER BY actions DESC
             LIMIT 20",
            ['cutoff' => $cutoff]
        );

        return [
            'failed_logins' => $failedLogins,
            'unusual_activity' => $unusualActivity
        ];
    }

    /**
     * Anomalías de usuarios
     */
    private function getUserAnomalies(int $cutoff): array
    {
        // Usuarios con múltiples IPs
        $multipleIPs = $this->db->query(
            "SELECT userid, COUNT(DISTINCT ip_address) as ip_count
             FROM iser_logs
             WHERE timecreated > :cutoff AND userid > 0
             GROUP BY userid
             HAVING ip_count >= 5
             ORDER BY ip_count DESC
             LIMIT 10",
            ['cutoff' => $cutoff]
        );

        // Usuarios con actividad inusualmente alta
        $highActivity = $this->db->query(
            "SELECT userid, COUNT(*) as action_count,
                    COUNT(DISTINCT component) as components_used
             FROM iser_logs
             WHERE timecreated > :cutoff AND userid > 0
             GROUP BY userid
             ORDER BY action_count DESC
             LIMIT 10",
            ['cutoff' => $cutoff]
        );

        // Usuarios con muchas operaciones de eliminación
        $massDelete = $this->db->query(
            "SELECT userid, COUNT(*) as delete_count,
                    GROUP_CONCAT(DISTINCT component) as components
             FROM iser_logs
             WHERE crud = 'd' AND timecreated > :cutoff AND userid > 0
             GROUP BY userid
             HAVING delete_count >= 10
             ORDER BY delete_count DESC",
            ['cutoff' => $cutoff]
        );

        return [
            'multiple_ips' => $multipleIPs,
            'high_activity' => $highActivity,
            'mass_delete' => $massDelete
        ];
    }

    /**
     * Patrones de acceso
     */
    private function getAccessPatterns(int $cutoff): array
    {
        // Accesos por hora del día
        $byHour = $this->db->query(
            "SELECT HOUR(FROM_UNIXTIME(timecreated)) as hour, COUNT(*) as count
             FROM iser_logs
             WHERE timecreated > :cutoff
             GROUP BY hour
             ORDER BY hour",
            ['cutoff' => $cutoff]
        );

        // Accesos por día de la semana
        $byDayOfWeek = $this->db->query(
            "SELECT DAYOFWEEK(FROM_UNIXTIME(timecreated)) as day, COUNT(*) as count
             FROM iser_logs
             WHERE timecreated > :cutoff
             GROUP BY day
             ORDER BY day",
            ['cutoff' => $cutoff]
        );

        // Actividad fuera de horario (10pm - 6am)
        $offHours = $this->db->query(
            "SELECT userid, COUNT(*) as count, ip_address
             FROM iser_logs
             WHERE timecreated > :cutoff
               AND HOUR(FROM_UNIXTIME(timecreated)) NOT BETWEEN 6 AND 21
               AND userid > 0
             GROUP BY userid, ip_address
             HAVING count >= 10
             ORDER BY count DESC
             LIMIT 20",
            ['cutoff' => $cutoff]
        );

        return [
            'by_hour' => $byHour,
            'by_day_of_week' => $byDayOfWeek,
            'off_hours' => $offHours
        ];
    }

    /**
     * Cambios de privilegios
     */
    private function getPrivilegeChanges(int $cutoff): array
    {
        // Cambios en roles
        $roleChanges = $this->db->query(
            "SELECT * FROM iser_audit_trail
             WHERE resource_type = 'role'
               AND action IN ('create', 'update', 'delete')
               AND timecreated > :cutoff
             ORDER BY timecreated DESC
             LIMIT 50",
            ['cutoff' => $cutoff]
        );

        // Cambios en permisos
        $permissionChanges = $this->db->query(
            "SELECT * FROM iser_audit_trail
             WHERE resource_type = 'permission'
               AND action IN ('assign', 'revoke')
               AND timecreated > :cutoff
             ORDER BY timecreated DESC
             LIMIT 50",
            ['cutoff' => $cutoff]
        );

        // Usuarios promovidos/degradados
        $userRoleChanges = $this->db->query(
            "SELECT * FROM iser_audit_trail
             WHERE resource_type = 'user_role'
               AND timecreated > :cutoff
             ORDER BY timecreated DESC
             LIMIT 50",
            ['cutoff' => $cutoff]
        );

        return [
            'role_changes' => $roleChanges,
            'permission_changes' => $permissionChanges,
            'user_role_changes' => $userRoleChanges
        ];
    }

    /**
     * Resumen de acceso a datos sensibles
     */
    private function getDataAccessSummary(int $cutoff): array
    {
        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_audit_trail
             WHERE timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        $byAction = $this->db->query(
            "SELECT action, COUNT(*) as count
             FROM iser_audit_trail
             WHERE timecreated > :cutoff
             GROUP BY action
             ORDER BY count DESC",
            ['cutoff' => $cutoff]
        );

        $byResourceType = $this->db->query(
            "SELECT resource_type, COUNT(*) as count
             FROM iser_audit_trail
             WHERE timecreated > :cutoff
             GROUP BY resource_type
             ORDER BY count DESC",
            ['cutoff' => $cutoff]
        );

        $topUsers = $this->db->query(
            "SELECT userid, COUNT(*) as access_count
             FROM iser_audit_trail
             WHERE timecreated > :cutoff
             GROUP BY userid
             ORDER BY access_count DESC
             LIMIT 10",
            ['cutoff' => $cutoff]
        );

        return [
            'total' => (int)$total,
            'by_action' => $byAction,
            'by_resource_type' => $byResourceType,
            'top_users' => $topUsers
        ];
    }

    /**
     * Generar reporte de cumplimiento (compliance)
     */
    public function generateComplianceReport(int $days = 30): array
    {
        $cutoff = time() - ($days * 86400);

        return [
            'period' => "{$days} días",
            'generated_at' => date('Y-m-d H:i:s'),
            'audit_completeness' => $this->checkAuditCompleteness($cutoff),
            'password_policies' => $this->checkPasswordPolicies($cutoff),
            'mfa_adoption' => $this->checkMFAAdoption(),
            'session_security' => $this->checkSessionSecurity($cutoff),
            'access_reviews' => $this->getAccessReviews($cutoff)
        ];
    }

    /**
     * Verificar completitud de auditoría
     */
    private function checkAuditCompleteness(int $cutoff): array
    {
        $totalActions = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_logs WHERE timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        $auditedActions = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_audit_trail WHERE timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        return [
            'total_actions' => (int)$totalActions,
            'audited_actions' => (int)$auditedActions,
            'coverage_percentage' => $totalActions > 0 ? round(($auditedActions / $totalActions) * 100, 2) : 0
        ];
    }

    /**
     * Verificar políticas de contraseñas
     */
    private function checkPasswordPolicies(int $cutoff): array
    {
        // Usuarios con contraseñas antiguas
        $oldPasswords = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_users
             WHERE password_changed < :cutoff",
            ['cutoff' => time() - (90 * 86400)] // 90 días
        )[0]['count'] ?? 0;

        return [
            'users_with_old_passwords' => (int)$oldPasswords,
            'password_change_required' => (int)$oldPasswords > 0
        ];
    }

    /**
     * Verificar adopción de MFA
     */
    private function checkMFAAdoption(): array
    {
        $totalUsers = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_users WHERE active = 1"
        )[0]['count'] ?? 0;

        $mfaEnabled = $this->db->query(
            "SELECT COUNT(DISTINCT user_id) as count FROM iser_mfa_factors
             WHERE active = 1"
        )[0]['count'] ?? 0;

        return [
            'total_active_users' => (int)$totalUsers,
            'mfa_enabled_users' => (int)$mfaEnabled,
            'mfa_adoption_percentage' => $totalUsers > 0 ? round(($mfaEnabled / $totalUsers) * 100, 2) : 0
        ];
    }

    /**
     * Verificar seguridad de sesiones
     */
    private function checkSessionSecurity(int $cutoff): array
    {
        $activeSessions = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_user_sessions
             WHERE last_activity > :cutoff",
            ['cutoff' => time() - 3600]
        )[0]['count'] ?? 0;

        return [
            'active_sessions' => (int)$activeSessions
        ];
    }

    /**
     * Revisiones de acceso
     */
    private function getAccessReviews(int $cutoff): array
    {
        $criticalAccess = $this->db->query(
            "SELECT COUNT(DISTINCT userid) as count FROM iser_audit_trail
             WHERE resource_type IN ('user', 'role', 'permission')
               AND timecreated > :cutoff",
            ['cutoff' => $cutoff]
        )[0]['count'] ?? 0;

        return [
            'users_with_critical_access' => (int)$criticalAccess
        ];
    }
}
