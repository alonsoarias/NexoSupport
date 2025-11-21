<?php
/**
 * Plugin class for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace report_log;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Log report plugin class.
 *
 * This class extends the core report plugin base and provides
 * access to historical log entries with filtering capabilities.
 */
class plugin extends \core\plugininfo\report {

    /**
     * Get the datasource for the log report.
     *
     * @return object
     */
    public function get_datasource(): object {
        return (object) [
            'type' => 'database',
            'table' => 'logstore_standard_log',
            'description' => 'Standard log store table',
        ];
    }

    /**
     * Get available columns for the log report.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'timecreated' => [
                'title' => get_string('time', 'report_log'),
                'sortable' => true,
                'type' => 'datetime',
            ],
            'fullname' => [
                'title' => get_string('fullnameuser', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
            'relatedfullname' => [
                'title' => get_string('relateduser', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
            'eventcontext' => [
                'title' => get_string('eventcontext', 'report_log'),
                'sortable' => false,
                'type' => 'string',
            ],
            'component' => [
                'title' => get_string('component', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
            'eventname' => [
                'title' => get_string('eventname', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
            'description' => [
                'title' => get_string('description', 'report_log'),
                'sortable' => false,
                'type' => 'string',
            ],
            'origin' => [
                'title' => get_string('origin', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
            'ip' => [
                'title' => get_string('ip_address', 'report_log'),
                'sortable' => true,
                'type' => 'string',
            ],
        ];
    }

    /**
     * Get available filters for the log report.
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
            'userid' => [
                'title' => get_string('user'),
                'type' => 'autocomplete',
                'ajax' => '/report/log/ajax/users.php',
            ],
            'date' => [
                'title' => get_string('date'),
                'type' => 'date',
            ],
            'edulevel' => [
                'title' => get_string('edulevel', 'report_log'),
                'type' => 'select',
                'options' => [
                    -1 => get_string('all'),
                    0 => get_string('other', 'report_log'),
                    1 => get_string('participating', 'report_log'),
                    2 => get_string('teaching', 'report_log'),
                ],
            ],
            'origin' => [
                'title' => get_string('origin', 'report_log'),
                'type' => 'select',
                'options' => [
                    '' => get_string('all'),
                    'cli' => 'cli',
                    'restore' => 'restore',
                    'web' => 'web',
                    'ws' => 'web service',
                ],
            ],
            'component' => [
                'title' => get_string('component', 'report_log'),
                'type' => 'text',
            ],
            'action' => [
                'title' => get_string('action', 'report_log'),
                'type' => 'text',
            ],
        ];
    }

    /**
     * Execute the report with the given parameters.
     *
     * @param array $params Filter parameters
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

        // User filter.
        if (!empty($params['userid'])) {
            $where[] = 'userid = :userid';
            $sqlparams['userid'] = $params['userid'];
        }

        // Date filter.
        if (!empty($params['date'])) {
            $where[] = 'timecreated >= :datefrom';
            $where[] = 'timecreated < :dateto';
            $sqlparams['datefrom'] = $params['date'];
            $sqlparams['dateto'] = $params['date'] + DAYSECS;
        }

        // Edulevel filter.
        if (isset($params['edulevel']) && $params['edulevel'] >= 0) {
            $where[] = 'edulevel = :edulevel';
            $sqlparams['edulevel'] = $params['edulevel'];
        }

        // Origin filter.
        if (!empty($params['origin'])) {
            $where[] = 'origin = :origin';
            $sqlparams['origin'] = $params['origin'];
        }

        $wheresql = empty($where) ? '1=1' : implode(' AND ', $where);

        $page = $params['page'] ?? 0;
        $perpage = $params['perpage'] ?? 100;

        $sql = "SELECT * FROM {logstore_standard_log}
                WHERE {$wheresql}
                ORDER BY timecreated DESC, id DESC";

        return $DB->get_records_sql($sql, $sqlparams, $page * $perpage, $perpage);
    }

    /**
     * Get export formats supported by this report.
     *
     * @return array
     */
    public function get_export_formats(): array {
        return ['csv', 'excel', 'ods'];
    }

    /**
     * Get aggregations available for this report.
     *
     * @return array
     */
    public function get_aggregations(): array {
        return [
            'count_by_user' => [
                'title' => get_string('logsbyuser', 'report_log'),
                'groupby' => 'userid',
                'aggregate' => 'COUNT(*)',
            ],
            'count_by_day' => [
                'title' => get_string('logsbyday', 'report_log'),
                'groupby' => 'DATE(FROM_UNIXTIME(timecreated))',
                'aggregate' => 'COUNT(*)',
            ],
            'count_by_action' => [
                'title' => get_string('logsbyaction', 'report_log'),
                'groupby' => 'action',
                'aggregate' => 'COUNT(*)',
            ],
        ];
    }
}
