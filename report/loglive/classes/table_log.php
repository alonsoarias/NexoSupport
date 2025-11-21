<?php
/**
 * Log table class for report_loglive.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_loglive;

defined('INTERNAL_ACCESS') || die();

/**
 * Table for displaying live log entries.
 */
class table_log {

    /** @var \stdClass Filter parameters */
    protected \stdClass $filters;

    /** @var \moodle_url Base URL */
    protected \moodle_url $url;

    /** @var int Current page */
    protected int $page;

    /** @var int Records per page */
    protected int $perpage;

    /** @var array Log data */
    protected array $data = [];

    /** @var int Timestamp of most recent log entry */
    protected int $until = 0;

    /**
     * Constructor.
     *
     * @param \stdClass $filters Filter parameters
     * @param \moodle_url $url Base URL
     * @param int $page Page number
     * @param int $perpage Records per page
     */
    public function __construct(\stdClass $filters, \moodle_url $url, int $page, int $perpage) {
        $this->filters = $filters;
        $this->url = $url;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->until = $filters->since;

        $this->query_db();
    }

    /**
     * Query the database for log entries.
     */
    protected function query_db(): void {
        global $DB;

        $where = ['l.timecreated > :since'];
        $params = ['since' => $this->filters->since];

        // Course filter.
        if (!empty($this->filters->courseid) && $this->filters->courseid != SITEID) {
            $where[] = 'l.courseid = :courseid';
            $params['courseid'] = $this->filters->courseid;
        }

        $whereclause = 'WHERE ' . implode(' AND ', $where);

        // Get data ordered by time descending (newest first).
        $sql = "SELECT l.*, u.firstname, u.lastname, u.username
                FROM {logstore_standard_log} l
                LEFT JOIN {users} u ON u.id = l.userid
                $whereclause
                ORDER BY l.timecreated DESC";

        $offset = $this->page * $this->perpage;
        $this->data = $DB->get_records_sql($sql, $params, $offset, $this->perpage);

        // Update 'until' to the most recent timestamp.
        foreach ($this->data as $row) {
            if ($row->timecreated > $this->until) {
                $this->until = $row->timecreated;
            }
        }
    }

    /**
     * Get the timestamp of the most recent log entry.
     *
     * @return int
     */
    public function get_until(): int {
        return $this->until;
    }

    /**
     * Get the log data.
     *
     * @return array
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Render the table as HTML.
     *
     * @return string HTML output
     */
    public function render(): string {
        $html = '<div class="loglive-controls mb-3">';
        $html .= '<button id="loglive-pause" class="btn btn-warning">' . get_string('pause', 'report_loglive') . '</button>';
        $html .= ' <span class="loglive-count badge badge-info ml-2">Live updates enabled</span>';
        $html .= '</div>';

        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped table-hover generaltable loglive-table">';
        $html .= '<thead class="thead-light"><tr>';
        $html .= '<th>' . get_string('time') . '</th>';
        $html .= '<th>' . get_string('user') . '</th>';
        $html .= '<th>' . get_string('eventname', 'report_loglive') . '</th>';
        $html .= '<th>' . get_string('component', 'report_loglive') . '</th>';
        $html .= '<th>' . get_string('action') . '</th>';
        $html .= '<th>' . get_string('ip_address', 'report_loglive') . '</th>';
        $html .= '</tr></thead><tbody>';

        if (empty($this->data)) {
            $html .= '<tr><td colspan="6" class="text-center">' . get_string('nologsyet', 'report_loglive') . '</td></tr>';
        } else {
            foreach ($this->data as $row) {
                $html .= $this->render_row($row);
            }
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Render a single row.
     *
     * @param \stdClass $row Log row
     * @param string $class Additional CSS class
     * @return string HTML row
     */
    public function render_row(\stdClass $row, string $class = ''): string {
        $html = '<tr class="' . $class . '">';
        $html .= '<td>' . userdate($row->timecreated, '%H:%M:%S') . '</td>';
        $html .= '<td>' . $this->format_user($row) . '</td>';
        $html .= '<td>' . $this->format_eventname($row->eventname) . '</td>';
        $html .= '<td>' . htmlspecialchars($row->component) . '</td>';
        $html .= '<td>' . $this->format_action($row->crud) . '</td>';
        $html .= '<td>' . htmlspecialchars($row->ip ?? '') . '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Render rows for AJAX response.
     *
     * @return string HTML rows only
     */
    public function render_ajax_rows(): string {
        $html = '';
        foreach ($this->data as $row) {
            $html .= $this->render_row($row, 'new-row');
        }
        return $html;
    }

    /**
     * Format user display.
     *
     * @param \stdClass $row Log row
     * @return string
     */
    protected function format_user(\stdClass $row): string {
        if (empty($row->userid)) {
            return get_string('guest');
        }

        $name = trim($row->firstname . ' ' . $row->lastname);
        if (empty($name)) {
            return 'User ' . $row->userid;
        }

        return htmlspecialchars($name);
    }

    /**
     * Format event name for display.
     *
     * @param string $eventname Full event class name
     * @return string
     */
    protected function format_eventname(string $eventname): string {
        $parts = explode('\\', $eventname);
        $name = end($parts);
        $name = str_replace('_', ' ', $name);
        return ucfirst($name);
    }

    /**
     * Format CRUD action.
     *
     * @param string $crud CRUD letter
     * @return string
     */
    protected function format_action(string $crud): string {
        $actions = [
            'c' => '<span class="badge badge-success">Create</span>',
            'r' => '<span class="badge badge-info">Read</span>',
            'u' => '<span class="badge badge-warning">Update</span>',
            'd' => '<span class="badge badge-danger">Delete</span>',
        ];

        return $actions[$crud] ?? htmlspecialchars($crud);
    }
}
