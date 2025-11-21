<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Quote Helper
 *
 * Escapes content for safe use in JavaScript strings.
 * Usage: {{#quote}}text to escape{{/quote}}
 *
 * Example:
 *   <script>
 *   var message = {{#quote}}{{usermessage}}{{/quote}};
 *   alert(message);
 *   </script>
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_quote_helper {

    /**
     * Escape content for JavaScript
     *
     * @param string $text The text to escape
     * @param \Mustache\LambdaHelper $helper The lambda helper
     * @return string Escaped and quoted string
     */
    public function __invoke(string $text, \Mustache\LambdaHelper $helper): string {
        // Render any Mustache variables first
        $text = $helper->render($text);

        // JSON encode handles all the escaping we need
        return json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
