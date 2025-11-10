<?php
/**
 * Gestor avanzado de logs con análisis y alertas
 * @package Report\Log
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Report\Log;

use ISER\Core\Database\Database;
use Monolog\Logger;

class LogManager
{
    private Database $db;
    private Logger $logger;
    private ReportLog $reportLog;

    public function __construct(Database $db, Logger $logger, ReportLog $reportLog)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->reportLog = $reportLog;
    }

    /**
     * Detectar actividad sospechosa
     */
    public function detectSuspiciousActivity(): array
    {
        $alerts = [];
        $now = time();
        $oneHourAgo = $now - 3600;

        // Detectar múltiples intentos de login fallidos
        // ACTUALIZADO: Usa schema normalizado con attempted_at
        $failedLogins = $this->db->query(
            "SELECT ip_address, username, COUNT(*) as attempts
             FROM iser_login_attempts
             WHERE success = 0 AND attempted_at > :time
             GROUP BY ip_address, username
             HAVING attempts >= 5
             ORDER BY attempts DESC",
            ['time' => $oneHourAgo]
        );

        foreach ($failedLogins as $login) {
            $alerts[] = [
                'type' => 'multiple_failed_logins',
                'severity' => $login['attempts'] >= 10 ? 4 : 3, // critical vs high
                'title' => "Múltiples intentos de login fallidos",
                'description' => "IP {$login['ip_address']} ha intentado acceder como '{$login['username']}' {$login['attempts']} veces sin éxito",
                'details' => $login,
                'ip_address' => $login['ip_address']
            ];
        }

        // Detectar actividad desde IPs inusuales
        $unusualIps = $this->db->query(
            "SELECT l.ip_address, COUNT(DISTINCT l.userid) as user_count,
                    COUNT(*) as action_count
             FROM iser_logs l
             WHERE l.timecreated > :time
               AND l.userid > 0
             GROUP BY l.ip_address
             HAVING user_count >= 5 OR action_count >= 100",
            ['time' => $oneHourAgo]
        );

        foreach ($unusualIps as $ip) {
            $alerts[] = [
                'type' => 'unusual_ip_activity',
                'severity' => 2, // medium
                'title' => "Actividad inusual desde IP",
                'description' => "IP {$ip['ip_address']} ha realizado {$ip['action_count']} acciones de {$ip['user_count']} usuarios diferentes",
                'details' => $ip,
                'ip_address' => $ip['ip_address']
            ];
        }

        // Detectar operaciones masivas de eliminación
        $massDeletes = $this->db->query(
            "SELECT userid, component, COUNT(*) as delete_count
             FROM iser_logs
             WHERE crud = 'd'
               AND timecreated > :time
             GROUP BY userid, component
             HAVING delete_count >= 10",
            ['time' => $oneHourAgo]
        );

        foreach ($massDeletes as $delete) {
            $alerts[] = [
                'type' => 'mass_deletion',
                'severity' => 3, // high
                'title' => "Eliminación masiva detectada",
                'description' => "Usuario {$delete['userid']} ha eliminado {$delete['delete_count']} registros en {$delete['component']}",
                'details' => $delete,
                'userid' => $delete['userid']
            ];
        }

        // Detectar accesos fuera de horario
        $currentHour = (int)date('H');
        if ($currentHour < 6 || $currentHour >= 22) {
            $offHoursActivity = $this->db->query(
                "SELECT userid, COUNT(*) as action_count, ip_address
                 FROM iser_logs
                 WHERE timecreated > :time
                   AND userid > 0
                 GROUP BY userid, ip_address
                 HAVING action_count >= 10",
                ['time' => $oneHourAgo]
            );

            foreach ($offHoursActivity as $activity) {
                $alerts[] = [
                    'type' => 'off_hours_activity',
                    'severity' => 1, // low
                    'title' => "Actividad fuera de horario",
                    'description' => "Usuario {$activity['userid']} ha realizado {$activity['action_count']} acciones fuera del horario laboral",
                    'details' => $activity,
                    'userid' => $activity['userid']
                ];
            }
        }

        // Guardar alertas en la base de datos
        foreach ($alerts as $alert) {
            $this->createSecurityAlert($alert);
        }

        return $alerts;
    }

    /**
     * Crear alerta de seguridad
     */
    public function createSecurityAlert(array $alert): int
    {
        $result = $this->db->execute(
            "INSERT INTO iser_security_alerts
             (alert_type, severity, title, description, details, userid, ip_address,
              source_component, status, timecreated, timemodified)
             VALUES
             (:type, :severity, :title, :description, :details, :userid, :ip,
              :component, 'new', :time, :time)",
            [
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'title' => $alert['title'],
                'description' => $alert['description'],
                'details' => json_encode($alert['details'] ?? []),
                'userid' => $alert['userid'] ?? null,
                'ip' => $alert['ip_address'] ?? null,
                'component' => $alert['source_component'] ?? 'system',
                'time' => time()
            ]
        );

        return $this->db->getLastInsertId();
    }

    /**
     * Obtener alertas de seguridad
     */
    public function getSecurityAlerts(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $where[] = 'severity >= :severity';
            $params['severity'] = $filters['severity'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'alert_type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'timecreated >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_security_alerts {$whereClause}",
            $params
        )[0]['count'] ?? 0;

        $alerts = $this->db->query(
            "SELECT * FROM iser_security_alerts
             {$whereClause}
             ORDER BY severity DESC, timecreated DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [
                'limit' => $perPage,
                'offset' => $offset
            ])
        );

        // Decodificar details JSON
        foreach ($alerts as &$alert) {
            if ($alert['details']) {
                $alert['details'] = json_decode($alert['details'], true);
            }
        }

        return [
            'alerts' => $alerts,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Actualizar estado de alerta
     */
    public function updateAlertStatus(int $alertId, string $status, ?string $notes = null, ?int $resolvedBy = null): bool
    {
        $validStatuses = ['new', 'investigating', 'resolved', 'false_positive'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Estado inválido: {$status}");
        }

        return $this->db->execute(
            "UPDATE iser_security_alerts
             SET status = :status,
                 resolution_notes = :notes,
                 resolved_by = :resolved_by,
                 timemodified = :time
             WHERE id = :id",
            [
                'status' => $status,
                'notes' => $notes,
                'resolved_by' => $resolvedBy,
                'time' => time(),
                'id' => $alertId
            ]
        ) > 0;
    }

    /**
     * Obtener sesiones activas
     */
    public function getActiveSessions(): array
    {
        $timeout = time() - 3600; // 1 hora de inactividad

        return $this->db->query(
            "SELECT s.*, u.username, u.email
             FROM iser_user_sessions s
             LEFT JOIN iser_users u ON s.userid = u.id
             WHERE s.last_activity > :timeout
             ORDER BY s.last_activity DESC",
            ['timeout' => $timeout]
        );
    }

    /**
     * Registrar o actualizar sesión activa
     */
    public function updateUserSession(int $userId, string $sessionId): void
    {
        $this->db->execute(
            "INSERT INTO iser_user_sessions
             (userid, session_id, ip_address, user_agent, last_activity, timecreated)
             VALUES (:userid, :session, :ip, :ua, :time, :time)
             ON DUPLICATE KEY UPDATE
             last_activity = :time,
             ip_address = :ip,
             user_agent = :ua",
            [
                'userid' => $userId,
                'session' => $sessionId,
                'ip' => $this->getClientIp(),
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'time' => time()
            ]
        );
    }

    /**
     * Eliminar sesión
     */
    public function removeUserSession(string $sessionId): bool
    {
        return $this->db->execute(
            "DELETE FROM iser_user_sessions WHERE session_id = :session",
            ['session' => $sessionId]
        ) > 0;
    }

    /**
     * Limpiar sesiones inactivas
     */
    public function cleanupInactiveSessions(): int
    {
        $timeout = time() - 86400; // 24 horas

        return $this->db->execute(
            "DELETE FROM iser_user_sessions WHERE last_activity < :timeout",
            ['timeout' => $timeout]
        );
    }

    /**
     * Registrar auditoría de acceso a datos sensibles
     */
    public function auditDataAccess(int $userId, string $action, string $resourceType, int $resourceId, ?array $oldValue = null, ?array $newValue = null): void
    {
        $this->db->execute(
            "INSERT INTO iser_audit_trail
             (userid, action, resource_type, resource_id, old_value, new_value, ip_address, timecreated)
             VALUES (:userid, :action, :type, :id, :old, :new, :ip, :time)",
            [
                'userid' => $userId,
                'action' => $action,
                'type' => $resourceType,
                'id' => $resourceId,
                'old' => $oldValue ? json_encode($oldValue) : null,
                'new' => $newValue ? json_encode($newValue) : null,
                'ip' => $this->getClientIp(),
                'time' => time()
            ]
        );
    }

    /**
     * Obtener pista de auditoría
     */
    public function getAuditTrail(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['userid'])) {
            $where[] = 'userid = :userid';
            $params['userid'] = $filters['userid'];
        }

        if (!empty($filters['resource_type'])) {
            $where[] = 'resource_type = :type';
            $params['type'] = $filters['resource_type'];
        }

        if (!empty($filters['resource_id'])) {
            $where[] = 'resource_id = :id';
            $params['id'] = $filters['resource_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'timecreated >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_audit_trail {$whereClause}",
            $params
        )[0]['count'] ?? 0;

        $audit = $this->db->query(
            "SELECT a.*, u.username, u.email
             FROM iser_audit_trail a
             LEFT JOIN iser_users u ON a.userid = u.id
             {$whereClause}
             ORDER BY a.timecreated DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [
                'limit' => $perPage,
                'offset' => $offset
            ])
        );

        // Decodificar JSON
        foreach ($audit as &$record) {
            if ($record['old_value']) {
                $record['old_value'] = json_decode($record['old_value'], true);
            }
            if ($record['new_value']) {
                $record['new_value'] = json_decode($record['new_value'], true);
            }
        }

        return [
            'audit' => $audit,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener resumen de actividad de usuario
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $cutoff = time() - ($days * 86400);

        $summary = [
            'total_actions' => 0,
            'by_component' => [],
            'by_crud' => [],
            'recent_activity' => [],
            'failed_logins' => 0,
            'security_alerts' => 0
        ];

        // Total de acciones
        $total = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_logs
             WHERE userid = :userid AND timecreated > :cutoff",
            ['userid' => $userId, 'cutoff' => $cutoff]
        );
        $summary['total_actions'] = $total[0]['count'] ?? 0;

        // Por componente
        $summary['by_component'] = $this->db->query(
            "SELECT component, COUNT(*) as count FROM iser_logs
             WHERE userid = :userid AND timecreated > :cutoff
             GROUP BY component
             ORDER BY count DESC",
            ['userid' => $userId, 'cutoff' => $cutoff]
        );

        // Por operación CRUD
        $summary['by_crud'] = $this->db->query(
            "SELECT crud, COUNT(*) as count FROM iser_logs
             WHERE userid = :userid AND timecreated > :cutoff
             GROUP BY crud",
            ['userid' => $userId, 'cutoff' => $cutoff]
        );

        // Actividad reciente
        $summary['recent_activity'] = $this->db->query(
            "SELECT * FROM iser_logs
             WHERE userid = :userid
             ORDER BY timecreated DESC
             LIMIT 20",
            ['userid' => $userId]
        );

        // Intentos de login fallidos
        // ACTUALIZADO: Usa schema normalizado con user_id y attempted_at
        $failed = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_login_attempts
             WHERE user_id = :user_id AND success = 0 AND attempted_at > :cutoff",
            ['user_id' => $userId, 'cutoff' => $cutoff]
        );
        $summary['failed_logins'] = $failed[0]['count'] ?? 0;

        // Alertas de seguridad
        $alerts = $this->db->query(
            "SELECT COUNT(*) as count FROM iser_security_alerts
             WHERE userid = :userid AND timecreated > :cutoff",
            ['userid' => $userId, 'cutoff' => $cutoff]
        );
        $summary['security_alerts'] = $alerts[0]['count'] ?? 0;

        return $summary;
    }

    /**
     * Obtener configuración de reportes
     * ACTUALIZADO: Usa tabla config consolidada con category='reports'
     */
    public function getReportConfig(string $configKey): mixed
    {
        $result = $this->db->query(
            "SELECT config_value, config_type FROM iser_config
             WHERE config_key = :key AND category = 'reports'",
            ['key' => $configKey]
        );

        if (empty($result)) {
            return null;
        }

        $value = $result[0]['config_value'];
        $type = $result[0]['config_type'];

        return match($type) {
            'int' => (int)$value,
            'bool' => (bool)$value,
            'json' => json_decode($value, true),
            default => $value
        };
    }

    /**
     * Actualizar configuración de reportes
     * ACTUALIZADO: Usa tabla config consolidada con category='reports'
     */
    public function updateReportConfig(string $configKey, mixed $value, string $type = 'string'): bool
    {
        if ($type === 'json') {
            $value = json_encode($value);
        } elseif ($type === 'bool') {
            $value = $value ? '1' : '0';
        } else {
            $value = (string)$value;
        }

        $now = time();

        return $this->db->execute(
            "UPDATE iser_config
             SET config_value = :value,
                 config_type = :type,
                 updated_at = :time
             WHERE config_key = :key AND category = 'reports'",
            [
                'value' => $value,
                'type' => $type,
                'time' => $now,
                'key' => $configKey
            ]
        ) > 0;
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
