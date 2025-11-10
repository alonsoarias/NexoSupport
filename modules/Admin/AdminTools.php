<?php
/**
 * ISER - Admin Tools Manager
 *
 * Manages administrative tools and utilities.
 * Provides access to system maintenance and configuration tools.
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Modules\Admin;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

class AdminTools
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all available admin tools
     *
     * @return array Admin tools
     */
    public function getTools(): array
    {
        return [
            'installaddon' => [
                'name' => 'Instalar Addon',
                'description' => 'Instalar nuevos plugins o módulos en el sistema',
                'url' => '/admin/tool/installaddon/index.php',
                'icon' => 'bi-cloud-download',
                'enabled' => true,
            ],
            'uploaduser' => [
                'name' => 'Cargar Usuarios',
                'description' => 'Importar usuarios masivamente desde archivo CSV',
                'url' => '/admin/tool/uploaduser/index.php',
                'icon' => 'bi-file-earmark-spreadsheet',
                'enabled' => true,
            ],
            'clearcache' => [
                'name' => 'Limpiar Caché',
                'description' => 'Limpiar el caché del sistema',
                'url' => '/admin/tool/clearcache/index.php',
                'icon' => 'bi-trash',
                'enabled' => true,
            ],
            'dbmaintenance' => [
                'name' => 'Mantenimiento de Base de Datos',
                'description' => 'Optimizar y reparar tablas de base de datos',
                'url' => '/admin/tool/dbmaintenance/index.php',
                'icon' => 'bi-database-gear',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get tool by identifier
     *
     * @param string $toolId Tool identifier
     * @return array|null Tool data or null
     */
    public function getTool(string $toolId): ?array
    {
        $tools = $this->getTools();
        return $tools[$toolId] ?? null;
    }

    /**
     * Clear system cache
     *
     * @return bool True on success
     */
    public function clearCache(): bool
    {
        try {
            $sql = "DELETE FROM {$this->db->table('cache')} WHERE expires < :now";
            $this->db->getConnection()->execute($sql, [':now' => time()]);

            Logger::auth('System cache cleared');
            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to clear cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get database size
     *
     * @return array Database size statistics
     */
    public function getDatabaseSize(): array
    {
        try {
            $sql = "SELECT
                    SUM(data_length + index_length) / 1024 / 1024 AS size_mb,
                    COUNT(*) AS table_count
                    FROM information_schema.TABLES
                    WHERE table_schema = :database";

            $dbName = $this->db->getConnection()->fetchColumn("SELECT DATABASE()");
            $result = $this->db->getConnection()->fetchOne($sql, [':database' => $dbName]);

            return [
                'size_mb' => round((float)($result['size_mb'] ?? 0), 2),
                'table_count' => (int)($result['table_count'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['size_mb' => 0, 'table_count' => 0];
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $sql_total = "SELECT COUNT(*) as count FROM {$this->db->table('cache')}";
            $sql_expired = "SELECT COUNT(*) as count FROM {$this->db->table('cache')} WHERE expires < :now";

            return [
                'total' => (int)$this->db->getConnection()->fetchColumn($sql_total),
                'expired' => (int)$this->db->getConnection()->fetchColumn($sql_expired, [':now' => time()]),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'expired' => 0];
        }
    }
}
