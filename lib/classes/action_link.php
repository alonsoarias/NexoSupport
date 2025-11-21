<?php
/**
 * Action link class.
 *
 * @package    core
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('INTERNAL_ACCESS') || die();

/**
 * Represents an action link with URL and text.
 *
 * Used for check results to provide action links to fix issues.
 */
class action_link {

    /** @var moodle_url The URL for the action */
    public moodle_url $url;

    /** @var string The link text */
    public string $text;

    /** @var array Additional HTML attributes */
    public array $attributes = [];

    /** @var string Optional icon */
    public string $icon = '';

    /**
     * Create a new action link.
     *
     * @param moodle_url|string $url The URL for the action
     * @param string $text The link text
     * @param array $attributes Additional HTML attributes
     * @param string $icon Optional icon name
     */
    public function __construct($url, string $text, array $attributes = [], string $icon = '') {
        if ($url instanceof moodle_url) {
            $this->url = $url;
        } else {
            $this->url = new moodle_url($url);
        }

        $this->text = $text;
        $this->attributes = $attributes;
        $this->icon = $icon;
    }

    /**
     * Render the action link as HTML.
     *
     * @return string HTML link
     */
    public function render(): string {
        $attrs = $this->attributes;
        $attrs['href'] = $this->url->out();

        $html = '<a';
        foreach ($attrs as $name => $value) {
            $html .= ' ' . htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';

        if ($this->icon) {
            $html .= '<i class="fa fa-' . htmlspecialchars($this->icon) . ' mr-1"></i>';
        }

        $html .= htmlspecialchars($this->text);
        $html .= '</a>';

        return $html;
    }

    /**
     * Convert to string.
     *
     * @return string The HTML link
     */
    public function __toString(): string {
        return $this->render();
    }
}
