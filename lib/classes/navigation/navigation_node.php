<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Navigation Node
 *
 * Represents a single node in the navigation tree. Can be a category,
 * item, or separator. Supports hierarchical structure with parent-child
 * relationships.
 *
 * @package core\navigation
 */
class navigation_node {

    /** Node types */
    const TYPE_CATEGORY = 'category';
    const TYPE_ITEM = 'item';
    const TYPE_SEPARATOR = 'separator';

    /** @var string Unique key for this node */
    private string $key;

    /** @var string Node type (category, item, separator) */
    private string $type;

    /** @var string Display text */
    private ?string $text = null;

    /** @var string URL for navigation items */
    private ?string $url = null;

    /** @var string Icon (Font Awesome class or emoji) */
    private ?string $icon = null;

    /** @var int Sort order */
    private int $order = 999;

    /** @var bool Is this node visible? */
    private bool $visible = true;

    /** @var bool Is this node active/selected? */
    private bool $active = false;

    /** @var bool Is this node expanded? */
    private bool $expanded = false;

    /** @var string|null Single capability required */
    private ?string $capability = null;

    /** @var array Multiple capabilities (any match) */
    private array $capabilities = [];

    /** @var navigation_node|null Parent node */
    private ?navigation_node $parent = null;

    /** @var array Child nodes */
    private array $children = [];

    /** @var array Additional custom data */
    private array $data = [];

    /**
     * Constructor
     *
     * @param string $key Unique key
     * @param string $type Node type
     * @param array $config Configuration array
     */
    public function __construct(string $key, string $type, array $config = []) {
        $this->key = $key;
        $this->type = $type;

        // Set properties from config
        $this->text = $config['text'] ?? null;
        $this->url = $config['url'] ?? null;
        $this->icon = $config['icon'] ?? null;
        $this->order = $config['order'] ?? 999;
        $this->visible = $config['visible'] ?? true;
        $this->active = $config['active'] ?? false;
        $this->expanded = $config['expanded'] ?? false;

        // Capabilities
        if (isset($config['capability'])) {
            $this->capability = $config['capability'];
        }
        if (isset($config['capabilities'])) {
            $this->capabilities = (array)$config['capabilities'];
        }

        // Additional data
        if (isset($config['data'])) {
            $this->data = $config['data'];
        }
    }

    /**
     * Get node key
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Get node type
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get display text
     *
     * @return string|null
     */
    public function get_text(): ?string {
        return $this->text;
    }

    /**
     * Set display text
     *
     * @param string $text
     * @return self
     */
    public function set_text(string $text): self {
        $this->text = $text;
        return $this;
    }

    /**
     * Get URL
     *
     * @return string|null
     */
    public function get_url(): ?string {
        return $this->url;
    }

    /**
     * Set URL
     *
     * @param string $url
     * @return self
     */
    public function set_url(string $url): self {
        $this->url = $url;
        return $this;
    }

    /**
     * Get icon
     *
     * @return string|null
     */
    public function get_icon(): ?string {
        return $this->icon;
    }

    /**
     * Set icon
     *
     * @param string $icon Font Awesome class (e.g., 'fa-home') or emoji
     * @return self
     */
    public function set_icon(string $icon): self {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function get_order(): int {
        return $this->order;
    }

    /**
     * Set sort order
     *
     * @param int $order
     * @return self
     */
    public function set_order(int $order): self {
        $this->order = $order;
        return $this;
    }

    /**
     * Is node visible?
     *
     * @return bool
     */
    public function is_visible(): bool {
        return $this->visible;
    }

    /**
     * Set visibility
     *
     * @param bool $visible
     * @return self
     */
    public function set_visible(bool $visible): self {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Is node active/selected?
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->active;
    }

    /**
     * Set active state
     *
     * @param bool $active
     * @return self
     */
    public function set_active(bool $active): self {
        $this->active = $active;
        return $this;
    }

    /**
     * Is node expanded?
     *
     * @return bool
     */
    public function is_expanded(): bool {
        return $this->expanded;
    }

    /**
     * Set expanded state
     *
     * @param bool $expanded
     * @return self
     */
    public function set_expanded(bool $expanded): self {
        $this->expanded = $expanded;
        return $this;
    }

    /**
     * Get parent node
     *
     * @return navigation_node|null
     */
    public function get_parent(): ?navigation_node {
        return $this->parent;
    }

    /**
     * Set parent node
     *
     * @param navigation_node|null $parent
     * @return self
     */
    public function set_parent(?navigation_node $parent): self {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Add child node
     *
     * @param navigation_node $child
     * @return self
     */
    public function add_child(navigation_node $child): self {
        $child->set_parent($this);
        $this->children[$child->get_key()] = $child;
        return $this;
    }

    /**
     * Get child nodes
     *
     * @param bool $sorted Sort by order?
     * @return array
     */
    public function get_children(bool $sorted = true): array {
        if (!$sorted) {
            return $this->children;
        }

        $children = $this->children;
        uasort($children, function($a, $b) {
            return $a->get_order() <=> $b->get_order();
        });

        return $children;
    }

    /**
     * Has children?
     *
     * @return bool
     */
    public function has_children(): bool {
        return !empty($this->children);
    }

    /**
     * Get child by key
     *
     * @param string $key
     * @return navigation_node|null
     */
    public function get_child(string $key): ?navigation_node {
        return $this->children[$key] ?? null;
    }

    /**
     * Remove child by key
     *
     * @param string $key
     * @return self
     */
    public function remove_child(string $key): self {
        if (isset($this->children[$key])) {
            $this->children[$key]->set_parent(null);
            unset($this->children[$key]);
        }
        return $this;
    }

    /**
     * Check if user has required capabilities
     *
     * Checks both single capability and multiple capabilities (OR logic).
     *
     * @param int|null $userid User ID (null = current user)
     * @param object|null $context Context (null = system context)
     * @return bool
     */
    public function check_access(?int $userid = null, ?object $context = null): bool {
        global $USER;

        // Site admins bypass all checks
        if (is_siteadmin($userid)) {
            return true;
        }

        // If no capabilities required, allow access
        if ($this->capability === null && empty($this->capabilities)) {
            return true;
        }

        $userid = $userid ?? ($USER->id ?? 0);
        $context = $context ?? \core\rbac\context::system();

        // Check single capability
        if ($this->capability !== null) {
            if (has_capability($this->capability, $context, $userid)) {
                return true;
            }
        }

        // Check multiple capabilities (OR logic - any match)
        foreach ($this->capabilities as $cap) {
            if (has_capability($cap, $context, $userid)) {
                return true;
            }
        }

        // No capabilities matched
        return false;
    }

    /**
     * Set custom data
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set_data(string $key, mixed $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get custom data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_data(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    /**
     * Export node as array (for templates)
     *
     * @param bool $recursive Include children?
     * @return array
     */
    public function to_array(bool $recursive = true): array {
        $array = [
            'key' => $this->key,
            'type' => $this->type,
            'text' => $this->text,
            'url' => $this->url,
            'icon' => $this->icon,
            'order' => $this->order,
            'visible' => $this->visible,
            'active' => $this->active,
            'expanded' => $this->expanded,
            'has_children' => $this->has_children(),
            'is_category' => $this->type === self::TYPE_CATEGORY,
            'is_item' => $this->type === self::TYPE_ITEM,
            'is_separator' => $this->type === self::TYPE_SEPARATOR,
        ];

        // Add icon classes
        if ($this->icon) {
            if (str_starts_with($this->icon, 'fa-')) {
                $array['icon_class'] = 'fas ' . $this->icon;
                $array['has_icon'] = true;
            } else {
                $array['icon_emoji'] = $this->icon;
                $array['has_icon'] = true;
            }
        }

        // Add children if recursive
        if ($recursive && $this->has_children()) {
            $array['children'] = [];
            foreach ($this->get_children() as $child) {
                $array['children'][] = $child->to_array(true);
            }
        }

        // Add custom data
        foreach ($this->data as $key => $value) {
            $array['data_' . $key] = $value;
        }

        return $array;
    }
}
