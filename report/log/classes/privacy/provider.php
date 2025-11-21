<?php
/**
 * Privacy provider for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace report_log\privacy;

use core_privacy\local\metadata\null_provider;

defined('NEXOSUPPORT_INTERNAL') || die();

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
