<?php
/**
 * Check for theme designer mode.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\performance;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if theme designer mode is enabled.
 *
 * Theme designer mode disables theme caching for development.
 */
class themedesignermode extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Theme Designer Mode';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $themedesignermode = $CFG->themedesignermode ?? false;

        if (!empty($themedesignermode)) {
            return new result(
                result::CRITICAL,
                'Theme designer mode is enabled',
                'Theme designer mode disables theme caching, causing significant performance degradation. ' .
                'CSS and templates are recompiled on every page load. ' .
                'This should only be enabled during theme development. Disable for production.'
            );
        }

        return new result(
            result::OK,
            'Theme designer mode is disabled',
            'Themes are properly cached for optimal performance.'
        );
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'development']),
            'Development settings'
        );
    }
}
