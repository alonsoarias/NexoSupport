<?php
/**
 * Renderer for report_loglive.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_loglive;

defined('INTERNAL_ACCESS') || die();

/**
 * Renderer for the live log report.
 */
class renderer extends \renderer_base {

    /**
     * Render the live log report.
     *
     * @param renderable $renderable The renderable object
     * @return string HTML output
     */
    public function render_renderable(renderable $renderable): string {
        $html = '';

        // Description.
        $html .= '<p class="lead">' . get_string('loglive_desc', 'report_loglive') . '</p>';

        // Render table.
        $table = $renderable->get_table();
        $html .= $table->render();

        return $html;
    }

    /**
     * Render AJAX response.
     *
     * @param renderable $renderable The renderable object
     * @return string JSON response
     */
    public function render_ajax(renderable $renderable): string {
        $table = $renderable->get_table();
        $data = $table->get_data();

        return json_encode([
            'logs' => $table->render_ajax_rows(),
            'until' => $table->get_until(),
            'newcount' => count($data),
        ]);
    }
}
