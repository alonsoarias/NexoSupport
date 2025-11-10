<?php
/**
 * ISER MFA System - User Configuration
 *
 * @package    ISER\Modules\Admin\Tool\Mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\Tool\Mfa;

use ISER\Core\Database\Database;

class MfaUserConfig
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get user MFA configuration
     */
    public function getUserConfig(int $userId, string $factor): array|false
    {
        return $this->db->selectOne('mfa_user_config', [
            'userid' => $userId,
            'factor' => $factor
        ]);
    }

    /**
     * Set user MFA configuration
     */
    public function setUserConfig(int $userId, string $factor, array $config): bool
    {
        $existing = $this->getUserConfig($userId, $factor);
        $now = time();

        $data = [
            'userid' => $userId,
            'factor' => $factor,
            'secret' => $config['secret'] ?? null,
            'config' => isset($config['data']) ? json_encode($config['data']) : null,
            'enabled' => $config['enabled'] ?? 1,
            'timemodified' => $now
        ];

        if ($existing) {
            return $this->db->update('mfa_user_config', $data, ['id' => $existing['id']]) > 0;
        } else {
            $data['timecreated'] = $now;
            return $this->db->insert('mfa_user_config', $data) !== false;
        }
    }

    /**
     * Get enabled factors for user
     */
    public function getEnabledFactors(int $userId): array
    {
        $sql = "SELECT uc.*, f.displayname
                FROM {$this->db->table('mfa_user_config')} uc
                JOIN {$this->db->table('mfa_factors')} f ON uc.factor = f.name
                WHERE uc.userid = :userid AND uc.enabled = 1 AND f.enabled = 1
                ORDER BY f.sortorder ASC";

        return $this->db->getConnection()->fetchAll($sql, [':userid' => $userId]);
    }

    /**
     * Enable factor for user
     */
    public function enableFactor(int $userId, string $factor): bool
    {
        $config = $this->getUserConfig($userId, $factor);
        if (!$config) return false;

        return $this->db->update('mfa_user_config', [
            'enabled' => 1,
            'timemodified' => time()
        ], ['id' => $config['id']]) > 0;
    }

    /**
     * Disable factor for user
     */
    public function disableFactor(int $userId, string $factor): bool
    {
        $config = $this->getUserConfig($userId, $factor);
        if (!$config) return false;

        return $this->db->update('mfa_user_config', [
            'enabled' => 0,
            'timemodified' => time()
        ], ['id' => $config['id']]) > 0;
    }

    /**
     * Remove factor configuration
     */
    public function removeFactor(int $userId, string $factor): bool
    {
        return $this->db->delete('mfa_user_config', [
            'userid' => $userId,
            'factor' => $factor
        ]) > 0;
    }

    /**
     * Check if factor is configured
     */
    public function isFactorConfigured(int $userId, string $factor): bool
    {
        $config = $this->getUserConfig($userId, $factor);
        return $config && $config['enabled'] == 1;
    }

    /**
     * Get user's primary factor
     */
    public function getPrimaryFactor(int $userId): ?string
    {
        $factors = $this->getEnabledFactors($userId);
        return !empty($factors) ? $factors[0]['factor'] : null;
    }

    /**
     * Count enabled factors for user
     */
    public function countEnabledFactors(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_user_config')}
                WHERE userid = :userid AND enabled = 1";

        $result = $this->db->getConnection()->fetchOne($sql, [':userid' => $userId]);
        return (int)($result['count'] ?? 0);
    }
}
