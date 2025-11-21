<?php
/**
 * MFA factor base class.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace tool_mfa\local\factor;

defined('NEXOSUPPORT_INTERNAL') || die();

use tool_mfa\plugininfo\factor;

/**
 * Base class for MFA factors.
 */
abstract class object_factor_base implements object_factor {

    /** @var string Factor name */
    protected string $name;

    /** @var string Current state */
    protected string $state = factor::STATE_UNKNOWN;

    /** @var string Icon class (FontAwesome) */
    protected string $icon = 'fa-shield';

    /** @var int Lock counter */
    protected int $lockcounter = 0;

    /**
     * Constructor.
     *
     * @param string $name Factor name
     */
    public function __construct(string $name) {
        $this->name = $name;
        $this->load_locked_state();
    }

    /**
     * Get factor name.
     *
     * @return string Factor name
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get display name.
     *
     * @return string Display name
     */
    public function get_display_name(): string {
        return get_string('pluginname', 'factor_' . $this->name);
    }

    /**
     * Get factor icon.
     *
     * @return string Icon class
     */
    public function get_icon(): string {
        return $this->icon;
    }

    /**
     * Check if factor is enabled.
     *
     * @return bool True if enabled
     */
    public function is_enabled(): bool {
        return (bool) get_config('factor_' . $this->name, 'enabled');
    }

    /**
     * Check if factor is active for current user.
     *
     * Default implementation - override in subclasses.
     *
     * @return bool True if active
     */
    public function is_active(): bool {
        return $this->is_enabled();
    }

    /**
     * Check if factor requires user input.
     *
     * @return bool True if requires input
     */
    public function has_input(): bool {
        return true;
    }

    /**
     * Get current state of factor.
     *
     * @return string State constant
     */
    public function get_state(): string {
        return $this->state;
    }

    /**
     * Set factor state.
     *
     * @param string $state New state
     * @return void
     */
    public function set_state(string $state): void {
        global $SESSION;

        $this->state = $state;

        // Store in session
        if (!isset($SESSION->mfa_factor_states)) {
            $SESSION->mfa_factor_states = [];
        }
        $SESSION->mfa_factor_states[$this->name] = $state;
    }

    /**
     * Get factor weight.
     *
     * @return int Weight value
     */
    public function get_weight(): int {
        return (int) get_config('factor_' . $this->name, 'weight') ?: 100;
    }

    /**
     * Define login form elements.
     *
     * @param \MoodleQuickForm $mform Form object
     * @return \MoodleQuickForm Modified form
     */
    public function login_form_definition($mform) {
        // Default: no form elements
        return $mform;
    }

    /**
     * Additional form definition after data is set.
     *
     * @param \MoodleQuickForm $mform Form object
     * @return \MoodleQuickForm Modified form
     */
    public function login_form_definition_after_data($mform) {
        // Default: nothing
        return $mform;
    }

    /**
     * Validate login form data.
     *
     * @param array $data Form data
     * @return array Validation errors
     */
    public function login_form_validation(array $data): array {
        return [];
    }

    /**
     * Called after MFA is passed.
     *
     * @return void
     */
    public function post_pass_state(): void {
        global $DB, $USER;

        // Update last verified time
        $DB->set_field('tool_mfa', 'lastverified', time(), [
            'userid' => $USER->id,
            'factor' => $this->name,
            'revoked' => 0,
        ]);
    }

    /**
     * Load lock state from database.
     *
     * @return void
     */
    protected function load_locked_state(): void {
        global $DB, $USER;

        if (!isset($USER->id) || $USER->id == 0) {
            return;
        }

        // Check session first
        if (isset($_SESSION['mfa_factor_states'][$this->name])) {
            $this->state = $_SESSION['mfa_factor_states'][$this->name];
        }

        // Get max lock counter for this factor
        $sql = "SELECT MAX(lockcounter) as maxlock FROM {tool_mfa}
                WHERE userid = ? AND factor = ? AND revoked = 0";
        $result = $DB->get_record_sql($sql, [$USER->id, $this->name]);

        if ($result && $result->maxlock) {
            $this->lockcounter = (int) $result->maxlock;

            // Check if locked
            $lockthreshold = get_config('tool_mfa', 'lockout') ?: 10;
            if ($this->lockcounter >= $lockthreshold) {
                $this->state = factor::STATE_LOCKED;
            }
        }
    }

    /**
     * Check if factor is ready to be used.
     *
     * Override in subclasses for factor-specific checks.
     *
     * @return bool True if ready
     */
    protected function is_ready(): bool {
        return true;
    }

    /**
     * Get user factor records.
     *
     * @return array Array of records
     */
    protected function get_user_factors(): array {
        global $DB, $USER;

        return $DB->get_records('tool_mfa', [
            'userid' => $USER->id,
            'factor' => $this->name,
            'revoked' => 0,
        ]);
    }

    /**
     * Get description for the factor.
     *
     * @return string Description
     */
    public function get_description(): string {
        $key = 'factor:description';
        $component = 'factor_' . $this->name;

        if (\core\string_manager::string_exists($key, $component)) {
            return get_string($key, $component);
        }

        return '';
    }

    /**
     * Get setup instructions.
     *
     * @return string Setup instructions
     */
    public function get_setup_instructions(): string {
        $key = 'factor:setup';
        $component = 'factor_' . $this->name;

        if (\core\string_manager::string_exists($key, $component)) {
            return get_string($key, $component);
        }

        return '';
    }
}
