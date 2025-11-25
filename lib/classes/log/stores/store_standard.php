<?php
namespace core\log\stores;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\log\store_interface;
use core\log\sql_reader;

/**
 * Standard Log Store
 *
 * Stores log data in the logstore_standard_log database table.
 * This is the primary log store for NexoSupport.
 *
 * @package    core\log
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class store_standard implements store_interface, sql_reader {

    /** @var string Table name */
    const TABLE = 'logstore_standard_log';

    /**
     * Write a log record
     *
     * @param array $record Log record data
     * @return bool Success
     */
    public function write(array $record): bool {
        global $DB;

        if ($DB === null) {
            return false;
        }

        try {
            // Ensure required fields
            $record = array_merge([
                'eventname' => '',
                'component' => 'core',
                'action' => 'unknown',
                'target' => 'system',
                'objecttable' => null,
                'objectid' => null,
                'crud' => 'r',
                'edulevel' => 0,
                'contextid' => 1,
                'contextlevel' => CONTEXT_SYSTEM,
                'contextinstanceid' => 0,
                'userid' => 0,
                'relateduserid' => null,
                'anonymous' => 0,
                'other' => null,
                'timecreated' => time(),
                'origin' => 'web',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'realuserid' => null,
            ], $record);

            // Encode 'other' if it's an array
            if (is_array($record['other'])) {
                $record['other'] = json_encode($record['other']);
            }

            // Insert record
            $DB->insert_record(self::TABLE, (object)$record);

            return true;

        } catch (\Exception $e) {
            debugging('Log write failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get store name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('pluginname', 'logstore_standard');
    }

    /**
     * Get store description
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('pluginname_desc', 'logstore_standard');
    }

    /**
     * Check if store is available
     *
     * @return bool
     */
    public function is_available(): bool {
        global $DB;
        return $DB !== null;
    }

    /**
     * Get log records with filtering
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
    ): array {
        global $DB;

        if ($DB === null) {
            return [];
        }

        $sql = "SELECT * FROM {" . self::TABLE . "}";

        if (!empty($selectwhere)) {
            $sql .= " WHERE " . $selectwhere;
        }

        if (!empty($sort)) {
            $sql .= " ORDER BY " . $sort;
        }

        try {
            return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            debugging('Log query failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

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
    ): int {
        global $DB;

        if ($DB === null) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM {" . self::TABLE . "}";

        if (!empty($selectwhere)) {
            $sql .= " WHERE " . $selectwhere;
        }

        try {
            return (int)$DB->count_records_sql($sql, $params);
        } catch (\Exception $e) {
            debugging('Log count failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Get a single log record by ID
     *
     * @param int $id Record ID
     * @return object|null Log record or null
     */
    public function get_event_by_id(int $id): ?object {
        global $DB;

        if ($DB === null) {
            return null;
        }

        try {
            $record = $DB->get_record(self::TABLE, ['id' => $id]);
            return $record ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get internal log reader table name
     *
     * @return string Table name
     */
    public function get_internal_log_table_name(): string {
        return self::TABLE;
    }

    /**
     * Get events for a specific user
     *
     * @param int $userid User ID
     * @param int $limitfrom Start offset
     * @param int $limitnum Number of records
     * @return array Log records
     */
    public function get_events_for_user(int $userid, int $limitfrom = 0, int $limitnum = 100): array {
        return $this->get_events_select(
            'userid = :userid',
            ['userid' => $userid],
            'timecreated DESC',
            $limitfrom,
            $limitnum
        );
    }

    /**
     * Get events by component
     *
     * @param string $component Component name
     * @param int $limitfrom Start offset
     * @param int $limitnum Number of records
     * @return array Log records
     */
    public function get_events_by_component(string $component, int $limitfrom = 0, int $limitnum = 100): array {
        return $this->get_events_select(
            'component = :component',
            ['component' => $component],
            'timecreated DESC',
            $limitfrom,
            $limitnum
        );
    }

    /**
     * Get events by action
     *
     * @param string $action Action name
     * @param int $limitfrom Start offset
     * @param int $limitnum Number of records
     * @return array Log records
     */
    public function get_events_by_action(string $action, int $limitfrom = 0, int $limitnum = 100): array {
        return $this->get_events_select(
            'action = :action',
            ['action' => $action],
            'timecreated DESC',
            $limitfrom,
            $limitnum
        );
    }

    /**
     * Get recent events
     *
     * @param int $since Timestamp to get events since
     * @param int $limitnum Maximum records
     * @return array Log records
     */
    public function get_recent_events(int $since = 0, int $limitnum = 100): array {
        if ($since === 0) {
            $since = time() - 86400; // Last 24 hours by default
        }

        return $this->get_events_select(
            'timecreated > :since',
            ['since' => $since],
            'timecreated DESC',
            0,
            $limitnum
        );
    }

    /**
     * Purge old log records
     *
     * @param int $before Delete records older than this timestamp
     * @return int Number of deleted records
     */
    public function purge_old_logs(int $before): int {
        global $DB;

        if ($DB === null) {
            return 0;
        }

        try {
            $count = $this->get_events_select_count(
                'timecreated < :before',
                ['before' => $before]
            );

            $DB->delete_records_select(self::TABLE, 'timecreated < ?', [$before]);

            return $count;

        } catch (\Exception $e) {
            debugging('Log purge failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Get log statistics
     *
     * @return array Statistics
     */
    public function get_statistics(): array {
        global $DB;

        if ($DB === null) {
            return [];
        }

        try {
            $total = $DB->count_records(self::TABLE);

            $today = $DB->count_records_select(
                self::TABLE,
                'timecreated > ?',
                [strtotime('today')]
            );

            $week = $DB->count_records_select(
                self::TABLE,
                'timecreated > ?',
                [strtotime('-7 days')]
            );

            // Get oldest and newest
            $oldest = $DB->get_record_sql(
                "SELECT MIN(timecreated) as oldest FROM {" . self::TABLE . "}"
            );
            $newest = $DB->get_record_sql(
                "SELECT MAX(timecreated) as newest FROM {" . self::TABLE . "}"
            );

            return [
                'total' => $total,
                'today' => $today,
                'week' => $week,
                'oldest' => $oldest->oldest ?? null,
                'newest' => $newest->newest ?? null,
            ];

        } catch (\Exception $e) {
            return [];
        }
    }
}
