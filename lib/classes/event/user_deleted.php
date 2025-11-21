<?php
/**
 * User deleted event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User deleted event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class user_deleted extends \core\event\base {

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
        return get_string('eventuserdeleted', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $userid = $this->data['userid'] ?? 0;
        $objectid = $this->data['objectid'] ?? 0;
        return "User {$userid} deleted user {$objectid}";
    }

    /**
     * Return the action.
     *
     * @return string
     */
    protected static function get_action() {
        return 'deleted';
    }

    /**
     * Return the object table.
     *
     * @return string
     */
    protected static function get_objecttable() {
        return 'users';
    }
}
