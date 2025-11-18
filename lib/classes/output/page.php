<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Page
 *
 * Objeto que representa una página y sus metadatos.
 * Similar a Moodle's $PAGE.
 *
 * @package core\output
 */
class page {

    /** @var string Page title */
    public string $title = 'NexoSupport';

    /** @var string Page heading */
    public string $heading = '';

    /** @var array Breadcrumbs */
    public array $breadcrumbs = [];

    /** @var array CSS URLs */
    public array $css_urls = [];

    /** @var array JS URLs */
    public array $js_urls = [];

    /** @var string Inline CSS */
    public string $inline_css = '';

    /** @var string Inline JS */
    public string $inline_js = '';

    /** @var int Max width */
    public int $maxwidth = 1400;

    /** @var string Page URL */
    public string $url = '';

    /**
     * Set page title
     *
     * @param string $title
     * @return void
     */
    public function set_title(string $title): void {
        $this->title = $title;
    }

    /**
     * Set page heading
     *
     * @param string $heading
     * @return void
     */
    public function set_heading(string $heading): void {
        $this->heading = $heading;
    }

    /**
     * Set max width
     *
     * @param int $width
     * @return void
     */
    public function set_maxwidth(int $width): void {
        $this->maxwidth = $width;
    }

    /**
     * Add breadcrumb
     *
     * @param string $text
     * @param string|null $url
     * @return void
     */
    public function add_breadcrumb(string $text, ?string $url = null): void {
        $this->breadcrumbs[] = [
            'text' => $text,
            'url' => $url
        ];
    }

    /**
     * Add CSS URL
     *
     * @param string $url
     * @return void
     */
    public function add_css(string $url): void {
        if (!in_array($url, $this->css_urls)) {
            $this->css_urls[] = $url;
        }
    }

    /**
     * Add JS URL
     *
     * @param string $url
     * @return void
     */
    public function add_js(string $url): void {
        if (!in_array($url, $this->js_urls)) {
            $this->js_urls[] = $url;
        }
    }

    /**
     * Add inline CSS
     *
     * @param string $css
     * @return void
     */
    public function add_inline_css(string $css): void {
        $this->inline_css .= $css . "\n";
    }

    /**
     * Add inline JS
     *
     * @param string $js
     * @return void
     */
    public function add_inline_js(string $js): void {
        $this->inline_js .= $js . "\n";
    }

    /**
     * Set page URL
     *
     * @param string $url
     * @return void
     */
    public function set_url(string $url): void {
        $this->url = $url;
    }
}
