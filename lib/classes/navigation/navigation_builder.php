<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Builder
 *
 * Provides a fluent API for building navigation trees. Simplifies
 * the process of creating complex hierarchical navigation structures.
 *
 * @package core\navigation
 */
class navigation_builder {

    /** @var navigation_tree Navigation tree being built */
    private navigation_tree $tree;

    /** @var array Pending nodes waiting for parents */
    private array $pending = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->tree = new navigation_tree();
    }

    /**
     * Add a category node
     *
     * @param string $key Unique key
     * @param array $config Configuration
     * @return self
     */
    public function add_category(string $key, array $config): self {
        $config['type'] = navigation_node::TYPE_CATEGORY;
        $node = new navigation_node($key, navigation_node::TYPE_CATEGORY, $config);

        $parent_key = $config['parent'] ?? null;
        $this->add_node_to_tree($node, $parent_key);

        return $this;
    }

    /**
     * Add an item node
     *
     * @param string $key Unique key
     * @param array $config Configuration
     * @return self
     */
    public function add_item(string $key, array $config): self {
        $node = new navigation_node($key, navigation_node::TYPE_ITEM, $config);

        $parent_key = $config['parent'] ?? null;
        $this->add_node_to_tree($node, $parent_key);

        return $this;
    }

    /**
     * Add a separator node
     *
     * @param string $key Unique key
     * @param array $config Configuration
     * @return self
     */
    public function add_separator(string $key, array $config = []): self {
        $node = new navigation_node($key, navigation_node::TYPE_SEPARATOR, $config);

        $parent_key = $config['parent'] ?? null;
        $this->add_node_to_tree($node, $parent_key);

        return $this;
    }

    /**
     * Add node to tree
     *
     * @param navigation_node $node
     * @param string|null $parent_key
     * @return void
     */
    private function add_node_to_tree(navigation_node $node, ?string $parent_key): void {
        // If no parent or parent exists, add directly
        if ($parent_key === null || $this->tree->get_node($parent_key) !== null) {
            $this->tree->add_node($node, $parent_key);

            // Process any pending children of this node
            $this->process_pending_children($node->get_key());
        } else {
            // Parent doesn't exist yet, add to pending
            if (!isset($this->pending[$parent_key])) {
                $this->pending[$parent_key] = [];
            }
            $this->pending[$parent_key][] = $node;
        }
    }

    /**
     * Process pending children for a parent
     *
     * @param string $parent_key
     * @return void
     */
    private function process_pending_children(string $parent_key): void {
        if (!isset($this->pending[$parent_key])) {
            return;
        }

        foreach ($this->pending[$parent_key] as $node) {
            $this->tree->add_node($node, $parent_key);
        }

        unset($this->pending[$parent_key]);
    }

    /**
     * Set active node by key
     *
     * @param string $key
     * @return self
     */
    public function set_active(string $key): self {
        $this->tree->set_active_node($key);
        return $this;
    }

    /**
     * Set active node by URL
     *
     * @param string $url
     * @return self
     */
    public function set_active_by_url(string $url): self {
        $node = $this->tree->find_by_url($url);
        if ($node !== null) {
            $this->tree->set_active_node($node);
        }
        return $this;
    }

    /**
     * Build and return the tree
     *
     * @param bool $filter_permissions Apply permission filtering?
     * @return navigation_tree
     */
    public function build(bool $filter_permissions = true): navigation_tree {
        // Warn about pending nodes
        if (!empty($this->pending)) {
            debugging('Navigation Builder: ' . count($this->pending) . ' nodes could not find their parents', DEBUG_DEVELOPER);
        }

        // Apply permission filtering
        if ($filter_permissions) {
            $this->tree->filter_by_permissions();
        }

        return $this->tree;
    }

    /**
     * Get the tree (alias for build)
     *
     * @param bool $filter_permissions
     * @return navigation_tree
     */
    public function get_tree(bool $filter_permissions = true): navigation_tree {
        return $this->build($filter_permissions);
    }

    /**
     * Quick helper: Add home
     *
     * @param array $config Additional configuration
     * @return self
     */
    public function add_home(array $config = []): self {
        return $this->add_item('home', array_merge([
            'text' => get_string('home', 'core'),
            'url' => '/',
            'icon' => 'fa-home',
            'order' => 1,
        ], $config));
    }

    /**
     * Quick helper: Add dashboard
     *
     * @param array $config Additional configuration
     * @return self
     */
    public function add_dashboard(array $config = []): self {
        return $this->add_item('dashboard', array_merge([
            'text' => get_string('dashboard', 'core'),
            'url' => '/',
            'icon' => 'fa-tachometer-alt',
            'order' => 2,
        ], $config));
    }

    /**
     * Quick helper: Add admin category
     *
     * @param array $config Additional configuration
     * @return self
     */
    public function add_admin_category(array $config = []): self {
        return $this->add_category('siteadmin', array_merge([
            'text' => get_string('administration', 'core'),
            'icon' => 'fa-cogs',
            'capability' => 'nexosupport/admin:managesettings',
            'order' => 100,
        ], $config));
    }

    /**
     * Debug: Print tree
     *
     * @return string
     */
    public function debug(): string {
        return $this->tree->debug_print();
    }
}
