<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache JavaScript Helper
 *
 * Collects JavaScript code to be executed at the end of the page.
 * Usage: {{#js}}JavaScript code here{{/js}}
 *
 * The JavaScript is NOT rendered inline - it's collected and output
 * at the end of the page for proper execution order.
 *
 * Example:
 *   {{#js}}
 *   require(['core/modal'], function(Modal) {
 *       Modal.create({title: '{{title}}'}).show();
 *   });
 *   {{/js}}
 *
 * SECURITY: This helper should NOT be nested inside other helpers
 * to prevent JavaScript injection attacks.
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_javascript_helper {

    /** @var array Collected JavaScript code */
    private static array $jscode = [];

    /**
     * Collect JavaScript code
     *
     * @param string $text The JavaScript code
     * @param \Mustache_LambdaHelper $helper The lambda helper
     * @return string Empty string (JS is collected, not rendered inline)
     */
    public function __invoke(string $text, \Mustache_LambdaHelper $helper): string {
        // Render any Mustache variables in the JavaScript
        $js = $helper->render($text);

        // Store for later output
        self::$jscode[] = trim($js);

        // Return empty - JavaScript will be output at end of page
        return '';
    }

    /**
     * Get all collected JavaScript
     *
     * @param bool $clear Whether to clear the collection after getting
     * @return array Array of JavaScript code blocks
     */
    public static function get_javascript(bool $clear = true): array {
        $code = self::$jscode;

        if ($clear) {
            self::$jscode = [];
        }

        return $code;
    }

    /**
     * Get JavaScript as a single string wrapped in script tags
     *
     * @param bool $clear Whether to clear the collection after getting
     * @return string JavaScript code with script tags, or empty string
     */
    public static function get_javascript_html(bool $clear = true): string {
        $code = self::get_javascript($clear);

        if (empty($code)) {
            return '';
        }

        $html = "<script>\n";
        $html .= "document.addEventListener('DOMContentLoaded', function() {\n";
        $html .= implode("\n\n", $code);
        $html .= "\n});\n";
        $html .= "</script>\n";

        return $html;
    }

    /**
     * Check if there is any collected JavaScript
     *
     * @return bool True if there is JavaScript waiting
     */
    public static function has_javascript(): bool {
        return !empty(self::$jscode);
    }

    /**
     * Clear all collected JavaScript
     *
     * @return void
     */
    public static function clear(): void {
        self::$jscode = [];
    }
}
