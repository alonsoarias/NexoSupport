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

    protected static function get_target() {
        return 'user';
    }

    protected static function get_objecttable() {
        return 'users';
    }

    protected static function get_crud() {
        return 'c';
    }

    public function get_description() {
        return "User {$this->relateduserid} was created by user {$this->userid}";
    }
}
