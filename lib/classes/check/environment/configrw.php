<?php
/**
 * Check for config file permissions.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\environment;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if configuration files have secure permissions.
 *
 * Config files should not be world-writable for security.
 */
class configrw extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Config File Permissions';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $configfile = $CFG->dirroot . '/.env';

        if (!file_exists($configfile)) {
            // Try config.php as fallback
            $configfile = $CFG->dirroot . '/config.php';
        }

        if (!file_exists($configfile)) {
            return new result(
                result::WARNING,
                'Configuration file not found',
                'Could not locate the configuration file to check permissions.'
            );
        }

        $perms = fileperms($configfile);

        // Check if world-writable (0002)
        if ($perms & 0x0002) {
            return new result(
                result::WARNING,
                'Configuration file is world-writable',
                'The configuration file has world-writable permissions. ' .
                'This is a security risk. Set permissions to 640 or less: ' .
                '<code>chmod 640 ' . htmlspecialchars(basename($configfile)) . '</code>'
            );
        }

        // Check if world-readable (0004)
        if ($perms & 0x0004) {
            return new result(
                result::INFO,
                'Configuration file is world-readable',
                'The configuration file is readable by all users. Consider restricting access ' .
                'using <code>chmod 640</code> for better security.'
            );
        }

        return new result(
            result::OK,
            'Configuration file has secure permissions',
            'The configuration file permissions are appropriately restricted.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        global $CFG;
        return new \action_link(
            new \nexo_url('/admin/environment.php'),
            'Environment'
        );
    }
}
