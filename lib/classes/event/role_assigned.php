<?php
/**
 * Role assigned event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    protected function init($data = null) {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        parent::init($data);
    }

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
        return "User {$this->userid} assigned role {$this->objectid} to user {$this->relateduserid}";
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
        return 'role';
    }

    /**
     * Return the object table.
     *
     * @return string
     */
    protected static function get_objecttable() {
        return 'role_assignments';
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
