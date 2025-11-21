<?php
/**
 * Log table class for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace report_log;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Table for displaying log entries.
 */
class table_log {

    /** @var \stdClass Filter parameters */
    protected \stdClass $filters;

    /** @var \nexo_url Base URL */
    protected \nexo_url $url;

    /** @var int Current page */
    protected int $page;

    /** @var int Records per page */
    protected int $perpage;

    /** @var array Log data */
    protected array $data = [];

    /** @var int Total count */
    protected int $totalcount = 0;

    /** @var array Column definitions */
    protected array $columns = [];

    /**
     * Constructor.
     *
     * @param \stdClass $filters Filter parameters
     * @param \nexo_url $url Base URL
     * @param int $page Page number
     * @param int $perpage Records per page
     */
    public function __construct(\stdClass $filters, \nexo_url $url, int $page, int $perpage) {
        $this->filters = $filters;
        $this->url = $url;
        $this->page = $page;
        $this->perpage = $perpage;

        $this->setup_columns();
        $this->query_db();
    }

    /**
     * Setup column definitions.
     */
    protected function setup_columns(): void {
        $this->columns = [
            'time' => get_string('time'),
            'fullnameuser' => get_string('user'),
            'eventname' => get_string('eventname', 'report_log'),
            'component' => get_string('component', 'report_log'),
            'action' => get_string('action'),
            'description' => get_string('description'),
            'origin' => get_string('origin', 'report_log'),
            'ip' => get_string('ip_address', 'report_log'),
        ];
    }

    /**
     * Query the database for log entries.
     */
    protected function query_db(): void {
        global $DB;

        $where = [];
        $params = [];

        // Course filter.
        if (!empty($this->filters->courseid) && $this->filters->courseid != SITEID) {
            $where[] = 'l.courseid = :courseid';
            $params['courseid'] = $this->filters->courseid;
        }

        // User filter.
        if (!empty($this->filters->userid)) {
            $where[] = 'l.userid = :userid';
            $params['userid'] = $this->filters->userid;
        }

        // Date filter (24 hour range).
        if (!empty($this->filters->date)) {
            $where[] = 'l.timecreated >= :datestart';
            $where[] = 'l.timecreated < :dateend';
            $params['datestart'] = $this->filters->date;
            $params['dateend'] = $this->filters->date + 86400;
        }

        // Action filter (CRUD).
        if (!empty($this->filters->modaction)) {
            $where[] = 'l.crud = :crud';
            $params['crud'] = $this->filters->modaction;
        }

        // Origin filter.
        if (!empty($this->filters->origin)) {
            $where[] = 'l.origin = :origin';
            $params['origin'] = $this->filters->origin;
        }

        // Educational level filter.
        if ($this->filters->edulevel >= 0) {
            $where[] = 'l.edulevel = :edulevel';
            $params['edulevel'] = $this->filters->edulevel;
        }

        $whereclause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count.
        $countsql = "SELECT COUNT(l.id)
                     FROM {logstore_standard_log} l
                     $whereclause";
        $this->totalcount = $DB->count_records_sql($countsql, $params);

        // Get data with pagination.
        $sql = "SELECT l.*, u.firstname, u.lastname, u.username
                FROM {logstore_standard_log} l
                LEFT JOIN {users} u ON u.id = l.userid
                $whereclause
                ORDER BY l.timecreated DESC";

        $offset = $this->page * $this->perpage;
        $this->data = $DB->get_records_sql($sql, $params, $offset, $this->perpage);
    }

    /**
     * Get the total count of records.
     *
     * @return int
     */
    public function get_total_count(): int {
        return $this->totalcount;
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
        if (empty($this->data)) {
            return '<div class="alert alert-info">' . get_string('nologsfound', 'report_log') . '</div>';
        }

        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-striped table-hover generaltable">';
        $html .= '<thead class="thead-light"><tr>';

        foreach ($this->columns as $key => $label) {
            $html .= '<th>' . htmlspecialchars($label) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($this->data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . userdate($row->timecreated, '%d %b %Y, %H:%M:%S') . '</td>';
            $html .= '<td>' . $this->format_user($row) . '</td>';
            $html .= '<td>' . $this->format_eventname($row->eventname) . '</td>';
            $html .= '<td>' . htmlspecialchars($row->component) . '</td>';
            $html .= '<td>' . $this->format_action($row->crud) . '</td>';
            $html .= '<td>' . $this->get_description($row) . '</td>';
            $html .= '<td>' . htmlspecialchars($row->origin ?? 'web') . '</td>';
            $html .= '<td>' . htmlspecialchars($row->ip ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        // Pagination.
        $html .= $this->render_pagination();

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
        // Extract readable name from class name.
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

    /**
     * Get description for the log entry.
     *
     * @param \stdClass $row Log row
     * @return string
     */
    protected function get_description(\stdClass $row): string {
        // Try to get a meaningful description.
        $parts = [];

        if (!empty($row->target)) {
            $parts[] = 'Target: ' . $row->target;
        }

        if (!empty($row->objecttable)) {
            $parts[] = 'Table: ' . $row->objecttable;
        }

        if (!empty($row->objectid)) {
            $parts[] = 'ID: ' . $row->objectid;
        }

        if (empty($parts)) {
            return '-';
        }

        return htmlspecialchars(implode(', ', $parts));
    }

    /**
     * Render pagination controls.
     *
     * @return string HTML pagination
     */
    protected function render_pagination(): string {
        $totalPages = ceil($this->totalcount / $this->perpage);

        if ($totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Log pagination"><ul class="pagination justify-content-center">';

        // Previous.
        $prevDisabled = $this->page <= 0 ? 'disabled' : '';
        $prevUrl = new \nexo_url($this->url, ['page' => max(0, $this->page - 1)]);
        $html .= '<li class="page-item ' . $prevDisabled . '">';
        $html .= '<a class="page-link" href="' . $prevUrl->out() . '">&laquo;</a></li>';

        // Page numbers.
        $startPage = max(0, $this->page - 2);
        $endPage = min($totalPages - 1, $this->page + 2);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = $i === $this->page ? 'active' : '';
            $pageUrl = new \nexo_url($this->url, ['page' => $i]);
            $html .= '<li class="page-item ' . $active . '">';
            $html .= '<a class="page-link" href="' . $pageUrl->out() . '">' . ($i + 1) . '</a></li>';
        }

        // Next.
        $nextDisabled = $this->page >= $totalPages - 1 ? 'disabled' : '';
        $nextUrl = new \nexo_url($this->url, ['page' => min($totalPages - 1, $this->page + 1)]);
        $html .= '<li class="page-item ' . $nextDisabled . '">';
        $html .= '<a class="page-link" href="' . $nextUrl->out() . '">&raquo;</a></li>';

        $html .= '</ul></nav>';

        // Summary.
        $start = $this->page * $this->perpage + 1;
        $end = min($this->totalcount, ($this->page + 1) * $this->perpage);
        $html .= '<p class="text-center text-muted">';
        $html .= sprintf('Showing %d to %d of %d entries', $start, $end, $this->totalcount);
        $html .= '</p>';

        return $html;
    }

    /**
     * Download logs in specified format.
     *
     * @param string $format Download format
     */
    public function download(string $format): void {
        global $DB;

        // Get all data without pagination.
        $where = [];
        $params = [];

        if (!empty($this->filters->courseid) && $this->filters->courseid != SITEID) {
            $where[] = 'l.courseid = :courseid';
            $params['courseid'] = $this->filters->courseid;
        }
        if (!empty($this->filters->userid)) {
            $where[] = 'l.userid = :userid';
            $params['userid'] = $this->filters->userid;
        }
        if (!empty($this->filters->date)) {
            $where[] = 'l.timecreated >= :datestart AND l.timecreated < :dateend';
            $params['datestart'] = $this->filters->date;
            $params['dateend'] = $this->filters->date + 86400;
        }

        $whereclause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT l.*, u.firstname, u.lastname, u.username
                FROM {logstore_standard_log} l
                LEFT JOIN {users} u ON u.id = l.userid
                $whereclause
                ORDER BY l.timecreated DESC";

        $data = $DB->get_records_sql($sql, $params, 0, 10000); // Max 10000 for download.

        $filename = 'logs_' . date('Y-m-d_H-i-s');

        switch ($format) {
            case 'csv':
                $this->download_csv($data, $filename);
                break;
            default:
                $this->download_csv($data, $filename);
        }
    }

    /**
     * Download as CSV.
     *
     * @param array $data Log data
     * @param string $filename Filename
     */
    protected function download_csv(array $data, string $filename): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Header row.
        fputcsv($output, ['Time', 'User', 'Event', 'Component', 'Action', 'Target', 'Origin', 'IP']);

        foreach ($data as $row) {
            fputcsv($output, [
                date('Y-m-d H:i:s', $row->timecreated),
                trim($row->firstname . ' ' . $row->lastname),
                $row->eventname,
                $row->component,
                $row->crud,
                $row->target ?? '',
                $row->origin ?? 'web',
                $row->ip ?? '',
            ]);
        }

        fclose($output);
    }
}
