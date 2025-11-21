<?php
/**
 * Privacy provider for report_performance.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_performance\privacy;

use core_privacy\local\metadata\null_provider;

defined('INTERNAL_ACCESS') || die();

/**
 * Privacy provider - this plugin does not store user data.
 */
class provider implements null_provider {

    /**
     * Get the language string identifier for the reason this plugin stores no data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
