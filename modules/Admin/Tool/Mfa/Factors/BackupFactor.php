<?php
/**
 * ISER MFA System - Backup Codes Factor
 *
 * @package    ISER\Modules\Admin\Tool\Mfa\Factors
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Admin\Tool\Mfa\Factors;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;

class BackupFactor implements MfaFactorInterface
{
    private Database $db;
    private int $codeCount;
    private int $codeLength;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->codeCount = (int)(getenv('MFA_BACKUP_CODES_COUNT') ?: 10);
        $this->codeLength = (int)(getenv('MFA_BACKUP_CODES_LENGTH') ?: 8);
    }

    public function getName(): string
    {
        return 'backup';
    }

    public function getDisplayName(): string
    {
        return 'Códigos de Respaldo';
    }

    public function getDescription(): string
    {
        return 'Códigos de un solo uso para emergencias cuando no puedas acceder a tus otros métodos de autenticación';
    }

    public function isConfigured(int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_backup_codes')}
                WHERE userid = :userid AND used = 0";

        $result = $this->db->getConnection()->fetchOne($sql, [':userid' => $userId]);
        return ($result['count'] ?? 0) > 0;
    }

    public function setup(int $userId, array $data = []): array
    {
        // Delete existing codes
        $this->revoke($userId);

        // Generate new codes
        $codes = $this->generateCodes();
        $now = time();

        foreach ($codes as $code) {
            $this->db->insert('mfa_backup_codes', [
                'userid' => $userId,
                'code_hash' => Helpers::hashPassword($code),
                'used' => 0,
                'used_at' => null,
                'timecreated' => $now
            ]);
        }

        // Update user config
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        $userConfig->setUserConfig($userId, $this->getName(), [
            'enabled' => 1,
            'data' => ['generated_at' => $now]
        ]);

        return [
            'success' => true,
            'codes' => $codes,
            'count' => count($codes)
        ];
    }

    public function verify(int $userId, string $code): bool
    {
        // Clean code (remove spaces, dashes)
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));

        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        // Get all unused codes for user
        $sql = "SELECT * FROM {$this->db->table('mfa_backup_codes')}
                WHERE userid = :userid AND used = 0";

        $codes = $this->db->getConnection()->fetchAll($sql, [':userid' => $userId]);

        foreach ($codes as $storedCode) {
            if (password_verify($code, $storedCode['code_hash'])) {
                // Mark code as used
                $this->db->update('mfa_backup_codes', [
                    'used' => 1,
                    'used_at' => time()
                ], ['id' => $storedCode['id']]);

                return true;
            }
        }

        return false;
    }

    public function getSetupTemplate(): string
    {
        return 'factors/backup_setup';
    }

    public function getVerifyTemplate(): string
    {
        return 'factors/backup_verify';
    }

    public function getSetupData(int $userId): array
    {
        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'code_count' => $this->codeCount,
            'code_length' => $this->codeLength
        ];
    }

    public function getVerifyData(int $userId): array
    {
        $remaining = $this->getRemainingCodesCount($userId);

        return [
            'factor_name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'remaining_codes' => $remaining,
            'code_length' => $this->codeLength
        ];
    }

    public function revoke(int $userId): bool
    {
        // Delete all backup codes for user
        $this->db->delete('mfa_backup_codes', ['userid' => $userId]);

        // Remove user config
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        return $userConfig->removeFactor($userId, $this->getName());
    }

    public function getConfig(int $userId): array|false
    {
        $userConfig = new \ISER\Modules\Admin\Tool\Mfa\MfaUserConfig($this->db);
        return $userConfig->getUserConfig($userId, $this->getName());
    }

    public function canBePrimary(): bool
    {
        return false; // Backup codes should not be primary
    }

    public function getSortOrder(): int
    {
        return 999; // Lowest priority
    }

    /**
     * Generate backup codes
     */
    private function generateCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < $this->codeCount; $i++) {
            $codes[] = $this->generateCode();
        }

        return $codes;
    }

    /**
     * Generate a single backup code
     */
    private function generateCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluding similar chars
        $code = '';

        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Format as XXXX-XXXX for 8 char codes
        if ($this->codeLength === 8) {
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }

        return $code;
    }

    /**
     * Get count of remaining unused codes
     */
    public function getRemainingCodesCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_backup_codes')}
                WHERE userid = :userid AND used = 0";

        $result = $this->db->getConnection()->fetchOne($sql, [':userid' => $userId]);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(int $userId): array
    {
        $sqlTotal = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_backup_codes')}
                     WHERE userid = :userid";
        $sqlUsed = "SELECT COUNT(*) as count FROM {$this->db->table('mfa_backup_codes')}
                    WHERE userid = :userid AND used = 1";

        $total = $this->db->getConnection()->fetchOne($sqlTotal, [':userid' => $userId]);
        $used = $this->db->getConnection()->fetchOne($sqlUsed, [':userid' => $userId]);

        return [
            'total' => (int)($total['count'] ?? 0),
            'used' => (int)($used['count'] ?? 0),
            'remaining' => (int)($total['count'] ?? 0) - (int)($used['count'] ?? 0)
        ];
    }
}
