<?php
/**
 * Check for secure cookie settings.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\http;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if cookies are configured securely.
 */
class cookiesecure extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Secure Cookies';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $issues = [];
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Check if using HTTPS
        if (!$isHttps && strpos($CFG->wwwroot ?? '', 'https://') !== 0) {
            $issues[] = 'Site is not using HTTPS';
        }

        // Check cookie_secure setting
        $cookieSecure = get_config('core', 'cookiesecure');
        if (empty($cookieSecure) && $isHttps) {
            $issues[] = 'Secure cookie flag is not enabled for HTTPS site';
        }

        // Check cookie_httponly
        $cookieHttpOnly = get_config('core', 'cookiehttponly');
        if (empty($cookieHttpOnly)) {
            $issues[] = 'HttpOnly cookie flag is not enabled';
        }

        // Check session.cookie_secure PHP setting
        if ($isHttps && !ini_get('session.cookie_secure')) {
            $issues[] = 'PHP session.cookie_secure is not enabled';
        }

        // Check session.cookie_httponly PHP setting
        if (!ini_get('session.cookie_httponly')) {
            $issues[] = 'PHP session.cookie_httponly is not enabled';
        }

        if (!empty($issues)) {
            $status = $isHttps ? result::WARNING : result::ERROR;
            return new result(
                $status,
                'Cookie security issues found',
                'The following cookie security issues were detected:<ul><li>' .
                implode('</li><li>', $issues) . '</li></ul>'
            );
        }

        return new result(
            result::OK,
            'Cookies are configured securely',
            'Cookies are using secure flags (Secure, HttpOnly) and the site uses HTTPS.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'sessionhandling']),
            'Session settings'
        );
    }
}
