<?php
/**
 * User logged in event.
 *
 * @package    core
 * @subpackage event
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User logged in event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class user_loggedin extends \core\event\base {

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
        return get_string('eventuserloggedin', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "User {$this->userid} logged in";
    }

    /**
     * Return legacy log data.
     *
     * @return array
     */
    protected static function get_action() {
        return 'loggedin';
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
        return 'r'; // Read
    }
}
