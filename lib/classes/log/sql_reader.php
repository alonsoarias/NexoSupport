<?php
namespace core\log;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * SQL Log Reader Interface
 *
 * Defines the contract for log stores that support SQL-based reading.
 * This is implemented by stores that allow querying log data.
 *
 * @package    core\log
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
interface sql_reader {

    /**
     * Get log records
     *
     * @param string $selectwhere SQL WHERE clause
     * @param array $params Query parameters
     * @param string $sort Sort order
     * @param int $limitfrom Start offset
     * @param int $limitnum Number of records
     * @return array Log records
     */
    public function get_events_select(
        string $selectwhere,
        array $params = [],
        string $sort = 'timecreated DESC',
        int $limitfrom = 0,
        int $limitnum = 0
    ): array;

    /**
     * Count log records
     *
     * @param string $selectwhere SQL WHERE clause
     * @param array $params Query parameters
     * @return int Record count
     */
    public function get_events_select_count(
        string $selectwhere,
        array $params = []
    ): int;

    /**
     * Get a single log record by ID
     *
     * @param int $id Record ID
     * @return object|null Log record or null
     */
    public function get_event_by_id(int $id): ?object;

    /**
     * Get internal log reader table name
     *
     * @return string Table name
     */
    public function get_internal_log_table_name(): string;
}
