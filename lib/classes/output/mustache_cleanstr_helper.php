<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Clean String Helper
 *
 * Similar to the str helper but strips HTML from the result.
 * Usage: {{#cleanstr}}identifier, component{{/cleanstr}}
 *
 * Example:
 *   {{#cleanstr}}description, mycomponent{{/cleanstr}}
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_cleanstr_helper {

    /**
     * Get a language string with HTML stripped
     *
     * @param string $text The helper arguments
     * @param \Mustache_LambdaHelper $helper The lambda helper
     * @return string Language string without HTML
     */
    public function __invoke(string $text, \Mustache_LambdaHelper $helper): string {
        // Render any Mustache variables first
        $text = $helper->render($text);

        // Parse arguments: identifier, component, param
        $parts = array_map('trim', explode(',', $text));

        $identifier = $parts[0] ?? '';
        $component = $parts[1] ?? 'core';
        $param = $parts[2] ?? null;

        if (empty($identifier)) {
            return '';
        }

        // Get the string
        $string = get_string($identifier, $component, $param);

        // Strip HTML and return
        return strip_tags($string);
    }
}
