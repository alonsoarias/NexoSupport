<?php
/**
 * Capability updated event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Capability updated event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 */
class capability_updated extends \core\event\base {

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
        return get_string('eventcapabilityupdated', 'core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $capability = $this->other['capability'] ?? 'unknown';
        $oldpermission = $this->other['oldpermission'] ?? 'unknown';
        $newpermission = $this->other['newpermission'] ?? 'unknown';
        return "User {$this->userid} updated capability '{$capability}' from '{$oldpermission}' to '{$newpermission}' for role {$this->objectid}";
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
        return 'u'; // Update
    }
}
