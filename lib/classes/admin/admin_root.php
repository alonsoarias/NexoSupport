<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Root of the admin settings tree
 *
 * Similar to Moodle's admin_root class.
 * This is the top-level container for all admin categories and pages.
 *
 * @package core\admin
 */
class admin_root implements parentable_part_of_admin_tree {

    /** @var string Name of the root */
    public string $name = 'root';

    /** @var string Visible name */
    public string $visiblename;

    /** @var part_of_admin_tree[] Children of root */
    protected array $children = [];

    /** @var array Errors accumulated during save */
    public array $errors = [];

    /** @var string Current search query */
    public string $search = '';

    /** @var bool Whether to load full tree */
    public bool $fulltree = true;

    /** @var bool Whether tree has been loaded */
    public bool $loaded = false;

    /** @var array Custom default values */
    public array $custom_defaults = [];

    /** @var array Cache for located pages */
    protected array $locate_cache = [];

    /**
     * Constructor
     *
     * @param bool $fulltree Whether to load the full tree
     */
    public function __construct(bool $fulltree = true) {
        $this->visiblename = get_string('administration', 'core');
        $this->fulltree = $fulltree;
    }

    /**
     * Locate a named node in the tree
     *
     * @param string $name Node name to find
     * @return part_of_admin_tree|null Found node or null
     */
    public function locate(string $name): ?part_of_admin_tree {
        // Check cache first
        if (isset($this->locate_cache[$name])) {
            return $this->locate_cache[$name];
        }

        // Search in children
        foreach ($this->children as $child) {
            if ($child->get_name() === $name) {
                $this->locate_cache[$name] = $child;
                return $child;
            }

            $found = $child->locate($name);
            if ($found !== null) {
                $this->locate_cache[$name] = $found;
                return $found;
            }
        }

        return null;
    }

    /**
     * Remove a named node from the tree
     *
     * @param string $name Node name to remove
     * @return bool True if removed
     */
    public function prune(string $name): bool {
        foreach ($this->children as $key => $child) {
            if ($child->get_name() === $name) {
                unset($this->children[$key]);
                $this->children = array_values($this->children);
                unset($this->locate_cache[$name]);
                return true;
            }

            if ($child instanceof parentable_part_of_admin_tree) {
                if ($child->prune($name)) {
                    unset($this->locate_cache[$name]);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search for text in the tree
     *
     * @param string $query Search query
     * @return array Matching nodes
     */
    public function search(string $query): array {
        $results = [];
        foreach ($this->children as $child) {
            $results = array_merge($results, $child->search($query));
        }
        return $results;
    }

    /**
     * Check access - root is always accessible to admins
     *
     * @return bool True if accessible
     */
    public function check_access(): bool {
        return has_capability('nexosupport/admin:viewdashboard');
    }

    /**
     * Root is never hidden
     *
     * @return bool Always false
     */
    public function is_hidden(): bool {
        return false;
    }

    /**
     * Root doesn't show save button
     *
     * @return bool Always false
     */
    public function show_save(): bool {
        return false;
    }

    /**
     * Get root name
     *
     * @return string 'root'
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
     * Add a child node
     *
     * @param string $destinationname Parent name ('root' for direct)
     * @param part_of_admin_tree $something Node to add
     * @param string|null $beforesibling Add before this sibling
     * @return bool True if added
     */
    public function add(string $destinationname, part_of_admin_tree $something, ?string $beforesibling = null): bool {
        // Clear cache
        $this->locate_cache = [];

        // Add directly to root
        if ($destinationname === 'root' || $destinationname === $this->name) {
            if ($beforesibling !== null) {
                foreach ($this->children as $key => $child) {
                    if ($child->get_name() === $beforesibling) {
                        array_splice($this->children, $key, 0, [$something]);
                        return true;
                    }
                }
            }
            $this->children[] = $something;
            return true;
        }

        // Find destination and add there
        $destination = $this->locate($destinationname);
        if ($destination instanceof parentable_part_of_admin_tree) {
            return $destination->add($destination->get_name(), $something, $beforesibling);
        }

        // Destination not found, add to root
        debugging("Could not find admin tree destination '$destinationname', adding to root");
        $this->children[] = $something;
        return true;
    }

    /**
     * Get all children
     *
     * @return part_of_admin_tree[] Children
     */
    public function get_children(): array {
        return $this->children;
    }

    /**
     * Check if has children
     *
     * @return bool True if has children
     */
    public function has_children(): bool {
        return !empty($this->children);
    }

    /**
     * Get all categories (for backward compatibility)
     *
     * @return admin_category[] Categories
     */
    public function get_categories(): array {
        $categories = [];
        foreach ($this->children as $child) {
            if ($child instanceof admin_category) {
                $categories[] = $child;
            }
        }
        return $categories;
    }

    /**
     * Find a page by name (for backward compatibility)
     *
     * @param string $name Page name
     * @return admin_settingpage|null Found page
     */
    public function find_page(string $name): ?admin_settingpage {
        $node = $this->locate($name);
        if ($node instanceof admin_settingpage) {
            return $node;
        }
        return null;
    }

    /**
     * Get template data for navigation
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        $children_data = [];
        foreach ($this->children as $child) {
            if ($child->check_access() && !$child->is_hidden()) {
                if (method_exists($child, 'get_template_data')) {
                    $children_data[] = $child->get_template_data();
                }
            }
        }

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'children' => $children_data,
            'haschildren' => !empty($children_data),
        ];
    }
}
