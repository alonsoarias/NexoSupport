<?php
/**
 * Renderable class for live log report.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_loglive;

defined('INTERNAL_ACCESS') || die();

/**
 * Renderable for the live log report.
 */
class renderable implements \renderable {

    /** @var int Default cutoff time in seconds (1 hour) */
    const CUTOFF = 3600;

    /** @var int Course ID */
    public int $courseid;

    /** @var int Current page */
    public int $page;

    /** @var int Records per page */
    public int $perpage;

    /** @var \moodle_url Base URL */
    public \moodle_url $url;

    /** @var int Timestamp to fetch logs since */
    public int $since;

    /** @var table_log|null The log table */
    protected ?table_log $table = null;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID
     * @param int $page Page number
     * @param int $perpage Records per page
     * @param \moodle_url $url Base URL
     * @param int $since Timestamp to fetch logs since (0 for cutoff)
     */
    public function __construct(
        int $courseid,
        int $page,
        int $perpage,
        \moodle_url $url,
        int $since = 0
    ) {
        $this->courseid = $courseid;
        $this->page = $page;
        $this->perpage = min($perpage, 500);
        $this->url = $url;

        // If no 'since' provided, use cutoff (last 1 hour).
        if ($since <= 0) {
            $this->since = time() - self::CUTOFF;
        } else {
            $this->since = $since;
        }
    }

    /**
     * Get the filter object.
     *
     * @return \stdClass Filter parameters
     */
    public function get_filters(): \stdClass {
        $filter = new \stdClass();
        $filter->courseid = $this->courseid;
        $filter->since = $this->since;
        return $filter;
    }

    /**
     * Get the log table.
     *
     * @return table_log
     */
    public function get_table(): table_log {
        if ($this->table === null) {
            $this->table = new table_log($this->get_filters(), $this->url, $this->page, $this->perpage);
        }
        return $this->table;
    }
}
