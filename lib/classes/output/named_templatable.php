<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Named Templatable Interface
 *
 * Extends templatable to also specify which template should be used.
 * Similar to Moodle's named_templatable interface.
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface named_templatable extends templatable {

    /**
     * Get the name of the template to use for this renderable
     *
     * @param renderer_base $renderer The renderer
     * @return string The template name (e.g., 'core/notification' or 'mod_forum/post')
     */
    public function get_template_name(renderer_base $renderer): string;
}
