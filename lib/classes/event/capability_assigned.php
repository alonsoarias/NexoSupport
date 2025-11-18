<?php
/**
 * Capability assigned event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Capability assigned event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class capability_assigned extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcapabilityassigned', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $capability = $this->other['capability'] ?? 'unknown';
        $permission = $this->other['permission'] ?? 'unknown';
        return "User {$this->userid} assigned capability '{$capability}' with permission '{$permission}' to role {$this->objectid}";
    }

    /**
     * Return the action.
     *
     * @return string
     */
    protected static function get_action() {
        return 'assigned';
    }

    /**
     * Return the target.
     *
     * @return string
     */
    protected static function get_target() {
        return 'capability';
    }

    /**
     * Return the object table.
     *
     * @return string
     */
    protected static function get_objecttable() {
        return 'role_capabilities';
    }

    /**
     * Return the CRUD type.
     *
     * @return string
     */
    protected static function get_crud() {
        return 'c'; // Create
    }
}
