<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache String Helper
 *
 * Provides {{#str}} helper for language string interpolation in templates.
 * Compatible with Moodle's string helper format.
 *
 * Usage formats:
 *   {{#str}} key, component {{/str}}
 *   {{#str}} key, component, parameter {{/str}}
 *   {{#str}} key, component, {{jsonparam}} {{/str}}
 *
 * Examples:
 *   {{#str}} yes {{/str}}                    -> get_string('yes')
 *   {{#str}} login, core {{/str}}            -> get_string('login', 'core')
 *   {{#str}} greeting, core, John {{/str}}   -> get_string('greeting', 'core', 'John')
 *   {{#str}} items, core, {"count": 5} {{/str}} -> get_string('items', 'core', ['count' => 5])
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_string_helper {

    /**
     * Invoke the helper
     *
     * @param string $text The template text containing the arguments
     * @param \Mustache_LambdaHelper $helper Mustache helper instance
     * @return string The localized string
     */
    public function __invoke(string $text, \Mustache_LambdaHelper $helper): string {
        // First render any variables in the text
        $text = $helper->render($text);
        $text = trim($text);

        if (empty($text)) {
            return '';
        }

        // Parse the arguments
        [$identifier, $component, $a] = $this->parse_arguments($text);

        if (empty($identifier)) {
            debugging('String helper called without identifier', DEBUG_DEVELOPER);
            return '';
        }

        // Get the translated string
        return get_string($identifier, $component, $a);
    }

    /**
     * Parse the helper arguments
     *
     * @param string $text Raw argument text
     * @return array [identifier, component, a]
     */
    private function parse_arguments(string $text): array {
        $identifier = '';
        $component = 'core';
        $a = null;

        // Try to parse JSON parameter first
        // Check if there's a JSON object in the text
        if (preg_match('/\{["\']?\w+/', $text)) {
            // May contain JSON, handle differently
            $parts = $this->split_with_json($text);
        } else {
            // Simple comma-separated values
            $parts = array_map('trim', explode(',', $text, 3));
        }

        // Extract parts
        $identifier = $parts[0] ?? '';
        $component = $parts[1] ?? 'core';
        $rawparam = $parts[2] ?? null;

        // Process the parameter
        if ($rawparam !== null) {
            $rawparam = trim($rawparam);

            // Try to decode as JSON
            if (!empty($rawparam) && ($rawparam[0] === '{' || $rawparam[0] === '[')) {
                $decoded = json_decode($rawparam, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $a = $decoded;
                } else {
                    $a = $rawparam;
                }
            } else {
                $a = $rawparam;
            }
        }

        // Clean up values
        $identifier = trim($identifier);
        $component = trim($component);

        // Default to 'core' if component is empty
        if (empty($component)) {
            $component = 'core';
        }

        return [$identifier, $component, $a];
    }

    /**
     * Split text into parts while preserving JSON objects
     *
     * @param string $text Text to split
     * @return array Parts
     */
    private function split_with_json(string $text): array {
        $parts = [];
        $current = '';
        $depth = 0;
        $inJson = false;
        $chars = str_split($text);

        foreach ($chars as $i => $char) {
            if ($char === '{' || $char === '[') {
                $depth++;
                $inJson = $depth > 0;
            } elseif ($char === '}' || $char === ']') {
                $depth--;
                if ($depth === 0) {
                    $inJson = false;
                }
            }

            if ($char === ',' && !$inJson && count($parts) < 2) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Add remaining text
        if ($current !== '' || count($parts) > 0) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Create helper callable for direct use
     *
     * @return callable
     */
    public static function create(): callable {
        $instance = new self();
        return function($text, $helper) use ($instance) {
            return $instance($text, $helper);
        };
    }
}
