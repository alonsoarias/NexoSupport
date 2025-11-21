<?php
/**
 * Renderable class for log report.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_log;

defined('INTERNAL_ACCESS') || die();

/**
 * Renderable for the log report.
 */
class renderable implements \renderable {

    /** @var int Course ID */
    public int $courseid;

    /** @var int User ID filter */
    public int $userid;

    /** @var string Module ID filter */
    public string $modid;

    /** @var string Module action filter */
    public string $modaction;

    /** @var int Date filter (timestamp) */
    public int $date;

    /** @var int Educational level filter */
    public int $edulevel;

    /** @var string Origin filter */
    public string $origin;

    /** @var int Current page */
    public int $page;

    /** @var int Records per page */
    public int $perpage;

    /** @var \moodle_url Base URL */
    public \moodle_url $url;

    /** @var table_log|null The log table */
    protected ?table_log $table = null;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param string $modid Module ID
     * @param string $modaction Module action
     * @param int $date Date timestamp
     * @param int $edulevel Educational level
     * @param string $origin Origin
     * @param int $page Page number
     * @param int $perpage Records per page
     * @param \moodle_url $url Base URL
     */
    public function __construct(
        int $courseid,
        int $userid,
        string $modid,
        string $modaction,
        int $date,
        int $edulevel,
        string $origin,
        int $page,
        int $perpage,
        \moodle_url $url
    ) {
        $this->courseid = $courseid;
        $this->userid = $userid;
        $this->modid = $modid;
        $this->modaction = $modaction;
        $this->date = $date;
        $this->edulevel = $edulevel;
        $this->origin = $origin;
        $this->page = $page;
        $this->perpage = min($perpage, 1000); // Max 1000 per page.
        $this->url = $url;
    }

    /**
     * Get the filter object.
     *
     * @return \stdClass Filter parameters
     */
    public function get_filters(): \stdClass {
        $filter = new \stdClass();
        $filter->courseid = $this->courseid;
        $filter->userid = $this->userid;
        $filter->modid = $this->modid;
        $filter->modaction = $this->modaction;
        $filter->date = $this->date;
        $filter->edulevel = $this->edulevel;
        $filter->origin = $this->origin;
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

    /**
     * Download logs in specified format.
     *
     * @param string $format Download format (csv, excel, ods)
     */
    public function download(string $format): void {
        $table = $this->get_table();
        $table->download($format);
    }

    /**
     * Get available users for filter.
     *
     * @return array User options
     */
    public function get_user_options(): array {
        global $DB;

        $users = [0 => get_string('allusers', 'report_log')];

        // Get users from log records.
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.username
                FROM {logstore_standard_log} l
                JOIN {users} u ON u.id = l.userid
                ORDER BY u.lastname, u.firstname";

        $records = $DB->get_records_sql($sql, [], 0, 100);
        foreach ($records as $user) {
            $users[$user->id] = fullname($user) . ' (' . $user->username . ')';
        }

        return $users;
    }

    /**
     * Get available date options for filter.
     *
     * @return array Date options
     */
    public function get_date_options(): array {
        global $DB;

        $dates = [0 => get_string('alldays', 'report_log')];

        // Get dates with log entries.
        $sql = "SELECT DISTINCT DATE(FROM_UNIXTIME(timecreated)) as logdate,
                       MIN(timecreated) as mintime
                FROM {logstore_standard_log}
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY logdate DESC
                LIMIT 30";

        try {
            $records = $DB->get_records_sql($sql);
            foreach ($records as $record) {
                $dates[$record->mintime] = userdate($record->mintime, '%d %B %Y');
            }
        } catch (\Exception $e) {
            // If query fails, provide last 30 days.
            for ($i = 0; $i < 30; $i++) {
                $time = strtotime("-$i days", strtotime('today'));
                $dates[$time] = userdate($time, '%d %B %Y');
            }
        }

        return $dates;
    }

    /**
     * Get available action options for filter.
     *
     * @return array Action options
     */
    public function get_action_options(): array {
        return [
            '' => get_string('allactions', 'report_log'),
            'c' => get_string('create'),
            'r' => get_string('view'),
            'u' => get_string('update'),
            'd' => get_string('delete'),
        ];
    }

    /**
     * Get available origin options for filter.
     *
     * @return array Origin options
     */
    public function get_origin_options(): array {
        return [
            '' => get_string('allorigins', 'report_log'),
            'web' => get_string('originweb', 'report_log'),
            'cli' => get_string('origincli', 'report_log'),
            'ws' => get_string('originws', 'report_log'),
            'restore' => get_string('originrestore', 'report_log'),
        ];
    }

    /**
     * Get educational level options.
     *
     * @return array Level options
     */
    public function get_edulevel_options(): array {
        return [
            -1 => get_string('alledulevels', 'report_log'),
            0 => get_string('other'),
            1 => get_string('teaching'),
            2 => get_string('participating'),
        ];
    }
}
