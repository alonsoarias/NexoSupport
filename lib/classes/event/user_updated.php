<?php
/**
 * User updated event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User updated event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class user_updated extends \core\event\base {

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
        return get_string('eventuserupdated', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $userid = $this->data['userid'] ?? 0;
        $objectid = $this->data['objectid'] ?? 0;
        return "User {$userid} updated user {$objectid}";
    }

    /**
     * Return the action.
     *
     * @return string
     */
    protected static function get_action() {
        return 'updated';
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
