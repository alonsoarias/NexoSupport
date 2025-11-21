<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * External admin page
 *
 * Similar to Moodle's admin_externalpage class.
 * Links to custom PHP pages within the admin tree.
 *
 * @package core\admin
 */
class admin_externalpage implements part_of_admin_tree {

    /** @var string Unique page name */
    public string $name;

    /** @var string Visible title */
    public string $visiblename;

    /** @var string URL to the external page */
    public string $url;

    /** @var string|array Required capability(ies) */
    public $req_capability;

    /** @var int Context level for capability check */
    public int $context;

    /** @var bool Whether this page is hidden */
    public bool $hidden = false;

    /** @var string Optional CSS class for icon */
    public string $iconclass = '';

    /**
     * Constructor
     *
     * @param string $name Unique name
     * @param string $visiblename Visible title
     * @param string $url URL to the page
     * @param string|array $req_capability Required capability(ies)
     * @param bool $hidden Whether hidden
     * @param int $context Context level (default CONTEXT_SYSTEM)
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $url,
        $req_capability = 'nexosupport/admin:manageconfig',
        bool $hidden = false,
        int $context = CONTEXT_SYSTEM
    ) {
        $this->name = $name;
        $this->visiblename = $visiblename;
        $this->url = $url;
        $this->req_capability = $req_capability;
        $this->hidden = $hidden;
        $this->context = $context;
    }

    /**
     * Locate this page by name
     *
     * @param string $name Name to find
     * @return part_of_admin_tree|null This if name matches, null otherwise
     */
    public function locate(string $name): ?part_of_admin_tree {
        if ($this->name === $name) {
            return $this;
        }
        return null;
    }

    /**
     * Can't prune self
     *
     * @param string $name Name to prune
     * @return bool Always false (parent handles pruning)
     */
    public function prune(string $name): bool {
        return false;
    }

    /**
     * Search for text in this page
     *
     * @param string $query Search query
     * @return array This page if matches, empty array otherwise
     */
    public function search(string $query): array {
        $query = strtolower($query);
        if (strpos(strtolower($this->name), $query) !== false ||
            strpos(strtolower($this->visiblename), $query) !== false) {
            return [$this];
        }
        return [];
    }

    /**
     * Check if user has access to this page
     *
     * @return bool True if accessible
     */
    public function check_access(): bool {
        if (is_array($this->req_capability)) {
            foreach ($this->req_capability as $cap) {
                if (has_capability($cap, $this->context)) {
                    return true;
                }
            }
            return false;
        }
        return has_capability($this->req_capability, $this->context);
    }

    /**
     * Check if this page is hidden
     *
     * @return bool True if hidden
     */
    public function is_hidden(): bool {
        return $this->hidden;
    }

    /**
     * External pages don't show save button
     *
     * @return bool Always false
     */
    public function show_save(): bool {
        return false;
    }

    /**
     * Get page name
     *
     * @return string Page name
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get visible name
     *
     * @return string Visible name
     */
    public function get_visiblename(): string {
        return $this->visiblename;
    }

    /**
     * Get the URL
     *
     * @return string URL
     */
    public function get_url(): string {
        return $this->url;
    }

    /**
     * Set icon class
     *
     * @param string $class CSS class for icon
     * @return void
     */
    public function set_icon(string $class): void {
        $this->iconclass = $class;
    }

    /**
     * Get template data for navigation
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'url' => $this->url,
            'iconclass' => $this->iconclass,
            'isexternal' => true,
        ];
    }
}
