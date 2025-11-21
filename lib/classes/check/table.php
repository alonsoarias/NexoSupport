<?php
/**
 * Table renderer for system checks.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Renders a table of system checks.
 *
 * Displays checks with their status, summary, and optional action links.
 */
class table {

    /** @var string The type of checks to display */
    protected string $type;

    /** @var \core\nexo_url The base URL for the page */
    protected \core\nexo_url $url;

    /** @var string|null The ID of a specific check to show in detail */
    protected ?string $detail;

    /** @var array The checks to display */
    protected array $checks;

    /**
     * Create a new check table.
     *
     * @param string $type The type of checks (security, performance, status)
     * @param \core\nexo_url $url The page URL
     * @param string $detail Optional check ID for detail view
     */
    public function __construct(string $type, \core\nexo_url $url, string $detail = '') {
        $this->type = $type;
        $this->url = $url;
        $this->detail = $detail ?: null;
        $this->checks = manager::get_checks($type);
    }

    /**
     * Render the table as HTML.
     *
     * @param object $output The output renderer (NexoOutput or renderer_base)
     * @return string The HTML output
     */
    public function render(object $output): string {
        $html = '';

        // Show summary counts
        $html .= $this->render_summary();

        // Show detail view if requested
        if ($this->detail) {
            $html .= $this->render_detail();
            return $html;
        }

        // Show the main table
        $html .= $this->render_table();

        return $html;
    }

    /**
     * Render the summary counts.
     *
     * @return string HTML summary
     */
    protected function render_summary(): string {
        $counts = [
            result::OK => 0,
            result::INFO => 0,
            result::WARNING => 0,
            result::ERROR => 0,
            result::CRITICAL => 0,
            result::NA => 0,
            result::UNKNOWN => 0,
        ];

        foreach ($this->checks as $check) {
            $result = $check->get_result();
            $status = $result->get_status();
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        $problems = $counts[result::WARNING] + $counts[result::ERROR] + $counts[result::CRITICAL];

        $html = '<div class="check-summary mb-4">';

        if ($problems > 0) {
            $html .= '<div class="alert alert-warning">';
            $html .= '<i class="fa fa-exclamation-triangle mr-2"></i>';
            $html .= sprintf('%d issue(s) found that may need attention.', $problems);
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-success">';
            $html .= '<i class="fa fa-check-circle mr-2"></i>';
            $html .= 'No issues found. All checks passed.';
            $html .= '</div>';
        }

        // Badge summary
        $html .= '<div class="check-badges">';
        foreach ($counts as $status => $count) {
            if ($count > 0) {
                $resultObj = new result($status, '');
                $html .= sprintf(
                    '<span class="badge badge-%s mr-2">%s: %d</span>',
                    $resultObj->get_status_class(),
                    $resultObj->get_status_label(),
                    $count
                );
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render the main checks table.
     *
     * @return string HTML table
     */
    protected function render_table(): string {
        if (empty($this->checks)) {
            return '<div class="alert alert-info">No checks available.</div>';
        }

        // Sort checks by severity (most severe first)
        usort($this->checks, function($a, $b) {
            $resultA = $a->get_result();
            $resultB = $b->get_result();
            return $resultB->get_severity() - $resultA->get_severity();
        });

        $html = '<table class="table table-striped table-hover generaltable">';
        $html .= '<thead class="thead-light">';
        $html .= '<tr>';
        $html .= '<th style="width: 120px;">Status</th>';
        $html .= '<th>Check</th>';
        $html .= '<th>Summary</th>';
        $html .= '<th style="width: 150px;">Action</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->checks as $check) {
            $result = $check->get_result();
            $html .= $this->render_row($check, $result);
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Render a single row in the table.
     *
     * @param check $check The check object
     * @param result $result The check result
     * @return string HTML row
     */
    protected function render_row(check $check, result $result): string {
        $statusClass = $result->get_status_class();
        $icon = $result->get_status_icon();

        $html = '<tr>';

        // Status column
        $html .= '<td>';
        $html .= sprintf(
            '<span class="badge badge-%s"><i class="fa fa-%s mr-1"></i>%s</span>',
            $statusClass,
            $icon,
            htmlspecialchars($result->get_status_label())
        );
        $html .= '</td>';

        // Check name column
        $html .= '<td>';
        $html .= '<strong>' . htmlspecialchars($check->get_name()) . '</strong>';
        $html .= '</td>';

        // Summary column
        $html .= '<td>';
        $html .= htmlspecialchars($result->get_summary());
        if ($result->get_details()) {
            $detailUrl = new \core\nexo_url($this->url, ['detail' => $check->get_id()]);
            $html .= ' <a href="' . $detailUrl->out() . '" class="text-muted">[details]</a>';
        }
        $html .= '</td>';

        // Action column
        $html .= '<td>';
        $actionLink = $check->get_action_link();
        if ($actionLink) {
            $html .= sprintf(
                '<a href="%s" class="btn btn-sm btn-outline-primary">%s</a>',
                $actionLink->url->out(),
                htmlspecialchars($actionLink->text)
            );
        }
        $html .= '</td>';

        $html .= '</tr>';

        return $html;
    }

    /**
     * Render the detail view for a specific check.
     *
     * @return string HTML detail view
     */
    protected function render_detail(): string {
        $check = manager::get_check($this->type, $this->detail);

        if (!$check) {
            return '<div class="alert alert-danger">Check not found.</div>';
        }

        $result = $check->get_result();

        $html = '<div class="check-detail card mb-4">';
        $html .= '<div class="card-header">';
        $html .= '<h4 class="mb-0">' . htmlspecialchars($check->get_name()) . '</h4>';
        $html .= '</div>';
        $html .= '<div class="card-body">';

        // Status badge
        $html .= '<p>';
        $html .= '<strong>Status:</strong> ';
        $html .= sprintf(
            '<span class="badge badge-%s"><i class="fa fa-%s mr-1"></i>%s</span>',
            $result->get_status_class(),
            $result->get_status_icon(),
            htmlspecialchars($result->get_status_label())
        );
        $html .= '</p>';

        // Summary
        $html .= '<p><strong>Summary:</strong> ' . htmlspecialchars($result->get_summary()) . '</p>';

        // Details
        if ($result->get_details()) {
            $html .= '<div class="mt-3">';
            $html .= '<strong>Details:</strong>';
            $html .= '<div class="mt-2 p-3 bg-light border rounded">';
            $html .= $result->get_details(); // May contain HTML
            $html .= '</div>';
            $html .= '</div>';
        }

        // Action link
        $actionLink = $check->get_action_link();
        if ($actionLink) {
            $html .= '<div class="mt-3">';
            $html .= sprintf(
                '<a href="%s" class="btn btn-primary">%s</a>',
                $actionLink->url->out(),
                htmlspecialchars($actionLink->text)
            );
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Back link
        $html .= '<p><a href="' . $this->url->out() . '">&laquo; Back to all checks</a></p>';

        return $html;
    }
}
