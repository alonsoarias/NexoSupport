<?php
/**
 * Check for unsecured dataroot.
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
 * Checks if the dataroot directory is accessible via web.
 *
 * The dataroot should not be accessible from the web for security.
 */
class unsecuredataroot extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Dataroot Security';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        // Check if dataroot is defined
        if (empty($CFG->dataroot)) {
            return new result(
                result::ERROR,
                'Dataroot is not configured',
                'The dataroot directory is not set in the configuration.'
            );
        }

        // Check if dataroot exists
        if (!is_dir($CFG->dataroot)) {
            return new result(
                result::ERROR,
                'Dataroot directory does not exist',
                'The configured dataroot directory does not exist: ' . htmlspecialchars($CFG->dataroot)
            );
        }

        // Check if dataroot is inside wwwroot (very bad)
        $dataroot = realpath($CFG->dataroot);
        $dirroot = realpath($CFG->dirroot);

        if ($dataroot && $dirroot) {
            if (strpos($dataroot, $dirroot) === 0) {
                return new result(
                    result::CRITICAL,
                    'Dataroot is inside the web directory',
                    'The dataroot directory is located inside the web-accessible directory. ' .
                    'This is a critical security issue. Move the dataroot outside of the web root.'
                );
            }
        }

        // Check if dataroot is writable
        if (!is_writable($CFG->dataroot)) {
            return new result(
                result::ERROR,
                'Dataroot is not writable',
                'The web server cannot write to the dataroot directory.'
            );
        }

        return new result(
            result::OK,
            'Dataroot is properly secured',
            'The dataroot directory is located outside the web root and is writable.'
        );
    }
}
