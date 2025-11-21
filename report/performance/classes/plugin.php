<?php
/**
 * Plugin class for report_performance.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace report_performance;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Performance report plugin class.
 *
 * This class extends the core report plugin base and provides
 * performance-related checks and their status.
 */
class plugin extends \core\plugininfo\report {

    /**
     * Get the datasource for the performance report.
     *
     * The performance report uses the check system as its datasource.
     *
     * @return object
     */
    public function get_datasource(): object {
        return (object) [
            'type' => 'check',
            'component' => 'performance',
            'table' => null,
            'description' => 'Performance checks from core\\check\\manager',
        ];
    }

    /**
     * Get available columns for the performance report.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'check_name' => [
                'title' => get_string('check', 'report_performance'),
                'sortable' => true,
                'type' => 'string',
            ],
            'status' => [
                'title' => get_string('status', 'report_performance'),
                'sortable' => true,
                'type' => 'status',
            ],
            'summary' => [
                'title' => get_string('summary', 'report_performance'),
                'sortable' => false,
                'type' => 'string',
            ],
            'details' => [
                'title' => get_string('details', 'report_performance'),
                'sortable' => false,
                'type' => 'link',
            ],
        ];
    }

    /**
     * Get available filters for the performance report.
     *
     * @return array
     */
    public function get_filters(): array {
        return [
            'status' => [
                'title' => get_string('status', 'report_performance'),
                'type' => 'select',
                'options' => [
                    '' => get_string('all'),
                    'ok' => get_string('statusok', 'report_performance'),
                    'info' => get_string('statusinfo', 'report_performance'),
                    'warning' => get_string('statuswarning', 'report_performance'),
                    'error' => get_string('statuserror', 'report_performance'),
                    'critical' => get_string('statuscritical', 'report_performance'),
                ],
            ],
            'detail' => [
                'title' => get_string('detail', 'report_performance'),
                'type' => 'text',
                'placeholder' => get_string('filterbycheck', 'report_performance'),
            ],
        ];
    }

    /**
     * Execute the report with the given parameters.
     *
     * @param array $params Filter parameters
     * @return array Check results
     */
    public function execute(array $params = []): array {
        $manager = new \core\check\manager();
        $checks = $manager->get_checks('performance');

        $results = [];
        foreach ($checks as $check) {
            $result = $check->get_result();

            // Apply status filter if set.
            if (!empty($params['status']) && $result->get_status() !== $params['status']) {
                continue;
            }

            $results[] = [
                'check' => $check,
                'result' => $result,
            ];
        }

        return $results;
    }

    /**
     * Get export formats supported by this report.
     *
     * @return array
     */
    public function get_export_formats(): array {
        return ['csv', 'pdf'];
    }
}
