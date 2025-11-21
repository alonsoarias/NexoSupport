<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Renderable Interface
 *
 * Marker interface for objects that can be rendered by a renderer.
 * Similar to Moodle's renderable interface.
 *
 * This is a marker interface - it contains no methods.
 * Classes implementing this interface are expected to be rendered
 * by calling a render method on a renderer.
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface renderable {
    // This is a marker interface - no methods required
}
