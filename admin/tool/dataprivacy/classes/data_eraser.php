<?php
/**
 * NexoSupport - Data Eraser
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\DataPrivacy;

defined('NEXOSUPPORT_INTERNAL') || die();

use PDO;

/**
 * Data Eraser
 *
 * Deletes or anonymizes user data
 */
class DataEraser
{
    /** @var PDO Database connection */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Delete all user data
     *
     * @param int $user_id User ID
     * @param int $admin_id Admin performing deletion
     * @param string $type Deletion type (hard, soft, anonymize)
     * @return array Result
     */
    public function delete_user_data(int $user_id, int $admin_id, string $type = 'anonymize'): array
    {
        try {
            // Create snapshot before deletion
            $snapshot = $this->create_deletion_snapshot($user_id);

            $this->db->beginTransaction();

            if ($type === 'hard') {
                $this->hard_delete($user_id);
            } elseif ($type === 'soft') {
                $this->soft_delete($user_id);
            } else {
                $this->anonymize_user($user_id);
            }

            // Record deletion
            $stmt = $this->db->prepare("
                INSERT INTO dataprivacy_deleted_users
                (original_user_id, deletion_type, deleted_by, data_snapshot)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $type, $admin_id, json_encode($snapshot)]);

            $this->db->commit();

            return [
                'success' => true,
                'type' => $type,
                'message' => 'User data deleted successfully',
            ];

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Deletion error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    /**
     * Hard delete - permanently remove all data
     *
     * @param int $user_id User ID
     * @return void
     */
    private function hard_delete(int $user_id): void
    {
        $tables = ['users', 'user_roles', 'logs', 'mfa_email_codes', 'mfa_user_factors'];

        foreach ($tables as $table) {
            try {
                $stmt = $this->db->prepare("DELETE FROM $table WHERE user_id = ?");
                $stmt->execute([$user_id]);
            } catch (\PDOException $e) {
                // Table might not exist or have user_id column
                continue;
            }
        }
    }

    /**
     * Soft delete - mark as deleted but keep data
     *
     * @param int $user_id User ID
     * @return void
     */
    private function soft_delete(int $user_id): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET deleted_at = NOW(), status = 'deleted'
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
    }

    /**
     * Anonymize user - replace personal data with placeholders
     *
     * @param int $user_id User ID
     * @return void
     */
    public function anonymize_user(int $user_id): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET
                username = CONCAT('deleted_user_', id),
                email = CONCAT('deleted_', id, '@example.com'),
                first_name = 'Deleted',
                last_name = 'User',
                phone = NULL,
                address = NULL,
                status = 'anonymized'
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
    }

    /**
     * Create snapshot of user data before deletion
     *
     * @param int $user_id User ID
     * @return array Snapshot data
     */
    private function create_deletion_snapshot(int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Verify deletion completeness
     *
     * @param int $user_id User ID
     * @return array Verification result
     */
    public function verify_deletion(int $user_id): array
    {
        $remaining = [];

        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $remaining[] = 'User record still exists';
            }
        } catch (\PDOException $e) {
            // Ignore
        }

        return [
            'complete' => empty($remaining),
            'remaining' => $remaining,
        ];
    }
}
