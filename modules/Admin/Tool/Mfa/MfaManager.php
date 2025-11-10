<?php
/**
 * ISER MFA System - MFA Manager
 *
 * @package    ISER\Modules\Admin\Tool\Mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Modules\Admin\Tool\Mfa;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;
use ISER\Modules\Admin\Tool\Mfa\Factors\MfaFactorInterface;

class MfaManager
{
    private Database $db;
    private array $factors = [];
    private bool $enabled;

    public function __construct(Database $db, bool $enabled = false)
    {
        $this->db = $db;
        $this->enabled = $enabled;
    }

    /**
     * Register an MFA factor
     */
    public function registerFactor(MfaFactorInterface $factor): void
    {
        $this->factors[$factor->getName()] = $factor;
    }

    /**
     * Get available MFA factors
     */
    public function getAvailableFactors(): array
    {
        $sql = "SELECT * FROM {$this->db->table('mfa_factors')}
                WHERE enabled = 1
                ORDER BY sortorder ASC";

        return $this->db->getConnection()->fetchAll($sql);
    }

    /**
     * Get factors configured for user
     */
    public function getUserFactors(int $userId): array
    {
        $sql = "SELECT uc.*, f.displayname, f.description
                FROM {$this->db->table('mfa_user_config')} uc
                JOIN {$this->db->table('mfa_factors')} f ON uc.factor = f.name
                WHERE uc.userid = :userid AND uc.enabled = 1 AND f.enabled = 1
                ORDER BY f.sortorder ASC";

        return $this->db->getConnection()->fetchAll($sql, [':userid' => $userId]);
    }

    /**
     * Check if MFA is required for user
     */
    public function isMfaRequired(int $userId): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check if user has admin role and MFA is required for admins
        if (defined('MFA_REQUIRED_FOR_ADMIN') && MFA_REQUIRED_FOR_ADMIN) {
            // This would check user roles - simplified for now
            return true;
        }

        // Check role-based policies
        $sql = "SELECT COUNT(*) as count
                FROM {$this->db->table('mfa_policies')} p
                JOIN {$this->db->table('role_assignments')} ra ON p.roleid = ra.roleid
                WHERE ra.userid = :userid
                AND p.requirement = 1
                AND (ra.timestart = 0 OR ra.timestart <= :now1)
                AND (ra.timeend = 0 OR ra.timeend >= :now2)";

        $now = time();
        $result = $this->db->getConnection()->fetchOne($sql, [
            ':userid' => $userId,
            ':now1' => $now,
            ':now2' => $now
        ]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Check if user has MFA configured
     */
    public function userHasMfa(int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_user_config')}
                WHERE userid = :userid AND enabled = 1";

        $result = $this->db->getConnection()->fetchOne($sql, [':userid' => $userId]);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Validate MFA code for user
     */
    public function validateMfa(int $userId, string $factorName, string $code): bool
    {
        if (!isset($this->factors[$factorName])) {
            $this->logAudit($userId, $factorName, 'verify', false, 'Factor not registered');
            return false;
        }

        $factor = $this->factors[$factorName];

        if (!$factor->isConfigured($userId)) {
            $this->logAudit($userId, $factorName, 'verify', false, 'Factor not configured');
            return false;
        }

        $result = $factor->verify($userId, $code);

        $this->logAudit($userId, $factorName, 'verify', $result,
            $result ? 'Verification successful' : 'Invalid code');

        return $result;
    }

    /**
     * Get factor instance
     */
    public function getFactor(string $factorName): ?MfaFactorInterface
    {
        return $this->factors[$factorName] ?? null;
    }

    /**
     * Enable MFA system
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable MFA system
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if MFA is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Log MFA audit event
     */
    public function logAudit(int $userId, string $factor, string $action, bool $success, string $details = ''): void
    {
        $this->db->insert('mfa_audit', [
            'userid' => $userId,
            'factor' => $factor,
            'action' => $action,
            'success' => $success ? 1 : 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details,
            'timecreated' => time()
        ]);

        Logger::security("MFA {$action}", [
            'userid' => $userId,
            'factor' => $factor,
            'success' => $success,
            'details' => $details
        ]);
    }

    /**
     * Get MFA statistics for user
     */
    public function getUserStats(int $userId): array
    {
        $factors = $this->getUserFactors($userId);

        $sql = "SELECT COUNT(*) as total, SUM(success) as successful
                FROM {$this->db->table('mfa_audit')}
                WHERE userid = :userid
                AND action = 'verify'
                AND timecreated > :since";

        $stats = $this->db->getConnection()->fetchOne($sql, [
            ':userid' => $userId,
            ':since' => time() - (30 * 86400) // Last 30 days
        ]);

        return [
            'configured_factors' => count($factors),
            'total_verifications' => (int)($stats['total'] ?? 0),
            'successful_verifications' => (int)($stats['successful'] ?? 0),
            'factors' => $factors
        ];
    }

    /**
     * Revoke all MFA for user
     */
    public function revokeAllFactors(int $userId): bool
    {
        $factors = $this->getUserFactors($userId);

        foreach ($factors as $factorConfig) {
            $factor = $this->getFactor($factorConfig['factor']);
            if ($factor) {
                $factor->revoke($userId);
            }
        }

        $this->logAudit($userId, 'all', 'revoke', true, 'All factors revoked');
        return true;
    }

    /**
     * Get MFA grace period end date for user
     */
    public function getGracePeriodEnd(int $userId): int
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);
        if (!$user) return 0;

        $gracePeriod = (int)(getenv('MFA_GRACE_PERIOD') ?: 7);
        return $user['timecreated'] + ($gracePeriod * 86400);
    }

    /**
     * Check if user is in grace period
     */
    public function isInGracePeriod(int $userId): bool
    {
        return time() < $this->getGracePeriodEnd($userId);
    }
}
