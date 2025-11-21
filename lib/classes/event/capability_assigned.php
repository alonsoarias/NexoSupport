<?php
/**
 * Capability assigned event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
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
        $userid = $this->data['userid'] ?? 0;
        $objectid = $this->data['objectid'] ?? 0;
        $other = is_string($this->data['other']) ? json_decode($this->data['other'], true) : ($this->data['other'] ?? []);
        $capability = $other['capability'] ?? 'unknown';
        $permission = $other['permission'] ?? 'unknown';
        return "User {$userid} assigned capability '{$capability}' with permission '{$permission}' to role {$objectid}";
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
     * Return the object table.
     *
     * @return string
     */
    protected static function get_objecttable() {
        return 'role_capabilities';
    }
}
