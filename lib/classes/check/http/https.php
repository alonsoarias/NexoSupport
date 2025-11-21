<?php
/**
 * Check for HTTPS configuration.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check\http;

use core\check\check;
use core\check\result;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Checks if the site is using HTTPS.
 */
class https extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'HTTPS';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $wwwrootHttps = !empty($CFG->wwwroot) && strpos($CFG->wwwroot, 'https://') === 0;
        $currentHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        if ($wwwrootHttps && $currentHttps) {
            return new result(
                result::OK,
                'Site is using HTTPS',
                'The site is properly configured to use HTTPS for secure connections.'
            );
        }

        if ($wwwrootHttps && !$currentHttps) {
            return new result(
                result::WARNING,
                'HTTPS configured but current request is HTTP',
                'The site wwwroot is configured for HTTPS, but the current request came via HTTP. ' .
                'Consider enforcing HTTPS redirects at the web server level.'
            );
        }

        if (!$wwwrootHttps) {
            return new result(
                result::ERROR,
                'Site is not using HTTPS',
                'The site wwwroot is configured with HTTP instead of HTTPS. ' .
                'HTTPS is essential for protecting user data and credentials. ' .
                'Obtain an SSL certificate and update your configuration.'
            );
        }

        return new result(
            result::WARNING,
            'HTTPS status unclear',
            'Could not determine HTTPS configuration status.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \core\nexo_url('/admin/settings.php', ['section' => 'http']),
            'HTTP settings'
        );
    }
}
