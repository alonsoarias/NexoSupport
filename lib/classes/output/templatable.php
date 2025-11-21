<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Templatable Interface
 *
 * Classes implementing this interface can export their data for use in Mustache templates.
 * Similar to Moodle's templatable interface.
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
interface templatable {

    /**
     * Export data for use in a Mustache template
     *
     * This method should return data that can be used in a Mustache template.
     * The data should be a stdClass object or array containing only:
     * - Scalar values (string, int, float, bool)
     * - Arrays of the above
     * - stdClass objects containing the above
     *
     * Complex objects should NOT be returned - convert them to simple data first.
     *
     * @param renderer_base $output The renderer
     * @return \stdClass|array Data for the template
     */
    public function export_for_template(renderer_base $output);
}
