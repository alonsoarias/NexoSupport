<?php
/**
 * Role assigned event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Role assigned event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class role_assigned extends \core\event\base {

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
        return get_string('eventroleassigned', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $userid = $this->data['userid'] ?? 0;
        $objectid = $this->data['objectid'] ?? 0;
        return "User {$userid} assigned role {$objectid}";
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
        return 'role_assignments';
    }
}
