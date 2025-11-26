<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Primary Navigation
 *
 * Manages the primary navigation (header/top bar) following Moodle 4.x architecture.
 * Contains site-wide navigation items visible to all authenticated users.
 *
 * @package core\navigation
 */
class primary_navigation {

    /** @var array Navigation nodes */
    protected array $nodes = [];

    /** @var string Currently active node key */
    protected string $active_key = '';

    /** @var string Current URL path */
    protected string $current_path;

    /**
     * Constructor
     *
     * Initializes the primary navigation and populates with site navigation items.
     */
    public function __construct() {
        $this->current_path = $this->get_current_path();
        $this->populate_site_navigation();
    }

    /**
     * Get current URL path
     *
     * @return string
     */
    protected function get_current_path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Add a navigation node
     *
     * @param navigation_node $node
     * @return self
     */
    public function add_node(navigation_node $node): self {
        $this->nodes[$node->get_key()] = $node;

        // Check if this node should be active based on URL
        $node_url = $node->get_url();
        if ($node_url !== null) {
            $node_path = parse_url($node_url, PHP_URL_PATH);
            if ($this->is_active_path($node_path)) {
                $this->set_active($node->get_key());
            }
        }

        return $this;
    }

    /**
     * Check if a path is active (matches current path or is a parent)
     *
     * @param string $path
     * @return bool
     */
    protected function is_active_path(string $path): bool {
        if ($path === $this->current_path) {
            return true;
        }

        // Check if current path starts with node path (for nested routes)
        if ($path !== '/' && str_starts_with($this->current_path, $path)) {
            return true;
        }

        return false;
    }

    /**
     * Set active node
     *
     * @param string $key Node key
     * @return self
     */
    public function set_active(string $key): self {
        // Deactivate previous active node
        if (!empty($this->active_key) && isset($this->nodes[$this->active_key])) {
            $this->nodes[$this->active_key]->set_active(false);
        }

        // Activate new node
        if (isset($this->nodes[$key])) {
            $this->nodes[$key]->set_active(true);
            $this->active_key = $key;
        }

        return $this;
    }

    /**
     * Get all nodes
     *
     * @return array
     */
    public function get_nodes(): array {
        // Sort by order
        $nodes = $this->nodes;
        uasort($nodes, function($a, $b) {
            return $a->get_order() <=> $b->get_order();
        });

        return $nodes;
    }

    /**
     * Get visible nodes (filtered by permissions)
     *
     * @return array
     */
    public function get_visible_nodes(): array {
        $visible = [];

        foreach ($this->get_nodes() as $key => $node) {
            if ($node->is_visible() && $node->check_access()) {
                $visible[$key] = $node;
            }
        }

        return $visible;
    }

    /**
     * Populate site navigation with default items
     *
     * @return void
     */
    protected function populate_site_navigation(): void {
        global $USER;

        // Dashboard / Home
        $this->add_node(new navigation_node('home', navigation_node::TYPE_ITEM, [
            'text' => get_string('dashboard', 'core'),
            'url' => '/',
            'icon' => 'fa-home',
            'order' => 10,
        ]));

        // Administration (only for users with admin capability)
        $this->add_node(new navigation_node('admin', navigation_node::TYPE_ITEM, [
            'text' => get_string('administration', 'core'),
            'url' => '/admin',
            'icon' => 'fa-cogs',
            'order' => 20,
            'capability' => 'nexosupport/admin:view',
        ]));

        // My Profile
        $this->add_node(new navigation_node('profile', navigation_node::TYPE_ITEM, [
            'text' => get_string('profile', 'core'),
            'url' => '/user/profile',
            'icon' => 'fa-user',
            'order' => 30,
        ]));
    }

    /**
     * Export for template
     *
     * @return array
     */
    public function export_for_template(): array {
        $nodes = [];

        foreach ($this->get_visible_nodes() as $node) {
            $nodes[] = $node->to_array(false);
        }

        return [
            'nodes' => $nodes,
            'has_nodes' => !empty($nodes),
            'active_key' => $this->active_key,
        ];
    }
}
