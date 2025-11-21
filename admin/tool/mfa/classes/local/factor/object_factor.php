<?php
/**
 * MFA factor interface.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace tool_mfa\local\factor;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Interface for MFA factors.
 */
interface object_factor {

    /**
     * Get factor name.
     *
     * @return string Factor name
     */
    public function get_name(): string;

    /**
     * Get display name.
     *
     * @return string Display name
     */
    public function get_display_name(): string;

    /**
     * Check if factor is enabled.
     *
     * @return bool True if enabled
     */
    public function is_enabled(): bool;

    /**
     * Check if factor is active for current user.
     *
     * @return bool True if active
     */
    public function is_active(): bool;

    /**
     * Check if factor requires user input.
     *
     * @return bool True if requires input
     */
    public function has_input(): bool;

    /**
     * Get current state of factor.
     *
     * @return string State constant
     */
    public function get_state(): string;

    /**
     * Set factor state.
     *
     * @param string $state New state
     * @return void
     */
    public function set_state(string $state): void;

    /**
     * Get factor weight.
     *
     * @return int Weight value
     */
    public function get_weight(): int;

    /**
     * Define login form elements.
     *
     * @param \MoodleQuickForm $mform Form object
     * @return \MoodleQuickForm Modified form
     */
    public function login_form_definition($mform);

    /**
     * Additional form definition after data is set.
     *
     * @param \MoodleQuickForm $mform Form object
     * @return \MoodleQuickForm Modified form
     */
    public function login_form_definition_after_data($mform);

    /**
     * Validate login form data.
     *
     * @param array $data Form data
     * @return array Validation errors
     */
    public function login_form_validation(array $data): array;

    /**
     * Called after MFA is passed.
     *
     * @return void
     */
    public function post_pass_state(): void;
}
