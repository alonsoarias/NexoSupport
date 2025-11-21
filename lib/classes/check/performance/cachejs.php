<?php
/**
 * Check for JavaScript caching.
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
 * Checks if JavaScript caching is enabled.
 *
 * JS caching improves page load performance.
 */
class cachejs extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'JavaScript Caching';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $cachejs = $CFG->cachejs ?? true;

        if (empty($cachejs)) {
            return new result(
                result::CRITICAL,
                'JavaScript caching is disabled',
                'JavaScript files are not being cached. This significantly impacts page load times ' .
                'as JS files must be processed on every request. Enable JS caching for production.'
            );
        }

        return new result(
            result::OK,
            'JavaScript caching is enabled',
            'JavaScript files are being cached for optimal performance.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'development']),
            'Development settings'
        );
    }
}
