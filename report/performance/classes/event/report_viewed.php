<?php
/**
 * Event triggered when performance report is viewed.
 *
 * @package    report_performance
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_performance\event;

use core\event\base;

defined('INTERNAL_ACCESS') || die();

/**
 * Event for viewing the performance report.
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
        return get_string('eventperformancereportviewed', 'report_performance');
    }

    /**
     * Get the event description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' viewed the performance report.";
    }

    /**
     * Get the URL related to the event.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/report/performance/index.php');
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
