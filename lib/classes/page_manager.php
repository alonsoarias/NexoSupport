<?php
namespace core;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Page manager class
 *
 * Handles page-level information like URL, title, context, and layout.
 * Similar to Moodle's $PAGE global object.
 *
 * @package core
 */
class page_manager {

    /** @var nexo_url Page URL */
    protected $url = null;

    /** @var string Page title */
    protected $title = '';

    /** @var \core\rbac\context Page context for RBAC */
    protected $context = null;

    /** @var string Page layout type */
    protected $pagelayout = 'standard';

    /** @var array Additional page data */
    protected $data = [];

    /**
     * Set the page URL
     *
     * @param nexo_url|string $url Page URL
     * @return void
     */
    public function set_url($url): void {
        if (is_string($url)) {
            $url = new nexo_url($url);
        }
        $this->url = $url;
    }

    /**
     * Get the page URL
     *
     * @return nexo_url|null
     */
    public function get_url(): ?nexo_url {
        return $this->url;
    }

    /**
     * Set the page title
     *
     * @param string $title Page title
     * @return void
     */
    public function set_title(string $title): void {
        $this->title = $title;
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Set the page context
     *
     * @param \core\rbac\context $context
     * @return void
     */
    public function set_context(\core\rbac\context $context): void {
        $this->context = $context;
    }

    /**
     * Get the page context
     *
     * @return \core\rbac\context|null
     */
    public function get_context(): ?\core\rbac\context {
        return $this->context;
    }

    /**
     * Set the page layout type
     *
     * @param string $layout Layout type (standard, admin, report, etc.)
     * @return void
     */
    public function set_pagelayout(string $layout): void {
        $this->pagelayout = $layout;
    }

    /**
     * Get the page layout type
     *
     * @return string
     */
    public function get_pagelayout(): string {
        return $this->pagelayout;
    }

    /**
     * Set custom page data
     *
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function set_data(string $key, $value): void {
        $this->data[$key] = $value;
    }

    /**
     * Get custom page data
     *
     * @param string $key Data key
     * @param mixed $default Default value if not set
     * @return mixed
     */
    public function get_data(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }
}
