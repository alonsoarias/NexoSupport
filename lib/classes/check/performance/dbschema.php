<?php
/**
 * Check for database schema consistency.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check\performance;

use core\check\check;
use core\check\result;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Checks if database schema matches expected structure.
 *
 * Schema mismatches can cause errors and performance issues.
 */
class dbschema extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Database Schema';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB, $CFG;

        // Get list of expected core tables
        $expectedTables = [
            'users',
            'config',
            'config_plugins',
            'roles',
            'capabilities',
            'role_assignments',
            'role_capabilities',
            'contexts',
            'sessions',
            'logstore_standard_log',
            'user_preferences',
            'user_password_history',
            'user_password_resets',
            'upgrade_log'
        ];

        $missingTables = [];
        $prefix = $CFG->prefix ?? '';

        foreach ($expectedTables as $table) {
            $fullTableName = $prefix . $table;
            try {
                // Try to get a count from the table to check existence
                $DB->count_records($table);
            } catch (\Exception $e) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            return new result(
                result::ERROR,
                count($missingTables) . ' table(s) missing',
                'The following expected tables are missing:<ul><li>' .
                implode('</li><li>', $missingTables) . '</li></ul>' .
                'This may indicate an incomplete installation or upgrade.'
            );
        }

        // Check for version mismatch
        $dbVersion = get_config('core', 'version');
        $codeVersion = $CFG->version ?? null;

        if ($dbVersion && $codeVersion && $dbVersion != $codeVersion) {
            return new result(
                result::WARNING,
                'Database version mismatch',
                'Database version (' . $dbVersion . ') does not match code version (' . $codeVersion . '). ' .
                'Run the upgrade process to synchronize.'
            );
        }

        return new result(
            result::OK,
            'Database schema is correct',
            'All expected tables exist and the version is synchronized.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/upgrade.php'),
            'Run upgrade'
        );
    }
}
