<?php
/**
 * User created event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

class user_created extends \core\event\base {
    protected static function get_action() {
        return 'created';
    }

    protected static function get_objecttable() {
        return 'users';
    }

    public function get_description() {
        $userid = $this->data['userid'] ?? 0;
        $objectid = $this->data['objectid'] ?? 0;
        return "User with id {$objectid} was created by user {$userid}";
    }
}
