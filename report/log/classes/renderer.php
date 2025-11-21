<?php
/**
 * Renderer for report_log.
 *
 * @package    report_log
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_log;

defined('INTERNAL_ACCESS') || die();

/**
 * Renderer for the log report.
 */
class renderer extends \renderer_base {

    /**
     * Render the log report.
     *
     * @param renderable $renderable The renderable object
     * @return string HTML output
     */
    public function render_renderable(renderable $renderable): string {
        $html = '';

        // Render filters.
        $html .= $this->render_filters($renderable);

        // Render table.
        $table = $renderable->get_table();
        $html .= $table->render();

        return $html;
    }

    /**
     * Render the filter form.
     *
     * @param renderable $renderable The renderable object
     * @return string HTML output
     */
    protected function render_filters(renderable $renderable): string {
        $html = '<form method="get" action="' . $renderable->url->out_omit_querystring() . '" class="mb-4">';
        $html .= '<input type="hidden" name="id" value="' . $renderable->courseid . '">';

        $html .= '<div class="card"><div class="card-header">';
        $html .= '<h5 class="mb-0">' . get_string('filters', 'report_log') . '</h5>';
        $html .= '</div><div class="card-body">';

        $html .= '<div class="row">';

        // User filter.
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<label for="user">' . get_string('user') . '</label>';
        $html .= '<select name="user" id="user" class="form-control">';
        foreach ($renderable->get_user_options() as $value => $label) {
            $selected = $renderable->userid == $value ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select></div>';

        // Date filter.
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<label for="date">' . get_string('date') . '</label>';
        $html .= '<select name="date" id="date" class="form-control">';
        foreach ($renderable->get_date_options() as $value => $label) {
            $selected = $renderable->date == $value ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select></div>';

        // Action filter.
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<label for="modaction">' . get_string('action') . '</label>';
        $html .= '<select name="modaction" id="modaction" class="form-control">';
        foreach ($renderable->get_action_options() as $value => $label) {
            $selected = $renderable->modaction == $value ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select></div>';

        // Origin filter.
        $html .= '<div class="col-md-3 mb-3">';
        $html .= '<label for="origin">' . get_string('origin', 'report_log') . '</label>';
        $html .= '<select name="origin" id="origin" class="form-control">';
        foreach ($renderable->get_origin_options() as $value => $label) {
            $selected = $renderable->origin == $value ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select></div>';

        $html .= '</div>'; // End row.

        // Submit buttons.
        $html .= '<div class="row">';
        $html .= '<div class="col-12">';
        $html .= '<button type="submit" class="btn btn-primary">' . get_string('gettheselogs', 'report_log') . '</button>';
        $html .= ' <a href="' . $renderable->url->out_omit_querystring() . '?id=' . $renderable->courseid . '&download=csv" class="btn btn-secondary">';
        $html .= get_string('downloadcsv', 'report_log') . '</a>';
        $html .= '</div></div>';

        $html .= '</div></div>'; // End card.
        $html .= '</form>';

        return $html;
    }
}
