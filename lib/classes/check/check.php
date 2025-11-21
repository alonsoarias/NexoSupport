<?php
/**
 * Abstract base class for all checks (security, performance, status).
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Base class for system checks.
 *
 * This class provides the foundation for security, performance, and status checks.
 * Each check evaluates a specific aspect of the system and returns a result.
 */
abstract class check {

    /** @var string The component this check belongs to */
    protected string $component = '';

    /**
     * Get the component this check belongs to.
     *
     * @return string The component name (e.g., 'core', 'mod_forum')
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check ID derived from the class name
     */
    public function get_id(): string {
        $class = get_class($this);
        $parts = explode('\\', $class);
        return end($parts);
    }

    /**
     * Get the global reference for this check.
     *
     * @return string The reference in format 'component_id'
     */
    public function get_ref(): string {
        $component = $this->get_component();
        $id = $this->get_id();

        if (empty($component)) {
            return $id;
        }

        return $component . '_' . $id;
    }

    /**
     * Get the human-readable name for this check.
     *
     * @return string The localized name of the check
     */
    public function get_name(): string {
        $id = $this->get_id();
        return get_string('check_' . $id, 'core_check', null, $id);
    }

    /**
     * Get an action link to fix or configure this check.
     *
     * @return \action_link|null An action link or null if not applicable
     */
    public function get_action_link(): ?\action_link {
        return null;
    }

    /**
     * Execute the check and return the result.
     *
     * @return result The result of the check
     */
    abstract public function get_result(): result;
}
