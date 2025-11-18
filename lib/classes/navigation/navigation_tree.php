<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Tree
 *
 * Manages the complete navigation tree structure. Handles building
 * hierarchy, filtering by permissions, finding nodes, and generating
 * breadcrumbs.
 *
 * @package core\navigation
 */
class navigation_tree {

    /** @var array All nodes indexed by key */
    private array $nodes = [];

    /** @var array Root nodes (no parent) */
    private array $roots = [];

    /** @var navigation_node|null Currently active node */
    private ?navigation_node $active_node = null;

    /** @var string Current URL path for active detection */
    private string $current_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->current_path = $this->get_current_path();
    }

    /**
     * Get current URL path
     *
     * @return string
     */
    private function get_current_path(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Add node to tree
     *
     * @param navigation_node $node
     * @param string|null $parent_key Parent node key
     * @return self
     */
    public function add_node(navigation_node $node, ?string $parent_key = null): self {
        $this->nodes[$node->get_key()] = $node;

        if ($parent_key !== null) {
            if (isset($this->nodes[$parent_key])) {
                $this->nodes[$parent_key]->add_child($node);
            }
        } else {
            $this->roots[$node->get_key()] = $node;
        }

        // Check if this node should be active
        if ($node->get_url() !== null) {
            $node_path = parse_url($node->get_url(), PHP_URL_PATH);
            if ($node_path === $this->current_path) {
                $node->set_active(true);
                $this->active_node = $node;
                $this->expand_to_node($node);
            }
        }

        return $this;
    }

    /**
     * Get all nodes
     *
     * @return array
     */
    public function get_all_nodes(): array {
        return $this->nodes;
    }

    /**
     * Get root nodes
     *
     * @param bool $sorted Sort by order?
     * @return array
     */
    public function get_roots(bool $sorted = true): array {
        if (!$sorted) {
            return $this->roots;
        }

        $roots = $this->roots;
        uasort($roots, function($a, $b) {
            return $a->get_order() <=> $b->get_order();
        });

        return $roots;
    }

    /**
     * Get node by key
     *
     * @param string $key
     * @return navigation_node|null
     */
    public function get_node(string $key): ?navigation_node {
        return $this->nodes[$key] ?? null;
    }

    /**
     * Find node by URL
     *
     * @param string $url
     * @return navigation_node|null
     */
    public function find_by_url(string $url): ?navigation_node {
        $search_path = parse_url($url, PHP_URL_PATH);

        foreach ($this->nodes as $node) {
            if ($node->get_url() !== null) {
                $node_path = parse_url($node->get_url(), PHP_URL_PATH);
                if ($node_path === $search_path) {
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * Search nodes by text
     *
     * @param string $query Search query
     * @return array Matching nodes
     */
    public function search(string $query): array {
        $query = strtolower($query);
        $results = [];

        foreach ($this->nodes as $node) {
            $text = strtolower($node->get_text() ?? '');
            if (str_contains($text, $query)) {
                $results[] = $node;
            }
        }

        return $results;
    }

    /**
     * Get active node
     *
     * @return navigation_node|null
     */
    public function get_active_node(): ?navigation_node {
        return $this->active_node;
    }

    /**
     * Set active node
     *
     * @param navigation_node|string $node Node or node key
     * @return self
     */
    public function set_active_node(navigation_node|string $node): self {
        if (is_string($node)) {
            $node = $this->get_node($node);
        }

        if ($node !== null) {
            // Deactivate previous active node
            if ($this->active_node !== null) {
                $this->active_node->set_active(false);
            }

            // Set new active node
            $node->set_active(true);
            $this->active_node = $node;

            // Expand all parents
            $this->expand_to_node($node);
        }

        return $this;
    }

    /**
     * Expand all parent nodes to a given node
     *
     * @param navigation_node $node
     * @return self
     */
    private function expand_to_node(navigation_node $node): self {
        $parent = $node->get_parent();
        while ($parent !== null) {
            $parent->set_expanded(true);
            $parent = $parent->get_parent();
        }
        return $this;
    }

    /**
     * Get breadcrumbs to active node
     *
     * @return array Array of nodes from root to active
     */
    public function get_breadcrumbs(): array {
        if ($this->active_node === null) {
            return [];
        }

        $breadcrumbs = [];
        $node = $this->active_node;

        while ($node !== null) {
            array_unshift($breadcrumbs, $node);
            $node = $node->get_parent();
        }

        return $breadcrumbs;
    }

    /**
     * Filter tree by user permissions
     *
     * Removes nodes that user doesn't have access to.
     *
     * @param int|null $userid User ID (null = current user)
     * @param object|null $context Context (null = system)
     * @return self
     */
    public function filter_by_permissions(?int $userid = null, ?object $context = null): self {
        // Filter roots
        foreach ($this->roots as $key => $node) {
            if (!$this->filter_node($node, $userid, $context)) {
                unset($this->roots[$key]);
                unset($this->nodes[$key]);
            }
        }

        return $this;
    }

    /**
     * Recursively filter node and children by permissions
     *
     * @param navigation_node $node
     * @param int|null $userid
     * @param object|null $context
     * @return bool True if node should be kept
     */
    private function filter_node(navigation_node $node, ?int $userid, ?object $context): bool {
        // Check access
        if (!$node->check_access($userid, $context)) {
            // Remove from parent if exists
            $parent = $node->get_parent();
            if ($parent !== null) {
                $parent->remove_child($node->get_key());
            }
            return false;
        }

        // Filter children
        if ($node->has_children()) {
            foreach ($node->get_children(false) as $child) {
                $this->filter_node($child, $userid, $context);
            }

            // If category has no children left after filtering, hide it
            if ($node->get_type() === navigation_node::TYPE_CATEGORY && !$node->has_children()) {
                $node->set_visible(false);
            }
        }

        return true;
    }

    /**
     * Get tree depth
     *
     * @return int Maximum depth of tree
     */
    public function get_depth(): int {
        $max_depth = 0;

        foreach ($this->roots as $root) {
            $depth = $this->get_node_depth($root);
            $max_depth = max($max_depth, $depth);
        }

        return $max_depth;
    }

    /**
     * Get depth of a node
     *
     * @param navigation_node $node
     * @param int $current_depth
     * @return int
     */
    private function get_node_depth(navigation_node $node, int $current_depth = 0): int {
        if (!$node->has_children()) {
            return $current_depth;
        }

        $max_child_depth = $current_depth;
        foreach ($node->get_children() as $child) {
            $child_depth = $this->get_node_depth($child, $current_depth + 1);
            $max_child_depth = max($max_child_depth, $child_depth);
        }

        return $max_child_depth;
    }

    /**
     * Export tree as array (for templates)
     *
     * @return array
     */
    public function to_array(): array {
        $roots = [];
        foreach ($this->get_roots() as $root) {
            if ($root->is_visible()) {
                $roots[] = $root->to_array(true);
            }
        }

        return [
            'nodes' => $roots,
            'has_nodes' => !empty($roots),
            'active_node' => $this->active_node ? $this->active_node->to_array(false) : null,
            'breadcrumbs' => array_map(fn($node) => $node->to_array(false), $this->get_breadcrumbs()),
            'has_breadcrumbs' => !empty($this->get_breadcrumbs()),
            'depth' => $this->get_depth(),
        ];
    }

    /**
     * Debug: Print tree structure
     *
     * @return string
     */
    public function debug_print(): string {
        $output = "Navigation Tree:\n";
        $output .= "================\n\n";

        foreach ($this->get_roots() as $root) {
            $output .= $this->debug_print_node($root, 0);
        }

        $output .= "\nActive Node: " . ($this->active_node ? $this->active_node->get_key() : 'none') . "\n";
        $output .= "Total Nodes: " . count($this->nodes) . "\n";
        $output .= "Root Nodes: " . count($this->roots) . "\n";
        $output .= "Tree Depth: " . $this->get_depth() . "\n";

        return $output;
    }

    /**
     * Debug: Print node and children
     *
     * @param navigation_node $node
     * @param int $level
     * @return string
     */
    private function debug_print_node(navigation_node $node, int $level): string {
        $indent = str_repeat('  ', $level);
        $icon = $node->get_icon() ? '[' . $node->get_icon() . '] ' : '';
        $active = $node->is_active() ? ' (ACTIVE)' : '';
        $expanded = $node->is_expanded() ? ' (EXPANDED)' : '';

        $output = $indent . $icon . $node->get_text() . $active . $expanded . "\n";

        if ($node->has_children()) {
            foreach ($node->get_children() as $child) {
                $output .= $this->debug_print_node($child, $level + 1);
            }
        }

        return $output;
    }
}
