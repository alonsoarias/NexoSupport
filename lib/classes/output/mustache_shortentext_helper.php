<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Shorten Text Helper
 *
 * Shortens text to a specified number of characters.
 * Usage: {{#shortentext}}length, text to shorten{{/shortentext}}
 *
 * Example:
 *   {{#shortentext}}50, {{description}}{{/shortentext}}
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_shortentext_helper {

    /**
     * Shorten text
     *
     * @param string $text The helper arguments (length, text)
     * @param \Mustache\LambdaHelper $helper The lambda helper
     * @return string Shortened text
     */
    public function __invoke(string $text, \Mustache\LambdaHelper $helper): string {
        // Render any Mustache variables first
        $text = $helper->render($text);

        // Parse arguments: length, text
        $commapos = strpos($text, ',');
        if ($commapos === false) {
            return $text;
        }

        $length = (int) trim(substr($text, 0, $commapos));
        $content = trim(substr($text, $commapos + 1));

        if ($length <= 0) {
            return $content;
        }

        return $this->shorten($content, $length);
    }

    /**
     * Shorten text to a maximum length
     *
     * @param string $text Text to shorten
     * @param int $length Maximum length
     * @param string $suffix Suffix to add if shortened
     * @return string Shortened text
     */
    protected function shorten(string $text, int $length, string $suffix = '...'): string {
        // Strip HTML tags first
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Trim whitespace
        $text = trim($text);

        // Check if shortening is needed
        if (mb_strlen($text) <= $length) {
            return htmlspecialchars($text);
        }

        // Shorten to length
        $shortened = mb_substr($text, 0, $length);

        // Try to break at word boundary
        $lastspace = mb_strrpos($shortened, ' ');
        if ($lastspace !== false && $lastspace > $length * 0.8) {
            $shortened = mb_substr($shortened, 0, $lastspace);
        }

        return htmlspecialchars(trim($shortened) . $suffix);
    }
}
