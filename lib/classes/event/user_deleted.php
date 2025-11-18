<?php
/**
 * User deleted event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        return "User {$this->userid} deleted user {$this->relateduserid}";
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
     * Return the target.
     *
     * @return string
     */
    protected static function get_target() {
        return 'user';
    }

    /**
     * Return the object table.
     *
     * @return string
     */
    protected static function get_objecttable() {
        return 'users';
    }

    /**
     * Return the CRUD type.
     *
     * @return string
     */
    protected static function get_crud() {
        return 'd'; // Delete
    }
}
