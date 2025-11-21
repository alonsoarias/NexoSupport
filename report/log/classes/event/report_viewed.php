<?php
/**
 * Event triggered when log report is viewed.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace report_log\event;

use core\event\base;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Event for viewing the log report.
 */
class report_viewed extends base {

    /**
     * Initialize the event.
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventreportviewed', 'report_log');
    }

    /**
     * Get the event description.
     *
     * @return string
     */
    public function get_description(): string {
        $courseid = $this->other['courseid'] ?? SITEID;
        return "The user with id '{$this->userid}' viewed the log report for course id '{$courseid}'.";
    }

    /**
     * Get the URL related to the event.
     *
     * @return \nexo_url
     */
    public function get_url(): \nexo_url {
        $courseid = $this->other['courseid'] ?? 0;
        return new \nexo_url('/report/log/index.php', ['id' => $courseid]);
    }

    /**
     * Get the action name.
     *
     * @return string
     */
    public static function get_action(): string {
        return 'viewed';
    }

    /**
     * Get the object table name.
     *
     * @return string|null
     */
    public static function get_objecttable(): ?string {
        return null;
    }
}
