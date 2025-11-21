<?php
/**
 * Plugin class for report_security.
 *
 * @package    report_security
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_security;

defined('INTERNAL_ACCESS') || die();

/**
 * Security report plugin class.
 *
 * This class extends the core report plugin base and provides
 * security-related checks and their status.
 */
class plugin extends \core\plugininfo\report {

    /**
     * Get the datasource for the security report.
     *
     * The security report uses the check system as its datasource.
     *
     * @return object
     */
    public function get_datasource(): object {
        return (object) [
            'type' => 'check',
            'component' => 'security',
            'table' => null,
            'description' => 'Security checks from core\\check\\manager',
        ];
    }

    /**
     * Get available columns for the security report.
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'check_name' => [
                'title' => get_string('check', 'report_security'),
                'sortable' => true,
                'type' => 'string',
            ],
            'status' => [
                'title' => get_string('status', 'report_security'),
                'sortable' => true,
                'type' => 'status',
            ],
            'summary' => [
                'title' => get_string('summary', 'report_security'),
                'sortable' => false,
                'type' => 'string',
            ],
            'details' => [
                'title' => get_string('details', 'report_security'),
                'sortable' => false,
                'type' => 'link',
            ],
        ];
    }

    /**
     * Get available filters for the security report.
     *
     * @return array
     */
    public function get_filters(): array {
        return [
            'status' => [
                'title' => get_string('status', 'report_security'),
                'type' => 'select',
                'options' => [
                    '' => get_string('all'),
                    'ok' => get_string('statusok', 'report_security'),
                    'info' => get_string('statusinfo', 'report_security'),
                    'warning' => get_string('statuswarning', 'report_security'),
                    'error' => get_string('statuserror', 'report_security'),
                    'critical' => get_string('statuscritical', 'report_security'),
                ],
            ],
            'detail' => [
                'title' => get_string('detail', 'report_security'),
                'type' => 'text',
                'placeholder' => get_string('filterbycheck', 'report_security'),
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
        $checks = $manager->get_checks('security');

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
