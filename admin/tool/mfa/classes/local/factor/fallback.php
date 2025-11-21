<?php
/**
 * Fallback factor - used when no input factors are available.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mfa\local\factor;

defined('NEXOSUPPORT_INTERNAL') || die();

use tool_mfa\plugininfo\factor;

/**
 * Fallback factor class.
 *
 * This factor is returned when no other input factors are available.
 * It shows a message to the user that they need to set up MFA.
 */
class fallback extends object_factor_base {

    /** @var string Icon */
    protected string $icon = 'fa-exclamation-triangle';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->name = 'fallback';
        $this->state = factor::STATE_NEUTRAL;
    }

    /**
     * Get display name.
     *
     * @return string Display name
     */
    public function get_display_name(): string {
        return get_string('factor:fallback', 'tool_mfa');
    }

    /**
     * Check if factor is enabled.
     *
     * @return bool Always true for fallback
     */
    public function is_enabled(): bool {
        return true;
    }

    /**
     * Check if factor is active.
     *
     * @return bool Always true for fallback
     */
    public function is_active(): bool {
        return true;
    }

    /**
     * Check if factor requires input.
     *
     * @return bool True - shows message
     */
    public function has_input(): bool {
        return true;
    }

    /**
     * Get weight - fallback never contributes.
     *
     * @return int Always 0
     */
    public function get_weight(): int {
        return 0;
    }

    /**
     * Define login form elements.
     *
     * @param \MoodleQuickForm $mform Form object
     * @return \MoodleQuickForm Modified form
     */
    public function login_form_definition($mform) {
        $mform->addElement('html', '<div class="alert alert-warning">' .
            get_string('factor:fallback_message', 'tool_mfa') . '</div>');
        return $mform;
    }
}
