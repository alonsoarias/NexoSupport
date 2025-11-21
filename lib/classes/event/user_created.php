<?php
/**
 * User created event.
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
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
