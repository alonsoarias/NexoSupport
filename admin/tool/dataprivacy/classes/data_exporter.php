<?php
/**
 * NexoSupport - Data Exporter
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\DataPrivacy;

defined('NEXOSUPPORT_INTERNAL') || die();

use PDO;

/**
 * Data Exporter
 *
 * Exports user data for GDPR compliance
 */
class DataExporter
{
    /** @var PDO Database connection */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Export all user data
     *
     * @param int $user_id User ID
     * @param string $format Format (json, xml)
     * @return array Result with file path
     */
    public function export_user_data(int $user_id, string $format = 'json'): array
    {
        $data = [
            'user_info' => $this->collect_user_info($user_id),
            'activity' => $this->collect_user_activity($user_id),
            'exported_at' => date('Y-m-d H:i:s'),
        ];

        $filename = "user_{$user_id}_export_" . time() . ".$format";
        $filepath = sys_get_temp_dir() . '/' . $filename;

        if ($format === 'json') {
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        } elseif ($format === 'xml') {
            file_put_contents($filepath, $this->array_to_xml($data));
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
        ];
    }

    /**
     * Collect user information
     *
     * @param int $user_id User ID
     * @return array User data
     */
    private function collect_user_info(int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Collect user activity
     *
     * @param int $user_id User ID
     * @return array Activity logs
     */
    private function collect_user_activity(int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM logs
                WHERE user_id = ?
                ORDER BY timestamp DESC
                LIMIT 1000
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Convert array to XML
     *
     * @param array $data Data array
     * @return string XML string
     */
    private function array_to_xml(array $data): string
    {
        $xml = new \SimpleXMLElement('<user_export/>');
        array_walk_recursive($data, function ($value, $key) use ($xml) {
            $xml->addChild($key, htmlspecialchars($value));
        });
        return $xml->asXML();
    }
}
