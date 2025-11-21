<?php
/**
 * Plugin class for report_loglive.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_loglive;

defined('INTERNAL_ACCESS') || die();

/**
 * Live log report plugin class.
 *
 * This class extends the core report plugin base and provides
 * real-time log viewing with AJAX polling.
 */
class plugin extends \core\plugininfo\report {

    /**
     * Default refresh interval in seconds.
     */
    const DEFAULT_REFRESH_INTERVAL = 60;

    /**
     * Get the datasource for the live log report.
     *
     * @return object
     */
    public function get_datasource(): object {
        return (object) [
            'type' => 'database',
            'table' => 'logstore_standard_log',
            'description' => 'Standard log store table with real-time updates',
            'realtime' => true,
            'refresh_interval' => self::DEFAULT_REFRESH_INTERVAL,
        ];
    }

    /**
     * Get available columns for the live log report.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'timecreated' => [
                'title' => get_string('time', 'report_loglive'),
                'sortable' => false,
                'type' => 'datetime',
            ],
            'fullname' => [
                'title' => get_string('fullnameuser', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'relatedfullname' => [
                'title' => get_string('relateduser', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'eventcontext' => [
                'title' => get_string('eventcontext', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'component' => [
                'title' => get_string('component', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'eventname' => [
                'title' => get_string('eventname', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'description' => [
                'title' => get_string('description', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'origin' => [
                'title' => get_string('origin', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
            'ip' => [
                'title' => get_string('ip_address', 'report_loglive'),
                'sortable' => false,
                'type' => 'string',
            ],
        ];
    }

    /**
     * Get available filters for the live log report.
     *
     * Live logs have limited filtering to maintain real-time performance.
     *
     * @return array
     */
    public function get_filters(): array {
        return [
            'courseid' => [
                'title' => get_string('course'),
                'type' => 'select',
                'options_callback' => 'get_course_options',
            ],
            'since' => [
                'title' => get_string('since', 'report_loglive'),
                'type' => 'hidden',
                'description' => 'Timestamp for fetching logs since last poll',
            ],
        ];
    }

    /**
     * Execute the report with the given parameters.
     *
     * @param array $params Filter parameters including 'since' for polling
     * @return array Log entries
     */
    public function execute(array $params = []): array {
        global $DB;

        $where = [];
        $sqlparams = [];

        // Course filter.
        if (!empty($params['courseid']) && $params['courseid'] != SITEID) {
            $where[] = 'courseid = :courseid';
            $sqlparams['courseid'] = $params['courseid'];
        }

        // Since filter for polling.
        if (!empty($params['since'])) {
            $where[] = 'timecreated > :since';
            $sqlparams['since'] = $params['since'];
        }

        $wheresql = empty($where) ? '1=1' : implode(' AND ', $where);

        $perpage = $params['perpage'] ?? 100;

        $sql = "SELECT * FROM {logstore_standard_log}
                WHERE {$wheresql}
                ORDER BY timecreated DESC, id DESC";

        return $DB->get_records_sql($sql, $sqlparams, 0, $perpage);
    }

    /**
     * Get the latest log entry timestamp.
     *
     * @param int $courseid
     * @return int
     */
    public function get_latest_timestamp(int $courseid = 0): int {
        global $DB;

        $where = '1=1';
        $params = [];

        if ($courseid && $courseid != SITEID) {
            $where = 'courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        $sql = "SELECT MAX(timecreated) FROM {logstore_standard_log} WHERE {$where}";
        return (int) $DB->get_field_sql($sql, $params);
    }

    /**
     * Get export formats supported by this report.
     *
     * Live log doesn't support export due to its real-time nature.
     *
     * @return array
     */
    public function get_export_formats(): array {
        return [];
    }

    /**
     * Check if this report can export.
     *
     * @return bool
     */
    public function can_export(): bool {
        return false;
    }

    /**
     * Get the refresh interval in seconds.
     *
     * @return int
     */
    public function get_refresh_interval(): int {
        $config = $this->get_config();
        return $config['refresh_interval'] ?? self::DEFAULT_REFRESH_INTERVAL;
    }
}
